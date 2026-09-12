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
            'failed' => 'Lỗi', 'cancelled' => 'Đã hủy', 'expired' => 'Hết hạn', 'skipped' => 'Bỏ qua do điểm cao',
            'draft' => 'Bản nháp', 'pending_review' => 'Chờ duyệt', 'in_review' => 'Chờ duyệt', 'proposed' => 'Đã tạo đề xuất',
            'approved' => 'Đã duyệt', 'rejected' => 'Từ chối', 'applied' => 'Đã áp dụng',
            'rolled_back' => 'Đã hoàn tác', 'stale' => 'Nguồn đã thay đổi', 'verify_failed' => 'Tái kiểm tra chưa đạt',
            'need_data' => 'Cần bổ sung dữ liệu', 'NEED_DATA' => 'Cần bổ sung dữ liệu',
            'PASS' => 'Đạt', 'IMPROVE' => 'Cần cải thiện', 'WEAK' => 'Yếu', 'FAIL' => 'Chưa đạt',
            'ACTION_REQUIRED' => 'Cần xử lý',
            'pass' => 'Đạt', 'improve' => 'Cần cải thiện', 'weak' => 'Yếu', 'fail' => 'Chưa đạt',
            'complete' => 'Hoàn tất', 'partial' => 'Chưa đánh giá đủ',
            'blocked' => 'Chưa đủ điều kiện', 'unmapped' => 'Chưa có brief từ khóa',
            'indexable' => 'Trang SEO', 'noindex' => 'Không index', 'excluded' => 'Ngoài phạm vi',
            'redirect' => 'Chuyển hướng', 'private' => 'Riêng tư', 'missing' => 'Không còn tồn tại',
        ][$status ?? ''] ?? ($status ?: 'Chưa có');
    }

    public function auditScoreLabel(?float $score): string
    {
        if ($score === null) {
            return 'Chưa thể tính tổng';
        }

        return ($score === floor($score)
            ? number_format($score, 0, '.', '')
            : number_format($score, 1, '.', '')).'/100';
    }

    public function auditGradeStatusLabel(?string $grade, ?string $status): string
    {
        return ($grade ? 'Cấp '.$grade : 'Chưa xếp hạng').' · '.$this->statusLabel($status);
    }

    public function auditCoverageLabel(array $report): string
    {
        $assessed = (int) data_get($report, 'assessed_dimensions', 0);
        $total = (int) data_get($report, 'total_dimensions', 0);
        if ($total < 1) {
            return 'Chưa xác định được phạm vi đánh giá.';
        }

        $coverage = (float) data_get($report, 'assessment_coverage', $assessed / $total * 100);
        $missing = collect(data_get($report, 'missing_dimensions', []))
            ->pluck('label')
            ->filter()
            ->values();
        if ($missing->isEmpty()) {
            $missing = collect(data_get($report, 'dimensions', []))
                ->filter(fn ($dimension): bool => is_array($dimension) && ($dimension['score'] ?? null) === null)
                ->map(fn (array $dimension): string => $this->auditDimensionLabel((string) ($dimension['dimension'] ?? '')))
                ->filter()
                ->values();
        }

        $label = sprintf(
            'Đã đánh giá %d/%d tiêu chí (%s%%)',
            $assessed,
            $total,
            number_format($coverage, 1, ',', '.'),
        );

        return $missing->isEmpty() ? $label : $label.' · Cần bổ sung: '.$missing->implode(', ');
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

    public function auditDimensionLabel(string $dimension): string
    {
        return [
            'crawl' => 'Khả năng thu thập dữ liệu',
            'keyword_ownership' => 'Quyền sở hữu từ khóa',
            'intent' => 'Ý định tìm kiếm',
            'metadata' => 'Metadata',
            'people_first' => 'Nội dung hữu ích',
            'topic' => 'Chủ đề bắt buộc',
            'entity' => 'Thực thể cần đề cập',
            'structure' => 'Cấu trúc nội dung',
            'links' => 'Liên kết nội bộ',
            'media' => 'Hình ảnh và alt',
            'schema' => 'Dữ liệu có cấu trúc',
            'trust' => 'Độ tin cậy',
        ][$dimension] ?? $dimension;
    }
}
