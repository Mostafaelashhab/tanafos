<?php

use App\Models\MerchantProfile;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $type = 'buyer';
    public string $business_name = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
            'type' => ['required', Rule::in(['buyer', 'merchant'])],
            'business_name' => ['required_if:type,merchant', 'nullable', 'string', 'max:255'],
        ]);

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'type' => $validated['type'],
                'locale' => app()->getLocale(),
            ]);

            if ($validated['type'] === 'merchant') {
                MerchantProfile::create([
                    'user_id' => $user->id,
                    'business_name' => $validated['business_name'],
                ]);
            }

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <x-auth-heading :title="__('Create your account')" :subtitle="__('Join :app in seconds.', ['app' => config('app.name')])" />

    <form wire:submit="register">
        <!-- Account type: buyer vs merchant -->
        <div>
            <x-input-label :value="__('I want to')" />
            <div class="mt-1 grid grid-cols-2 gap-3">
                <label class="cursor-pointer rounded-lg border p-3 text-center text-sm transition flex items-center justify-center gap-2
                              {{ $type === 'buyer' ? 'border-brand-500 bg-brand-50 text-brand-700 font-semibold' : 'border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                    <input type="radio" wire:model.live="type" value="buyer" class="sr-only" />
                    <x-icon name="shopping-bag" class="w-5 h-5" /> {{ __('Request products') }}
                </label>
                <label class="cursor-pointer rounded-lg border p-3 text-center text-sm transition flex items-center justify-center gap-2
                              {{ $type === 'merchant' ? 'border-brand-500 bg-brand-50 text-brand-700 font-semibold' : 'border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                    <input type="radio" wire:model.live="type" value="merchant" class="sr-only" />
                    <x-icon name="storefront" class="w-5 h-5" /> {{ __('Sell as a merchant') }}
                </label>
            </div>
            <x-input-error :messages="$errors->get('type')" class="mt-2" />
        </div>

        <!-- Name -->
        <div class="mt-4">
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" name="name" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Business name (merchants only) -->
        @if ($type === 'merchant')
            <div class="mt-4">
                <x-input-label for="business_name" :value="__('Business name')" />
                <x-text-input wire:model="business_name" id="business_name" class="block mt-1 w-full" type="text" name="business_name" />
                <x-input-error :messages="$errors->get('business_name')" class="mt-2" />
            </div>
        @endif

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" name="email" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input wire:model="password" id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <x-primary-button class="mt-6 w-full">
            {{ __('Register') }}
        </x-primary-button>

        <p class="mt-6 text-center text-sm text-gray-500">
            {{ __('Already registered?') }}
            <a href="{{ route('login') }}" wire:navigate class="text-brand-600 font-semibold hover:underline">{{ __('Log in') }}</a>
        </p>
    </form>
</div>
