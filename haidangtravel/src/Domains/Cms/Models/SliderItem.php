<?php

namespace Src\Domains\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Src\Domains\Cms\Models\Concerns\RegistersFrontsiteImageConversions;

class SliderItem extends Model implements HasMedia
{
    use InteractsWithMedia;
    use RegistersFrontsiteImageConversions;

    protected $table = 'slider_items';

    protected $fillable = [
        'slider_id',
        'title',
        'subtitle',
        'description',
        'image_alt',
        'image_link',
        'effect',
        'cta_label',
        'cta_url',
        'primary_label',
        'primary_url',
        'secondary_label',
        'secondary_url',
        'video_url',
        'show_overlay',
        'show_inner_media',
        'order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'order' => 'integer',
            'show_inner_media' => 'boolean',
            'show_overlay' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
        $this->addMediaCollection('mobile_image')->singleFile();
        $this->addMediaCollection('inner_image')->singleFile();
    }

    public function registerMediaConversions(?\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $this->registerFrontsiteImageConversions();
    }

    public function slider(): BelongsTo
    {
        return $this->belongsTo(Slider::class);
    }
}
