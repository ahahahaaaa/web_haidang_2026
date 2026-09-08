@php
    $destinationCountryGroups = $destinations
        ->groupBy(function ($destination) {
            if ((bool) $destination->is_country_root) {
                return 'country:'.$destination->getKey();
            }

            return $destination->country?->getKey()
                ? 'country:'.$destination->country->getKey()
                : 'unassigned';
        })
        ->map(function ($items) {
            $country = $items->first(fn ($destination) => (bool) $destination->is_country_root)
                ?: $items->first()?->country;

            return [
                'label' => $country?->name ?: 'Chưa gán quốc gia',
                'sort_order' => filled($country?->sort_order) ? (int) $country->sort_order : PHP_INT_MAX,
                'items' => $items
                    ->sort(function ($left, $right): int {
                        return [
                            (bool) $left->is_country_root ? 0 : 1,
                            filled($left->sort_order) ? (int) $left->sort_order : PHP_INT_MAX,
                            (string) $left->name,
                        ] <=> [
                            (bool) $right->is_country_root ? 0 : 1,
                            filled($right->sort_order) ? (int) $right->sort_order : PHP_INT_MAX,
                            (string) $right->name,
                        ];
                    })
                    ->values(),
            ];
        })
        ->sortBy([
            ['sort_order', 'asc'],
            ['label', 'asc'],
        ])
        ->values();
@endphp

@foreach ($destinationCountryGroups as $group)
    <optgroup label="{{ $group['label'] }}">
        @foreach ($group['items'] as $destination)
            @php
                $destinationOptionLabel = $destination->name.((bool) $destination->is_country_root ? ' (quốc gia)' : '');
            @endphp
            <option value="{{ $destination->id }}">{{ $destinationOptionLabel }}</option>
        @endforeach
    </optgroup>
@endforeach
