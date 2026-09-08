<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFrontsiteConsultationRequest;
use App\Mail\FrontsiteConsultationRequestMail;
use App\Services\Cms\SiteSettingsManager;
use App\Support\HomePageContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Src\Domains\Cms\Models\LandingPage;
use Throwable;

class FrontsiteConsultationController extends Controller
{
    public function store(StoreFrontsiteConsultationRequest $request, SiteSettingsManager $site): RedirectResponse
    {
        $settings = $site->current();
        $recipient = $settings->mail_contact_recipient ?: $settings->primary_email;

        if (! $recipient) {
            return back()
                ->withErrors(['form' => 'Chưa cấu hình email nhận liên hệ trong phần theme settings.'], 'frontsiteConsultation')
                ->withInput();
        }

        try {
            Mail::to($recipient)->send(new FrontsiteConsultationRequestMail(
                payload: $request->validated(),
                companyName: $settings->company_name ?: $settings->site_name,
            ));
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withErrors(['form' => 'Không thể gửi yêu cầu lúc này. Vui lòng thử lại sau hoặc gọi trực tiếp hotline.'], 'frontsiteConsultation')
                ->withInput();
        }

        $homeLanding = LandingPage::query()->where('page_key', 'home')->first();
        $successMessage = HomePageContent::prepareConfig($homeLanding?->home_config)['consultation']['success_message']
            ?: 'Yêu cầu tư vấn đã được gửi. Đội ngũ sẽ liên hệ với bạn trong thời gian sớm nhất.';

        return back()->with('frontsite_consultation_status', $successMessage);
    }
}
