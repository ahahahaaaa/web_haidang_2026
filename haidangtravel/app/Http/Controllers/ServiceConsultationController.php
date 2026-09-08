<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServiceConsultationRequest;
use App\Mail\ServiceConsultationRequestMail;
use App\Services\Cms\SiteSettingsManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Src\Domains\Cms\Models\Service;
use Throwable;

class ServiceConsultationController extends Controller
{
    public function store(StoreServiceConsultationRequest $request, Service $service, SiteSettingsManager $site): RedirectResponse
    {
        $settings = $site->current();
        $recipient = $settings->mail_contact_recipient ?: $settings->primary_email;

        if (! $recipient) {
            return back()
                ->withErrors(['form' => 'Chưa cấu hình email nhận liên hệ trong phần theme settings.'], 'serviceConsultation')
                ->withInput();
        }

        try {
            Mail::to($recipient)->send(new ServiceConsultationRequestMail(
                service: $service,
                payload: $request->validated(),
                companyName: $settings->company_name ?: $settings->site_name,
            ));
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withErrors(['form' => 'Không thể gửi yêu cầu lúc này. Vui lòng thử lại sau hoặc gọi trực tiếp hotline.'], 'serviceConsultation')
                ->withInput();
        }

        return redirect()
            ->route('services.show', $service)
            ->with('service_consultation_status', 'Yêu cầu tư vấn đã được gửi. Đội ngũ sẽ liên hệ với bạn trong thời gian sớm nhất.');
    }
}
