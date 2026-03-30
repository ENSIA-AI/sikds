{{-- A minimal login version, waiting for the figma design.. --}}
<x-layouts.auth title="Connexion">
    <div class="flex flex-col gap-6">
        <div class="text-center">
            <h1 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                {{ config('app.name') }}
            </h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                Connectez-vous avec votre compte institutionnel (SSO MESRS).
            </p>
        </div>

        @if (session('error'))
            <div
                class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200"
                role="alert"
            >
                {{ session('error') }}
            </div>
        @endif

        <flux:button variant="primary" :href="route('sso.redirect')" class="w-full">
            Continuer avec le SSO
        </flux:button>

        <p class="text-center text-xs text-zinc-500 dark:text-zinc-400">
            Vous serez redirigé vers le portail de connexion officiel.
        </p>
    </div>
</x-layouts.auth>
