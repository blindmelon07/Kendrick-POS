<?php

use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeCashAdvance;
use App\Models\EmployeeDeduction;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Employee Profile')] class extends Component
{
    use WithPagination;

    public int $employeeId;
    public string $attendanceMonth;

    // Attendance form
    public bool $showAttendanceModal = false;
    public ?int $editingAttendanceId = null;
    public string $attDate = '';
    public string $attTimeIn = '';
    public string $attTimeOut = '';
    public string $attStatus = 'present';
    public float $attHours = 8;
    public float $attOvertime = 0;
    public string $attNotes = '';

    // Deduction form
    public bool $showDeductionModal = false;
    public ?int $editingDeductionId = null;
    public string $dedType = 'sss';
    public string $dedDescription = '';
    public float $dedAmount = 0;
    public bool $dedRecurring = false;
    public string $dedEffectiveDate = '';
    public string $dedNotes = '';

    // Cash advance form
    public bool $showCashAdvanceModal = false;
    public ?int $editingCashAdvanceId = null;
    public float $caAmount = 0;
    public float $caAmountPaid = 0;
    public string $caDateGranted = '';
    public string $caReason = '';
    public string $caStatus = 'active';
    public string $caNotes = '';

    public function mount(int $employeeId): void
    {
        $this->employeeId     = $employeeId;
        $this->attendanceMonth = now()->format('Y-m');
        $this->attDate         = now()->format('Y-m-d');
        $this->caDateGranted   = now()->format('Y-m-d');
        $this->dedEffectiveDate = now()->format('Y-m-d');
    }

    #[Computed]
    public function employee(): Employee
    {
        return Employee::findOrFail($this->employeeId);
    }

    #[Computed]
    public function attendances(): \Illuminate\Database\Eloquent\Collection
    {
        [$year, $month] = explode('-', $this->attendanceMonth);
        return EmployeeAttendance::where('employee_id', $this->employeeId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderByDesc('date')
            ->get();
    }

    #[Computed]
    public function deductions(): \Illuminate\Database\Eloquent\Collection
    {
        return EmployeeDeduction::where('employee_id', $this->employeeId)
            ->orderByDesc('created_at')
            ->get();
    }

    #[Computed]
    public function cashAdvances(): \Illuminate\Database\Eloquent\Collection
    {
        return EmployeeCashAdvance::where('employee_id', $this->employeeId)
            ->orderByDesc('date_granted')
            ->get();
    }

    // ── Attendance ──────────────────────────────────────────────
    public function openAttendanceModal(?int $id = null): void
    {
        $this->resetAttendanceForm();
        if ($id) {
            $att = EmployeeAttendance::findOrFail($id);
            $this->editingAttendanceId = $id;
            $this->attDate    = $att->date->format('Y-m-d');
            $this->attTimeIn  = $att->time_in ?? '';
            $this->attTimeOut = $att->time_out ?? '';
            $this->attStatus  = $att->status;
            $this->attHours   = (float) $att->hours_worked;
            $this->attOvertime = (float) $att->overtime_hours;
            $this->attNotes   = $att->notes ?? '';
        }
        $this->showAttendanceModal = true;
    }

    public function saveAttendance(): void
    {
        $this->validate([
            'attDate'   => ['required', 'date'],
            'attStatus' => ['required', 'in:present,absent,late,half_day,holiday,leave'],
            'attHours'  => ['nullable', 'numeric', 'min:0'],
            'attOvertime' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data = [
            'employee_id'   => $this->employeeId,
            'date'          => $this->attDate,
            'time_in'       => $this->attTimeIn ?: null,
            'time_out'      => $this->attTimeOut ?: null,
            'status'        => $this->attStatus,
            'hours_worked'  => $this->attHours,
            'overtime_hours'=> $this->attOvertime,
            'notes'         => $this->attNotes ?: null,
        ];

        if ($this->editingAttendanceId) {
            EmployeeAttendance::findOrFail($this->editingAttendanceId)->update($data);
        } else {
            EmployeeAttendance::updateOrCreate(
                ['employee_id' => $this->employeeId, 'date' => $this->attDate],
                $data
            );
        }

        $this->showAttendanceModal = false;
        unset($this->attendances);
        $this->dispatch('notify', message: 'Attendance saved.');
        $this->resetAttendanceForm();
    }

    public function deleteAttendance(int $id): void
    {
        EmployeeAttendance::findOrFail($id)->delete();
        unset($this->attendances);
    }

    // ── Deductions ───────────────────────────────────────────────
    public function openDeductionModal(?int $id = null): void
    {
        $this->resetDeductionForm();
        if ($id) {
            $ded = EmployeeDeduction::findOrFail($id);
            $this->editingDeductionId = $id;
            $this->dedType          = $ded->type;
            $this->dedDescription   = $ded->description;
            $this->dedAmount        = (float) $ded->amount;
            $this->dedRecurring     = $ded->is_recurring;
            $this->dedEffectiveDate = $ded->effective_date?->format('Y-m-d') ?? '';
            $this->dedNotes         = $ded->notes ?? '';
        }
        $this->showDeductionModal = true;
    }

    public function saveDeduction(): void
    {
        $this->validate([
            'dedDescription' => ['required', 'string'],
            'dedAmount'      => ['required', 'numeric', 'min:0.01'],
            'dedType'        => ['required', 'in:sss,philhealth,pagibig,tax,cash_advance,loan,other'],
        ]);

        $data = [
            'employee_id'    => $this->employeeId,
            'type'           => $this->dedType,
            'description'    => $this->dedDescription,
            'amount'         => $this->dedAmount,
            'is_recurring'   => $this->dedRecurring,
            'effective_date' => $this->dedEffectiveDate ?: null,
            'notes'          => $this->dedNotes ?: null,
            'created_by'     => Auth::id(),
        ];

        if ($this->editingDeductionId) {
            EmployeeDeduction::findOrFail($this->editingDeductionId)->update($data);
        } else {
            EmployeeDeduction::create($data);
        }

        $this->showDeductionModal = false;
        unset($this->deductions);
        $this->dispatch('notify', message: 'Deduction saved.');
        $this->resetDeductionForm();
    }

    public function deleteDeduction(int $id): void
    {
        EmployeeDeduction::findOrFail($id)->delete();
        unset($this->deductions);
    }

    // ── Cash Advances ────────────────────────────────────────────
    public function openCashAdvanceModal(?int $id = null): void
    {
        $this->resetCashAdvanceForm();
        if ($id) {
            $ca = EmployeeCashAdvance::findOrFail($id);
            $this->editingCashAdvanceId = $id;
            $this->caAmount      = (float) $ca->amount;
            $this->caAmountPaid  = (float) $ca->amount_paid;
            $this->caDateGranted = $ca->date_granted->format('Y-m-d');
            $this->caReason      = $ca->reason ?? '';
            $this->caStatus      = $ca->status;
            $this->caNotes       = $ca->notes ?? '';
        }
        $this->showCashAdvanceModal = true;
    }

    public function saveCashAdvance(): void
    {
        $this->validate([
            'caAmount'      => ['required', 'numeric', 'min:0.01'],
            'caAmountPaid'  => ['nullable', 'numeric', 'min:0'],
            'caDateGranted' => ['required', 'date'],
            'caStatus'      => ['required', 'in:active,fully_paid'],
        ]);

        $data = [
            'employee_id'  => $this->employeeId,
            'amount'       => $this->caAmount,
            'amount_paid'  => $this->caAmountPaid,
            'date_granted' => $this->caDateGranted,
            'reason'       => $this->caReason ?: null,
            'status'       => $this->caStatus,
            'notes'        => $this->caNotes ?: null,
            'created_by'   => Auth::id(),
        ];

        if ($this->editingCashAdvanceId) {
            EmployeeCashAdvance::findOrFail($this->editingCashAdvanceId)->update($data);
        } else {
            EmployeeCashAdvance::create($data);
        }

        $this->showCashAdvanceModal = false;
        unset($this->cashAdvances);
        $this->dispatch('notify', message: 'Cash advance saved.');
        $this->resetCashAdvanceForm();
    }

    public function deleteCashAdvance(int $id): void
    {
        EmployeeCashAdvance::findOrFail($id)->delete();
        unset($this->cashAdvances);
    }

    private function resetAttendanceForm(): void
    {
        $this->editingAttendanceId = null;
        $this->attDate     = now()->format('Y-m-d');
        $this->attTimeIn   = '';
        $this->attTimeOut  = '';
        $this->attStatus   = 'present';
        $this->attHours    = 8;
        $this->attOvertime = 0;
        $this->attNotes    = '';
        $this->resetValidation();
    }

    private function resetDeductionForm(): void
    {
        $this->editingDeductionId = null;
        $this->dedType          = 'sss';
        $this->dedDescription   = '';
        $this->dedAmount        = 0;
        $this->dedRecurring     = false;
        $this->dedEffectiveDate = now()->format('Y-m-d');
        $this->dedNotes         = '';
        $this->resetValidation();
    }

    private function resetCashAdvanceForm(): void
    {
        $this->editingCashAdvanceId = null;
        $this->caAmount      = 0;
        $this->caAmountPaid  = 0;
        $this->caDateGranted = now()->format('Y-m-d');
        $this->caReason      = '';
        $this->caStatus      = 'active';
        $this->caNotes       = '';
        $this->resetValidation();
    }

    public function attendanceColor(string $status): string
    {
        return match($status) {
            'present'  => 'green',
            'late'     => 'yellow',
            'half_day' => 'yellow',
            'absent'   => 'red',
            'holiday'  => 'blue',
            'leave'    => 'purple',
            default    => 'zinc',
        };
    }
};
?>

<div x-data="{ tab: 'attendance' }">
    {{-- Header --}}
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <flux:button href="{{ route('employees.index') }}" wire:navigate variant="ghost" icon="arrow-left" size="sm" />
            <div>
                <flux:heading>{{ $this->employee->full_name }}</flux:heading>
                <p class="text-sm text-zinc-500">{{ $this->employee->employee_no }} · {{ $this->employee->position ?? 'No position' }}</p>
            </div>
        </div>
        <div class="flex gap-2 flex-wrap">
            <flux:badge size="lg" color="{{ match($this->employee->status) { 'active' => 'green', 'inactive' => 'yellow', 'terminated' => 'red', default => 'zinc' } }}">
                {{ ucfirst($this->employee->status) }}
            </flux:badge>
            <flux:badge size="lg" color="zinc">
                ₱{{ number_format($this->employee->basic_salary, 2) }}/{{ $this->employee->salary_type === 'daily' ? 'day' : 'mo' }}
            </flux:badge>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="mb-4 flex gap-1 rounded-xl border border-zinc-200 bg-zinc-50 p-1 dark:border-zinc-700 dark:bg-zinc-800">
        @foreach (['attendance' => 'Attendance', 'deductions' => 'Deductions', 'cash_advance' => 'Cash Advance'] as $key => $label)
            <button
                @click="tab = '{{ $key }}'"
                :class="tab === '{{ $key }}' ? 'bg-white shadow text-zinc-800 dark:bg-zinc-700 dark:text-zinc-100' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300'"
                class="flex-1 rounded-lg px-3 py-2 text-sm font-medium transition"
            >{{ $label }}</button>
        @endforeach
    </div>

    {{-- ATTENDANCE TAB --}}
    <div x-show="tab === 'attendance'">
        <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2">
                <flux:input type="month" wire:model.live="attendanceMonth" class="w-44" />
                <span class="text-sm text-zinc-500">
                    {{ $this->attendances->where('status', 'present')->count() + $this->attendances->where('status', 'late')->count() }} present,
                    {{ $this->attendances->where('status', 'absent')->count() }} absent
                </span>
            </div>
            <flux:button wire:click="openAttendanceModal()" variant="primary" icon="plus" size="sm">Log Attendance</flux:button>
        </div>

        {{-- Mobile --}}
        <div class="sm:hidden space-y-2">
            @forelse ($this->attendances as $att)
                <div wire:key="att-m-{{ $att->id }}" class="rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-zinc-800 dark:text-zinc-100">{{ $att->date->format('D, M d') }}</p>
                            <p class="text-xs text-zinc-400">
                                {{ $att->time_in ?? '—' }} – {{ $att->time_out ?? '—' }}
                                · {{ $att->hours_worked + 0 }}h
                                @if ($att->overtime_hours > 0) + {{ $att->overtime_hours + 0 }}h OT @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-1">
                            <flux:badge size="sm" color="{{ $this->attendanceColor($att->status) }}">
                                {{ ucfirst(str_replace('_', ' ', $att->status)) }}
                            </flux:badge>
                            <flux:button wire:click="openAttendanceModal({{ $att->id }})" variant="ghost" size="sm" icon="pencil" />
                            <flux:button wire:click="deleteAttendance({{ $att->id }})" variant="ghost" size="sm" icon="trash" class="text-red-500" />
                        </div>
                    </div>
                </div>
            @empty
                <p class="py-8 text-center text-zinc-400">No attendance records for this month.</p>
            @endforelse
        </div>

        {{-- Desktop --}}
        <div class="hidden sm:block overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="px-4 py-2 text-left font-medium text-zinc-600 dark:text-zinc-300">Date</th>
                        <th class="px-4 py-2 text-center font-medium text-zinc-600 dark:text-zinc-300">Status</th>
                        <th class="px-4 py-2 text-center font-medium text-zinc-600 dark:text-zinc-300">Time In</th>
                        <th class="px-4 py-2 text-center font-medium text-zinc-600 dark:text-zinc-300">Time Out</th>
                        <th class="px-4 py-2 text-right font-medium text-zinc-600 dark:text-zinc-300">Hours</th>
                        <th class="px-4 py-2 text-right font-medium text-zinc-600 dark:text-zinc-300">Overtime</th>
                        <th class="px-4 py-2 text-left font-medium text-zinc-600 dark:text-zinc-300">Notes</th>
                        <th class="px-4 py-2 text-center font-medium text-zinc-600 dark:text-zinc-300">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->attendances as $att)
                        <tr wire:key="att-{{ $att->id }}" class="border-b border-zinc-100 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/50">
                            <td class="px-4 py-2 font-medium">{{ $att->date->format('D, M d Y') }}</td>
                            <td class="px-4 py-2 text-center">
                                <flux:badge size="sm" color="{{ $this->attendanceColor($att->status) }}">
                                    {{ ucfirst(str_replace('_', ' ', $att->status)) }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-2 text-center text-zinc-500">{{ $att->time_in ?? '—' }}</td>
                            <td class="px-4 py-2 text-center text-zinc-500">{{ $att->time_out ?? '—' }}</td>
                            <td class="px-4 py-2 text-right">{{ $att->hours_worked + 0 }}h</td>
                            <td class="px-4 py-2 text-right">{{ $att->overtime_hours > 0 ? $att->overtime_hours + 0 . 'h' : '—' }}</td>
                            <td class="px-4 py-2 text-xs text-zinc-400">{{ $att->notes ?? '—' }}</td>
                            <td class="px-4 py-2">
                                <div class="flex justify-center gap-1">
                                    <flux:button wire:click="openAttendanceModal({{ $att->id }})" variant="ghost" size="sm" icon="pencil" />
                                    <flux:button wire:click="deleteAttendance({{ $att->id }})" variant="ghost" size="sm" icon="trash" class="text-red-500" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-8 text-center text-zinc-400">No attendance records for this month.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- DEDUCTIONS TAB --}}
    <div x-show="tab === 'deductions'" x-cloak>
        <div class="mb-3 flex items-center justify-between">
            <p class="text-sm text-zinc-500">
                Total recurring: ₱{{ number_format($this->deductions->where('is_recurring', true)->sum('amount'), 2) }}/period
            </p>
            <flux:button wire:click="openDeductionModal()" variant="primary" icon="plus" size="sm">Add Deduction</flux:button>
        </div>

        {{-- Mobile --}}
        <div class="sm:hidden space-y-2">
            @forelse ($this->deductions as $ded)
                <div wire:key="ded-m-{{ $ded->id }}" class="rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="font-medium text-zinc-800 dark:text-zinc-100">{{ $ded->description }}</p>
                            <p class="text-xs text-zinc-400">{{ strtoupper($ded->type) }} · {{ $ded->is_recurring ? 'Recurring' : 'One-time' }}</p>
                        </div>
                        <div class="flex items-center gap-1">
                            <p class="text-sm font-semibold text-red-500">-₱{{ number_format($ded->amount, 2) }}</p>
                            <flux:button wire:click="openDeductionModal({{ $ded->id }})" variant="ghost" size="sm" icon="pencil" />
                            <flux:button wire:click="deleteDeduction({{ $ded->id }})" variant="ghost" size="sm" icon="trash" class="text-red-500" />
                        </div>
                    </div>
                </div>
            @empty
                <p class="py-8 text-center text-zinc-400">No deductions recorded.</p>
            @endforelse
        </div>

        {{-- Desktop --}}
        <div class="hidden sm:block overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="px-4 py-2 text-left font-medium text-zinc-600 dark:text-zinc-300">Type</th>
                        <th class="px-4 py-2 text-left font-medium text-zinc-600 dark:text-zinc-300">Description</th>
                        <th class="px-4 py-2 text-right font-medium text-zinc-600 dark:text-zinc-300">Amount</th>
                        <th class="px-4 py-2 text-center font-medium text-zinc-600 dark:text-zinc-300">Recurring</th>
                        <th class="px-4 py-2 text-left font-medium text-zinc-600 dark:text-zinc-300">Effective Date</th>
                        <th class="px-4 py-2 text-center font-medium text-zinc-600 dark:text-zinc-300">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->deductions as $ded)
                        <tr wire:key="ded-{{ $ded->id }}" class="border-b border-zinc-100 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/50">
                            <td class="px-4 py-2">
                                <flux:badge size="sm" color="zinc">{{ strtoupper($ded->type) }}</flux:badge>
                            </td>
                            <td class="px-4 py-2 font-medium">{{ $ded->description }}</td>
                            <td class="px-4 py-2 text-right font-semibold text-red-500">-₱{{ number_format($ded->amount, 2) }}</td>
                            <td class="px-4 py-2 text-center">
                                <flux:badge size="sm" color="{{ $ded->is_recurring ? 'blue' : 'zinc' }}">
                                    {{ $ded->is_recurring ? 'Recurring' : 'One-time' }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-2 text-zinc-500">{{ $ded->effective_date?->format('M d, Y') ?? '—' }}</td>
                            <td class="px-4 py-2">
                                <div class="flex justify-center gap-1">
                                    <flux:button wire:click="openDeductionModal({{ $ded->id }})" variant="ghost" size="sm" icon="pencil" />
                                    <flux:button wire:click="deleteDeduction({{ $ded->id }})" variant="ghost" size="sm" icon="trash" class="text-red-500" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-zinc-400">No deductions recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- CASH ADVANCE TAB --}}
    <div x-show="tab === 'cash_advance'" x-cloak>
        <div class="mb-3 flex items-center justify-between">
            @php $totalBalance = $this->cashAdvances->where('status', 'active')->sum(fn($ca) => $ca->balance); @endphp
            <p class="text-sm text-zinc-500">Outstanding balance: <span class="font-semibold text-red-500">₱{{ number_format($totalBalance, 2) }}</span></p>
            <flux:button wire:click="openCashAdvanceModal()" variant="primary" icon="plus" size="sm">Grant Cash Advance</flux:button>
        </div>

        {{-- Mobile --}}
        <div class="sm:hidden space-y-2">
            @forelse ($this->cashAdvances as $ca)
                <div wire:key="ca-m-{{ $ca->id }}" class="rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="font-medium text-zinc-800 dark:text-zinc-100">₱{{ number_format($ca->amount, 2) }}</p>
                            <p class="text-xs text-zinc-400">{{ $ca->date_granted->format('M d, Y') }} · {{ $ca->reason ?? 'No reason' }}</p>
                        </div>
                        <div class="flex items-center gap-1">
                            <flux:badge size="sm" color="{{ $ca->status === 'fully_paid' ? 'green' : 'red' }}">
                                {{ $ca->status === 'fully_paid' ? 'Paid' : '₱' . number_format($ca->balance, 2) . ' left' }}
                            </flux:badge>
                            <flux:button wire:click="openCashAdvanceModal({{ $ca->id }})" variant="ghost" size="sm" icon="pencil" />
                            <flux:button wire:click="deleteCashAdvance({{ $ca->id }})" variant="ghost" size="sm" icon="trash" class="text-red-500" />
                        </div>
                    </div>
                </div>
            @empty
                <p class="py-8 text-center text-zinc-400">No cash advances recorded.</p>
            @endforelse
        </div>

        {{-- Desktop --}}
        <div class="hidden sm:block overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="px-4 py-2 text-left font-medium text-zinc-600 dark:text-zinc-300">Date Granted</th>
                        <th class="px-4 py-2 text-left font-medium text-zinc-600 dark:text-zinc-300">Reason</th>
                        <th class="px-4 py-2 text-right font-medium text-zinc-600 dark:text-zinc-300">Amount</th>
                        <th class="px-4 py-2 text-right font-medium text-zinc-600 dark:text-zinc-300">Paid</th>
                        <th class="px-4 py-2 text-right font-medium text-zinc-600 dark:text-zinc-300">Balance</th>
                        <th class="px-4 py-2 text-center font-medium text-zinc-600 dark:text-zinc-300">Status</th>
                        <th class="px-4 py-2 text-center font-medium text-zinc-600 dark:text-zinc-300">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->cashAdvances as $ca)
                        <tr wire:key="ca-{{ $ca->id }}" class="border-b border-zinc-100 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/50">
                            <td class="px-4 py-2">{{ $ca->date_granted->format('M d, Y') }}</td>
                            <td class="px-4 py-2 text-zinc-500">{{ $ca->reason ?? '—' }}</td>
                            <td class="px-4 py-2 text-right font-medium">₱{{ number_format($ca->amount, 2) }}</td>
                            <td class="px-4 py-2 text-right text-green-600">₱{{ number_format($ca->amount_paid, 2) }}</td>
                            <td class="px-4 py-2 text-right font-semibold {{ $ca->balance > 0 ? 'text-red-500' : 'text-zinc-400' }}">
                                ₱{{ number_format($ca->balance, 2) }}
                            </td>
                            <td class="px-4 py-2 text-center">
                                <flux:badge size="sm" color="{{ $ca->status === 'fully_paid' ? 'green' : 'yellow' }}">
                                    {{ $ca->status === 'fully_paid' ? 'Fully Paid' : 'Active' }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-2">
                                <div class="flex justify-center gap-1">
                                    <flux:button wire:click="openCashAdvanceModal({{ $ca->id }})" variant="ghost" size="sm" icon="pencil" />
                                    <flux:button wire:click="deleteCashAdvance({{ $ca->id }})" variant="ghost" size="sm" icon="trash" class="text-red-500" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-zinc-400">No cash advances recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Attendance Modal --}}
    <flux:modal wire:model="showAttendanceModal" class="max-w-lg">
        <flux:heading>{{ $editingAttendanceId ? 'Edit Attendance' : 'Log Attendance' }}</flux:heading>
        <div class="mt-4 grid grid-cols-2 gap-3">
            <flux:field class="col-span-2">
                <flux:label>Date</flux:label>
                <flux:input type="date" wire:model="attDate" />
                @error('attDate') <flux:error>{{ $message }}</flux:error> @enderror
            </flux:field>
            <flux:field class="col-span-2">
                <flux:label>Status</flux:label>
                <flux:select wire:model="attStatus">
                    <flux:select.option value="present">Present</flux:select.option>
                    <flux:select.option value="absent">Absent</flux:select.option>
                    <flux:select.option value="late">Late</flux:select.option>
                    <flux:select.option value="half_day">Half Day</flux:select.option>
                    <flux:select.option value="holiday">Holiday</flux:select.option>
                    <flux:select.option value="leave">Leave</flux:select.option>
                </flux:select>
            </flux:field>
            <flux:field>
                <flux:label>Time In</flux:label>
                <flux:input type="time" wire:model="attTimeIn" />
            </flux:field>
            <flux:field>
                <flux:label>Time Out</flux:label>
                <flux:input type="time" wire:model="attTimeOut" />
            </flux:field>
            <flux:field>
                <flux:label>Hours Worked</flux:label>
                <flux:input type="number" min="0" step="0.5" wire:model="attHours" />
            </flux:field>
            <flux:field>
                <flux:label>Overtime Hours</flux:label>
                <flux:input type="number" min="0" step="0.5" wire:model="attOvertime" />
            </flux:field>
            <flux:field class="col-span-2">
                <flux:label>Notes</flux:label>
                <flux:input wire:model="attNotes" placeholder="optional" />
            </flux:field>
        </div>
        <div class="mt-4 flex justify-end gap-2">
            <flux:button wire:click="$set('showAttendanceModal', false)" variant="ghost">Cancel</flux:button>
            <flux:button wire:click="saveAttendance" variant="primary">Save</flux:button>
        </div>
    </flux:modal>

    {{-- Deduction Modal --}}
    <flux:modal wire:model="showDeductionModal" class="max-w-lg">
        <flux:heading>{{ $editingDeductionId ? 'Edit Deduction' : 'Add Deduction' }}</flux:heading>
        <div class="mt-4 grid grid-cols-2 gap-3">
            <flux:field>
                <flux:label>Type</flux:label>
                <flux:select wire:model="dedType">
                    <flux:select.option value="sss">SSS</flux:select.option>
                    <flux:select.option value="philhealth">PhilHealth</flux:select.option>
                    <flux:select.option value="pagibig">Pag-IBIG</flux:select.option>
                    <flux:select.option value="tax">Tax (BIR)</flux:select.option>
                    <flux:select.option value="cash_advance">Cash Advance</flux:select.option>
                    <flux:select.option value="loan">Loan</flux:select.option>
                    <flux:select.option value="other">Other</flux:select.option>
                </flux:select>
            </flux:field>
            <flux:field>
                <flux:label>Amount</flux:label>
                <flux:input type="number" min="0" step="0.01" wire:model="dedAmount" prefix="₱" />
                @error('dedAmount') <flux:error>{{ $message }}</flux:error> @enderror
            </flux:field>
            <flux:field class="col-span-2">
                <flux:label>Description <flux:badge size="xs" color="red">Required</flux:badge></flux:label>
                <flux:input wire:model="dedDescription" placeholder="e.g. SSS Contribution May 2026" />
                @error('dedDescription') <flux:error>{{ $message }}</flux:error> @enderror
            </flux:field>
            <flux:field>
                <flux:label>Effective Date</flux:label>
                <flux:input type="date" wire:model="dedEffectiveDate" />
            </flux:field>
            <flux:field>
                <flux:label>Recurring</flux:label>
                <flux:switch wire:model="dedRecurring" label="Deduct every payroll" />
            </flux:field>
            <flux:field class="col-span-2">
                <flux:label>Notes</flux:label>
                <flux:textarea wire:model="dedNotes" rows="2" />
            </flux:field>
        </div>
        <div class="mt-4 flex justify-end gap-2">
            <flux:button wire:click="$set('showDeductionModal', false)" variant="ghost">Cancel</flux:button>
            <flux:button wire:click="saveDeduction" variant="primary">Save</flux:button>
        </div>
    </flux:modal>

    {{-- Cash Advance Modal --}}
    <flux:modal wire:model="showCashAdvanceModal" class="max-w-lg">
        <flux:heading>{{ $editingCashAdvanceId ? 'Edit Cash Advance' : 'Grant Cash Advance' }}</flux:heading>
        <div class="mt-4 grid grid-cols-2 gap-3">
            <flux:field>
                <flux:label>Amount Granted <flux:badge size="xs" color="red">Required</flux:badge></flux:label>
                <flux:input type="number" min="0" step="0.01" wire:model="caAmount" prefix="₱" />
                @error('caAmount') <flux:error>{{ $message }}</flux:error> @enderror
            </flux:field>
            <flux:field>
                <flux:label>Amount Paid Back</flux:label>
                <flux:input type="number" min="0" step="0.01" wire:model="caAmountPaid" prefix="₱" />
            </flux:field>
            <flux:field>
                <flux:label>Date Granted</flux:label>
                <flux:input type="date" wire:model="caDateGranted" />
                @error('caDateGranted') <flux:error>{{ $message }}</flux:error> @enderror
            </flux:field>
            <flux:field>
                <flux:label>Status</flux:label>
                <flux:select wire:model="caStatus">
                    <flux:select.option value="active">Active</flux:select.option>
                    <flux:select.option value="fully_paid">Fully Paid</flux:select.option>
                </flux:select>
            </flux:field>
            <flux:field class="col-span-2">
                <flux:label>Reason</flux:label>
                <flux:input wire:model="caReason" placeholder="e.g. Emergency medical" />
            </flux:field>
            <flux:field class="col-span-2">
                <flux:label>Notes</flux:label>
                <flux:textarea wire:model="caNotes" rows="2" />
            </flux:field>
        </div>
        <div class="mt-4 flex justify-end gap-2">
            <flux:button wire:click="$set('showCashAdvanceModal', false)" variant="ghost">Cancel</flux:button>
            <flux:button wire:click="saveCashAdvance" variant="primary">Save</flux:button>
        </div>
    </flux:modal>
</div>
