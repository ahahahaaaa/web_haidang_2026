<?php

namespace Src\Domains\Seo\Actions;

use Src\Domains\Seo\Enums\SeoClusterStatus;
use Src\Domains\Seo\Enums\SeoPageStatus;
use Src\Domains\Seo\Enums\SeoPageType;
use Src\Domains\Seo\Models\ContentCluster;
use Src\Domains\Seo\Models\SeoLink;
use Src\Domains\Seo\Models\SeoPage;
use Src\Domains\Seo\Support\SeoMetaFactory;
use Src\Domains\Seo\Support\SeoQaValidator;
use Src\Domains\Seo\Support\SeoSchemaFactory;
use Src\Domains\Seo\Support\SeoSlugGenerator;
use Src\Domains\Seo\Support\SeoUrlBuilder;

class SeedSeoDemoPagesAction
{
    public function __construct(
        protected SeoSlugGenerator $slugGenerator,
        protected SeoMetaFactory $metaFactory,
        protected SeoSchemaFactory $schemaFactory,
        protected SeoQaValidator $qaValidator,
        protected SeoUrlBuilder $urlBuilder,
    ) {}

    public function execute(): array
    {
        $pages = [];

        foreach ($this->definitions() as $definition) {
            $cluster = $this->upsertCluster($definition);
            $page = $this->upsertPage($definition, $cluster);
            $pages[$definition['key']] = $page;
        }

        $this->seedLinks($pages);

        foreach ($pages as $page) {
            $this->finalizePage($page);
        }

        return array_values($pages);
    }

    protected function finalizePage(SeoPage $page): void
    {
        $page->refresh()->loadMissing('outgoingLinks.targetPage.cluster');
        $meta = $this->metaFactory->forPage($page);

        $page->meta_title = $meta['meta_title'] ?? null;
        $page->meta_description = $meta['meta_description'] ?? null;
        $page->og_title = $meta['og_title'] ?? null;
        $page->og_description = $meta['og_description'] ?? null;
        $page->schema = $this->schemaFactory->forPage($page);
        $page->save();

        $report = $this->qaValidator->validate($page, $page->outgoingLinks()->count());
        $page->qa_report = $report;
        $page->status = $report['status'] === 'pass' ? SeoPageStatus::PendingReview : SeoPageStatus::QaFailed;
        $page->save();
    }

    protected function upsertCluster(array $definition): ?ContentCluster
    {
        if (($definition['mode'] ?? 'manual') !== 'cluster') {
            return null;
        }

        $clusterDefinition = $definition['cluster'];

        return ContentCluster::query()->updateOrCreate(
            ['name' => $clusterDefinition['name']],
            [
                'primary_keyword' => $definition['primary_keyword'],
                'secondary_keywords' => $definition['secondary_keywords'],
                'lsi_keywords' => $definition['secondary_keywords'],
                'intent' => $clusterDefinition['intent'],
                'target_page_type' => $clusterDefinition['target_page_type'],
                'business_value' => 8,
                'priority_score' => 80,
                'status' => SeoClusterStatus::Completed,
                'context' => array_filter([
                    'location' => $clusterDefinition['location'] ?? null,
                    'cta' => $clusterDefinition['cta'] ?? null,
                    'demo_key' => $definition['key'],
                ]),
            ],
        );
    }

    protected function upsertPage(array $definition, ?ContentCluster $cluster): SeoPage
    {
        $slug = $this->slugGenerator->generate($definition['slug']);

        return SeoPage::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'content_cluster_id' => $cluster?->getKey(),
                'page_type' => $definition['page_type'],
                'title' => $definition['title'],
                'slug' => $slug,
                'canonical_url' => $this->urlBuilder->canonicalUrl($definition['page_type'], $slug),
                'primary_keyword' => $definition['primary_keyword'],
                'secondary_keywords' => $definition['secondary_keywords'],
                'h1' => $definition['h1'],
                'excerpt' => $definition['excerpt'],
                'content' => $definition['content'],
                'faq_items' => $definition['faq_items'],
                'generation_payload' => [
                    'source' => 'demo_seed',
                    'demo_key' => $definition['key'],
                    'mode' => $definition['mode'],
                ],
                'status' => SeoPageStatus::Draft,
            ],
        );
    }

    protected function seedLinks(array $pages): void
    {
        foreach ($this->definitions() as $definition) {
            $source = $pages[$definition['key']] ?? null;

            if (! $source) {
                continue;
            }

            foreach ($definition['link_targets'] as $link) {
                $target = $pages[$link['target']] ?? null;

                if (! $target) {
                    continue;
                }

                SeoLink::query()->firstOrCreate(
                    [
                        'source_page_id' => $source->getKey(),
                        'target_page_id' => $target->getKey(),
                        'anchor_text' => $link['anchor'],
                    ],
                    [
                        'link_type' => 'related',
                        'priority' => 50,
                        'status' => 'suggested',
                    ],
                );
            }
        }
    }

    protected function renderSections(array $sections): string
    {
        return collect($sections)
            ->map(fn (array $section) => '## '.$section[0]."\n\n".$section[1])
            ->implode("\n\n");
    }

    protected function definitions(): array
    {
        return [
            [
                'key' => 'tour-category-domestic',
                'mode' => 'cluster',
                'cluster' => [
                    'name' => 'SEO danh mục tour trong nước',
                    'intent' => 'commercial',
                    'target_page_type' => SeoPageType::TourCategory->value,
                    'location' => 'Việt Nam',
                    'cta' => 'Nhận tư vấn tour trong nước',
                ],
                'page_type' => SeoPageType::TourCategory->value,
                'title' => 'Tour trong nước',
                'slug' => 'tour-trong-nuoc',
                'primary_keyword' => 'tour trong nước',
                'secondary_keywords' => ['tour việt nam', 'tour nội địa', 'tour trong nước giá tốt'],
                'h1' => 'Danh mục tour trong nước theo nhu cầu, lịch khởi hành và điểm đến',
                'excerpt' => 'Trang danh mục này gom các lựa chọn tour nội địa để người đọc đi nhanh từ nhu cầu chung sang nhóm tour và điểm đến phù hợp hơn.',
                'faq_items' => [
                    ['question' => 'Tour trong nước phù hợp với nhóm khách nào?', 'answer' => 'Tour trong nước phù hợp cho khách gia đình, nhóm bạn, khách đoàn và cả khách muốn đi ngắn ngày cuối tuần.'],
                ],
                'content' => $this->categoryContent(),
                'link_targets' => [
                    ['target' => 'destination-phu-quoc', 'anchor' => 'tour Phú Quốc'],
                    ['target' => 'region-dong-nam-a', 'anchor' => 'tour Đông Nam Á'],
                    ['target' => 'contact-tour', 'anchor' => 'liên hệ tư vấn tour'],
                ],
            ],
            [
                'key' => 'destination-phu-quoc',
                'mode' => 'cluster',
                'cluster' => [
                    'name' => 'SEO điểm đến Phú Quốc',
                    'intent' => 'commercial',
                    'target_page_type' => SeoPageType::Destination->value,
                    'location' => 'Phú Quốc',
                    'cta' => 'Nhận tư vấn tour Phú Quốc',
                ],
                'page_type' => SeoPageType::Destination->value,
                'title' => 'Tour Phú Quốc',
                'slug' => 'phu-quoc',
                'primary_keyword' => 'tour phú quốc',
                'secondary_keywords' => ['tour phú quốc giá tốt', 'du lịch phú quốc', 'lịch khởi hành phú quốc'],
                'h1' => 'Tour Phú Quốc giá tốt, lịch khởi hành mới nhất',
                'excerpt' => 'Trang điểm đến này kết hợp tín hiệu commercial và guide content để người đọc hiểu nhanh khi nào nên đi, chọn tour nào và nên chuẩn bị gì.',
                'faq_items' => [
                    ['question' => 'Đi Phú Quốc nên chọn tour mấy ngày?', 'answer' => 'Tour 3 ngày 2 đêm hoặc 4 ngày 3 đêm thường phù hợp với phần lớn khách muốn kết hợp nghỉ dưỡng và tham quan.'],
                ],
                'content' => $this->destinationContent(),
                'link_targets' => [
                    ['target' => 'tour-category-domestic', 'anchor' => 'tour trong nước'],
                    ['target' => 'service-visa', 'anchor' => 'dịch vụ visa và giấy tờ'],
                    ['target' => 'blog-thailand-guide', 'anchor' => 'cẩm nang chuẩn bị lịch trình'],
                ],
            ],
            [
                'key' => 'region-dong-nam-a',
                'mode' => 'cluster',
                'cluster' => [
                    'name' => 'SEO vùng Đông Nam Á',
                    'intent' => 'commercial',
                    'target_page_type' => SeoPageType::Region->value,
                    'location' => 'Đông Nam Á',
                    'cta' => 'Nhận gợi ý route Đông Nam Á',
                ],
                'page_type' => SeoPageType::Region->value,
                'title' => 'Tour Đông Nam Á',
                'slug' => 'dong-nam-a',
                'primary_keyword' => 'tour đông nam á',
                'secondary_keywords' => ['du lịch đông nam á', 'tour thái lan singapore malaysia'],
                'h1' => 'Tour Đông Nam Á theo cụm điểm đến và lịch khởi hành',
                'excerpt' => 'Trang hub vùng miền này giúp so sánh các route phổ biến trong Đông Nam Á trước khi chọn quốc gia hoặc tour cụ thể.',
                'faq_items' => [
                    ['question' => 'Nên bắt đầu tour Đông Nam Á từ quốc gia nào?', 'answer' => 'Tùy ngân sách và thời gian, Thái Lan, Singapore hoặc Malaysia thường là những lựa chọn dễ bắt đầu nhất.'],
                ],
                'content' => $this->regionContent(),
                'link_targets' => [
                    ['target' => 'destination-thailand', 'anchor' => 'tour Thái Lan'],
                    ['target' => 'blog-thailand-guide', 'anchor' => 'kinh nghiệm chuẩn bị tour Thái Lan'],
                    ['target' => 'contact-tour', 'anchor' => 'nhận tư vấn route phù hợp'],
                ],
            ],
            [
                'key' => 'destination-thailand',
                'mode' => 'cluster',
                'cluster' => [
                    'name' => 'SEO điểm đến Thái Lan',
                    'intent' => 'commercial',
                    'target_page_type' => SeoPageType::Destination->value,
                    'location' => 'Thái Lan',
                    'cta' => 'Nhận tư vấn tour Thái Lan',
                ],
                'page_type' => SeoPageType::Destination->value,
                'title' => 'Tour Thái Lan',
                'slug' => 'thai-lan',
                'primary_keyword' => 'tour thái lan',
                'secondary_keywords' => ['du lịch thái lan', 'tour bangkok pattaya', 'tour thái lan giá tốt'],
                'h1' => 'Tour Thái Lan theo điểm đến, lịch khởi hành và ngân sách',
                'excerpt' => 'Trang điểm đến này giúp gom nhanh các nhóm hành trình phổ biến của Thái Lan để khách chốt route, mùa đi và thời lượng phù hợp.',
                'faq_items' => [
                    ['question' => 'Tour Thái Lan thường đi những điểm nào?', 'answer' => 'Bangkok, Pattaya, Chiang Mai và Phuket là những nhóm điểm đến được quan tâm nhiều nhất.'],
                ],
                'content' => $this->thailandDestinationContent(),
                'link_targets' => [
                    ['target' => 'region-dong-nam-a', 'anchor' => 'tour Đông Nam Á'],
                    ['target' => 'blog-thailand-guide', 'anchor' => 'cẩm nang du lịch Thái Lan'],
                    ['target' => 'contact-tour', 'anchor' => 'liên hệ tư vấn tour Thái Lan'],
                ],
            ],
            [
                'key' => 'service-visa',
                'mode' => 'manual',
                'page_type' => SeoPageType::Service->value,
                'title' => 'Dịch vụ visa du lịch',
                'slug' => 'visa-du-lich',
                'primary_keyword' => 'dịch vụ visa du lịch',
                'secondary_keywords' => ['hồ sơ visa du lịch', 'tư vấn visa'],
                'h1' => 'Dịch vụ visa du lịch giúp chuẩn hóa hồ sơ và giảm sai sót',
                'excerpt' => 'Trang dịch vụ này giúp người đọc hiểu khi nào nên dùng hỗ trợ visa và cần chuẩn bị gì trước khi gửi hồ sơ hoặc chọn tour quốc tế.',
                'faq_items' => [
                    ['question' => 'Khi nào nên dùng dịch vụ visa?', 'answer' => 'Khi bạn cần chuẩn hóa hồ sơ, kiểm tra giấy tờ và muốn giảm rủi ro sai sót ở bước chuẩn bị ban đầu.'],
                ],
                'content' => $this->serviceContent(),
                'link_targets' => [
                    ['target' => 'destination-thailand', 'anchor' => 'tour Thái Lan'],
                    ['target' => 'contact-tour', 'anchor' => 'liên hệ tư vấn visa'],
                ],
            ],
            [
                'key' => 'blog-thailand-guide',
                'mode' => 'manual',
                'page_type' => SeoPageType::Blog->value,
                'title' => 'Kinh nghiệm chuẩn bị tour Thái Lan',
                'slug' => 'kinh-nghiem-chuan-bi-tour-thai-lan',
                'primary_keyword' => 'kinh nghiệm chuẩn bị tour thái lan',
                'secondary_keywords' => ['chuẩn bị đi thái lan', 'du lịch thái lan cần gì'],
                'h1' => 'Kinh nghiệm chuẩn bị tour Thái Lan từ lịch trình đến giấy tờ',
                'excerpt' => 'Bài viết này giúp khách lần đầu đi Thái Lan nhìn rõ các bước chuẩn bị trước khi chọn tour hoặc dịch vụ hỗ trợ phù hợp.',
                'faq_items' => [
                    ['question' => 'Đi tour Thái Lan nên chuẩn bị gì trước?', 'answer' => 'Bạn nên chuẩn bị lịch trình dự kiến, giấy tờ cần thiết, ngân sách và kiểu trải nghiệm ưu tiên trước khi chọn tour.'],
                ],
                'content' => $this->blogContent(),
                'link_targets' => [
                    ['target' => 'destination-thailand', 'anchor' => 'tour Thái Lan'],
                    ['target' => 'service-visa', 'anchor' => 'dịch vụ visa du lịch'],
                ],
            ],
            [
                'key' => 'contact-tour',
                'mode' => 'manual',
                'page_type' => SeoPageType::Contact->value,
                'title' => 'Liên hệ tư vấn tour',
                'slug' => 'lien-he-tu-van-tour',
                'primary_keyword' => 'liên hệ tư vấn tour',
                'secondary_keywords' => ['tư vấn lịch trình du lịch', 'liên hệ đặt tour'],
                'h1' => 'Liên hệ tư vấn tour nhanh và đúng đầu mối',
                'excerpt' => 'Trang liên hệ này được tối ưu để khách gửi yêu cầu về điểm đến, thời gian đi, số lượng khách và dịch vụ cần hỗ trợ tới đúng đầu mối.',
                'faq_items' => [
                    ['question' => 'Gửi yêu cầu tư vấn tour cần thông tin gì?', 'answer' => 'Bạn nên chuẩn bị điểm đến quan tâm, ngày đi dự kiến, số lượng khách và dịch vụ cần hỗ trợ như visa hoặc vé máy bay.'],
                ],
                'content' => $this->contactContent(),
                'link_targets' => [
                    ['target' => 'tour-category-domestic', 'anchor' => 'tour trong nước'],
                    ['target' => 'destination-thailand', 'anchor' => 'tour Thái Lan'],
                    ['target' => 'service-visa', 'anchor' => 'dịch vụ visa du lịch'],
                ],
            ],
        ];
    }

    protected function categoryContent(): string
    {
        return $this->renderSections([
            ['Vai trò của trang danh mục', str_repeat('Danh mục tour tốt phải giúp người đọc đi từ nhu cầu chung sang nhóm tour cụ thể mà không bị ngợp thông tin. Khi nội dung nêu rõ lịch khởi hành, điểm đến, kiểu hành trình và nhóm khách phù hợp, trang sẽ vừa hỗ trợ SEO vừa hỗ trợ chuyển đổi thực tế. ', 8)],
            ['Cách chọn nhanh nhóm tour phù hợp', str_repeat('Người đọc thường cần một lớp định hướng rất sớm: nên xem tour trong nước hay tour quốc tế, đi nghỉ dưỡng hay tham quan, ưu tiên giá, thời lượng hay trải nghiệm. Trang danh mục cần giải thích ngắn gọn để họ rẽ đúng nhánh nội dung ngay từ đầu. ', 8)],
            ['Internal link cần có', str_repeat('Một danh mục mạnh không dừng ở mô tả chung mà phải có liên kết sang điểm đến nổi bật, cụm vùng miền, bài viết hỗ trợ và contact page để người đọc luôn có bước đi tiếp rõ ràng sau mỗi đoạn nội dung. ', 8)],
        ]);
    }

    protected function destinationContent(): string
    {
        return $this->renderSections([
            ['Vì sao Phú Quốc là điểm đến có intent cao', str_repeat('Phú Quốc là nhóm điểm đến có intent mua tour rất rõ vì khách thường đi theo mùa, theo khung nghỉ ngắn và cần được gợi ý hành trình phù hợp nhanh. Nội dung nên cho thấy đây là trang hub để chọn tour, không chỉ là bài giới thiệu điểm đến đơn thuần. ', 8)],
            ['Thông tin người đọc thường cần trước khi chọn tour', str_repeat('Khách thường quan tâm mùa đi, thời lượng phù hợp, lịch trình nổi bật, phương tiện di chuyển và kiểu trải nghiệm dành cho gia đình, nhóm bạn hoặc khách muốn nghỉ dưỡng. Nếu trả lời các câu hỏi này sớm, trang sẽ hỗ trợ quyết định tốt hơn. ', 8)],
            ['Điểm nối sang tour và dịch vụ', str_repeat('Sau khi nắm được bối cảnh điểm đến, người đọc cần được dẫn tiếp sang tour cụ thể, danh mục tour trong nước, dịch vụ visa hoặc trang liên hệ để khóa nhanh bước tiếp theo thay vì phải quay lại menu hoặc tìm lại từ đầu. ', 8)],
        ]);
    }

    protected function regionContent(): string
    {
        return $this->renderSections([
            ['Vì sao cần trang hub theo vùng', str_repeat('Trang vùng miền giúp gom nhiều điểm đến có liên hệ chặt về địa lý và kiểu hành trình. Điều này rất phù hợp với nhu cầu của khách chưa chốt quốc gia hoặc muốn so sánh nhanh các route gần nhau trước khi quyết định. ', 8)],
            ['Cách dùng trang vùng để dẫn quyết định', str_repeat('Trang nên giúp người đọc hiểu nhanh vùng này nổi bật ở kiểu trải nghiệm nào, mùa đi ra sao, nhóm tour nào phổ biến và nên rẽ tiếp sang quốc gia hay điểm đến nào để tiết kiệm thời gian tìm hiểu. ', 8)],
            ['Tín hiệu thương mại cần giữ', str_repeat('Dù là trang hub, nội dung vẫn nên nhắc tới lịch khởi hành, nhóm điểm đến chính, các route phổ biến và CTA rõ ràng để giữ intent mua tour thay vì chỉ trở thành một bài tổng quan mơ hồ. ', 8)],
        ]);
    }

    protected function thailandDestinationContent(): string
    {
        return $this->renderSections([
            ['Vai trò của trang điểm đến', str_repeat('Trang điểm đến Thái Lan là lớp hub quan trọng để gom nhiều điểm đến lớn, nhiều route phổ biến và nhiều intent tìm tour khác nhau trong cùng một thị trường. Nếu tổ chức tốt, trang này vừa mạnh về SEO vừa giúp khách chốt tour nhanh hơn. ', 8)],
            ['Những thông tin cần xuất hiện sớm', str_repeat('Người đọc thường muốn thấy các cụm điểm đến chính, thời lượng phổ biến, mùa đi, kiểu tour thường gặp và link sang bài viết chuẩn bị hành trình. Các tín hiệu này giúp họ định vị nhanh mà không cần mở quá nhiều tab khác nhau. ', 8)],
            ['Cách chuyển sang bước tiếp theo', str_repeat('Sau phần giới thiệu điểm đến, trang nên điều hướng sang route vùng miền, bài viết cẩm nang, dịch vụ hỗ trợ và contact page để người đọc luôn có hướng đi tiếp theo phù hợp với mức độ sẵn sàng ra quyết định của họ. ', 8)],
        ]);
    }

    protected function serviceContent(): string
    {
        return $this->renderSections([
            ['Khi nào dịch vụ visa tạo ra giá trị thật', str_repeat('Dịch vụ visa đặc biệt hữu ích khi khách đã chốt điểm đến nhưng chưa tự tin ở phần hồ sơ, giấy tờ hoặc quy trình chuẩn bị. Nội dung tốt cần giúp người đọc hiểu rõ khi nào nên dùng hỗ trợ và giới hạn hỗ trợ nằm ở đâu. ', 8)],
            ['Những dữ liệu nên chuẩn bị trước', str_repeat('Khách nên chuẩn bị thông tin chuyến đi, giấy tờ cá nhân, thời gian dự kiến và những câu hỏi đang vướng ở phần hồ sơ. Khi trang nêu rõ đầu vào cần có, khả năng chuyển đổi sang bước tư vấn sẽ cao hơn. ', 8)],
            ['Internal link nên đi đâu', str_repeat('Trang dịch vụ nên nối sang các quốc gia hoặc tour có nhu cầu visa cao, đồng thời giữ một CTA liên hệ rõ ràng để người đọc không bị mất mạch sau khi hiểu xong phạm vi hỗ trợ. ', 8)],
        ]);
    }

    protected function blogContent(): string
    {
        return $this->renderSections([
            ['Mục tiêu của bài viết cẩm nang', str_repeat('Bài viết cẩm nang tốt không chỉ trả lời câu hỏi mà còn giúp người đọc tự tin hơn trước khi chuyển sang bước đặt tour hoặc nhờ tư vấn. Nội dung cần rõ, thực tế, có thứ tự và dẫn tiếp sang các trang thương mại liên quan. ', 9)],
            ['Những điểm nên giải thích sớm', str_repeat('Người đọc thường muốn biết nên chuẩn bị gì, nên khóa thông tin nào trước, chi tiết nào có thể hỏi sau và khi nào nên chuyển sang contact page để được đội ngũ hỗ trợ theo tình huống thực tế của họ. ', 9)],
            ['Điểm nối sang tour hoặc dịch vụ', str_repeat('Sau khi đọc xong cẩm nang, người đọc nên có sẵn các nhánh đi tiếp như tour quốc gia liên quan, dịch vụ visa hoặc contact page để không đứt mạch ra quyết định. Đây là phần rất quan trọng với travel SEO. ', 9)],
        ]);
    }

    protected function contactContent(): string
    {
        return $this->renderSections([
            ['Khi nào nên gửi yêu cầu tư vấn', str_repeat('Ngay khi bạn đã có điểm đến quan tâm hoặc thời gian dự kiến đi, trang contact nên giúp bạn gửi brief ngắn đến đúng đầu mối để không phải trao đổi nhiều vòng không cần thiết. Đây là bước rất quan trọng với travel lead. ', 8)],
            ['Những thông tin giúp đội ngũ phản hồi nhanh hơn', str_repeat('Nếu khách chuẩn bị trước điểm đến, ngày đi, số lượng khách và dịch vụ cần hỗ trợ như visa hoặc vé máy bay, khả năng tư vấn đúng và nhanh sẽ cao hơn nhiều so với một yêu cầu quá chung chung. ', 8)],
            ['Cách điều hướng sau contact', str_repeat('Ngay cả trang liên hệ cũng nên giữ mạch internal link sang tour nổi bật, cụm nội dung liên quan và dịch vụ bổ trợ để khách luôn có chỗ đi tiếp nếu họ chưa muốn gửi form ngay lập tức. ', 8)],
        ]);
    }
}
