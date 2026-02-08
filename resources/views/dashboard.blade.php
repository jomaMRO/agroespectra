<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Dashboard
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Panel de usuario
                </p>
            </div>

            <a href="{{ route('profile.edit') }}"
               class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                Editar perfil
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 p-6">
                <p class="text-sm text-gray-500 dark:text-gray-400">Sesión iniciada como</p>
                <h3 class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                    {{ auth()->user()->name ?? auth()->user()->email }}
                </h3>

                <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="rounded-xl bg-gray-50 dark:bg-gray-900/40 p-4 ring-1 ring-gray-200 dark:ring-gray-700">
                        <div class="text-xs text-gray-500 dark:text-gray-400">Email</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">
                            {{ auth()->user()->email }}
                        </div>
                    </div>

                    <div class="rounded-xl bg-gray-50 dark:bg-gray-900/40 p-4 ring-1 ring-gray-200 dark:ring-gray-700">
                        <div class="text-xs text-gray-500 dark:text-gray-400">Verificación</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">
                            {{ auth()->user()->email_verified_at ? 'Verificado' : 'No verificado' }}
                        </div>
                    </div>
                </div>

                <div class="mt-6">
                    <a href="{{ url('/') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                        Volver al inicio
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
