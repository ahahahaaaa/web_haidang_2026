/* Bound this project to the SEO workbook. Configure Script Properties before deploying. */
const QUEUE_HEADERS = ['Row_ID','Site_ID','Locale','Page_ID','Page_Type','URL','Enabled','Status','Priority','Primary_Keyword','Search_Intent','Secondary_Keywords','Semantic_Terms','Entities','Required_Topics','Required_Internal_Links','Fact_Sources','Notes','Image_URL','Image_Prompt','Image_Alt','Row_Revision','Task_ID','Proposal_ID','Result_URL','Last_Error','Updated_At'];

function doPost(e) {
  let lock;
  const props = PropertiesService.getScriptProperties();
  const spreadsheetId = props.getProperty('SPREADSHEET_ID');
  try {
    const secret = props.getProperty('SHARED_SECRET');
    const raw = e && e.postData && e.postData.contents;
    if (!secret || secret.length < 32 || !raw || raw.length > 150000) throw Error('INVALID_REQUEST');
    const request = JSON.parse(raw);
    if (typeof request.payload !== 'string' || !/^\d{10}$/.test(request.timestamp) || !/^[a-zA-Z0-9-]{20,64}$/.test(request.nonce)
        || Math.abs(Date.now()/1000 - Number(request.timestamp)) > 300) throw Error('EXPIRED_REQUEST');
    const signed = request.timestamp+'\n'+request.nonce+'\n'+request.payload;
    const expected = hex(Utilities.computeHmacSha256Signature(signed, secret, Utilities.Charset.UTF_8));
    if (!constantEqual(expected, request.signature)) throw Error('INVALID_SIGNATURE');
    const input = JSON.parse(request.payload);
    if (input.spreadsheet_id !== spreadsheetId) throw Error('WRONG_WORKBOOK');
    lock = LockService.getScriptLock();
    if (!lock.tryLock(20000)) throw Error('BUSY');
    const book = SpreadsheetApp.openById(spreadsheetId);
    useNonce(book, request.nonce);
    const rows = readRows(book, '18_AUTOMATION_QUEUE', QUEUE_HEADERS);
    if (new Set(rows.map(r=>r.Row_ID)).size !== rows.length) throw Error('DUPLICATE_ROW_ID');
    let data;
    if (input.operation === 'next') {
      if (!Array.isArray(input.page_types) || input.page_types.length > 16) throw Error('INVALID_SCOPE');
      const eligible = rows.filter(r=>r.Site_ID === input.site_id && r.Enabled === true && r.Status === 'READY' && input.page_types.includes(r.Page_Type));
      eligible.sort((a,b)=>String(a.Priority).localeCompare(String(b.Priority)) || String(a.Row_ID).localeCompare(String(b.Row_ID)));
      data = {row: eligible.length ? normalized(book, eligible[0]) : null};
    } else if (input.operation === 'row') {
      const row = rows.find(r=>r.Row_ID === input.row_id && r.Site_ID === input.site_id);
      data = {row: row ? normalized(book,row) : null};
    } else if (input.operation === 'event') {
      data = applyEvent(book, rows, input.event_id, input.event);
    } else throw Error('UNKNOWN_OPERATION');
    return json({ok:true,spreadsheet_id:spreadsheetId,data:data});
  } catch (error) {
    return json({ok:false,spreadsheet_id:spreadsheetId,error:'REQUEST_REJECTED'});
  } finally {
    if (lock) lock.releaseLock();
  }
}

function readRows(book, name, required) {
  const sheet = book.getSheetByName(name);
  if (!sheet || sheet.getLastRow() < 1 || sheet.getLastRow() > 10000 || sheet.getLastColumn() > 100) throw Error('INVALID_SHEET');
  const values = sheet.getDataRange().getValues();
  const headers = values[0].map(String);
  if (new Set(headers).size !== headers.length || required.some(h=>!headers.includes(h))) throw Error('INVALID_HEADERS');
  return values.slice(1).map((values,index)=>Object.assign({_row:index+2,_sheet:sheet,_headers:headers},Object.fromEntries(headers.map((h,c)=>[h,values[c]]))))
    .filter(r=>required.some(h=>r[h] !== ''));
}

function normalized(book, row) {
  if (!row.Row_ID || !row.Page_ID || row.Site_ID === '' || row.Locale !== 'vi') throw Error('INVALID_IDENTITY');
  const sets = readRows(book,'15_KEYWORD_SET',['Keyword_ID','Site_ID','Locale','Target_Page_ID','Primary_Keyword','Search_Intent','Status']);
  const maps = readRows(book,'17_KEYWORD_MAP',['Map_ID','Keyword_ID','Page_ID','Relationship','Intent','Status','Site_ID','Locale']);
  const active = sets.filter(s=>s.Status === 'ACTIVE' && s.Site_ID === row.Site_ID && s.Locale === row.Locale && s.Target_Page_ID === row.Page_ID);
  if (active.length !== 1 || !active[0].Primary_Keyword) throw Error('MISSING_OR_AMBIGUOUS_STRATEGY');
  const set = active[0];
  const owners = maps.filter(m=>m.Status === 'ACTIVE' && m.Site_ID === row.Site_ID && m.Locale === row.Locale && m.Keyword_ID === set.Keyword_ID && m.Relationship === 'OWNER');
  if (owners.length !== 1 || owners[0].Page_ID !== row.Page_ID || owners[0].Intent !== set.Search_Intent) throw Error('INVALID_OWNER');
  const competing = sets.filter(s=>s.Status === 'ACTIVE' && s.Site_ID === row.Site_ID && s.Locale === row.Locale
    && String(s.Primary_Keyword).trim().toLocaleLowerCase() === String(set.Primary_Keyword).trim().toLocaleLowerCase() && s.Search_Intent === set.Search_Intent);
  if (competing.length !== 1) throw Error('CANNIBALIZATION');
  const result = {row_id:String(row.Row_ID),site_id:String(row.Site_ID),locale:String(row.Locale),page_id:String(row.Page_ID),page_type:String(row.Page_Type),url:String(row.URL),enabled:row.Enabled === true,priority:String(row.Priority),
    primary_keyword:String(set.Primary_Keyword),search_intent:String(set.Search_Intent),secondary_keywords:list(set.Secondary_Keywords),semantic_terms:list(set.Semantic_Terms),entities:list(set.Entities),
    required_topics:list(set.Required_Topics),required_internal_links:list(set.Required_Internal_Links),fact_sources:list(set.Fact_Sources_JSON),notes:String(row.Notes || ''),
    image_url:String(row.Image_URL || ''),image_prompt:String(row.Image_Prompt || ''),image_alt:String(row.Image_Alt || '')};
  result.row_revision = digest(JSON.stringify({inputs:result,keyword_id:set.Keyword_ID,map_id:owners[0].Map_ID}));
  result.status = String(row.Status);
  result.task_id = String(row.Task_ID || '');
  result.proposal_id = String(row.Proposal_ID || '');
  return result;
}

function applyEvent(book, rows, eventId, event) {
  if (!/^[A-Za-z0-9:_-]{8,150}$/.test(eventId) || !event || !['CLAIMED','PREVIEW','PUBLISHED','NEED_DATA','FAILED'].includes(event.status)
      || !/^[0-9A-HJKMNP-TV-Z]{26}$/i.test(event.task_id || '') || !/^[a-f0-9]{64}$/.test(event.expected_revision || '')) throw Error('INVALID_EVENT');
  const ledger = internalSheet(book,'_SEO_SYNC_EVENTS',['Event_ID','Payload_Hash','Created_At']);
  const ledgerRows = ledger.getDataRange().getValues();
  const hash = digest(JSON.stringify(event));
  const existing = ledgerRows.slice(1).find(r=>r[0] === eventId);
  if (existing) {
    if (existing[1] !== hash) throw Error('EVENT_CONFLICT');
    return {ack:true,event_id:eventId};
  }
  const row = rows.find(r=>r.Row_ID === event.row_id && r.Site_ID === event.site_id);
  if (!row || !row.Enabled || normalized(book,row).row_revision !== event.expected_revision) throw Error('STALE_ROW');
  if (event.status === 'CLAIMED') {
    if (!(row.Status === 'READY' && !row.Task_ID) && !(row.Status === 'CLAIMED' && row.Task_ID === event.task_id)) throw Error('ALREADY_CLAIMED');
  } else if (row.Task_ID !== event.task_id || (row.Status !== 'CLAIMED' && row.Status !== event.status)) throw Error('TASK_CONFLICT');
  const updates = {Status:event.status,Task_ID:event.task_id,Row_Revision:event.expected_revision,Updated_At:new Date()};
  if (event.proposal_id) updates.Proposal_ID = event.proposal_id;
  if (event.result_url) {
    if (!/^https?:\/\//.test(event.result_url) || event.result_url.length > 2048) throw Error('INVALID_RESULT_URL');
    updates.Result_URL = event.result_url;
  }
  if (event.last_error !== undefined) updates.Last_Error = String(event.last_error).slice(0,5000);
  // Write only output cells; never rewrite strategy/input fields or formulas.
  Object.keys(updates).forEach(h=>row._sheet.getRange(row._row,row._headers.indexOf(h)+1).setValue(safeCell(updates[h])));
  SpreadsheetApp.flush();
  ledger.appendRow([eventId,hash,new Date()]);
  return {ack:true,event_id:eventId};
}

function useNonce(book, nonce) {
  const sheet = internalSheet(book,'_SEO_NONCES',['Nonce','Expires_At']);
  const rows = sheet.getDataRange().getValues();
  const now = Date.now();
  if (rows.slice(1).some(r=>r[0] === nonce && new Date(r[1]).getTime() > now)) throw Error('REPLAY');
  const expired = rows.findIndex((r,i)=>i>0 && new Date(r[1]).getTime() <= now);
  if (expired > 0) sheet.getRange(expired+1,1,1,2).setValues([[nonce,new Date(now+600000)]]);
  else {
    if (rows.length >= 10000) throw Error('NONCE_CAPACITY');
    sheet.appendRow([nonce,new Date(now+600000)]);
  }
}
function internalSheet(book,name,headers) {
  let sheet = book.getSheetByName(name);
  if (!sheet) { sheet=book.insertSheet(name); sheet.appendRow(headers); sheet.hideSheet(); }
  return sheet;
}
function list(value) { if (value === '' || value === null || value === undefined) return []; const items=JSON.parse(String(value)); if (!Array.isArray(items) || items.length>50) throw Error('INVALID_LIST'); return items; }
function safeCell(value) { return typeof value === 'string' && /^[=+@-]/.test(value) ? "'"+value : value; }
function digest(value) { return hex(Utilities.computeDigest(Utilities.DigestAlgorithm.SHA_256,value,Utilities.Charset.UTF_8)); }
function hex(bytes) { return bytes.map(b=>('0'+((b+256)%256).toString(16)).slice(-2)).join(''); }
function constantEqual(a,b) { if (typeof b !== 'string' || a.length !== b.length) return false; let diff=0; for(let i=0;i<a.length;i++) diff|=a.charCodeAt(i)^b.charCodeAt(i); return diff === 0; }
function json(data) { return ContentService.createTextOutput(JSON.stringify(data)).setMimeType(ContentService.MimeType.JSON); }
