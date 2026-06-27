@if (config('banha.push.enabled'))
    <div x-data="{
            state: 'default',
            async init() { this.state = await window.TanafosPush.status(); },
            async toggle() {
                if (this.state === 'subscribed') { this.state = await window.TanafosPush.unsubscribe(); }
                else { this.state = await window.TanafosPush.subscribe(); }
            }
         }"
         x-show="state !== 'unsupported' && state !== 'denied'"
         x-cloak {{ $attributes }}>
        <button @click="toggle"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium ring-1 transition"
                :class="state === 'subscribed' ? 'bg-brand-50 text-brand-700 ring-brand-200' : 'bg-white text-gray-600 ring-gray-200 hover:bg-gray-50'">
            <x-icon name="bell" class="w-4 h-4" />
            <span x-show="state === 'subscribed'">{{ __('Notifications on') }}</span>
            <span x-show="state !== 'subscribed'">{{ __('Enable push notifications') }}</span>
        </button>
    </div>
@endif
