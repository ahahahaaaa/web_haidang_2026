<?php

namespace App\Console\Commands\Cms;

use App\Jobs\Cms\RunBlogAutomationJob;
use App\Models\User;
use App\Services\Cms\BlogAutomationRunner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use JsonException;

class RunBlogAutomationCommand extends Command
{
    protected $signature = 'blog:automation:run
        {configPath : Đường dẫn file JSON config}
        {--dispatch : Chỉ đẩy job vào queue}
        {--user-email= : Email người dùng để lấy author mặc định khi payload không truyền author_name}';

    protected $description = 'Chạy hoặc dispatch luồng crawl và biên tập blog từ các link tham chiếu';

    public function handle(BlogAutomationRunner $runner): int
    {
        try {
            $payload = $this->loadPayload((string) $this->argument('configPath'));
        } catch (\RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $actor = $this->resolveActor((string) $this->option('user-email'));

        if ((bool) $this->option('dispatch')) {
            RunBlogAutomationJob::dispatch($payload, $actor?->getKey())
                ->onQueue(config('blog_automation.queue', 'seo'));

            $this->info('Đã dispatch job blog automation vào queue.');

            return self::SUCCESS;
        }

        try {
            $post = $runner->run($payload, $actor);
        } catch (\Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->table(['ID', 'Tiêu đề', 'Slug', 'Trạng thái'], [[
            $post->getKey(),
            $post->title,
            $post->slug,
            $post->status,
        ]]);

        $this->line('URL công khai: '.route('blog.show', $post));

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    protected function loadPayload(string $path): array
    {
        $resolvedPath = $this->resolvePath($path);

        if (! File::exists($resolvedPath)) {
            throw new \RuntimeException("Không tìm thấy file config [{$resolvedPath}].");
        }

        try {
            $decoded = json_decode((string) File::get($resolvedPath), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new \RuntimeException('File config blog automation không phải JSON hợp lệ.', previous: $exception);
        }

        if (! is_array($decoded)) {
            throw new \RuntimeException('Payload blog automation phải là một object JSON.');
        }

        return $decoded;
    }

    protected function resolvePath(string $path): string
    {
        if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1 || str_starts_with($path, '/') || str_starts_with($path, '\\')) {
            return $path;
        }

        return base_path($path);
    }

    protected function resolveActor(string $email): ?User
    {
        $email = trim($email);

        if ($email === '') {
            return null;
        }

        return User::query()->where('email', $email)->first();
    }
}
