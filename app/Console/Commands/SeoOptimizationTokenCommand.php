<?php

namespace App\Console\Commands;

use App\Models\SeoOptimizationCredential;
use App\Models\User;
use App\Services\SeoOptimization\OptimizationAccess;
use App\Services\SeoOptimization\PageRegistryService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SeoOptimizationTokenCommand extends Command
{
    protected $signature = 'seo-optimize:token {user : ID hoặc email tài khoản dịch vụ} {name : Nhãn token} {--types=blog_post : Loại trang phân cách bằng dấu phẩy} {--days=30 : Hiệu lực 1–90 ngày} {--automation : Cho phép nhận Sheet, Media và báo hoàn tất theo publish policy server} {--revoke= : Thu hồi token theo ID, không tạo token mới}';

    protected $description = 'Tạo token MCP đề xuất; tùy chọn automation tuân theo publish policy do người quản trị cấu hình';

    public function handle(OptimizationAccess $access): int
    {
        $identity = (string) $this->argument('user');
        $user = User::query()->where(is_numeric($identity) ? 'id' : 'email', $identity)->firstOrFail();
        if ($id = $this->option('revoke')) {
            SeoOptimizationCredential::query()->where('user_id', $user->id)->findOrFail($id)->update(['revoked_at' => now()]);
            $this->info('Đã thu hồi token.');

            return self::SUCCESS;
        }
        $access->authorize($user, 'index');
        $access->authorize($user, 'audit');
        $access->authorize($user, 'propose');
        $types = array_values(array_unique(array_filter(array_map('trim', explode(',', (string) $this->option('types'))))));
        $days = filter_var($this->option('days'), FILTER_VALIDATE_INT);
        if ($types === [] || array_diff($types, PageRegistryService::PAGE_TYPES) || $days === false || $days < 1 || $days > 90) {
            $this->error('Danh sách loại trang hoặc thời hạn token không hợp lệ.');

            return self::FAILURE;
        }
        foreach ($types as $type) {
            if (! $user->can(OptimizationAccess::PAGE_PERMISSIONS[$type].'.edit') || ! $user->can(OptimizationAccess::PAGE_PERMISSIONS[$type].'.index')) {
                $this->error('Tài khoản chưa được cấp quyền nội dung cho loại trang '.$type.'.');

                return self::FAILURE;
            }
        }
        $token = Str::random(64);
        $abilities = ['read', 'audit', 'propose'];
        if ($this->option('automation')) {
            if (! $user->can('admin.media.index')) {
                $this->error('Token tự động cần tài khoản có quyền Media.');

                return self::FAILURE;
            }
            $abilities[] = 'automate';
            $this->warn('Token này có thể kích hoạt publish khi server được cấu hình Luôn publish.');
        }
        $credential = SeoOptimizationCredential::query()->create(['user_id' => $user->id, 'name' => Str::limit($this->argument('name'), 150, ''), 'token_hash' => hash('sha256', $token), 'abilities' => $abilities, 'allowed_page_types' => $types, 'expires_at' => now()->addDays($days)]);
        $this->info('Token ID: '.$credential->id.'. Lưu giá trị tiếp theo vào secret store; chỉ hiển thị một lần, không đưa vào chat/log.');
        $this->line($token);

        return self::SUCCESS;
    }
}
