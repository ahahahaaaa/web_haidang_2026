<?php

namespace App\Console\Commands;

use App\Services\Frontsite\FrontsiteCache;
use Illuminate\Console\Command;

class ClearFrontsiteCacheCommand extends Command
{
    protected $signature = 'frontsite:cache:clear {groups?* : Nhóm cache frontsite cần clear, ví dụ home tours blog}';

    protected $description = 'Clear cache frontsite bằng cách bump version nhóm cache, không flush cache framework/admin.';

    public function handle(FrontsiteCache $cache): int
    {
        $groups = collect((array) $this->argument('groups'))
            ->map(fn (mixed $group): string => trim((string) $group))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($groups === [] || in_array('all', $groups, true)) {
            $cache->forgetAll();
            $this->info('Đã clear toàn bộ cache frontsite.');

            return self::SUCCESS;
        }

        $cache->forgetGroups($groups);
        $this->info('Đã clear cache frontsite cho nhóm: '.implode(', ', $groups).'.');

        return self::SUCCESS;
    }
}
