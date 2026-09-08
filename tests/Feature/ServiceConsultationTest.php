<?php

namespace Tests\Feature;

use App\Mail\ServiceConsultationRequestMail;
use Database\Seeders\CmsBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Src\Domains\Cms\Models\Service;
use Tests\TestCase;

class ServiceConsultationTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_consultation_form_sends_mail_and_redirects_back_to_service_detail(): void
    {
        Mail::fake();
        $this->seed(CmsBootstrapSeeder::class);

        $service = Service::query()->where('slug', 'thi-cong-nha-pho-tron-goi')->firstOrFail();

        $response = $this->post(route('services.consultation.store', $service), [
            'name' => 'Nguyen Van A',
            'phone' => '0909000111',
            'email' => 'customer@example.com',
            'message' => 'Can tu van pham vi thi cong va thoi gian du kien.',
        ]);

        $response
            ->assertRedirect(route('services.show', $service))
            ->assertSessionHas('service_consultation_status');

        Mail::assertSent(ServiceConsultationRequestMail::class, function (ServiceConsultationRequestMail $mail) use ($service) {
            return $mail->service->is($service)
                && $mail->payload['email'] === 'customer@example.com'
                && $mail->payload['phone'] === '0909000111';
        });
    }

    public function test_service_consultation_form_validates_required_fields(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $service = Service::query()->where('slug', 'thi-cong-nha-pho-tron-goi')->firstOrFail();

        $this->from(route('services.show', $service))
            ->post(route('services.consultation.store', $service), [])
            ->assertRedirect(route('services.show', $service))
            ->assertSessionHasErrors(['name', 'phone', 'email', 'message'], null, 'serviceConsultation');
    }
}
