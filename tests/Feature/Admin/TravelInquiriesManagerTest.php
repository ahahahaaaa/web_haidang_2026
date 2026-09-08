<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Cms\TravelInquiriesManager;
use App\Models\User;
use Database\Seeders\CmsBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Src\Domains\Cms\Enums\TravelInquirySource;
use Src\Domains\Cms\Models\TravelInquiry;
use Tests\TestCase;

class TravelInquiriesManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_travel_inquiries_can_be_filtered_by_created_date_range(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $beforeRange = $this->createInquiry('Nguyễn Trước Khoảng', '2026-05-01 09:00:00');
        $insideRange = $this->createInquiry('Nguyễn Trong Khoảng', '2026-05-10 09:00:00');
        $afterRange = $this->createInquiry('Nguyễn Sau Khoảng', '2026-05-20 09:00:00');

        $this->actingAs($user);

        Livewire::test(TravelInquiriesManager::class)
            ->set('selectedId', $insideRange->id)
            ->set('createdFrom', '2026-05-05')
            ->set('createdTo', '2026-05-15')
            ->assertSeeText('Nguyễn Trong Khoảng')
            ->assertViewHas('inquiries', function ($inquiries) use ($beforeRange, $insideRange, $afterRange): bool {
                $ids = $inquiries->pluck('id');

                return $ids->contains($insideRange->id)
                    && ! $ids->contains($beforeRange->id)
                    && ! $ids->contains($afterRange->id);
            });
    }

    protected function createInquiry(string $customerName, string $createdAt): TravelInquiry
    {
        $inquiry = TravelInquiry::query()->create([
            'source' => TravelInquirySource::General->value,
            'context_title' => 'Yêu cầu tư vấn test',
            'status' => 'new',
            'customer_name' => $customerName,
            'customer_phone' => '0901234567',
            'customer_email' => 'test@example.com',
        ]);

        $inquiry->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->save();

        return $inquiry->refresh();
    }
}
