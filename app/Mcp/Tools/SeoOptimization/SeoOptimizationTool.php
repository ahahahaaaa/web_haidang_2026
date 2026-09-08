<?php

namespace App\Mcp\Tools\SeoOptimization;

use App\Models\SeoOptimizationCredential;
use App\Models\SeoOptimizationPage;
use App\Models\SeoOptimizationTask;
use App\Models\User;
use App\Services\SeoOptimization\OptimizationAccess;
use App\Services\SeoOptimization\PageRegistryService;
use Closure;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

abstract class SeoOptimizationTool extends Tool
{
    protected string $ability = 'read';

    public function __construct(protected Request $httpRequest, protected OptimizationAccess $access) {}

    public function shouldRegister(): bool
    {
        $credential = $this->httpRequest->attributes->get('seo_optimization_credential');

        return $credential instanceof SeoOptimizationCredential
            && in_array($this->ability, $credential->abilities ?? [], true);
    }

    protected function credential(): SeoOptimizationCredential
    {
        $credential = $this->httpRequest->attributes->get('seo_optimization_credential');
        if (! $credential instanceof SeoOptimizationCredential || ! $this->shouldRegister()) {
            throw new AuthorizationException('Token không có quyền thực hiện thao tác này.');
        }

        return $credential;
    }

    protected function actor(): User
    {
        $credential = $this->credential();
        $user = $this->httpRequest->user();
        if (! $user instanceof User || (string) $user->id !== (string) $credential->user_id) {
            throw new AuthorizationException('Danh tính kết nối SEO không hợp lệ.');
        }

        $this->access->authorize($user, $this->ability);

        return $user;
    }

    protected function pages(): Builder
    {
        return $this->access->queryFor($this->actor())
            ->whereIn('page_type', $this->credential()->allowed_page_types ?? [])
            ->whereNotIn('classification', ['DRAFT_OR_PRIVATE', 'UNRESOLVED']);
    }

    protected function page(string $id): SeoOptimizationPage
    {
        $page = $this->pages()->findOrFail($id);
        $descriptor = app(PageRegistryService::class)->descriptor($page);
        abort_if(in_array($descriptor['classification'], ['DRAFT_OR_PRIVATE', 'UNRESOLVED'], true), 404);
        $this->access->authorize($this->actor(), $this->ability, $page);

        return $page;
    }

    protected function task(string $id): SeoOptimizationTask
    {
        $task = SeoOptimizationTask::query()
            ->whereIn('page_id', $this->pages()->select('id'))
            ->findOrFail($id);
        $this->page((string) $task->page_id);

        return $task;
    }

    protected function result(Closure $operation): Response|ResponseFactory
    {
        try {
            return Response::structured($operation());
        } catch (ModelNotFoundException) {
            return Response::error('NOT_FOUND: Không tìm thấy mục trong phạm vi được phép.');
        } catch (AuthorizationException) {
            return Response::error('FORBIDDEN: Kết nối không có quyền thực hiện thao tác này.');
        } catch (ValidationException $exception) {
            return Response::error('VALIDATION_FAILED: '.implode(' ', array_merge(...array_values($exception->errors()))));
        } catch (DomainException) {
            return Response::error('SOURCE_OR_POLICY_CONFLICT: Kiểm tra phiên bản nguồn, dữ kiện và trạng thái hiện tại trong CMS trước khi thử lại.');
        } catch (HttpExceptionInterface $exception) {
            return Response::error(match ($exception->getStatusCode()) {
                401, 403 => 'FORBIDDEN: Kết nối không có quyền thực hiện thao tác này.',
                404 => 'NOT_FOUND: Không tìm thấy mục trong phạm vi được phép.',
                409 => 'CONFLICT: Phiên bản nguồn hoặc trạng thái task đã thay đổi. Đọc lại dữ liệu hiện tại.',
                422 => 'VALIDATION_FAILED: Dữ liệu đề xuất không đáp ứng quy tắc an toàn.',
                429 => 'RATE_LIMITED: Đã đạt giới hạn xử lý; thử lại ở lượt chạy sau.',
                default => 'OPERATION_FAILED: Thao tác chưa hoàn tất; kiểm tra trạng thái trong CMS.',
            });
        } catch (\Throwable $exception) {
            report($exception);

            return Response::error('OPERATION_FAILED: Thao tác chưa hoàn tất; kiểm tra trạng thái trong CMS trước khi thử lại với cùng idempotency_key.');
        }
    }

    protected function taskData(SeoOptimizationTask $task): array
    {
        return $task->only(['id', 'page_id', 'status', 'source_version', 'leased_until', 'attempts', 'created_at', 'updated_at']);
    }
}
