<?php

namespace App\Services\SeoOptimization;

use App\Models\SeoOptimizationCredential;
use App\Models\SeoOptimizationEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SeoOptimizationCredentialService
{
    public function __construct(private readonly OptimizationAccess $access) {}

    /**
     * @param  array<int, string>  $pageTypes
     * @return array{credential: SeoOptimizationCredential, token: string}
     */
    public function issue(User $subject, string $name, array $pageTypes, int $days, bool $automation, ?User $actor = null): array
    {
        if (! $subject->is_active) {
            throw ValidationException::withMessages([
                'tokenUserId' => 'Tài khoản nhận token đang bị khóa.',
            ]);
        }
        foreach (['index', 'audit', 'propose'] as $action) {
            if (! $subject->can('admin.seo-optimization.'.$action)) {
                throw ValidationException::withMessages([
                    'tokenUserId' => 'Tài khoản chưa có đủ quyền SEO để dùng MCP.',
                ]);
            }
        }

        $this->access->authorize($subject, 'index');
        $this->access->authorize($subject, 'audit');
        $this->access->authorize($subject, 'propose');

        if ($subject->email_verified_at === null) {
            throw ValidationException::withMessages([
                'tokenUserId' => 'Tài khoản nhận token phải có email_verified_at để MCP xác thực.',
            ]);
        }

        $pageTypes = array_values(array_unique(array_filter(array_map('trim', $pageTypes))));
        if ($pageTypes === [] || array_diff($pageTypes, PageRegistryService::PAGE_TYPES)) {
            throw ValidationException::withMessages([
                'tokenAllowedTypes' => 'Danh sách loại trang không hợp lệ.',
            ]);
        }
        if ($days < 1 || $days > 90) {
            throw ValidationException::withMessages([
                'tokenDays' => 'Thời hạn token phải từ 1 đến 90 ngày.',
            ]);
        }

        foreach ($pageTypes as $pageType) {
            $permission = OptimizationAccess::PAGE_PERMISSIONS[$pageType] ?? null;
            if (! $permission || ! $subject->can($permission.'.index') || ! $subject->can($permission.'.edit')) {
                throw ValidationException::withMessages([
                    'tokenAllowedTypes' => 'Tài khoản chưa có đủ quyền xem và sửa loại trang '.$pageType.'.',
                ]);
            }
        }

        $abilities = ['read', 'audit', 'propose'];
        if ($automation) {
            if (! $subject->can('admin.media.index')) {
                throw ValidationException::withMessages([
                    'tokenAutomation' => 'Token tự động cần tài khoản có quyền Media.',
                ]);
            }
            $abilities[] = 'automate';
        }

        $name = trim($name);
        if ($name === '') {
            throw ValidationException::withMessages(['tokenName' => 'Tên token là bắt buộc.']);
        }

        $token = Str::random(64);

        $credential = DB::transaction(function () use ($subject, $actor, $name, $pageTypes, $days, $abilities, $token): SeoOptimizationCredential {
            $credential = SeoOptimizationCredential::query()->create([
                'user_id' => $subject->id,
                'name' => Str::limit($name, 150, ''),
                'token_hash' => hash('sha256', $token),
                'abilities' => $abilities,
                'allowed_page_types' => $pageTypes,
                'expires_at' => now()->addDays($days),
            ]);

            SeoOptimizationEvent::query()->create([
                'actor_id' => $actor?->id,
                'event' => 'credential.created',
                'payload' => [
                    'credential_id' => $credential->id,
                    'subject_user_id' => $subject->id,
                    'name' => $credential->name,
                    'abilities' => $abilities,
                    'allowed_page_types' => $pageTypes,
                    'expires_at' => $credential->expires_at?->toIso8601String(),
                ],
            ]);

            return $credential;
        });

        return ['credential' => $credential, 'token' => $token];
    }

    public function revoke(SeoOptimizationCredential $credential, ?User $actor = null): void
    {
        if ($credential->revoked_at !== null) {
            return;
        }

        DB::transaction(function () use ($credential, $actor): void {
            $credential->forceFill(['revoked_at' => now()])->save();
            SeoOptimizationEvent::query()->create([
                'actor_id' => $actor?->id,
                'event' => 'credential.revoked',
                'payload' => [
                    'credential_id' => $credential->id,
                    'subject_user_id' => $credential->user_id,
                    'name' => $credential->name,
                ],
            ]);
        });
    }
}
