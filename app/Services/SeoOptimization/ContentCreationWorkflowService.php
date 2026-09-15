<?php

namespace App\Services\SeoOptimization;

use App\Models\SeoContentCreationTask;
use App\Models\SeoOptimizationCredential;
use App\Models\SeoOptimizationEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ContentCreationWorkflowService
{
    public function __construct(
        private readonly ContentCreationRegistry $registry,
        private readonly ContentCreationWriter $writer,
        private readonly ContentCreationMediaService $media,
        private readonly ContentCreationQualityService $quality,
        private readonly OptimizationAccess $access,
    ) {}

    /** @return array<string, mixed> */
    public function start(User $user, SeoOptimizationCredential $credential, string $type, array $brief, string $idempotencyKey): array
    {
        $contract = $this->authorize($user, $credential, $type);
        $brief = $this->validateBrief($brief);
        $requestHash = $this->hash([$type, $brief, ContentCreationRegistry::CONTRACT_VERSION]);
        $leaseToken = Str::random(64);

        $task = DB::transaction(function () use ($user, $credential, $type, $brief, $idempotencyKey, $requestHash, $leaseToken): SeoContentCreationTask {
            $existing = SeoContentCreationTask::query()->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();
            if ($existing) {
                abort_unless(
                    (int) $existing->requested_by === (int) $user->id
                    && (string) $existing->credential_id === (string) $credential->id,
                    409,
                    'idempotency_key đã được dùng bởi yêu cầu khác.',
                );
                abort_unless(hash_equals($existing->request_hash, $requestHash), 409, 'idempotency_key đã dùng cho yêu cầu tạo nội dung khác.');
                if ($existing->status === 'drafting') {
                    $existing->forceFill([
                        'lease_token_hash' => hash('sha256', $leaseToken),
                        'leased_until' => now()->addMinutes((int) config('seo_optimization.content_creation_lease_minutes', 60)),
                    ])->save();
                }

                return $existing;
            }

            $task = SeoContentCreationTask::query()->create([
                'requested_by' => $user->id,
                'credential_id' => $credential->id,
                'content_type' => $type,
                'status' => 'drafting',
                'brief' => $brief,
                'idempotency_key' => $idempotencyKey,
                'request_hash' => $requestHash,
                'lease_token_hash' => hash('sha256', $leaseToken),
                'leased_until' => now()->addMinutes((int) config('seo_optimization.content_creation_lease_minutes', 60)),
            ]);
            SeoOptimizationEvent::query()->create([
                'actor_id' => $user->id,
                'event' => 'content_creation.started',
                'payload' => ['task_id' => $task->id, 'content_type' => $type, 'credential_id' => $credential->id],
            ]);

            return $task;
        }, 1);

        return [
            'task' => $this->taskData($task),
            'lease_token' => $task->status === 'drafting' ? $leaseToken : null,
            'upload_url' => $task->status === 'drafting' ? url('/mcp/seo-optimization/content-creation/'.$task->id.'/media') : null,
            'contract' => $contract,
            'instructions' => 'Tạo payload đúng fields; ảnh có sẵn dùng search_cms_content_media. Ảnh mới upload multipart reference,image,alt,prompt,lease_token. Chèn ảnh nội dung bằng marker [[media:reference]], rồi khai báo media_placements. CMS luôn tạo draft/inactive; Codex không được gửi status, canonical, schema, giá, rating hoặc lịch khởi hành.',
        ];
    }

    /** @return array<string, mixed> */
    public function submit(User $user, SeoOptimizationCredential $credential, string $taskId, string $leaseToken, array $payload, array $placements): array
    {
        return DB::transaction(function () use ($user, $credential, $taskId, $leaseToken, $payload, $placements): array {
            $task = SeoContentCreationTask::query()->lockForUpdate()->findOrFail($taskId);
            $contract = $this->authorize($user, $credential, $task->content_type);
            $this->assertLease($task, $user, $credential, $leaseToken);
            $prepared = $this->writer->prepare($task->content_type, $payload);
            $withMedia = $this->media->preparePlacements($task, $prepared, $placements, $contract);
            $contentHash = $this->hash([$withMedia['payload'], $this->serializablePlacements($withMedia['placements'])]);

            if (in_array($task->status, ['completed', 'ready_for_review'], true)) {
                abort_unless($task->content_hash && hash_equals($task->content_hash, $contentHash), 409, 'Task đã nhận payload khác.');

                return $task->result ?? [];
            }

            $quality = $this->quality->assess($withMedia['payload'], $withMedia['placements'], $task->brief ?? []);
            $task->forceFill([
                'payload' => ['fields' => $withMedia['payload'], 'media_placements' => $this->serializablePlacements($withMedia['placements'])],
                'content_hash' => $contentHash,
                'attempts' => $task->attempts + 1,
            ]);

            if (($contract['commit_mode'] ?? null) === 'manual_review') {
                $result = [
                    'task_id' => $task->id,
                    'status' => 'ready_for_review',
                    'content_type' => $task->content_type,
                    'commit_mode' => 'manual_review',
                    'seo_readiness' => $quality,
                    'public_content_changed' => false,
                    'notice' => $contract['notice'],
                ];
                $task->forceFill(['status' => 'ready_for_review', 'result' => $result, 'leased_until' => now()])->save();
                $this->event($task, $user, 'content_creation.ready_for_review', $result);

                return $result;
            }

            $owner = $this->writer->create($task->content_type, $withMedia['payload'], $user);
            $this->media->attach($owner, $task->content_type, $withMedia['placements']);
            $result = $this->resultForOwner($task, $contract, $owner, $quality);
            $task->forceFill(['status' => 'completed', 'result' => $result, 'completed_at' => now(), 'leased_until' => now()])->save();
            $this->event($task, $user, 'content_creation.committed', $result);

            return $result;
        }, 1);
    }

    /** @return array<string, mixed> */
    public function approveManual(SeoContentCreationTask $task, User $user): array
    {
        $this->access->authorize($user, 'approve');

        return DB::transaction(function () use ($task, $user): array {
            $task = SeoContentCreationTask::query()->lockForUpdate()->findOrFail($task->id);
            abort_unless($task->status === 'ready_for_review', 409, 'Yêu cầu không ở trạng thái chờ xác nhận.');
            $contract = $this->registry->get($task->content_type);
            abort_unless($user->can($contract['permission']), 403);
            $payload = $task->payload['fields'] ?? [];
            $placements = $this->hydratePlacements($task->payload['media_placements'] ?? []);
            $owner = $this->writer->create($task->content_type, $payload, $user);
            $this->media->attach($owner, $task->content_type, $placements);
            $quality = $task->result['seo_readiness'] ?? $this->quality->assess($payload, $placements, $task->brief ?? []);
            $result = $this->resultForOwner($task, $contract, $owner, $quality);
            $task->forceFill(['status' => 'completed', 'result' => $result, 'completed_at' => now()])->save();
            $this->event($task, $user, 'content_creation.manual_commit', $result);

            return $result;
        }, 1);
    }

    public function findForCredential(string $id, User $user, SeoOptimizationCredential $credential): SeoContentCreationTask
    {
        $task = SeoContentCreationTask::query()->with(['assets.media', 'requester'])->findOrFail($id);
        abort_unless((string) $task->credential_id === (string) $credential->id && (int) $task->requested_by === (int) $user->id, 403);
        $this->authorize($user, $credential, $task->content_type);

        return $task;
    }

    /** @return array<string, mixed> */
    public function taskData(SeoContentCreationTask $task): array
    {
        return [
            ...$task->only(['id', 'content_type', 'status', 'brief', 'result', 'attempts', 'last_error', 'leased_until', 'completed_at', 'created_at', 'updated_at']),
            'assets' => $task->relationLoaded('assets') ? $task->assets->map(fn ($asset) => $asset->manifest)->values()->all() : [],
        ];
    }

    private function authorize(User $user, SeoOptimizationCredential $credential, string $type): array
    {
        $this->access->authorize($user, 'create');
        abort_unless((int) $credential->user_id === (int) $user->id && in_array('create', $credential->abilities ?? [], true), 403);
        abort_unless(in_array($type, $credential->allowed_page_types ?? [], true), 403, 'Loại nội dung nằm ngoài phạm vi token.');
        $contract = $this->registry->get($type);
        abort_unless($user->can($contract['permission']), 403, 'Tài khoản không có quyền tạo loại nội dung này.');
        abort_unless($user->can('admin.media.index'), 403, 'Tạo nội dung qua Codex cần quyền Media.');

        return $contract;
    }

    private function assertLease(SeoContentCreationTask $task, User $user, SeoOptimizationCredential $credential, string $leaseToken): void
    {
        abort_unless(
            (int) $task->requested_by === (int) $user->id
            && (string) $task->credential_id === (string) $credential->id,
            403,
            'Task không thuộc token tạo nội dung hiện tại.',
        );
        if (in_array($task->status, ['completed', 'ready_for_review'], true)) {
            return;
        }
        abort_unless($task->status === 'drafting' && (string) $task->credential_id === (string) $credential->id
            && $task->leased_until?->isFuture() && $leaseToken !== ''
            && hash_equals((string) $task->lease_token_hash, hash('sha256', $leaseToken)), 409, 'Lease tạo nội dung đã hết hạn hoặc không hợp lệ.');
    }

    private function validateBrief(array $brief): array
    {
        return Validator::make($brief, [
            'request' => ['required', 'string', 'max:10000'],
            'primary_keyword' => ['required', 'string', 'max:255'],
            'search_intent' => ['required', 'string', 'max:120'],
            'secondary_keywords' => ['nullable', 'array', 'max:30'],
            'secondary_keywords.*' => ['string', 'max:255'],
            'entities' => ['nullable', 'array', 'max:30'],
            'entities.*' => ['string', 'max:255'],
            'required_topics' => ['nullable', 'array', 'max:30'],
            'required_topics.*' => ['string', 'max:255'],
            'facts' => ['nullable', 'array', 'max:50'],
            'facts.*' => ['string', 'max:2000'],
            'image_requirements' => ['nullable', 'string', 'max:5000'],
        ])->validate();
    }

    private function resultForOwner(SeoContentCreationTask $task, array $contract, Model $owner, array $quality): array
    {
        $editorUrl = route($contract['editor_route'], [$owner]);

        return [
            'task_id' => $task->id,
            'status' => 'completed',
            'content_type' => $task->content_type,
            'owner_type' => $owner->getMorphClass(),
            'owner_id' => (string) $owner->getKey(),
            'editor_url' => $editorUrl,
            'publish_state' => $task->content_type === 'landing' ? 'inactive' : (($contract['commit_mode'] ?? null) === 'manual_review' ? 'taxonomy_created' : 'draft'),
            'seo_readiness' => $quality,
            'public_content_changed' => ($contract['commit_mode'] ?? null) === 'manual_review',
            'notice' => ($contract['commit_mode'] ?? null) === 'manual_review'
                ? 'Taxonomy không có lifecycle draft và đã được tạo sau xác nhận thủ công.'
                : 'CMS đã tạo bản nháp/inactive. Chưa publish và chưa đưa URL vào sitemap.',
        ];
    }

    private function event(SeoContentCreationTask $task, User $user, string $event, array $payload): void
    {
        SeoOptimizationEvent::query()->create([
            'actor_id' => $user->id,
            'event' => $event,
            'payload' => ['task_id' => $task->id, 'content_type' => $task->content_type, ...$payload],
        ]);
    }

    private function serializablePlacements(array $placements): array
    {
        return collect($placements)->map(fn (array $placement) => collect($placement)->except('media')->all())->values()->all();
    }

    private function hydratePlacements(array $placements): array
    {
        $media = Media::query()
            ->whereIn('id', collect($placements)->pluck('media_id')->all())->get()->keyBy('id');

        return collect($placements)->map(function (array $placement) use ($media): array {
            $placement['media'] = $media->get((int) $placement['media_id']);
            abort_unless($placement['media'], 409, 'Media của yêu cầu không còn tồn tại.');

            return $placement;
        })->all();
    }

    private function hash(array $value): string
    {
        return hash('sha256', json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }
}
