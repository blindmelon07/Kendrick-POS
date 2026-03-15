<?php

use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

new #[Title('User Management')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showModal = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $selectedRole = '';

    public bool $showDeleteModal = false;
    public ?int $deletingId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /** @return \Illuminate\Contracts\Pagination\LengthAwarePaginator */
    #[Computed]
    public function users(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return User::query()
            ->with('roles')
            ->when($this->search, fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(15);
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Role> */
    #[Computed]
    public function roles(): \Illuminate\Database\Eloquent\Collection
    {
        return Role::orderBy('name')->get();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $user                = User::with('roles')->findOrFail($id);
        $this->editingId     = $id;
        $this->name          = $user->name;
        $this->email         = $user->email;
        $this->password      = '';
        $this->selectedRole  = $user->roles->first()?->name ?? '';
        $this->showModal     = true;
    }

    public function save(): void
    {
        $rules = [
            'name'         => ['required', 'string', 'max:255'],
            'selectedRole' => ['nullable', 'string', 'exists:roles,name'],
        ];

        if ($this->editingId) {
            $rules['email']    = ['required', 'email', 'max:255', \Illuminate\Validation\Rule::unique('users', 'email')->ignore($this->editingId)];
            $rules['password'] = ['nullable', 'string', 'min:8'];
        } else {
            $rules['email']    = ['required', 'email', 'max:255', 'unique:users,email'];
            $rules['password'] = ['required', 'string', 'min:8'];
        }

        $this->validate($rules);

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);
            $user->update(array_filter([
                'name'  => $this->name,
                'email' => $this->email,
                ...(filled($this->password) ? ['password' => bcrypt($this->password)] : []),
            ]));
        } else {
            $user = User::create([
                'name'              => $this->name,
                'email'             => $this->email,
                'password'          => bcrypt($this->password),
                'email_verified_at' => now(),
            ]);
        }

        if (filled($this->selectedRole)) {
            $user->syncRoles([$this->selectedRole]);
        } else {
            $user->syncRoles([]);
        }

        $this->showModal = false;
        unset($this->users);
        $this->dispatch('notify', message: $this->editingId ? 'User updated.' : 'User created.');
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId      = $id;
        $this->showDeleteModal = true;
    }

    public function deleteUser(): void
    {
        $user = User::findOrFail($this->deletingId);

        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            $this->showDeleteModal = false;
            $this->dispatch('notify', message: 'You cannot delete your own account.');

            return;
        }

        $user->delete();
        $this->showDeleteModal = false;
        $this->deletingId      = null;
        unset($this->users);
        $this->dispatch('notify', message: 'User deleted.');
    }

    private function resetForm(): void
    {
        $this->name         = '';
        $this->email        = '';
        $this->password     = '';
        $this->selectedRole = '';
        $this->resetValidation();
    }
};
?>

<div>
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Search name or email..."
            icon="magnifying-glass"
            class="sm:max-w-xs"
        />
        <flux:button wire:click="create" variant="primary" icon="plus">Add User</flux:button>
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Name</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Email</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Role</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Joined</th>
                    <th class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-300">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->users as $user)
                    <tr wire:key="user-{{ $user->id }}" class="border-b border-zinc-100 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/50">
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $user->name }}
                            @if ($user->id === auth()->id())
                                <flux:badge size="sm" color="blue" class="ml-1">You</flux:badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            @if ($user->roles->isNotEmpty())
                                <flux:badge size="sm" color="{{ match($user->roles->first()->name) { 'admin' => 'red', 'manager' => 'amber', default => 'green' } }}">
                                    {{ ucfirst($user->roles->first()->name) }}
                                </flux:badge>
                            @else
                                <flux:badge size="sm" color="zinc">No role</flux:badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400">{{ $user->created_at->format('M d, Y') }}</td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <flux:button size="sm" wire:click="edit({{ $user->id }})" icon="pencil-square">Edit</flux:button>
                                @if ($user->id !== auth()->id())
                                    <flux:button size="sm" variant="danger" wire:click="confirmDelete({{ $user->id }})" icon="trash">Delete</flux:button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-zinc-400">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $this->users->links() }}
    </div>

    {{-- Create / Edit Modal --}}
    <flux:modal wire:model="showModal" class="w-full max-w-md">
        <div class="space-y-4">
            <flux:heading>{{ $editingId ? 'Edit User' : 'Add User' }}</flux:heading>

            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input wire:model="name" placeholder="Full name" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Email</flux:label>
                <flux:input type="email" wire:model="email" placeholder="email@example.com" />
                <flux:error name="email" />
            </flux:field>

            <flux:field>
                <flux:label>{{ $editingId ? 'New Password (leave blank to keep)' : 'Password' }}</flux:label>
                <flux:input type="password" wire:model="password" placeholder="{{ $editingId ? 'Leave blank to keep current' : 'Min 8 characters' }}" />
                <flux:error name="password" />
            </flux:field>

            <flux:field>
                <flux:label>Role</flux:label>
                <flux:select wire:model="selectedRole">
                    <flux:select.option value="">No role</flux:select.option>
                    @foreach ($this->roles as $role)
                        <flux:select.option value="{{ $role->name }}">{{ ucfirst($role->name) }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="selectedRole" />
            </flux:field>

            <div class="flex justify-end gap-2">
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
            <flux:heading>Delete User</flux:heading>
            <flux:text>Are you sure you want to delete this user? This action cannot be undone.</flux:text>
            <div class="flex justify-end gap-2">
                <flux:button wire:click="$set('showDeleteModal', false)">Cancel</flux:button>
                <flux:button variant="danger" wire:click="deleteUser">Delete</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
