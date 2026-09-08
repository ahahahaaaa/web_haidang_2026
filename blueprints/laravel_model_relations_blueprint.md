# Laravel Model Relations Blueprint

## Core note
There is no first-class `Company` Eloquent model required for the first version if Company data comes from Theme Settings.

Instead, expose Company through a service such as:
- `ThemeSettingsService`
- `CompanyProfileService`

---

## Suggested Eloquent relations

## Service
```php
class Service extends Model
{
    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function projects()
    {
        return $this->hasMany(Project::class, 'primary_service_id');
    }

    public function blogPosts()
    {
        return $this->belongsToMany(BlogPost::class, 'blog_post_service');
    }

    public function seoMeta()
    {
        return $this->morphOne(SeoMeta::class, 'entity');
    }

    public function seoSchemas()
    {
        return $this->morphMany(SeoSchema::class, 'entity');
    }

    public function faqs()
    {
        return $this->morphMany(Faq::class, 'entity');
    }

    public function testimonials()
    {
        return $this->hasMany(Testimonial::class);
    }
}
```

## Project
```php
class Project extends Model
{
    public function primaryService()
    {
        return $this->belongsTo(Service::class, 'primary_service_id');
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'project_service');
    }

    public function blogPosts()
    {
        return $this->belongsToMany(BlogPost::class, 'blog_post_project');
    }

    public function seoMeta()
    {
        return $this->morphOne(SeoMeta::class, 'entity');
    }

    public function seoSchemas()
    {
        return $this->morphMany(SeoSchema::class, 'entity');
    }

    public function faqs()
    {
        return $this->morphMany(Faq::class, 'entity');
    }

    public function testimonials()
    {
        return $this->hasMany(Testimonial::class);
    }
}
```

## BlogPost
```php
class BlogPost extends Model
{
    public function category()
    {
        return $this->belongsTo(BlogCategory::class, 'category_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'blog_post_tag');
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'blog_post_service');
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'blog_post_project');
    }

    public function ctaService()
    {
        return $this->belongsTo(Service::class, 'cta_service_id');
    }

    public function seoMeta()
    {
        return $this->morphOne(SeoMeta::class, 'entity');
    }

    public function seoSchemas()
    {
        return $this->morphMany(SeoSchema::class, 'entity');
    }

    public function faqs()
    {
        return $this->morphMany(Faq::class, 'entity');
    }
}
```

## Faq
```php
class Faq extends Model
{
    public function entity()
    {
        return $this->morphTo();
    }
}
```

## SeoMeta
```php
class SeoMeta extends Model
{
    public function entity()
    {
        return $this->morphTo();
    }
}
```

## SeoSchema
```php
class SeoSchema extends Model
{
    public function entity()
    {
        return $this->morphTo();
    }
}
```

---

## Service-layer relation to Company
Instead of Eloquent relation, use service composition.

Example:
```php
$companyProfile = app(CompanyProfileService::class)->get();
$siteSeo = app(ThemeSettingsService::class)->seoDefaults();
```

Use this data in:
- API resources
- schema builders
- blade / frontend transformers
- contact components
- lead notification jobs

---

## Recommended additional services
- `ThemeSettingsService`
- `CompanyProfileService`
- `SiteSeoDefaultsService`
- `OrganizationSchemaBuilder`
- `ServiceSchemaBuilder`
- `ProjectSchemaBuilder`
- `BlogPostingSchemaBuilder`
