<?php

namespace App\Services\SeoOptimization;

use App\Models\SeoOptimizationEvent;
use App\Models\SeoOptimizationPolicy;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OptimizationPolicyService
{
    public function __construct(private OptimizationAccess $access) {}

    public function current(): ?SeoOptimizationPolicy
    {
        return SeoOptimizationPolicy::query()->where('site_id', config('seo_optimization.site_id'))->first();
    }

    public function save(User $user, string $mode, array $types, ?string $expectedRevision): SeoOptimizationPolicy
    {
        abort_if(request()->attributes->has('seo_optimization_credential'), 403);
        $this->access->authorize($user, 'settings');
        Validator::make(['mode' => $mode, 'types' => $types], [
            'mode' => ['required', Rule::in(['preview', 'always_publish'])],
            'types' => ['required', 'array', 'min:1', 'max:16'],
            'types.*' => ['string', 'distinct', Rule::in(PageRegistryService::PAGE_TYPES)],
        ])->validate();
        if ($mode === 'always_publish') {
            $this->access->authorize($user, 'approve');
            $this->access->authorize($user, 'apply');
        }
        foreach ($types as $type) {
            abort_unless($user->can(OptimizationAccess::PAGE_PERMISSIONS[$type].'.edit'), 403);
        }

        return DB::transaction(function () use ($user, $mode, $types, $expectedRevision) {
            $policy = SeoOptimizationPolicy::query()->firstOrCreate(['site_id' => config('seo_optimization.site_id')], [
                'publish_mode' => 'preview', 'allowed_page_types' => [], 'revision' => (string) Str::uuid(),
            ]);
            $policy = SeoOptimizationPolicy::query()->lockForUpdate()->findOrFail($policy->id);
            abort_if(! $policy->wasRecentlyCreated && $expectedRevision !== null && $expectedRevision !== $policy->revision, 409, 'Cấu hình đã thay đổi; tải lại trang.');
            abort_if($expectedRevision === null && $policy->updated_by !== null, 409, 'Cấu hình đã thay đổi; tải lại trang.');
            $before = $policy->only(['publish_mode', 'allowed_page_types', 'revision']);
            $policy->update(['publish_mode' => $mode, 'allowed_page_types' => array_values($types), 'revision' => (string) Str::uuid(), 'updated_by' => $user->id]);
            SeoOptimizationEvent::query()->create(['actor_id' => $user->id, 'event' => 'automation.policy_saved', 'payload' => [
                'site_id' => $policy->site_id, 'before' => $before, 'after' => $policy->only(['publish_mode', 'allowed_page_types', 'revision']),
            ]]);

            return $policy;
        });
    }
}
