<?php

namespace Src\Domains\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Src\Domains\Cms\Models\Concerns\RegistersFrontsiteImageConversions;

class SiteSetting extends Model implements HasMedia
{
    use InteractsWithMedia;
    use RegistersFrontsiteImageConversions;

    protected $table = 'site_settings';

    protected $fillable = [
        'site_name',
        'site_tagline',
        'site_description',
        'active_theme',
        'logo_url',
        'favicon_url',
        'og_image_url',
        'company_name',
        'about_summary',
        'tour_terms_title',
        'tour_terms_content',
        'tour_terms_items',
        'address',
        'phone',
        'hotline',
        'primary_email',
        'support_email',
        'sales_email',
        'mail_from_name',
        'mail_from_address',
        'mail_contact_recipient',
        'map_embed_url',
        'facebook_url',
        'youtube_url',
        'tiktok_url',
        'instagram_url',
        'zalo_url',
        'messenger_url',
        'linkedin_url',
        'experience_years',
        'completed_projects_count',
        'team_size',
        'quality_badge_label',
        'copyright_text',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'seo_robots',
        'ga_measurement_id',
        'facebook_pixel_id',
        'after_header_html',
        'end_body_html',
        'structured_data',
    ];

    protected function casts(): array
    {
        return [
            'completed_projects_count' => 'integer',
            'experience_years' => 'integer',
            'structured_data' => 'array',
            'tour_terms_items' => 'array',
            'team_size' => 'integer',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
        $this->addMediaCollection('favicon')->singleFile();
        $this->addMediaCollection('og_image')->singleFile();
        $this->addMediaCollection('library');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->registerFrontsiteImageConversions(['logo', 'og_image']);
    }
}
