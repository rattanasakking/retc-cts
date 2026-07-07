<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="max-w-3xl mx-auto space-y-6">
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <livewire:profile.update-profile-information-form />
            </div>
        </div>

        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <livewire:profile.update-password-form />
            </div>
        </div>

        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <livewire:profile.delete-user-form />
            </div>
        </div>
    </div>
</x-app-layout>
