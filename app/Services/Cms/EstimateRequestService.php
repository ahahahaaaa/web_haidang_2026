<?php

namespace App\Services\Cms;

use App\Mail\EstimateCustomerResultMail;
use App\Mail\EstimateRequestMail;
use App\Services\Estimator\EstimateCatalogService;
use App\Services\Estimator\EstimatePayloadService;
use App\Support\EstimatePageContent;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Src\Domains\Cms\Models\EstimateRequest;
use Src\Domains\Cms\Models\SiteSetting;
use Throwable;

class EstimateRequestService
{
    public function __construct(
        protected EstimateKeyService $keys,
        protected EstimatePayloadService $payloads,
        protected EstimateCatalogService $catalog,
    ) {}

    public function submit(array $validated, array $estimateConfig, SiteSetting $settings): EstimateRequest
    {
        $tier = EstimatePageContent::findTier($estimateConfig, (string) ($validated['selected_tier_code'] ?? ''));

        if (! $tier) {
            throw ValidationException::withMessages([
                'selected_tier_code' => 'Cấp dự toán đã chọn không hợp lệ.',
            ]);
        }

        $selectedLevel = trim((string) ($validated['selected_estimator_level'] ?? ''))
            ?: EstimatePageContent::estimatorLevelForTier($estimateConfig, (string) ($validated['selected_tier_code'] ?? ''));
        $inputPayload = $this->payloads->decode($validated['input_payload'] ?? '[]');
        $normalizedInputs = $this->payloads->normalizeForStorage($estimateConfig, $inputPayload, $selectedLevel);
        $resultPayload = json_decode((string) ($validated['result_payload'] ?? '[]'), true);
        $estimateResult = is_array($resultPayload) ? $resultPayload : null;
        $catalogPayload = $this->catalog->uiPayload();
        $sendAdminEmail = (bool) data_get($estimateConfig, 'delivery.send_email', true);
        $sendCustomerEmail = (bool) data_get($estimateConfig, 'delivery.send_customer_email', true);
        $storeCollection = (bool) data_get($estimateConfig, 'delivery.store_collection', true);
        $recipient = $sendAdminEmail ? $this->resolveRecipient($settings) : null;
        $customerRecipient = $sendCustomerEmail ? trim((string) ($validated['customer_email'] ?? '')) : null;

        if (! $storeCollection && ! $recipient && ! $customerRecipient) {
            throw ValidationException::withMessages([
                'form' => 'Landing dự toán hiện chưa có kênh nhận kết quả khả dụng.',
            ]);
        }

        $accessKey = null;

        if ((bool) data_get($tier, 'requires_key', false)) {
            $accessKey = $this->keys->validateForTier(
                (string) ($validated['estimate_key'] ?? ''),
                (string) data_get($tier, 'code'),
            );
        }

        $request = EstimateRequest::query()->create([
            'estimate_access_key_id' => $accessKey?->getKey(),
            'tier_code' => (string) data_get($tier, 'code'),
            'tier_name' => (string) data_get($tier, 'name'),
            'requires_key' => (bool) data_get($tier, 'requires_key', false),
            'estimate_key_code' => $accessKey?->code,
            'delivery_channel' => $this->resolveDeliveryChannel($recipient, $customerRecipient, $storeCollection),
            'mail_status' => $recipient ? 'pending' : 'skipped',
            'customer_mail_status' => $customerRecipient ? 'pending' : 'skipped',
            'status' => 'submitted',
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['customer_phone'],
            'customer_email' => $validated['customer_email'],
            'company_name' => $validated['company_name'] ?: null,
            'project_location' => $validated['project_location'] ?: null,
            'project_overview' => $validated['project_overview'],
            'page_url' => $validated['page_url'] ?: null,
            'input_payload' => $normalizedInputs,
            'result_snapshot' => [
                'level' => $selectedLevel,
                'catalog_version' => data_get($estimateResult, 'catalog_version', data_get($catalogPayload, 'catalog_version', 'seed-v1')),
                'room_template_version' => data_get($estimateResult, 'room_template_version', data_get($catalogPayload, 'room_template_version', 'seed-v1')),
                'price_book_code' => data_get($estimateResult, 'pricing_summary.price_book_code', data_get($estimateResult, 'price_book_code')),
                'component_tree' => data_get($estimateResult, 'component_tree_breakdown', []),
                'title' => (string) data_get($tier, 'result_title'),
                'summary' => (string) data_get($tier, 'result_summary'),
                'bullets' => data_get($tier, 'result_bullets', []),
                'delivery_text' => (string) data_get($tier, 'delivery_text'),
                'estimate' => $estimateResult,
            ],
        ]);

        if ($accessKey) {
            $this->keys->markUsed($accessKey);
        }

        if ($recipient) {
            try {
                Mail::to($recipient)->send(new EstimateRequestMail(
                    estimateRequest: $request,
                    companyName: $settings->company_name ?: $settings->site_name,
                ));

                $request->forceFill([
                    'mail_status' => 'sent',
                    'mailed_at' => now(),
                    'mailed_to' => $recipient,
                ])->save();
            } catch (Throwable $exception) {
                report($exception);

                $request->forceFill([
                    'mail_status' => 'failed',
                    'mailed_to' => $recipient,
                ])->save();
            }
        }

        if ($customerRecipient) {
            try {
                Mail::to($customerRecipient)->send(new EstimateCustomerResultMail(
                    estimateRequest: $request,
                    companyName: $settings->company_name ?: $settings->site_name,
                    siteSettings: $settings,
                ));

                $request->forceFill([
                    'customer_mail_status' => 'sent',
                    'customer_mailed_at' => now(),
                    'customer_mailed_to' => $customerRecipient,
                ])->save();
            } catch (Throwable $exception) {
                report($exception);

                $request->forceFill([
                    'customer_mail_status' => 'failed',
                    'customer_mailed_to' => $customerRecipient,
                ])->save();
            }
        }

        return $request;
    }

    protected function resolveDeliveryChannel(?string $recipient, ?string $customerRecipient, bool $storeCollection): string
    {
        $channels = [];

        if ($recipient) {
            $channels[] = 'admin_email';
        }

        if ($customerRecipient) {
            $channels[] = 'customer_email';
        }

        if ($storeCollection) {
            $channels[] = 'collection';
        }

        return $channels !== [] ? implode('_and_', $channels) : 'none';
    }

    protected function resolveRecipient(SiteSetting $settings): ?string
    {
        $recipient = trim((string) ($settings->mail_contact_recipient ?: $settings->primary_email));

        return $recipient !== '' ? $recipient : null;
    }
}
