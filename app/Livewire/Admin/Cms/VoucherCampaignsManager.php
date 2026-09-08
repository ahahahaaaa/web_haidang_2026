<?php

namespace App\Livewire\Admin\Cms;

use App\Livewire\Admin\Cms\Concerns\AuthorizesAdminPermissions;
use App\Services\Travel\VoucherCampaignService;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Src\Domains\Cms\Models\LandingPage;
use Src\Domains\Cms\Models\VoucherCampaign;
use Src\Domains\Cms\Models\VoucherCode;

#[Layout('layouts.app')]
#[Title('Voucher campaigns')]
class VoucherCampaignsManager extends Component
{
    use AuthorizesAdminPermissions;
    use WithPagination;

    public array $form = [];

    public ?string $currentRouteName = null;

    public string $search = '';

    public ?int $selectedId = null;

    public string $statusFilter = '';

    public string $claimedVoucherSearch = '';

    public string $claimedVoucherLeadStatusFilter = '';

    public string $claimedVoucherClaimedFrom = '';

    public string $claimedVoucherClaimedTo = '';

    public function mount(?VoucherCampaign $campaign = null): void
    {
        $this->currentRouteName = request()->route()?->getName();
        $this->resetForm();

        if ($campaign?->exists) {
            $this->editCampaign((int) $campaign->getKey());
        }
    }

    public function createCampaign(): void
    {
        $this->authorizeAdminPermission('admin.voucher-campaigns.edit');

        $this->selectedId = null;
        $this->resetForm();
    }

    public function editCampaign(int $id): void
    {
        $this->authorizeAdminPermission('admin.voucher-campaigns.edit');

        $campaign = VoucherCampaign::query()->findOrFail($id);
        $this->selectedId = $campaign->id;
        $this->form = [
            'title' => $campaign->title,
            'slug' => $campaign->slug,
            'description' => $campaign->description,
            'landing_page_id' => $campaign->landing_page_id,
            'frame_image_url' => $campaign->frame_image_url,
            'code_prefix' => $campaign->code_prefix ?: 'HDTRAVEL',
            'code_quantity' => max(1, (int) $campaign->code_quantity),
            'starts_at' => $campaign->starts_at?->format('d/m/Y') ?? '',
            'ends_at' => $campaign->ends_at?->format('d/m/Y') ?? '',
            'code_valid_until' => $campaign->code_valid_until?->format('d/m/Y') ?? '',
            'is_active' => $campaign->is_active,
        ];
    }

    public function refreshCodes(VoucherCampaignService $vouchers): void
    {
        $this->authorizeAdminPermission('admin.voucher-campaigns.edit');

        if (! $this->selectedId) {
            return;
        }

        $campaign = VoucherCampaign::query()->findOrFail($this->selectedId);
        $quantity = max(1, (int) ($this->form['code_quantity'] ?? $campaign->code_quantity ?: 1));
        $prefix = (string) ($this->form['code_prefix'] ?? $campaign->code_prefix ?: 'HDTRAVEL');

        $vouchers->refreshGeneratedCodes($campaign, $quantity, $prefix);

        session()->flash('status', 'Đã đổi mới bộ mã voucher. Cookie mã cũ của khách sẽ không còn hiệu lực.');
    }

    public function selectFrameImageFromLibrary(int $mediaId, ?string $alt = null): void
    {
        $this->authorizeAdminPermission('admin.voucher-campaigns.edit');

        $media = Media::query()
            ->whereKey($mediaId)
            ->where('mime_type', 'like', 'image/%')
            ->firstOrFail();

        $this->form['frame_image_url'] = (string) $media->getUrl();
    }

    public function clearFrameImage(): void
    {
        $this->authorizeAdminPermission('admin.voucher-campaigns.edit');

        $this->form['frame_image_url'] = '';
    }

    public function deleteCampaign(int $id): void
    {
        $this->authorizeAdminPermission('admin.voucher-campaigns.edit');

        $wasSelected = $this->selectedId === $id;

        VoucherCampaign::query()->findOrFail($id)->delete();

        if ($wasSelected) {
            $this->selectedId = null;
            $this->resetForm();
        }

        session()->flash('status', 'Đã xóa campaign voucher.');

        if ($wasSelected || in_array($this->currentRouteName, ['admin.voucher-campaigns.create', 'admin.voucher-campaigns.edit'], true)) {
            $this->redirectRoute('admin.voucher-campaigns', navigate: true);
        }
    }

    public function render()
    {
        $campaigns = VoucherCampaign::query()
            ->with('landingPage')
            ->withCount([
                'codes',
                'codes as claimed_codes_count' => fn ($query) => $query->whereIn('status', [VoucherCode::STATUS_CLAIMED, VoucherCode::STATUS_USED]),
                'codes as used_codes_count' => fn ($query) => $query->where('status', VoucherCode::STATUS_USED),
                'codes as available_codes_count' => fn ($query) => $query->where('status', VoucherCode::STATUS_AVAILABLE),
            ])
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($nested): void {
                    $nested
                        ->where('title', 'like', '%'.$this->search.'%')
                        ->orWhere('slug', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->statusFilter === 'active', fn ($query) => $query->redeemable())
            ->when($this->statusFilter === 'inactive', fn ($query) => $query->where('is_active', false))
            ->latest('id')
            ->paginate(10);

        $selectedCampaign = null;
        $claimedVoucherCodes = collect();
        $filteredClaimedVoucherCodesCount = 0;

        if ($this->selectedId) {
            $selectedCampaign = VoucherCampaign::query()
                ->withCount([
                    'codes',
                    'codes as claimed_codes_count' => fn ($query) => $query->whereIn('status', [VoucherCode::STATUS_CLAIMED, VoucherCode::STATUS_USED]),
                    'codes as used_codes_count' => fn ($query) => $query->where('status', VoucherCode::STATUS_USED),
                    'codes as available_codes_count' => fn ($query) => $query->where('status', VoucherCode::STATUS_AVAILABLE),
                ])
                ->find($this->selectedId);

            $claimedVoucherCodesQuery = VoucherCode::query()
                ->with('inquiry:id,customer_name,customer_phone,customer_email,context_title,status,created_at')
                ->where('voucher_campaign_id', $this->selectedId)
                ->issued()
                ->when($this->claimedVoucherSearch !== '', function ($query): void {
                    $query->where(function ($nested): void {
                        $nested
                            ->where('code', 'like', '%'.$this->claimedVoucherSearch.'%')
                            ->orWhereHas('inquiry', function ($inquiryQuery): void {
                                $inquiryQuery
                                    ->where('customer_name', 'like', '%'.$this->claimedVoucherSearch.'%')
                                    ->orWhere('customer_phone', 'like', '%'.$this->claimedVoucherSearch.'%')
                                    ->orWhere('customer_email', 'like', '%'.$this->claimedVoucherSearch.'%')
                                    ->orWhere('context_title', 'like', '%'.$this->claimedVoucherSearch.'%');
                            });
                    });
                })
                ->when($this->claimedVoucherLeadStatusFilter === 'missing', fn ($query) => $query->whereNull('travel_inquiry_id'))
                ->when(
                    in_array($this->claimedVoucherLeadStatusFilter, ['new', 'contacted', 'closed'], true),
                    fn ($query) => $query->whereHas('inquiry', fn ($inquiryQuery) => $inquiryQuery->where('status', $this->claimedVoucherLeadStatusFilter)),
                )
                ->when($this->claimedVoucherClaimedFrom !== '', fn ($query) => $query->whereDate('claimed_at', '>=', $this->claimedVoucherClaimedFrom))
                ->when($this->claimedVoucherClaimedTo !== '', fn ($query) => $query->whereDate('claimed_at', '<=', $this->claimedVoucherClaimedTo));

            $filteredClaimedVoucherCodesCount = (clone $claimedVoucherCodesQuery)->count();
            $claimedVoucherCodes = $claimedVoucherCodesQuery
                ->latest('claimed_at')
                ->latest('id')
                ->limit(300)
                ->get();
        }

        $landingPages = LandingPage::query()
            ->whereNull('page_key')
            ->orderBy('title')
            ->get(['id', 'title', 'slug']);
        $usedVoucherLandingPageIds = VoucherCampaign::query()
            ->whereNotNull('landing_page_id')
            ->when($this->selectedId, fn ($query) => $query->whereKeyNot($this->selectedId))
            ->pluck('landing_page_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        return view('livewire.admin.cms.voucher-campaigns-manager', [
            'campaigns' => $campaigns,
            'isEditor' => in_array($this->currentRouteName, ['admin.voucher-campaigns.create', 'admin.voucher-campaigns.edit'], true),
            'landingPages' => $landingPages,
            'selectedCampaign' => $selectedCampaign,
            'claimedVoucherCodes' => $claimedVoucherCodes,
            'filteredClaimedVoucherCodesCount' => $filteredClaimedVoucherCodesCount,
            'hasClaimedVoucherFilters' => filled($this->claimedVoucherSearch)
                || filled($this->claimedVoucherLeadStatusFilter)
                || filled($this->claimedVoucherClaimedFrom)
                || filled($this->claimedVoucherClaimedTo),
            'usedVoucherLandingPageIds' => $usedVoucherLandingPageIds,
        ]);
    }

    public function save(VoucherCampaignService $vouchers): void
    {
        $this->authorizeAdminPermission('admin.voucher-campaigns.edit');
        $this->normalizeFormBeforeValidation();

        $validated = $this->validate([
            'form.title' => ['required', 'string', 'max:255'],
            'form.slug' => [
                'required',
                'string',
                'max:120',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('voucher_campaigns', 'slug')->ignore($this->selectedId),
            ],
            'form.description' => ['nullable', 'string', 'max:1000'],
            'form.landing_page_id' => [
                'nullable',
                'integer',
                'exists:landing_pages,id',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (blank($value)) {
                        return;
                    }

                    $isAlreadyUsed = VoucherCampaign::query()
                        ->where('landing_page_id', (int) $value)
                        ->when($this->selectedId, fn ($query) => $query->whereKeyNot($this->selectedId))
                        ->exists();

                    if ($isAlreadyUsed) {
                        $fail('Landing page này đã được gắn với một campaign voucher khác. Hãy sửa campaign hiện có hoặc chọn landing page khác.');
                    }
                },
            ],
            'form.frame_image_url' => ['nullable', 'string', 'max:2048'],
            'form.code_prefix' => ['required', 'string', 'max:50'],
            'form.code_quantity' => ['required', 'integer', 'min:1', 'max:5000'],
            'form.starts_at' => ['nullable', 'string', $this->adminDateRule()],
            'form.ends_at' => [
                'nullable',
                'string',
                $this->adminDateRule(),
                function (string $attribute, mixed $value, Closure $fail): void {
                    $startsAt = $this->adminDateFromForm('starts_at');
                    $endsAt = $this->adminDateFromValue($value);

                    if ($startsAt && $endsAt && $endsAt->lt($startsAt)) {
                        $fail('Ngày kết thúc nhận mã phải sau hoặc bằng ngày bắt đầu nhận mã.');
                    }
                },
            ],
            'form.code_valid_until' => [
                'nullable',
                'string',
                $this->adminDateRule(),
                function (string $attribute, mixed $value, Closure $fail): void {
                    $endsAt = $this->adminDateFromForm('ends_at');
                    $codeValidUntil = $this->adminDateFromValue($value);

                    if ($endsAt && $codeValidUntil && $codeValidUntil->lt($endsAt)) {
                        $fail('Hạn sử dụng voucher phải sau hoặc bằng ngày kết thúc nhận mã.');
                    }
                },
            ],
            'form.is_active' => ['boolean'],
        ]);

        $payload = $validated['form'];
        $payload['starts_at'] = $this->adminDateFromValue($payload['starts_at'] ?? null)?->startOfDay();
        $payload['ends_at'] = $this->adminDateFromValue($payload['ends_at'] ?? null)?->endOfDay();
        $payload['code_valid_until'] = $this->adminDateFromValue($payload['code_valid_until'] ?? null)?->endOfDay();

        $campaign = VoucherCampaign::query()->updateOrCreate(
            ['id' => $this->selectedId],
            [
                ...$payload,
                'code_set_version' => $this->selectedId
                    ? VoucherCampaign::query()->whereKey($this->selectedId)->value('code_set_version')
                    : (string) Str::uuid(),
            ],
        );

        $this->selectedId = $campaign->id;
        $vouchers->ensureGeneratedCodes($campaign, (int) $payload['code_quantity'], (string) $payload['code_prefix']);
        session()->flash('status', 'Đã lưu chiến dịch voucher.');
    }

    public function updatedFormTitle(string $value): void
    {
        if ($this->selectedId || filled($this->form['slug'] ?? null)) {
            return;
        }

        $this->form['slug'] = Str::slug($value);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function clearClaimedVoucherFilters(): void
    {
        $this->claimedVoucherSearch = '';
        $this->claimedVoucherLeadStatusFilter = '';
        $this->claimedVoucherClaimedFrom = '';
        $this->claimedVoucherClaimedTo = '';
    }

    protected function normalizeFormBeforeValidation(): void
    {
        $this->form['title'] = trim((string) ($this->form['title'] ?? ''));
        $this->form['slug'] = Str::slug((string) ($this->form['slug'] ?? $this->form['title'] ?? ''));
        $this->form['description'] = filled($this->form['description'] ?? null) ? trim((string) $this->form['description']) : null;
        $this->form['frame_image_url'] = filled($this->form['frame_image_url'] ?? null) ? trim((string) $this->form['frame_image_url']) : null;
        $this->form['code_prefix'] = Str::of((string) ($this->form['code_prefix'] ?? 'HDTRAVEL'))->upper()->replaceMatches('/[^A-Z0-9]+/', '-')->trim('-')->value() ?: 'HDTRAVEL';
        $this->form['code_quantity'] = (int) ($this->form['code_quantity'] ?? 1);
        $this->form['landing_page_id'] = filled($this->form['landing_page_id'] ?? null) ? (int) $this->form['landing_page_id'] : null;
        foreach (['starts_at', 'ends_at', 'code_valid_until'] as $dateKey) {
            $this->form[$dateKey] = filled($this->form[$dateKey] ?? null) ? trim((string) $this->form[$dateKey]) : null;
        }
        $this->form['is_active'] = (bool) ($this->form['is_active'] ?? false);
    }

    protected function adminDateRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (blank($value) || $this->adminDateFromValue($value)) {
                return;
            }

            $fail('Ngày phải có định dạng dd/mm/yyyy.');
        };
    }

    protected function adminDateFromForm(string $key): ?Carbon
    {
        return $this->adminDateFromValue($this->form[$key] ?? null);
    }

    protected function adminDateFromValue(mixed $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->copy();
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }

        $value = trim((string) $value);

        try {
            $date = Carbon::createFromFormat('!d/m/Y', $value);
        } catch (\Throwable) {
            return null;
        }

        return $date && $date->format('d/m/Y') === $value ? $date : null;
    }

    protected function resetForm(): void
    {
        $this->form = [
            'title' => '',
            'slug' => '',
            'description' => '',
            'landing_page_id' => null,
            'frame_image_url' => '',
            'code_prefix' => 'HDTRAVEL',
            'code_quantity' => 100,
            'starts_at' => now()->startOfDay()->format('d/m/Y'),
            'ends_at' => now()->addMonth()->endOfDay()->format('d/m/Y'),
            'code_valid_until' => now()->addMonth()->endOfDay()->format('d/m/Y'),
            'is_active' => true,
        ];
    }
}
