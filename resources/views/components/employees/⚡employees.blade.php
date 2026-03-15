<?php

use App\Models\Employee;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Employees')] class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $typeFilter = '';

    public bool $showModal = false;
    public ?int $editingId = null;

    public string $firstName = '';
    public string $lastName = '';
    public string $middleName = '';
    public string $phone = '';
    public string $email = '';
    public string $address = '';
    public string $position = '';
    public string $department = '';
    public string $employmentType = 'regular';
    public string $dateHired = '';
    public float $basicSalary = 0;
    public string $salaryType = 'daily';
    public string $status = 'active';
    public string $notes = '';

    public bool $showDeleteModal = false;
    public ?int $deletingId = null;

    public function updatedSearch(): void { $this->resetPage(); }

    #[Computed]
    public function employees(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Employee::query()
            ->when($this->search, fn ($q) => $q
                ->where('first_name', 'like', "%{$this->search}%")
                ->orWhere('last_name', 'like', "%{$this->search}%")
                ->orWhere('employee_no', 'like', "%{$this->search}%")
                ->orWhere('position', 'like', "%{$this->search}%")
            )
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter, fn ($q) => $q->where('employment_type', $this->typeFilter))
            ->orderBy('last_name')
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
        $emp                   = Employee::findOrFail($id);
        $this->editingId       = $id;
        $this->firstName       = $emp->first_name;
        $this->lastName        = $emp->last_name;
        $this->middleName      = $emp->middle_name ?? '';
        $this->phone           = $emp->phone ?? '';
        $this->email           = $emp->email ?? '';
        $this->address         = $emp->address ?? '';
        $this->position        = $emp->position ?? '';
        $this->department      = $emp->department ?? '';
        $this->employmentType  = $emp->employment_type;
        $this->dateHired       = $emp->date_hired?->format('Y-m-d') ?? '';
        $this->basicSalary     = (float) $emp->basic_salary;
        $this->salaryType      = $emp->salary_type;
        $this->status          = $emp->status;
        $this->notes           = $emp->notes ?? '';
        $this->showModal       = true;
    }

    public function save(): void
    {
        $this->validate([
            'firstName'  => ['required', 'string', 'max:100'],
            'lastName'   => ['required', 'string', 'max:100'],
            'basicSalary'=> ['required', 'numeric', 'min:0'],
            'status'     => ['required', 'in:active,inactive,terminated,resigned'],
        ]);

        $data = [
            'first_name'      => $this->firstName,
            'last_name'       => $this->lastName,
            'middle_name'     => $this->middleName ?: null,
            'phone'           => $this->phone ?: null,
            'email'           => $this->email ?: null,
            'address'         => $this->address ?: null,
            'position'        => $this->position ?: null,
            'department'      => $this->department ?: null,
            'employment_type' => $this->employmentType,
            'date_hired'      => $this->dateHired ?: null,
            'basic_salary'    => $this->basicSalary,
            'salary_type'     => $this->salaryType,
            'status'          => $this->status,
            'notes'           => $this->notes ?: null,
        ];

        if ($this->editingId) {
            Employee::findOrFail($this->editingId)->update($data);
        } else {
            $data['employee_no'] = Employee::generateEmployeeNo();
            Employee::create($data);
        }

        $this->showModal = false;
        unset($this->employees);
        $this->dispatch('notify', message: $this->editingId ? 'Employee updated.' : 'Employee added.');
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId      = $id;
        $this->showDeleteModal = true;
    }

    public function deleteEmployee(): void
    {
        Employee::findOrFail($this->deletingId)->delete();
        $this->showDeleteModal = false;
        $this->deletingId      = null;
        unset($this->employees);
    }

    private function resetForm(): void
    {
        $this->firstName      = '';
        $this->lastName       = '';
        $this->middleName     = '';
        $this->phone          = '';
        $this->email          = '';
        $this->address        = '';
        $this->position       = '';
        $this->department     = '';
        $this->employmentType = 'regular';
        $this->dateHired      = '';
        $this->basicSalary    = 0;
        $this->salaryType     = 'daily';
        $this->status         = 'active';
        $this->notes          = '';
        $this->resetValidation();
    }

    public function statusColor(string $status): string
    {
        return match($status) {
            'active'     => 'green',
            'inactive'   => 'yellow',
            'terminated' => 'red',
            'resigned'   => 'zinc',
            default      => 'zinc',
        };
    }
};
?>

<div>
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-1 flex-col gap-2 sm:flex-row">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search name, ID, position..."
                icon="magnifying-glass"
                class="flex-1"
            />
            <flux:select wire:model.live="statusFilter" class="w-36">
                <flux:select.option value="">All Status</flux:select.option>
                <flux:select.option value="active">Active</flux:select.option>
                <flux:select.option value="inactive">Inactive</flux:select.option>
                <flux:select.option value="terminated">Terminated</flux:select.option>
                <flux:select.option value="resigned">Resigned</flux:select.option>
            </flux:select>
            <flux:select wire:model.live="typeFilter" class="w-40">
                <flux:select.option value="">All Types</flux:select.option>
                <flux:select.option value="regular">Regular</flux:select.option>
                <flux:select.option value="probationary">Probationary</flux:select.option>
                <flux:select.option value="contractual">Contractual</flux:select.option>
                <flux:select.option value="part_time">Part-time</flux:select.option>
            </flux:select>
        </div>
        <flux:button wire:click="create" variant="primary" icon="plus">Add Employee</flux:button>
    </div>

    {{-- Mobile cards --}}
    <div class="sm:hidden space-y-3">
        @forelse ($this->employees as $emp)
            <div wire:key="emp-m-{{ $emp->id }}" class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="font-mono text-xs text-zinc-400">{{ $emp->employee_no }}</p>
                        <p class="font-semibold text-zinc-800 dark:text-zinc-100">{{ $emp->full_name }}</p>
                        <p class="text-xs text-zinc-500">{{ $emp->position ?? '—' }}{{ $emp->department ? ' · ' . $emp->department : '' }}</p>
                    </div>
                    <flux:badge size="sm" color="{{ $this->statusColor($emp->status) }}">
                        {{ ucfirst($emp->status) }}
                    </flux:badge>
                </div>
                <div class="mt-2 flex items-center justify-between">
                    <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">
                        ₱{{ number_format($emp->basic_salary, 2) }}
                        <span class="text-xs font-normal text-zinc-400">/{{ $emp->salary_type === 'daily' ? 'day' : 'mo' }}</span>
                    </p>
                    <div class="flex gap-1">
                        <flux:button href="{{ route('employees.show', $emp->id) }}" wire:navigate variant="ghost" size="sm" icon="eye" title="View Profile" />
                        <flux:button wire:click="edit({{ $emp->id }})" variant="ghost" size="sm" icon="pencil" title="Edit" />
                        <flux:button wire:click="confirmDelete({{ $emp->id }})" variant="ghost" size="sm" icon="trash" class="text-red-500" title="Delete" />
                    </div>
                </div>
            </div>
        @empty
            <p class="py-10 text-center text-zinc-400">No employees found.</p>
        @endforelse
    </div>

    {{-- Desktop table --}}
    <div class="hidden sm:block overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">ID</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Name</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Position</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Department</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Type</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-600 dark:text-zinc-300">Salary</th>
                    <th class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-300">Status</th>
                    <th class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-300">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->employees as $emp)
                    <tr wire:key="emp-{{ $emp->id }}" class="border-b border-zinc-100 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/50">
                        <td class="px-4 py-3 font-mono text-xs text-zinc-500">{{ $emp->employee_no }}</td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-zinc-800 dark:text-zinc-100">{{ $emp->full_name }}</p>
                            @if ($emp->phone)
                                <p class="text-xs text-zinc-400">{{ $emp->phone }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $emp->position ?? '—' }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $emp->department ?? '—' }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ ucfirst(str_replace('_', '-', $emp->employment_type)) }}</td>
                        <td class="px-4 py-3 text-right font-medium">
                            ₱{{ number_format($emp->basic_salary, 2) }}
                            <span class="text-xs text-zinc-400">/{{ $emp->salary_type === 'daily' ? 'day' : 'mo' }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <flux:badge size="sm" color="{{ $this->statusColor($emp->status) }}">
                                {{ ucfirst($emp->status) }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-1">
                                <flux:button href="{{ route('employees.show', $emp->id) }}" wire:navigate variant="ghost" size="sm" icon="eye" title="View Profile" />
                                <flux:button wire:click="edit({{ $emp->id }})" variant="ghost" size="sm" icon="pencil" title="Edit" />
                                <flux:button wire:click="confirmDelete({{ $emp->id }})" variant="ghost" size="sm" icon="trash" class="text-red-500" title="Delete" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-zinc-400">No employees found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $this->employees->links() }}</div>

    {{-- Add / Edit Modal --}}
    <flux:modal wire:model="showModal" class="max-w-2xl">
        <flux:heading>{{ $editingId ? 'Edit Employee' : 'Add Employee' }}</flux:heading>
        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
            <flux:field>
                <flux:label>First Name <flux:badge size="xs" color="red">Required</flux:badge></flux:label>
                <flux:input wire:model="firstName" />
                @error('firstName') <flux:error>{{ $message }}</flux:error> @enderror
            </flux:field>
            <flux:field>
                <flux:label>Last Name <flux:badge size="xs" color="red">Required</flux:badge></flux:label>
                <flux:input wire:model="lastName" />
                @error('lastName') <flux:error>{{ $message }}</flux:error> @enderror
            </flux:field>
            <flux:field>
                <flux:label>Middle Name</flux:label>
                <flux:input wire:model="middleName" />
            </flux:field>
            <flux:field>
                <flux:label>Phone</flux:label>
                <flux:input wire:model="phone" placeholder="09XX XXX XXXX" />
            </flux:field>
            <flux:field>
                <flux:label>Email</flux:label>
                <flux:input type="email" wire:model="email" />
            </flux:field>
            <flux:field>
                <flux:label>Date Hired</flux:label>
                <flux:input type="date" wire:model="dateHired" />
            </flux:field>
            <flux:field class="sm:col-span-2">
                <flux:label>Address</flux:label>
                <flux:input wire:model="address" />
            </flux:field>
            <flux:field>
                <flux:label>Position</flux:label>
                <flux:input wire:model="position" placeholder="e.g. Driver, Laborer" />
            </flux:field>
            <flux:field>
                <flux:label>Department</flux:label>
                <flux:input wire:model="department" placeholder="e.g. Operations" />
            </flux:field>
            <flux:field>
                <flux:label>Employment Type</flux:label>
                <flux:select wire:model="employmentType">
                    <flux:select.option value="regular">Regular</flux:select.option>
                    <flux:select.option value="probationary">Probationary</flux:select.option>
                    <flux:select.option value="contractual">Contractual</flux:select.option>
                    <flux:select.option value="part_time">Part-time</flux:select.option>
                </flux:select>
            </flux:field>
            <flux:field>
                <flux:label>Status</flux:label>
                <flux:select wire:model="status">
                    <flux:select.option value="active">Active</flux:select.option>
                    <flux:select.option value="inactive">Inactive</flux:select.option>
                    <flux:select.option value="terminated">Terminated</flux:select.option>
                    <flux:select.option value="resigned">Resigned</flux:select.option>
                </flux:select>
            </flux:field>
            <flux:field>
                <flux:label>Basic Salary <flux:badge size="xs" color="red">Required</flux:badge></flux:label>
                <flux:input type="number" min="0" step="0.01" wire:model="basicSalary" prefix="₱" />
                @error('basicSalary') <flux:error>{{ $message }}</flux:error> @enderror
            </flux:field>
            <flux:field>
                <flux:label>Salary Type</flux:label>
                <flux:select wire:model="salaryType">
                    <flux:select.option value="daily">Daily</flux:select.option>
                    <flux:select.option value="monthly">Monthly</flux:select.option>
                </flux:select>
            </flux:field>
            <flux:field class="sm:col-span-2">
                <flux:label>Notes</flux:label>
                <flux:textarea wire:model="notes" rows="2" />
            </flux:field>
        </div>
        <div class="mt-6 flex justify-end gap-2">
            <flux:button wire:click="$set('showModal', false)" variant="ghost">Cancel</flux:button>
            <flux:button wire:click="save" variant="primary">{{ $editingId ? 'Update' : 'Add Employee' }}</flux:button>
        </div>
    </flux:modal>

    {{-- Delete Confirm --}}
    <flux:modal wire:model="showDeleteModal" class="max-w-sm">
        <flux:heading>Delete Employee?</flux:heading>
        <flux:text class="mt-1">All attendance, deductions, and cash advance records will also be deleted.</flux:text>
        <div class="mt-4 flex justify-end gap-2">
            <flux:button wire:click="$set('showDeleteModal', false)" variant="ghost">Cancel</flux:button>
            <flux:button wire:click="deleteEmployee" variant="danger">Delete</flux:button>
        </div>
    </flux:modal>
</div>
