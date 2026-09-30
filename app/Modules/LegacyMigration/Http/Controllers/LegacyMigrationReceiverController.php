<?php

namespace App\Modules\LegacyMigration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\LegacyMigration\Exceptions\LegacyMigrationConflict;
use App\Modules\LegacyMigration\Http\Requests\StoreLegacyChunkRequest;
use App\Modules\LegacyMigration\Http\Requests\StoreLegacyRunRequest;
use App\Modules\LegacyMigration\Models\LegacyMigrationRun;
use App\Modules\LegacyMigration\Services\LegacyMigrationReceiver;
use App\Modules\LegacyMigration\Services\LegacyRunCounter;
use Illuminate\Http\JsonResponse;

class LegacyMigrationReceiverController extends Controller
{
    public function storeRun(StoreLegacyRunRequest $request, LegacyMigrationReceiver $receiver): JsonResponse
    {
        try {
            [$run, $created] = $receiver->createRun($request->validated());
        } catch (LegacyMigrationConflict $exception) {
            return $this->conflict($exception);
        }

        return response()->json([
            'message' => $created ? 'Đã khởi tạo phiên chuyển dữ liệu.' : 'Phiên chuyển dữ liệu đã tồn tại.',
            'id' => $run->id,
            'run_id' => $run->source_run_id,
            'status' => $run->status,
        ], $created ? 201 : 200);
    }

    public function storeChunk(
        StoreLegacyChunkRequest $request,
        string $sourceRunUuid,
        int $sequence,
        LegacyMigrationReceiver $receiver,
    ): JsonResponse {
        try {
            [$chunk, $created] = $receiver->receiveChunk(
                $sourceRunUuid,
                $sequence,
                trim((string) $request->header('Idempotency-Key')),
                $request->getContent(),
            );
        } catch (LegacyMigrationConflict $exception) {
            return $this->conflict($exception);
        }

        return response()->json([
            'message' => $created ? 'Đã nhận lô dữ liệu.' : 'Lô dữ liệu đã được nhận trước đó.',
            'status' => 'accepted',
            'sequence' => $chunk->sequence,
            'payload_hash' => $chunk->payload_hash,
        ], $created ? 202 : 200);
    }

    public function finalize(string $sourceRunUuid, LegacyMigrationReceiver $receiver): JsonResponse
    {
        try {
            [$run, $changed] = $receiver->finalize($sourceRunUuid);
        } catch (LegacyMigrationConflict $exception) {
            return $this->conflict($exception);
        }

        $warnings = (array) data_get($run->summary_json, 'finalize_warnings', []);

        return response()->json([
            'message' => $changed
                ? ($warnings === []
                    ? 'Đã finalize phiên; dữ liệu sẵn sàng để chọn URL và cast.'
                    : 'Đã finalize phiên với cảnh báo; chỉ dữ liệu đã staging được đưa vào xử lý.')
                : 'Phiên đã được finalize trước đó.',
            'status' => $run->status,
            'run_id' => $run->source_run_id,
            'warnings' => $warnings,
        ], $changed ? 202 : 200);
    }

    public function show(string $sourceRunUuid, LegacyRunCounter $counter): JsonResponse
    {
        $run = $counter->refresh(
            LegacyMigrationRun::query()->where('source_run_id', $sourceRunUuid)->firstOrFail(),
        );

        return response()->json([
            'message' => 'Đã tải trạng thái chuyển dữ liệu.',
            'data' => [
                'status' => $run->status,
                'total_chunks' => $run->expected_chunks,
                'received_chunks' => $run->received_chunks,
                'total_urls' => $run->expected_urls,
                'staged_urls' => $run->staged_urls,
                'mapped_urls' => $run->mapped_urls,
                'casted_urls' => $run->casted_urls,
                'blocked_urls' => $run->blocked_urls,
                'failed_urls' => $run->failed_urls,
            ],
        ]);
    }

    private function conflict(LegacyMigrationConflict $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 409);
    }
}
