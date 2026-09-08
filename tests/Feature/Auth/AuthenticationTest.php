<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\CmsBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\Features;
use Src\Domains\Cms\Models\SiteSetting;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
    }

    public function test_registration_is_temporarily_disabled(): void
    {
        $this->assertFalse(Route::has('register'));
        $this->assertFalse(Route::has('register.store'));

        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'Người dùng mới',
            'email' => 'new-user@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();
    }

    public function test_login_screen_renders_current_company_branding_from_theme_settings(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $settings = SiteSetting::query()->findOrFail(1);
        $settings->update([
            'company_name' => 'Công ty Du lịch Hải Đăng Travel',
            'site_tagline' => 'Quản trị tour và dịch vụ du lịch',
            'hotline' => '0909 000 111',
            'primary_email' => 'info@haidangtravel.com',
            'address' => '123 Nguyễn Trãi, Quận 1, TP. Hồ Chí Minh',
        ]);
        $settings
            ->addMedia(UploadedFile::fake()->image('cms-login-logo.png', 320, 160))
            ->usingName('CMS login logo')
            ->usingFileName('cms-login-logo.png')
            ->toMediaCollection('logo', 'public');

        $this->get(route('login'))
            ->assertOk()
            ->assertSeeText('Đăng nhập CMS')
            ->assertSeeText('Công ty Du lịch Hải Đăng Travel')
            ->assertSeeText('Quản trị tour và dịch vụ du lịch')
            ->assertSeeText('Email quản trị')
            ->assertSeeText('Mật khẩu')
            ->assertSeeText('Ghi nhớ đăng nhập')
            ->assertSeeText('Thông tin công ty')
            ->assertSeeText('0909 000 111')
            ->assertSeeText('info@haidangtravel.com')
            ->assertSeeText('123 Nguyễn Trãi, Quận 1, TP. Hồ Chí Minh')
            ->assertSee('cms-login-logo', false);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrorsIn('email');

        $this->assertGuest();
    }

    public function test_inactive_users_can_not_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrorsIn('email');

        $this->assertGuest();
    }

    public function test_users_with_two_factor_enabled_are_redirected_to_two_factor_challenge(): void
    {
        $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $user = User::factory()->withTwoFactor()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('two-factor.login'));
        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('home'));

        $this->assertGuest();
    }
}
