<x-app-layout>
    @php($user = auth()->user())

    <div class="max-w-2xl mx-auto px-4 py-5 space-y-5">

        {{-- Profile header --}}
        <div class="bg-white shadow-soft rounded-3xl p-6 flex items-center gap-4">
            <span class="w-16 h-16 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center font-extrabold text-2xl shrink-0">
                {{ mb_substr($user->name, 0, 1) }}
            </span>
            <div class="min-w-0">
                <h1 class="font-extrabold text-xl text-gray-900 truncate">{{ $user->name }}</h1>
                <div class="text-sm text-gray-400 truncate">{{ $user->email }}</div>
                <span class="mt-1 inline-block text-xs font-semibold bg-brand-50 text-brand-700 rounded-full px-2.5 py-0.5">
                    {{ __(ucfirst($user->type)) }}
                </span>
            </div>
        </div>

        <div class="bg-white shadow-soft rounded-3xl p-6">
            <div class="max-w-xl">
                <livewire:profile.update-profile-information-form />
            </div>
        </div>

        <div class="bg-white shadow-soft rounded-3xl p-6">
            <div class="max-w-xl">
                <livewire:profile.update-password-form />
            </div>
        </div>

        <div class="bg-white shadow-soft rounded-3xl p-6">
            <div class="max-w-xl">
                <livewire:profile.delete-user-form />
            </div>
        </div>

        {{-- Logout --}}
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-2xl bg-white shadow-soft text-red-600 font-semibold">
                <x-icon name="logout" class="w-5 h-5" /> {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-app-layout>
