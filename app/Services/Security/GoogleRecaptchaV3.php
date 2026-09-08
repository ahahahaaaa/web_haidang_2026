<?php

namespace App\Services\Security;

use App\Services\Cms\SiteSettingsManager;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Throwable;

class GoogleRecaptchaV3
{
    public function __construct(protected SiteSettingsManager $siteSettings) {}

    public function enabled(): bool
    {
        return (bool) $this->siteSettings->current()->google_recaptcha_v3_enabled;
    }

    public function siteKey(): ?string
    {
        $siteKey = trim((string) ($this->siteSettings->current()->google_recaptcha_v3_site_key ?: config('services.google_recaptcha_v3.site_key')));

        return $siteKey !== '' ? $siteKey : null;
    }

    public function secretKey(): ?string
    {
        $secretKey = trim((string) ($this->siteSettings->current()->google_recaptcha_v3_secret_key ?: config('services.google_recaptcha_v3.secret_key')));

        return $secretKey !== '' ? $secretKey : null;
    }

    public function minScore(): float
    {
        $score = (float) ($this->siteSettings->current()->google_recaptcha_v3_min_score ?: config('services.google_recaptcha_v3.min_score', 0.5));

        return max(0.0, min(1.0, $score));
    }

    public function frontendEnabled(): bool
    {
        return $this->enabled() && filled($this->siteKey());
    }

    public function validateRequest(Request $request, string $action): ?string
    {
        if (! $this->enabled()) {
            return null;
        }

        $secretKey = $this->secretKey();

        if (blank($secretKey)) {
            return 'Chưa cấu hình Secret key Google reCAPTCHA v3 trong Theme Settings.';
        }

        $token = trim((string) $request->input('g-recaptcha-response'));

        if ($token === '') {
            return 'Vui lòng xác minh reCAPTCHA trước khi gửi form.';
        }

        try {
            $response = Http::asForm()
                ->timeout(8)
                ->connectTimeout(4)
                ->post((string) config('services.google_recaptcha_v3.verify_url'), [
                    'secret' => $secretKey,
                    'response' => $token,
                    'remoteip' => $request->ip(),
                ]);
        } catch (ConnectionException) {
            return 'Không thể kết nối Google reCAPTCHA. Vui lòng thử lại sau.';
        } catch (Throwable $exception) {
            report($exception);

            return 'Không thể kiểm tra Google reCAPTCHA. Vui lòng thử lại sau.';
        }

        if (! $response->ok()) {
            return 'Google reCAPTCHA chưa phản hồi thành công. Vui lòng thử lại sau.';
        }

        $payload = $response->json();

        if (! (bool) data_get($payload, 'success')) {
            return 'Xác minh reCAPTCHA không hợp lệ. Vui lòng thử lại.';
        }

        $responseAction = trim((string) data_get($payload, 'action'));

        if ($responseAction !== '' && $responseAction !== $action) {
            return 'Phiên xác minh reCAPTCHA không khớp với form đang gửi.';
        }

        $score = (float) data_get($payload, 'score', 0);

        if ($score < $this->minScore()) {
            return 'Yêu cầu có dấu hiệu tự động. Vui lòng thử lại hoặc liên hệ hotline.';
        }

        return null;
    }
}
