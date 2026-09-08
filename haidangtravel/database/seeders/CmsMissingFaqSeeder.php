<?php

namespace Database\Seeders;

use App\Support\EstimatePageContent;
use App\Support\FaqContent;
use Illuminate\Database\Seeder;
use Src\Domains\Cms\Models\LandingPage;
use Src\Domains\Cms\Models\Project;
use Src\Domains\Cms\Models\Service;

class CmsMissingFaqSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedLandingPageFaq();
        $this->seedServiceFaq();
        $this->seedProjectFaq();
    }

    protected function seedLandingPageFaq(): void
    {
        $landingFaqMap = [
            'services' => [
                [
                    'question' => 'Trang dịch vụ giúp tôi bắt đầu từ đâu trước khi liên hệ?',
                    'answer' => 'Trang dịch vụ giúp bạn xác định đúng nhóm nhu cầu, hiểu nhanh phạm vi công việc và biết nên chuẩn bị những thông tin nào trước khi gửi yêu cầu tư vấn.',
                ],
                [
                    'question' => 'Khi nào tôi nên chuyển từ trang dịch vụ sang trang dự toán?',
                    'answer' => 'Khi bạn đã có các thông số sơ bộ về loại công trình, quy mô hoặc mức hoàn thiện, hãy chuyển sang trang dự toán để chuẩn hóa đầu vào chi phí.',
                ],
                [
                    'question' => 'Tôi cần chuẩn bị những gì để chọn đúng dịch vụ?',
                    'answer' => 'Nên chuẩn bị loại công trình, hiện trạng, mục tiêu sử dụng, mốc tiến độ mong muốn và các yêu cầu ưu tiên về vật liệu hoặc chất lượng hoàn thiện.',
                ],
            ],
            'projects' => [
                [
                    'question' => 'Tôi nên xem trang dự án theo tiêu chí nào trước?',
                    'answer' => 'Hãy bắt đầu từ loại công trình, vị trí, quy mô diện tích và timeline để nhanh chóng tìm ra case study gần nhất với bối cảnh thực tế của bạn.',
                ],
                [
                    'question' => 'Trang dự án có ích gì ngoài việc xem hình ảnh công trình?',
                    'answer' => 'Ngoài hình ảnh, mỗi dự án còn cho thấy cách đội ngũ xử lý đầu bài, tổ chức thi công, kiểm soát vật liệu và bàn giao kết quả theo từng giai đoạn.',
                ],
                [
                    'question' => 'Khi nào tôi nên chuyển sang trang dịch vụ hoặc dự toán sau khi xem dự án?',
                    'answer' => 'Hãy sang trang dịch vụ khi bạn muốn hiểu rõ phạm vi công việc phù hợp, và sang trang dự toán khi đã có đủ dữ liệu sơ bộ để chuẩn hóa brief chi phí.',
                ],
            ],
            'estimate' => EstimatePageContent::defaultFaqItems(),
        ];

        foreach ($landingFaqMap as $pageKey => $faqItems) {
            $landingPage = LandingPage::query()->where('page_key', $pageKey)->first();

            if (! $landingPage || FaqContent::prepareItems($landingPage->faq_items) !== []) {
                continue;
            }

            $landingPage->update([
                'faq_items' => $faqItems,
            ]);
        }
    }

    protected function seedServiceFaq(): void
    {
        Service::query()->with('category')->get()->each(function (Service $service): void {
            $updates = [];

            if (FaqContent::prepareItems($service->faq_items) === []) {
                $updates['faq_items'] = $this->defaultServiceFaqItems($service);
            }

            if (FaqContent::prepareQuestions($service->related_questions) === []) {
                $updates['related_questions'] = $this->defaultServiceRelatedQuestions($service);
            }

            if ($updates !== []) {
                $service->update($updates);
            }
        });
    }

    protected function seedProjectFaq(): void
    {
        Project::query()->with(['category', 'type'])->get()->each(function (Project $project): void {
            $updates = [];

            if (FaqContent::prepareItems($project->faq_items) === []) {
                $updates['faq_items'] = $this->defaultProjectFaqItems($project);
            }

            if (FaqContent::prepareQuestions($project->related_questions) === []) {
                $updates['related_questions'] = $this->defaultProjectRelatedQuestions($project);
            }

            if ($updates !== []) {
                $project->update($updates);
            }
        });
    }

    protected function defaultServiceFaqItems(Service $service): array
    {
        $category = $service->category?->name ?: 'nhóm công trình liên quan';
        $excerpt = trim((string) ($service->excerpt ?: 'Dịch vụ này được tổ chức để giúp khách hàng nhìn rõ phạm vi công việc, tiến độ và các điểm kiểm soát trước khi triển khai.'));
        $priceNote = trim((string) ($service->price_note ?: 'Chi phí thực tế sẽ thay đổi theo quy mô, điều kiện hiện trường và mức độ hoàn thiện mong muốn.'));

        return [
            [
                'question' => $service->title.' phù hợp với nhu cầu nào?',
                'answer' => $excerpt.' Đây là lựa chọn phù hợp khi khách hàng cần một đầu mối rõ ràng cho nhóm nhu cầu thuộc '.$category.'.',
            ],
            [
                'question' => 'Tôi nên chuẩn bị gì trước khi yêu cầu '.$service->title.'?',
                'answer' => 'Bạn nên chuẩn bị loại công trình, hiện trạng, quy mô, mục tiêu sử dụng và mốc tiến độ mong muốn để đội ngũ tư vấn chốt phạm vi nhanh hơn và sát thực tế hơn.',
            ],
            [
                'question' => 'Yếu tố nào thường ảnh hưởng đến phạm vi và chi phí của '.$service->title.'?',
                'answer' => $priceNote.' Ngoài ra, vật liệu, tiến độ mong muốn và mức độ phối hợp nhiều hạng mục cùng lúc cũng ảnh hưởng trực tiếp đến kế hoạch triển khai.',
            ],
        ];
    }

    protected function defaultServiceRelatedQuestions(Service $service): array
    {
        return [
            'Trước khi yêu cầu '.$service->title.' tôi nên chuẩn bị những thông tin nào?',
            'Dịch vụ '.$service->title.' phù hợp nhất với loại công trình nào?',
            'Làm sao để kiểm soát phát sinh khi triển khai '.$service->title.'?',
        ];
    }

    protected function defaultProjectFaqItems(Project $project): array
    {
        $category = $project->category?->name ?: 'công trình tương tự';
        $type = $project->type?->name ?: 'dự án cùng nhóm';
        $location = $project->location ?: 'khu vực triển khai tương đương';
        $area = $project->area_value ? number_format((float) $project->area_value, 0, ',', '.').' '.$project->area_unit : 'quy mô thực tế của công trình';
        $timeline = $project->timeline ?: 'timeline triển khai';

        return [
            [
                'question' => 'Tôi nên tham chiếu '.$project->title.' trong trường hợp nào?',
                'answer' => 'Dự án này phù hợp để tham chiếu khi bạn đang chuẩn bị một '.$type.' hoặc một công trình thuộc nhóm '.$category.' có yêu cầu tương đồng về phạm vi, chất lượng bàn giao hoặc cách tổ chức triển khai.',
            ],
            [
                'question' => 'Điểm nào của '.$project->title.' nên xem trước?',
                'answer' => 'Bạn nên xem trước vị trí '.$location.', quy mô khoảng '.$area.' và '.$timeline.' để đánh giá nhanh mức độ gần với đầu bài thực tế của công trình mình đang chuẩn bị.',
            ],
            [
                'question' => 'Từ '.$project->title.' tôi có thể rút ra gì cho giai đoạn lập brief?',
                'answer' => 'Case study này giúp bạn hình dung cách mô tả mục tiêu công trình, phạm vi ưu tiên, các điểm kiểm soát thi công và logic phối hợp giữa tiến độ, vật liệu và chất lượng bàn giao.',
            ],
        ];
    }

    protected function defaultProjectRelatedQuestions(Project $project): array
    {
        return [
            'Nếu tôi có công trình tương tự '.$project->title.', nên bắt đầu từ khảo sát hay lập brief trước?',
            'Những yếu tố nào của '.$project->title.' có thể áp dụng cho công trình khác?',
            'Sau khi xem '.$project->title.' tôi nên chuyển sang trang dịch vụ hay trang dự toán?',
        ];
    }
}
