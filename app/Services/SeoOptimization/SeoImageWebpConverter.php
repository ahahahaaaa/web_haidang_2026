<?php

namespace App\Services\SeoOptimization;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Spatie\Image\Enums\ImageDriver;
use Spatie\Image\Image;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class SeoImageWebpConverter
{
    public function convert(UploadedFile $source): UploadedFile
    {
        $temporary = tempnam(sys_get_temp_dir(), 'seo-webp-');
        abort_if($temporary === false, 503, 'Không tạo được tệp WebP tạm.');

        try {
            $this->image($source->getPathname())
                ->quality($this->quality())
                ->format('webp')
                ->save($temporary);

            clearstatcache(true, $temporary);
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($temporary);
            if ($mime !== 'image/webp' || ! is_file($temporary) || filesize($temporary) < 1) {
                throw ValidationException::withMessages(['image' => 'Không tạo được ảnh WebP hợp lệ.']);
            }

            return new UploadedFile($temporary, 'seo-normalized.webp', 'image/webp', null, true);
        } catch (Throwable $exception) {
            if (is_file($temporary)) {
                unlink($temporary);
            }
            if ($exception instanceof ValidationException || $exception instanceof HttpExceptionInterface) {
                throw $exception;
            }

            throw ValidationException::withMessages(['image' => 'Không thể chuyển ảnh sang WebP; hãy dùng một ảnh JPEG, PNG hoặc WebP hợp lệ khác.']);
        }
    }

    private function quality(): int
    {
        return max(1, min(100, (int) config('seo_optimization.media_webp_quality', 82)));
    }

    private function image(string $path): Image
    {
        if (function_exists('imagewebp')) {
            return Image::useImageDriver(ImageDriver::Gd)->loadFile($path);
        }
        if (class_exists(\Imagick::class) && \Imagick::queryFormats('WEBP') !== []) {
            return Image::useImageDriver(ImageDriver::Imagick)->loadFile($path);
        }

        abort(503, 'Máy chủ cần PHP GD hoặc Imagick có hỗ trợ WebP để xử lý ảnh SEO.');
    }
}
