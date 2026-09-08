<?php

namespace App\Jobs\Cms;

use App\Models\User;
use App\Services\Cms\BlogAutomationRunner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class RunBlogAutomationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public array $payload,
        public ?int $actorId = null,
    ) {
        $this->onQueue(config('blog_automation.queue', 'seo'));
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(BlogAutomationRunner $runner): void
    {
        $actor = $this->actorId ? User::query()->find($this->actorId) : null;

        $runner->run($this->payload, $actor);
    }

    public function failed(?Throwable $exception): void
    {
        if ($exception) {
            report($exception);
        }
    }
}
