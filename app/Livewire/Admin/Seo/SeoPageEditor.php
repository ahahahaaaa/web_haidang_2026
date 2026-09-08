<?php

namespace App\Livewire\Admin\Seo;

use App\Livewire\Admin\Cms\Concerns\InteractsWithEditorContent;
use Livewire\Component;
use Src\Domains\Seo\Enums\SeoPageStatus;
use Src\Domains\Seo\Jobs\GenerateSeoDraftJob;
use Src\Domains\Seo\Jobs\PublishSeoPageJob;
use Src\Domains\Seo\Jobs\ValidateSeoPageJob;
use Src\Domains\Seo\Models\SeoPage;
use Src\Domains\Seo\Support\SeoSlugGenerator;

class SeoPageEditor extends Component
{
    use InteractsWithEditorContent;

    public SeoPage $page;

    protected function rules(): array
    {
        return [
            'page.title' => ['nullable', 'string', 'max:255'],
            'page.slug' => ['nullable', 'string', 'max:255'],
            'page.h1' => ['nullable', 'string', 'max:255'],
            'page.content' => ['nullable', 'string'],
            'page.meta_title' => ['nullable', 'string', 'max:255'],
            'page.meta_description' => ['nullable', 'string', 'max:320'],
        ];
    }

    public function save(SeoSlugGenerator $slugGenerator): void
    {
        $this->authorize('update', $this->page);
        $this->validate();
        $slugSeed = trim((string) ($this->page->slug ?: $this->page->title ?: $this->page->h1 ?: $this->page->primary_keyword ?: $this->page->getOriginal('slug')));
        $this->page->slug = $slugGenerator->generate($slugSeed);
        $this->page->content = $this->richEditorContent($this->page->content);
        $this->page->meta_description = $this->plainEditorContent($this->page->meta_description);
        $this->page->save();

        session()->flash('status', 'Đã lưu bản nháp SEO.');
    }

    public function regenerate(): void
    {
        $this->authorize('update', $this->page);
        GenerateSeoDraftJob::dispatch($this->page->getKey())->onQueue(config('seo_ai.queue', 'seo'));

        session()->flash('status', 'Đã đưa tác vụ tạo lại nội dung SEO vào hàng đợi.');
    }

    public function runQa(): void
    {
        $this->authorize('update', $this->page);
        ValidateSeoPageJob::dispatch($this->page->getKey())->onQueue(config('seo_ai.queue', 'seo'));

        session()->flash('status', 'Đã đưa tác vụ QA vào hàng đợi.');
    }

    public function approve(): void
    {
        $this->authorize('publish', $this->page);
        $this->page->status = SeoPageStatus::Approved;
        $this->page->save();

        session()->flash('status', 'Trang SEO đã được phê duyệt.');
    }

    public function publish(): void
    {
        $this->authorize('publish', $this->page);
        PublishSeoPageJob::dispatch($this->page->getKey())->onQueue(config('seo_ai.queue', 'seo'));

        session()->flash('status', 'Đã đưa tác vụ xuất bản vào hàng đợi.');
    }

    public function render()
    {
        return view('livewire.admin.seo.page-editor');
    }
}
