<?php

namespace App\Modules\LegacyMigration\Services;

use App\Models\User;
use App\Modules\LegacyMigration\Models\LegacyMigrationRun;
use App\Modules\LegacyMigration\Models\LegacyStagedUrl;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class LegacyPreservedUrlRedirector
{
    public function __construct(private LegacyContentCaster $caster) {}

    public function count(LegacyMigrationRun $run): int
    {
        return $this->eligibleQuery($run)->count();
    }

    /** @return array{converted: int, skipped: int, failed: int, errors: array<int, string>} */
    public function convert(LegacyMigrationRun $run, User $actor): array
    {
        $result = ['converted' => 0, 'skipped' => 0, 'failed' => 0, 'errors' => []];

        $this->eligibleQuery($run)
            ->select('legacy_staged_urls.*')
            ->chunkById(100, function ($urls) use ($actor, &$result): void {
                foreach ($urls as $url) {
                    try {
                        $this->caster->convertPreservedUrlToRedirect($url, $actor);
                        $result['converted']++;
                    } catch (\InvalidArgumentException $exception) {
                        $result['skipped']++;
                        $this->rememberError($result['errors'], $url, $exception);
                    } catch (Throwable $exception) {
                        report($exception);
                        $result['failed']++;
                        $this->rememberError($result['errors'], $url, $exception);
                    }
                }
            });

        return $result;
    }

    private function eligibleQuery(LegacyMigrationRun $run): Builder
    {
        return LegacyStagedUrl::query()
            ->where('run_id', $run->id)
            ->where('status', 'casted')
            ->where('mapping_mode', 'cast_preserve_url')
            ->whereNotNull('target_type')
            ->where('target_type', '<>', 'system_route')
            ->whereNotNull('target_id')
            ->whereNotNull('target_path');
    }

    /** @param array<int, string> $errors */
    private function rememberError(array &$errors, LegacyStagedUrl $url, Throwable $exception): void
    {
        if (count($errors) >= 5) {
            return;
        }

        $errors[] = sprintf('%s: %s', $url->normalized_path, $exception->getMessage());
    }
}
