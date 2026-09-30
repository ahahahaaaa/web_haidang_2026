<?php

namespace App\Modules\LegacyMigration\Services;

use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use RuntimeException;
use Spatie\Image\Image;
use Throwable;

class LegacyImageFileNormalizer
{
    public function normalize(UploadedFile $upload, int $maximumPixels, int $maximumBytes): UploadedFile
    {
        $path = $upload->getPathname();
        clearstatcache(true, $path);

        if (! is_file($path) || filesize($path) < 1 || filesize($path) > $maximumBytes) {
            throw new InvalidArgumentException('Ảnh rỗng hoặc vượt quá dung lượng cho phép.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/avif' => 'avif'];

        if (! isset($extensions[$mime])) {
            throw new InvalidArgumentException('Nguồn tải không trả ảnh JPEG, PNG, WebP hoặc AVIF thật (MIME: '.($mime ?: 'không xác định').'); không nhận SVG hoặc HTML.');
        }

        $dimensions = @getimagesize($path);
        $width = (int) ($dimensions[0] ?? 0);
        $height = (int) ($dimensions[1] ?? 0);

        if ($width < 1 || $height < 1 || $width > intdiv(max(1, $maximumPixels), $height)) {
            throw new InvalidArgumentException('Ảnh không hợp lệ hoặc vượt quá giới hạn '.number_format($maximumPixels).' pixels.');
        }

        $driver = (string) config('media-library.image_driver', 'gd');
        $converted = null;

        try {
            try {
                $image = @Image::useImageDriver($driver)->loadFile($path);
            } catch (Throwable $exception) {
                if ($mime !== 'image/avif' || $driver !== 'gd' || ! extension_loaded('imagick')) {
                    throw $exception;
                }

                $image = @Image::useImageDriver('imagick')->loadFile($path);
            }

            if ($mime !== 'image/avif') {
                return $upload;
            }

            $converted = tempnam(sys_get_temp_dir(), 'legacy-webp-');

            if ($converted === false) {
                throw new RuntimeException('Không tạo được tệp tạm để chuyển ảnh AVIF sang WebP.');
            }

            try {
                $image->format('webp')->save($converted);
            } catch (Throwable $exception) {
                throw new RuntimeException('Không ghi được ảnh WebP sau khi giải mã AVIF.', 0, $exception);
            }
            clearstatcache(true, $converted);

            if (! is_file($converted) || filesize($converted) < 1) {
                throw new RuntimeException('Không ghi được dữ liệu ảnh WebP vào tệp tạm sau khi giải mã AVIF.');
            }

            if ((new \finfo(FILEINFO_MIME_TYPE))->file($converted) !== 'image/webp'
                || filesize($converted) > $maximumBytes
            ) {
                throw new InvalidArgumentException('Ảnh AVIF chuyển sang WebP không hợp lệ hoặc vượt quá dung lượng cho phép.');
            }

            return new UploadedFile($converted, 'legacy-converted-'.hash_file('sha256', $converted).'.webp', 'image/webp', null, true);
        } catch (Throwable $exception) {
            if (is_string($converted) && is_file($converted)) {
                @unlink($converted);
            }

            if ($exception instanceof InvalidArgumentException || $exception instanceof RuntimeException) {
                throw $exception;
            }

            throw new InvalidArgumentException(
                'Không thể giải mã đầy đủ ảnh '.strtoupper($extensions[$mime]).' bằng driver '.$driver.'. Kiểm tra file ảnh và hỗ trợ định dạng trên server.',
                0,
                $exception,
            );
        }
    }
}
