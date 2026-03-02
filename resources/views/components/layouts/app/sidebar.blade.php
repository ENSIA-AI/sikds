<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark"

      @if (App::getLocale() == 'ar')
        dir="rtl"
      @endif
     >
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800">
<flux:header class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:navbar class="-mb-px max-lg:hidden flex justify-start justify-items-start">

        </flux:navbar>
        <flux:spacer />

        <flux:navbar class="-mb-px  flex justify-end items-end">
            <!-- language switcher -->
<flux:dropdown class="hidden lg:block" position="bottom" align="start">
    <flux:button variant="subtle" class="flex items-center gap-1">
        <span class="uppercase text-sm">
            {{ app()->getLocale() }}
        </span>
        <flux:icon name="chevron-down" class="w-4 h-4" />
    </flux:button>

    <flux:menu class="w-auto">
        @foreach(config('languages.lang') as $key => $locale)
            <flux:menu.item href="{{ route('changeLanguage', $key) }}">
                {{ __($locale) }}
            </flux:menu.item>
        @endforeach
    </flux:menu>
</flux:dropdown>


        </flux:navbar>
</flux:header>
<flux:sidebar collapsible  class=" border-zinc-200
bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-900 w-auto border-r">
    <flux:sidebar.header >
        <x-app-logo/>
        <flux:sidebar.collapse />
        <flux:sidebar.toggle class="lg:hidden" icon="x-mark"/>
    </flux:sidebar.header>

    <flux:navlist class="px-2">
        <flux:navlist.item href="{{ route('dashboard') }}" icon="home" :current="request()->routeIs('dashboard')" wire:navigate>
            {{ __('Dashboard') }}
        </flux:navlist.item>
    </flux:navlist>
    <flux:spacer/>

    <!-- Desktop User Menu -->
    <flux:dropdown class="hidden lg:block" position="bottom" align="start">
        <flux:profile
            :name=" auth()->user()->fullName "
            :initials="auth()->user()->initials()"
            icon:trailing="chevrons-up-down"
        />
        <flux:menu class="w-[220px]">
            <flux:menu.item as="button" href="https://accounts.mesrs.dz/home" icon="user" class="w-full" target="_blank">
                {{ __('account') }}
            </flux:menu.item>
            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                    {{ __('Log Out') }}
                </flux:menu.item>
            </form>
        </flux:menu>
    </flux:dropdown>
</flux:sidebar>

<!-- Mobile Header (Small Screens) -->
<flux:header class="lg:hidden">
    <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left"/>


    <!-- ✅ Role changer dropdown for mobile -->
<flux:dropdown position="top" align="end" class="m-0 p-0">


</flux:dropdown>
    <!-- ✅ Add spacer to push items apart -->
    <flux:spacer/>
    <!-- ✅ Language dropdown  -->
    <flux:dropdown position="top" align="end">
        <flux:button variant="subtle" class="flex items-center gap-1">
            <span class="uppercase text-sm">
                {{ app()->getLocale() }}
            </span>
            <flux:icon name="chevron-down" class="w-4 h-4" />
        </flux:button>

        <flux:menu class="w-auto">
            @foreach(config('languages.lang') as $key => $locale)
                <flux:menu.item href="{{ route('changeLanguage', $key) }}">
                    {{ __($locale) }}
                </flux:menu.item>
            @endforeach
        </flux:menu>
    </flux:dropdown>
    <!-- User dropdown menu -->
    <flux:dropdown position="top" align="end">
        <flux:profile
            :initials="auth()->user()->initials()"
            icon-trailing="chevron-down"
        />

        <flux:menu>
            <flux:menu.radio.group>
                <div class="p-0 text-sm font-normal">
                    <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                        <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                            <span class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                {{ auth()->user()->initials() }}
                            </span>
                        </span>
                        <div class="grid flex-1 text-start text-sm leading-tight">
                            <span class="truncate font-semibold">{{ auth()->user()->fullName }}</span>
                        </div>
                    </div>
                </div>
            </flux:menu.radio.group>


            <flux:menu.separator/>

            <flux:menu.item as="button" href="https://accounts.mesrs.dz/home" icon="user" class="w-full" target="_blank">
                {{ __('account') }}
            </flux:menu.item>


            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                    {{ __('Log Out') }}
                </flux:menu.item>
            </form>
        </flux:menu>
    </flux:dropdown>
</flux:header>
{{ $slot }}

@fluxScripts
<!-- Adds the Core Table Scripts -->

@rappasoftTableScripts



<!-- Adds any relevant Third-Party Scripts (e.g. Flatpickr) -->

@rappasoftTableThirdPartyScripts
<script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

</body>
</html>
