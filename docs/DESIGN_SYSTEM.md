# DESIGN_SYSTEM.md

## 1. Design foundation

This system now targets the Haidang Travel frontsite and should no longer inherit the old construction palette.

Primary frontsite palette:
- Primary orange: `#FF6A00`
- Primary orange hover: `#E65F00`
- Primary gradient: `linear-gradient(135deg, #FF6A00, #FF8C00)`
- Secondary blue: `#004A99`
- Accent dark: `#222222`
- Background main: `#F8FAFC`
- Background soft: `#FDFAF7`
- Primary text: `#1E293B`
- Secondary text: `#475569`
- Muted text: `#64748B`
- Border soft: `#E2E8F0`
- Border muted: `#F1F5F9`
- Border strong: `#CBD5E1`
- Success: `#10B981`
- Danger: `#EF4444`
- Price highlight: `#E31C25`
- Rating / warning: `#FFB800`

Extended visual direction:
- travel trust,
- bright conversion accents,
- clean editorial content,
- large visual blocks,
- light premium surfaces,
- modern travel landing rhythm.

---

## 2. Material and surfaces

### No-line rule
Avoid heavy 1px borders for layout separation.
Use surface transitions instead:
- `surface`
- `surface-container-low`
- `surface-container`
- `surface-container-lowest`

### Surface nesting
- foundational canvas: light surface
- inset sections: slightly deeper tinted surface
- cards: white or lowest surface on tinted parent

---

## 3. Color usage

### Orange
Use for:
- primary CTA,
- badges,
- key actions,
- high-conversion emphasis.

### Blue
Use for:
- headings,
- navigation accents,
- trust/authority cues,
- metric numbers,
- brand anchors.

### Gradient rule
Large branded areas should not feel flat.
Preferred angle: subtle 15-degree gradient using core tone → container/variant tone.

Examples:
- CTA button: orange → orange hover/deeper orange
- hero overlays: blue/orange atmospheric blend
- avoid using orange on more than roughly 20% of a screen at once

---

## 4. Typography

Recommended fonts:
- Headings: Plus Jakarta Sans, Poppins, or Montserrat
- Body: Inter or Roboto

Hierarchy:
- hero statements: large, editorial, tight tracking
- body copy: readable, airy, technical
- headings should feel stable and authoritative

Recommended scale:
- H1: 40–56px
- H2 shared section heading (`.frontsite-h2`): `clamp(1.5rem, 1.25rem + .58vw, 1.1rem)` / 30px baseline
- H3: 22–28px
- Body: 16–18px
- Meta: 14px

### Shared section heading for H2

Áp dụng mẫu heading hiện tại của frontsite cho các block section có `H2` + mô tả, theo shared partial `resources/views/themes/haidangtravel/partials/section-heading.blade.php`.

Class chuẩn:

```text
Wrapper: flex flex-col gap-4 max-w-3xl items-start text-left
H2: frontsite-text-reveal frontsite-h2
Description: frontsite-text-reveal max-w-3xl text-sm leading-7 text-slate-600 sm:text-base
```

CSS intent tương ứng:

```css
.section-heading {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 16px;
  max-width: 48rem;
  text-align: left;
}

.section-heading__title {
  font-family: var(--font-heading);
  font-size: clamp(1.875rem, 1.76rem + .58vw, 1.25rem);
  font-weight: 800;
  line-height: 1.12;
  color: #020617;
  text-wrap: balance;
}

.section-heading__description {
  max-width: 48rem;
  font-size: 14px;
  line-height: 28px;
  color: #475569;
}

@media (min-width: 640px) {
  .section-heading__description {
    font-size: 16px;
  }
}
```

Notes:
- Mẫu này ưu tiên một title đậm, gọn, tối màu và một đoạn mô tả phụ màu `secondary text`; không thêm xử lý trang trí rời rạc quanh `H2`.
- Khi cần căn giữa, giữ nguyên scale và màu, chỉ đổi wrapper sang `mx-auto text-center items-center`.

---

## 5. Layout and spacing

- 12-column desktop grid
- strong whitespace
- frontsite section padding phải theo nhịp gọn hơn bản cũ; các block section chính đang dùng chuẩn giảm khoảng 50% vertical padding so với nhịp spacing rộng trước đây
- standard section wrapper nên ưu tiên khoảng `32px` mobile/base và `40px` desktop, tương ứng các class kiểu `py-8` và `lg:py-10`
- hero wrapper hoặc intro band lớn có thể dùng khoảng `40px` mobile/base và `48px` desktop, tương ứng các class kiểu `py-10` và `lg:py-12`
- detail content wrapper hoặc block cần tách lớp rõ hơn có thể dùng khoảng `36px` mobile/base và `44px` desktop, tương ứng các class kiểu `py-9` và `lg:py-11`
- chỉ áp dụng rule giảm padding này cho section wrapper chính của frontsite; không ép giảm đồng loạt padding bên trong card, form, modal, breadcrumb, hoặc footer
- card padding: 20–32px
- 8px spacing rhythm

Use asymmetry carefully:
- hero text may align left while supporting content spans adjacent columns
- project grids may mix large and small cards

---

## 6. Components

### Buttons — “Beams”
- Primary: orange gradient or solid orange, white text, medium radius
- Secondary: white or tinted slab, dark text
- Tertiary: text + icon-end, restrained motion
- shared frontsite search-bar submit buttons keep white text on the orange primary surface; do not switch this CTA back to dark text unless the whole search-bar contrast system is redesigned repo-wide
- CTA liên hệ trong `TourCard` phải dùng nhãn ngắn `Tư vấn` thay cho copy dài như `Nhận tư vấn tour` để nút không bị chật trên mobile và các grid card dày.
- Các nút đóng, dismiss, hoặc icon-only button được thiết kế theo footprint vuông phải luôn giữ `width = height` ở mọi breakpoint; không để line-height, font-size, hoặc content làm méo thành hình chữ nhật.
- Padding tối thiểu cho icon-only square button là `15px`; ưu tiên triển khai bằng `aspect-square` kết hợp `min-width` và `min-height` tương ứng để touch target không nhỏ hơn khoảng `46px`.

### Icons
- frontsite runtime dùng lại Font Awesome thật cho các icon giao diện thay vì thay thế toàn bộ bằng lớp SVG-mask custom; điều này giữ glyph đồng nhất với các class `fa-*` đã có trong Blade/CMS
- các icon hiển thị ở header, footer, card, contact rows, submenu, và slider controls nên tiếp tục dùng cùng hệ Font Awesome hiện tại để tránh sai hình hoặc lệch trọng lượng nét
- không đổi frontsite sang một cơ chế icon khác theo từng view nhỏ lẻ; nếu cần thay bộ icon, phải coi đó là một thay đổi design system cấp repo

### Breadcrumbs
- Breadcrumb của trang điểm đến và tour detail phải phản ánh đúng cây browse tour đang dùng trên frontsite: `Trang chủ` -> scope tour (`Tour trong nước`, `Tour nước ngoài`, hoặc `Tour đoàn`) -> `Vùng miền` -> `Điểm đến hiện tại` -> tên tour khi ở trang chi tiết.
- Không render cấp quốc gia như một node trung gian khi điểm đến hiện tại là điểm đến con của quốc gia; ví dụ tour Bắc Kinh phải render `Trang chủ` -> `Tour nước ngoài` -> `Châu Á` -> `Bắc Kinh` -> `{Tên tour}`, không chèn `Trung Quốc` giữa `Châu Á` và `Bắc Kinh`.
- Breadcrumb của trang quốc gia root tại `/tour-{slug}` vẫn giữ quốc gia như node hiện tại, nhưng không được thêm node nhãn chung `Quốc gia`; ví dụ trang Ấn Độ phải render `Trang chủ` -> `Tour nước ngoài` -> `Ấn Độ`.
- Breadcrumb hiển thị và schema `BreadcrumbList` phải dùng chung một nguồn item để không lệch thứ tự, tên link, hoặc URL canonical giữa UI và JSON-LD.
- Nếu dữ liệu vùng miền hoặc điểm đến thiếu, breadcrumb được phép bỏ cấp thiếu nhưng không được thay bằng nhãn chung như `Điểm đến` khi đã có scope/region/destination cụ thể.

### Header / Navigation
- Header logo ưu tiên dùng image logo thật từ CMS; không render thêm `site_name`, brand text, hoặc tagline bên cạnh logo trong frontsite header.
- Header logo phải có `max-width` hợp lý để không lấn navigation: khoảng `144px` trên mobile, `176px` trên tablet nhỏ, và tối đa khoảng `200px` trên desktop header hiện tại.
- Đây là website du lịch; header public chỉ giữ các nhóm điều hướng travel như `Tour`, `Dịch vụ`, `Blog`, `Liên hệ`, `Về chúng tôi` và không hiển thị nội dung legacy kiểu `Dự án`, `Dự toán`, `Xây dựng`, `Thi công`, `Nội thất`.
- Mục `Tour` trên header là một nhóm điều hướng có submenu cố định gồm `Tour trong nước`, `Tour nước ngoài`, và `Tour đoàn`.
- Mục `Dịch vụ` trên header ưu tiên sinh submenu tự động từ các danh mục dịch vụ có ít nhất một service `published`; các item con thủ công trong menu CMS vẫn được phép nối thêm phía sau.
- Hotline header phải giữ kiểu đơn giản giống CTA phụ: chỉ gồm Font Awesome phone icon và số điện thoại, không thêm label text kiểu `Hotline:` hoặc copy phụ như `24/7`.
- Nút `Nhận tư vấn` ở header phải mở popup form liên hệ dùng chung ngay trên trang hiện tại, không đẩy người dùng sang `/lien-he`.
- Submenu header có thể dùng Font Awesome icon cho từng mục khi icon đã có trong CMS hoặc khi nhóm Tour cần icon định hướng rõ hơn.

### Footer
- Footer là khối trust + discovery cuối trang; không được biến thành nơi nhồi ngẫu nhiên quá nhiều link rời rạc.
- Footer brand ở cột công ty dùng logo thật từ CMS thay cho text tên công ty; ảnh logo dùng thumb responsive, `loading="lazy"`, chiếm full width trong khung logo và áp filter trắng trực tiếp lên PNG, không đặt nền trắng phía sau logo.
- Footer navigation nên hỗ trợ tối đa 2 cột menu lấy từ CMS khi cả `footer` và `footer_secondary` đều có item.
- Trên mobile, 2 menu footer nên ưu tiên đi theo layout 2 cột nếu cả 2 nhóm đều có dữ liệu để tránh một cột mảnh và một khoảng trống lớn.
- Tiêu đề cột footer như `Đi nhanh` không được hard-code; phải lấy từ dữ liệu CMS, ưu tiên dùng `description` của menu tương ứng.
- `Zalo` là một social/support action hợp lệ trong footer và có thể dùng badge/chữ nhận diện ngắn gọn nếu bộ icon hiện tại không có glyph phù hợp.
- Social actions ở footer phải giữ kích thước chạm an toàn, có `aria-label`, và thống nhất surface với các icon social còn lại.
- Cột footer `Kênh hỗ trợ` chỉ nên render tiêu đề và các social/support action; không render thêm các dòng nội dung phụ như `Đặt tour`, `Tư vấn`, hoặc `Email` vì thông tin liên hệ chính đã nằm ở cột company/contact.

### Frontsite contact form
- Frontsite dùng một pipeline liên hệ dùng chung dựa trên `TravelInquiry`; modal popup vẫn là biến thể mặc định cho các CTA `Gửi yêu cầu`, `Nhận tư vấn`, `Liên hệ ngay` và các biến thể tương tự.
- Khi task đã chốt rõ ràng, CTA banner frontsite có thể dùng biến thể form inline rút gọn, nhưng vẫn phải ghi về cùng pipeline `TravelInquiry` và giữ cùng rule feedback/validation với modal.
- Bộ field chuẩn của form `Thông tin đặt tour` gồm: `Loại thông tin`, `Họ tên`, `Email`, `Điện thoại`, `Số khách người lớn`, `Số trẻ em`, `Địa chỉ`, `Tiêu đề`, `Nội dung`.
- `Số khách người lớn` lưu vào `meta.adult_guest_count`; admin chỉ đọc fallback `meta.company_name` khi giá trị cũ là dạng số. `Số trẻ em` dùng trường `party_size` hiện có trong `TravelInquiry`.
- Các nhu cầu theo tour, dịch vụ hoặc liên hệ chung phải đi chung vào pipeline `TravelInquiry`; nếu cần thêm ngữ cảnh thì truyền bằng hidden context hoặc meta, không tách sang form public khác.
- Trường `Loại thông tin` dùng 3 lựa chọn chuẩn: `Du lịch`, `Chăm sóc khách hàng`, `Liên hệ thông tin khác`.
- Modal liên hệ phải mở tại chỗ, có backdrop tối nhẹ, khóa scroll nền khi mở, và đóng được bằng `Esc`, nút close, hoặc click backdrop.
- Copy trong modal ưu tiên ngắn, trực diện, thiên về hành động; tránh thêm các đoạn marketing dài làm loãng thao tác gửi form.
- Trạng thái thành công hoặc lỗi validation của form popup phải hiển thị ngay trong modal để người dùng không bị mất ngữ cảnh trang đang xem.
- Frontsite write form phải ưu tiên submit bằng Ajax khi có JavaScript, không reload toàn trang chỉ để hiển thị lỗi nhập liệu.
- Feedback tổng của form phải nằm ngay trong chính panel/modal đang submit và dùng cùng nhịp surface với form đó.
- Lỗi validation phải hiển thị trực tiếp dưới từng field; khi field bị lỗi cần có trạng thái invalid rõ ràng nhưng vẫn giữ visual sạch của frontsite.
- Redirect + flash message chỉ là fallback cho no-JS hoặc lỗi bất khả kháng; không xem đó là UX chuẩn mặc định.

### Voucher promotion landing
- Landing page voucher là biến thể promotion của custom landing page, nhưng lead vẫn phải ghi vào pipeline `TravelInquiry`; không tạo public lead handler song song.
- Mỗi campaign voucher cần có thời gian áp dụng `starts_at` / `ends_at`, trạng thái bật/tắt, số lượng mã, prefix mã, và frame hình chung dùng cho popup hiển thị mã.
- Khi khách submit form thành công, popup phải hiển thị mã voucher rõ ràng, có tiêu đề campaign, mô tả ngắn, hạn áp dụng và frame hình nếu campaign có cấu hình.
- Mã đã cấp được lưu bằng session cookie theo campaign. Khách quay lại cùng trình duyệt có thể bấm `Xem lại mã`, nhưng markup cached của landing page không được chứa mã cá nhân.
- Khi admin đổi mới bộ mã, campaign phải đổi `code_set_version`; cookie cũ không còn hợp lệ và endpoint xem lại mã phải quên cookie cũ.
- Nếu hết mã hoặc campaign hết hạn, form vẫn được phép ghi nhận lead vào `TravelInquiry`, nhưng không được hứa rằng khách đã nhận mã.
- Admin `Travel Inquiries` phải cho phép lọc lead voucher theo campaign riêng. Filter cần có lựa chọn xem toàn bộ lead voucher và từng campaign cụ thể, đồng thời đọc cả `meta.voucher_campaign_slug` lẫn `meta.voucher.campaign_slug` để bao phủ lead chưa được cấp mã, lead đã cấp mã, và các trường hợp campaign không còn redeemable.
- Admin `Voucher Campaigns` khi mở một campaign phải có filter cục bộ cho bảng mã đã cấp phát theo mã voucher, thông tin khách/lead, trạng thái lead và ngày cấp phát; đây là filter nghiệp vụ của campaign đang mở, không phải filter toàn cục của danh sách campaign.

### Frontsite cache contract
- Frontsite public pages may be response-cached, so UI changes that affect header, footer, menus, sliders, taxonomy rails, listing cards, landing blocks, tour/service/blog detail, or shared media must be treated as cache-sensitive changes.
- Cached markup must not depend on per-user or per-session state, except the CSRF token placeholder restored by `CacheFrontsiteResponse`; session errors, old input, and inquiry flash feedback must continue bypassing the response cache.
- Inquiry modal and inline inquiry variants must remain safe under cached page HTML: submit state, validation errors, success feedback, and field values should be driven by Ajax or fallback redirect state, not by stale server-rendered fragments.
- Any new public CTA, search surface, carousel, or block that renders data from CMS models must confirm the related model changes invalidate the right frontsite cache groups.
- After a cache-sensitive design change, verify the page can serve both `X-Frontsite-Cache: MISS` and `X-Frontsite-Cache: HIT`, and confirm the shared inquiry form still submits with a valid CSRF token.

### Production performance without visual change
- Performance work on the public frontsite must preserve visual parity: do not change layout, copy, color, spacing, icon treatment, CTA hierarchy, menu structure, image selection, or the final rendered position/size of existing support widgets unless the task explicitly asks for a design change.
- The Zalo OA widget is allowed in Theme Settings `End body HTML`, but the widget div must stay as the visible contract and the Zalo SDK must not be pasted as a blocking direct script. Keep the current widget div and load `https://sp.zalo.me/plugins/sdk.js` through the shared deferred loader so the bubble still appears after idle/interaction without shifting page content.
- Do not add CSS overrides for `.zalo-chat-widget` as a first response to performance issues. Only add position/size stabilization after screenshot comparison proves the final visual state is unchanged.
- Laravel Boost browser logging is development tooling, not a production design dependency. Production must not rely on `/_boost/browser-logs`, and disabling Boost must never change the public UI.
- Image conversion regeneration, response-cache warming, and asset build changes are operational performance work; they must not alter CMS-selected media, visible image order, or rendered frontsite component structure.

### Cards — “Modules”
- low-noise surfaces
- generous padding
- no hard dividers
- hover can brighten surface and add subtle accent
- shared frontsite cards for `TourCard` and `BlogCard` must use a vertical stack: full-width image on top, content block below
- do not use the side-by-side media/content card layout as the default public pattern for tour or blog listings
- riêng `TourCard` list khi một danh sách đang render dưới `4` tour thì trên desktop được phép chuyển sang biến thể ngang, mỗi hàng `1` card và card chiếm `100%` chiều ngang để tránh tạo khoảng trống cột; biến thể này dùng bố cục `2 cột` rõ ràng gồm `1 cột ảnh` và `1 cột nội dung`
- rule card ngang này không áp dụng cho homepage; các block tour ở trang chủ vẫn giữ card dọc và grid mặc định để nhịp section ổn định hơn
- ở biến thể ngang, cột ảnh dùng slider riêng để gộp `ảnh đại diện/cover` với gallery ảnh của tour; desktop giữ stage ảnh cố định khoảng `24rem`, thumb nằm trong chính cột ảnh ở mép dưới, có autoplay và có nút điều hướng trước/sau
- thumbnail strip của biến thể ngang chỉ hiển thị ảnh thumb; không render text, badge, mô tả, panel nền, hoặc helper copy trong vùng thumb
- biến thể ngang chỉ hiển thị slider ở desktop; mobile vẫn đọc như card dọc bình thường với ảnh trên và content dưới
- chip `Chủ đề tour` của `TourCard` ngang chỉ nằm trên media/slider bằng icon tag; nếu tour không có `primaryCategory` thì ẩn chip này, không fallback sang trạng thái như `Tư vấn thêm` hoặc `Đang nhận khách`
- card dọc được hiển thị chip `Chủ đề tour` trong content chip row, đặt cùng hàng với `standard_label` và cạnh cụm rating khi `rating_average` + `rating_count` có dữ liệu
- mọi `TourCard` / product card phải hiển thị cụm đánh giá cấu hình từ `Tour.rating_average` và `Tour.rating_count` khi cả hai trường có dữ liệu; không tự bù rating, review count, hoặc star placeholder khi thiếu dữ liệu
- cụm đánh giá cấu hình từ `rating_average` và `rating_count` là rating summary độc lập với review item chi tiết; feature flag `TRAVEL_REVIEWS_ENABLED` không được ẩn cụm rating này trên card, hero, listing, hoặc schema `AggregateRating`
- content chip row của `TourCard` ngang không được render lại `Chủ đề tour` nếu chủ đề đã nằm trên media/slider; giữ hàng content gọn với `standard_label` và rating khi có
- image block should lead the card visually and may contain lightweight overlay chips for high-signal status such as scope, topic, intent, date, or CTA bridge
- content block should start with the title, then follow with scannable information rows that use icons to explain meaning quickly
- `TourCard` information rows should prioritize practical buying fields such as departure place, departure date, duration, transport, and price; slot/contact state may stay as a compact chip instead of becoming an extra meta row
- `BlogCard` information rows should prioritize reading-support fields such as category, published date, reading time, author, and the bridge toward tour/service exploration
- shared frontsite grids that render `TourCard` or `BlogCard` should use the common helper rhythm `gap-2`; do not expand card spacing ad hoc on listing, homepage, landing, or related-content sections
- `TourCard` meta rows should stay compact and inline: keep `Khởi Hành:` as the only explicit label, while duration and transport rely on icon + value without extra label noise
- icons inside cards are explanatory, not decorative; prefer one icon per data row and keep labels short
- CTA and price area should stay at the bottom of the content block so comparison remains stable across cards of different lengths
- in the default non-horizontal `TourCard`, the price cluster should render as 2 lines: line 1 keeps `Giá từ` and the crossed base price on the same row, line 2 shows the current highlighted sale/current price below
- in that default non-horizontal price cluster, `Giá từ` and the crossed base price should read like one aligned label row with a small intentional spacing, not like two disconnected fragments
- in the horizontal `TourCard` variant, the price cluster may stay on a single compact row with the current price and crossed base price aligned together
- the horizontal `TourCard` thumbnail rail should use a subtle custom scrollbar treatment that feels light on the dark media surface; do not leave the browser-default scrollbar styling on this rail
- in related-tour sections, once the shared section heading is rendered, the layout should go straight into the card grid; do not insert an extra helper bar with duplicate label copy or a tour-count badge above the grid
- on tour detail, `Tour liên quan` should switch from grid to the shared card-carousel when it has 4 or more tours; desktop shows 3 tour cards per view, while mobile uses the homepage tour rail rhythm with 1 full card plus roughly 1/5 of the next card visible

### Topic taxonomy rails
- `Chủ đề tour` uses the compact icon-tile rail, not the destination card shell
- each topic tile should contain only the image/icon surface and the title; do not reintroduce count badges, excerpt paragraphs, or CTA rows inside the topic card
- the landing-page block version of this rail may expose `eyebrow`, `title`, and `description`, but any heading part left blank must collapse instead of rendering placeholder or fallback copy
- navigator arrows belong to the rail wrapper and should be optionally suppressible from CMS when a landing page wants the strip to feel quieter

### Trust proof sections
- khối `Vì sao chọn / trust proof` không nên render như 3-4 card paragraph dài có trọng số thị giác ngang nhau
- pattern ưu tiên là `1 featured proof card + nhóm proof card phụ + dải trust metrics` khi dữ liệu thực có sẵn
- mỗi proof card nên đi theo cấu trúc `nhãn nổi bật ngắn -> tiêu đề rõ -> một đoạn chứng minh gọn`
- mỗi proof card có thể cấu hình một icon Font Awesome; frontsite dùng fallback icon theo vị trí khi dữ liệu cũ chưa có icon
- mobile trust proof phải gọn hơn desktop: chỉ giữ icon chính, số thứ tự nhẹ, tiêu đề và một đoạn ngắn; các icon nền lớn chỉ là trang trí desktop/tablet
- landing-page `trust_proof` block có thể nhập thêm dải chỉ số tin cậy gồm `value`, `label`, và icon Font Awesome; chỉ render khi số liệu là thật và đã được nhập trong CMS
- copy trong proof card phải ngắn hơn rich text; ưu tiên câu xác nhận có tính quyết định thay vì mô tả lan man
- homepage `trust` config và landing-page block `trust_proof` phải dùng cùng visual contract để người biên tập không tạo ra hai kiểu `Vì sao chọn chúng tôi` khác nhau
- trust metrics chỉ dùng khi số liệu là thật; nếu không có số liệu tốt thì dừng ở proof cards, không bù bằng counter giả

### Homepage process cards
- the homepage `Quy trình tư vấn` block may attach up to one image per step from the shared CMS Media popup flow
- process-step images are optional; cards must still render cleanly when a step has only number, title, and description
- when a step has an image, the preferred treatment is image on top with the step number overlaid or kept tightly coupled to the media surface
- each step still keeps the explanatory copy as the primary content; do not let decorative imagery overpower the process title or description
- CMS should store the chosen step image as part of the homepage config for that step instead of introducing a separate upload runtime just for the process block

### Tour detail sidebar
- tour detail sticky aside should use one light surface card that combines the starting price block and quick-info rows, instead of a dark pricing card plus a second separate info card
- on mobile, this tour detail price/quick-info aside should appear before the main content stack; from `lg` upward it returns to the right-side sticky column beside the content
- the price cluster starts with `Giá:` on the top row, optional crossed base price aligned opposite, and the main current price highlighted in `Price highlight` red; this compact aside price scale should stay around 70% of the previous large treatment, and when no numeric price exists it should show a truthful `Liên hệ` state instead of forced `/ Khách` copy
- quick-info rows in this card should use one icon plus one inline label/value sentence and prioritize real tour facts already present in CMS such as departure location, next departure date, duration, transport, and standard
- the aside quick-info set should stay compact; do not add a separate `Số chỗ` row by default in this price card
- do not render last-minute promo or urgency panels such as `ưu đãi giờ chót` inside the tour detail sidebar
- the bottom action cluster in this sidebar should keep one direct shortcut with phone icon + label `Gọi` when a contact phone exists, while the filled CTA remains `Đặt tour` and stays tied to the shared inquiry/contact pipeline instead of old copy such as `Liên hệ`, `Ngày khác`, or `Đặt ngay`
- tour detail phone CTA should resolve from `Tour.contact_phone` first; if it is blank, fallback to site hotline/phone from `SiteSetting`, so Sale-owned tours can show the right direct contact number without breaking the existing global hotline behavior
- the tour-detail social share cluster belongs at the bottom of this same price sidebar, after the schedule shortcut if it exists; do not render the tour share block as a separate panel in the main content column
- the compact tour share cluster should not render a heading, intro paragraph, or visible button text; all actions including native share, Zalo, Facebook, Instagram, Telegram, and copy-link render as icon-only square buttons with accessible `aria-label` / `title` text
- compact tour share icons should be visually prominent inside the square buttons, roughly double the standard text-button icon size, while keeping the button footprint compact enough for the 22rem sidebar
- the full social-share panel with heading and explanatory copy remains appropriate for blog detail or other editorial content areas, but the tour sidebar must use the compact variant

### Tour departure schedule block
- the `Lịch khởi hành & giá theo tháng` block on tour detail should stay comparison-friendly and lighter than the older dense table version
- the default desktop table columns are: `Ngày đi`, `Khởi hành`, `Tiêu chuẩn`, `Giá`, `Trạng thái`, and `Tác vụ`
- do not render `Ngày về` and `Số chỗ` as default columns in this desktop schedule table
- desktop schedule tables inside `departure-month-*` panels should not use wide `tracking-[0.18em]` letter spacing; keep table headers and secondary hints on normal tracking so dates, destinations, and duration labels stay easier to scan
- the mobile card version of this same block should follow the same reduced information set and should not reintroduce `Ngày về` or `Số chỗ` as separate rows by default
- if duration context is useful, keep it as a secondary hint inside the `Tiêu chuẩn` column directly under the standard label and before any `pricing_note`; do not place the duration hint inside the `Ngày đi` or `Khởi hành` column, and do not restore a dedicated `Ngày về` column

### Expandable rich text blocks
- long-form frontsite content blocks such as `Chi tiết tour` on tour detail and the editorial intro/content panel on destination or tour taxonomy listing pages should use one shared expandable-rich-text pattern instead of dumping the full rich text height by default
- `Chi tiết tour` on the tour detail page keeps the larger threshold: `400px` on mobile and `800px` on desktop
- editorial intro/content panels on `Điểm đến` and `Chủ đề tour` listing pages use the tighter threshold: `300px` on mobile and `600px` on desktop
- when the rendered content height does not exceed the collapsed threshold, do not show any toggle button or gradient fade
- when the content exceeds the threshold, keep the panel inline on the page, add a soft bottom fade, and show one rounded button directly under the content
- the button copy must use the Vietnamese labels `Xem thêm` for collapsed state and `Thu gọn` for expanded state
- expanding content should reveal the full HTML in place; do not move the rich text into a modal, tab, drawer, or separate page just to handle long copy
- this behavior should stay reusable through the shared Blade partial `resources/views/themes/haidangtravel/partials/expandable-rich-text.blade.php` and the shared frontsite JS controller currently bundled from `resources/js/front/service-detail.js`
- rich text `.theme-copy h2` headings use `font-size: clamp(1.5rem,1.64rem + .42vw,1rem)` with the shared heading font and `line-height: 1.15`

### Taxonomy rating surfaces
- listing pages for `Chủ đề tour` and `Điểm đến` should surface real rating data early in the hero/meta area when `rating_average` and `rating_count` exist
- the preferred hero format is one compact translucent chip in the same visual family as tour detail trust chips, with copy like `4,8/5 • 128 lượt đánh giá`
- this hero rating chip is a quick trust signal only; it does not replace the full review grid further down the page
- when published review items exist, the full review block should still appear after the tour grid/pagination area and before FAQ
- `TRAVEL_REVIEWS_ENABLED=false` chỉ tắt review item surfaces: action/màn `Quản lý đánh giá` trong CMS, full review grid trên frontsite, và schema property `review`; rating summary từ `rating_average` + `rating_count` vẫn hiển thị nếu có dữ liệu
- khi `TRAVEL_REVIEWS_ENABLED=true` và có review item published hợp lệ, summary/schema của khối review ưu tiên tính từ review thật; nếu không có review thật thì dùng rating summary ảo từ `rating_average` + `rating_count`
- khi review grid bị tắt bởi feature flag, khoảng trống layout phải tự khép lại và FAQ/CTA tiếp theo lên theo nhịp bình thường; không render placeholder hoặc copy giải thích việc review đang tắt
- never fabricate rating averages, review counts, or placeholder stars when the current taxonomy has no real data

### FAQ accordions
- frontsite FAQ uses one shared accordion pattern across homepage, service detail, tour detail, SEO page, and landing page blocks
- each FAQ item must render as a full-width row; do not split FAQ into side-by-side cards, uneven columns, or alternating widths
- the question row should use a clear trigger area with the text on the left and a dedicated `+ / -` icon state on the right
- default visual state opens only the first FAQ item; subsequent items remain collapsed until the user interacts
- FAQ accordions should operate in single-open mode so opening one item closes the others
- the open/close affordance should remain obvious on touch and desktop, with a sufficiently large click target and no reliance on hover-only cues
- FAQ answers may be collapsed visually, but should stay in the rendered HTML structure and follow the shared frontsite accordion behavior and accessibility hooks
- FAQ section headings on frontsite do not show a supporting description paragraph
- shared `Điều khoản tour` on tour detail pages follows the same visual rule: heading only, then accordion content
- tour detail should prefer the current tour's own `tour_terms_items`; when that array is empty, fallback to the shared template configured in `Theme Settings`
- FAQ and shared `Điều khoản tour` use a compact accordion density with internal padding reduced by about 50% from the previous roomy spacing

### Tour itinerary accordion
- `Lịch trình chi tiết` on tour detail reuses the shared frontsite accordion mechanics, but it should not inherit the old FAQ-like title row with a large left badge/day chip
- the itinerary trigger row should lead directly with the title content, aligned flush to the start of the text line
- itinerary titles use the orange emphasis text color instead of a standalone badge block; the title span inside `#tour-itinerary-trigger-*` should render at `1rem` on mobile/base and scale back to `1.2rem` from the small breakpoint upward
- keep the right-side `+ / -` control smaller and secondary so the title remains the primary visual anchor
- itinerary items use a compact rhythm: small gaps between cards and trigger/panel padding reduced by about 50% from the older roomy version
- opening a `tour-itinerary-trigger-*` item should scroll the accordion item back to the top of the viewport below the sticky header so the newly opened content is easier to read
- the expanded `tour-itinerary-panel-*` content surface should stay bright, clean, and borderless; avoid inner ring or outline styling that makes the panel feel boxed twice

### Sliders — “Showcases”
- use sliders only for landing hero or media gallery surfaces where sequential visuals add real value
- keep H1, supporting copy, and primary CTA readable in the first render; do not hide critical selling copy inside motion-only layers
- each slide may be image-led or video-led, but text contrast must stay safe when overlay is enabled; if overlay is turned off, the chosen image itself must still preserve legibility
- primary CTA uses the orange emphasis system; secondary CTA stays lighter with white/translucent or tinted treatment
- render slide title, subtitle, description, and buttons only when those values exist
- when a slide sub-part is empty, hide that exact sub-part instead of rendering placeholder copy, empty wrappers, or borrowed fallback content from another field
- slider items support one main/desktop visual, one optional mobile visual, and one optional inner mini visual for the hero side panel
- mobile visuals should be curated separately when the focal subject would crop badly on narrow screens; otherwise the desktop visual may remain the fallback
- the hero inner mini visual is optional and must collapse completely when the item disables `show_inner_media` or when no usable asset exists
- overlay is item-level and must be truly removable; do not leave a phantom dark wash, empty wrapper, or dead visual layer after the toggle is turned off
- gallery cards may use the same item-level visual contract, including responsive images and a softer media overlay treatment
- gallery cards that open media in a lightbox should show a clear `Phóng to` action with a magnifying-glass style affordance; the open action should be obvious before hover and not depend on guesswork
- shared gallery lightboxes should present a visible item counter plus `prev / next` navigation, and support `Esc`, backdrop close, and left/right keyboard navigation on desktop
- when multiple gallery sets exist on the same page, lightbox navigation must stay inside the active gallery collection instead of stepping across unrelated media blocks
- frontsite gallery sliders should go straight into the media shell instead of starting with a separate eyebrow/title/description wrapper by default
- thumbnail rails in frontsite gallery sliders should be image-only previews; do not render item titles, descriptions, numbering, badges, or helper text in or above the rail by default
- on mobile, frontsite gallery sliders should keep the primary media stage full-width and place the thumbnail rail below as a horizontal scroll strip instead of compressing the media into a multi-column thumbnail layout
- taxonomy detail page galleries for `Điểm đến` and `Chủ đề tour` should switch that same thumbnail rail to a vertical right-side stack on desktop using an `8:2` main-stage/thumb-column ratio so the thumb column is one quarter of the main image column, while keeping the mobile bottom-strip layout unchanged
- desktop thumbnail buttons in `frontsite-media-gallery` should keep a minimum height of `80px` so the right-side thumbnail stack stays stable and touch/click targets remain comfortable
- when a thumbnail rail is horizontally scrollable, prefer a slim custom scrollbar that matches the surface tone; avoid leaving the default browser scrollbar if the rail sits inside a polished media shell such as the horizontal `TourCard` gallery
- homepage landing hero dot controls use the compact `service-hero-dot` baseline: inactive dot `1rem x 1rem`, active dot `1.5rem` wide, same height `1rem`, with subtle white border on inactive state and solid white fill on active state
- do not enlarge homepage hero dots ad hoc for visual emphasis; if touch target needs to grow, adjust the outer nav capsule or padding first instead of inflating the dot glyph itself
- if a slide has no media asset, collapse the media panel and keep the text layout balanced instead of showing an empty visual frame
- use arrows, dots, or carousel controls only when there is more than one item
- landing galleries may become a carousel when item count is high enough; small sets should remain a clean static grid
- every visual asset should carry meaningful alt text when the content is image-based
- slider motion uses the shared animate.css runtime but may choose from the broader non-exit animate.css catalog for slider items; do not use exit effects for persistent content because they would hide the slide copy after reveal

### Shared media with Spatie
- frontsite image upload and image picking should go through the shared Spatie Media Library popup flow; do not create separate public/admin upload pipelines for `Tour`, `Điểm đến`, `Chủ đề tour`, `LandingPage`, `Bài viết`, `Danh mục bài viết`, slider, or gallery surfaces unless a new domain requirement is explicitly approved
- primary image-bearing objects in the travel runtime should prefer these collections: `cover` for `Tour`, `Service`, `BlogPost`; `avatar` for `TourCategory`, `Destination`, `Region`, `ContentCategory`; landing-page media collections generated by `LandingPageBlocks` / `LandingPageVisuals`; slider item collections `image`, `mobile_image`, `inner_image`; site-setting collections `logo` and `og_image`
- every frontsite image-driven model should expose the three shared conversion sizes `small`, `medium`, and `full`; design decisions must assume those sizes exist instead of coupling UI directly to original files
- conversion intent is editorial-safe resize, not aggressive crop; current shared bounds are `small = 500x500`, `medium = 1000x1000`, `full = 2400x2400` with fit-max behavior so the source focal area is preserved as much as possible
- shared conversions should preserve the original image format where possible so transparent PNG assets keep their alpha channel instead of being flattened by an implicit JPG output
- shared frontsite conversions should run non-queued so the core public image sizes are generated immediately without waiting on a queue worker
- OG and other social-preview image contexts should use `small`
- all frontsite cards that are not the primary stage of a slider should use `small`; this includes compact topic rails, larger taxonomy cards, standard listing cards, blog category cards, process cards, and static landing-gallery tiles
- gallery thumbnail rails and other compact image-thumb strips should use `small`
- the primary in-page stage/main image of a non-hero slider or gallery should use `medium`
- hero backgrounds, hero side-panel visuals, full-screen or `Phóng to` media, and lightbox/open-zoom targets should use `full`
- tour-detail gallery follows a split-source rule: stage preview uses `medium`, but the `Phóng to` action and lightbox source must use `full`; horizontal `TourCard` sliders follow the same stage/thumb separation
- taxonomy detail page galleries for `Điểm đến` and `Chủ đề tour` are an explicit stage-size exception: their visible frontsite gallery stage should use `full`, while thumbnail rails still use `small`
- frontsite taxonomy detail galleries may choose whether to prepend the taxonomy primary image, but the current `Chủ đề tour` detail page must not inject its `avatar` as the first slide; it should render only the explicit taxonomy `gallery` items in the shared frontsite gallery shell
- when a gallery or slider supports click-to-open media, the visible card/stage should stay optimized for in-page rendering while the opened asset resolves to `full`; do not reuse the same heavy asset for both states by default
- when a model still carries a direct URL fallback such as `cover_image_url`, `logo_url`, or `og_image_url`, runtime may fall back to that URL for continuity, but the preferred design contract remains Spatie-managed media with conversions
- alt text remains required even when media comes from the shared library; editors should provide meaningful alt/caption text for travel imagery instead of relying on file names

### Landing Pages — Visual Slots
- landing pages stay editorial first; hero and gallery are opt-in visual slots, not default clutter
- `hero` and `gallery` may source from `none`, `slider`, or `media`
- `slider` source should prefer an explicitly chosen slider, then fallback to `banner-location`
- `media` source should use curated assets from the shared Media popup flow
- `none` should keep the default static/editorial version of the block
- homepage may keep its bespoke hero until an explicit landing-page override is enabled
- gallery eyebrow, title, and description should not be treated as the default wrapper for frontsite gallery sliders; only render editorial copy above the media set when a page-specific block contract explicitly needs it
- avoid repeating the same asset set in both hero and gallery unless the campaign truly needs it

### CMS Sidebar — Grouped Rails
- CMS navigation uses grouped cards instead of a long flat menu
- each group needs one clear icon, one strong label, and concise child-action labels
- groups must support expand/collapse and remember state per browser
- the current group should feel active before the user opens it; use a tinted surface instead of loud borders
- child actions should read like tasks, for example `Danh sách tour`, `Tạo mới tour`, `Chủ đề tour`
- hide empty groups entirely when the signed-in user has no allowed child action
- keep `Sliders` as its own group, visually parallel to `Landing Pages`, not nested inside it

### Permission Matrix Editor
- account permission UI should mirror the same `group -> action` hierarchy as the sidebar
- `Admin` is a high-trust role with no checkbox clutter; the matrix matters most for `Content`
- default `Content` access should read as blog-first publishing, then optional expanded duties
- `Sale` is a scoped operational role, not a general content role; its account UI should show it as a distinct role option with concise copy that it can only create, view, and edit tours assigned to that same account
- Sale accounts need a `Số điện thoại` field because newly created Sale-owned tours use this phone as the default `Tour.contact_phone`; when the user phone is blank, the tour editor falls back to the current site hotline/phone
- Tour editor should expose `Số điện thoại liên hệ tour` near the operational tour fields and expose `Sale phụ trách` only for non-Sale admins; Sale users should see their ownership as read-only context rather than a selector they can change
- Tour index rows may show `Liên hệ` and `Sale phụ trách` as low-noise meta lines so admin operators can scan ownership and contact routing without adding a new card or modal
- use grouped checkboxes or toggles with short business descriptions, not raw permission-key walls
- when permissions change, the resulting sidebar should feel predictable: only granted actions should appear

### Tour CMS taxonomy selectors
- In `/admin/tours`, the `scope` field is the driver for taxonomy option lists: `Tour trong nước` must show domestic `Chủ đề tour` and domestic `Điểm đến`; `Tour nước ngoài` must show international options; `Tour đoàn` follows its own group where data exists; taxonomy options with empty/null scope are shared and may appear in every scope.
- Tour list filters for `Chủ đề tour` and `Điểm đến` should follow the selected scope and reset when scope changes, so editors do not accidentally filter domestic tours with international taxonomy, or the reverse.
- Tour editor taxonomy selects should refresh when `scope` changes while preserving already-selected values when editing old records, so changing the editor state does not silently drop existing taxonomy links.
- Destination selects in Tour CMS must use the country tree as the visual model: group options by country, show the country root first as `Tên quốc gia (quốc gia)`, then show child destinations under the same group.
- `Điểm đến chính`, `Điểm đến bổ sung`, and the Tour list `Điểm đến` filter may include country roots. A country root is a valid destination option, especially for international tours where the country itself is the selling destination.
- Country root destinations must expose the same page display toggles as regular destinations: `Hiển thị tour trên trang điểm đến` and `Hiển thị blog trên trang điểm đến`. The `/tour-{slug}` country hub must honor those toggles; country blog lists should come from blog posts linked through `country_destination_id` and may include posts for child destinations when that country context is saved.
- In the Destination editor, switching `Là quốc gia root` on must clear or ignore hidden parent fields such as `country_id` and `region_id` before validation. Hidden Livewire state must not keep a stale child-destination country value that blocks saving a country root, and validation copy for this state should be Vietnamese and business-facing.

### Landing Page Builder — Blocks
- landing-page authoring is block-first, not field-tab-first
- start with a template preset, then let the editor add, reorder, duplicate, or remove blocks
- on the homepage system page, each non-hero block may choose a `home_position` fallback around fixed homepage anchors; this helps new campaign banners/widgets land near `home-tour-topics` or `featured-tours` without adding one-off Blade includes
- the homepage visual order after hero is `home_config.layout_order`, a single mixed list of `section:{key}` hardcode sections and `block:{uuid}` dynamic blocks
- the homepage `Blocks` stack is for editing dynamic block content; moving a dynamic block across fixed homepage sections must update `home_config.layout_order`
- non-hero dynamic blocks must still appear in the mixed homepage order when `home_position` is default; use type-based fallback anchors so editor content never silently disappears from the public homepage
- fixed homepage sections after hero use `home_config.layout_order` for their order and `home_config.{section}.is_enabled` for visibility; keep the underlying Blade sections available and hide through config instead of deleting hardcoded sections for layout changes
- hero blocks remain locked as the first visual block because they carry the page H1, primary offer, and first CTA
- block cards should expose the block type quickly and keep the most important controls visible at the top edge
- reorder, duplicate, and delete actions should stay lightweight and not require modal friction
- hero and gallery blocks should clearly indicate whether the source is `slider` or `media`
- query-driven blocks such as `tour_list` and `blog_list` should present filters as editorial targeting controls, not database jargon
- SEO fields stay secondary to content blocks; they should not visually overpower the page builder
- cloning a landing page should feel like starting from a campaign kit, with media and block order preserved for editing

### Inputs — “Blueprints”
- neutral background
- ghost border
- stronger orange focus state on frontsite
- frontsite select controls use one shared enhanced layer: `Tom Select`
- frontsite date controls use one shared enhanced layer: `Flatpickr`
- Admin CMS date/time fields must use the Vietnamese display contract `dd/mm/yyyy`; when time is needed, append it after the date as `dd/mm/yyyy HH:mm` or `dd/mm/yyyy HH:mm:ss`.
- Admin editors and filters should not expose U.S. ordering such as `mm/dd/yyyy`; helper text, placeholders, table cells, detail panels, exports, and validation messages should all reinforce `dd/mm/yyyy`.
- Internal persistence, Livewire state, API payloads, and query filters may normalize to ISO formats such as `YYYY-MM-DD` or ISO 8601, but that normalization must stay behind the UI boundary.

### Homepage Search Bar
- the homepage search block directly below the hero should use the same shared search-bar component language as the listing pages
- structure: search icon, one text field, one submit button, grouped inside one rounded light surface
- the main homepage search field is a text query for tours; do not replace it with quick-intent rails, date, budget, or select-driven destination pickers
- the submit action should send users into the existing tour browse/search flow instead of creating a homepage-only filter state
- the bar should feel light, premium, and utility-first: white surface, restrained shadow, dark text, and one clear orange CTA
- mobile should stack the grouped search controls cleanly without changing the underlying one-query-one-submit contract

### Listing Search Bar
- homepage and public listing pages should use one shared compact search bar surface instead of bespoke filter bars per page family
- the default composition is: search icon, one text field, one submit button, grouped inside one rounded light surface
- `/tour-trong-nuoc` and `/tour-nuoc-ngoai` may use the extended composition: search icon, text query field, `Chủ đề` select, submit button, still grouped inside the same rounded light surface
- on those 2 scope pages, the `Chủ đề` select uses the shared `Tom Select` styling, keeps the outer wrapper in the compact secondary slot with a desktop minimum width around `18rem`, but lets the desktop `Tom Select` control itself expand to around `21rem` so long topic labels do not get clipped; on mobile it still stacks between the text field and submit button
- this search bar pattern applies across blog, service category/listing, tour scope pages, tour taxonomy pages, destination pages, region pages, and country pages
- avoid adding extra select/date/budget controls into this shared listing bar beyond that controlled `Chủ đề` exception unless the product rule is explicitly changed repo-wide

### Blog listing category grid
- `/blog` không cần một featured article block riêng như stage mở đầu; sau intro và category rail có thể đi thẳng vào article grid nếu đó là flow gọn hơn cho page
- khối `Danh mục` ở `/blog` chỉ render category blog cấp 1 và dùng visual shell tham chiếu từ cụm `Chủ đề tour` homepage
- cụm này không dùng heading riêng hay `theme-panel` wrapper có border viền bao ngoài
- trên mobile, nếu root category count `<= 2` thì cụm này dùng grid `2` cột; nếu count `> 2` thì chuyển sang shared carousel với `2` card mỗi lượt
- trên desktop, nếu root category count `<= 6` thì cụm này hiển thị như một hàng card canh giữa; nếu count `> 6` thì chuyển sang shared carousel với `6` card mỗi lượt
- khi grid danh mục blog đang hiện diện, wrapper intro/editorial phía trên nó nên dùng full width của page content container thay vì bị bó bởi một `max-width` nhỏ hơn kiểu `max-w-5xl`
- category con không render trực tiếp trong cụm `/blog` này; việc lọc tiếp theo vẫn đi qua branch logic sau khi user bấm root category hoặc query category cụ thể

### Blog detail content wrapper
- khối wrapper chính của blog detail bao quanh TOC, rich text body, `Thông tin bài viết`, và FAQ nên dùng full width của content container hiện tại
- không bó hẹp wrapper này bằng `max-width` nhỏ hơn kiểu `max-w-5xl`, trừ khi một task sau đó định nghĩa lại layout detail page một cách rõ ràng

### Pagination copy
- summary copy của frontsite pagination phải dùng tiếng Việt theo format `Hiển thị X đến Y trong tổng số Z kết quả`
- label điều hướng mặc định dùng `Trước` và `Tiếp theo`

### Homepage discovery rails
- homepage taxonomy discovery should use two lightweight browse-entry rails when data is available: `Chủ đề tour` and `Điểm đến nổi bật`
- both rails use the same image-led card shell as the current destination slider pattern: media on top, count badge over media, title, short summary, and CTA line inside the content block
- `Chủ đề tour` does not fall back to the older compact avatar-chip card anymore; it should follow the same card composition as `Điểm đến nổi bật`, with only accent tone or fallback gradient adjusted when needed
- each card links directly to its canonical taxonomy hub such as `/danh-muc-tour/{slug}` or `/tour-{slug}`; do not convert these rails into modal triggers or homepage-only filter widgets
- when a rail follows the current homepage slider treatment, wrap the heading, prev/next controls, and card track inside the shared `data-card-carousel` container so the existing carousel JS can bind correctly
- shared homepage card-carousels may auto-advance when there is real overflow, but autoplay must pause on hover, focus, and direct user interaction
- mobile should keep the rail horizontally scrollable with stable card width; desktop may reuse the same shared card-carousel treatment when the section opts into slider mode, while still falling back to plain server-rendered link cards without JS
- mobile card-carousel variants inside taxonomy tab panels should show one primary item plus a visible sliver of the next item, roughly `1 + 1/5`, instead of forcing exactly one full-width card that hides overflow affordance
- taxonomy cards may show avatar or cover imagery from CMS; if no usable media exists, fallback to a short 2-letter monogram inside the shared media area
- only show destinations that currently have published tours so the homepage does not send users into thin or empty destination hubs

### Homepage featured-tour tabs
- `data-home-featured-tabs` uses the same mobile tablist contract as taxonomy tabs: `home-featured-tab-international`, `home-featured-tab-domestic`, and `home-featured-tab-group` scroll horizontally on mobile without creating page-level horizontal overflow
- `home-featured-tab-*` buttons use `text-sm` at every breakpoint; mobile compactness comes from button height/padding, not from shrinking the label type to `text-xs`
- from tablet/desktop upward, the featured-tour tablist may wrap normally so the three scope buttons sit with the existing homepage rhythm
- featured-tour rails that opt into shared desktop slider mode with `data-desktop-slider="true"` must set `--desktop-card-width` explicitly; `--desktop-columns` only controls the inactive/static grid state, while the active slider track uses `--desktop-card-width`
- for a 4-card desktop viewport/row, set `--desktop-columns: 4` together with `--desktop-card-width: calc((100% - 3rem) / 4)` because the shared carousel gap is `1rem` and four cards create three gaps
- the `/diem-thuong` featured-tour slider follows this 4-card desktop contract

### Region taxonomy tabs
- `region_taxonomy_tabs` uses a distinct region-first compare layout: region tablist on top for mobile and a left sidebar tablist on desktop
- when `region_taxonomy_tabs.card_source_type = destination`, country root records are allowed to render in the same card grid as regular destinations when they have published tours in the active region/scope, including through published child destinations; visually they keep the destination-card treatment rather than becoming a separate country rail
- mobile tablists for `region_taxonomy_tabs` must be horizontal scroll rails with stable button widths and no page-level horizontal overflow; the grid item that owns the rail must allow shrinkage (`min-width: 0`) before desktop switches to the left sidebar tablist
- `tour_taxonomy_tabs` tablists use the same mobile horizontal-scroll treatment, then may wrap normally from tablet/desktop upward
- `tour-taxonomy-tab-*` buttons use `text-sm` at every breakpoint; do not downgrade these tab labels to `text-xs` for mobile-only compactness
- the active panel card should be image-led with the media filling the full card shell; the small `H3` title sits inside the image surface near the bottom, not outside the card
- `region_taxonomy_tabs` active panel cards should resolve taxonomy imagery at `medium` instead of the default card-level `small`, both for dynamic landing-page widgets and the homepage fixed section.
- the title and description share one bottom translucent overlay block inside the image; in the resting state the block height clamps to the title only, then expands with motion on hover to reveal the description without reserving extra height beforehand
- the description layer should stay out of normal flow in the resting state so the card never shows a blank translucent gap under the title before hover
- desktop behavior for these cards is `3-up static grid` when the active tab has `3` or fewer items, and switches to `3-up slider` only when the active tab exceeds `3` items

### Admin form actions
- backend admin form submit feedback should use `SweetAlert2` instead of relying only on inline flash banners
- submit flow should cover three states: loading modal while request is in-flight, success toast after save, warning/error alert when validation fails
- destructive actions can reuse the same visual system so confirm dialogs stay consistent with save feedback
- create/edit forms that exceed one viewport should use a fixed-bottom action bar on mobile and a sticky bottom action bar on larger screens
- action bars must leave bottom padding for long forms so inputs are not hidden behind the controls
- keep one clear primary action in red gradient; secondary actions should stay outline/tinted and never visually compete with save
- validation summary should appear near the top of the form in addition to field-level errors when possible, so users know immediately why submit failed
- Blog CMS `slug` input must keep field-level validation visible: blank slug may auto-generate and receive numeric suffixes for uniqueness, but a manually entered duplicate slug should show an inline validation error instead of surfacing a database unique-index failure
- whenever adding a new `upload image` field or `Quill` editor in admin, verify the added component still works correctly with the shared `Media popup` flow and does not bypass the existing media library behavior
- after integrating any new `upload image` field or `Quill` editor, run a CMS admin regression check for all related components to confirm media selection, insertion, replacement, preview, and save flows still behave correctly across create/edit screens
- always regression-test `Media popup` and `Quill` flows after switching admin views with `wire:navigate` (for example from `/admin/theme-settings` to a blog/service/project detail screen), because popup bindings must continue working after Livewire swaps the page DOM
- any shared admin JS that powers `Media popup`, image pickers, or `Quill` integrations must be resilient to DOM re-render/replacement and must re-bind correctly after admin view navigation

### Project stats
- large blue numbers
- restrained labels

---

## 7. Imagery

Prefer:
- real destination photography,
- transport / departure / group-travel moments,
- itinerary or place-based storytelling with atmospheric travel context,
- campaign imagery that still feels editorial instead of poster-noisy,
- image-led storytelling.

Avoid:
- construction or real-estate imagery that no longer matches the travel runtime,
- generic business stock photos when destination imagery is available,
- noisy collage layouts,
- excessive sliders.

---

## 8. Interaction

- hover lift or brighten, not dramatic bounce
- fade/translate reveals acceptable if restrained
- CTA pulse only sparingly
- glass/backdrop effects only where they add clarity (e.g. fixed nav)
- admin feedback motion should be quick and quiet: loading states reassure, success toasts auto-dismiss, warnings stay confirm-driven

### Frontsite Header Submenu Flyout Navigation
- Desktop menu level 1 is laid out horizontally on the header bar.
- Level 2 appears in a clean white dropdown container directly below level 1 on hover.
- Level 3+ submenus render in a **separate flyout container extending horizontally to the right (`left-full top-0 pl-2`)** when hovering over a parent level 2 item that has children.
- Do not collapse or inline all submenu levels into a single shared container block; preserve clean horizontal flyout panel separation.
- Mobile menu keeps a clean vertical accordion structure using `<details>` / `<summary>`.

### Text reveal system

- Frontsite text motion keeps restrained defaults for normal sections: `fadeInDown`, `fadeIn`, and `fadeInUp`.
- Slider items may override those defaults through the shared motion runtime with a broader non-exit animate.css catalog such as fade, zoom, slide, bounce, rotate, flip, back, lightspeed, and special entrance effects.
- Use shared hooks `frontsite-text-reveal`, `data-reveal`, `data-reveal-effect`, `data-hero-text`, and `data-hero-effect` instead of one-off inline animation logic.
- Hidden reveal state should combine `opacity: 0` with a small downward offset so sections visibly “arrive” on scroll.
- Hover text motion should be subtle: prefer a short `translateX` on headings within cards or linked panels instead of scaling text.
- The current shared motion controller lives in `resources/js/front/service-detail.js` and is bundled through `resources/js/app.js`.
- Hero slides, FAQ open states, card carousel sync, and text reveal should continue using this shared motion layer instead of page-specific custom scripts.
- Landing hero overrides and gallery carousels should reuse the same shared motion hooks instead of introducing separate animation systems.
- Exit-only animate.css classes are intentionally excluded from slider CMS choices because persistent copy must remain visible after animation completes.
- Keep motion rules documented inline here; do not depend on a separate legacy text-reveal document.

---

## 9. Do / Don’t

### Do
- embrace whitespace,
- use tonal separation,
- preserve the bright, trustworthy travel feel,
- maintain CTA clarity,
- keep components consistent.

### Don’t
- use harsh black borders,
- overdecorate,
- introduce random accent colors,
- crowd the grid,
- weaken the conversion path,
- overuse orange across large backgrounds.
