<?php

namespace Src\Domains\Seo\Support;

use Src\Domains\Seo\Enums\SeoPageType;
use Src\Domains\Seo\Models\SeoPage;

class SeoMetaFactory
{
    public function forPage(SeoPage $page): array
    {
        $keyword = trim((string) $page->primary_keyword);
        $title = trim((string) ($page->title ?: $page->h1 ?: $keyword));
        $businessName = trim((string) config('seo_ai.business_name'));
        $focusLabel = $this->focusLabel($page);
        $location = $this->resolveLocation($page);
        $pageType = $page->page_type instanceof SeoPageType
            ? $page->page_type
            : SeoPageType::tryFrom((string) $page->page_type);

        $metaTitle = match ($pageType) {
            SeoPageType::ServiceCategory => $this->limit($this->joinTitle([$focusLabel ?: $keyword ?: $title, 'Dịch vụ du lịch', $businessName]), 60),
            SeoPageType::Service => $this->limit($this->joinTitle([$keyword ?: $title, $businessName]), 60),
            SeoPageType::Blog => $this->limit($this->joinTitle([$title, $businessName]), 60),
            SeoPageType::Contact => $this->limit($this->joinTitle(['Liên hệ '.$businessName, 'Tư vấn tour và dịch vụ du lịch']), 60),
            SeoPageType::TourCategory => $this->limit(($focusLabel ?: $title).' | Danh sách tour, lịch khởi hành và giá mới nhất', 60),
            SeoPageType::Destination => $this->limit('Tour '.($focusLabel ?: $title).' giá tốt, lịch khởi hành mới nhất', 60),
            SeoPageType::Region => $this->limit('Tour '.($focusLabel ?: $title).' | Điểm đến và lịch khởi hành mới nhất', 60),
            SeoPageType::Homepage => $this->limit($this->joinTitle([$title, $businessName]), 60),
            SeoPageType::Project => $this->limit($this->joinTitle([$title, 'Dự án tiêu biểu', $businessName]), 60),
            SeoPageType::LocationLanding => $this->limit($this->joinTitle([$keyword ?: $title, $location ? 'tại '.$location : null, $businessName]), 60),
            default => $this->limit($this->joinTitle([$title, $businessName]), 60),
        };

        $description = $this->fallbackDescription($page, $focusLabel, $location);
        $metaDescription = $this->limit($description, 160);

        return [
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'og_title' => $metaTitle,
            'og_description' => $metaDescription,
        ];
    }

    protected function fallbackDescription(SeoPage $page, ?string $focusLabel, ?string $location): string
    {
        $excerpt = trim(strip_tags((string) $page->excerpt));

        if ($excerpt !== '') {
            return $excerpt;
        }

        $keyword = trim((string) $page->primary_keyword);
        $pageType = $page->page_type instanceof SeoPageType
            ? $page->page_type
            : SeoPageType::tryFrom((string) $page->page_type);
        $focus = $focusLabel ?: $keyword;

        return match ($pageType) {
            SeoPageType::ServiceCategory => "{$focus} giúp gom đúng nhóm dịch vụ du lịch, so sánh nhu cầu hỗ trợ, xem service liên quan và gửi yêu cầu tư vấn nhanh hơn.",
            SeoPageType::Service => "{$focus} với thông tin rõ về phạm vi dịch vụ, cách triển khai, chi phí tham khảo và bước liên hệ phù hợp cho hành trình du lịch.",
            SeoPageType::Blog => "Tìm hiểu {$focus} rõ ràng, thực tế và dễ áp dụng cho nhu cầu tìm tour, chuẩn bị lịch trình và chọn dịch vụ du lịch phù hợp.",
            SeoPageType::Contact => "Liên hệ nhanh để được tư vấn tour, visa, vé máy bay và dịch vụ du lịch phù hợp với lịch trình, điểm đến và ngân sách của bạn.",
            SeoPageType::TourCategory => "{$focus} với danh sách tour, lịch khởi hành, điểm đến nổi bật và gợi ý lựa chọn phù hợp theo nhu cầu thực tế.",
            SeoPageType::Destination => "Khám phá tour {$focus}, lịch khởi hành mới nhất, điểm nổi bật, kinh nghiệm đi thực tế và bước đặt tour phù hợp hơn.",
            SeoPageType::Region => "Tổng hợp tour {$focus}, nhóm điểm đến nổi bật, lịch khởi hành và gợi ý hành trình phù hợp cho từng kiểu du khách.",
            SeoPageType::LocationLanding => "{$keyword}".($location ? " tại {$location}" : '')." với ngữ cảnh địa phương rõ ràng và CTA tư vấn nhanh.",
            SeoPageType::Project => "Khám phá dự án {$focus} với phạm vi triển khai, giải pháp thực hiện và chất lượng thi công được trình bày minh bạch.",
            default => "Thông tin {$focus} được trình bày rõ ràng, đúng trọng tâm và bám sát intent tìm kiếm thực tế.",
        };
    }

    protected function focusLabel(SeoPage $page): ?string
    {
        $location = $this->resolveLocation($page);

        if ($location !== null) {
            return $location;
        }

        $candidate = trim((string) ($page->h1 ?: $page->title ?: $page->primary_keyword));

        return $candidate !== '' ? $candidate : null;
    }

    protected function joinTitle(array $parts): string
    {
        return implode(' | ', array_values(array_filter(array_map(
            static fn ($part) => trim((string) $part),
            $parts,
        ))));
    }

    protected function limit(string $value, int $max): string
    {
        return trim(mb_substr($value, 0, $max));
    }

    protected function resolveLocation(SeoPage $page): ?string
    {
        $location = trim((string) data_get($page->cluster?->context, 'location'));

        return $location !== '' ? $location : null;
    }
}
