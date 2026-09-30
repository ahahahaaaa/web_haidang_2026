<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$settings = Src\Domains\Cms\Models\SiteSetting::query()->firstOrFail();
$path = App\Support\FrontsiteSectionHeadings::STRUCTURED_DATA_KEY.'.tour_cta';
$before = data_get($settings->structured_data, $path);
$defaults = App\Support\FrontsiteSectionHeadings::definitions()['tour']['items']['tour_cta'];

if (! in_array('--apply', $argv, true)) {
    echo json_encode(['id' => $settings->id, 'before' => $before, 'after' => ['title' => $defaults['title'], 'description' => $defaults['description']]], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$backup = __DIR__.'/tour-offer-copy-backup-'.date('Ymd-His').'.json';
if (file_put_contents($backup, json_encode(['site_setting_id' => $settings->id, 'path' => $path, 'before' => $before], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) === false) {
    throw new RuntimeException('Could not save backup.');
}

$data = $settings->structured_data ?? [];
data_set($data, $path.'.title', $defaults['title']);
data_set($data, $path.'.description', $defaults['description']);
$settings->update(['structured_data' => $data]);
echo 'Updated tour CTA title/description. Backup: '.$backup.PHP_EOL;
