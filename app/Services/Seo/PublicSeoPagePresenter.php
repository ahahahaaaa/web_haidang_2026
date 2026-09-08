<?php

namespace App\Services\Seo;

use App\Services\Cms\SiteSettingsManager;
use App\Support\RichText;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Src\Domains\Seo\Enums\SeoPageType;
use Src\Domains\Seo\Models\SeoPage;
use Src\Domains\Seo\Support\SeoSchemaFactory;

class PublicSeoPagePresenter
{
    public function __construct(
        protected SiteSettingsManager $site,
        protected SeoSchemaFactory $schemaFactory,
    ) {
    }

    public function present(SeoPage $page, bool $isPreview = false): array
    {
        $page->loadMissing([
            'cluster',
            'outgoingLinks.targetPage.cluster',
        ]);

        $settings = $this->site->current();
        $title = $page->h1 ?: $page->title;
        $canonical = $isPreview ? null : ($page->canonical_url ?: url('/seo-pages/'.$page->slug));
        $description = $page->meta_description ?: $page->excerpt ?: $settings->seo_description;
        $location = $this->resolveLocation($page);
        $relatedLinks = $this->relatedLinks($page);
        $summaryLinks = $relatedLinks !== [] ? $relatedLinks : $this->fallbackLinks();
        $pageType = $page->page_type instanceof SeoPageType
            ? $page->page_type
            : SeoPageType::tryFrom((string) $page->page_type);
        $focusLabel = $this->focusLabel($page, $location);

        return [
            'page' => $page,
            'isPreview' => $isPreview,
            'pageTypeLabel' => $this->pageTypeLabel($pageType),
            'pageTypeValue' => $pageType?->value,
            'locationLabel' => $location,
            'focusLabel' => $focusLabel,
            'heroEyebrow' => $this->heroEyebrow($pageType),
            'summary' => $page->excerpt ?: $this->defaultSummary($pageType, $focusLabel),
            'summaryBullets' => $this->summaryBullets($pageType, $page, $focusLabel, $relatedLinks),
            'summaryLinks' => $summaryLinks,
            'summaryUpdatedLabel' => $this->updatedLabel($page),
            'breadcrumbItems' => $this->breadcrumbItems($page, $pageType),
            'quickFacts' => $this->quickFacts($pageType, $page, $focusLabel, count($relatedLinks)),
            'isHubPage' => $pageType?->isTravelHub() ?? false,
            'isContactPage' => $pageType === SeoPageType::Contact,
            'hubChecklistTitle' => $this->hubChecklistTitle($pageType, $focusLabel),
            'hubFocusCards' => $this->hubFocusCards($pageType, $focusLabel),
            'hubChecklist' => $this->hubChecklist($pageType, $focusLabel),
            'contactChannels' => $this->contactChannels(),
            'contactChecklist' => $this->contactChecklist(),
            'contactSteps' => $this->contactSteps(),
            'faqItems' => $this->faqItems($page),
            'relatedLinks' => $relatedLinks,
            'renderedContent' => $this->renderedContent($page),
            'ctaTitle' => $this->ctaTitle($pageType, $focusLabel),
            'ctaDescription' => $this->ctaDescription($pageType, $focusLabel),
            'ctaPrimaryLabel' => trim((string) data_get($page->cluster?->context, 'cta')) ?: 'Nhận tư vấn hành trình phù hợp',
            'ctaSecondaryLabel' => $this->ctaSecondaryLabel($pageType),
            'ctaSecondaryUrl' => $this->ctaSecondaryUrl($pageType),
            'seo' => [
                'canonical' => $canonical,
                'description' => $description,
                'og_description' => $page->og_description ?: $description,
                'og_image' => $page->og_image ?: $settings->getFirstMediaUrl('og_image'),
                'og_title' => $page->og_title ?: $page->meta_title ?: $title,
                'robots' => $isPreview ? 'noindex,nofollow' : 'index,follow',
                'schema' => $page->schema ?: $this->schemaFactory->forPage($page),
                'title' => $page->meta_title ?: $title,
                'type' => $pageType === SeoPageType::Blog ? 'article' : 'website',
            ],
        ];
    }

    protected function heroEyebrow(?SeoPageType $pageType): string
    {
        return match ($pageType) {
            SeoPageType::TourCategory => 'DANH MỤC TOUR',
            SeoPageType::ServiceCategory => 'DANH MỤC DỊCH VỤ',
            SeoPageType::Destination => 'TRANG ĐIỂM ĐẾN',
            SeoPageType::Region => 'TRANG VÙNG MIỀN',
            SeoPageType::Service => 'DỊCH VỤ DU LỊCH',
            SeoPageType::Blog => 'CẨM NANG DU LỊCH',
            SeoPageType::Contact => 'LIÊN HỆ TƯ VẤN',
            default => 'SEO LANDING PAGE',
        };
    }

    protected function pageTypeLabel(?SeoPageType $pageType): string
    {
        return $pageType?->label() ?? 'SEO page';
    }

    protected function defaultSummary(?SeoPageType $pageType, string $focusLabel): string
    {
        return match ($pageType) {
            SeoPageType::TourCategory => 'Trang này gom các lựa chọn tour nổi bật trong cùng một nhóm nhu cầu để bạn so sánh lịch khởi hành, điểm đến và mức giá thuận tiện hơn.',
            SeoPageType::ServiceCategory => 'Trang này gom các dịch vụ du lịch cùng nhóm nhu cầu để bạn hiểu phạm vi hỗ trợ, xem service liên quan và gửi yêu cầu tư vấn nhanh hơn.',
            SeoPageType::Destination => 'Trang hub này giúp bạn hiểu nhanh điểm đến, thời điểm phù hợp và những lựa chọn tour liên quan để chốt hành trình dễ hơn.',
            SeoPageType::Region => 'Trang hub vùng miền giúp nhóm các điểm đến, hành trình và hướng chọn tour theo cùng một cụm địa lý rõ ràng.',
            SeoPageType::Contact => 'Trang liên hệ này giúp bạn gửi yêu cầu tư vấn tour, visa hoặc dịch vụ du lịch tới đúng đầu mối phụ trách nhanh hơn.',
            SeoPageType::Blog => 'Bài viết được dựng để trả lời một nhóm truy vấn rõ ràng và điều hướng người đọc sang tour hoặc dịch vụ phù hợp hơn sau khi tìm hiểu.',
            SeoPageType::Service => 'Trang dịch vụ này giúp bạn hiểu phạm vi hỗ trợ, khi nào nên dùng và bước tiếp theo để được tư vấn đúng nhu cầu du lịch.',
            default => 'Trang này được xây để trả lời một nhóm truy vấn cụ thể và điều hướng người đọc sang bước tiếp theo phù hợp hơn trong hành trình tìm hiểu du lịch.',
        };
    }

    protected function summaryBullets(?SeoPageType $pageType, SeoPage $page, string $focusLabel, array $relatedLinks): array
    {
        $hubBullet = $pageType === SeoPageType::ServiceCategory
            ? 'Trang này đóng vai trò hub để dẫn tiếp sang service detail, dịch vụ bổ trợ hoặc nội dung tư vấn liên quan.'
            : 'Trang này đóng vai trò hub để dẫn tiếp sang tour, điểm đến hoặc cụm nội dung liên quan.';

        return collect([
            $focusLabel !== '' ? 'Trọng tâm chính của trang: '.$focusLabel.'.' : null,
            filled($page->primary_keyword) ? 'Từ khóa chính: '.$page->primary_keyword.'.' : null,
            filled(data_get($page->secondary_keywords, '0')) ? 'Các truy vấn liên quan xoay quanh '.implode(', ', array_slice($page->secondary_keywords ?? [], 0, 3)).'.' : null,
            ($pageType?->isTravelHub() ?? false) ? $hubBullet : null,
            $relatedLinks !== [] ? 'Trang này có sẵn liên kết nội bộ để đi tiếp sang các tour, dịch vụ hoặc bài viết liên quan.' : null,
        ])
            ->filter()
            ->take(4)
            ->values()
            ->all();
    }

    protected function breadcrumbItems(SeoPage $page, ?SeoPageType $pageType): array
    {
        $items = [
            ['label' => 'Trang chủ', 'url' => route('home')],
        ];

        if ($pageType?->isTravelHub()) {
            $items[] = ['label' => $pageType->sectionLabel()];
        }

        $items[] = ['label' => $page->h1 ?: $page->title];

        return $items;
    }

    protected function quickFacts(?SeoPageType $pageType, SeoPage $page, string $focusLabel, int $relatedCount): array
    {
        return array_values(array_filter([
            [
                'label' => 'Loại trang',
                'value' => $this->pageTypeLabel($pageType),
            ],
            $focusLabel !== '' && $pageType?->isTravelHub() ? [
                'label' => 'Trọng tâm',
                'value' => $focusLabel,
            ] : null,
            data_get($page->cluster?->context, 'cta') ? [
                'label' => 'CTA chính',
                'value' => data_get($page->cluster?->context, 'cta'),
            ] : null,
            $relatedCount > 0 ? [
                'label' => 'Liên kết nội bộ',
                'value' => $relatedCount.' trang liên quan',
            ] : null,
        ]));
    }

    protected function faqItems(SeoPage $page): array
    {
        return collect($page->faq_items ?? [])
            ->map(function ($item) use ($page) {
                if (is_array($item)) {
                    $question = trim((string) ($item['question'] ?? $item['title'] ?? ''));
                    $answer = trim((string) ($item['answer'] ?? ''));
                } else {
                    $question = trim((string) $item);
                    $answer = '';
                }

                if ($question === '') {
                    return null;
                }

                return [
                    'question' => $question,
                    'answer' => $answer !== '' ? $answer : $this->defaultFaqAnswer($page),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function defaultFaqAnswer(SeoPage $page): string
    {
        $pageType = $page->page_type instanceof SeoPageType
            ? $page->page_type
            : SeoPageType::tryFrom((string) $page->page_type);

        return match ($pageType) {
            SeoPageType::ServiceCategory => 'Trang danh mục dịch vụ này giúp bạn khoanh vùng đúng nhóm hỗ trợ trước khi mở service detail hoặc gửi yêu cầu tư vấn.',
            SeoPageType::TourCategory,
            SeoPageType::Destination,
            SeoPageType::Region => 'Trang này giúp bạn khoanh vùng lựa chọn phù hợp hơn trước khi đi sâu vào từng tour hoặc gửi yêu cầu tư vấn.',
            SeoPageType::Service => 'Trang dịch vụ này dùng để giúp bạn hiểu phạm vi hỗ trợ trước khi nhờ đội ngũ tư vấn theo lịch trình thực tế.',
            SeoPageType::Blog => 'Bài viết này giúp bạn hiểu chủ đề trước; khi cần chốt hành trình hoặc dịch vụ cụ thể, bạn nên gửi yêu cầu để được tư vấn rõ hơn.',
            SeoPageType::Contact => 'Bạn có thể chuẩn bị trước điểm đến, thời gian dự kiến và số lượng khách để đội ngũ phản hồi nhanh hơn.',
            default => 'Đội ngũ sẽ hỗ trợ làm rõ thêm lựa chọn phù hợp khi bạn cần trao đổi chi tiết.',
        };
    }

    protected function relatedLinks(SeoPage $page): array
    {
        return $page->outgoingLinks
            ->sortByDesc('priority')
            ->map(function ($link) {
                $target = $link->targetPage;

                if (! $target || $target->status?->value !== 'published') {
                    return null;
                }

                return [
                    'label' => $link->anchor_text ?: ($target->h1 ?: $target->title),
                    'url' => $target->canonical_url ?: url('/seo-pages/'.$target->slug),
                    'excerpt' => $target->excerpt,
                ];
            })
            ->filter()
            ->take(4)
            ->values()
            ->all();
    }

    protected function fallbackLinks(): array
    {
        return [
            ['label' => 'Khám phá tour trong nước', 'url' => route('tours.domestic')],
            ['label' => 'Xem dịch vụ du lịch', 'url' => route('services.index')],
            ['label' => 'Đọc cẩm nang du lịch', 'url' => route('blog.index')],
            ['label' => 'Liên hệ tư vấn', 'url' => route('contact')],
        ];
    }

    protected function hubFocusCards(?SeoPageType $pageType, string $focusLabel): array
    {
        if (! ($pageType?->isTravelHub() ?? false) || $focusLabel === '') {
            return [];
        }

        if ($pageType === SeoPageType::ServiceCategory) {
            return [
                [
                    'title' => 'Gom đúng nhóm dịch vụ quanh '.$focusLabel,
                    'text' => 'Người đọc cần hiểu nhanh danh mục này giải quyết nhu cầu gì, gồm các service nào và khi nào nên gửi yêu cầu tư vấn.',
                ],
                [
                    'title' => 'Dẫn sang service detail thật',
                    'text' => 'Trang mạnh khi có internal link rõ sang từng dịch vụ như visa, vé máy bay, SIM du lịch hoặc tư vấn du học thay vì chỉ mô tả chung.',
                ],
                [
                    'title' => 'Giữ tín hiệu hỗ trợ du lịch rõ ràng',
                    'text' => 'Nội dung nên nhắc đến hồ sơ, lịch trình, số khách, điểm đến hoặc bước chuẩn bị trước chuyến đi khi các dữ kiện đó phù hợp.',
                ],
            ];
        }

        return [
            [
                'title' => 'Khóa rõ intent tìm tour quanh '.$focusLabel,
                'text' => 'Người đọc cần hiểu nhanh đây là trang hub để khám phá lựa chọn phù hợp, thay vì phải tự lần mò qua quá nhiều trang không cùng ngữ cảnh.',
            ],
            [
                'title' => 'Nối từ hub sang tour và dịch vụ thật',
                'text' => 'Trang mạnh khi có internal link rõ sang tour liên quan, dịch vụ bổ trợ và các bài viết cẩm nang giúp chốt hành trình dễ hơn.',
            ],
            [
                'title' => 'Giữ travel signals đủ mạnh',
                'text' => 'Nội dung nên nhắc đến lịch khởi hành, điểm nổi bật, cách chọn tour, thời điểm phù hợp hoặc kiểu hành trình thay vì chỉ mô tả chung.',
            ],
        ];
    }

    protected function hubChecklistTitle(?SeoPageType $pageType, string $focusLabel): string
    {
        if ($pageType === SeoPageType::ServiceCategory) {
            return 'Điểm cần chốt trước khi mở rộng cụm dịch vụ quanh '.$focusLabel;
        }

        if ($pageType?->isTravelHub() ?? false) {
            return 'Điểm cần chốt trước khi mở rộng cụm nội dung quanh '.$focusLabel;
        }

        return 'Điểm cần chốt trước khi mở rộng nội dung';
    }

    protected function hubChecklist(?SeoPageType $pageType, string $focusLabel): array
    {
        if (! ($pageType?->isTravelHub() ?? false) || $focusLabel === '') {
            return [];
        }

        if ($pageType === SeoPageType::ServiceCategory) {
            return [
                'Xác định rõ '.$focusLabel.' đang hỗ trợ nhóm nhu cầu dịch vụ du lịch nào.',
                'Ưu tiên liên kết tới service detail, tour liên quan và bài viết hỗ trợ quyết định.',
                'Nêu rõ thông tin khách nên chuẩn bị như điểm đến, thời gian, số khách hoặc hồ sơ nếu phù hợp.',
                'Giữ CTA rõ ràng để người đọc chuyển nhanh sang bước tư vấn dịch vụ.',
            ];
        }

        return [
            'Xác định rõ '.$focusLabel.' đang giải quyết nhu cầu nào trong hành trình tìm tour.',
            'Ưu tiên liên kết tới tour nổi bật, nhóm điểm đến liên quan và bài viết hỗ trợ quyết định.',
            'Nêu đủ tín hiệu mua hàng như lịch khởi hành, điểm nổi bật, thời lượng hoặc giá tham khảo khi có thể.',
            'Giữ CTA rõ ràng để người đọc chuyển nhanh sang bước tư vấn hoặc chọn tour.',
        ];
    }

    protected function contactChannels(): array
    {
        $settings = $this->site->current();
        $hotline = trim((string) ($settings->hotline ?: $settings->phone));
        $phone = trim((string) $settings->phone);
        $email = trim((string) $settings->primary_email);
        $address = trim((string) $settings->address);

        return array_values(array_filter([
            $hotline !== '' ? [
                'label' => 'Hotline',
                'value' => $hotline,
                'href' => 'tel:'.preg_replace('/\s+/', '', $hotline),
                'note' => 'Phù hợp khi bạn cần chốt nhanh tour, visa hoặc nhu cầu hỗ trợ gấp.',
            ] : null,
            $email !== '' ? [
                'label' => 'Email',
                'value' => $email,
                'href' => 'mailto:'.$email,
                'note' => 'Phù hợp khi bạn đã có lịch trình dự kiến hoặc cần gửi brief chi tiết cho đoàn.',
            ] : null,
            $address !== '' ? [
                'label' => 'Địa chỉ',
                'value' => $address,
                'href' => null,
                'note' => 'Dùng khi cần xác minh thông tin doanh nghiệp hoặc hẹn gặp trao đổi trực tiếp.',
            ] : null,
            ($phone !== '' && $phone !== $hotline) ? [
                'label' => 'Điện thoại',
                'value' => $phone,
                'href' => 'tel:'.preg_replace('/\s+/', '', $phone),
                'note' => 'Kênh hỗ trợ thêm khi hotline đang bận hoặc cần gọi lại theo khung giờ hẹn trước.',
            ] : null,
        ]));
    }

    protected function contactChecklist(): array
    {
        return [
            'Điểm đến hoặc nhóm tour bạn đang quan tâm.',
            'Ngày dự kiến đi và thời lượng mong muốn.',
            'Số lượng khách, người lớn hoặc trẻ em nếu đã có.',
            'Nhu cầu bổ sung như visa, vé máy bay, khách sạn hay tour đoàn riêng.',
        ];
    }

    protected function contactSteps(): array
    {
        return [
            [
                'title' => 'Gửi yêu cầu ngắn gọn',
                'text' => 'Bắt đầu bằng điểm đến, thời gian dự kiến và số lượng khách để hệ thống định tuyến đúng đầu mối.',
            ],
            [
                'title' => 'Rà nhu cầu và chốt dữ liệu',
                'text' => 'Đội ngũ kiểm tra đầu vào, hỏi thêm phần còn thiếu và xác định lựa chọn tour hoặc dịch vụ phù hợp nhất.',
            ],
            [
                'title' => 'Đề xuất hành trình hoặc phương án hỗ trợ',
                'text' => 'Khi thông tin đã đủ, người phụ trách sẽ gửi tư vấn, lịch trình, gợi ý tour hoặc bước tiếp theo phù hợp.',
            ],
        ];
    }

    protected function renderedContent(SeoPage $page): HtmlString
    {
        $markdown = Str::markdown(trim((string) $page->content));

        return RichText::render($markdown);
    }

    protected function ctaTitle(?SeoPageType $pageType, string $focusLabel): string
    {
        return match ($pageType) {
            SeoPageType::TourCategory => 'Muốn đội ngũ gợi ý tour phù hợp nhanh hơn?',
            SeoPageType::ServiceCategory => 'Muốn đội ngũ tư vấn đúng nhóm dịch vụ nhanh hơn?',
            SeoPageType::Destination => 'Muốn chốt hành trình phù hợp cho '.$focusLabel.'?',
            SeoPageType::Region => 'Muốn so sánh nhanh các lựa chọn tour trong '.$focusLabel.'?',
            SeoPageType::Blog => 'Muốn chuyển phần tìm hiểu thành kế hoạch đi thực tế?',
            SeoPageType::Contact => 'Sẵn sàng gửi nhu cầu để đội ngũ phản hồi?',
            default => 'Cần hỗ trợ chọn tour hoặc dịch vụ du lịch phù hợp?',
        };
    }

    protected function ctaDescription(?SeoPageType $pageType, string $focusLabel): string
    {
        return match ($pageType) {
            SeoPageType::TourCategory => 'Gửi nhanh nhu cầu về điểm đến, thời gian đi và ngân sách để đội ngũ rút gọn các lựa chọn tour phù hợp hơn.',
            SeoPageType::ServiceCategory => 'Chia sẻ nhu cầu dịch vụ, điểm đến, thời gian dự kiến và số lượng khách để đội ngũ gợi ý bước hỗ trợ phù hợp hơn.',
            SeoPageType::Destination => 'Chia sẻ nhu cầu đi '.$focusLabel.' để đội ngũ gợi ý lịch khởi hành, phương tiện và tour phù hợp với nhóm khách của bạn.',
            SeoPageType::Region => 'Gửi ngắn về điểm đến, thời lượng và kiểu trải nghiệm bạn muốn để đội ngũ gợi ý route phù hợp hơn trong cùng vùng.',
            SeoPageType::Contact => 'Bạn có thể gửi brief ngắn về điểm đến, thời gian đi và số lượng khách để đội ngũ phản hồi đúng kênh và đúng người phụ trách hơn.',
            default => 'Chia sẻ ngắn về điểm đến, thời gian dự kiến và số lượng khách để đội ngũ phản hồi đúng nhu cầu hơn.',
        };
    }

    protected function ctaSecondaryLabel(?SeoPageType $pageType): string
    {
        if ($pageType === SeoPageType::ServiceCategory) {
            return 'Xem tất cả dịch vụ';
        }

        return $pageType === SeoPageType::Blog ? 'Xem tour trong nước' : 'Liên hệ ngay';
    }

    protected function ctaSecondaryUrl(?SeoPageType $pageType): string
    {
        if ($pageType === SeoPageType::ServiceCategory) {
            return route('services.index');
        }

        return $pageType === SeoPageType::Blog
            ? route('tours.domestic')
            : route('contact');
    }

    protected function updatedLabel(SeoPage $page): ?string
    {
        $timestamp = $page->published_at ?: $page->updated_at;

        return $timestamp ? 'Cập nhật lần cuối '.optional($timestamp)->format('d/m/Y') : null;
    }

    protected function resolveLocation(SeoPage $page): ?string
    {
        $location = trim((string) data_get($page->cluster?->context, 'location'));

        return $location !== '' ? $location : null;
    }

    protected function focusLabel(SeoPage $page, ?string $location): string
    {
        if ($location !== null) {
            return $location;
        }

        return trim((string) ($page->h1 ?: $page->title ?: $page->primary_keyword));
    }
}
