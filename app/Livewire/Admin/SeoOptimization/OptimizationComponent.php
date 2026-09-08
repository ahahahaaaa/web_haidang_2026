<?php

namespace App\Livewire\Admin\SeoOptimization;

use App\Livewire\Admin\Cms\Concerns\AuthorizesAdminPermissions;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

abstract class OptimizationComponent extends Component
{
    use AuthorizesAdminPermissions;

    protected function actor(): User
    {
        $user = auth()->user();
        abort_unless($user instanceof User && $user->is_active, 403);
        $this->authorizeAdminPermission('access admin panel');

        return $user;
    }

    protected function perform(callable $operation, string $message): void
    {
        $this->resetErrorBag();

        try {
            $operation();
            session()->flash('status', $message);
        } catch (ValidationException|AuthorizationException|HttpExceptionInterface $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('workflow', 'Chưa thể hoàn tất thao tác. Vui lòng kiểm tra trạng thái rồi thử lại; thông tin lỗi đã được ghi nhận.');
        }
    }

    public function statusLabel(?string $status): string
    {
        return [
            'INDEXABLE' => 'Trang SEO', 'NOINDEX_INTENTIONAL' => 'Chủ định không index',
            'EXPECTED_INDEXABLE_ERROR' => 'Cần kiểm tra public', 'DRAFT_OR_PRIVATE' => 'Bản nháp / riêng tư', 'UNRESOLVED' => 'Chưa xác định nguồn',
            'pending' => 'Đang chờ', 'queued' => 'Đang chờ', 'leased' => 'Codex đang xử lý',
            'running' => 'Đang xử lý', 'completed' => 'Hoàn tất', 'succeeded' => 'Hoàn tất',
            'failed' => 'Lỗi', 'cancelled' => 'Đã hủy', 'expired' => 'Hết hạn',
            'draft' => 'Bản nháp', 'pending_review' => 'Chờ duyệt', 'in_review' => 'Chờ duyệt', 'proposed' => 'Đã tạo đề xuất',
            'approved' => 'Đã duyệt', 'rejected' => 'Từ chối', 'applied' => 'Đã áp dụng',
            'rolled_back' => 'Đã hoàn tác', 'stale' => 'Nguồn đã thay đổi', 'verify_failed' => 'Tái kiểm tra chưa đạt',
            'need_data' => 'Cần bổ sung dữ liệu', 'NEED_DATA' => 'Cần bổ sung dữ liệu',
            'PASS' => 'Đạt', 'IMPROVE' => 'Cần cải thiện', 'WEAK' => 'Yếu', 'FAIL' => 'Chưa đạt',
            'pass' => 'Đạt', 'complete' => 'Hoàn tất', 'partial' => 'Chưa đánh giá đủ',
            'blocked' => 'Chưa đủ điều kiện', 'unmapped' => 'Chưa có brief từ khóa',
            'indexable' => 'Trang SEO', 'noindex' => 'Không index', 'excluded' => 'Ngoài phạm vi',
            'redirect' => 'Chuyển hướng', 'private' => 'Riêng tư', 'missing' => 'Không còn tồn tại',
        ][$status ?? ''] ?? ($status ?: 'Chưa có');
    }

    public function pageTypeLabel(string $type): string
    {
        return [
            'service_index' => 'Danh sách dịch vụ', 'blog_index' => 'Danh sách blog', 'landing' => 'Landing page',
            'home' => 'Trang chủ', 'homepage' => 'Trang chủ', 'about' => 'Giới thiệu',
            'contact' => 'Liên hệ', 'tour' => 'Tour', 'tour_detail' => 'Tour',
            'tour_category' => 'Chủ đề tour', 'destination' => 'Điểm đến', 'country' => 'Quốc gia',
            'region' => 'Vùng miền', 'service' => 'Dịch vụ', 'service_detail' => 'Dịch vụ',
            'service_category' => 'Danh mục dịch vụ', 'service_listing' => 'Danh sách dịch vụ',
            'services' => 'Danh sách dịch vụ', 'blog' => 'Bài viết', 'blog_post' => 'Bài viết',
            'blog_category' => 'Danh mục blog', 'blog_listing' => 'Danh sách blog',
            'landing_page' => 'Landing page', 'custom_landing' => 'Landing page',
            'tour_scope' => 'Landing tour', 'tour_landing' => 'Landing tour',
        ][$type] ?? $type;
    }
}
