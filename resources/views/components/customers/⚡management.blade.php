<?php

use App\Models\Customer;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Customers')] class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    public bool $showModal = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $phone = '';
    public string $email = '';
    public string $address = '';
    public string $notes = '';
    public bool $isActive = true;

    public bool $showDeleteModal = false;
    public ?int $deletingId = null;

    public function updatedSearch(): void { $this->resetPage(); }

    #[Computed]
    public function customers(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Customer::query()
            ->when($this->search, fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('phone', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
            )
            ->when($this->statusFilter !== '', fn ($q) => $q->where('is_active', $this->statusFilter === 'active'))
            ->withCount('orders')
            ->latest()
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
        $customer        = Customer::findOrFail($id);
        $this->editingId = $id;
        $this->name      = $customer->name;
        $this->phone     = $customer->phone ?? '';
        $this->email     = $customer->email ?? '';
        $this->address   = $customer->address ?? '';
        $this->notes     = $customer->notes ?? '';
        $this->isActive  = $customer->is_active;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'name'  => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $data = [
            'name'      => $this->name,
            'phone'     => $this->phone ?: null,
            'email'     => $this->email ?: null,
            'address'   => $this->address ?: null,
            'notes'     => $this->notes ?: null,
            'is_active' => $this->isActive,
        ];

        if ($this->editingId) {
            Customer::findOrFail($this->editingId)->update($data);
        } else {
            Customer::create($data);
        }

        $this->showModal = false;
        unset($this->customers);
        $this->dispatch('notify', message: $this->editingId ? 'Customer updated.' : 'Customer created.');
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId      = $id;
        $this->showDeleteModal = true;
    }

    public function deleteCustomer(): void
    {
        Customer::findOrFail($this->deletingId)->delete();
        $this->showDeleteModal = false;
        $this->deletingId      = null;
        unset($this->customers);
    }

    private function resetForm(): void
    {
        $this->name     = '';
        $this->phone    = '';
        $this->email    = '';
        $this->address  = '';
        $this->notes    = '';
        $this->isActive = true;
        $this->resetValidation();
    }
};
?>

<div>
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-1 gap-2">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search name, phone, email..."
                icon="magnifying-glass"
                class="flex-1"
            />
            <flux:select wire:model.live="statusFilter" class="w-36">
                <flux:select.option value="">All</flux:select.option>
                <flux:select.option value="active">Active</flux:select.option>
                <flux:select.option value="inactive">Inactive</flux:select.option>
            </flux:select>
        </div>
        <flux:button wire:click="create" variant="primary" icon="plus">Add Customer</flux:button>
    </div>

    {{-- Mobile cards --}}
    <div class="sm:hidden space-y-3">
        @forelse ($this->customers as $customer)
            <div wire:key="cust-m-{{ $customer->id }}" class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="font-semibold text-zinc-800 dark:text-zinc-100">{{ $customer->name }}</p>
                        @if ($customer->phone)
                            <p class="text-xs text-zinc-500">{{ $customer->phone }}</p>
                        @endif
                        @if ($customer->email)
                            <p class="text-xs text-zinc-400">{{ $customer->email }}</p>
                        @endif
                    </div>
                    <div class="flex flex-col items-end gap-1">
                        <flux:badge size="sm" color="{{ $customer->is_active ? 'green' : 'zinc' }}">
                            {{ $customer->is_active ? 'Active' : 'Inactive' }}
                        </flux:badge>
                        <span class="text-xs text-zinc-400">{{ $customer->orders_count }} order(s)</span>
                    </div>
                </div>
                @if ($customer->address)
                    <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400 line-clamp-2">{{ $customer->address }}</p>
                @endif
                <div class="mt-3 flex justify-end gap-1">
                    <flux:button wire:click="edit({{ $customer->id }})" variant="ghost" size="sm" icon="pencil" title="Edit" />
                    <flux:button wire:click="confirmDelete({{ $customer->id }})" variant="ghost" size="sm" icon="trash" class="text-red-500" title="Delete" />
                </div>
            </div>
        @empty
            <p class="py-10 text-center text-zinc-400">No customers found.</p>
        @endforelse
    </div>

    {{-- Desktop table --}}
    <div class="hidden sm:block overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Name</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Phone</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Email</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Address</th>
                    <th class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-300">Orders</th>
                    <th class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-300">Status</th>
                    <th class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-300">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->customers as $customer)
                    <tr wire:key="cust-{{ $customer->id }}" class="border-b border-zinc-100 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/50">
                        <td class="px-4 py-3 font-medium text-zinc-800 dark:text-zinc-100">{{ $customer->name }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $customer->phone ?? '—' }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $customer->email ?? '—' }}</td>
                        <td class="px-4 py-3 max-w-48 truncate text-zinc-600 dark:text-zinc-400" title="{{ $customer->address }}">
                            {{ $customer->address ? \Illuminate\Support\Str::limit($customer->address, 40) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <flux:badge size="sm" color="zinc">{{ $customer->orders_count }}</flux:badge>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <flux:badge size="sm" color="{{ $customer->is_active ? 'green' : 'zinc' }}">
                                {{ $customer->is_active ? 'Active' : 'Inactive' }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-center gap-1">
                                <flux:button wire:click="edit({{ $customer->id }})" variant="ghost" size="sm" icon="pencil" title="Edit" />
                                <flux:button wire:click="confirmDelete({{ $customer->id }})" variant="ghost" size="sm" icon="trash" class="text-red-500" title="Delete" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-zinc-400">No customers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $this->customers->links() }}</div>

    {{-- Create / Edit Modal --}}
    <flux:modal wire:model="showModal" class="max-w-lg">
        <flux:heading>{{ $editingId ? 'Edit Customer' : 'Add Customer' }}</flux:heading>
        <div class="mt-4 space-y-3">
            <flux:field>
                <flux:label>Name <flux:badge size="xs" color="red">Required</flux:badge></flux:label>
                <flux:input wire:model="name" placeholder="Full name" />
                @error('name') <flux:error>{{ $message }}</flux:error> @enderror
            </flux:field>
            <div class="grid grid-cols-2 gap-3">
                <flux:field>
                    <flux:label>Phone</flux:label>
                    <flux:input wire:model="phone" placeholder="09XX XXX XXXX" />
                    @error('phone') <flux:error>{{ $message }}</flux:error> @enderror
                </flux:field>
                <flux:field>
                    <flux:label>Email</flux:label>
                    <flux:input type="email" wire:model="email" placeholder="optional" />
                    @error('email') <flux:error>{{ $message }}</flux:error> @enderror
                </flux:field>
            </div>
            <flux:field>
                <flux:label>Default Delivery Address</flux:label>
                <flux:textarea wire:model="address" rows="2" placeholder="Street, Barangay, City" />
            </flux:field>
            <flux:field>
                <flux:label>Notes</flux:label>
                <flux:textarea wire:model="notes" rows="2" placeholder="Internal notes about this customer" />
            </flux:field>
            <flux:field>
                <flux:label>Status</flux:label>
                <flux:switch wire:model="isActive" label="Active" />
            </flux:field>
        </div>
        <div class="mt-6 flex justify-end gap-2">
            <flux:button wire:click="$set('showModal', false)" variant="ghost">Cancel</flux:button>
            <flux:button wire:click="save" variant="primary">{{ $editingId ? 'Update' : 'Create' }}</flux:button>
        </div>
    </flux:modal>

    {{-- Delete Confirm --}}
    <flux:modal wire:model="showDeleteModal" class="max-w-sm">
        <flux:heading>Delete Customer?</flux:heading>
        <flux:text class="mt-1">Existing orders linked to this customer will not be deleted.</flux:text>
        <div class="mt-4 flex justify-end gap-2">
            <flux:button wire:click="$set('showDeleteModal', false)" variant="ghost">Cancel</flux:button>
            <flux:button wire:click="deleteCustomer" variant="danger">Delete</flux:button>
        </div>
    </flux:modal>
</div>
