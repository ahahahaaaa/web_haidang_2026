import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { Workbook, SpreadsheetFile } from '@oai/artifact-tool';

const dir = path.dirname(fileURLToPath(import.meta.url));
const source = JSON.parse(await fs.readFile(path.join(dir,'inventory.json'),'utf8'));
const wb = Workbook.create();
const definitions = [];
const sheetNames = [];
const end = source.pages.length + 1;
const array = value => JSON.stringify(value ?? []);
const literal = value => typeof value === 'string' && /^[=+@-]/.test(value) ? "'"+value : value;
function add(name, headers, rows, options={}) {
  const sheet=wb.worksheets.add(name);
  sheet.showGridLines=false;
  sheetNames.push(name);
  const matrix=[headers,...rows].map(r=>r.map(v=>literal(v ?? '')));
  sheet.getRangeByIndexes(0,0,matrix.length,headers.length).values=matrix;
  const area=sheet.getRangeByIndexes(0,0,matrix.length,headers.length);
  area.format.font={name:'Arial',size:10,color:'#202124'};
  area.format.fill='#FFFFFF'; area.format.rowHeight=32; area.format.wrapText=true;
  area.format.verticalAlignment='top'; area.format.columnWidth=25;
  sheet.getRangeByIndexes(0,0,1,headers.length).format={fill:'#F1F3F4',font:{name:'Arial',size:10,bold:true,color:'#202124'},rowHeight:38,wrapText:true};
  headers.forEach((header,c)=>{
    if (/URL|Title|title|Target_Page_ID|Page_ID|Row_ID|Source_Version|Keyword_ID|Map_ID/.test(header)) sheet.getRangeByIndexes(0,c,matrix.length,1).format.columnWidth=42;
    if (/Prompt|Notes|Evidence|Recommendation|Fact_|Required_|Secondary|Semantic|Entities/.test(header)) sheet.getRangeByIndexes(0,c,matrix.length,1).format.columnWidth=36;
  });
  if (rows.length>5 && name!=='00_HUONG_DAN') {sheet.freezePanes.freezeRows(1);sheet.freezePanes.freezeColumns(1);}
  const dropdowns=options.dropdowns??{};
  for (const [column,values] of Object.entries(dropdowns)) sheet.getRangeByIndexes(1,Number(column),Math.max(1,rows.length),1).dataValidation={rule:{type:'list',values}};
  if (rows.length && name!=='00_HUONG_DAN') {
    const table=sheet.tables.add(`A1:${col(headers.length)}${matrix.length}`,true,'Seo'+name.replace(/[^A-Za-z0-9]/g,''));
    table.style='TableStyleLight1';
    definitions.push({name,sheetName:name,tableName:table.name,rows:matrix.length,columns:headers.length,headers,dropdowns});
  }
  return sheet;
}
function col(n){let s='';while(n){n--;s=String.fromCharCode(65+n%26)+s;n=Math.floor(n/26);}return s;}
const statuses=['HOLD','READY','CLAIMED','PREVIEW','PUBLISHED','NEED_DATA','FAILED'];
const intents=['INFORMATIONAL','COMMERCIAL_INVESTIGATION','TRANSACTIONAL','NAVIGATIONAL','LOCAL_SERVICE'];
const queueHeaders=['Row_ID','Site_ID','Locale','Page_ID','Page_Type','URL','Enabled','Status','Priority','Primary_Keyword','Search_Intent','Secondary_Keywords','Semantic_Terms','Entities','Required_Topics','Required_Internal_Links','Fact_Sources','Notes','Image_URL','Image_Prompt','Image_Alt','Row_Revision','Task_ID','Proposal_ID','Result_URL','Last_Error','Updated_At'];
const keywordHeaders=['Keyword_ID','Cluster_ID','Cluster_Name','Primary_Keyword','Secondary_Keywords','Semantic_Terms','Entities','Search_Intent','Target_Page_ID','Target_URL','Page_Type','Priority','Required_Topics','Required_Internal_Links','Freshness_Class','Fact_Source','Fact_Sources_JSON','Status','Site_ID','Locale'];
const guide=[
 ['Nguồn dữ liệu',`${source.pages.length} URL từ CMS môi trường ${source.environment}, site ${source.site_id}. Không xác nhận dữ liệu hoặc Page_ID production.`],
 ['Ngày trích xuất',source.exported_at],
 ['Cách bắt đầu','Thiết lập kết nối Apps Script và MCP đúng host. Pilot một bài ở chế độ Buộc preview. Chưa có lịch hoặc dòng tự động nào được bật.'],
 ['Chiến lược từ khóa','Nhập keyword và intent trong 15_KEYWORD_SET, xác nhận OWNER ở 17_KEYWORD_MAP, chuyển cả hai sang ACTIVE sau đối soát. Không bịa keyword đã được duyệt.'],
 ['Hàng chờ','18_AUTOMATION_QUEUE: chọn đúng Page_ID, bật Enabled, Status READY. Cột keyword trong queue là bản tham chiếu; sửa chiến lược ở tab 15/17.'],
 ['Ảnh trống','Image_URL trống: Codex tạo prompt phù hợp và render ảnh minh họa, rồi upload vào Media. Thiếu công cụ tạo ảnh thì NEED_DATA.'],
 ['Ảnh có URL','Image_URL có URL HTTPS: nhập ảnh thật vào Media. Cùng domain: dùng lại Media đã biết; không suy đoán đường dẫn.'],
 ['Yêu cầu ảnh','Image_Prompt và Image_Alt tùy chọn. Ảnh AI cần ghi rõ minh họa; không thể hiện là bằng chứng chụp tour thực tế.'],
 ['Luôn publish','Chỉ cấu hình trên server bởi quản trị viên có quyền. Codex báo hoàn tất; server kiểm tra nguồn, ảnh, facts và quyền trước khi tự áp dụng.'],
 ['Buộc preview','Mặc định. Chỉ lưu đề xuất/diff, nội dung public giữ nguyên cho đến khi duyệt và áp dụng riêng.'],
 ['Dữ kiện thiếu','Không tự tạo giá, lịch khởi hành, visa, chính sách, review/rating. NEED_DATA không phải lỗi cần bỏ qua.'],
 ['Phạm vi ảnh bản đầu','Ảnh chèn vào trường rich text được phép. Chưa tự thay hero/cover của trang block-only; cần adapter hoặc người biên tập.'],
 ['Kết quả và ACK','Task_ID, Proposal_ID, Row_Revision, Result_URL, Last_Error, Updated_At do server ghi. Sheet lỗi sau apply: retry đồng bộ, không chạy lại bài.'],
 ['HOLD / READY','HOLD: chưa cho chạy; READY: đã chuẩn bị đầu vào và cho phép nhận việc.'],
 ['CLAIMED','Codex đang giữ lượt xử lý. Không chỉnh input của dòng này; thay đổi làm revision cũ hết hiệu lực.'],
 ['PREVIEW / PUBLISHED','PREVIEW: chờ duyệt trong CMS. PUBLISHED: server đã áp dụng và kiểm tra nội dung theo khả năng hiện tại.'],
 ['NEED_DATA / FAILED','NEED_DATA: bổ sung dữ kiện/công cụ. FAILED: kiểm tra Last_Error và trạng thái CMS; không tự đặt lại READY để ép chạy.'],
 ['Danh sách trong ô','Các trường Secondary_Keywords, Semantic_Terms, Entities, Required_Topics, Required_Internal_Links, Fact_Sources_JSON dùng JSON array.'],
 ['Bảo mật','Không đặt token/secret/API key trong Sheet. Chỉ chia sẻ editor với người được tin cậy. Sheet không có cột cấp quyền publish.'],
 ['Kiểm chứng','Các tab audit/gaps/queue-history đang trống có chủ đích; chưa có audit thành công hoặc điểm SEO được tạo trong lần chuẩn bị này.'],
 ['Đổi môi trường','Không dùng Page_ID/site local trực tiếp trên production. Xuất inventory đúng môi trường và đối soát trước khi bật.'],
];
const gs=add('00_HUONG_DAN',['Mục','Hướng dẫn'],guide);gs.getRange('A1:A22').format.columnWidth=27;gs.getRange('B1:B22').format.columnWidth=105;gs.getRange('A2:B22').format.rowHeight=50;
gs.getRange('B3').setNumberFormat('dd/mm/yyyy hh:mm');
add('01_URL_INVENTORY',['Page_ID','Page_Type','Title','URL','Site_ID','Locale','Owner_Type','Owner_ID','Classification','Source_Version','Inventory_At'],source.pages.map(p=>[p.page_id,p.page_type,p.title,p.url,p.site_id,p.locale,p.owner_type,p.owner_id,p.classification,p.source_version,p.inventory_at]));
const qs=add('18_AUTOMATION_QUEUE',queueHeaders,source.pages.map(p=>['row-'+p.page_id,p.site_id,p.locale,p.page_id,p.page_type,p.url,false,'HOLD','P2','','','[]','[]','[]','[]','[]','[]','','','','','','','','','','']),{dropdowns:{7:statuses,8:['P0','P1','P2','P3']}});
add('15_KEYWORD_SET',keywordHeaders,source.pages.map(p=>{const b=p.brief;return ['kw-'+p.page_id,'','',b.primary_keyword??'',array(b.secondary_keywords),array(b.semantic_terms),array(b.entities),b.search_intent??'',p.page_id,p.url,p.page_type,'P2',array(b.required_topics),array(b.required_internal_links),'','',array(b.fact_sources),'REVIEW',p.site_id,p.locale];}),{dropdowns:{7:intents,11:['P0','P1','P2','P3'],14:['STATIC','SEASONAL','FREQUENT','REGULATORY'],17:['ACTIVE','HOLD','REVIEW']}});
add('17_KEYWORD_MAP',['Map_ID','Keyword_ID','Page_ID','Relationship','Intent','Canonical_Owner','Internal_Link_To','Suggested_Anchor','Cannibalization_Risk','Status','Notes','Site_ID','Locale'],source.pages.map(p=>['map-'+p.page_id,'kw-'+p.page_id,p.page_id,'OWNER',p.brief.search_intent??'',false,'','','','REVIEW','Chưa xác nhận chiến lược/mapping.',p.site_id,p.locale]),{dropdowns:{3:['OWNER','SUPPORTING','AVOID'],4:intents,8:['NONE','LOW','MEDIUM','HIGH'],9:['ACTIVE','REVIEW']}});
const lookup={9:'D',10:'H',11:'E',12:'F',13:'G',14:'M',15:'N',16:'Q'};
for(const [column,sourceColumn] of Object.entries(lookup)) {
 const formulas=source.pages.map((_,i)=>{const term=`INDEX('15_KEYWORD_SET'!$${sourceColumn}$2:$${sourceColumn}$${end},MATCH(D${i+2},'15_KEYWORD_SET'!$I$2:$I$${end},0))`;return [`=IFERROR(IF(${term}="","",${term}),"")`];});
 qs.getRangeByIndexes(1,Number(column),formulas.length,1).formulas=formulas;
}
add('16_PAGE_KEYWORD_AUDIT',['Audit_ID','Page_ID','URL','Primary_Keyword','Check_Type','Rule_ID','Status','Score','Evidence','Recommendation','Requires_Human','Source_Version','Audited_At'],[]);
add('04_CONTENT_GAPS',['Gap_ID','Page_ID','Audit_ID','Gap_Type','Status','Recommendation','Fact_Source','Proposal_ID','Updated_At'],[]);
add('07_CMS_QUEUE',['Queue_ID','Proposal_ID','Page_ID','Site_ID','Operation','Patch_Hash','Expected_Version','Status','Result_Version','Last_Error','Updated_At'],[]);
wb.worksheets.getItem('01_URL_INVENTORY').getRange(`K2:K${end}`).setNumberFormat('dd/mm/yyyy hh:mm');
wb.recalculate();
console.log((await wb.inspect({kind:'table',range:'18_AUTOMATION_QUEUE!A1:K3',include:'values,formulas',tableMaxRows:3,tableMaxCols:11,maxChars:2200})).ndjson);
console.log((await wb.inspect({kind:'match',searchTerm:'#REF!|#DIV/0!|#VALUE!|#NAME\\?|#N/A|#NUM!|#NULL!|#SPILL!|#CALC!',options:{useRegex:true,maxResults:20},maxChars:1200})).ndjson);
for (let i=0;i<sheetNames.length;i++) {
 const sheet=wb.worksheets.getItemAt(i);
 const range=sheet.name==='00_HUONG_DAN'?'A1:B8':(sheet.name==='18_AUTOMATION_QUEUE'?'A1:F4':sheet.name==='01_URL_INVENTORY'?'A1:D4':'A1:F4');
 const png=await wb.render({sheetName:sheet.name,range,scale:1,format:'png'});
 await fs.writeFile(path.join(dir,`${sheet.name}.png`),new Uint8Array(await png.arrayBuffer()));
}
const xlsx=await SpreadsheetFile.exportXlsx(wb);await xlsx.save(path.join(dir,'haidang-seo-automation.xlsx'));
await fs.writeFile(path.join(dir,'tables.json'),JSON.stringify(definitions,null,2));
console.log(JSON.stringify({output:path.join(dir,'haidang-seo-automation.xlsx'),pages:source.pages.length,tabs:sheetNames.length}));
