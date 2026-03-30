<x-layouts.auth :title="config('app.name')">
    <div class="flex flex-col gap-6 text-center">
        <p class="text-sm text-zinc-600 dark:text-zinc-400">
            Bienvenue. Connectez-vous pour continuer.
        </p>

        <flux:button variant="primary" :href="route('login')" class="w-full">
            Se connecter
        </flux:button>
    </div>
</x-layouts.auth>
