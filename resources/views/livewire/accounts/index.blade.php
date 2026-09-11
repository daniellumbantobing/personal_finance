<?php

use Livewire\Volt\Component;
use App\Models\Account;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public $accounts;
    public $name = '';
    public $type = 'cash';
    public $balance = 0;
    public $currency = '';

    public $editingId = null;

    public function mount()
    {
        $this->currency = Auth::user()->currency ?? 'IDR';
        $this->loadAccounts();
    }

    public function loadAccounts()
    {
        $this->accounts = Auth::user()->accounts()->latest()->get();
    }

    public function save()
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:cash,bank,ewallet,credit_card,investment,other',
            'balance' => 'required|numeric',
            'currency' => 'required|string|max:3',
        ]);

        if ($this->editingId) {
            Auth::user()->accounts()->findOrFail($this->editingId)->update($validated);
        } else {
            Auth::user()->accounts()->create($validated);
        }

        $this->reset('name', 'balance', 'editingId');
        $this->type = 'cash';
        $this->loadAccounts();
    }

    public function edit($id)
    {
        $account = Auth::user()->accounts()->findOrFail($id);
        $this->editingId = $account->id;
        $this->name = $account->name;
        $this->type = $account->type;
        $this->balance = $account->balance;
        $this->currency = $account->currency;
    }

    public function delete($id)
    {
        Auth::user()->accounts()->findOrFail($id)->delete();
        $this->loadAccounts();
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Accounts') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
            
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200 dark:border-slate-700/80 dark:border-slate-800 shadow-card">
                <header>
                    <h2 class="font-display font-bold text-xl text-slate-900 dark:text-white">
                        {{ $editingId ? __('Edit Account') : __('Add New Account') }}
                    </h2>
                </header>
                <form wire:submit="save" class="mt-6 space-y-6">
                    <div>
                        <x-input-label for="name" :value="__('Account Name')" />
                        <x-text-input wire:model="name" id="name" class="mt-1 block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 dark:bg-slate-800/50 focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-800 dark:bg-slate-900 focus:ring-4 focus:ring-indigo-500/10 transition-all" type="text" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="type" :value="__('Type')" />
                        <select wire:model="type" id="type" class="mt-1 block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 dark:bg-slate-800/50 focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-800 dark:bg-slate-900 focus:ring-4 focus:ring-indigo-500/10 transition-all">
                            <option value="cash">Cash</option>
                            <option value="bank">Bank</option>
                            <option value="ewallet">E-Wallet</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="investment">Investment</option>
                            <option value="other">Other</option>
                        </select>
                        <x-input-error :messages="$errors->get('type')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="balance" :value="__('Starting Balance')" />
                        <x-text-input wire:model="balance" id="balance" class="mt-1 block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 dark:bg-slate-800/50 focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-800 dark:bg-slate-900 focus:ring-4 focus:ring-indigo-500/10 transition-all" type="number" step="0.01" required />
                        <x-input-error :messages="$errors->get('balance')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="currency" :value="__('Currency')" />
                        <x-text-input wire:model="currency" id="currency" class="mt-1 block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 dark:bg-slate-800/50 focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-800 dark:bg-slate-900 focus:ring-4 focus:ring-indigo-500/10 transition-all" type="text" required />
                        <x-input-error :messages="$errors->get('currency')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4">
                        <button class="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-sm transition-all shadow-md active:scale-95">{{ __("Save") }}</button>
                        @if($editingId)
                            <button type="button" wire:click="$set('editingId', null)" class="text-sm text-gray-600 dark:text-slate-400 underline">Cancel</button>
                        @endif
                    </div>
                </form>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200 dark:border-slate-700/80 dark:border-slate-800 shadow-card">
                <header>
                    <h2 class="font-display font-bold text-xl text-slate-900 dark:text-white mb-4">
                        {{ __('Your Accounts') }}
                    </h2>
                </header>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-slate-400">
                        <thead class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
                            <tr>
                                <th class="px-6 py-3">Name</th>
                                <th class="px-6 py-3">Type</th>
                                <th class="px-6 py-3">Balance</th>
                                <th class="px-6 py-3">Currency</th>
                                <th class="px-6 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($accounts as $account)
                                <tr class="hover:bg-slate-50 dark:bg-slate-800 transition-colors border-b border-slate-100 dark:border-slate-800">
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-slate-200">{{ $account->name }}</td>
                                    <td class="px-6 py-4 capitalize">{{ str_replace('_', ' ', $account->type) }}</td>
                                    <td class="px-6 py-4">{{ number_format($account->balance, 2) }}</td>
                                    <td class="px-6 py-4">{{ $account->currency }}</td>
                                    <td class="px-6 py-4 space-x-2">
                                        <button wire:click="edit({{ $account->id }})" class="font-medium text-blue-600 hover:underline">Edit</button>
                                        <button wire:click="delete({{ $account->id }})" class="font-medium text-red-600 hover:underline" onclick="confirm('Are you sure?') || event.stopImmediatePropagation()">Delete</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center">No accounts found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
            </div>
</div>
    </div>
</div>



