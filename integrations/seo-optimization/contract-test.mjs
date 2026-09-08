import assert from 'node:assert/strict';
import crypto from 'node:crypto';
import fs from 'node:fs';
import vm from 'node:vm';

// Isolated Apps Script contract test. No network, Google account or CMS writes.
class Sheet {
  constructor(values = []) { this.values = values; }
  getLastRow() { return this.values.length; }
  getLastColumn() { return this.values[0]?.length ?? 0; }
  getDataRange() { return { getValues: () => this.values.map(r => [...r]) }; }
  getRange(row, column) {
    return {
      setValue: value => { this.values[row - 1][column - 1] = value; },
      setValues: values => values.forEach((r, i) => r.forEach((v, j) => { this.values[row - 1 + i][column - 1 + j] = v; })),
    };
  }
  appendRow(row) { this.values.push(row); }
  hideSheet() {}
}
const sheets = new Map();
const book = { getSheetByName: name => sheets.get(name), insertSheet: name => { const sheet = new Sheet(); sheets.set(name, sheet); return sheet; } };
const secret = 'test-only-secret-not-for-deployment-123456';
const context = vm.createContext({
  PropertiesService: { getScriptProperties: () => ({ getProperty: key => key === 'SPREADSHEET_ID' ? 'test-book' : secret }) },
  LockService: { getScriptLock: () => ({ tryLock: () => true, releaseLock() {} }) },
  SpreadsheetApp: { openById: id => { assert.equal(id, 'test-book'); return book; }, flush() {} },
  Utilities: {
    Charset: { UTF_8: 'utf8' }, DigestAlgorithm: { SHA_256: 'sha256' },
    computeDigest: (_, value) => [...crypto.createHash('sha256').update(value).digest()],
    computeHmacSha256Signature: (value, key) => [...crypto.createHmac('sha256', key).update(value).digest()],
  },
  ContentService: { MimeType: { JSON: 'json' }, createTextOutput: text => ({ setMimeType: () => JSON.parse(text) }) },
});
vm.runInContext(fs.readFileSync(new URL('./Code.gs', import.meta.url), 'utf8'), context);
const headers = Array.from(vm.runInContext('QUEUE_HEADERS', context));
const pageId = '01M1ZX00000000000000000000';
const taskId = '01M1ZX11111111111111111111';
const queueRow = { Row_ID: 'row-test', Site_ID: 'test-site', Locale: 'vi', Page_ID: pageId, Page_Type: 'service', URL: 'https://example.com/service', Enabled: true, Status: 'READY', Priority: 'P2', Primary_Keyword: 'Ignored projection' };
sheets.set('18_AUTOMATION_QUEUE', new Sheet([headers, headers.map(h => queueRow[h] ?? '')]));
const keywordHeaders = ['Keyword_ID','Site_ID','Locale','Target_Page_ID','Primary_Keyword','Search_Intent','Status','Secondary_Keywords','Semantic_Terms','Entities','Required_Topics','Required_Internal_Links','Fact_Sources_JSON'];
sheets.set('15_KEYWORD_SET', new Sheet([keywordHeaders, ['kw-test','test-site','vi',pageId,'tư vấn hành trình','LOCAL_SERVICE','ACTIVE','[]','[]','[]','[]','[]','[]']]));
const mapHeaders = ['Map_ID','Keyword_ID','Page_ID','Relationship','Intent','Status','Site_ID','Locale'];
sheets.set('17_KEYWORD_MAP', new Sheet([mapHeaders, ['map-test','kw-test',pageId,'OWNER','LOCAL_SERVICE','ACTIVE','test-site','vi']]));
function signed(operation, extra = {}) {
  const payload = JSON.stringify({ spreadsheet_id: 'test-book', operation, site_id: 'test-site', ...extra });
  const timestamp = String(Math.floor(Date.now() / 1000));
  const nonce = crypto.randomUUID();
  return { payload, timestamp, nonce, signature: crypto.createHmac('sha256', secret).update(`${timestamp}\n${nonce}\n${payload}`).digest('hex') };
}
const post = body => context.doPost({ postData: { contents: JSON.stringify(body) } });
const next = post(signed('next', { page_types: ['service'] }));
assert.equal(next.ok, true);
assert.equal(next.data.row.primary_keyword, 'tư vấn hành trình');
const revision = next.data.row.row_revision;
const event = { row_id: 'row-test', site_id: 'test-site', expected_revision: revision, status: 'CLAIMED', task_id: taskId };
assert.equal(post(signed('event', { event_id: 'claim-test', event })).data.ack, true);
const claimed = post(signed('row', { row_id: 'row-test' }));
assert.equal(claimed.data.row.row_revision, revision, 'Output updates must not change the input revision.');
assert.equal(claimed.data.row.task_id, taskId);
assert.equal(post(signed('event', { event_id: 'claim-test', event })).data.ack, true, 'Same event/payload is idempotent.');
assert.equal(post(signed('event', { event_id: 'claim-test', event: { ...event, status: 'PUBLISHED' } })).ok, false, 'Same key with different payload is rejected.');
const request = signed('row', { row_id: 'row-test' });
assert.equal(post(request).ok, true);
assert.equal(post(request).ok, false, 'Nonce replay must fail.');
assert.equal(post({ ...signed('row'), signature: '0'.repeat(64) }).ok, false);
const terminal = { ...event, status: 'PREVIEW', last_error: '=IMPORTXML("bad")' };
assert.equal(post(signed('event', { event_id: 'complete-test', event: terminal })).data.ack, true);
assert.equal(sheets.get('18_AUTOMATION_QUEUE').values[1][headers.indexOf('Last_Error')], '\'=IMPORTXML("bad")');
assert.equal(sheets.get('18_AUTOMATION_QUEUE').values[1][headers.indexOf('Primary_Keyword')], 'Ignored projection', 'No input/formula rewriting.');
sheets.get('15_KEYWORD_SET').values[1][4] = 'chiến lược đã đổi';
assert.notEqual(post(signed('row', { row_id: 'row-test' })).data.row.row_revision, revision);
assert.equal(post(signed('event', { event_id: 'stale-test', event: { ...terminal, status: 'PUBLISHED' } })).ok, false);
sheets.get('17_KEYWORD_MAP').values[1][2] = 'another-page';
assert.equal(post(signed('row', { row_id: 'row-test' })).ok, false, 'Mismatched keyword owner blocks reads.');
console.log('Apps Script contract: 16 assertions passed (isolated mocks; deployment not tested).');
