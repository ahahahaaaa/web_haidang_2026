<?php

namespace App\Services\Frontsite;

use App\Services\Cms\SiteSettingsManager;
use App\Support\GeoContent;
use App\Support\RichText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Src\Domains\Cms\Enums\TourScope;
use Src\Domains\Cms\Models\BlogPost;
use Src\Domains\Cms\Models\ContentCategory;
use Src\Domains\Cms\Models\Destination;
use Src\Domains\Cms\Models\LandingPage;
use Src\Domains\Cms\Models\Region;
use Src\Domains\Cms\Models\Service;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourCategory;

class FrontsiteGeoPresenter
{
    public function __construct(protected SiteSettingsManager $site) {}

    public function forHome(?LandingPage $landing, Collection $featuredTours, Collection $tourCategories, Collection $destinations): array
    {
        $summary = $this->summaryFor($landing, [
            $landing?->hero_excerpt,
            $landing?->intro_excerpt,
            $landing?->body,
            $landing?->meta_description,
            $this->site->current()->seo_description,
        ]);

        return $this->payload($landing, 'Tóm tắt nhanh về Hải Đăng Travel', $summary, [
            ['label' => 'Tour đang gợi ý', 'value' => $this->countLabel($featuredTours->count(), 'tour')],
            ['label' => 'Chủ đề nổi bật', 'value' => $this->countLabel($tourCategories->count(), 'chủ đề')],
            ['label' => 'Điểm đến nổi bật', 'value' => $this->countLabel($destinations->count(), 'điểm đến')],
        ], [
            'Dùng trang chủ để đi nhanh vào nhóm tour, điểm đến hoặc dịch vụ hỗ trợ phù hợp.',
            'Khi cần lịch trình riêng, hãy gửi một yêu cầu để đội ngũ tư vấn theo ngày đi, ngân sách và quy mô đoàn.',
        ], [
            ['label' => 'Tour trong nước', 'url' => route('tours.domestic')],
            ['label' => 'Tour nước ngoài', 'url' => route('tours.international')],
            ['label' => 'Dịch vụ du lịch', 'url' => route('services.index')],
        ], $this->contactCta());
    }

    public function forTour(Tour $tour): array
    {
        $tour->loadMissing(['primaryCategory', 'destination', 'region']);
        $nextDeparture = $tour->relationLoaded('departures')
            ? $tour->departures->first()
            : $tour->departures()->published()->orderBy('departure_date')->orderBy('sort_order')->first();

        return $this->payload($tour, 'Tóm tắt nhanh tour', $this->summaryFor($tour), [
            ['label' => 'Phạm vi', 'value' => $tour->scope?->label()],
            ['label' => 'Thời lượng', 'value' => $this->durationLabel($tour)],
            ['label' => 'Điểm đến', 'value' => $tour->destination?->name],
            ['label' => 'Vùng miền', 'value' => $tour->region?->name],
            ['label' => 'Khởi hành', 'value' => $tour->departure_location],
            ['label' => 'Phương tiện', 'value' => $tour->transport ?: $nextDeparture?->transport_label],
            ['label' => 'Lịch gần nhất', 'value' => optional($nextDeparture?->departure_date)->format('d/m/Y')],
            ['label' => 'Giá tham khảo', 'value' => $this->priceLabel($nextDeparture?->sale_price ?? $tour->sale_price, $nextDeparture?->base_price ?? $tour->base_price)],
        ], [
            'So sánh lịch khởi hành, thời lượng, tiêu chuẩn dịch vụ và giá đang hiển thị trước khi gửi yêu cầu.',
            'Nếu đi theo nhóm riêng, hãy ghi rõ số khách, ngày dự kiến và điểm khởi hành để nhận tư vấn sát nhu cầu.',
        ], [
            $tour->primaryCategory ? ['label' => $tour->primaryCategory->name, 'url' => route('tour-categories.show', $tour->primaryCategory)] : null,
            $tour->destination ? ['label' => $tour->destination->name, 'url' => route('destinations.show', $tour->destination)] : null,
            $tour->region ? ['label' => $tour->region->name, 'url' => route('regions.show', $tour->region)] : null,
            ['label' => $tour->scope?->label() ?: 'Danh sách tour', 'url' => $tour->scope instanceof TourScope ? route($tour->scope->routeName()) : route('tours.domestic')],
        ], [
            'label' => $tour->cta_mode === 'contact' ? 'Tư vấn' : 'Kiểm tra chỗ ngay',
            'modal' => true,
            'source' => 'tour',
            'tour_id' => $tour->id,
            'context' => $tour->title,
            'subject' => $tour->title,
        ]);
    }

    public function forTourListing(array $page, Collection|LengthAwarePaginator $tours): array
    {
        $source = data_get($page, 'fixed_category')
            ?: data_get($page, 'fixed_country')
            ?: data_get($page, 'fixed_destination')
            ?: data_get($page, 'fixed_region')
            ?: data_get($page, 'landing');
        $tourCollection = $tours instanceof LengthAwarePaginator ? $tours->getCollection() : $tours;
        $total = $tours instanceof LengthAwarePaginator ? $tours->total() : $tourCollection->count();

        return $this->payload($source, 'Tóm tắt nhanh danh sách tour', $this->summaryFor($source, [
            data_get($source, 'excerpt'),
            data_get($source, 'content'),
            data_get($page, 'page_description'),
            data_get($source, 'meta_description'),
        ]), [
            ['label' => 'Ngữ cảnh', 'value' => data_get($page, 'context_label')],
            ['label' => 'Tổng tour phù hợp', 'value' => $this->countLabel((int) $total, 'tour')],
            ['label' => 'Đang hiển thị', 'value' => $this->countLabel($tourCollection->count(), 'tour')],
            ['label' => 'Phạm vi', 'value' => $this->scopeLabel(data_get($source, 'scope'))],
        ], [
            'Dùng bộ lọc và thanh tìm kiếm để thu hẹp tour theo điểm đến, ngân sách, ngày đi hoặc phương tiện.',
            'Mở trang tour chi tiết để kiểm tra lịch khởi hành, bảng giá và điều kiện áp dụng từ dữ liệu đang publish.',
        ], [
            data_get($page, 'fixed_category') ? ['label' => 'Danh mục: '.data_get($page, 'fixed_category.name'), 'url' => route('tour-categories.show', data_get($page, 'fixed_category'))] : null,
            data_get($page, 'fixed_country') ? ['label' => 'Quốc gia: '.data_get($page, 'fixed_country.name'), 'url' => route('countries.show', ['slug' => data_get($page, 'fixed_country.slug')])] : null,
            data_get($page, 'fixed_destination') ? ['label' => 'Điểm đến: '.data_get($page, 'fixed_destination.name'), 'url' => route('destinations.show', data_get($page, 'fixed_destination'))] : null,
            data_get($page, 'fixed_region') ? ['label' => 'Vùng miền: '.data_get($page, 'fixed_region.name'), 'url' => route('regions.show', data_get($page, 'fixed_region'))] : null,
            ['label' => 'Liên hệ tư vấn', 'url' => route('contact')],
        ], $this->contactCta());
    }

    public function forService(Service $service): array
    {
        $service->loadMissing('category');
        $categoryUrl = $service->category && $service->category->taxonomy === 'service'
            ? route('service-categories.show', ['category' => $service->category->slug])
            : route('services.index');

        return $this->payload($service, 'Tóm tắt nhanh dịch vụ', $this->summaryFor($service), [
            ['label' => 'Danh mục', 'value' => $service->category?->name ?: 'Dịch vụ du lịch'],
            ['label' => 'Trạng thái', 'value' => $service->status === 'published' ? 'Đang hoạt động' : 'Đang cập nhật'],
            ['label' => 'Ghi chú giá', 'value' => $service->price_note],
            ['label' => 'Dịch vụ nổi bật', 'value' => $service->is_featured ? 'Có' : null],
        ], [
            'Đọc phần phạm vi dịch vụ và chuẩn bị sẵn điểm đến, thời gian đi, số khách hoặc hồ sơ cần hỗ trợ.',
            'Nếu cần báo giá hoặc timeline xử lý, gửi yêu cầu để đội ngũ tư vấn dựa trên thông tin thực tế.',
        ], [
            ['label' => 'Tất cả dịch vụ', 'url' => route('services.index')],
            $service->category ? ['label' => $service->category->name, 'url' => $categoryUrl] : null,
            ['label' => 'Liên hệ', 'url' => route('contact')],
        ], [
            'label' => 'Nhận tư vấn dịch vụ',
            'modal' => true,
            'source' => 'service',
            'service_id' => $service->id,
            'context' => $service->title,
            'subject' => $service->title,
        ]);
    }

    public function forServiceListing(?LandingPage $landing, ?ContentCategory $category, Collection $services, Collection $categories): array
    {
        $source = $category ?: $landing;

        return $this->payload($source, 'Tóm tắt nhanh nhóm dịch vụ', $this->summaryFor($source, [
            $category?->description,
            $category?->content,
            $landing?->hero_excerpt,
            $landing?->intro_excerpt,
            $landing?->meta_description,
        ]), [
            ['label' => 'Danh mục đang xem', 'value' => $category?->name ?: 'Tất cả dịch vụ'],
            ['label' => 'Dịch vụ đang hiển thị', 'value' => $this->countLabel($services->count(), 'dịch vụ')],
            ['label' => 'Nhóm dịch vụ có URL riêng', 'value' => $this->countLabel($categories->count(), 'nhóm')],
        ], [
            'Dùng hub dịch vụ để chọn đúng nhóm nhu cầu trước khi mở trang chi tiết hoặc gửi yêu cầu tư vấn.',
            'Các ghi chú về giá, phạm vi hỗ trợ và CTA được lấy từ dữ liệu dịch vụ đang publish.',
        ], [
            ['label' => 'Tất cả dịch vụ', 'url' => route('services.index')],
            $category ? ['label' => $category->name, 'url' => route('service-categories.show', ['category' => $category->slug])] : null,
            ['label' => 'Liên hệ tư vấn', 'url' => route('contact')],
        ], $this->contactCta());
    }

    public function forBlogPost(BlogPost $post): array
    {
        $post->loadMissing(['category.parent', 'countryDestination', 'destination.country']);

        return $this->payload($post, 'Tóm tắt nhanh bài viết', $this->summaryFor($post), [
            ['label' => 'Danh mục', 'value' => $post->category?->name],
            ['label' => 'Quốc gia', 'value' => $post->countryDestination?->name],
            ['label' => 'Điểm đến', 'value' => $post->destination?->name],
            ['label' => 'Tác giả', 'value' => $post->author_name],
            ['label' => 'Ngày đăng', 'value' => optional($post->published_at)->format('d/m/Y')],
            ['label' => 'Cập nhật', 'value' => optional($post->updated_at)->format('d/m/Y')],
            ['label' => 'Thời gian đọc', 'value' => $post->reading_time_minutes ? $post->reading_time_minutes.' phút' : null],
        ], [
            'Dùng bài viết như nguồn tham khảo trước khi so sánh tour, dịch vụ hoặc hồ sơ cần chuẩn bị.',
            'Các liên kết danh mục giúp mở rộng bối cảnh đọc theo cùng chủ đề du lịch.',
        ], [
            ['label' => 'Tất cả bài viết', 'url' => route('blog.index')],
            $post->category ? ['label' => $post->category->name, 'url' => route('blog-categories.show', ['slug' => $post->category->slug])] : null,
            $post->countryDestination ? ['label' => $post->countryDestination->name, 'url' => route('countries.show', ['slug' => $post->countryDestination->slug])] : null,
            $post->destination ? ['label' => $post->destination->name, 'url' => route('destinations.show', $post->destination)] : null,
            ['label' => 'Liên hệ tư vấn', 'url' => route('contact')],
        ], $this->contactCta('Gửi yêu cầu theo bài viết'));
    }

    public function forBlogListing(?LandingPage $landing, ?ContentCategory $category, LengthAwarePaginator $posts): array
    {
        $source = $category ?: $landing;

        return $this->payload($source, 'Tóm tắt nhanh chủ đề blog', $this->summaryFor($source, [
            $category?->description,
            $landing?->hero_excerpt,
            $landing?->intro_excerpt,
            $landing?->meta_description,
        ]), [
            ['label' => 'Chủ đề đang xem', 'value' => $category?->name ?: 'Tất cả bài viết'],
            ['label' => 'Tổng bài phù hợp', 'value' => $this->countLabel($posts->total(), 'bài')],
            ['label' => 'Đang hiển thị', 'value' => $this->countLabel($posts->count(), 'bài')],
        ], [
            'Dùng danh mục blog để gom các bài cùng intent trước khi đọc sâu từng nội dung chi tiết.',
            'Khi bài viết liên quan đến tour hoặc thủ tục, hãy đối chiếu thêm trang dịch vụ hoặc gửi yêu cầu tư vấn.',
        ], [
            ['label' => 'Tất cả bài viết', 'url' => route('blog.index')],
            $category ? ['label' => $category->name, 'url' => route('blog-categories.show', ['slug' => $category->slug])] : null,
            ['label' => 'Dịch vụ du lịch', 'url' => route('services.index')],
        ], $this->contactCta());
    }

    public function forLanding(LandingPage $landing): array
    {
        return $this->payload($landing, 'Tóm tắt nhanh landing page', $this->summaryFor($landing, [
            $landing->hero_excerpt,
            $landing->intro_excerpt,
            $landing->body,
            $landing->meta_description,
        ]), [
            ['label' => 'Loại trang', 'value' => $landing->template_key ?: 'generic'],
            ['label' => 'Chế độ nội dung', 'value' => $landing->editor_mode ?: LandingPage::EDITOR_MODE_BLOCKS],
            ['label' => 'Cập nhật', 'value' => optional($landing->updated_at)->format('d/m/Y')],
        ], [
            'Landing này gom nội dung đang publish để giúp người đọc hiểu nhanh mục tiêu trang trước khi xem các block chi tiết.',
            'Ưu tiên kiểm tra CTA và liên kết nội bộ hiển thị trên trang để đi tới bước tư vấn phù hợp.',
        ], [
            ['label' => 'Trang chủ', 'url' => route('home')],
            ['label' => 'Tour trong nước', 'url' => route('tours.domestic')],
            ['label' => 'Liên hệ', 'url' => route('contact')],
        ], $this->contactCta());
    }

    public function forLandingBlock(array $block, ?LandingPage $landing = null): array
    {
        if (! (bool) ($block['is_enabled'] ?? true)) {
            return ['is_enabled' => false];
        }

        $summary = GeoContent::plainText($block['answer_summary'] ?? '', GeoContent::MAX_SUMMARY_LENGTH)
            ?: $this->summaryFor($landing);
        $notes = GeoContent::normalizeDecisionNotes($block['decision_notes'] ?? []);

        return $this->payload(null, trim((string) ($block['title'] ?? '')) ?: 'Tóm tắt nhanh', $summary, [
            ['label' => 'Nguồn nội dung', 'value' => $landing?->title],
            ['label' => 'Cập nhật', 'value' => optional($landing?->updated_at)->format('d/m/Y')],
        ], $notes !== [] ? $notes : [
            'Block này được đặt thủ công trong landing builder để nhấn mạnh nội dung cần trích dẫn.',
        ], [
            ['label' => 'Liên hệ', 'url' => route('contact')],
            ['label' => 'Danh sách tour', 'url' => route('tours.domestic')],
        ], $this->contactCta());
    }

    protected function payload(?Model $source, string $title, ?string $summary, array $facts, array $fallbackNotes, array $links, array $cta): array
    {
        if (! (bool) config('frontsite_geo.enabled', true)) {
            return ['is_enabled' => false];
        }

        $config = $source ? GeoContent::normalize($source->getAttribute('geo_config')) : GeoContent::defaultConfig();

        if (! (bool) ($config['is_enabled'] ?? true)) {
            return ['is_enabled' => false];
        }

        $summary = ($config['answer_summary'] ?? '') !== ''
            ? $config['answer_summary']
            : GeoContent::plainText($summary ?? '', GeoContent::MAX_SUMMARY_LENGTH);
        $facts = $this->filterRows($facts);
        $decisionNotes = ($config['decision_notes'] ?? []) !== []
            ? $config['decision_notes']
            : GeoContent::normalizeDecisionNotes($fallbackNotes);
        $links = $this->filterRows($links, ['label', 'url']);

        if ($summary === '' && $facts === [] && $decisionNotes === [] && $links === []) {
            return ['is_enabled' => false];
        }

        return [
            'is_enabled' => true,
            'title' => $title,
            'summary' => $summary,
            'facts' => $facts,
            'decision_notes' => $decisionNotes,
            'links' => $links,
            'updated_label' => ($config['updated_label'] ?? '') !== ''
                ? $config['updated_label']
                : (optional($source?->updated_at)->format('d/m/Y') ? 'Cập nhật '.optional($source?->updated_at)->format('d/m/Y') : ''),
            'cta' => $cta,
        ];
    }

    protected function summaryFor(?object $source, array $fallbacks = []): string
    {
        $config = $source instanceof Model ? GeoContent::normalize($source->getAttribute('geo_config')) : GeoContent::defaultConfig();

        if (($config['answer_summary'] ?? '') !== '') {
            return $config['answer_summary'];
        }

        $values = $fallbacks !== [] ? $fallbacks : [
            data_get($source, 'excerpt'),
            data_get($source, 'content'),
            data_get($source, 'meta_description'),
        ];

        foreach ($values as $value) {
            $text = GeoContent::plainText($value ?? '', GeoContent::MAX_SUMMARY_LENGTH);

            if ($text !== '') {
                return $text;
            }
        }

        return '';
    }

    protected function contactCta(string $label = 'Gửi yêu cầu tư vấn'): array
    {
        return [
            'label' => $label,
            'url' => route('contact'),
            'modal' => false,
        ];
    }

    protected function countLabel(int $count, string $unit): string
    {
        return number_format($count, 0, ',', '.').' '.$unit;
    }

    protected function durationLabel(Tour $tour): ?string
    {
        $days = (int) ($tour->duration_days ?? 0);
        $nights = (int) ($tour->duration_nights ?? 0);

        if ($days > 0 && $nights > 0) {
            return $days.' ngày '.$nights.' đêm';
        }

        if ($days > 0) {
            return $days.' ngày';
        }

        return null;
    }

    protected function filterRows(array $rows, array $requiredKeys = ['label', 'value']): array
    {
        return collect($rows)
            ->filter(fn ($row) => is_array($row))
            ->map(function (array $row): array {
                $row['label'] = trim((string) ($row['label'] ?? ''));

                if (array_key_exists('value', $row)) {
                    $row['value'] = RichText::normalizePlain((string) $row['value']);
                }

                if (array_key_exists('url', $row)) {
                    $row['url'] = trim((string) $row['url']);
                }

                return $row;
            })
            ->filter(function (array $row) use ($requiredKeys): bool {
                foreach ($requiredKeys as $key) {
                    if (! filled($row[$key] ?? null)) {
                        return false;
                    }
                }

                return true;
            })
            ->values()
            ->all();
    }

    protected function priceLabel(mixed $salePrice, mixed $basePrice): ?string
    {
        $price = filled($salePrice) ? (int) $salePrice : (filled($basePrice) ? (int) $basePrice : null);

        return $price ? number_format($price, 0, ',', '.').'đ' : null;
    }

    protected function scopeLabel(mixed $scope): ?string
    {
        if ($scope instanceof TourScope) {
            return $scope->label();
        }

        $scope = trim((string) $scope);

        return $scope === '' ? null : (TourScope::tryFrom($scope)?->label() ?: Str::headline($scope));
    }
}
