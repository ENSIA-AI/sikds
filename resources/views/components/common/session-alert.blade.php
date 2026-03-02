@if (session()->has('success'))
    <x-common.alert
        type="success"
        :title="__('component/alert.success')"
        :message="session('success')"
        class="mt-8 shadow-lg"
        id="my-custom-alert"
    />
@endif

@if (session()->has('error'))
    <x-common.alert
        type="danger"
        :title="__('component/alert.error')"
        :message="session('error')"
        class="mt-8 shadow-lg"
        id="my-custom-alert"
    />
@endif
