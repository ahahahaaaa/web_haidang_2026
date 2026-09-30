<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Support\ContentGallery;
use Src\Domains\Cms\Models\Tour;

$tour = Tour::query()
    ->where('slug', 'tour-du-lich-tay-tang-cung-dien-potala')
    ->with('media')
    ->firstOrFail();

$days = [
    [
        'uuid' => 'tibet-itinerary-day-1',
        'title' => 'NGÀY 1: VIỆT NAM – CÔN MINH 100KM',
        'meals' => 'Tối (trên máy bay)',
        'image_alt' => 'Khung cảnh cao nguyên mở đầu hành trình khám phá Tây Tạng',
        'source_collection' => 'cover',
    ],
    [
        'uuid' => 'tibet-itinerary-day-2',
        'title' => 'NGÀY 2: CÔN MINH – LHASA – PHỐ ĐI BỘ BAKHOR 100KM',
        'meals' => 'Sáng, trưa, tối',
        'image_alt' => 'Khung cảnh hồ Yamdrok trên hành trình đến Lhasa',
        'source_collection' => ContentGallery::tourCollection('7af155f5-385f-4322-95b2-8c0d76db7c14'),
    ],
    [
        'uuid' => 'tibet-itinerary-day-3',
        'title' => 'NGÀY 3: CHÙA ĐẠI CHIÊU – CUNG ĐIỆN POTALA – ĐỀN THỜ THẦN TÀI DZAMBALA',
        'meals' => 'Sáng, trưa, tối',
        'image_alt' => 'Du khách trong trang phục Tây Tạng trước cung điện Potala',
        'source_collection' => ContentGallery::tourCollection('612a49a0-bb23-4daf-a8fd-323675295274'),
    ],
    [
        'uuid' => 'tibet-itinerary-day-4',
        'title' => 'NGÀY 4: LHASA – ĐÈO GAMBALA – HỒ YAMDROK – SÔNG BĂNG VĨNH CỬU – SHIGATSE',
        'meals' => 'Sáng, trưa, tối',
        'image_alt' => 'Sông băng Karola hùng vĩ trên đường đến Shigatse',
        'source_collection' => ContentGallery::tourCollection('b8e81eac-831f-4597-bf46-d9628093bc0c'),
    ],
    [
        'uuid' => 'tibet-itinerary-day-5',
        'title' => 'NGÀY 5: SHIGATSE – TU VIỆN TASHILHUNPO – SÂN BAY LHASA – CÔN MINH',
        'meals' => 'Sáng, trưa, tối',
        'image_alt' => 'Du khách trải nghiệm chụp ảnh cùng bò Yak tại Tây Tạng',
        'source_collection' => ContentGallery::tourCollection('5e74c05a-cb93-43c5-ac1c-cb94afaf3fe2'),
    ],
    [
        'uuid' => 'tibet-itinerary-day-6',
        'title' => 'NGÀY 6: CÔN MINH – VIỆT NAM',
        'meals' => 'Sáng, trưa, tối (trên máy bay)',
        'image_alt' => 'Khung cảnh Tây Tạng khép lại hành trình sáu ngày',
        'source_collection' => 'cover',
    ],
];

$currentItinerary = collect($tour->itinerary ?? [])->values();

if ($currentItinerary->count() !== count($days)) {
    throw new RuntimeException('Tour phải có đúng '.count($days).' ngày trước khi đồng bộ dữ liệu demo.');
}

$itinerary = collect($days)->map(function (array $day, int $index) use ($currentItinerary): array {
    $current = $currentItinerary->get($index, []);

    return [
        'uuid' => $day['uuid'],
        'title' => $day['title'],
        'meals' => $day['meals'],
        'image_alt' => $day['image_alt'],
        'image_url' => '',
        'content' => (string) data_get($current, 'content', ''),
    ];
})->all();

$preview = collect($days)->map(fn (array $day, int $index) => [
    'day' => $index + 1,
    'title' => $day['title'],
    'meals' => $day['meals'],
    'image_alt' => $day['image_alt'],
    'source_collection' => $day['source_collection'],
    'target_collection' => ContentGallery::tourItineraryCollection($day['uuid']),
])->all();

if (in_array('--verify', $argv, true)) {
    $tour->refresh()->load('media');

    echo json_encode([
        'tour_id' => $tour->getKey(),
        'slug' => $tour->slug,
        'days' => collect($tour->itinerary ?? [])->map(function (array $item, int $index) use ($tour): array {
            $uuid = (string) data_get($item, 'uuid');
            $collection = ContentGallery::tourItineraryCollection($uuid);
            $media = $tour->getFirstMedia($collection);

            return [
                'day' => $index + 1,
                'uuid' => $uuid,
                'title' => data_get($item, 'title'),
                'meals' => data_get($item, 'meals'),
                'image_alt' => data_get($item, 'image_alt'),
                'media_id' => $media?->getKey(),
                'media_collection' => $media?->collection_name,
                'media_url' => $media?->getUrl(),
            ];
        })->all(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;
    exit;
}

if (! in_array('--apply', $argv, true)) {
    echo json_encode([
        'tour_id' => $tour->getKey(),
        'tour' => $tour->title,
        'changes' => $preview,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;
    exit;
}

$backupPath = __DIR__.'/tibet-itinerary-demo-backup-'.date('Ymd-His').'.json';
$backup = [
    'tour_id' => $tour->getKey(),
    'slug' => $tour->slug,
    'itinerary' => $tour->itinerary,
    'target_media' => collect($days)->mapWithKeys(function (array $day) use ($tour): array {
        $collection = ContentGallery::tourItineraryCollection($day['uuid']);

        return [$collection => $tour->getMedia($collection)->map(fn ($media) => [
            'id' => $media->getKey(),
            'file_name' => $media->file_name,
            'custom_properties' => $media->custom_properties,
        ])->all()];
    })->all(),
];

if (file_put_contents($backupPath, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) === false) {
    throw new RuntimeException('Không thể tạo file backup trước khi cập nhật.');
}

foreach ($days as $day) {
    $sourceMedia = $tour->getFirstMedia($day['source_collection']);

    if (! $sourceMedia) {
        throw new RuntimeException('Không tìm thấy ảnh nguồn trong collection '.$day['source_collection'].'.');
    }

    $targetCollection = ContentGallery::tourItineraryCollection($day['uuid']);
    $tour->clearMediaCollection($targetCollection);
    $sourceMedia->copy(
        model: $tour,
        collectionName: $targetCollection,
        diskName: config('media-library.disk_name', 'public'),
        fileAdderCallback: fn ($fileAdder) => $fileAdder->withCustomProperties([
            'alt' => $day['image_alt'],
            'source_demo_media_id' => (int) $sourceMedia->getKey(),
        ]),
    );
}

$tour->update(['itinerary' => $itinerary]);

echo json_encode([
    'updated' => true,
    'tour_id' => $tour->getKey(),
    'slug' => $tour->slug,
    'backup' => $backupPath,
    'days' => $preview,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;
