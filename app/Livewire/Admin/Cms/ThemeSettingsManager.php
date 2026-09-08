<?php

namespace App\Livewire\Admin\Cms;

use App\Livewire\Admin\Cms\Concerns\HandlesMediaUploads;
use App\Livewire\Admin\Cms\Concerns\InteractsWithEditorContent;
use App\Services\Cms\SiteSettingsManager;
use App\Services\Frontsite\FrontsiteCache;
use App\Services\Travel\CustomerLoyaltyApi;
use App\Services\Travel\CustomerLoyaltyApiException;
use App\Support\FooterSocialLinks;
use App\Support\FrontsiteSectionHeadings;
use App\Support\GoogleMapsEmbedUrl;
use App\Support\SitewideHtmlSnippets;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Src\Domains\Cms\Models\SiteSetting;
use Throwable;

#[Layout('layouts.app')]
#[Title('Cấu hình theme')]
class ThemeSettingsManager extends Component
{
    use HandlesMediaUploads;
    use InteractsWithEditorContent;
    use WithFileUploads;

    public array $form = [];

    public array $frontsiteCacheStatus = [];

    public ?array $frontsiteCacheClearResult = null;

    public ?array $customerLoyaltyApiCheckResult = null;

    public mixed $faviconUpload = null;

    public mixed $logoUpload = null;

    public mixed $ogImageUpload = null;

    public SiteSetting $settings;

    public function addTourTermItem(): void
    {
        $this->form['tour_terms_items'][] = $this->blankTourTermItem();
    }

    public function addFooterSocialLink(): void
    {
        $this->form['structured_data'][FooterSocialLinks::STRUCTURED_DATA_KEY][] = FooterSocialLinks::blankItem();
    }

    public function mount(SiteSettingsManager $site, FrontsiteCache $frontsiteCache): void
    {
        $this->settings = $site->current();
        $this->selectedLibraryMediaSelections = [];
        $this->fillForm();
        $this->refreshFrontsiteCacheStatus($frontsiteCache);
    }

    public function removeTourTermItem(int $index): void
    {
        $items = array_values(array_filter(
            $this->form['tour_terms_items'] ?? [],
            fn (mixed $item, int $itemIndex): bool => $itemIndex !== $index,
            ARRAY_FILTER_USE_BOTH,
        ));

        $this->form['tour_terms_items'] = $items !== [] ? $items : [$this->blankTourTermItem()];
    }

    public function removeFooterSocialLink(int $index): void
    {
        $items = array_values(array_filter(
            $this->form['structured_data'][FooterSocialLinks::STRUCTURED_DATA_KEY] ?? [],
            fn (mixed $item, int $itemIndex): bool => $itemIndex !== $index,
            ARRAY_FILTER_USE_BOTH,
        ));

        $this->form['structured_data'][FooterSocialLinks::STRUCTURED_DATA_KEY] = $items;
    }

    public function moveFooterSocialLink(int $index, string $direction): void
    {
        $items = array_values($this->form['structured_data'][FooterSocialLinks::STRUCTURED_DATA_KEY] ?? []);
        $targetIndex = $direction === 'up' ? $index - 1 : $index + 1;

        if (! isset($items[$index], $items[$targetIndex])) {
            return;
        }

        [$items[$index], $items[$targetIndex]] = [$items[$targetIndex], $items[$index]];

        $this->form['structured_data'][FooterSocialLinks::STRUCTURED_DATA_KEY] = $items;
    }

    public function save(SiteSettingsManager $site, FrontsiteCache $frontsiteCache): void
    {
        $this->resetErrorBag('customerLoyaltyApi');
        $this->customerLoyaltyApiCheckResult = null;

        $this->form['ga_measurement_id'] = $this->normalizeGaMeasurementId($this->form['ga_measurement_id'] ?? null);
        $this->form['facebook_pixel_id'] = $this->normalizeFacebookPixelId($this->form['facebook_pixel_id'] ?? null);
        $this->form['map_embed_url'] = GoogleMapsEmbedUrl::normalize($this->form['map_embed_url'] ?? null);
        $this->form['after_header_html'] = SitewideHtmlSnippets::htmlSnippetContent($this->form['after_header_html'] ?? null);
        $this->form['end_body_html'] = SitewideHtmlSnippets::endBodyHtml($this->form['end_body_html'] ?? null);
        $this->form['google_recaptcha_v3_enabled'] = (bool) ($this->form['google_recaptcha_v3_enabled'] ?? false);
        $this->form['google_recaptcha_v3_site_key'] = $this->nullableTrimmedString($this->form['google_recaptcha_v3_site_key'] ?? null);
        $this->form['google_recaptcha_v3_secret_key'] = $this->nullableTrimmedString($this->form['google_recaptcha_v3_secret_key'] ?? null);
        $this->form['google_recaptcha_v3_min_score'] = $this->normalizeRecaptchaScore($this->form['google_recaptcha_v3_min_score'] ?? null);
        $this->form['customer_loyalty_api_base_url'] = $this->normalizeCustomerLoyaltyBaseUrl($this->form['customer_loyalty_api_base_url'] ?? null);
        $this->form['customer_loyalty_api_username'] = $this->nullableTrimmedString($this->form['customer_loyalty_api_username'] ?? null);
        $this->form['customer_loyalty_api_password'] = $this->nullableTrimmedString($this->form['customer_loyalty_api_password'] ?? null);

        $validated = $this->validate([
            'form.site_name' => ['required', 'string', 'max:255'],
            'form.site_tagline' => ['nullable', 'string', 'max:255'],
            'form.site_description' => ['nullable', 'string'],
            'form.active_theme' => ['required', 'string', 'max:100'],
            'form.company_name' => ['nullable', 'string', 'max:255'],
            'form.about_summary' => ['nullable', 'string'],
            'form.tour_terms_title' => ['nullable', 'string', 'max:255'],
            'form.tour_terms_items' => ['nullable', 'array'],
            'form.tour_terms_items.*.title' => ['nullable', 'string', 'max:255'],
            'form.tour_terms_items.*.content' => ['nullable', 'string', 'max:5000'],
            'form.address' => ['nullable', 'string', 'max:255'],
            'form.phone' => ['nullable', 'string', 'max:50'],
            'form.hotline' => ['nullable', 'string', 'max:50'],
            'form.primary_email' => ['nullable', 'email'],
            'form.support_email' => ['nullable', 'email'],
            'form.sales_email' => ['nullable', 'email'],
            'form.mail_from_name' => ['nullable', 'string', 'max:255'],
            'form.mail_from_address' => ['nullable', 'email'],
            'form.mail_contact_recipient' => ['nullable', 'email'],
            'form.map_embed_url' => [
                'nullable',
                'url',
                fn (string $attribute, mixed $value, \Closure $fail) => GoogleMapsEmbedUrl::isEmbeddable($value)
                    ?: $fail('Google Maps URL phải là URL có thể nhúng, ví dụ link Embed a map hoặc link Google Maps có tọa độ/địa điểm.'),
            ],
            'form.facebook_url' => ['nullable', 'url'],
            'form.youtube_url' => ['nullable', 'url'],
            'form.tiktok_url' => ['nullable', 'url'],
            'form.instagram_url' => ['nullable', 'url'],
            'form.zalo_url' => ['nullable', 'url'],
            'form.messenger_url' => ['nullable', 'url'],
            'form.linkedin_url' => ['nullable', 'url'],
            'form.experience_years' => ['nullable', 'integer', 'min:0'],
            'form.completed_projects_count' => ['nullable', 'integer', 'min:0'],
            'form.team_size' => ['nullable', 'integer', 'min:0'],
            'form.quality_badge_label' => ['nullable', 'string', 'max:255'],
            'form.copyright_text' => ['nullable', 'string', 'max:255'],
            'form.seo_title' => ['nullable', 'string', 'max:255'],
            'form.seo_description' => ['nullable', 'string', 'max:500'],
            'form.seo_keywords' => ['nullable', 'string', 'max:255'],
            'form.seo_robots' => ['nullable', 'string', 'max:255'],
            'form.ga_measurement_id' => ['nullable', 'string', 'max:30', 'regex:/^G-[A-Z0-9]+$/'],
            'form.facebook_pixel_id' => ['nullable', 'string', 'max:30', 'regex:/^\d{5,20}$/'],
            'form.after_header_html' => ['nullable', 'string', 'max:100000'],
            'form.end_body_html' => ['nullable', 'string', 'max:100000'],
            'form.google_recaptcha_v3_enabled' => ['boolean'],
            'form.google_recaptcha_v3_site_key' => [
                Rule::requiredIf(fn (): bool => (bool) ($this->form['google_recaptcha_v3_enabled'] ?? false)),
                'nullable',
                'string',
                'max:255',
            ],
            'form.google_recaptcha_v3_secret_key' => [
                Rule::requiredIf(fn (): bool => (bool) ($this->form['google_recaptcha_v3_enabled'] ?? false) && blank($this->settings->google_recaptcha_v3_secret_key)),
                'nullable',
                'string',
                'max:2000',
            ],
            'form.google_recaptcha_v3_min_score' => ['required', 'numeric', 'min:0', 'max:1'],
            'form.customer_loyalty_api_base_url' => ['nullable', 'url', 'max:500'],
            'form.customer_loyalty_api_username' => ['nullable', 'string', 'max:255'],
            'form.customer_loyalty_api_password' => ['nullable', 'string', 'max:2000'],
            'form.frontsite_section_headings' => ['nullable', 'array'],
            'form.frontsite_section_headings.*.is_visible' => ['boolean'],
            'form.frontsite_section_headings.*.title' => ['nullable', 'string', 'max:255'],
            'form.frontsite_section_headings.*.description' => ['nullable', 'string', 'max:1000'],
            'form.structured_data.organization.image_url' => ['nullable', 'string', 'max:500'],
            'form.structured_data.local_business.price_range' => ['nullable', 'string', 'max:255'],
            'form.structured_data.company.legal_name' => ['nullable', 'string', 'max:255'],
            'form.structured_data.company.business_license' => ['nullable', 'string', 'max:120'],
            'form.structured_data.company.international_travel_license' => ['nullable', 'string', 'max:120'],
            'form.structured_data.company.tax_code' => ['nullable', 'string', 'max:80'],
            'form.structured_data.address.street_address' => ['nullable', 'string', 'max:255'],
            'form.structured_data.address.address_locality' => ['nullable', 'string', 'max:255'],
            'form.structured_data.address.address_region' => ['nullable', 'string', 'max:255'],
            'form.structured_data.address.postal_code' => ['nullable', 'string', 'max:30'],
            'form.structured_data.address.address_country' => ['nullable', 'string', 'max:255'],
            'form.structured_data.'.FooterSocialLinks::STRUCTURED_DATA_KEY => ['nullable', 'array'],
            'form.structured_data.'.FooterSocialLinks::STRUCTURED_DATA_KEY.'.*.platform' => ['required', Rule::in(FooterSocialLinks::platformKeys())],
            'form.structured_data.'.FooterSocialLinks::STRUCTURED_DATA_KEY.'.*.label' => ['nullable', 'string', 'max:120'],
            'form.structured_data.'.FooterSocialLinks::STRUCTURED_DATA_KEY.'.*.description' => ['nullable', 'string', 'max:160'],
            'form.structured_data.'.FooterSocialLinks::STRUCTURED_DATA_KEY.'.*.url' => ['nullable', 'url', 'max:500'],
            'form.structured_data.'.FooterSocialLinks::STRUCTURED_DATA_KEY.'.*.is_active' => ['boolean'],
        ]);

        $validatedForm = $validated['form'];
        $googleRecaptchaSecretKey = (string) ($validatedForm['google_recaptcha_v3_secret_key'] ?? '');
        $customerLoyaltyApiPassword = (string) ($validatedForm['customer_loyalty_api_password'] ?? '');
        $customerLoyaltyApiCredentialsChanged = $this->customerLoyaltyApiCredentialsChanged($validatedForm, $customerLoyaltyApiPassword);

        if ($googleRecaptchaSecretKey === '') {
            unset($validatedForm['google_recaptcha_v3_secret_key']);
        }

        if ($customerLoyaltyApiPassword === '') {
            unset($validatedForm['customer_loyalty_api_password']);
        }

        if ($customerLoyaltyApiCredentialsChanged) {
            $validatedForm['customer_loyalty_api_token'] = null;
            $validatedForm['customer_loyalty_api_token_expires_at'] = null;
            $validatedForm['customer_loyalty_api_token_refreshed_at'] = null;
        }

        $structuredData = $validatedForm['structured_data'] ?? [];
        $structuredData[FrontsiteSectionHeadings::STRUCTURED_DATA_KEY] = FrontsiteSectionHeadings::prepare(
            $validatedForm['frontsite_section_headings'] ?? [],
        );
        $structuredData[FooterSocialLinks::STRUCTURED_DATA_KEY] = FooterSocialLinks::storedConfig(
            data_get($structuredData, FooterSocialLinks::STRUCTURED_DATA_KEY, []),
        );
        unset($validatedForm['frontsite_section_headings']);

        $this->settings->fill([
            ...$validatedForm,
            'site_description' => $this->plainEditorContent($validatedForm['site_description'] ?? null),
            'about_summary' => $this->plainEditorContent($validatedForm['about_summary'] ?? null),
            'tour_terms_content' => null,
            'tour_terms_items' => $this->normalizeTourTermItems($validatedForm['tour_terms_items'] ?? []),
            'seo_description' => $this->plainEditorContent($validatedForm['seo_description'] ?? null),
            'structured_data' => $this->filterStructuredData($structuredData),
        ]);
        $this->settings->save();

        $this->syncSingleImageSelection($this->settings, 'logoUpload', 'logo');
        $this->syncSingleImageSelection($this->settings, 'faviconUpload', 'favicon');
        $this->syncSingleImageSelection($this->settings, 'ogImageUpload', 'og_image');

        $this->logoUpload = null;
        $this->faviconUpload = null;
        $this->ogImageUpload = null;
        $this->selectedLibraryMediaSelections = [];

        $site->refresh();
        $this->settings = $site->current();
        $this->fillForm();
        $this->refreshFrontsiteCacheStatus($frontsiteCache);

        session()->flash('status', 'Đã lưu cấu hình theme và SEO.');
    }

    public function checkCustomerLoyaltyApi(CustomerLoyaltyApi $loyaltyApi, SiteSettingsManager $site): void
    {
        $this->resetErrorBag('customerLoyaltyApi');
        $this->customerLoyaltyApiCheckResult = null;

        try {
            $token = $loyaltyApi->refreshToken();
            $site->refresh();
            $this->settings = $site->current();
            $this->fillForm();

            $this->customerLoyaltyApiCheckResult = [
                'status' => 'success',
                'message' => 'Kiểm tra API quà thành công. Token đã sẵn sàng và sẽ tự gia hạn khi cần.',
                'token_preview' => $this->maskToken($token),
                'checked_at_label' => $this->formatCacheTimestamp(now()->toIso8601String()),
            ];
        } catch (CustomerLoyaltyApiException $exception) {
            $this->customerLoyaltyApiCheckResult = [
                'status' => 'error',
                'message' => $exception->getMessage(),
                'checked_at_label' => $this->formatCacheTimestamp(now()->toIso8601String()),
            ];

            $this->addError('customerLoyaltyApi', $exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            $message = 'Không thể kiểm tra đăng nhập API quà. Vui lòng kiểm tra log hệ thống.';
            $this->customerLoyaltyApiCheckResult = [
                'status' => 'error',
                'message' => $message,
                'checked_at_label' => $this->formatCacheTimestamp(now()->toIso8601String()),
            ];

            $this->addError('customerLoyaltyApi', $message);
        }
    }

    public function clearFrontsiteCache(FrontsiteCache $frontsiteCache): void
    {
        $this->resetErrorBag('frontsiteCache');

        try {
            $result = $frontsiteCache->forgetAll();

            $this->frontsiteCacheClearResult = [
                ...$result,
                'status' => 'success',
                'message' => 'Đã xóa cache frontsite. Request public kế tiếp sẽ render lại rồi tạo cache mới.',
                'cleared_at_label' => $this->formatCacheTimestamp($result['cleared_at'] ?? null),
            ];

            session()->flash('status', 'Đã xóa cache frontsite.');
        } catch (Throwable $exception) {
            report($exception);

            $this->frontsiteCacheClearResult = [
                'status' => 'error',
                'message' => 'Không thể xóa cache frontsite. Vui lòng kiểm tra log hệ thống.',
                'cleared_at_label' => $this->formatCacheTimestamp(now()->toIso8601String()),
            ];

            $this->addError('frontsiteCache', 'Không thể xóa cache frontsite. Vui lòng kiểm tra log hệ thống.');
        }

        $this->refreshFrontsiteCacheStatus($frontsiteCache);
    }

    public function render()
    {
        return view('livewire.admin.cms.theme-settings-manager', [
            'selectedFaviconLibraryMedia' => data_get($this->resolveSelectedUploadMediaPayload(['faviconUpload']), 'faviconUpload'),
            'selectedLogoLibraryMedia' => data_get($this->resolveSelectedUploadMediaPayload(['logoUpload']), 'logoUpload'),
            'selectedOgImageLibraryMedia' => data_get($this->resolveSelectedUploadMediaPayload(['ogImageUpload']), 'ogImageUpload'),
            'customerLoyaltyApiStatus' => $this->customerLoyaltyApiStatus(),
        ]);
    }

    protected function fillForm(): void
    {
        $tourTermsItems = $this->normalizeTourTermItems($this->settings->tour_terms_items ?? []);

        if ($tourTermsItems === [] && filled($this->settings->tour_terms_content)) {
            $tourTermsItems = [[
                'title' => $this->settings->tour_terms_title ?: 'Quy định chung',
                'content' => (string) $this->settings->tour_terms_content,
            ]];
        }

        $this->form = [
            'site_name' => $this->settings->site_name,
            'site_tagline' => $this->settings->site_tagline,
            'site_description' => $this->settings->site_description,
            'active_theme' => $this->settings->active_theme,
            'company_name' => $this->settings->company_name,
            'about_summary' => $this->settings->about_summary,
            'tour_terms_title' => $this->settings->tour_terms_title,
            'tour_terms_items' => $tourTermsItems !== [] ? $tourTermsItems : [$this->blankTourTermItem()],
            'address' => $this->settings->address,
            'phone' => $this->settings->phone,
            'hotline' => $this->settings->hotline,
            'primary_email' => $this->settings->primary_email,
            'support_email' => $this->settings->support_email,
            'sales_email' => $this->settings->sales_email,
            'mail_from_name' => $this->settings->mail_from_name,
            'mail_from_address' => $this->settings->mail_from_address,
            'mail_contact_recipient' => $this->settings->mail_contact_recipient,
            'map_embed_url' => $this->settings->map_embed_url,
            'facebook_url' => $this->settings->facebook_url,
            'youtube_url' => $this->settings->youtube_url,
            'tiktok_url' => $this->settings->tiktok_url,
            'instagram_url' => $this->settings->instagram_url,
            'zalo_url' => $this->settings->zalo_url,
            'messenger_url' => $this->settings->messenger_url,
            'linkedin_url' => $this->settings->linkedin_url,
            'experience_years' => $this->settings->experience_years,
            'completed_projects_count' => $this->settings->completed_projects_count,
            'team_size' => $this->settings->team_size,
            'quality_badge_label' => $this->settings->quality_badge_label,
            'copyright_text' => $this->settings->copyright_text,
            'seo_title' => $this->settings->seo_title,
            'seo_description' => $this->settings->seo_description,
            'seo_keywords' => $this->settings->seo_keywords,
            'seo_robots' => $this->settings->seo_robots ?: 'index,follow',
            'ga_measurement_id' => $this->normalizeGaMeasurementId($this->settings->ga_measurement_id),
            'facebook_pixel_id' => $this->normalizeFacebookPixelId($this->settings->facebook_pixel_id),
            'after_header_html' => $this->settings->after_header_html,
            'end_body_html' => $this->settings->end_body_html,
            'google_recaptcha_v3_enabled' => (bool) $this->settings->google_recaptcha_v3_enabled,
            'google_recaptcha_v3_site_key' => $this->settings->google_recaptcha_v3_site_key ?: config('services.google_recaptcha_v3.site_key'),
            'google_recaptcha_v3_secret_key' => '',
            'google_recaptcha_v3_min_score' => $this->normalizeRecaptchaScore($this->settings->google_recaptcha_v3_min_score ?: config('services.google_recaptcha_v3.min_score', 0.5)),
            'customer_loyalty_api_base_url' => $this->settings->customer_loyalty_api_base_url ?: config('customer_loyalty.base_url'),
            'customer_loyalty_api_username' => $this->settings->customer_loyalty_api_username,
            'customer_loyalty_api_password' => '',
            'frontsite_section_headings' => FrontsiteSectionHeadings::prepare(
                data_get($this->settings->structured_data, FrontsiteSectionHeadings::STRUCTURED_DATA_KEY),
            ),
            'structured_data' => [
                FooterSocialLinks::STRUCTURED_DATA_KEY => FooterSocialLinks::formItems(
                    data_get($this->settings->structured_data, FooterSocialLinks::STRUCTURED_DATA_KEY),
                    $this->settings,
                ),
                'company' => [
                    'legal_name' => data_get($this->settings->structured_data, 'company.legal_name'),
                    'business_license' => data_get($this->settings->structured_data, 'company.business_license'),
                    'international_travel_license' => data_get($this->settings->structured_data, 'company.international_travel_license'),
                    'tax_code' => data_get($this->settings->structured_data, 'company.tax_code'),
                ],
                'organization' => [
                    'image_url' => data_get($this->settings->structured_data, 'organization.image_url'),
                ],
                'local_business' => [
                    'price_range' => data_get($this->settings->structured_data, 'local_business.price_range'),
                ],
                'address' => [
                    'street_address' => data_get($this->settings->structured_data, 'address.street_address') ?: $this->settings->address,
                    'address_locality' => data_get($this->settings->structured_data, 'address.address_locality'),
                    'address_region' => data_get($this->settings->structured_data, 'address.address_region'),
                    'postal_code' => data_get($this->settings->structured_data, 'address.postal_code'),
                    'address_country' => data_get($this->settings->structured_data, 'address.address_country'),
                ],
            ],
        ];
    }

    protected function customerLoyaltyApiStatus(): array
    {
        $baseUrl = $this->settings->customer_loyalty_api_base_url ?: config('customer_loyalty.base_url');
        $username = $this->settings->customer_loyalty_api_username ?: config('customer_loyalty.username');
        $hasPassword = filled($this->settings->customer_loyalty_api_password ?: config('customer_loyalty.password'));
        $hasToken = filled($this->settings->customer_loyalty_api_token ?: config('customer_loyalty.token'));
        $expiresAt = $this->settings->customer_loyalty_api_token_expires_at;
        $isExpired = $expiresAt ? now()->gte($expiresAt) : false;

        return [
            'base_url' => $baseUrl,
            'username' => $username,
            'has_password' => $hasPassword,
            'has_token' => $hasToken,
            'token_expires_at_label' => $this->formatCacheTimestamp($expiresAt),
            'token_refreshed_at_label' => $this->formatCacheTimestamp($this->settings->customer_loyalty_api_token_refreshed_at),
            'configured_label' => filled($baseUrl) && filled($username) && $hasPassword ? 'Đã đủ thông tin đăng nhập' : 'Chưa đủ thông tin đăng nhập',
            'token_label' => $hasToken ? ($isExpired ? 'Token đã hết hạn' : 'Đã có token') : 'Chưa có token',
            'token_is_expired' => $isExpired,
        ];
    }

    protected function customerLoyaltyApiCredentialsChanged(array $validatedForm, string $password): bool
    {
        $baseUrl = $this->normalizeCustomerLoyaltyBaseUrl($validatedForm['customer_loyalty_api_base_url'] ?? null);
        $username = $this->nullableTrimmedString($validatedForm['customer_loyalty_api_username'] ?? null);

        return $baseUrl !== $this->normalizeCustomerLoyaltyBaseUrl($this->settings->customer_loyalty_api_base_url)
            || $username !== $this->nullableTrimmedString($this->settings->customer_loyalty_api_username)
            || $password !== '';
    }

    protected function refreshFrontsiteCacheStatus(FrontsiteCache $frontsiteCache): void
    {
        $status = $frontsiteCache->status();
        $lastClear = data_get($status, 'last_clear');

        $this->frontsiteCacheStatus = [
            ...$status,
            'enabled_label' => data_get($status, 'enabled') ? 'Đang bật' : 'Đang tắt',
            'response_enabled_label' => data_get($status, 'response_enabled') ? 'Đang bật' : 'Đang tắt',
            'stale_enabled_label' => data_get($status, 'stale_enabled') ? 'Đang bật' : 'Đang tắt',
            'ttl_labels' => collect(data_get($status, 'ttl', []))
                ->map(fn (mixed $seconds): string => $this->formatCacheSeconds((int) $seconds))
                ->all(),
            'last_clear_label' => $this->formatCacheTimestamp(data_get($lastClear, 'cleared_at')),
            'last_clear_groups_label' => implode(', ', data_get($lastClear, 'groups', [])),
            'checked_at_label' => $this->formatCacheTimestamp(data_get($status, 'checked_at')),
        ];
    }

    protected function formatCacheSeconds(int $seconds): string
    {
        if ($seconds >= 3600 && $seconds % 3600 === 0) {
            return (int) ($seconds / 3600).' giờ';
        }

        if ($seconds >= 60 && $seconds % 60 === 0) {
            return (int) ($seconds / 60).' phút';
        }

        return $seconds.' giây';
    }

    protected function formatCacheTimestamp(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse((string) $value)
                ->timezone((string) config('app.timezone'))
                ->format('d/m/Y H:i:s');
        } catch (Throwable) {
            return (string) $value;
        }
    }

    protected function blankTourTermItem(): array
    {
        return [
            'title' => '',
            'content' => '',
        ];
    }

    protected function normalizeTourTermItems(?array $items): array
    {
        return collect($items ?? [])
            ->filter(fn (mixed $item) => is_array($item))
            ->map(fn (array $item) => [
                'title' => $this->plainEditorContent((string) ($item['title'] ?? '')),
                'content' => $this->richEditorContent((string) ($item['content'] ?? '')),
            ])
            ->filter(fn (array $item) => $item['title'] !== '' && $item['content'] !== '')
            ->values()
            ->all();
    }

    protected function filterStructuredData(mixed $value): mixed
    {
        if (is_string($value)) {
            $value = trim($value);

            return $value !== '' ? $value : null;
        }

        if (! is_array($value)) {
            return $value;
        }

        $filtered = [];

        foreach ($value as $key => $item) {
            $cleaned = $this->filterStructuredData($item);

            if ($cleaned === null || $cleaned === []) {
                continue;
            }

            $filtered[$key] = $cleaned;
        }

        return $filtered === [] ? null : $filtered;
    }

    protected function normalizeGaMeasurementId(mixed $value): ?string
    {
        $normalized = strtoupper(trim((string) $value));
        $normalized = preg_replace('/\s+/', '', $normalized ?? '');

        return $normalized !== '' ? $normalized : null;
    }

    protected function normalizeFacebookPixelId(mixed $value): ?string
    {
        $normalized = preg_replace('/\s+/', '', trim((string) $value));

        return $normalized !== '' ? $normalized : null;
    }

    protected function normalizeRecaptchaScore(mixed $value): float
    {
        if (! is_numeric($value)) {
            return 0.5;
        }

        return round(max(0.0, min(1.0, (float) $value)), 2);
    }

    protected function htmlSnippetContent(mixed $value): ?string
    {
        return SitewideHtmlSnippets::htmlSnippetContent($value);
    }

    protected function normalizeCustomerLoyaltyBaseUrl(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? rtrim($value, '/') : null;
    }

    protected function nullableTrimmedString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    protected function maskToken(string $token): string
    {
        if (strlen($token) <= 12) {
            return str_repeat('*', strlen($token));
        }

        return substr($token, 0, 6).'...'.substr($token, -4);
    }
}
