<?php

namespace App\Livewire\Admin\Estimator;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Src\Domains\Estimator\Models\EstimatePriceBook;

#[Layout('layouts.app')]
#[Title('Bảng giá dự toán')]
class EstimatePriceBooksManager extends Component
{
    public array $books = [];

    public function mount(): void
    {
        $this->loadState();
    }

    public function render()
    {
        return view('livewire.admin.estimator.estimate-price-books-manager');
    }

    public function save(): void
    {
        $this->validate([
            'books' => ['required', 'array', 'min:1'],
            'books.*.code' => ['required', 'string', 'max:100'],
            'books.*.name' => ['required', 'string', 'max:255'],
            'books.*.version' => ['required', 'string', 'max:50'],
            'books.*.status' => ['required', 'string', 'max:50'],
            'books.*.level_support_text' => ['nullable', 'string'],
            'books.*.building_types_text' => ['nullable', 'string'],
            'books.*.description' => ['nullable', 'string'],
            'books.*.is_active' => ['boolean'],
            'books.*.item_prices_text' => ['nullable', 'string'],
        ]);

        foreach ($this->books as $row) {
            $code = trim((string) ($row['code'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));

            if ($code === '' || $name === '') {
                continue;
            }

            EstimatePriceBook::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'version' => trim((string) ($row['version'] ?? 'v1')) ?: 'v1',
                    'status' => trim((string) ($row['status'] ?? 'draft')) ?: 'draft',
                    'level_support' => $this->parseList($row['level_support_text'] ?? ''),
                    'building_types' => $this->parseList($row['building_types_text'] ?? ''),
                    'description' => trim((string) ($row['description'] ?? '')) ?: null,
                    'is_active' => (bool) ($row['is_active'] ?? true),
                    'published_at' => trim((string) ($row['status'] ?? '')) === 'published' ? now() : null,
                    'item_prices' => $this->parseItemPrices($row['item_prices_text'] ?? ''),
                ],
            );
        }

        $this->loadState();
        session()->flash('status', 'Đã lưu bảng giá versioned cho dự toán.');
    }

    public function addBook(): void
    {
        $this->books[] = [
            'code' => '',
            'name' => '',
            'version' => 'v1',
            'status' => 'draft',
            'level_support_text' => '',
            'building_types_text' => '',
            'description' => '',
            'is_active' => true,
            'item_prices_text' => '',
        ];
    }

    protected function loadState(): void
    {
        $this->books = EstimatePriceBook::query()
            ->orderBy('name')
            ->get()
            ->map(fn (EstimatePriceBook $book) => [
                'code' => $book->code,
                'name' => $book->name,
                'version' => $book->version,
                'status' => $book->status,
                'level_support_text' => implode(', ', $book->level_support ?? []),
                'building_types_text' => implode(', ', $book->building_types ?? []),
                'description' => $book->description,
                'is_active' => $book->is_active,
                'item_prices_text' => collect($book->item_prices ?? [])
                    ->map(fn (mixed $price, string $code) => $code.'='.$price)
                    ->implode("\n"),
            ])
            ->all();
    }

    protected function parseList(mixed $value): array
    {
        return collect(preg_split('/[\r\n,]+/', trim((string) $value)) ?: [])
            ->map(fn (string $item) => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    protected function parseItemPrices(mixed $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', trim((string) $value)) ?: [])
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->reduce(function (array $carry, string $line) {
                $segments = preg_split('/=|:/', $line, 2) ?: [];
                $code = trim((string) ($segments[0] ?? ''));
                $price = trim((string) ($segments[1] ?? ''));

                if ($code !== '' && is_numeric($price)) {
                    $carry[$code] = (float) $price;
                }

                return $carry;
            }, []);
    }
}
