<?php

namespace App\Services\Admin;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;

class FrontendHtmlImageScanner
{
    /**
     * @return \Illuminate\Support\Collection<int, array{
     *     alt: string,
     *     name: string,
     *     occurrences: int,
     *     source_file: string,
     *     source_files: array<int, string>,
     *     source_index: int,
     *     src: string
     * }>
     */
    public function scanDirectory(string $path): Collection
    {
        $directory = $this->resolveDirectory($path);

        if (! is_dir($directory)) {
            throw new InvalidArgumentException("Không tìm thấy thư mục [{$path}] để quét ảnh.");
        }

        return collect(File::allFiles($directory))
            ->filter(fn (\SplFileInfo $file) => $file->getFilename() === 'code.html')
            ->sortBy(fn (\SplFileInfo $file) => $file->getRealPath())
            ->flatMap(fn (\SplFileInfo $file) => $this->scanFile($file->getRealPath()))
            ->groupBy('src')
            ->map(fn (Collection $items) => $this->collapseGroup($items))
            ->values();
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{
     *     alt: string,
     *     name: string,
     *     source_file: string,
     *     source_index: int,
     *     src: string
     * }>
     */
    public function scanFile(string $path): Collection
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $html = File::get($path);
        $wrapped = '<!DOCTYPE html><html><body>'.$html.'</body></html>';

        $document->loadHTML('<?xml encoding="UTF-8">'.$wrapped, LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($document);
        $images = $xpath->query('//img');

        if ($images === false) {
            return collect();
        }

        return collect(iterator_to_array($images))
            ->filter(fn (mixed $node) => $node instanceof DOMElement)
            ->map(fn (DOMElement $image, int $index) => $this->buildImagePayload($image, $path, $index + 1))
            ->filter()
            ->values();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array{
     *     alt: string,
     *     name: string,
     *     source_file: string,
     *     source_index: int,
     *     src: string
     * }>  $items
     * @return array{
     *     alt: string,
     *     name: string,
     *     occurrences: int,
     *     source_file: string,
     *     source_files: array<int, string>,
     *     source_index: int,
     *     src: string
     * }
     */
    protected function collapseGroup(Collection $items): array
    {
        /** @var array{alt: string, name: string, source_file: string, source_index: int, src: string} $first */
        $first = $items->first();

        $alt = $items->pluck('alt')->first(fn (string $value) => filled($value)) ?: $first['alt'];
        $name = $items->pluck('name')->first(fn (string $value) => filled($value)) ?: $first['name'];

        return [
            'alt' => $alt,
            'name' => $name,
            'occurrences' => $items->count(),
            'source_file' => $first['source_file'],
            'source_files' => $items->pluck('source_file')->unique()->values()->all(),
            'source_index' => $first['source_index'],
            'src' => $first['src'],
        ];
    }

    /**
     * @return array{
     *     alt: string,
     *     name: string,
     *     source_file: string,
     *     source_index: int,
     *     src: string
     * }|null
     */
    protected function buildImagePayload(DOMElement $image, string $path, int $index): ?array
    {
        $src = trim($image->getAttribute('src'));

        if (! Str::startsWith($src, ['http://', 'https://'])) {
            return null;
        }

        $section = Str::headline(basename(dirname($path)));
        $alt = trim($image->getAttribute('alt'));
        $alt = $alt !== '' ? $alt : trim($image->getAttribute('data-alt'));
        $fallbackName = "{$section} image {$index}";
        $name = Str::limit($alt !== '' ? $alt : $fallbackName, 255, '');

        return [
            'alt' => Str::limit($alt !== '' ? $alt : $name, 255, ''),
            'name' => $name,
            'source_file' => $this->relativePath($path),
            'source_index' => $index,
            'src' => $src,
        ];
    }

    protected function relativePath(string $path): string
    {
        $base = base_path().DIRECTORY_SEPARATOR;
        $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        if (Str::startsWith($normalizedPath, $base)) {
            return Str::after($normalizedPath, $base);
        }

        return $normalizedPath;
    }

    protected function resolveDirectory(string $path): string
    {
        if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1 || Str::startsWith($path, ['/', '\\'])) {
            return $path;
        }

        return base_path($path);
    }
}
