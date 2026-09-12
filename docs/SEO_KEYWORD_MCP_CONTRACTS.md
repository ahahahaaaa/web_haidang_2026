# Hợp đồng dữ liệu và tích hợp SEO Keyword MCP (đã thay thế)

> **Trạng thái 08/09/2026:** contract Google Sheet v1 trong tài liệu này đã retired và không còn là runtime. Contract thực thi hiện hành nằm trong [SEO AI Direct MCP Optimizer v2](SEO_DIRECT_MCP_OPTIMIZER_PLAN.md) và skill `haidang-travel-seo-post-optimizer`. Giữ tài liệu bên dưới chỉ để tham chiếu quyết định cũ; không cấu hình Apps Script hoặc tool Sheet theo nội dung này.

Phiên bản 1.0 — 06/09/2026 — **thiết kế đề xuất, chưa phải API đang chạy**.

Contract của 8 MCP tool đang triển khai trong module ngày 07/09/2026 được ghi riêng tại [SEO_AI_OPTIMIZE_RUNBOOK.md](SEO_AI_OPTIMIZE_RUNBOOK.md). Bộ contract mở rộng ở đây vẫn là mục tiêu thiết kế, đặc biệt các tool keyword/Sheet chưa được hiện thực đầy đủ.

Đọc cùng [SEO_KEYWORD_MCP_DESIGN.md](SEO_KEYWORD_MCP_DESIGN.md). Tài liệu này chốt tên, dữ liệu, trạng thái, quyền và hành vi lỗi cho implementation. Các payload là ví dụ minh họa; Page_ID, keyword, revision và URL trong ví dụ không xác nhận record thực tế đã tồn tại.

## 1. Quy ước chung

- `contract_version = 1.0`; API/tool JSON dùng `snake_case`; cột Sheet giữ nguyên tên skill. Có lớp serializer mapping giữa hai dạng.
- ID do hệ thống sinh ổn định, không dùng số dòng Sheet. `page_id`, `audit_id`, `proposal_id`, `queue_id` mới dùng ULID; `keyword_id`/`cluster_id` có thể giữ ID nghiệp vụ đã tồn tại sau kiểm tra uniqueness.
- `site_id` là định danh cấu hình môi trường/site. Local, staging, production không dùng chung queue ghi. V1 locale `vi`; khóa dữ liệu vẫn có locale để tránh trộn về sau.
- Thời gian giao tiếp ISO 8601 UTC; CMS hiển thị `Asia/Ho_Chi_Minh` theo chuẩn ngày `dd/mm/yyyy`. Không lưu ngày kiểu `06/09/2026` trong API để tránh ambiguity.
- `source_version`, `strategy_revision`, `rule_version`, `renderer_version`, `policy_version` là opaque string. Client chỉ gửi lại và so khớp, không tự tăng hoặc suy đoán thứ tự.
- `source_version` phải thay đổi khi nội dung public liên quan thay đổi, kể cả dependency. `updated_at` của owner đơn lẻ không đủ làm version.
- Danh sách trong ô Sheet ghi JSON array chuẩn; không tách bằng dấu phẩy vì keyword/entity có thể chứa dấu phẩy. Giá trị rỗng là `[]` hoặc cell rỗng theo kiểu field, không dùng chuỗi `null` tùy ý.
- Phân trang dùng `cursor` + `limit`, trả `next_cursor`. Limit và kích thước payload có cấu hình giới hạn; mặc định đề xuất 50, tối đa 200 cho list metadata, không áp dụng máy móc cho HTML.
- Mọi write có `idempotency_key`; write thay dữ liệu đã có còn cần expected revision/version. Retry cùng key phải cùng payload và cùng actor/site/scope.
- Enum lạ, owner không resolve được, ID trùng hoặc field ngoài schema phải báo validation; không tự đoán và apply.

### Trạng thái tách biệt

| Field | Giá trị |
| --- | --- |
| `assessment_status` | `COMPLETE`, `PARTIAL`, `UNMAPPED`, `NEED_DATA`, `FAILED` |
| `score_grade` | `PASS`, `IMPROVE`, `WEAK`, `FAIL`, hoặc `null` khi chưa có overall score |
| `acceptance_status` | `PASS`, `ACTION_REQUIRED`, `BLOCKED`, `STALE`, `PENDING_SYNC`, `NOT_APPLICABLE` |
| `sheet_sync_status` | `PENDING`, `SYNCED`, `CONFLICT`, `FAILED` |
| `rule.status` | Giữ skill: `PASS`, `WEAK`, `MISSING`, `WRONG_INTENT`, `CANNIBALIZATION`, `OVER_OPTIMIZED`, `NEED_DATA` |
| `rule.assessment` | Mở rộng: `ASSESSED`, `UNASSESSED`, `NOT_APPLICABLE` |
| `gap.action_policy` | Giữ skill: `AUTO_FIX_ALLOWED`, `PROPOSE_ONLY`, `NEED_DATA` |
| `severity` / `priority` | `P0`, `P1`, `P2`, `P3`; hai field dùng cho hai mục đích khác nhau |

Rule chưa assessed không được xuất một `status = PASS`. Trong JSON audit của skill, `score` là số tùy chọn nên **bỏ field** khi chưa đánh giá; `overall_score = null` chỉ thuộc envelope tổng hợp mở rộng. Không thêm enum mới vào `rule.status` làm hỏng consumer cũ.

## 2. Google Sheet: tương thích và mở rộng

Ba tab `15_KEYWORD_SET`, `16_PAGE_KEYWORD_AUDIT`, `17_KEYWORD_MAP` theo skill là bắt buộc. Đọc header để map theo tên, không hard-code cột A/B/C. Thêm cột cuối bảng theo migration có preview, giữ dữ liệu/công thức/format chưa thuộc module.

Trước migration: xác nhận workbook ID + owner, inventory tab/header, backup được phép, kiểm tra duplicate ID và contract version. Nếu workbook cũ dùng tên khác, tạo mapping rõ; không đổi tên tab hoặc ghi đè chỉ vì có tên gần giống.

### 2.1. `15_KEYWORD_SET`

Một row là một keyword set có primary keyword, intent và owner dự kiến; nhiều row có thể chung cluster.

| Cột gốc của skill | Kiểu / quy tắc |
| --- | --- |
| `Keyword_ID` | String, duy nhất trong site/locale |
| `Cluster_ID`, `Cluster_Name` | ID và nhãn nhóm; tên không dùng làm khóa |
| `Primary_Keyword` | Text có dấu, không rỗng khi ACTIVE |
| `Secondary_Keywords` | JSON string array |
| `Semantic_Terms` | JSON string array |
| `Entities` | JSON string array tên/ID entity; metadata chi tiết ở `Requirements_JSON` |
| `Search_Intent` | Intent chuẩn; alias nhãn của Sheet cũ được map qua bảng explicit |
| `Page_Type` | Một trong 16 loại của design; có mapper cho nhãn legacy `Article`/`Tour`/`Destination` |
| `Target_Page_ID`, `Target_URL` | Owner dự kiến và URL hiện hành; ID quyết định identity, URL dùng đối soát |
| `Priority` | `P0/P1/P2/P3` |
| `Required_Topics` | JSON string array; requirement detail được join theo ID/tên |
| `Required_Internal_Links` | JSON string array Page_ID hoặc canonical URL nội bộ đã resolve |
| `Freshness_Class` | `STATIC/SEASONAL/FREQUENT/REGULATORY` |
| `Fact_Source` | Tham chiếu nguồn chính dễ đọc; nguồn từng claim trong `Fact_Sources_JSON` |
| `Status` | Giữ skill: `ACTIVE/HOLD/REVIEW` |
| `Owner` | Người chịu trách nhiệm strategy; không dùng trực tiếp để cấp quyền CMS |
| `Updated_At` | ISO timestamp |

Cột bổ sung: `Site_ID`, `Locale`, `Supporting_Pages_JSON`, `Requirements_JSON`, `Fact_Sources_JSON`, `Row_Revision`, `Row_Hash`, `Approved_Revision`, `Approved_By`, `Approved_At`, `Validation_Errors_JSON`, `Contract_Version`.

`Requirements_JSON` mô tả từng requirement: `id`, `kind`, `label`, `required`, `weight`, `accepted_aliases`, `applicability`, `evidence_requirement`. Không đổi entity string array gốc thành object array khiến schema skill không tương thích.

Row có `Status = ACTIVE` nhưng hash khác approved revision vẫn là bản đang sửa chưa được sử dụng cho apply. Cột approval là projection được bảo vệ; thẩm quyền activation kiểm tra bằng service, không tin text trong ô.

### 2.2. `17_KEYWORD_MAP`

| Cột gốc của skill | Kiểu / quy tắc |
| --- | --- |
| `Map_ID` | String duy nhất |
| `Keyword_ID`, `Page_ID` | Tham chiếu keyword set và registry |
| `Relationship` | `OWNER/SUPPORTING/AVOID` |
| `Intent` | Intent chuẩn, phù hợp set hoặc ngoại lệ đã duyệt |
| `Canonical_Owner` | Boolean; chỉ true trên quan hệ OWNER được chấp nhận |
| `Internal_Link_To` | Page_ID đích; URL nhập tay phải resolve trước activation |
| `Suggested_Anchor` | Text gợi ý, không mặc định exact-match |
| `Cannibalization_Risk` | `NONE/LOW/MEDIUM/HIGH`, có evidence khi khác NONE |
| `Status` | `ACTIVE/REVIEW` |
| `Notes` | Lý do/ngoại lệ, không phải chỉ thị thực thi |

Cột bổ sung: `Site_ID`, `Locale`, `Row_Revision`, `Row_Hash`, `Approved_Revision`, `Approved_By`, `Approved_At`, `Updated_At`, `Contract_Version`.

Ràng buộc activation: với cùng site + locale + primary keyword chuẩn hóa + intent, có đúng một OWNER active. Normalization phục vụ matching giữ bản gốc có dấu; so sánh bỏ dấu chỉ tạo candidate review, không tự đồng nhất các từ có nghĩa khác nhau. `Target_Page_ID` ở tab 15 phải khớp OWNER tab 17 trong cùng strategy revision. Thiếu owner không được activate như một chiến lược hoàn chỉnh.

### 2.3. `16_PAGE_KEYWORD_AUDIT`

Một row là **một rule result trong một audit**, không phải toàn bộ một trang. `Audit_ID` là run ID nên lặp qua nhiều rule; khóa row mới là `Audit_ID + Rule_ID + Subject_Key`. `Subject_Key` phân biệt link/entity/claim nếu cùng rule có nhiều đối tượng.

| Cột gốc của skill | Kiểu / quy tắc |
| --- | --- |
| `Audit_ID`, `Page_ID`, `URL`, `Cluster_ID`, `Primary_Keyword` | Identity và strategy tại thời điểm audit |
| `Check_Type` | `INTENT/METADATA/PRIMARY/SECONDARY/ENTITY/TOPIC/HEADING/LINK/SCHEMA/MEDIA/TRUST/TECHNICAL` |
| `Rule_ID` | ID ổn định trong rule pack |
| `Status` | Enum rule của skill; rỗng nếu chưa assessed và chưa đủ bằng chứng để kết luận |
| `Score` | 0–100 của rule; rỗng khi UNASSESSED/NOT_APPLICABLE |
| `Evidence` | Trích đoạn/lý do dễ đọc; evidence chi tiết ở field mở rộng |
| `Recommendation` | Đề xuất có phạm vi |
| `Auto_Fix` | Boolean eligibility, không phải approval |
| `Requires_Human` | Boolean; v1 true cho thay đổi CMS |
| `Patch_Scope` | Logical field/block/capability dự kiến; read-only nếu không hỗ trợ |
| `Source_Version` | Version snapshot CMS |
| `Agent` | Client/executor hiển thị; actor có thẩm quyền lưu riêng |
| `Audited_At` | ISO timestamp |

Cột bổ sung: `Result_ID`, `Subject_Key`, `Site_ID`, `Locale`, `Assessment`, `Severity`, `Confidence`, `Dimension`, `Dimension_Weight`, `Rule_Weight`, `Evidence_JSON`, `Fact_References_JSON`, `Snapshot_ID`, `Strategy_Revision`, `Rule_Version`, `Renderer_Version`, `Model_Version`, `Overall_Score`, `Score_Grade`, `Acceptance_Status`, `Run_Status`, `Audit_Manifest_Hash`, `Result_Count`, `Contract_Version`.

Row diagnostic chưa có `Status` hợp lệ chỉ thuộc phần mở rộng của Sheet; serializer không xuất row đó như một `page-keyword-audit.schema.json` hợp lệ vì schema skill yêu cầu status. API trả chúng trong `unassessed_requirements`, kèm lý do; các rule có kết luận `NEED_DATA` hợp lệ vẫn xuất theo schema gốc. Row N/A lưu lý do/policy, không giả lập PASS. Consumer cũ chỉ nhận danh sách rule hợp lệ; consumer mới đọc thêm assessment và run completeness.

Các field tổng hợp lặp có kiểm soát trong một Audit_ID để consumer có thể group. Không cộng `Overall_Score` theo số row. Dashboard lấy một kết quả tổng hợp duy nhất của audit hoàn tất mới nhất, không tính trung bình tất cả lịch sử rule.

Audit history append theo Audit_ID mới. Retry upsert cùng Result_ID và cùng hash trả ACK; khác nội dung cho một run đã complete trả conflict. `upsert_page_keyword_audit` không được viết lại run lịch sử đã chốt.

Khi write nhiều batch, `Run_Status = SYNCING` cho tới khi đủ `Result_Count` và khớp manifest hash. Chỉ sau finalization mới `COMPLETE` và trả ACK toàn audit. Consumer bỏ qua run dở, tránh coi một phần rule đã ghi là audit hoàn chỉnh.

### 2.4. Kết nối `03_SEO_AUDIT`, `04_CONTENT_GAPS`, `07_CMS_QUEUE`

Chưa xác minh schema workbook/control module hiện hữu; bảng sau là **logical contract cần map**, không phải cam kết tên cột hiện tại. Adapter phải có compatibility tests trước khi ghi.

| Tab | Logical fields tối thiểu |
| --- | --- |
| `03_SEO_AUDIT` | `issue_id`, `page_id`, `audit_id`, `rule_id`, `severity`, `category`, `evidence`, `source_version`, `lifecycle_status`, `owner`, `updated_at` |
| `04_CONTENT_GAPS` | `gap_id`, `page_id`, `audit_id`, `gap_type`, `status` theo action policy của skill, `recommendation`, `fact_source`, `fact_references`, `lifecycle_status`, `owner`, `proposal_id`, `updated_at` |
| `07_CMS_QUEUE` | `queue_id`, `proposal_id`, `page_id`, `site_id`, `operation`, `patch_hash`, `expected_version`, `strategy_revision`, `approval_id`, `approved_by`, `approved_at`, `status`, `idempotency_key`, `execution_id`, `result_version`, `verification_audit_id`, `last_error`, `updated_at` |

`gap_type`: `KEYWORD/TOPIC/ENTITY/INTERNAL_LINK/FACT/SCHEMA/INTENT`. Gap `status` giữ `AUTO_FIX_ALLOWED/PROPOSE_ONLY/NEED_DATA`; vòng đời dùng field khác: `OPEN/IN_REVIEW/QUEUED/RESOLVED/DISMISSED`. Không đổi action policy thành `RESOLVED` khiến consumer skill mất ý nghĩa.

Một issue lặp nhiều audit giữ stable issue fingerprint theo page + rule + subject; nối lịch sử evidence. Chỉ resolve khi rule recheck xác nhận hoặc reviewer dismiss có lý do. Không resolve vì audit mới chưa chạy tới rule đó.

Nếu control workbook không hỗ trợ đầy đủ approval/version/hash, tắt apply integration và chỉ dùng read/audit/proposal. Không tạo một queue thực thi thứ hai cạnh tranh queue đã được quản trị.

## 3. Dữ liệu lưu trong CMS

Đề xuất các bảng sau; tên là mục tiêu triển khai mới, không khẳng định đã tồn tại. Tận dụng jobs/cache/permissions hiện hành, tránh xây thêm framework queue riêng.

| Bảng | Nội dung chính | Index/ràng buộc |
| --- | --- | --- |
| `seo_optimization_pages` | Page_ID, site/locale, type, owner identity, canonical path, classification, dependencies, latest versions, capabilities | Unique owner identity; canonical conflicts được ghi nhận, không silently merge |
| `seo_optimization_audits` | Run/batch/page, snapshot payload/ref + hash, versions, rule results JSON, score/gates, job/sync state, actor/usage | Index page + captured_at; fingerprint để reuse; run ID duy nhất |
| `seo_optimization_issues` | Issue/gap fingerprint, latest audit/evidence, severity/action policy, lifecycle, assignee | Unique site + fingerprint; index status/severity/page |
| `seo_optimization_proposals` | Page, before/after logical patch, hashes, evidence, facts, impact, source/strategy/policy versions, dry-run result, review decisions | Immutable revision; thay nội dung tạo proposal revision mới |
| `seo_optimization_executions` | Queue/proposal/page, approval ref, idempotency request hash, lifecycle, before/after versions, verification, errors | Unique site + actor scope + idempotency key; một active apply/page |
| `seo_optimization_events` | Append-only actor/action/time, approvals/revocations, apply changelog, policy/config changes | Event ID unique; index entity/request; không cho UI update lịch sử |
| `seo_optimization_outbox` | Durable messages cần sync Sheet, attempts, next_retry_at, last_error, ACK | Message ID/idempotency unique; index status + next_retry_at |

Strategy cache là projection theo workbook + accepted revision, dùng cache hoặc storage hiện hành; không tạo editable keyword tables làm source of truth thứ hai. Snapshot lớn có thể archive vào private storage hiện hành theo retention; SQL giữ hash/ref, không cần một kho dữ liệu mới ngay từ v1.

Inventory discoveries/aliases có thể nằm JSON theo page hoặc bảng con nếu cần unique lookup lớn; chỉ tách bảng khi triển khai chứng minh nhu cầu truy vấn. Không lưu private URL token đầy đủ; redact trước khi persist.

### Source version và invalidation

- Adapter tính normalized source fingerprint gồm owner, dependency versions và renderer/config fingerprint.
- Source token ánh xạ fingerprint đã capture; resolve token phải kiểm tra current fingerprint, không chỉ đọc token cache.
- Event CMS/master-data/media/query dependency thay đổi đánh dấu stale audit/proposal. Duy trì reverse dependency để giới hạn các trang cần recheck.
- Transaction apply phải kiểm tra owner/dependency hiện hành và lock các bản ghi/version liên quan tới patch theo domain writer. Nếu domain writer chưa đảm bảo kiểm tra version nhất quán, capability apply giữ disabled cho field đó.
- Dependency có thể thay đổi hợp lệ sau commit; verification phân biệt nội dung bị áp dụng sai với snapshot đã bị bản mới thay thế (`VERIFY_SUPERSEDED`). Không rollback một update nghiệp vụ mới chỉ để giữ audit cũ.

## 4. Page adapter và snapshot contract

### 4.1. Giao diện adapter đề xuất

```text
enumerate(site, cursor)                 -> page descriptors
resolve(page_id)                       -> owner + route + dependencies
readSource(page_id)                    -> allowlisted source fields + fingerprint
capturePublic(page_id, expected?)      -> sanitized snapshot
capabilities(page_id, actor)           -> readable/writable logical fields
validatePatch(page_id, patch, context) -> field errors + impact + safety class
applyPatch(page_id, patch, context)    -> new source version + change event
invalidationTargets(change)           -> frontsite groups + dependent pages
```

`capturePublic` không nhận URL ngoài registry từ người gọi. Model/relationship phải load có scope published thích hợp, tránh N+1 cho danh sách. `applyPatch` gọi domain writer/validation hiện hành để giữ enum/publish/media/cache behavior.

### 4.2. Payload page tối thiểu và mở rộng

```json
{
  "contract_version": "1.0",
  "page_id": "01K4F0A0000000000000000001",
  "site_id": "haidang-local",
  "locale": "vi",
  "url": "/tour-vi-du",
  "version": "pv_example_42",
  "page_type": "destination",
  "title": "Thông tin du lịch điểm đến minh họa",
  "meta_description": "Nội dung minh họa cho hợp đồng dữ liệu, không phải metadata đã publish.",
  "canonical": "http://localhost:8001/tour-vi-du",
  "h1": "Du lịch điểm đến minh họa",
  "html": "<main><h1>Du lịch điểm đến minh họa</h1><p>Nội dung minh họa.</p></main>",
  "structured_data": [],
  "media": [],
  "internal_links": [],
  "updated_at": "2026-09-06T03:00:00Z",
  "snapshot": {
    "snapshot_id": "01K4F0A0000000000000000002",
    "captured_at": "2026-09-06T03:01:00Z",
    "http_status": 200,
    "classification": "INDEXABLE",
    "robots": "index,follow",
    "render_mode": "server_html",
    "renderer_version": "rv_example_1",
    "parser_version": "parser-1.0",
    "source_consistent": true,
    "dependencies": [{"type": "destination", "key": "example", "version": "dep_example_7"}]
  },
  "capabilities": {
    "read": ["metadata", "main_content", "geo", "structured_data"],
    "write": ["meta_description"],
    "requires_review": true
  }
}
```

Giữ `version` trong page payload theo skill; audit dùng `source_version = page.version`. `title` là HTML title hiệu lực; title nguồn và metadata fallback trace nằm ở field mở rộng `field_sources`. `h1` tương thích là H1 chính; `headings` mở rộng chứa tất cả H1–H6 để phát hiện nhiều H1. `html` đã loại dữ liệu nhạy cảm; snapshot thô nếu cần chỉ nằm private storage có quyền.

Media item mở rộng: `media_id`, `usage_id`, `src`, `alt`, `caption`, `role`, `source_field`, `is_decorative`, `visible`. Link item: `source_region`, `href`, `target_page_id`, `anchor`, `rel`, `http_status`, `canonical_target`, `visibility`, `fragment`. Bằng chứng heading/content: stable block UUID hoặc source field + selector + quote + offsets/hash; selector chỉ có ý nghĩa trong snapshot tương ứng.

### 4.3. Allowlist ghi theo field

| Logical field | Ánh xạ | Guard |
| --- | --- | --- |
| `meta_title`, `meta_description` | Cột CMS nếu model hỗ trợ | Validate độ dài/ngữ nghĩa/facts; ContentCategory read-only tới khi migration riêng sẵn sàng |
| `h1` | Title/name/hero/block của từng adapter | Không giả định cột h1; shared impact phải được duyệt; chỉ bật khi ánh xạ một nghĩa rõ |
| `main_content` | `content`, `body` hoặc rich-text block UUID | Không overwrite toàn bộ blocks/home_config; sanitizer giữ markup được phép |
| `faq_items` | FAQ source đang được public renderer sử dụng | Chỉ Q/A visible, facts có nguồn, không thêm schema ẩn |
| `geo.answer_summary`, `geo.decision_notes` | `geo_config` hiện hành | Giữ shape/normalize và flags; không tự bật block đang bị editor tắt |
| `internal_link` | Node/đoạn cụ thể trong editable rich text | Verify anchor/target/context; không đổi menu toàn site ngầm |
| `media_alt` | Alt của đúng usage nếu có | Không sửa global media metadata nếu tác động nhiều trang chưa được duyệt |
| `schema_syntax` | Chỉ nguồn JSON editable thực sự được runtime dùng | Nếu schema do code tạo, xuất code ticket và không apply qua CMS |

V1 cấm generic patch vào `slug`, `canonical_url`, redirect config, `status`, `published_at`, permission, template code, scripts, media binary, `tour_departures`, rating/review facts, price hoặc master-data IDs. Nếu business cần thay đổi, chuyển workflow tương ứng hoặc proposal/ticket có quyền riêng. New content đề xuất cho trang hiện hữu vẫn qua review; tạo trang public mới nằm ngoài apply v1.

## 5. Audit contract và scoring

Một rule pack được version hóa phải khai báo: Rule_ID, dimension, applicability, severity policy, weight, deterministic/semantic assessor, scoring rubric, evidence requirements, policy action, confidence threshold nếu có. Gold fixtures chứng minh cách chấm 0/partial/100 cho từng rule; không cho model tự chọn trọng số.

Ví dụ một rule chưa đủ nguồn:

```json
{
  "audit_id": "01K4F0A0000000000000000003",
  "page_id": "01K4F0A0000000000000000001",
  "rule_id": "TRUST.FACT_SOURCE",
  "check_type": "TRUST",
  "status": "NEED_DATA",
  "evidence": "Claim về thời hạn xử lý chưa có nguồn được xác nhận.",
  "recommendation": "Bổ sung nguồn và thời điểm xác minh trước khi đề xuất cập nhật claim.",
  "auto_fix": false,
  "requires_human": true,
  "source_version": "pv_example_42",
  "assessment": "UNASSESSED",
  "subject_key": "claim_example_1",
  "severity": "P1",
  "dimension": "trust_freshness",
  "evidence_json": [{"source_field": "content", "quote": "Claim minh họa cần kiểm chứng", "snapshot_id": "01K4F0A0000000000000000002"}]
}
```

Không có score trong ví dụ vì chưa đánh giá được tính đúng của claim. Một rule xác định được “thiếu nguồn bắt buộc” có thể cho điểm 0 về provenance; rule khác về factual accuracy vẫn UNASSESSED. Rule pack cần phân biệt hai phép kiểm tra này, tránh chấm tùy tiện cùng một case.

Kết quả tổng hợp của `seo_page_check`:

```json
{
  "data": {
    "page_id": "01K4F0A0000000000000000001",
    "audit_id": "01K4F0A0000000000000000003",
    "source_version": "pv_example_42",
    "strategy_revision": "strategy_example_12",
    "rule_version": "keyword-audit-1.0",
    "primary_keyword": "du lịch điểm đến minh họa",
    "score": null,
    "status": "NEED_DATA",
    "score_grade": null,
    "assessment_status": "NEED_DATA",
    "acceptance_status": "BLOCKED",
    "sheet_sync_status": "SYNCED",
    "assessed_rules": 28,
    "applicable_rules": 30,
    "issues": [{"rule_id": "TRUST.FACT_SOURCE", "severity": "P1", "action_policy": "NEED_DATA"}]
  },
  "meta": {"request_id": "req_example_1", "contract_version": "1.0"},
  "errors": []
}
```

`score` ở summary là overall 100 điểm. `status` là legacy-friendly summary: `PASS/IMPROVE/WEAK/FAIL` khi complete, hoặc `UNMAPPED/NEED_DATA/PARTIAL/FAILED/STALE` khi chưa dùng score được. Consumer mới phải đọc các status tách biệt, không parse màu badge từ duy nhất một string.

Nếu có cached audit và source_version không khớp hiện tại, trả audit đó với `acceptance_status = STALE` và current version, không gọi kết quả cũ là mới. Semantic/cannibalization partial phải nêu `scope_complete = false`; không kết luận toàn site không có cạnh tranh khi mới crawl một phần.

## 6. MCP tools

### 6.1. Nhóm bắt buộc theo skill

Các input dưới đây cộng thêm `site_id` nếu token cho phép nhiều site; mặc định resolve từ credential scope. Không cho người gọi nâng scope bằng một site_id tùy ý. Tool audit tạo execution/audit record, vì vậy cần quyền audit; read tool không tự khởi chạy AI có chi phí.

| Tool | Input chính | Output | Tác động |
| --- | --- | --- | --- |
| `get_keyword_set` | `status?`, `cluster_id?`, `page_type?`, `cursor?`, `limit?` | Sets + accepted revision + validation/stale flags | Read |
| `get_keyword_cluster` | `cluster_id` | Cluster, owner/supporting, requirements, nguồn | Read |
| `get_page_keyword_map` | `page_id` | Mapping active + revision + conflicts | Read |
| `audit_page_keywords` | `page_id`, `source_version?`, `idempotency_key` | Primary/secondary/metadata rule results hoặc job ID | Ghi audit, không ghi content |
| `audit_page_content` | `page_id`, `source_version?`, `idempotency_key` | Content/headings/topic/entity/GEO report | Ghi audit |
| `audit_search_intent` | `page_id`, `source_version?`, `idempotency_key` | Expected/observed intent, evidence, confidence | Ghi audit |
| `audit_entity_coverage` | `page_id`, `source_version?`, `idempotency_key` | Required/found/missing entities + evidence | Ghi audit |
| `audit_topic_coverage` | `page_id`, `source_version?`, `idempotency_key` | Topic depth/coverage và sections cần bổ sung | Ghi audit |
| `audit_internal_links` | `page_id`, `source_version?`, `idempotency_key` | Link graph slice, broken/missing links, scope completeness | Ghi audit |
| `detect_cannibalization` | `cluster_id?`, `page_ids?`, `inventory_revision?`, `idempotency_key` | Cặp trang, keyword/intent, evidence, risk, limits | Ghi site audit; không đổi owner |
| `detect_overoptimization` | `page_id`, `source_version?`, `idempotency_key` | Vị trí lặp/nhồi, evidence và đề xuất | Ghi audit |
| `propose_content_gap` | `page_id`, `audit_id`, `source_version`, `idempotency_key` | Gap/proposal IDs, policy, missing facts | Ghi 04/proposal; không apply |
| `propose_internal_links` | `page_id`, `audit_id`, `source_version`, `idempotency_key` | Đích + anchor + vị trí + lý do | Ghi proposal; không chèn link |
| `propose_keyword_mapping` | `page_id`, `cluster_id`, `expected_strategy_revision`, `idempotency_key` | Proposed mapping + owner conflicts | Ghi đề xuất REVIEW; không activate |
| `seo_page_check` | `page_id`, `source_version?`, `refresh?`, `idempotency_key?` | Tổng hợp score/status/issues hoặc job ID | Mặc định đọc latest; refresh mới tạo audit |
| `upsert_page_keyword_audit` | `audit_id`, `results`, `expected_manifest_hash?`, `idempotency_key` | Row/batch ACK hoặc conflict | Integration-only, validate provenance |

Các tool `audit_*` và `detect_*` nhận source_version thì phải dùng đúng snapshot version đó, đồng thời công bố nếu đã stale; proposal/apply chỉ nhận current version. Không có version thì server resolve current và ghi vào response. `refresh=true` bắt buộc idempotency và quyền audit; default `refresh=false` không âm thầm gọi AI.

`detect_cannibalization` yêu cầu cluster hoặc page_ids; full-site batch được khởi tạo qua admin/API có scope xác nhận. Không cho một lời gọi không filter vô tình tạo chi phí O(n²). Các kết quả slice có thể được hợp nhất thành audit tổng hợp nhưng chỉ COMPLETE khi cùng source/strategy/rule versions và đủ chiều cần thiết.

### 6.2. Tools hỗ trợ đề xuất thêm

`list_seo_pages`, `get_seo_page_snapshot`, `get_seo_audit_job`, `get_seo_proposal`, `get_seo_capabilities`: đọc registry/snapshot/trạng thái theo quyền. `upsert_keyword_set` và `upsert_keyword_map`: tạo/sửa revision REVIEW với expected revision, không tự ACTIVE.

V1 không expose tool generic `execute_sql`, `update_any_model`, `publish_all` hoặc `apply_arbitrary_patch`. Approve/apply qua CMS authenticated workflow. Nếu control MCP đã có apply tool, chỉ bật sau khi chứng minh nó enforce đầy đủ approval/version/allowlist như CMS action; không cho tool cũ bypass contract mới.

### 6.3. Quyền và provenance của audit write

Agent client không được tự khai `approved_by`, current source version, model usage hoặc `Auto_Fix=true` rồi khiến hệ thống tin. Server kiểm tra snapshot ID, rule pack, execution identity và validator. Audit bên ngoài có thể nhập như `EXTERNAL_UNVERIFIED`; chưa dùng cho acceptance/auto-fix tới khi được recheck hoặc reviewer xác nhận có chứng cứ.

MCP tool annotations chỉ mô tả hành vi, không thay authorization. SDK/schema/auth phải được pin theo phiên bản triển khai thực tế; transport lựa chọn theo [MCP transports](https://modelcontextprotocol.io/specification/2025-11-25/basic/transports). Remote OAuth/provider setup là deployment gate, không dùng một secret chung trong prompt của mọi nhân viên.

## 7. REST/admin endpoints đề xuất

Prefix mới: `/api/v1/seo-optimization`. Tất cả route phải được đăng ký đúng middleware API, có xác thực và policy. Không nhầm với `/v1/seo/*` legacy đang load trong route group khác.

| Method/path sau prefix | Hành vi | Quyền |
| --- | --- | --- |
| `GET /pages`, `/pages/{page}`, `/pages/{page}/snapshot` | Registry/detail/public sanitized snapshot | `index` + page read scope |
| `GET /keywords`, `/keywords/{keyword}`, `/pages/{page}/keyword-map` | Strategy/map và revision | `index` |
| `POST /keyword-revisions` | Submit strategy/map REVIEW với expected revision | `strategy.edit` |
| `POST /keyword-revisions/{revision}/activate` | Validate + activate strategy revision | `strategy.approve` |
| `POST /inventory-runs` | Kiểm kê read-only theo site scope | `audit` |
| `POST /audit-runs` | Audit page_ids hoặc inventory revision + filters đóng băng | `audit` |
| `GET /runs/{run}`, `POST /runs/{run}/cancel` | Progress/result; cancel queued work | `index` / `audit` |
| `GET /gaps`, `GET /proposals/{proposal}` | Gaps/diff/evidence | `index` |
| `POST /proposals` | Tạo proposal từ audit versioned | `propose` + owner read scope |
| `POST /proposals/{proposal}/dry-run` | Validate/preview, không thay content | `propose` |
| `POST /proposals/{proposal}/approve`, `/reject` | Review quyết định theo immutable hash | `approve` + owner edit scope |
| `POST /proposals/{proposal}/apply` | Tạo queue execution sau approval + Sheet ACK | `apply` + owner edit scope |
| `GET /executions/{execution}`, `POST /executions/{execution}/verify` | Kết quả; retry verification không reapply | `index` / `apply` |
| `POST /executions/{execution}/rollback-proposal` | Tạo diff đảo ngược có expected version | `rollback` + owner edit scope |
| `GET /integration/health`, `POST /integration/retry-sync` | Health che secret, retry outbox scope rõ | `settings` |

Tên permission đầy đủ có prefix `admin.seo-optimization.`. Đăng ký `index`, `audit`, `strategy.edit`, `strategy.approve`, `propose`, `approve`, `apply`, `rollback`, `settings` trong registry/seed/account UI. Quyền SEO không tự vượt quyền đọc/sửa model gốc. Role Admin có thể được cấp theo quy tắc repo; role Content không mặc định có approve/apply/settings mới.

Web actions dùng session/CSRF và verified policy hiện hành; API service dùng credential scoped với Sanctum nếu phù hợp runtime. Remote MCP thêm transport auth theo provider được chọn, rồi map principal vào cùng policies. Không coi “đã đăng nhập” là quyền sửa mọi page.

Request async trả HTTP 202 + run/execution ID và status URL; GET đọc lại tiến độ. HTTP 200 cho đọc/synchronous dry-run, 201 cho tạo proposal, 409 cho version/idempotency conflict, 422 validation. Không giữ một HTTP request mở tới khi toàn site audit xong.

## 8. Proposal, approval và apply

### 8.1. Logical patch request

```json
{
  "contract_version": "1.0",
  "proposal_id": "01K4F0A0000000000000000004",
  "page_id": "01K4F0A0000000000000000001",
  "expected_version": "pv_example_42",
  "expected_strategy_revision": "strategy_example_12",
  "expected_policy_version": "policy_example_1",
  "idempotency_key": "request-example-dryrun-1",
  "dry_run": true,
  "operation": "update_seo_fields",
  "patch": [
    {
      "op": "replace",
      "field": "meta_description",
      "expected_value_hash": "example-before-hash",
      "value": "Mô tả minh họa chỉ sử dụng thông tin đã được xác nhận trong nội dung trang.",
      "evidence_refs": ["evidence_example_1"],
      "fact_refs": []
    }
  ]
}
```

`expected_value_hash` ở ví dụ là placeholder; implementation dùng hash chuẩn do server phát sinh. `operation` v1 allowlist: `update_seo_fields`, `update_content_fragment`, `update_geo`, `insert_internal_link`, `update_media_alt`. Mỗi operation có schema patch riêng; không diễn giải `field` thành đường dẫn SQL/JSON tùy ý.

`dry_run=true` chạy đủ validation và impact, có thể ghi audit log/dry-run result, nhưng không sửa content, publish state, chiến lược active hoặc tạo execution apply. Không sử dụng cùng idempotency key khi đổi từ dry-run sang apply vì payload/side effect khác.

Endpoint dry-run cố định `dry_run=true`; endpoint apply cố định `dry_run=false` và từ chối giá trị trái endpoint. Caller không được dùng một flag trong body để biến quyền preview thành quyền apply.

Apply request có `dry_run=false`, `approval_id`, proposal revision/hash đã được server lưu. Server nạp patch từ proposal bất biến và kiểm tra payload nếu client gửi lại; không cho thay `value` sau khi duyệt. Reviewer phải xem diff và tác động shared fields; nếu thay field title/name, liệt kê card/breadcrumb/schema và các trang query liên quan.

### 8.2. Approval có thẩm quyền

Approval event lưu: ID, authenticated reviewer ID, page scope, proposal ID/revision/hash, source/strategy/policy versions, dry-run hash, quyết định, thời gian và lý do. Execution lưu requester, reviewer và worker executor riêng.

V1 mặc định reviewer khác người/service tạo proposal. Nếu tổ chức muốn self-approval cho Admin thì phải bật policy riêng có log và chỉ định phạm vi; không ngầm miễn kiểm tra. Thu hồi quyền/approval trước apply làm queue dừng. Approval hết hiệu lực khi patch/source/strategy/policy đổi; thời hạn duyệt thêm là cấu hình nếu nghiệp vụ cần.

### 8.3. State machines

```text
Proposal:
DRAFT → NEED_DATA hoặc IN_REVIEW → APPROVED hoặc REJECTED
APPROVED → QUEUED
bất kỳ revision đang chờ nào có nguồn/strategy đổi → STALE → revision mới

Execution:
WAITING_SHEET → QUEUED → VALIDATING → APPLYING → APPLIED
APPLIED → VERIFYING → VERIFIED hoặc VERIFY_FAILED hoặc VERIFY_SUPERSEDED
chưa commit: CANCELLED / CONFLICT / FAILED
sau commit: không trả về QUEUED để apply lại

Sheet sync (trục riêng):
PENDING → SYNCED hoặc CONFLICT hoặc FAILED → retry/đối soát
```

Batch audit dùng run metadata bền vững: `run_id`, inventory revision, tập Page_ID đã đóng băng, số queued/running/completed/failed/skipped/cancelled, actor và budget. Run có trạng thái `QUEUED/RUNNING/COMPLETED/PARTIAL/FAILED/CANCELLED`; một audit page lỗi không xóa kết quả page khác. Có thể dùng parent run record trong `seo_optimization_audits` với `record_kind = batch`, `page_id = null`; child dùng `record_kind = page`, `parent_run_id`. Query điểm và Sheet audit export chỉ đọc child page records. Resume chỉ tạo lại công việc chưa xong theo idempotency, không reset run thành công.

`NEED_DATA` cần owner/nguồn bổ sung rồi tạo revision mới. `REJECTED` giữ lịch sử, không xóa. `FAILED` phải ghi `mutation_committed` và execution receipt; nếu không rõ commit, reconcile trước mọi retry. Một patch VERIFIED nhưng audit score chưa PASS vẫn có acceptance ACTION_REQUIRED.

### 8.4. Thứ tự commit và chống lặp

1. Check các feature flags/quyền/approval và Sheet strategy revision; queue intent có ACK.
2. Tạo/lookup execution bằng idempotency scope, so request hash.
3. Lock page, đọc lại source/dependencies và expected field hashes, revalidate patch.
4. Transaction ghi model qua domain writer + execution receipt + before/after changelog + outbox.
5. Commit, invalidate cache, enqueue verification; không gọi AI hoặc Sheet network trong transaction model.
6. Verification capture source mới, đóng issue được xác nhận; Sheet outbox đồng bộ queue/audit và trả ACK.

Nếu response mất sau bước 4, retry cùng key trả execution receipt, không ghi model lần nữa. Nếu Sheet lỗi sau commit, `mutation_committed=true`, sync PENDING/FAILED; không báo “chưa áp dụng” chỉ vì Sheet chưa cập nhật.

Queue consumer không thực thi trực tiếp bất kỳ text patch nào đọc từ ô Sheet. Nó resolve proposal/approval tại CMS, so hash/version với intent đã ACK và kiểm tra quyền hiện tại.

## 9. Apps Script gateway và đồng bộ

### 9.1. Boundary và xác thực

MCP gateway/CMS worker gọi adapter Apps Script qua HTTPS JSON; Apps Script chỉ đọc/ghi workbook đã allowlist. AI không chạy trong Apps Script. Mọi request gắn credential/site/workbook cố định theo cấu hình máy chủ, không nhận một spreadsheet ID tùy ý từ page content.

Thiết kế service-to-service đề xuất dùng signed envelope trong POST body: `key_id`, `timestamp`, `nonce`, `request_id`, `idempotency_key`, `action`, `payload`, `payload_hash`, `signature`. Signature được tính trên serialization chuẩn với HMAC; secret ở server secret store và Script Properties có quyền hạn chế, không đặt vào query/Sheet. Nonce/timestamp được kiểm tra chống replay, rotation key có version. Tham số thời gian cho phép được cấu hình và kiểm thử clock skew.

Đây là application-layer authentication cho service adapter, không thay remote MCP OAuth hay quyền reviewer. Endpoint phải từ chối request chưa xác thực trước khi đọc/ghi workbook. Giới hạn account/deployment của Apps Script và nguy cơ quota exhaustion cần được kiểm tra tại deployment gate; khi chưa có cách triển khai đáp ứng bảo mật, giữ integration write disabled.

Apps Script response cũng gắn request ID, payload/row revision/hash và signature xác minh ở gateway. Credential compromised cần có quy trình revoke/rotate và đối soát outbox. Không log signed body chứa nội dung hoặc secret ở reverse proxy mặc định.

### 9.2. Actions và envelope

Allowlist adapter actions: `health`, `read_keyword_sets`, `read_keyword_maps`, `submit_strategy_revision`, `activate_strategy_revision`, `write_audit_batch`, `finalize_audit`, `upsert_gap`, `upsert_issue`, `write_queue_intent`, `write_queue_result`, `get_request_receipt`.

Mutation response logical payload:

```json
{
  "request_id": "req_example_sheet_1",
  "idempotency_key": "sheet-audit-example-batch-1",
  "status": "ACK",
  "data": {
    "audit_id": "01K4F0A0000000000000000003",
    "batch_id": "batch_example_1",
    "written_result_ids": ["result_example_1"],
    "run_complete": false,
    "sheet_revision": "sheet_example_81"
  },
  "errors": []
}
```

ACK từng batch chưa phải ACK toàn run. `finalize_audit` xác nhận manifest/count mới trả `run_complete=true`. Gateway phân biệt HTTP transport success với `status` của application response; không coi HTTP 200 là chắc chắn write thành công. JSON lỗi quota/conflict có `retryable`, không bắt Apps Script hỗ trợ mọi HTTP status như REST server.

### 9.3. Đồng thời và direct Sheet edits

- Mọi strategy write dùng `expected_row_revision` và `expected_row_hash`; đối chiếu trước khi ghi.
- Apps Script dùng lock ngắn cho các writer do script kiểm soát. LockService cung cấp cơ chế khóa trong script; nó không khóa thao tác chỉnh ô trực tiếp của người dùng. [Google LockService](https://developers.google.com/apps-script/reference/lock/lock-service).
- Các vùng active snapshot, audit, queue, IDs/hash/approval được bảo vệ và chỉ service writer ghi. Người biên tập sửa vùng strategy draft được cho phép; thao tác “Submit for review” tạo immutable candidate revision để activation.
- Nếu chủ workbook sửa trực tiếp vùng active bằng quyền cao, reconciliation phát hiện hash khác accepted revision, đánh dấu conflict và dừng apply. Không dùng last-write-wins để ghi đè dữ liệu của họ.
- Activation tab 15 và 17 là một strategy manifest chung; đọc chỉ dùng manifest COMMITTED có đủ set/map hashes. Nếu write gián đoạn, candidate chưa COMMITTED không được dùng. Không giả định nhiều request Sheet là một database transaction.
- Request receipts/idempotency lưu tại vùng tích hợp được bảo vệ trong workbook hoặc kho receipt đã được phê duyệt; cần cùng lock/write protocol. Tên/range kho receipt được xác nhận lúc onboarding, không chiếm một tab nghiệp vụ có sẵn.
- Khi timeout không rõ kết quả: `get_request_receipt` bằng cùng request/key; nếu chưa xác định vẫn pending, không tạo key mới để ghi lặp.

### 9.4. Quota, batch và phục hồi

Backoff + jitter cho lỗi tạm thời; bounded retry rồi đưa hàng chờ đối soát. Validation/permission/version conflict không retry vô hạn. Jobs audit chạy ở CMS, Sheet chỉ nhận batch kết quả; chunk size/runtime quota là cấu hình theo account thực tế. Google có quota/giới hạn có thể thay đổi, nên kiểm tra trong rollout thay vì hard-code một mức cho mọi tài khoản. [Apps Script quotas](https://developers.google.com/apps-script/guides/services/quotas).

Reconciliation định kỳ khi được bật: đối chiếu accepted strategy hash, missing/duplicate audit rows, incomplete manifests, queue intents chưa có execution receipt và execution committed chưa ACK. Chỉ retry sync với execution đã commit; không áp dụng content lần nữa.

## 10. Lỗi chuẩn và hành vi client

| Code | HTTP gateway | Retry / xử lý |
| --- | ---: | --- |
| `UNAUTHENTICATED` | 401 | Đăng nhập/renew credential theo cơ chế được phép |
| `FORBIDDEN` | 403 | Dừng, hiển thị quyền thiếu; không thử credential khác để vượt quyền |
| `PAGE_NOT_FOUND` | 404 | Refresh inventory; không tự tạo page |
| `PAGE_OUT_OF_SCOPE` | 403 | Không crawl/patch URL ngoài scope |
| `STALE_SOURCE` | 409 | Capture/audit mới, proposal mới và duyệt lại |
| `STRATEGY_CONFLICT` | 409 | Đối soát Sheet revision và owner mapping |
| `POLICY_CHANGED` | 409 | Dry-run/review lại theo policy mới |
| `IDEMPOTENCY_CONFLICT` | 409 | Không retry key đó với payload khác |
| `APPROVAL_REQUIRED` | 409 | Chờ approval hợp lệ tại CMS |
| `UNSUPPORTED_PATCH_SCOPE` | 422 | Chuyển read-only/ticket hoặc adapter được duyệt |
| `NEED_DATA` | 422 cho proposal/apply không hợp lệ | Bổ sung facts; audit report có thể trả 200 với status NEED_DATA |
| `VALIDATION_FAILED` | 422 | Field errors, không mutation |
| `SHEET_SCHEMA_MISMATCH` | 503 | Tắt write integration, sửa mapping có preview |
| `SHEET_SYNC_PENDING` | 409 trước apply | Đợi ACK/đối soát; nếu đã commit chỉ retry sync |
| `RATE_LIMITED` | 429 | Backoff theo retry hint và ngân sách |
| `PROVIDER_UNAVAILABLE` | 503 | Giữ technical report, semantic PARTIAL; không score giả |
| `VERIFY_FAILED` | 200 khi đọc execution | Hiển thị apply receipt và lỗi verify, không mặc định reapply |
| `VERIFY_SUPERSEDED` | 200 khi đọc execution | Nguồn đổi sau commit; audit phiên bản mới, giữ lịch sử |

Envelope lỗi có `request_id`, `code`, `message`, `field_errors`, `retryable`, `current_version` nếu được phép, `execution_id` khi đã tạo và `mutation_committed`. Không trả stack trace, credential, SQL hoặc raw private HTML cho client.

## 11. Cấu hình và feature flags

Các key dưới đây là đề xuất cho `config/seo_optimization.php`, chưa phải biến môi trường đang có:

| Nhóm | Key logic | Mặc định an toàn |
| --- | --- | --- |
| Enablement | `enabled`, `audit_enabled`, `ai_enabled`, `apply_enabled` | Tắt module/write cho tới rollout; có thể bật read-only độc lập |
| Site | `site_id`, `canonical_origin`, `allowed_hosts`, `locale` | Explicit từng môi trường; không đoán production từ APP_URL local |
| Integration | `workbook_id`, `tab_mapping`, `apps_script_endpoint`, secret references | Chưa xác minh → disabled, không tự tạo workbook |
| Strategy | `accepted_revision`, `strategy_cache_ttl`, `require_fresh_strategy_for_apply` | Yêu cầu kiểm tra fresh trước apply |
| AI | `provider`, `model`, `prompt_version`, `confidence_thresholds`, `max_tokens_per_run`, `budget` | Giới hạn rõ, structured output, tắt nếu thiếu credential |
| Jobs | `queue`, `concurrency`, `batch_size`, `timeouts`, `retry_policy` | Bounded, pilot đo trước khi mở rộng |
| Freshness | Interval theo `STATIC/SEASONAL/FREQUENT/REGULATORY`, dependency triggers | Interval cần SEO/business owner duyệt |
| Approval | `require_human_review`, `allow_self_approval`, `allowed_operations` | true, false, allowlist hẹp |
| Storage | Snapshot retention, archive, log redaction | Không giữ PII/secrets; không xóa hồ sơ đang mở |

Kill switch apply ngăn job chưa commit; vẫn cho chạy verification, changelog và sync cho execution đã commit. Kill switch AI không làm hỏng metadata/schema/public response. Cập nhật config/policy ghi actor và policy version.

## 12. File map và gói triển khai dự kiến

Các đường dẫn dưới đây giúp chia việc, **chưa được tạo trong lần cập nhật tài liệu này**:

| Khu vực | Đường dẫn đề xuất / nguồn cần nối |
| --- | --- |
| Module services/adapters | `app/Services/SeoOptimization/` |
| Records/policies/jobs | `app/Models/SeoOptimization*.php`, `app/Policies/`, `app/Jobs/SeoOptimization/` theo convention repo được kiểm tra lúc code |
| API requests/controllers | `app/Http/Requests/SeoOptimization/`, `app/Http/Controllers/Api/SeoOptimization/` |
| Admin UI | `app/Livewire/Admin/SeoOptimization/`, `resources/views/livewire/admin/seo-optimization/` |
| MCP | Business tools dưới namespace riêng; route/provider đăng ký theo package/SDK thực tế đã xác minh |
| Config/schema migrations | `config/seo_optimization.php`, migrations additive cho records mới; category meta migration riêng nếu được duyệt |
| Registry/quyền | `app/Support/Admin/AdminNavigationRegistry.php`, permission/role seed sync và account matrix |
| Public source | `routes/frontsite.php`, `app/Http/Controllers/FrontsiteController.php`, `src/Domains/Cms/Models/` |
| GEO/schema/cache | `app/Services/Frontsite/FrontsiteGeoPresenter.php`, `app/Services/Seo/SitemapBuilder.php`, cache invalidator và theme `haidangtravel` |
| Apps Script | Thư mục integration mới sau khi xác nhận vị trí/versioning/deployment của control module |
| Tests | Unit rule/score/parser; feature inventory/adapters/permissions/apply; integration Sheet/MCP; browser admin/public pilot |

Không đổi root catch-all landing route để phục vụ SEO module. API/admin routes phải được đăng ký tường minh và giữ public IA hiện tại.

### Checklist review contract trước khi code

- [ ] Workbook/control contract đã được xác minh, có compatibility mapping và fixtures.
- [ ] 16 page types và owner/H1/content/FAQ/GEO field mappings được xác nhận trên code đang triển khai.
- [ ] Strategy 15/17 có activation manifest, không đọc revision đang viết dở.
- [ ] Audit row key, run manifest và rule/overall score không bị nhập nhằng.
- [ ] Mỗi MCP tool có input schema, permission, mutation boundary và test lỗi.
- [ ] Phân biệt source stale, strategy conflict, sync pending, verify failure và apply failure.
- [ ] Không có lối apply bypass approval hoặc allowlist qua tool/Sheet/legacy API.
- [ ] Lock/version protocol tương thích với domain writers và master-data sync hiện hành.
- [ ] Budget, retention, freshness và chủ nguồn facts được xác nhận cho môi trường pilot.
- [ ] Rollout và test acceptance khớp phase trong tài liệu design.
