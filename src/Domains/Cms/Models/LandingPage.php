<?php

namespace Src\Domains\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Src\Domains\Cms\Models\Concerns\RegistersFrontsiteImageConversions;

class LandingPage extends Model implements HasMedia
{
    use InteractsWithMedia;
    use RegistersFrontsiteImageConversions;

    public const EDITOR_MODE_BLOCKS = 'blocks';

    public const EDITOR_MODE_HTML = 'html';

    protected $table = 'landing_pages';

    protected $fillable = [
        'page_key',
        'template_key',
        'editor_mode',
        'title',
        'slug',
        'is_active',
        'hero_badge',
        'hero_title',
        'hero_excerpt',
        'intro_title',
        'intro_excerpt',
        'body',
        'cta_title',
        'cta_excerpt',
        'cta_primary_label',
        'cta_primary_url',
        'cta_secondary_label',
        'cta_secondary_url',
        'meta_title',
        'meta_description',
        'og_title',
        'og_description',
        'canonical_url',
        'robots_directive',
        'schema',
        'geo_config',
        'service_detail_config',
        'home_config',
        'visual_config',
        'blocks',
        'estimate_config',
        'faq_items',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'geo_config' => 'array',
            'schema' => 'array',
            'service_detail_config' => 'array',
            'home_config' => 'array',
            'visual_config' => 'array',
            'blocks' => 'array',
            'estimate_config' => 'array',
            'faq_items' => 'array',
        ];
    }

    public function isSystemPage(): bool
    {
        return filled($this->page_key);
    }

    public function isHtmlMode(): bool
    {
        return ($this->editor_mode ?? self::EDITOR_MODE_BLOCKS) === self::EDITOR_MODE_HTML;
    }

    public function registerMediaConversions(?\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $this->registerFrontsiteImageConversions();
    }
}
