<?php

namespace App\Services\SeoOptimization;

use FilesystemIterator;
use JsonException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use ZipArchive;

class CodexSeoPluginPackage
{
    public const PLUGIN_NAME = 'haidang-travel-seo';

    public const MARKETPLACE_NAME = 'haidang-travel';

    public const VERSION = '1.3.0';

    public const TOKEN_ENV_VAR = 'SEO_HAIDANG_MCP_TOKEN';

    public function available(): bool
    {
        return class_exists(ZipArchive::class) && is_dir($this->sourceDirectory());
    }

    public function build(string $endpoint): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Máy chủ chưa bật PHP ZipArchive nên chưa thể đóng gói plugin.');
        }

        $source = realpath($this->sourceDirectory());
        if ($source === false || ! is_dir($source)) {
            throw new RuntimeException('Không tìm thấy mã nguồn plugin Codex SEO.');
        }
        if (! in_array(parse_url($endpoint, PHP_URL_SCHEME), ['http', 'https'], true)) {
            throw new RuntimeException('Endpoint MCP không hợp lệ.');
        }

        $archivePath = tempnam(sys_get_temp_dir(), 'haidang-seo-plugin-');
        if ($archivePath === false) {
            throw new RuntimeException('Không thể tạo file tạm cho plugin.');
        }

        $zip = new ZipArchive;
        if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($archivePath);
            throw new RuntimeException('Không thể mở gói ZIP plugin.');
        }

        try {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
            );

            foreach ($files as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($source) + 1));
                $contents = file_get_contents($file->getPathname());
                if ($contents === false) {
                    throw new RuntimeException('Không thể đọc file plugin: '.$relativePath);
                }

                if ($relativePath === '.mcp.json') {
                    $contents = $this->mcpConfiguration($endpoint);
                }

                if (! $zip->addFromString('plugins/'.self::PLUGIN_NAME.'/'.$relativePath, $contents)) {
                    throw new RuntimeException('Không thể thêm file vào plugin: '.$relativePath);
                }
            }

            if (! $zip->addFromString('.agents/plugins/marketplace.json', $this->marketplaceManifest())) {
                throw new RuntimeException('Không thể thêm marketplace manifest vào plugin.');
            }
        } catch (\Throwable $exception) {
            $zip->close();
            @unlink($archivePath);
            throw $exception;
        }

        if (! $zip->close()) {
            @unlink($archivePath);
            throw new RuntimeException('Không thể hoàn tất gói ZIP plugin.');
        }

        return $archivePath;
    }

    private function sourceDirectory(): string
    {
        return resource_path('codex-plugins/'.self::PLUGIN_NAME);
    }

    /** @throws JsonException */
    private function mcpConfiguration(string $endpoint): string
    {
        return json_encode([
            'mcpServers' => [
                'seo_haidang' => [
                    'type' => 'http',
                    'url' => $endpoint,
                    'bearer_token_env_var' => self::TOKEN_ENV_VAR,
                ],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL;
    }

    /** @throws JsonException */
    private function marketplaceManifest(): string
    {
        return json_encode([
            'name' => self::MARKETPLACE_NAME,
            'interface' => ['displayName' => 'Hải Đăng Travel'],
            'plugins' => [[
                'name' => self::PLUGIN_NAME,
                'source' => ['source' => 'local', 'path' => './plugins/'.self::PLUGIN_NAME],
                'policy' => ['installation' => 'AVAILABLE', 'authentication' => 'ON_INSTALL'],
                'category' => 'Marketing',
            ]],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL;
    }
}
