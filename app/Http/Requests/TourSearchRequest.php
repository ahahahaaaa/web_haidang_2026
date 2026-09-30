<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Src\Domains\Cms\Enums\TourScope;

class TourSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(collect([
            'q',
            'scope',
            'departure_location',
            'destination',
            'departure_date',
            'category',
            'transport',
            'budget',
        ])->mapWithKeys(function (string $key): array {
            $value = trim((string) $this->query($key, ''));

            return [$key => $value !== '' ? $value : null];
        })->all());
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:160'],
            'scope' => ['nullable', Rule::in(array_merge(['non_group'], array_map(fn (TourScope $scope) => $scope->value, TourScope::cases())))],
            'departure_location' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9-]+$/'],
            'destination' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9-]+$/'],
            'departure_date' => ['nullable', 'date_format:Y-m-d', 'after:today'],
            'category' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9-]+$/'],
            'transport' => ['nullable', 'string', 'max:255'],
            'budget' => ['nullable', Rule::in(['under-5m', '5m-10m', '10m-20m', '20m-plus'])],
        ];
    }

    public function messages(): array
    {
        return [
            'scope.in' => 'Phân loại tour không hợp lệ.',
            'departure_location.regex' => 'Điểm khởi hành không hợp lệ.',
            'destination.regex' => 'Điểm đến không hợp lệ.',
            'departure_date.date_format' => 'Ngày đi phải theo định dạng ngày/tháng/năm.',
            'departure_date.after' => 'Ngày đi phải từ ngày mai trở đi.',
            'category.regex' => 'Chủ đề tour không hợp lệ.',
            'budget.in' => 'Khoảng ngân sách không hợp lệ.',
        ];
    }
}
