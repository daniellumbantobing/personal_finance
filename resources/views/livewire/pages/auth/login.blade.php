<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: false);
    }
}; ?>

<div>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login" class="space-y-5">
        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">{{ __('Email') }}</label>
            <input wire:model="form.email" id="email" class="block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 px-4 py-3 text-sm focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-900 focus:ring-4 focus:ring-indigo-500/10 transition-all" type="email" name="email" required autofocus autocomplete="username" placeholder="hello@example.com" />
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block text-sm font-semibold text-slate-700 dark:text-slate-300">{{ __('Password') }}</label>
                @if (Route::has('password.request'))
                    <a class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors" href="{{ route('password.request') }}" wire:navigate>
                        {{ __('Forgot password?') }}
                    </a>
                @endif
            </div>
            <input wire:model="form.password" id="password" class="block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 px-4 py-3 text-sm focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-900 focus:ring-4 focus:ring-indigo-500/10 transition-all" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block">
            <label for="remember" class="inline-flex items-center">
                <input wire:model="form.remember" id="remember" type="checkbox" class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500 w-4 h-4" name="remember">
                <span class="ms-2 text-sm text-slate-600 dark:text-slate-400">{{ __('Keep me logged in') }}</span>
            </label>
        </div>

        <div class="pt-2">
            <button type="submit"
                    wire:loading.attr="disabled"
                    wire:target="login"
                    class="relative w-full h-12 flex items-center justify-center rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-indigo-600 dark:hover:bg-indigo-700 text-white font-semibold text-sm transition-all shadow-md hover:shadow-lg active:scale-[0.99] disabled:opacity-85 disabled:cursor-wait">
                <!-- Normal State -->
                <span wire:loading.remove wire:target="login" class="inline-flex items-center gap-2">
                    <span>{{ __('Masuk ke Akun') }}</span>
                    <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </span>

                <!-- Loading State (Only Spinner) -->
                <span wire:loading wire:target="login" class="inline-flex items-center justify-center">
                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </span>
            </button>
        </div>
        
        <div class="text-center mt-6 text-sm text-slate-500 dark:text-slate-400">
            Don't have an account? 
            <a href="{{ route('register') }}" class="font-bold text-indigo-600 hover:text-indigo-800">Sign up</a>
        </div>
    </form>
</div>

