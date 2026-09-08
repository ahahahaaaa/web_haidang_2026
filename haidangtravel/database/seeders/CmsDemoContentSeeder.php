<?php

namespace Database\Seeders;

use App\Support\EstimatePageContent;
use App\Support\HomePageContent;
use App\Support\ServiceDetailContent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Src\Domains\Cms\Models\BlogPost;
use Src\Domains\Cms\Models\ContentCategory;
use Src\Domains\Cms\Models\LandingPage;
use Src\Domains\Cms\Models\Project;
use Src\Domains\Cms\Models\ProjectType;
use Src\Domains\Cms\Models\Service;
use Src\Domains\Cms\Models\SiteSetting;
use Src\Domains\Cms\Models\Slider;
use Src\Domains\Cms\Models\SliderItem;

class CmsDemoContentSeeder extends Seeder
{
    protected array $imagePools = [
        'industrial' => [
            'ab6axuc2afyzx9n6d9aoznoaz76h-g6lolihh7aa-9166ec152a5f.png',
            'ab6axuaw-64kuw2jnt1ke0sigxkdzgv94cfqngle-81cca7b944ce.png',
            'ab6axua-i-9ip7d2-blnof7ijmylpwloxpe5eo2m-a4580aa6e6e9.png',
            'ab6axuc5mwd5hbiqekqmiew18s4gp2ce0z2apm-b-0aee5777c818.png',
            'ab6axubfdqrhcykune7gatjrmhaxrft2uqsllkrf-01e573b76ae4.png',
            'ab6axudm0ai1dzhztzhvpi7p0jfillpccjvcpsir-5d67705d36fc.png',
            'ab6axua1g4tqmntwwuduqrtcnblvz7voy3mo7npq-63410b7e863d.png',
            'ab6axuchkclorjitnxh5tboj6cz-vcjbxrz9m96j-cd39974336ac.png',
            'ChatGPT-Image-Apr-7,-2026,-10_57_37-AM.jpg',
        ],
        'commercial' => [
            'ab6axuacwqkfzzshmmddcix1ab2u1whvknr4b17n-5cecce359cdb.png',
            'ab6axucplpfxor4vs-azoa6winiap-8vwsck-imr-72241b88da7c.png',
            'ab6axud49kzt0kpufa7g6dydhi2w8jovbilbgwjf-0c5d814cc870.png',
            'ab6axudaxorrxpyqmyfqc4w94n7wt44q9entbaau-6153289f90c4.png',
            'ab6axucvrxeza9os4wc6au0rebpq3sfmdqecxfvd-833e58f3848e.png',
            'ab6axucpbtxt3etet2hrgita5pbihtfiil2lnhjj-49cf24b934b2.png',
            'ab6axua21tahc09ctxejoodfn-rlruu5nvgthfmo-b0ab827e5c39.png',
            'ChatGPT-Image-Apr-7,-2026,-11_07_47-AM.jpg',
        ],
        'residential' => [
            'ab6axudslzvbenrqxq8nnpw-quyagkh-pxcilklg-59bf1d92be59.png',
            'ab6axuc4bl02fhx6d3hjegfex4cbbe5-ecsvxrbb-5f56f1a3a7f8.png',
            'ab6axudvkjqkue3s87nb04evd-fuco38smly6vca-fb41d0ae14de.png',
            'ab6axubchvys-snshvz1fq9btufczxgi2cydv-ho-7ad7cbb3956f.png',
            'ab6axubebalheavvfzesrsgq9he5uuwldgjy8x7x-656a0ac846f5.png',
            'ab6axud-jru73snqa1kfhpug3ut-jzut-qeopmom-63e5b00f7807.png',
            'ab6axubkr7dxiu2iztablxtj5d8qvwdxnzalnvcd-cfa192ec1362.png',
            'ab6axua4-nvpv8lkhh-rlb165bikjzy9bek8o-ib-87b1dd370a20.png',
            'ab6axucqex644nz9t1ixd9r7peppxvxi5k0t7ew9-3b59ea2ecd2e.png',
            'ab6axucayawql1mf-tvqkm85kmuqixnwwx-y1yr--e7be0cdbde46.png',
        ],
        'interior' => [
            'ab6axucslqjjk8xxedne2vsxlsvby-ydmeoicd83-d16412c59c83.png',
            'ab6axudvkjqkue3s87nb04evd-fuco38smly6vca-fb41d0ae14de.png',
            'ab6axudsb1v1v9oqeukuevs-ganxpovyp6lbjsob-8aa81bb3bab8.png',
            'ab6axucddazev4td1mua1lw8-nhcmvro-nhwy2d--88eb0247cde1.png',
            'ab6axucpbtxt3etet2hrgita5pbihtfiil2lnhjj-49cf24b934b2.png',
            'ab6axucayawql1mf-tvqkm85kmuqixnwwx-y1yr--e7be0cdbde46.png',
            'ab6axudslzvbenrqxq8nnpw-quyagkh-pxcilklg-59bf1d92be59.png',
        ],
        'technology' => [
            'ab6axucny-6fgtskrctfaw-4ueid14dbnurbjx7x-8ce82a7e4c99.png',
            'ab6axude3deavjqceboawsef5els1ppae28jl24i-6dba577e7798.png',
            'ab6axudm0ai1dzhztzhvpi7p0jfillpccjvcpsir-5d67705d36fc.png',
            'ab6axucqex644nz9t1ixd9r7peppxvxi5k0t7ew9-3b59ea2ecd2e.png',
            'ab6axucayawql1mf-tvqkm85kmuqixnwwx-y1yr--e7be0cdbde46.png',
            'ChatGPT-Image-Apr-7,-2026,-11_07_47-AM.jpg',
        ],
    ];

    public function run(): void
    {
        $site = SiteSetting::query()->firstOrFail();
        $libraryMedia = $this->ensureDemoImageLibrary($site);

        [$serviceCategories, $projectCategories, $blogCategories, $projectTypes] = $this->seedTaxonomies();

        $services = $this->seedServices($serviceCategories, $libraryMedia);
        $projects = $this->seedProjects($projectCategories, $projectTypes, $libraryMedia);
        $posts = $this->seedBlogPosts($blogCategories, $libraryMedia);

        $this->seedLandingPages($services, $projects, $posts, $libraryMedia);
        $this->seedHomeSlider($libraryMedia);
        $this->refreshSiteSettings($site, $projects);
    }

    protected function ensureDemoImageLibrary(SiteSetting $site): Collection
    {
        $wantedFiles = collect($this->imagePools)->flatten()->unique()->values();

        $libraryMedia = Media::query()
            ->where('model_type', SiteSetting::class)
            ->where('model_id', $site->id)
            ->where('collection_name', 'library')
            ->get()
            ->keyBy('file_name');

        $missingFiles = $wantedFiles->reject(fn (string $fileName) => $libraryMedia->has($fileName))->values();

        if ($missingFiles->isNotEmpty()) {
            $scannedFiles = collect(File::exists(public_path('storage')) ? File::allFiles(public_path('storage')) : [])
                ->filter(fn ($file) => in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'webp'], true))
                ->keyBy(fn ($file) => $file->getFilename());

            foreach ($missingFiles as $fileName) {
                $file = $scannedFiles->get($fileName);

                if (! $file) {
                    continue;
                }

                $site
                    ->addMedia($file->getRealPath())
                    ->usingFileName($fileName)
                    ->withCustomProperties(['alt' => Str::headline(pathinfo($fileName, PATHINFO_FILENAME))])
                    ->toMediaCollection('library', config('media-library.disk_name', 'public'));
            }

            $libraryMedia = Media::query()
                ->where('model_type', SiteSetting::class)
                ->where('model_id', $site->id)
                ->where('collection_name', 'library')
                ->get()
                ->keyBy('file_name');
        }

        return $libraryMedia;
    }

    protected function seedTaxonomies(): array
    {
        $serviceCategories = collect([
            ['name' => 'Xây nhà trọn gói', 'slug' => 'xay-nha-tron-goi', 'description' => 'Nhóm dịch vụ xây mới nhà ở, biệt thự và công trình dân dụng cần đầu mối quản lý đồng bộ.'],
            ['name' => 'Cải tạo và hoàn thiện', 'slug' => 'cai-tao-va-hoan-thien', 'description' => 'Nhóm dịch vụ nâng cấp công năng, gia cố kết cấu, chống thấm và hoàn thiện công trình đang sử dụng.'],
            ['name' => 'Nội thất nhà ở', 'slug' => 'noi-that-nha-o', 'description' => 'Nhóm dịch vụ thiết kế và thi công nội thất cho căn hộ, nhà phố và biệt thự.'],
            ['name' => 'Công trình công nghiệp', 'slug' => 'cong-trinh-cong-nghiep', 'description' => 'Nhóm dịch vụ dành cho nhà xưởng, kho logistics, kết cấu thép và công trình sản xuất.'],
            ['name' => 'F&B và hospitality', 'slug' => 'fb-va-hospitality', 'description' => 'Nhóm dịch vụ thi công quán ăn, nhà hàng, khách sạn mini và bếp công nghiệp.'],
            ['name' => 'Văn phòng và showroom', 'slug' => 'van-phong-va-showroom', 'description' => 'Nhóm dịch vụ cải tạo văn phòng, hoàn thiện facade và thi công không gian bán lẻ.'],
        ])->values()->map(function (array $item, int $index) {
            return ContentCategory::query()->updateOrCreate(
                ['taxonomy' => 'service', 'slug' => $item['slug']],
                ['name' => $item['name'], 'description' => $item['description'], 'sort_order' => $index + 1],
            );
        })->keyBy('slug');

        $projectCategories = collect([
            ['name' => 'Nhà ở', 'slug' => 'nha-o', 'description' => 'Các công trình nhà phố, căn hộ mẫu và nhà ở kết hợp kinh doanh.'],
            ['name' => 'Biệt thự', 'slug' => 'biet-thu', 'description' => 'Các công trình biệt thự, villa sân vườn và biệt thự nghỉ dưỡng.'],
            ['name' => 'Công nghiệp', 'slug' => 'cong-nghiep', 'description' => 'Nhà xưởng, kho vận, nhà kho lạnh và công trình sản xuất.'],
            ['name' => 'Văn phòng', 'slug' => 'van-phong', 'description' => 'Công trình văn phòng điều hành, khối làm việc và không gian doanh nghiệp.'],
            ['name' => 'Quán ăn và nhà hàng', 'slug' => 'quan-an-va-nha-hang', 'description' => 'Các dự án F&B chú trọng trải nghiệm vận hành và thương hiệu.'],
            ['name' => 'Showroom và bán lẻ', 'slug' => 'showroom-va-ban-le', 'description' => 'Không gian trải nghiệm sản phẩm, bán hàng và trưng bày thương hiệu.'],
            ['name' => 'Khách sạn và lưu trú', 'slug' => 'khach-san-va-luu-tru', 'description' => 'Khối dự án khách sạn mini, lưu trú ngắn ngày và công trình hospitality.'],
        ])->values()->map(function (array $item, int $index) {
            return ContentCategory::query()->updateOrCreate(
                ['taxonomy' => 'project', 'slug' => $item['slug']],
                ['name' => $item['name'], 'description' => $item['description'], 'sort_order' => $index + 1],
            );
        })->keyBy('slug');

        $blogCategories = collect([
            ['name' => 'Kiến thức xây dựng', 'slug' => 'kien-thuc-xay-dung', 'description' => 'Kiến thức nền tảng về xây nhà, cải tạo, quản lý chi phí và vật liệu.'],
            ['name' => 'Công nghệ xây dựng', 'slug' => 'cong-nghe-xay-dung', 'description' => 'Các bài viết về BIM, AI, vật liệu mới và công nghệ vận hành công trường.'],
            ['name' => 'Cải tạo và hoàn thiện', 'slug' => 'cai-tao-va-hoan-thien', 'description' => 'Kinh nghiệm cải tạo nhà ở, quán ăn, nhà hàng và không gian đang vận hành.'],
            ['name' => 'Công trình công nghiệp', 'slug' => 'cong-trinh-cong-nghiep', 'description' => 'Các bài viết chuyên sâu về nhà xưởng, kho vận, kết cấu và hệ kỹ thuật công nghiệp.'],
        ])->values()->map(function (array $item, int $index) {
            return ContentCategory::query()->updateOrCreate(
                ['taxonomy' => 'blog', 'slug' => $item['slug']],
                ['name' => $item['name'], 'description' => $item['description'], 'sort_order' => $index + 1],
            );
        })->keyBy('slug');

        $projectTypes = collect([
            ['name' => 'Nhà ở', 'slug' => 'residential', 'description' => 'Công trình phục vụ nhu cầu ở, sinh hoạt và gia đình.'],
            ['name' => 'Thương mại', 'slug' => 'commercial', 'description' => 'Công trình bán lẻ, showroom và khối thương mại dịch vụ.'],
            ['name' => 'Công nghiệp', 'slug' => 'industrial', 'description' => 'Công trình sản xuất, kho vận và kết cấu công nghiệp.'],
            ['name' => 'Văn phòng', 'slug' => 'office', 'description' => 'Không gian làm việc, điều hành và trụ sở doanh nghiệp.'],
            ['name' => 'F&B và hospitality', 'slug' => 'hospitality', 'description' => 'Công trình quán ăn, nhà hàng và lưu trú.'],
            ['name' => 'Cải tạo', 'slug' => 'renovation', 'description' => 'Các công trình nâng cấp, làm mới và cải tạo vận hành.'],
        ])->values()->map(function (array $item, int $index) {
            return ProjectType::query()->updateOrCreate(
                ['slug' => $item['slug']],
                ['name' => $item['name'], 'description' => $item['description'], 'sort_order' => $index + 1],
            );
        })->keyBy('slug');

        return [$serviceCategories, $projectCategories, $blogCategories, $projectTypes];
    }

    protected function serviceBlueprints(): array
    {
        return require database_path('seeders/Data/services.php');
    }

    protected function projectBlueprints(): array
    {
        return require database_path('seeders/Data/projects.php');
    }

    protected function blogBlueprints(): array
    {
        return require database_path('seeders/Data/blogs.php');
    }

    protected function seedServices(Collection $categories, Collection $libraryMedia): Collection
    {
        return collect($this->serviceBlueprints())->values()->map(function (array $blueprint, int $index) use ($categories, $libraryMedia) {
            $detailConfig = $this->serviceDetailConfig($blueprint);

            $service = Service::query()->updateOrCreate(
                ['slug' => $blueprint['slug']],
                [
                    'title' => $blueprint['title'],
                    'excerpt' => $this->serviceExcerpt($blueprint),
                    'content' => $this->serviceContent($blueprint),
                    'status' => 'published',
                    'content_category_id' => $categories[$blueprint['category']]->id,
                    'icon_class' => $blueprint['icon'],
                    'price_note' => $blueprint['price_note'],
                    'is_featured' => $index < 8,
                    'cover_alt' => $blueprint['title'],
                    'meta_title' => $blueprint['title'].' | Phong Thành Đạt',
                    'meta_description' => $this->seoDescription($blueprint['summary'], $blueprint['keyword']),
                    'og_title' => $blueprint['title'].' | Phong Thành Đạt',
                    'og_description' => $this->seoDescription($blueprint['summary'], $blueprint['keyword']),
                    'robots_directive' => 'index,follow',
                    'schema' => [[
                        '@context' => 'https://schema.org',
                        '@type' => 'Service',
                        'name' => $blueprint['title'],
                        'description' => $blueprint['summary'],
                    ]],
                    'detail_config' => $detailConfig,
                    'faq_items' => $this->serviceFaqItems($blueprint),
                    'related_questions' => $this->serviceRelatedQuestions($blueprint),
                ],
            );

            $this->copyMediaFromPool($service, 'cover', $blueprint['pool'], $index, $libraryMedia, $blueprint['title']);

            foreach (data_get($detailConfig, 'hero_slides', []) as $heroIndex => $slide) {
                $this->copyMediaFromPool(
                    $service,
                    ServiceDetailContent::heroSlideCollection($slide['uuid']),
                    $blueprint['pool'],
                    $index + $heroIndex + 1,
                    $libraryMedia,
                    $slide['image_alt'] ?: $blueprint['title'],
                );
            }

            foreach (data_get($detailConfig, 'feature_blocks', []) as $featureIndex => $block) {
                $this->copyMediaFromPool(
                    $service,
                    ServiceDetailContent::featureBlockCollection($block['uuid']),
                    $blueprint['pool'],
                    $index + $featureIndex + 3,
                    $libraryMedia,
                    $block['image_alt'] ?: $block['title'] ?: $blueprint['title'],
                );
            }

            return $service->fresh();
        })->keyBy('slug');
    }

    protected function seedProjects(Collection $categories, Collection $types, Collection $libraryMedia): Collection
    {
        return collect($this->projectBlueprints())->values()->map(function (array $blueprint, int $index) use ($categories, $types, $libraryMedia) {
            $project = Project::query()->updateOrCreate(
                ['slug' => $blueprint['slug']],
                [
                    'title' => $blueprint['title'],
                    'excerpt' => $this->projectExcerpt($blueprint),
                    'content' => $this->projectContent($blueprint),
                    'status' => 'published',
                    'content_category_id' => $categories[$blueprint['category']]->id,
                    'project_type_id' => $types[$blueprint['type']]->id,
                    'location' => $blueprint['location'],
                    'area_value' => $blueprint['area'],
                    'area_unit' => 'm2',
                    'timeline' => $blueprint['timeline'],
                    'completion_date' => $blueprint['completion_date'],
                    'is_featured' => $index < 8,
                    'cover_alt' => $blueprint['title'],
                    'meta_title' => $blueprint['title'].' | Dự án Phong Thành Đạt',
                    'meta_description' => $this->seoDescription($blueprint['summary'], $blueprint['keyword']),
                    'og_title' => $blueprint['title'].' | Dự án Phong Thành Đạt',
                    'og_description' => $this->seoDescription($blueprint['summary'], $blueprint['keyword']),
                    'robots_directive' => 'index,follow',
                    'schema' => [[
                        '@context' => 'https://schema.org',
                        '@type' => 'CreativeWork',
                        'name' => $blueprint['title'],
                        'description' => $blueprint['summary'],
                    ]],
                    'faq_items' => $this->projectFaqItems($blueprint),
                    'related_questions' => $this->projectRelatedQuestions($blueprint),
                ],
            );

            $this->copyMediaFromPool($project, 'cover', $blueprint['pool'], $index, $libraryMedia, $blueprint['title']);

            return $project->fresh();
        })->keyBy('slug');
    }

    protected function seedBlogPosts(Collection $categories, Collection $libraryMedia): Collection
    {
        return collect($this->blogBlueprints())->values()->map(function (array $blueprint, int $index) use ($categories, $libraryMedia) {
            $content = $this->blogContent($blueprint);

            $post = BlogPost::query()->updateOrCreate(
                ['slug' => $blueprint['slug']],
                [
                    'title' => $blueprint['title'],
                    'excerpt' => $this->blogExcerpt($blueprint),
                    'content' => $content,
                    'status' => 'published',
                    'content_category_id' => $categories[$blueprint['category']]->id,
                    'author_name' => 'Ban biên tập Phong Thành Đạt',
                    'published_at' => Carbon::now()->subDays($index * 3 + 2),
                    'is_featured' => $index < 8,
                    'sort_order' => $index + 1,
                    'cover_alt' => $blueprint['title'],
                    'meta_title' => $blueprint['title'].' | Blog Phong Thành Đạt',
                    'meta_description' => $this->seoDescription($blueprint['summary'], $blueprint['keyword']),
                    'og_title' => $blueprint['title'].' | Blog Phong Thành Đạt',
                    'og_description' => $this->seoDescription($blueprint['summary'], $blueprint['keyword']),
                    'robots_directive' => 'index,follow',
                    'schema' => [[
                        '@context' => 'https://schema.org',
                        '@type' => 'Article',
                        'headline' => $blueprint['title'],
                        'description' => $blueprint['summary'],
                    ]],
                    'reading_time_minutes' => $this->estimateReadingTime($content),
                ],
            );

            $this->copyMediaFromPool($post, 'cover', $blueprint['pool'], $index, $libraryMedia, $blueprint['title']);

            return $post->fresh();
        })->keyBy('slug');
    }

    protected function serviceExcerpt(array $blueprint): string
    {
        return $blueprint['summary'].' Đội ngũ triển khai theo checklist rõ ràng về phạm vi, vật tư, tiến độ và chất lượng bàn giao.';
    }

    protected function projectExcerpt(array $blueprint): string
    {
        return $blueprint['summary'].' Công trình đặt trọng tâm vào tổ chức thi công, chất lượng hoàn thiện và khả năng vận hành sau bàn giao.';
    }

    protected function blogExcerpt(array $blueprint): string
    {
        return $blueprint['summary'].' Bài viết giúp người đọc hiểu vấn đề nhanh, đúng trọng tâm và biết cách áp dụng vào công trình thực tế.';
    }

    protected function serviceFaqItems(array $blueprint): array
    {
        $deliverables = collect($blueprint['deliverables'] ?? [])->take(3)->implode(', ');
        $highlights = collect($blueprint['highlights'] ?? [])->take(3)->implode(', ');

        return [
            [
                'question' => $blueprint['title'].' thường bao gồm những phần việc nào?',
                'answer' => 'Thông thường dịch vụ này bao gồm '.$deliverables.'. Phạm vi thực tế sẽ được chốt lại sau khi khảo sát hiện trạng, làm rõ nhu cầu và xác định các ràng buộc kỹ thuật của công trình.',
            ],
            [
                'question' => 'Khi nào nên chọn '.$blueprint['title'].'?',
                'answer' => $blueprint['fit_note'].' Đây là lựa chọn phù hợp khi chủ đầu tư cần một đầu mối có thể nhìn tổng thể phạm vi, tiến độ và các điểm kiểm soát quan trọng ngay từ đầu.',
            ],
            [
                'question' => 'Yếu tố nào ảnh hưởng nhiều nhất đến chi phí của '.$blueprint['title'].'?',
                'answer' => $blueprint['cost_note'].' '.$blueprint['price_note'].' Những điểm như quy mô, mức hoàn thiện, điều kiện hiện trường và tiến độ mong muốn sẽ làm thay đổi tổng mức đầu tư.',
            ],
            [
                'question' => 'Làm sao để kiểm soát chất lượng và tiến độ trong quá trình triển khai?',
                'answer' => 'Đội ngũ cần bám chặt các mốc nghiệm thu, checklist hiện trường và cơ chế phối hợp vật tư theo phase. Với dịch vụ này, các điểm kiểm soát thường tập trung vào '.$highlights.'.',
            ],
        ];
    }

    protected function serviceRelatedQuestions(array $blueprint): array
    {
        return [
            'Trước khi yêu cầu '.$blueprint['keyword'].' tôi nên chuẩn bị những thông tin nào?',
            'Làm sao để kiểm soát phát sinh khi triển khai '.$blueprint['title'].'?',
            'Dịch vụ '.$blueprint['title'].' phù hợp nhất với loại công trình nào?',
        ];
    }

    protected function serviceContent(array $blueprint): string
    {
        $deliverables = collect($blueprint['deliverables'])->map(fn (string $item) => '<li>'.$item.'</li>')->implode('');
        $highlights = collect($blueprint['highlights'])->map(fn (string $item) => '<li>'.$item.'</li>')->implode('');
        $useCases = collect($blueprint['use_cases'])->map(fn (string $item) => '<li>'.$item.'</li>')->implode('');

        return <<<HTML
<p>{$blueprint['summary']}. Đây là nhóm dịch vụ được thiết kế cho {$blueprint['audience']}, vì vậy trọng tâm không chỉ nằm ở phần thi công mà còn ở cách tổ chức thông tin, điều phối vật tư và xử lý phát sinh để công trình đạt chất lượng ổn định.</p>
<h2>Phạm vi công việc thường bao gồm những gì?</h2>
<ul>{$deliverables}</ul>
<h2>Vì sao dịch vụ này phù hợp với {$blueprint['audience']}?</h2>
<p>{$blueprint['fit_note']} Khi đầu bài được bóc tách sớm, chủ đầu tư sẽ thấy rõ việc nào cần làm trước, việc nào có thể song song và đâu là điểm cần kiểm soát để tránh chậm tiến độ.</p>
<h2>Điểm kiểm soát chất lượng quan trọng</h2>
<ul>{$highlights}</ul>
<h2>Các tình huống áp dụng phổ biến</h2>
<ul>{$useCases}</ul>
<h2>Tối ưu chi phí và khả năng vận hành sau bàn giao</h2>
<p>{$blueprint['cost_note']} Bài toán của chúng tôi là giúp khách hàng nhìn được tổng thể trước khi chốt vật liệu, nhân sự và phương án triển khai, thay vì chỉ nhìn vào đơn giá từng hạng mục rời rạc.</p>
<p>Nếu bạn đang tìm một đơn vị cho dịch vụ <strong>{$blueprint['title']}</strong>, hãy ưu tiên đội ngũ có khả năng nói rõ phạm vi, tiến độ, checklist nghiệm thu và cơ chế phản hồi hiện trường ngay từ buổi làm việc đầu tiên.</p>
HTML;
    }

    protected function projectContent(array $blueprint): string
    {
        $materials = collect($blueprint['materials'])->map(fn (string $item) => '<li>'.$item.'</li>')->implode('');
        $results = collect($blueprint['results'])->map(fn (string $item) => '<li>'.$item.'</li>')->implode('');

        return <<<HTML
<p>{$blueprint['summary']}. Dự án được triển khai tại <strong>{$blueprint['location']}</strong> với quy mô khoảng <strong>{$blueprint['area']} m2</strong>, yêu cầu cách tổ chức thi công phù hợp với bối cảnh vận hành, nhân sự sử dụng và mục tiêu thẩm mỹ của chủ đầu tư.</p>
<h2>Bài toán đầu bài</h2>
<p>{$blueprint['challenge']} Vì vậy toàn bộ quá trình cần một nhịp triển khai gọn, dễ kiểm soát và có phương án xử lý trước cho các rủi ro thường gặp tại công trường.</p>
<h2>Giải pháp và cách tổ chức triển khai</h2>
<p>{$blueprint['solution']} Nhóm triển khai chia công việc theo phase, kiểm soát vật tư theo đầu việc và cập nhật hiện trường bám theo mốc nghiệm thu thay vì chờ đến cuối dự án mới rà soát.</p>
<h2>Vật liệu, kỹ thuật và công nghệ được ưu tiên</h2>
<ul>{$materials}</ul>
<h2>Kết quả bàn giao và giá trị vận hành</h2>
<ul>{$results}</ul>
<p>Trang dự án không chỉ đóng vai trò làm đẹp portfolio mà còn là một landing page SEO có thể dẫn khách hàng về nhóm dịch vụ tương ứng, giúp quá trình bán hàng mạch lạc và thuyết phục hơn.</p>
HTML;
    }

    protected function projectFaqItems(array $blueprint): array
    {
        $materials = collect($blueprint['materials'] ?? [])->take(3)->implode(', ');
        $results = collect($blueprint['results'] ?? [])->take(3)->implode(', ');

        return [
            [
                'question' => 'Dự án '.$blueprint['title'].' bắt đầu từ bài toán gì?',
                'answer' => $blueprint['challenge'].' Đây là lý do nhóm triển khai phải bóc tách đầu bài rõ ngay từ giai đoạn chuẩn bị để tránh kéo dài tiến độ hoặc sửa sai ở cuối dự án.',
            ],
            [
                'question' => 'Giải pháp chính giúp dự án '.$blueprint['title'].' về đích đúng kế hoạch là gì?',
                'answer' => $blueprint['solution'].' Việc chia phase, khóa mẫu vật liệu và kiểm soát hiện trường theo mốc nghiệm thu là yếu tố giúp toàn bộ công trình chạy ổn định hơn.',
            ],
            [
                'question' => 'Những vật liệu hoặc giải pháp kỹ thuật nào được ưu tiên ở dự án này?',
                'answer' => 'Các lựa chọn nổi bật gồm '.$materials.'. Đây là những hạng mục được ưu tiên để tăng độ bền, thẩm mỹ và khả năng vận hành sau khi bàn giao.',
            ],
            [
                'question' => 'Giá trị bàn giao nổi bật của '.$blueprint['title'].' là gì?',
                'answer' => 'Kết quả dễ thấy nhất của dự án này gồm '.$results.'. Những điểm này giúp case study trở thành tài liệu tham chiếu tốt cho các công trình có nhu cầu tương tự.',
            ],
        ];
    }

    protected function projectRelatedQuestions(array $blueprint): array
    {
        return [
            'Dự án tại '.$blueprint['location'].' có quy mô bao nhiêu và triển khai trong bao lâu?',
            'Nếu tôi có công trình tương tự '.$blueprint['title'].', nên bắt đầu từ khảo sát hay lập brief trước?',
            'Những yếu tố nào của dự án '.$blueprint['title'].' có thể áp dụng cho công trình khác?',
        ];
    }

    protected function blogContent(array $blueprint): string
    {
        $signals = collect($blueprint['signals'])->map(fn (string $item) => '<li>'.$item.'</li>')->implode('');
        $actions = collect($blueprint['actions'])->map(fn (string $item) => '<li>'.$item.'</li>')->implode('');
        $risks = collect($blueprint['risks'])->map(fn (string $item) => '<li>'.$item.'</li>')->implode('');

        return <<<HTML
<p>{$blueprint['summary']}. Trong bối cảnh thị trường xây dựng tại Việt Nam đang ưu tiên tiến độ, hiệu quả vận hành và vòng đời công trình, chủ đầu tư cần những nội dung giải thích rõ vấn đề chứ không chỉ liệt kê xu hướng chung chung.</p>
<h2>Vì sao chủ đề này đáng quan tâm?</h2>
<p>{$blueprint['importance']} Khi hiểu đúng bối cảnh ứng dụng, người đọc sẽ biết lúc nào nên đầu tư, lúc nào cần làm thử từng bước và khi nào nên dừng để tránh phát sinh chi phí không cần thiết.</p>
<h2>Dấu hiệu cho thấy doanh nghiệp nên xem xét giải pháp này</h2>
<ul>{$signals}</ul>
<h2>Những bước triển khai thực tế nên bắt đầu từ đâu?</h2>
<ul>{$actions}</ul>
<h2>Sai lầm thường gặp và cách tránh</h2>
<ul>{$risks}</ul>
<h2>Kết luận</h2>
<p>{$blueprint['closing']} Nếu bạn đang chuẩn bị cho một nhu cầu cụ thể như <a href="/dich-vu/{$blueprint['service_slug']}">{$blueprint['service_title']}</a> hoặc muốn tham khảo cách chúng tôi triển khai ở dự án <a href="/du-an/{$blueprint['project_slug']}">{$blueprint['project_title']}</a>, hãy tiếp tục đọc sâu trong hệ nội dung để có thêm góc nhìn phù hợp.</p>
HTML;
    }

    protected function serviceDetailConfig(array $blueprint): array
    {
        return [
            'hero_slides' => [
                ['uuid' => 'hero-1', 'eyebrow' => strtoupper($blueprint['badge']), 'title' => $blueprint['title'], 'description' => $blueprint['summary'], 'primary_label' => 'Liên hệ ngay', 'secondary_label' => 'Gọi tư vấn', 'image_alt' => $blueprint['title']],
                ['uuid' => 'hero-2', 'eyebrow' => 'QUY TRÌNH TRIỂN KHAI', 'title' => 'Rõ phạm vi, rõ tiến độ, rõ người phụ trách', 'description' => $blueprint['fit_note'], 'primary_label' => 'Nhận báo giá', 'secondary_label' => 'Xem dự án', 'image_alt' => 'Quy trình '.$blueprint['title']],
            ],
            'feature_blocks' => [
                ['uuid' => 'feature-1', 'eyebrow' => 'GIẢI PHÁP PHÙ HỢP', 'title' => 'Thiết kế phương án bám sát đầu bài thật', 'description' => $blueprint['fit_note'], 'highlights' => array_slice($blueprint['highlights'], 0, 3), 'image_alt' => 'Giải pháp '.$blueprint['title']],
                ['uuid' => 'feature-2', 'eyebrow' => 'TỔ CHỨC THI CÔNG', 'title' => 'Quản lý hiện trường theo từng phase', 'description' => $blueprint['cost_note'], 'highlights' => array_slice(array_reverse($blueprint['highlights']), 0, 3), 'image_alt' => 'Thi công '.$blueprint['title']],
            ],
            'process' => [
                'eyebrow' => 'QUY TRÌNH',
                'title' => 'Các bước triển khai dịch vụ',
                'description' => 'Nội dung này được seed theo cấu trúc cố định để service detail nào cũng có thông tin đủ sâu, đủ dễ hiểu và dễ chuyển đổi.',
                'cards' => [
                    ['icon_class' => 'fa-solid fa-comments', 'title' => 'Tiếp nhận nhu cầu', 'description' => 'Làm rõ mục tiêu, ngân sách, thời gian và những ràng buộc đặc thù của công trình.'],
                    ['icon_class' => 'fa-solid fa-ruler-combined', 'title' => 'Khảo sát - đo đạc', 'description' => 'Đánh giá hiện trạng, phương án kỹ thuật và khả năng tổ chức thi công tại chỗ.'],
                    ['icon_class' => 'fa-solid fa-file-circle-check', 'title' => 'Lập phương án', 'description' => 'Đề xuất phạm vi, vật liệu, tiến độ và cách kiểm soát chất lượng theo từng phase.'],
                    ['icon_class' => 'fa-solid fa-helmet-safety', 'title' => 'Thi công - giám sát', 'description' => 'Phối hợp nhân sự, nhà cung cấp và xử lý phát sinh ngay tại hiện trường.'],
                    ['icon_class' => 'fa-solid fa-key', 'title' => 'Nghiệm thu - bàn giao', 'description' => 'Kiểm tra checklist, hoàn thiện hồ sơ và hỗ trợ vận hành sau bàn giao.'],
                ],
            ],
            'pricing' => [
                'eyebrow' => 'MỨC ĐẦU TƯ THAM KHẢO',
                'title' => 'Khung tham khảo cho giai đoạn lập kế hoạch',
                'description' => $blueprint['price_note'],
                'columns' => ['label' => 'Gói', 'size' => 'Quy mô', 'price' => 'Mức đầu tư', 'note' => 'Ghi chú'],
                'rows' => $blueprint['pricing_rows'],
                'footnote' => 'Chi phí thực tế còn phụ thuộc hiện trạng, vật liệu, điều kiện thi công và yêu cầu vận hành của từng công trình.',
            ],
        ];
    }

    protected function seedLandingPages(Collection $services, Collection $projects, Collection $posts, Collection $libraryMedia): void
    {
        $featuredServiceSlugs = ['thi-cong-nha-pho-tron-goi', 'xay-biet-thu-tron-goi', 'thi-cong-nha-xuong-tien-che', 'thi-cong-nha-hang-phong-cach-viet'];
        $featuredProjectSlugs = ['biet-thu-lakeview', 'villa-san-vuon-ven-song-thu-duc', 'nha-xuong-co-khi-long-an-giai-doan-1', 'showroom-vat-lieu-hoan-thien-go-vap', 'nha-hang-san-vuon-riverside-thao-dien', 'biet-thu-nghi-duong-ho-tram', 'toa-nha-van-phong-ket-hop-showroom-tan-binh', 'nha-hang-rooftop-trung-tam-tp-hcm'];
        $featuredBlogSlugs = ['xu-huong-xay-dung-hien-dai-2026', 'vat-lieu-xanh-trong-xay-nha-dan-dung-tai-viet-nam', 'bim-thay-doi-quan-ly-cong-truong-tai-viet-nam', 'ai-camera-va-dashboard-tien-do-cho-cong-trinh', 'cai-tao-nha-pho-dang-o-ma-van-kiem-soat-bui-va-tien-do', 'he-mep-cho-quan-ca-phe-nhung-diem-hay-bi-bo-sot'];

        LandingPage::query()->updateOrCreate(['page_key' => 'about'], ['title' => 'Về Phong Thành Đạt', 'slug' => 've-chung-toi', 'hero_badge' => 'DOANH NGHIỆP XÂY DỰNG', 'hero_title' => 'Đồng hành cùng chủ đầu tư từ ý tưởng đến công trình hoàn thiện', 'hero_excerpt' => 'Phong Thành Đạt xây dựng hệ thống triển khai tập trung vào chất lượng, tiến độ, tính minh bạch và khả năng vận hành lâu dài cho từng dự án.', 'intro_title' => 'Một đội ngũ làm thật, quản lý sát và nói rõ từng hạng mục', 'intro_excerpt' => 'Chúng tôi phục vụ các nhóm công trình nhà ở, công nghiệp, văn phòng, quán ăn, nhà hàng và cải tạo vận hành với tư duy thi công thực tế.', 'body' => '<p>Phong Thành Đạt vận hành như một đối tác triển khai dự án, không chỉ là đội thi công nhận việc theo từng hạng mục rời rạc. Mục tiêu là giúp chủ đầu tư nhìn rõ phạm vi, tiến độ, chất lượng và chi phí ngay từ giai đoạn đầu.</p><p>Đội ngũ tập trung vào các nhóm công trình cần tổ chức thi công chặt chẽ như xây nhà trọn gói, cải tạo nhà phố đang ở, công trình công nghiệp, văn phòng, showroom, quán ăn và nhà hàng.</p>', 'meta_title' => 'Về Phong Thành Đạt | Đơn vị xây dựng và cải tạo công trình', 'meta_description' => 'Tìm hiểu cách Phong Thành Đạt triển khai công trình nhà ở, công nghiệp, văn phòng, quán ăn và nhà hàng theo quy trình rõ ràng.', 'og_title' => 'Về Phong Thành Đạt | Đơn vị xây dựng và cải tạo công trình', 'og_description' => 'Đội ngũ thi công và quản lý dự án tập trung vào chất lượng, tiến độ và khả năng vận hành thực tế sau bàn giao.', 'robots_directive' => 'index,follow']);

        $servicesPage = LandingPage::query()->updateOrCreate(['page_key' => 'services'], ['title' => 'Dịch vụ xây dựng và cải tạo', 'slug' => 'dich-vu', 'hero_badge' => 'DỊCH VỤ', 'hero_title' => 'Giải pháp xây dựng, cải tạo và hoàn thiện theo từng nhóm công trình', 'hero_excerpt' => 'Danh mục dịch vụ được tổ chức rõ theo nhà ở, công nghiệp, văn phòng, F&B và hoàn thiện vận hành để khách hàng dễ chọn đúng nhu cầu.', 'intro_title' => 'Dịch vụ được thiết kế theo logic triển khai thực tế', 'intro_excerpt' => 'Mỗi dịch vụ không chỉ mô tả phạm vi thi công mà còn thể hiện cách quản lý chất lượng, tiến độ, vật tư và rủi ro phát sinh.', 'cta_title' => 'Bạn cần một phương án triển khai rõ người phụ trách, rõ tiến độ và rõ chi phí?', 'cta_excerpt' => 'Gửi brief công trình để đội ngũ tư vấn nhóm dịch vụ phù hợp, phạm vi công việc cần làm trước và lộ trình triển khai ưu tiên.', 'cta_primary_label' => 'Nhận tư vấn nhanh', 'cta_primary_url' => '#consultation', 'cta_secondary_label' => 'Xem dự án', 'cta_secondary_url' => '/du-an', 'meta_title' => 'Dịch vụ xây dựng, xây nhà và cải tạo | Phong Thành Đạt', 'meta_description' => 'Khám phá các dịch vụ xây nhà, cải tạo, công trình nhà ở, công nghiệp, quán ăn và nhà hàng với nội dung rõ ràng, dễ hiểu.', 'og_title' => 'Dịch vụ xây dựng, xây nhà và cải tạo | Phong Thành Đạt', 'og_description' => 'Giải pháp thi công theo từng nhóm công trình, chú trọng chất lượng, tiến độ và khả năng vận hành sau bàn giao.', 'robots_directive' => 'index,follow', 'faq_items' => [['question' => 'Phong Thành Đạt đang triển khai những nhóm dịch vụ nào?', 'answer' => 'Landing dịch vụ hiện được tổ chức theo các nhóm nhà ở, cải tạo, công trình công nghiệp, văn phòng, showroom và F&B để khách hàng chọn đúng nhu cầu ngay từ đầu.'], ['question' => 'Tôi nên bắt đầu từ trang dịch vụ hay trang dự toán?', 'answer' => 'Nếu bạn đang cần hiểu phạm vi công việc, hãy bắt đầu từ trang dịch vụ. Nếu đã có brief sơ bộ và muốn kiểm tra nhanh đầu vào chi phí, hãy chuyển sang trang dự toán.'], ['question' => 'Cần chuẩn bị gì trước khi gửi yêu cầu tư vấn dịch vụ?', 'answer' => 'Nên chuẩn bị loại công trình, quy mô, hiện trạng, mốc tiến độ mong muốn và các yêu cầu đặc thù về vật liệu hoặc vận hành để đội ngũ tư vấn nhóm dịch vụ sát hơn.']], 'service_detail_config' => ['partners' => ['eyebrow' => 'HỆ SINH THÁI TRIỂN KHAI', 'title' => 'Nhóm đối tác hỗ trợ tiến độ và chất lượng', 'description' => 'Các collection ảnh đối tác được dùng như tín hiệu niềm tin cho trang dịch vụ, thể hiện chuỗi cung ứng và năng lực phối hợp thực tế.', 'items' => [['uuid' => 'partner-1', 'name' => 'Nhà cung cấp vật liệu hoàn thiện', 'description' => 'Đảm bảo mẫu mã, tiến độ giao hàng và tiêu chuẩn hoàn thiện đồng bộ.', 'image_alt' => 'Đối tác vật liệu hoàn thiện'], ['uuid' => 'partner-2', 'name' => 'Đối tác kết cấu và lắp dựng', 'description' => 'Phối hợp kết cấu, cẩu lắp và gia công tại xưởng cho các dự án cần tốc độ.', 'image_alt' => 'Đối tác kết cấu và lắp dựng'], ['uuid' => 'partner-3', 'name' => 'Đối tác hệ MEP', 'description' => 'Đồng bộ điện, nước, thông gió, bếp công nghiệp và thiết bị vận hành.', 'image_alt' => 'Đối tác hệ MEP'], ['uuid' => 'partner-4', 'name' => 'Đối tác facade và nhôm kính', 'description' => 'Xử lý các hạng mục mặt dựng, cửa đi và giải pháp che nắng phù hợp.', 'image_alt' => 'Đối tác facade và nhôm kính'], ['uuid' => 'partner-5', 'name' => 'Đối tác kiểm soát hiện trường', 'description' => 'Tăng tính minh bạch trong kiểm tra chất lượng và an toàn thi công.', 'image_alt' => 'Đối tác kiểm soát hiện trường']]]]]);

        LandingPage::query()->updateOrCreate(['page_key' => 'projects'], ['title' => 'Dự án tiêu biểu', 'slug' => 'du-an', 'hero_badge' => 'DỰ ÁN', 'hero_title' => 'Các case study thể hiện cách chúng tôi giải bài toán thật của công trình', 'hero_excerpt' => 'Danh mục dự án được xây để hỗ trợ khách hàng xem nhanh quy mô, vị trí, vật liệu, tiến độ và kết quả bàn giao của từng công trình.', 'intro_title' => 'Mỗi dự án là một câu chuyện triển khai có đầu vào rõ và đầu ra đo được', 'intro_excerpt' => 'Từ nhà phố, biệt thự đến nhà xưởng, văn phòng, quán ăn và nhà hàng, nội dung dự án được viết theo hướng dễ đọc và có giá trị SEO lâu dài.', 'meta_title' => 'Dự án xây dựng tiêu biểu | Nhà ở, công nghiệp, nhà hàng', 'meta_description' => 'Xem các dự án nhà ở, biệt thự, công nghiệp, văn phòng, quán ăn và showroom được triển khai theo quy trình thực tế.', 'og_title' => 'Dự án xây dựng tiêu biểu | Nhà ở, công nghiệp, nhà hàng', 'og_description' => 'Tổng hợp case study giúp khách hàng hình dung quy mô, giải pháp và chất lượng bàn giao công trình.', 'robots_directive' => 'index,follow', 'faq_items' => [['question' => 'Tôi nên xem dự án theo tiêu chí nào trước?', 'answer' => 'Bạn nên bắt đầu từ loại công trình, vị trí, quy mô diện tích và timeline để nhanh chóng lọc ra các case study gần nhất với nhu cầu thực tế của mình.'], ['question' => 'Trang dự án giúp tôi hiểu được những gì ngoài hình ảnh đẹp?', 'answer' => 'Ngoài ảnh, mỗi project detail còn tóm tắt bối cảnh đầu bài, giải pháp triển khai, vật liệu ưu tiên, tiến độ và giá trị bàn giao để người xem có thêm dữ liệu tham chiếu.'], ['question' => 'Khi nào nên chuyển từ trang dự án sang trang dịch vụ hoặc dự toán?', 'answer' => 'Hãy chuyển sang trang dịch vụ khi bạn muốn biết phạm vi công việc phù hợp, và sang trang dự toán khi đã có đủ thông số sơ bộ để chuẩn hóa brief chi phí.']]]);

        LandingPage::query()->updateOrCreate(['page_key' => 'blog'], ['title' => 'Blog xây dựng và công nghệ xây dựng', 'slug' => 'blog', 'hero_badge' => 'BLOG', 'hero_title' => 'Kiến thức xây dựng dễ hiểu, có chiều sâu và sát với bối cảnh Việt Nam', 'hero_excerpt' => 'Các bài viết tập trung vào xây nhà, cải tạo, vật liệu, công nghệ mới và kinh nghiệm vận hành công trình nhà ở, công nghiệp, quán ăn, nhà hàng.', 'intro_title' => 'Nội dung viết để hỗ trợ ra quyết định, không chỉ để đọc lướt', 'intro_excerpt' => 'Blog được tổ chức theo cụm chủ đề nhằm tăng topical authority và dẫn người đọc về đúng dịch vụ hoặc dự án liên quan.', 'meta_title' => 'Blog xây dựng, xây nhà và công nghệ mới tại Việt Nam', 'meta_description' => 'Đọc các bài viết về xây dựng, xây nhà, cải tạo, vật liệu mới, BIM, AI công trường và kinh nghiệm triển khai công trình tại Việt Nam.', 'og_title' => 'Blog xây dựng, xây nhà và công nghệ mới tại Việt Nam', 'og_description' => 'Tổng hợp nội dung dễ hiểu, chuẩn SEO và giàu giá trị ứng dụng cho nhà ở, công nghiệp, quán ăn và nhà hàng.', 'robots_directive' => 'index,follow']);

        LandingPage::query()->updateOrCreate(['page_key' => 'estimate'], ['title' => 'Dự toán công trình', 'slug' => 'du-toan', 'hero_badge' => 'DỰ TOÁN', 'hero_title' => 'Chuẩn hóa đầu vào trước khi gửi yêu cầu dự toán công trình', 'hero_excerpt' => 'Landing page này giúp khách hàng nhập đúng thông số, chọn đúng cấp dự toán và cung cấp thông tin chính xác hơn qua popup xác nhận.', 'intro_title' => 'Chọn cấp dự toán phù hợp', 'intro_excerpt' => 'Có 3 cấp dự toán để phù hợp các nhu cầu từ sơ bộ đến cao cấp, trong đó các cấp cao có thể yêu cầu mã key được quản lý riêng.', 'cta_title' => 'Bạn cần gửi yêu cầu dự toán ngay bây giờ?', 'cta_excerpt' => 'Điền thông số đầu vào, chọn cấp dự toán phù hợp và xác nhận lại thông tin khách hàng để hệ thống ghi nhận.', 'cta_primary_label' => 'Gửi yêu cầu dự toán', 'cta_primary_url' => '#estimate-builder', 'cta_secondary_label' => 'Xem dịch vụ', 'cta_secondary_url' => '/dich-vu', 'meta_title' => 'Landing dự toán công trình | Chuẩn hóa đầu vào và cấp dự toán', 'meta_description' => 'Nhập thông số đầu vào, chọn 3 cấp dự toán và gửi yêu cầu chính xác hơn qua popup xác nhận thông tin khách hàng.', 'og_title' => 'Landing dự toán công trình | Chuẩn hóa đầu vào và cấp dự toán', 'og_description' => 'Trang dự toán giúp chuẩn hóa brief đầu vào, quản lý cấp dự toán và kiểm soát key cho các cấp cao hơn.', 'robots_directive' => 'index,follow', 'estimate_config' => EstimatePageContent::defaultConfig(), 'faq_items' => EstimatePageContent::defaultFaqItems()]);

        $homeConfig = [
            'hero' => [
                'slides' => [
                    ['uuid' => 'hero-1', 'eyebrow' => 'TỔNG THẦU XÂY DỰNG', 'title' => 'Xây nhà, cải tạo và thi công công trình với tiến độ rõ ràng', 'description' => 'Dữ liệu demo chuẩn tiếng Việt, có chiều sâu SEO và gắn ảnh đúng collection để frontsite hiển thị ngay.', 'primary_label' => 'Xem dịch vụ', 'primary_url' => '/dich-vu', 'secondary_label' => 'Xem dự án', 'secondary_url' => '/du-an', 'image_alt' => 'Tổng thầu xây dựng và cải tạo công trình'],
                    ['uuid' => 'hero-2', 'eyebrow' => 'NHÀ Ở VÀ BIỆT THỰ', 'title' => 'Không gian sống được tổ chức tốt ngay từ bản vẽ đến bàn giao', 'description' => 'Tập trung vào nhà phố, biệt thự, căn hộ và các hạng mục hoàn thiện cần phối hợp chặt giữa thiết kế, vật tư và hiện trường.', 'primary_label' => 'Khám phá dự án nhà ở', 'primary_url' => '/du-an', 'secondary_label' => 'Liên hệ ngay', 'secondary_url' => '#consultation', 'image_alt' => 'Thi công nhà ở và biệt thự'],
                    ['uuid' => 'hero-3', 'eyebrow' => 'CÔNG NGHIỆP VÀ LOGISTICS', 'title' => 'Nhà xưởng, kho vận và văn phòng điều hành cần một nhịp triển khai thống nhất', 'description' => 'Dữ liệu demo phản ánh đúng logic của công trình công nghiệp hiện đại từ mặt bằng đến vận hành sau bàn giao.', 'primary_label' => 'Xem công trình công nghiệp', 'primary_url' => '/du-an', 'secondary_label' => 'Xem blog công nghệ', 'secondary_url' => '/blog', 'image_alt' => 'Thi công nhà xưởng và logistics'],
                    ['uuid' => 'hero-4', 'eyebrow' => 'F&B VÀ HOSPITALITY', 'title' => 'Quán ăn, nhà hàng và khách sạn mini cần tối ưu vận hành ngay trong quá trình thi công', 'description' => 'Bộ dữ liệu mẫu đã bao gồm các chủ đề về bếp công nghiệp, MEP, cải tạo vận hành và trải nghiệm thương hiệu.', 'primary_label' => 'Xem dự án F&B', 'primary_url' => '/du-an', 'secondary_label' => 'Đọc bài viết mới', 'secondary_url' => '/blog', 'image_alt' => 'Thi công quán ăn và nhà hàng'],
                ],
            ],
            'stats' => [
                'items' => [
                    ['uuid' => 'stat-1', 'value' => '18+', 'label' => 'Năm kinh nghiệm vận hành dự án'],
                    ['uuid' => 'stat-2', 'value' => '480+', 'label' => 'Công trình và hạng mục đã triển khai'],
                    ['uuid' => 'stat-3', 'value' => '90+', 'label' => 'Nhân sự kỹ thuật và điều phối'],
                    ['uuid' => 'stat-4', 'value' => '24/7', 'label' => 'Khả năng phản hồi hiện trường'],
                ],
            ],
            'services' => [
                'eyebrow' => 'Dịch vụ nổi bật',
                'title' => 'Những nhóm dịch vụ đang được quan tâm nhiều nhất',
                'description' => '4 card này liên kết trực tiếp về service detail để kiểm tra flow điều hướng và CTA.',
                'cta_label' => 'Xem toàn bộ dịch vụ',
                'cta_url' => '/dich-vu',
                'items' => collect($featuredServiceSlugs)->map(fn (string $slug) => ['uuid' => 'service-'.$slug, 'icon' => 'domain', 'title' => $services[$slug]->title, 'description' => $services[$slug]->excerpt, 'service_id' => $services[$slug]->id, 'link_label' => 'Xem chi tiết', 'url' => '/dich-vu/'.$services[$slug]->slug])->values()->all(),
            ],
            'gallery' => [
                'eyebrow' => 'Khoảnh khắc công trình',
                'title' => 'Gallery demo cho nhà ở, công nghiệp, showroom và F&B',
                'description' => 'Ảnh được copy vào collection riêng của landing page để kiểm tra đúng luồng media cho homepage gallery.',
                'items' => collect($featuredProjectSlugs)->map(fn (string $slug, int $index) => ['uuid' => 'gallery-'.($index + 1), 'title' => $projects[$slug]->title, 'subtitle' => $projects[$slug]->location, 'url' => '/du-an/'.$projects[$slug]->slug, 'image_alt' => $projects[$slug]->title])->values()->all(),
            ],
            'process' => [
                'eyebrow' => 'Quy trình làm việc',
                'title' => 'Rõ bước, rõ trách nhiệm, rõ điểm kiểm soát',
                'description' => 'Đây là phần mô phỏng đúng logic một doanh nghiệp xây dựng cần thể hiện để tăng niềm tin và khả năng chuyển đổi.',
                'cards' => [
                    ['uuid' => 'process-1', 'title' => 'Tiếp nhận brief', 'description' => 'Xác định loại công trình, mục tiêu sử dụng, ngân sách và các ràng buộc đặc thù ngay từ đầu.'],
                    ['uuid' => 'process-2', 'title' => 'Khảo sát và đánh giá', 'description' => 'Rà hiện trạng, mặt bằng, kết cấu, hệ kỹ thuật và điều kiện tổ chức thi công thực tế.'],
                    ['uuid' => 'process-3', 'title' => 'Đề xuất giải pháp', 'description' => 'Lập phương án mặt bằng, vật liệu, cấu kiện, tiến độ và cách kiểm soát chất lượng theo từng phase.'],
                    ['uuid' => 'process-4', 'title' => 'Triển khai hiện trường', 'description' => 'Điều phối đội ngũ, vật tư, nhà cung cấp và xử lý vướng mắc phát sinh trong quá trình thi công.'],
                    ['uuid' => 'process-5', 'title' => 'Nghiệm thu - bàn giao', 'description' => 'Kiểm tra checklist, chạy thử hạng mục cần vận hành và bàn giao theo hồ sơ rõ ràng.'],
                ],
            ],
            'values' => [
                'eyebrow' => 'Giá trị cốt lõi',
                'title' => 'Khách hàng cần nhiều hơn một lời hứa đẹp về tiến độ và chất lượng',
                'description' => 'Phần này giúp trang chủ có chiều sâu nội dung mà vẫn gọn và dễ đọc.',
                'cards' => [
                    ['uuid' => 'value-1', 'title' => 'Minh bạch từng hạng mục', 'role' => 'Quản lý chi phí', 'text' => 'Phạm vi thi công, vật tư và mốc nghiệm thu được mô tả rõ để hạn chế tranh cãi khi triển khai.'],
                    ['uuid' => 'value-2', 'title' => 'Giải pháp bám sát vận hành', 'role' => 'Tối ưu công năng', 'text' => 'Thiết kế và thi công phải phục vụ cách sống, cách bán hàng hoặc cách vận hành thật của công trình.'],
                    ['uuid' => 'value-3', 'title' => 'Đội ngũ phối hợp chủ động', 'role' => 'Giảm phát sinh', 'text' => 'Kỹ thuật, hiện trường và nhà cung cấp cần nói cùng một ngôn ngữ để công trình về đích đúng chất lượng.'],
                ],
            ],
            'insights' => [
                'eyebrow' => 'Blog mới',
                'title' => 'Nội dung hỗ trợ quyết định cho chủ đầu tư và đội vận hành',
                'description' => 'Nhóm bài viết này liên kết từ trang chủ sang blog để kiểm tra khả năng dẫn người dùng đi sâu vào hệ nội dung.',
                'cta_label' => 'Xem tất cả bài viết',
                'cta_url' => '/blog',
                'items' => collect($featuredBlogSlugs)->map(fn (string $slug, int $index) => ['uuid' => 'insight-'.($index + 1), 'blog_ref' => (string) $posts[$slug]->id])->values()->all(),
            ],
            'final_cta' => [
                'title' => 'Sẵn sàng biến nhu cầu thành một kế hoạch thi công khả thi?',
                'description' => 'Dữ liệu demo đã sẵn sàng để trình bày năng lực doanh nghiệp và khả năng chuyển nhu cầu thành lộ trình triển khai thực tế.',
                'primary_label' => 'Gửi yêu cầu tư vấn',
                'secondary_label' => 'Xem toàn bộ dự án',
                'secondary_url' => '/du-an',
            ],
            'consultation' => [
                'eyebrow' => 'Liên hệ ngay',
                'title' => 'Gửi nhu cầu để đội ngũ tư vấn nhóm dịch vụ phù hợp',
                'description' => 'Phần form này dùng để kiểm tra khả năng chuyển đổi của frontsite sau khi người dùng xem nội dung dịch vụ, dự án hoặc blog.',
                'button_label' => 'Gửi yêu cầu',
                'success_message' => 'Yêu cầu đã được ghi nhận. Đội ngũ sẽ liên hệ với bạn sớm nhất có thể.',
            ],
        ];

        $homePage = LandingPage::query()->updateOrCreate(
            ['page_key' => 'home'],
            [
                'title' => 'Trang chủ',
                'hero_badge' => 'PHONG THÀNH ĐẠT',
                'hero_title' => 'Xây dựng niềm tin bằng công trình được tổ chức chỉn chu',
                'hero_excerpt' => 'Trang chủ được seed với slider, gallery, dịch vụ nổi bật và bài viết mới để frontsite hiển thị đầy đủ ngay sau khi chạy seeder.',
                'intro_title' => 'Từ nhà ở đến công trình công nghiệp, điều quan trọng nhất là cách tổ chức thi công',
                'intro_excerpt' => 'Chúng tôi xây dữ liệu demo để khách hàng có thể hình dung rõ cách hệ thống này vận hành khi đưa vào khai thác thực tế.',
                'cta_title' => 'Bạn cần tư vấn nhóm dịch vụ phù hợp cho công trình sắp triển khai?',
                'cta_excerpt' => 'Chia sẻ nhu cầu, vị trí, quy mô và mục tiêu sử dụng để đội ngũ gợi ý lộ trình triển khai phù hợp nhất.',
                'cta_primary_label' => 'Nhận tư vấn',
                'cta_primary_url' => '#consultation',
                'cta_secondary_label' => 'Gọi hotline',
                'cta_secondary_url' => '',
                'meta_title' => 'Phong Thành Đạt | Xây dựng, cải tạo và công trình công nghiệp',
                'meta_description' => 'Website demo cho doanh nghiệp xây dựng với dữ liệu mẫu chuẩn SEO về xây nhà, cải tạo, công trình công nghiệp, quán ăn và nhà hàng.',
                'og_title' => 'Phong Thành Đạt | Xây dựng, cải tạo và công trình công nghiệp',
                'og_description' => 'Trang chủ hiển thị slider, gallery, dự án, dịch vụ và blog để phục vụ demo frontsite và nội dung SEO.',
                'robots_directive' => 'index,follow',
                'home_config' => $homeConfig,
            ],
        );

        foreach (data_get($homePage->home_config, 'hero.slides', []) as $index => $slide) {
            $this->copyMediaFromPool($homePage, HomePageContent::heroSlideCollection($slide['uuid']), ['commercial', 'residential', 'industrial', 'interior'][$index % 4], $index, $libraryMedia, $slide['image_alt']);
        }

        foreach (data_get($homePage->home_config, 'gallery.items', []) as $index => $item) {
            $this->copyMediaFromPool($homePage, HomePageContent::galleryCollection($item['uuid']), ['residential', 'commercial', 'industrial', 'interior'][$index % 4], $index + 2, $libraryMedia, $item['image_alt']);
        }

        foreach (data_get($servicesPage->service_detail_config, 'partners.items', []) as $index => $partner) {
            $this->copyMediaFromPool($servicesPage, ServiceDetailContent::partnerCollection($partner['uuid']), ['commercial', 'industrial', 'technology', 'interior', 'residential'][$index % 5], $index, $libraryMedia, $partner['image_alt']);
        }
    }

    protected function seedHomeSlider(Collection $libraryMedia): void
    {
        $slider = Slider::query()->updateOrCreate(['location' => 'home-hero'], ['name' => 'Home Hero Slider', 'description' => 'Slider demo cho trang chủ.', 'is_active' => true, 'autoplay_delay' => 5500]);

        $items = [
            ['order' => 1, 'title' => 'Xây nhà, cải tạo và hoàn thiện theo nhịp triển khai thực tế', 'subtitle' => 'TỔNG THẦU XÂY DỰNG', 'description' => 'Slider fallback cho homepage, đồng bộ với nội dung home_config để kiểm tra cả hai luồng.', 'cta_label' => 'Xem dịch vụ', 'cta_url' => '/dich-vu', 'pool' => 'commercial'],
            ['order' => 2, 'title' => 'Nhà ở, biệt thự và nội thất cần một đầu mối quản lý xuyên suốt', 'subtitle' => 'NHÀ Ở VÀ BIỆT THỰ', 'description' => 'Nội dung và ảnh được seed vào đúng media collection của SliderItem.', 'cta_label' => 'Xem dự án', 'cta_url' => '/du-an', 'pool' => 'residential'],
            ['order' => 3, 'title' => 'Nhà xưởng, logistics và kết cấu thép yêu cầu kiểm soát chặt về tiến độ', 'subtitle' => 'CÔNG NGHIỆP', 'description' => 'Dữ liệu demo cho thấy hệ thống có thể vận hành tốt với công trình quy mô lớn.', 'cta_label' => 'Khám phá nhà xưởng', 'cta_url' => '/du-an', 'pool' => 'industrial'],
            ['order' => 4, 'title' => 'Quán ăn, nhà hàng và showroom cần tối ưu trải nghiệm từ khi còn trên bản vẽ', 'subtitle' => 'F&B VÀ THƯƠNG MẠI', 'description' => 'Bộ dữ liệu mẫu đã bao phủ nhiều nhóm ngành khác nhau để phục vụ demo frontsite.', 'cta_label' => 'Đọc blog', 'cta_url' => '/blog', 'pool' => 'interior'],
        ];

        foreach ($items as $index => $item) {
            $sliderItem = SliderItem::query()->updateOrCreate(['slider_id' => $slider->id, 'order' => $item['order']], ['title' => $item['title'], 'subtitle' => $item['subtitle'], 'description' => $item['description'], 'image_alt' => $item['title'], 'cta_label' => $item['cta_label'], 'cta_url' => $item['cta_url'], 'effect' => 'animate__fadeInUp', 'is_active' => true]);
            $this->copyMediaFromPool($sliderItem, 'image', $item['pool'], $index, $libraryMedia, $item['title']);
        }

        SliderItem::query()->where('slider_id', $slider->id)->whereNotIn('order', collect($items)->pluck('order')->all())->delete();
    }

    protected function refreshSiteSettings(SiteSetting $site, Collection $projects): void
    {
        $site->update(['site_tagline' => 'Xây dựng giá trị vận hành bền vững', 'site_description' => 'Doanh nghiệp xây dựng và cải tạo công trình nhà ở, công nghiệp, quán ăn, nhà hàng, văn phòng và showroom tại Việt Nam.', 'about_summary' => 'Phong Thành Đạt tập trung vào giải pháp thi công rõ phạm vi, rõ tiến độ và rõ điểm kiểm soát chất lượng cho từng nhóm công trình.', 'experience_years' => 18, 'completed_projects_count' => max(480, $projects->count()), 'team_size' => 90, 'quality_badge_label' => 'Quy trình - Chất lượng - Tiến độ', 'seo_title' => 'Phong Thành Đạt | Xây dựng, cải tạo và công trình công nghiệp', 'seo_description' => 'Giải pháp xây dựng, xây nhà, cải tạo, nhà hàng, quán ăn, văn phòng và công trình công nghiệp với nội dung chuẩn SEO và hình ảnh demo đầy đủ.', 'seo_keywords' => 'xây dựng, xây nhà, cải tạo, công trình nhà ở, công trình công nghiệp, quán ăn, nhà hàng, blog xây dựng, công nghệ xây dựng', 'seo_robots' => 'index,follow']);
    }

    protected function copyMediaFromPool(HasMedia $model, string $collection, string $pool, int $offset, Collection $libraryMedia, string $alt): void
    {
        $sourceMedia = $this->resolvePoolMedia($pool, $offset, $libraryMedia);

        if (! $sourceMedia) {
            return;
        }

        if (method_exists($model, 'clearMediaCollection')) {
            $model->clearMediaCollection($collection);
        }

        $sourceMedia->copy(model: $model, collectionName: $collection, diskName: config('media-library.disk_name', 'public'), fileAdderCallback: fn ($fileAdder) => $fileAdder->withCustomProperties(['alt' => $alt, 'source_library_media_id' => (int) $sourceMedia->getKey()]));
    }

    protected function resolvePoolMedia(string $pool, int $offset, Collection $libraryMedia): ?Media
    {
        $files = $this->imagePools[$pool] ?? [];

        foreach ($files as $step => $fileName) {
            $selected = $files[($offset + $step) % count($files)] ?? null;

            if ($selected && $libraryMedia->has($selected)) {
                return $libraryMedia->get($selected);
            }
        }

        return $libraryMedia->values()->get($offset % max(1, $libraryMedia->count()));
    }

    protected function estimateReadingTime(string $html): int
    {
        $tokens = preg_split('/\s+/u', trim(strip_tags($html))) ?: [];

        return max(4, (int) ceil(count(array_filter($tokens)) / 180));
    }

    protected function seoDescription(string $summary, string $keyword): string
    {
        return Str::limit(trim($summary).' Nội dung tập trung vào '.$keyword.' với góc nhìn thực tế, dễ hiểu và có thể áp dụng tại Việt Nam.', 160, '');
    }
}
