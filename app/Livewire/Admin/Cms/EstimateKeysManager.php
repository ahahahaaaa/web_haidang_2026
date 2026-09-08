<?php

namespace App\Livewire\Admin\Cms;

use App\Services\Cms\EstimateKeyService;
use App\Support\EstimatePageContent;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Src\Domains\Cms\Models\EstimateAccessKey;
use Src\Domains\Cms\Models\LandingPage;

#[Layout('layouts.app')]
#[Title('Khóa dự toán')]
class EstimateKeysManager extends Component
{
    public array $form = [
        'label' => '',
        'notes' => '',
        'tier_codes' => [],
    ];

    public function mount(): void
    {
        $this->resetForm();
    }

    public function render()
    {
        return view('livewire.admin.cms.estimate-keys-manager', [
            'keys' => EstimateAccessKey::query()->latest()->get(),
            'tierOptions' => $this->tierOptions(),
        ]);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'form.label' => ['required', 'string', 'max:255'],
            'form.notes' => ['nullable', 'string', 'max:1000'],
            'form.tier_codes' => ['required', 'array', 'min:1'],
            'form.tier_codes.*' => ['required', 'string', 'max:100'],
        ]);

        app(EstimateKeyService::class)->create(
            $validated['form']['label'],
            $validated['form']['tier_codes'],
            $validated['form']['notes'] ?? null,
        );

        $this->resetForm();
        session()->flash('status', 'Đã tạo key dự toán mới.');
    }

    public function toggle(int $id): void
    {
        $key = EstimateAccessKey::query()->findOrFail($id);

        $key->forceFill([
            'is_active' => ! $key->is_active,
        ])->save();

        session()->flash('status', $key->is_active ? 'Đã bật key.' : 'Đã tắt key.');
    }

    protected function resetForm(): void
    {
        $this->form = [
            'label' => '',
            'notes' => '',
            'tier_codes' => collect($this->tierOptions())
                ->pluck('code')
                ->values()
                ->all(),
        ];
    }

    protected function tierOptions(): array
    {
        $estimateConfig = EstimatePageContent::prepareConfig(
            LandingPage::query()->where('page_key', 'estimate')->first()?->estimate_config,
        );

        return collect(data_get($estimateConfig, 'tiers.items', []))
            ->filter(fn (array $tier) => (bool) data_get($tier, 'requires_key', false))
            ->map(fn (array $tier) => [
                'code' => (string) data_get($tier, 'code'),
                'name' => (string) data_get($tier, 'name'),
            ])
            ->values()
            ->all();
    }
}
