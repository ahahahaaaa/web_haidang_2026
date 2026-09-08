<?php

namespace App\Services\SeoOptimization;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OptimizationSheetGateway
{
    public function configured(): bool
    {
        return filled(config('seo_optimization.spreadsheet_id')) && filled(config('seo_optimization.sheet_endpoint'))
            && strlen((string) config('seo_optimization.sheet_secret')) >= 32;
    }

    public function getNext(string $siteId, array $allowedPageTypes): ?array
    {
        $data = $this->send('next', ['site_id' => $siteId, 'page_types' => $allowedPageTypes]);

        return empty($data['row']) ? null : $this->row($data['row']);
    }

    public function getRow(string $rowId): ?array
    {
        $data = $this->send('row', ['site_id' => config('seo_optimization.site_id'), 'row_id' => $rowId]);

        return empty($data['row']) ? null : $this->row($data['row']);
    }

    public function syncEvent(string $eventId, array $payload): array
    {
        $data = $this->send('event', ['event_id' => $eventId, 'event' => $payload]);
        if (($data['ack'] ?? false) !== true || ($data['event_id'] ?? '') !== $eventId) {
            $this->fail('Sheet chưa xác nhận đúng sự kiện; không đánh dấu đồng bộ thành công.');
        }

        return $data;
    }

    private function send(string $operation, array $data): array
    {
        $endpoint = (string) config('seo_optimization.sheet_endpoint');
        if (! $this->configured() || ! preg_match('~^https://script\.google\.com/macros/s/[A-Za-z0-9_-]+/exec$~D', $endpoint)) {
            $this->fail('Chưa cấu hình Apps Script endpoint HTTPS và shared secret hợp lệ.');
        }
        $payload = json_encode(['spreadsheet_id' => config('seo_optimization.spreadsheet_id'), 'operation' => $operation, ...$data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $timestamp = (string) now()->timestamp;
        $nonce = (string) Str::uuid();
        $body = ['payload' => $payload, 'timestamp' => $timestamp, 'nonce' => $nonce,
            'signature' => hash_hmac('sha256', $timestamp."\n".$nonce."\n".$payload, (string) config('seo_optimization.sheet_secret'))];
        try {
            $response = Http::connectTimeout(5)->timeout(25)->withoutRedirecting()->post($endpoint, $body);
            if (in_array($response->status(), [302, 303], true)) {
                $location = $response->header('Location');
                $parts = parse_url($location);
                if (($parts['scheme'] ?? '') !== 'https' || ($parts['host'] ?? '') !== 'script.googleusercontent.com'
                    || ($parts['path'] ?? '') !== '/macros/echo' || isset($parts['user']) || isset($parts['port']) || isset($parts['fragment'])) {
                    $this->fail('Apps Script chuyển hướng không hợp lệ.');
                }
                $response = Http::connectTimeout(5)->timeout(25)->withoutRedirecting()->get($location);
            }
            if (! $response->successful() || strlen($response->body()) > 2097152) {
                $this->fail('Không nhận được phản hồi Sheet hợp lệ.');
            }
            $result = $response->json();
            if (! is_array($result) || ($result['ok'] ?? false) !== true
                || ($result['spreadsheet_id'] ?? '') !== config('seo_optimization.spreadsheet_id') || ! is_array($result['data'] ?? null)) {
                $this->fail('Sheet từ chối yêu cầu hoặc sai workbook; kiểm tra revision, trạng thái và cấu hình.');
            }

            return $result['data'];
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable) {
            $this->fail('Kết nối Sheet chưa hoàn tất; không áp dụng nội dung khi chưa đọc được nguồn hiện hành.');
        }
    }

    private function row(array $row): array
    {
        $rules = [
            'row_id' => ['required', 'string', 'max:100'], 'site_id' => ['required', 'string', 'max:80'],
            'locale' => ['required', 'in:vi'], 'page_id' => ['required', 'ulid'],
            'page_type' => ['required', Rule::in(PageRegistryService::PAGE_TYPES)], 'url' => ['required', 'url:http,https', 'max:2000'],
            'enabled' => ['required', 'boolean'], 'status' => ['required', Rule::in(['READY', 'HOLD', 'CLAIMED', 'PREVIEW', 'PUBLISHED', 'NEED_DATA', 'FAILED'])],
            'priority' => ['required', Rule::in(['P0', 'P1', 'P2', 'P3'])],
            'primary_keyword' => ['required', 'string', 'max:200'], 'search_intent' => ['required', Rule::in(OptimizationBrief::INTENTS)],
            'notes' => ['nullable', 'string', 'max:5000'], 'image_url' => ['nullable', 'url:https', 'max:2048'],
            'image_prompt' => ['nullable', 'string', 'max:10000'], 'image_alt' => ['nullable', 'string', 'max:255'],
            'row_revision' => ['required', 'string', 'size:64', 'regex:/^[a-f0-9]+$/'],
            'task_id' => ['nullable', 'ulid'], 'proposal_id' => ['nullable', 'ulid'],
        ];
        foreach (['secondary_keywords', 'semantic_terms', 'entities', 'required_topics', 'required_internal_links', 'fact_sources'] as $key) {
            $rules[$key] = ['present', 'array', 'max:50'];
        }

        return Validator::make($row, $rules)->validate();
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['sheet' => $message]);
    }
}
