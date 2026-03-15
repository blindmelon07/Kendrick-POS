<?php

use App\Models\Supplier;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Suppliers')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showModal = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $contactName = '';
    public string $email = '';
    public string $phone = '';
    public string $address = '';
    public string $notes = '';
    public bool $isActive = true;

    public bool $showDeleteModal = false;
    public ?int $deletingId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function suppliers(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Supplier::query()
            ->when($this->search, fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('contact_name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
                ->orWhere('phone', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(15);
    }

    public function create(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $supplier            = Supplier::findOrFail($id);
        $this->editingId     = $id;
        $this->name          = $supplier->name;
        $this->contactName   = $supplier->contact_name ?? '';
        $this->email         = $supplier->email ?? '';
        $this->phone         = $supplier->phone ?? '';
        $this->address       = $supplier->address ?? '';
        $this->notes         = $supplier->notes ?? '';
        $this->isActive      = $supplier->is_active;
        $this->showModal     = true;
    }

    public function save(): void
    {
        $this->validate([
            'name'        => ['required', 'string', 'max:255'],
            'contactName' => ['nullable', 'string', 'max:255'],
            'email'       => ['nullable', 'email', 'max:255'],
            'phone'       => ['nullable', 'string', 'max:50'],
            'address'     => ['nullable', 'string', 'max:500'],
            'notes'       => ['nullable', 'string', 'max:1000'],
        ]);

        $data = [
            'name'         => $this->name,
            'contact_name' => $this->contactName ?: null,
            'email'        => $this->email ?: null,
            'phone'        => $this->phone ?: null,
            'address'      => $this->address ?: null,
            'notes'        => $this->notes ?: null,
            'is_active'    => $this->isActive,
        ];

        if ($this->editingId) {
            Supplier::findOrFail($this->editingId)->update($data);
            $message = 'Supplier updated.';
        } else {
            Supplier::create($data);
            $message = 'Supplier created.';
        }

        $this->showModal = false;
        unset($this->suppliers);
        $this->dispatch('notify', message: $message);
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId      = $id;
        $this->showDeleteModal = true;
    }

    public function deleteSupplier(): void
    {
        $supplier = Supplier::findOrFail($this->deletingId);

        if ($supplier->deliveryOrders()->exists() || $supplier->purchaseOrders()->exists()) {
            $this->showDeleteModal = false;
            $this->dispatch('notify', message: 'Cannot delete supplier with existing orders.');

            return;
        }

        $supplier->delete();
        $this->showDeleteModal = false;
        $this->deletingId      = null;
        unset($this->suppliers);
        $this->dispatch('notify', message: 'Supplier deleted.');
    }

    private function resetForm(): void
    {
        $this->name        = '';
        $this->contactName = '';
        $this->email       = '';
        $this->phone       = '';
        $this->address     = '';
        $this->notes       = '';
        $this->isActive    = true;
        $this->resetValidation();
    }
};
?>

<div>
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Search name, contact, email or phone..."
            icon="magnifying-glass"
            class="sm:max-w-xs"
        />
        <flux:button wire:click="create" variant="primary" icon="plus">Add Supplier</flux:button>
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Name</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Contact</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Email</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Phone</th>
                    <th class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-300">Status</th>
                    <th class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-300">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->suppliers as $supplier)
                    <tr wire:key="supplier-{{ $supplier->id }}" class="border-b border-zinc-100 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/50">
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $supplier->name }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $supplier->contact_name ?? '—' }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $supplier->email ?? '—' }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $supplier->phone ?? '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            <flux:badge size="sm" color="{{ $supplier->is_active ? 'green' : 'zinc' }}">
                                {{ $supplier->is_active ? 'Active' : 'Inactive' }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <flux:button size="sm" wire:click="edit({{ $supplier->id }})" icon="pencil-square">Edit</flux:button>
                                <flux:button size="sm" variant="danger" wire:click="confirmDelete({{ $supplier->id }})" icon="trash">Delete</flux:button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-zinc-400">No suppliers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $this->suppliers->links() }}
    </div>

    {{-- Create / Edit Modal --}}
    <flux:modal wire:model="showModal" class="w-full max-w-lg">
        <div class="space-y-4">
            <flux:heading>{{ $editingId ? 'Edit Supplier' : 'Add Supplier' }}</flux:heading>

            <flux:field>
                <flux:label>Name <flux:badge size="sm" color="red">Required</flux:badge></flux:label>
                <flux:input wire:model="name" placeholder="Supplier company name" />
                <flux:error name="name" />
            </flux:field>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label>Contact Person</flux:label>
                    <flux:input wire:model="contactName" placeholder="Full name" />
                    <flux:error name="contactName" />
                </flux:field>

                <flux:field>
                    <flux:label>Phone</flux:label>
                    <flux:input wire:model="phone" placeholder="+63 900 000 0000" />
                    <flux:error name="phone" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Email</flux:label>
                <flux:input type="email" wire:model="email" placeholder="supplier@example.com" />
                <flux:error name="email" />
            </flux:field>

            <flux:field>
                <flux:label>Address</flux:label>
                <flux:textarea wire:model="address" rows="2" placeholder="Street, City, Province" />
                <flux:error name="address" />
            </flux:field>

            <flux:field>
                <flux:label>Notes</flux:label>
                <flux:textarea wire:model="notes" rows="2" placeholder="Optional notes..." />
                <flux:error name="notes" />
            </flux:field>

            <flux:field>
                <flux:checkbox wire:model="isActive" label="Active supplier" />
            </flux:field>

            <div class="flex justify-end gap-2 pt-2">
                <flux:button wire:click="$set('showModal', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">{{ $editingId ? 'Update' : 'Create' }}</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Delete Confirmation Modal --}}
    <flux:modal wire:model="showDeleteModal" class="w-full max-w-sm">
        <div class="space-y-4">
            <flux:heading>Delete Supplier</flux:heading>
            <flux:text>Are you sure you want to delete this supplier? This cannot be undone.</flux:text>
            <div class="flex justify-end gap-2">
                <flux:button wire:click="$set('showDeleteModal', false)">Cancel</flux:button>
                <flux:button variant="danger" wire:click="deleteSupplier">Delete</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
