<?php

namespace Src\Domains\Cms\Models\Concerns;

use App\Support\FrontsiteMedia;
use Spatie\Image\Enums\Fit;

trait RegistersFrontsiteImageConversions
{
    /**
     * @param  array<int, string>|null  $collections
     */
    protected function registerFrontsiteImageConversions(?array $collections = null): void
    {
        $collections = array_values(array_filter(
            array_unique(array_map(fn ($collection) => trim((string) $collection), $collections ?? [])),
            fn (string $collection) => $collection !== '',
        ));

        $this->registerFrontsiteImageConversion(FrontsiteMedia::SIZE_SMALL, $collections);
        $this->registerFrontsiteImageConversion(FrontsiteMedia::SIZE_MEDIUM, $collections);
        $this->registerFrontsiteImageConversion(FrontsiteMedia::SIZE_FULL, $collections);
    }

    /**
     * @param  array<int, string>  $collections
     */
    protected function registerFrontsiteImageConversion(string $size, array $collections): void
    {
        [$width, $height] = FrontsiteMedia::bounds($size);

        $conversion = $this
            ->addMediaConversion($size)
            ->keepOriginalImageFormat()
            ->fit(Fit::Max, $width, $height)
            ->optimize()
            ->nonQueued();

        if ($collections !== []) {
            $conversion->performOnCollections(...$collections);
        }
    }
}
