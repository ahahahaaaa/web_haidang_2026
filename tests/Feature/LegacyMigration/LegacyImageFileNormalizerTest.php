<?php

namespace Tests\Feature\LegacyMigration;

use App\Modules\LegacyMigration\Services\LegacyImageFileNormalizer;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LegacyImageFileNormalizerTest extends TestCase
{
    #[DataProvider('rasterFormats')]
    public function test_decodable_images_keep_the_original_file(string $extension): void
    {
        config()->set('media-library.image_driver', 'gd');
        $upload = UploadedFile::fake()->image('image.'.$extension, 32, 24);

        $normalized = app(LegacyImageFileNormalizer::class)->normalize($upload, 24_000_000, 10485760);

        $this->assertSame($upload, $normalized);
        $this->assertNotFalse(@imagecreatefromstring(file_get_contents($normalized->getPathname())));
    }

    public static function rasterFormats(): array
    {
        return [['jpg'], ['png'], ['webp']];
    }

    public function test_avif_is_actually_decoded_and_transcoded_to_webp(): void
    {
        if (! function_exists('imageavif') || ! (gd_info()['AVIF Support'] ?? false)) {
            $this->markTestSkipped('GD AVIF support is required for this conversion test.');
        }

        config()->set('media-library.image_driver', 'gd');
        $image = imagecreatetruecolor(32, 24);
        ob_start();
        imageavif($image);
        $binary = ob_get_clean();
        $upload = UploadedFile::fake()->createWithContent('image.avif', $binary);
        $originalHash = hash_file('sha256', $upload->getPathname());
        $normalized = app(LegacyImageFileNormalizer::class)->normalize($upload, 24_000_000, 10485760);

        try {
            $this->assertNotSame($upload->getPathname(), $normalized->getPathname());
            $this->assertSame('image/webp', $normalized->getMimeType());
            $this->assertSame('webp', $normalized->getClientOriginalExtension());
            $this->assertSame([32, 24], array_slice(getimagesize($normalized->getPathname()), 0, 2));
            $this->assertNotFalse(imagecreatefromwebp($normalized->getPathname()));
            $this->assertSame($originalHash, hash_file('sha256', $upload->getPathname()));
        } finally {
            unlink($normalized->getPathname());
        }
    }

    public function test_png_with_a_valid_size_header_but_broken_pixel_data_is_rejected(): void
    {
        config()->set('media-library.image_driver', 'gd');
        $valid = UploadedFile::fake()->image('image.png', 32, 24);
        $upload = UploadedFile::fake()->createWithContent('broken.png', substr(file_get_contents($valid->getPathname()), 0, 33));
        $this->assertSame([32, 24], array_slice(getimagesize($upload->getPathname()), 0, 2));
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Không thể giải mã đầy đủ ảnh PNG');

        app(LegacyImageFileNormalizer::class)->normalize($upload, 24_000_000, 10485760);
    }

    public function test_pixel_limit_is_checked_before_full_decode(): void
    {
        $upload = UploadedFile::fake()->image('image.png', 32, 24);
        $this->expectExceptionMessage('vượt quá giới hạn 100 pixels');

        app(LegacyImageFileNormalizer::class)->normalize($upload, 100, 10485760);
    }

    public function test_javascript_is_not_accepted_as_an_image(): void
    {
        $upload = UploadedFile::fake()->createWithContent('image.png', 'function blocked() { return false; }');
        $this->expectExceptionMessage('không nhận SVG hoặc HTML');

        app(LegacyImageFileNormalizer::class)->normalize($upload, 24_000_000, 10485760);
    }
}
