<?php

namespace Tests\Feature;

use App\Support\FaqContent;
use Database\Seeders\CmsEstimateBootstrapSeeder;
use Database\Seeders\CmsMissingFaqSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Domains\Cms\Models\LandingPage;
use Src\Domains\Cms\Models\Project;
use Src\Domains\Cms\Models\Service;
use Tests\TestCase;

class CmsMissingFaqSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_faq_seeder_backfills_empty_service_project_and_landing_faq_fields(): void
    {
        $this->seed(CmsEstimateBootstrapSeeder::class);

        $serviceToBackfill = Service::query()->where('slug', 'thi-cong-nha-pho-tron-goi')->firstOrFail();
        $serviceToPreserve = Service::query()->where('slug', 'thiet-ke-noi-that-biet-thu')->firstOrFail();
        $project = Project::query()->where('slug', 'biet-thu-lakeview')->firstOrFail();
        $servicesLanding = LandingPage::query()->where('page_key', 'services')->firstOrFail();
        $projectsLanding = LandingPage::query()->where('page_key', 'projects')->firstOrFail();
        $estimateLanding = LandingPage::query()->where('page_key', 'estimate')->firstOrFail();

        $serviceToBackfill->update([
            'faq_items' => null,
            'related_questions' => null,
        ]);

        $project->update([
            'faq_items' => [],
            'related_questions' => [],
        ]);

        $servicesLanding->update(['faq_items' => null]);
        $projectsLanding->update(['faq_items' => []]);
        $estimateLanding->update(['faq_items' => null]);

        $existingServiceFaq = $serviceToPreserve->faq_items;
        $existingServiceRelatedQuestions = $serviceToPreserve->related_questions;

        $this->seed(CmsMissingFaqSeeder::class);

        $serviceToBackfill->refresh();
        $serviceToPreserve->refresh();
        $project->refresh();
        $servicesLanding->refresh();
        $projectsLanding->refresh();
        $estimateLanding->refresh();

        $this->assertNotEmpty(FaqContent::prepareItems($serviceToBackfill->faq_items));
        $this->assertNotEmpty(FaqContent::prepareQuestions($serviceToBackfill->related_questions));
        $this->assertStringContainsString($serviceToBackfill->title, (string) data_get($serviceToBackfill->faq_items, '0.question'));

        $this->assertNotEmpty(FaqContent::prepareItems($project->faq_items));
        $this->assertNotEmpty(FaqContent::prepareQuestions($project->related_questions));
        $this->assertStringContainsString($project->title, (string) data_get($project->faq_items, '0.question'));

        $this->assertNotEmpty(FaqContent::prepareItems($servicesLanding->faq_items));
        $this->assertNotEmpty(FaqContent::prepareItems($projectsLanding->faq_items));
        $this->assertSame('Tôi nên bắt đầu dùng trang dự toán từ bước nào?', data_get($estimateLanding->faq_items, '0.question'));

        $this->assertSame($existingServiceFaq, $serviceToPreserve->faq_items);
        $this->assertSame($existingServiceRelatedQuestions, $serviceToPreserve->related_questions);
    }
}
