<?php

namespace App\Http\Middleware;

use App\Services\Frontsite\PublicUrlTargetRenderer;
use App\Support\FrontsiteUrls;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Src\Domains\Cms\Models\PublicUrlMapping;
use Symfony\Component\HttpFoundation\Response;

class ResolvePublicUrlMapping
{
    public function __construct(private PublicUrlTargetRenderer $renderer) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('public_url_mappings.enabled')
            || ! $request->isMethodSafe()
            || $request->is('api*', 'admin*', 'livewire*', 'storage*', 'build*')
            || ! Schema::hasTable('public_url_mappings')
        ) {
            return $next($request);
        }

        $path = FrontsiteUrls::canonicalPath($request->getPathInfo());
        $mapping = PublicUrlMapping::query()
            ->where('source_hash', hash('sha256', $path))
            ->where('source_path', $path)
            ->where('is_active', true)
            ->first();

        if (! $mapping) {
            return $next($request);
        }

        if ($mapping->mode === PublicUrlMapping::MODE_REDIRECT && $mapping->source_path !== $mapping->target_path) {
            return redirect()->to($mapping->target_path, $mapping->status_code)
                ->withHeaders([
                    'Cache-Control' => 'public, max-age='.max(0, (int) config('public_url_mappings.cache_seconds', 300)),
                    'X-Public-URL-Mapping' => 'redirect',
                ]);
        }

        if ($mapping->mode === PublicUrlMapping::MODE_RENDER) {
            return $this->renderer->render($request, $mapping) ?? $next($request);
        }

        return $next($request);
    }
}
