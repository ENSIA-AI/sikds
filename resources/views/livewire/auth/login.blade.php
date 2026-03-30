<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Sign in with SSO')" :description="__('Use your MESRS account to access SIKDS.')" />

    <x-auth-session-status class="text-center" :status="session('status')" />

    <div class="flex flex-col gap-4">
        <flux:button variant="primary" :href="route('sso.redirect')" class="w-full">
            {{ __('Continue with MESRS SSO') }}
        </flux:button>

        <div class="text-center text-sm text-zinc-600 dark:text-zinc-400">
            {{ __('You will be redirected to the official MESRS login portal.') }}
        </div>
    </div>
</div>
