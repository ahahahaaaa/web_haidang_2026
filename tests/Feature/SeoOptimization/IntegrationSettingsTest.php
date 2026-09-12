<?php

namespace Tests\Feature\SeoOptimization;

use App\Livewire\Admin\SeoOptimization\IntegrationSettings;
use App\Mcp\Servers\SeoOptimizationServer;
use App\Models\SeoOptimizationCredential;
use App\Models\SeoOptimizationEvent;
use App\Models\User;
use App\Services\SeoOptimization\CodexSeoPluginPackage;
use App\Services\SeoOptimization\PageAuditService;
use App\Services\SeoOptimization\SeoOptimizationCredentialService;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use ZipArchive;

class IntegrationSettingsTest extends OptimizationTestCase
{
    public function test_admin_can_issue_one_time_automation_token_and_revoke_it(): void
    {
        $this->writer->givePermissionTo(Permission::findOrCreate('admin.media.index', 'web'));

        Livewire::actingAs($this->reviewer)
            ->test(IntegrationSettings::class)
            ->set('tokenUserId', (string) $this->writer->id)
            ->set('tokenName', 'Schedule UI test')
            ->set('tokenDays', '14')
            ->set('tokenAllowedTypes', ['service'])
            ->set('tokenAutomation', true)
            ->call('createToken')
            ->assertHasNoErrors()
            ->assertDispatched('seo-token-issued', function (string $event, array $params): bool {
                $credential = SeoOptimizationCredential::query()->where('name', 'Schedule UI test')->firstOrFail();

                return $event === 'seo-token-issued'
                    && strlen($params['token'] ?? '') === 64
                    && hash_equals($credential->token_hash, hash('sha256', $params['token']))
                    && ($params['envVar'] ?? null) === CodexSeoPluginPackage::TOKEN_ENV_VAR;
            });

        $credential = SeoOptimizationCredential::query()->where('name', 'Schedule UI test')->firstOrFail();
        $this->assertSame(['read', 'audit', 'propose', 'automate'], $credential->abilities);
        $this->assertTrue(SeoOptimizationEvent::query()->where('event', 'credential.created')->where('payload->credential_id', $credential->id)->exists());
        $this->assertStringNotContainsString($credential->token_hash, json_encode(SeoOptimizationEvent::query()->where('event', 'credential.created')->firstOrFail()->payload));

        Livewire::actingAs($this->reviewer)
            ->test(IntegrationSettings::class)
            ->call('revokeToken', $credential->id)
            ->assertHasNoErrors();

        $this->assertNotNull($credential->fresh()->revoked_at);
        $this->assertTrue(SeoOptimizationEvent::query()->where('event', 'credential.revoked')->where('payload->credential_id', $credential->id)->exists());
    }

    public function test_token_cannot_be_issued_to_unverified_account(): void
    {
        $this->writer->forceFill(['email_verified_at' => null])->save();

        $this->expectException(ValidationException::class);
        app(SeoOptimizationCredentialService::class)->issue($this->writer, 'Unverified', ['service'], 30, false, $this->reviewer);
    }

    public function test_automation_token_requires_media_permission(): void
    {
        Livewire::actingAs($this->reviewer)
            ->test(IntegrationSettings::class)
            ->set('tokenUserId', (string) $this->writer->id)
            ->set('tokenName', 'Missing media permission')
            ->set('tokenDays', '30')
            ->set('tokenAllowedTypes', ['service'])
            ->set('tokenAutomation', true)
            ->call('createToken')
            ->assertHasErrors(['tokenAutomation'])
            ->assertNotDispatched('seo-token-issued');

        $this->assertFalse(SeoOptimizationCredential::query()->where('name', 'Missing media permission')->exists());
    }

    public function test_plugin_package_contains_valid_marketplace_mcp_and_no_secret(): void
    {
        $package = app(CodexSeoPluginPackage::class);
        $archivePath = $package->build('https://example.test/mcp/seo-optimization');
        $zip = new ZipArchive;

        try {
            $this->assertTrue($zip->open($archivePath) === true);
            $mcp = $zip->getFromName('plugins/haidang-travel-seo/.mcp.json');
            $marketplace = $zip->getFromName('.agents/plugins/marketplace.json');
            $pluginManifest = $zip->getFromName('plugins/haidang-travel-seo/.codex-plugin/plugin.json');
            $skill = $zip->getFromName('plugins/haidang-travel-seo/skills/haidang-travel-seo-post-optimizer/SKILL.md');

            $this->assertIsString($mcp);
            $this->assertIsString($marketplace);
            $this->assertIsString($pluginManifest);
            $this->assertIsString($skill);
            $this->assertStringContainsString('https://example.test/mcp/seo-optimization', $mcp);
            $this->assertStringContainsString(CodexSeoPluginPackage::TOKEN_ENV_VAR, $mcp);
            $this->assertStringContainsString('điểm sau lớn hơn điểm trước', $skill);
            $this->assertStringContainsString('baseline chính xác', $skill);
            $this->assertStringContainsString('queue_source=admin_queue', $skill);
            $this->assertStringContainsString('không gọi `commit_content_optimization`', $skill);
            $this->assertStringContainsString('12 tiêu chí', $skill);
            $this->assertStringContainsString('`seo_page_check`', $skill);
            $this->assertStringContainsString('`required_topics`', $skill);
            $this->assertStringContainsString('`entities`', $skill);
            $this->assertStringContainsString('P0/P1', $skill);
            $this->assertStringContainsString('`PASS`', $skill);
            foreach (PageAuditService::WEIGHTS as $dimension => $weight) {
                $this->assertStringContainsString("`{$dimension}` ({$weight})", $skill);
            }
            $this->assertStringNotContainsString($this->testToken, $mcp.$marketplace.$pluginManifest.$skill);
            $this->assertSame('haidang-travel', json_decode($marketplace, true, flags: JSON_THROW_ON_ERROR)['name']);
            $this->assertSame(CodexSeoPluginPackage::VERSION, json_decode($pluginManifest, true, flags: JSON_THROW_ON_ERROR)['version']);
        } finally {
            $zip->close();
            @unlink($archivePath);
        }
    }

    public function test_authorized_admin_can_download_plugin_but_unprivileged_user_cannot(): void
    {
        $this->actingAs($this->reviewer)
            ->get('/admin/seo-optimization/settings/plugin')
            ->assertOk()
            ->assertDownload('haidang-travel-seo-codex-v1.3.0.zip')
            ->assertHeader('content-type', 'application/zip');

        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->get('/admin/seo-optimization/settings/plugin')
            ->assertForbidden();
    }

    public function test_settings_page_contains_safe_test_and_cross_platform_instructions(): void
    {
        $this->actingAs($this->reviewer)
            ->get('/admin/seo-optimization/settings')
            ->assertOk()
            ->assertSee('Tạo token cho tài khoản dịch vụ')
            ->assertSee('list_seo_pages')
            ->assertSee('Windows PowerShell')
            ->assertSee('macOS')
            ->assertSee('Linux')
            ->assertSee('tự áp dụng khi điểm tăng')
            ->assertSee(CodexSeoPluginPackage::TOKEN_ENV_VAR);
    }

    public function test_settings_page_lists_registered_mcp_tools_and_current_schedule_contract(): void
    {
        $catalog = SeoOptimizationServer::toolCatalog();
        $response = $this->actingAs($this->reviewer)
            ->get('/admin/seo-optimization/settings')
            ->assertOk()
            ->assertSee('13/13 tool cho cấu hình token hiện tại')
            ->assertSee('admin_queue_only=false')
            ->assertSee('điểm audit hiện hành trên 80')
            ->assertSee('seo_page_check đúng một lần')
            ->assertSee('editor_mode=html')
            ->assertSee('editor_mode=blocks')
            ->assertSee('queue_source=automatic_selection');

        $this->assertCount(13, $catalog);
        foreach ($catalog as $tool) {
            $response->assertSee($tool['name']);
            $this->assertTrue($tool['allowed']);
        }

        Livewire::actingAs($this->reviewer)
            ->test(IntegrationSettings::class)
            ->set('tokenAutomation', false)
            ->assertSee('10/13 tool cho cấu hình token hiện tại')
            ->assertSee('Cần bật tự động');
    }
}
