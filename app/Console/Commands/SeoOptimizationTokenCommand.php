<?php

namespace App\Console\Commands;

use App\Models\SeoOptimizationCredential;
use App\Models\User;
use App\Services\SeoOptimization\PageRegistryService;
use App\Services\SeoOptimization\SeoOptimizationCredentialService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use Throwable;

class SeoOptimizationTokenCommand extends Command
{
    protected $signature = 'seo-optimize:token {user : ID hoặc email tài khoản dịch vụ} {name : Nhãn token} {--types=blog_post : Loại trang phân cách bằng dấu phẩy} {--days=30 : Hiệu lực 1–90 ngày} {--automation : Cho phép tự chọn bài CMS, xử lý Media và chốt theo publish policy server} {--revoke= : Thu hồi token theo ID, không tạo token mới}';

    protected $description = 'Tạo token MCP đề xuất; tùy chọn automation tuân theo publish policy do người quản trị cấu hình';

    public function handle(SeoOptimizationCredentialService $credentials): int
    {
        $identity = (string) $this->argument('user');
        $user = User::query()->where(is_numeric($identity) ? 'id' : 'email', $identity)->firstOrFail();
        if ($id = $this->option('revoke')) {
            $credentials->revoke(SeoOptimizationCredential::query()->where('user_id', $user->id)->findOrFail($id), $user);
            $this->info('Đã thu hồi token.');

            return self::SUCCESS;
        }
        $types = array_values(array_unique(array_filter(array_map('trim', explode(',', (string) $this->option('types'))))));
        $days = filter_var($this->option('days'), FILTER_VALIDATE_INT);
        if ($types === [] || array_diff($types, PageRegistryService::PAGE_TYPES) || $days === false || $days < 1 || $days > 90) {
            $this->error('Danh sách loại trang hoặc thời hạn token không hợp lệ.');

            return self::FAILURE;
        }
        try {
            $result = $credentials->issue(
                $user,
                (string) $this->argument('name'),
                $types,
                $days,
                (bool) $this->option('automation'),
                $user,
            );
        } catch (ValidationException $exception) {
            $this->error(collect($exception->errors())->flatten()->implode(' '));

            return self::FAILURE;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
        if ($this->option('automation')) {
            $this->warn('Token này có thể kích hoạt publish khi server được cấu hình Luôn publish.');
        }
        $credential = $result['credential'];
        $this->info('Token ID: '.$credential->id.'. Lưu giá trị tiếp theo vào secret store; chỉ hiển thị một lần, không đưa vào chat/log.');
        $this->line($result['token']);

        return self::SUCCESS;
    }
}
