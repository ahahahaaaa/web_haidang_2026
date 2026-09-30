<?php

namespace App\Modules\LegacyMigration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\LegacyMigration\Models\LegacyMigrationRedirect;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportLegacyRedirectsController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $stream = fopen('php://output', 'wb');
            fputcsv($stream, ['source_path', 'target_path', 'status_code']);

            LegacyMigrationRedirect::query()->where('is_active', true)->orderBy('id')->lazyById()
                ->each(fn (LegacyMigrationRedirect $redirect) => fputcsv($stream, [
                    $redirect->source_path,
                    $redirect->target_path,
                    $redirect->status_code,
                ]));

            fclose($stream);
        }, 'legacy-migration-redirects-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
