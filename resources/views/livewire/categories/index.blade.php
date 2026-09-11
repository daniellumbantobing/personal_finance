<?php

use Livewire\Volt\Component;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public $categories;
    public $name = '';
    public $type = 'expense';
    public $icon = '';
    public $parent_id = null;

    public $editingId = null;

    public function mount()
    {
        $this->loadCategories();
    }

    public function loadCategories()
    {
        $this->categories = Category::where(function ($query) {
            $query->where('user_id', Auth::id())
                  ->orWhere('is_default', true);
        })->orderBy('type')->orderBy('name')->get();
    }

    public function save()
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:income,expense',
            'icon' => 'nullable|string|max:50',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        if ($this->editingId) {
            $category = Auth::user()->categories()->findOrFail($this->editingId);
            $category->update($validated);
        } else {
            Auth::user()->categories()->create($validated);
        }

        $this->reset('name', 'icon', 'parent_id', 'editingId');
        $this->type = 'expense';
        $this->loadCategories();
    }

    public function edit($id)
    {
        $category = Auth::user()->categories()->findOrFail($id);
        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->type = $category->type;
        $this->icon = $category->icon;
        $this->parent_id = $category->parent_id;
    }

    public function delete($id)
    {
        Auth::user()->categories()->findOrFail($id)->delete();
        $this->loadCategories();
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Categories') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
            
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200 dark:border-slate-700/80 dark:border-slate-800 shadow-card">
                <header>
                    <h2 class="font-display font-bold text-xl text-slate-900 dark:text-white">
                        {{ $editingId ? __('Edit Category') : __('Add New Custom Category') }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-slate-400">
                        Add a new category to group your transactions.
                    </p>
                </header>
                <form wire:submit="save" class="mt-6 space-y-6">
                    <div>
                        <x-input-label for="name" :value="__('Category Name')" />
                        <x-text-input wire:model="name" id="name" class="mt-1 block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 dark:bg-slate-800/50 focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-800 dark:bg-slate-900 focus:ring-4 focus:ring-indigo-500/10 transition-all" type="text" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="type" :value="__('Type')" />
                        <select wire:model="type" id="type" class="mt-1 block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 dark:bg-slate-800/50 focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-800 dark:bg-slate-900 focus:ring-4 focus:ring-indigo-500/10 transition-all">
                            <option value="expense">Expense</option>
                            <option value="income">Income</option>
                        </select>
                        <x-input-error :messages="$errors->get('type')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="icon" :value="__('Icon (Emoji)')" />
                        <x-text-input wire:model="icon" id="icon" class="mt-1 block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 dark:bg-slate-800/50 focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-800 dark:bg-slate-900 focus:ring-4 focus:ring-indigo-500/10 transition-all" type="text" placeholder="e.g. 🍔" />
                        <x-input-error :messages="$errors->get('icon')" class="mt-2" />
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
                        {{ __('All Categories') }}
                    </h2>
                </header>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-slate-400">
                        <thead class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
                            <tr>
                                <th class="px-6 py-3">Icon</th>
                                <th class="px-6 py-3">Name</th>
                                <th class="px-6 py-3">Type</th>
                                <th class="px-6 py-3">Owner</th>
                                <th class="px-6 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($categories as $category)
                                <tr class="hover:bg-slate-50 dark:bg-slate-800 transition-colors border-b border-slate-100 dark:border-slate-800">
                                    <td class="px-6 py-4 text-xl">{{ $category->icon }}</td>
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-slate-200">{{ $category->name }}</td>
                                    <td class="px-6 py-4 capitalize">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $category->type === 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $category->type }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ $category->is_default ? 'System (Default)' : 'Custom' }}
                                    </td>
                                    <td class="px-6 py-4 space-x-2">
                                        @if(!$category->is_default && $category->user_id === auth()->id())
                                            <button wire:click="edit({{ $category->id }})" class="font-medium text-blue-600 hover:underline">Edit</button>
                                            <button wire:click="delete({{ $category->id }})" class="font-medium text-red-600 hover:underline" onclick="confirm('Are you sure?') || event.stopImmediatePropagation()">Delete</button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center">No categories found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
            </div>
</div>
    </div>
</div>



