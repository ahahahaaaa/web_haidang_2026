<?php

namespace App\Http\Controllers\Admin\SeoOptimization;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SeoOptimization\CodexSeoPluginPackage;
use App\Services\SeoOptimization\OptimizationAccess;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DownloadCodexPluginController extends Controller
{
    public function __invoke(Request $request, OptimizationAccess $access, CodexSeoPluginPackage $package): BinaryFileResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $access->authorize($user, 'settings');

        $archive = $package->build(url('/mcp/seo-optimization'));

        return response()->download(
            $archive,
            'haidang-travel-seo-codex-v'.CodexSeoPluginPackage::VERSION.'.zip',
            ['Content-Type' => 'application/zip', 'Cache-Control' => 'no-store, private'],
        )->deleteFileAfterSend(true);
    }
}
