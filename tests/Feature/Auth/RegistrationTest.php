<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.register');
    }

    public function test_new_users_can_register(): void
    {
        $component = Volt::test('pages.auth.register')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password');

        $component->call('register');

        $component->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
        $this->assertSame('buyer', User::firstWhere('email', 'test@example.com')->type);
    }

    public function test_merchant_registration_creates_a_profile(): void
    {
        $component = Volt::test('pages.auth.register')
            ->set('type', 'merchant')
            ->set('name', 'Shop Owner')
            ->set('business_name', 'متجر الاختبار')
            ->set('email', 'shop@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password');

        $component->call('register');

        $component->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();

        $user = User::firstWhere('email', 'shop@example.com');
        $this->assertSame('merchant', $user->type);
        $this->assertNotNull($user->merchantProfile);
        $this->assertSame('متجر الاختبار', $user->merchantProfile->business_name);
    }

    public function test_merchant_registration_requires_a_business_name(): void
    {
        $component = Volt::test('pages.auth.register')
            ->set('type', 'merchant')
            ->set('name', 'Shop Owner')
            ->set('business_name', '')
            ->set('email', 'shop2@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password');

        $component->call('register')->assertHasErrors(['business_name']);

        $this->assertGuest();
    }
}
