<?php

namespace App\Console\Commands;

use App\Services\Admin\FrontendHtmlImageImporter;
use App\Services\Admin\FrontendHtmlImageScanner;
use Illuminate\Console\Command;
use InvalidArgumentException;

class ImportFrontendMediaCommand extends Command
{
    protected $signature = 'media:import-frontend-images
        {--path=docs/front_end : Thư mục chứa các mẫu code.html}
        {--dry-run : Chỉ quét và báo cáo, chưa import}
        {--force : Import lại kể cả khi source_url đã tồn tại}
        {--insecure : Bỏ qua SSL verification khi tải ảnh remote trong môi trường local}';

    protected $description = 'Quét docs/front_end, tải các ảnh từ code.html về và import vào Media Admin library';

    public function handle(FrontendHtmlImageImporter $importer, FrontendHtmlImageScanner $scanner): int
    {
        $path = (string) $this->option('path');
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $insecure = (bool) $this->option('insecure');

        try {
            $images = $scanner->scanDirectory($path);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($images->isEmpty()) {
            $this->warn("Không tìm thấy ảnh remote nào trong [{$path}].");

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Tìm thấy %d ảnh remote duy nhất từ %s.',
            $images->count(),
            $path,
        ));

        $result = $importer->import($path, $dryRun, $force, $insecure);

        $rows = collect($result['items'])
            ->take(12)
            ->map(fn (array $item) => [
                $item['status'],
                $item['name'],
                $item['message'],
                $item['src'],
            ])
            ->all();

        if ($rows !== []) {
            $this->table(['Trạng thái', 'Tên', 'Thông tin', 'URL'], $rows);
        }

        if (count($result['items']) > count($rows)) {
            $this->line('...');
            $this->line(sprintf('Hiển thị %d/%d bản ghi đầu tiên.', count($rows), count($result['items'])));
        }

        $this->newLine();
        $this->line("Scanned: {$result['scanned']}");
        $this->line("Imported: {$result['imported']}");
        $this->line("Skipped: {$result['skipped']}");
        $this->line("Failed: {$result['failed']}");

        if ($dryRun) {
            $this->comment('Chế độ dry-run: chưa có file nào được tải vào Media Admin.');
        }

        if ($insecure) {
            $this->comment('Đã import với chế độ bỏ qua SSL verification cho ảnh remote.');
        }

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
