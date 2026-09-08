<?php

namespace App\Console\Commands;

use App\Services\Seo\SitemapBuilder;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;

class WarmFrontsiteCacheCommand extends Command
{
    protected $signature = 'frontsite:cache:warm {--limit=0 : Giới hạn số URL cần warm}';

    protected $description = 'Warm cache response cho các URL frontsite canonical trong sitemap.';

    public function handle(SitemapBuilder $sitemapBuilder, HttpKernel $kernel): int
    {
        $urls = collect($sitemapBuilder->build())
            ->pluck('url')
            ->filter()
            ->unique()
            ->values();
        $limit = max(0, (int) $this->option('limit'));

        if ($limit > 0) {
            $urls = $urls->take($limit)->values();
        }

        if ($urls->isEmpty()) {
            $this->warn('Không có URL frontsite nào để warm.');

            return self::SUCCESS;
        }

        $warmed = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar($urls->count());
        $bar->start();

        foreach ($urls as $url) {
            $request = Request::create((string) $url, 'GET');
            $request->headers->set('Accept', 'text/html,application/xhtml+xml');

            $response = $kernel->handle($request);
            $kernel->terminate($request, $response);

            if ($response->getStatusCode() >= 400) {
                $failed++;
            } else {
                $warmed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Đã warm {$warmed} URL frontsite.");

        if ($failed > 0) {
            $this->warn("Có {$failed} URL trả status lỗi trong lúc warm.");
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
