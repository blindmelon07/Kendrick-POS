<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\Process\Process;

new #[Title('Backup & Restore')] class extends Component
{
    public bool $showRestoreModal = false;
    public bool $showDeleteModal  = false;
    public ?string $targetFile    = null;

    /** @return list<array{path:string,name:string,size:string,modified:\Carbon\Carbon}> */
    #[Computed]
    public function backups(): array
    {
        $dir  = config('backup.backup.name');
        $disk = Storage::disk('local');

        if (! $disk->exists($dir)) {
            return [];
        }

        return collect($disk->files($dir))
            ->filter(fn (string $f) => str_ends_with($f, '.zip'))
            ->map(fn (string $f) => [
                'path'     => $f,
                'name'     => basename($f),
                'size'     => $this->formatBytes($disk->size($f)),
                'modified' => \Carbon\Carbon::createFromTimestamp($disk->lastModified($f)),
            ])
            ->sortByDesc('modified')
            ->values()
            ->toArray();
    }

    public function runBackup(bool $dbOnly = true): void
    {
        try {
            $exitCode = Artisan::call('backup:run', $dbOnly ? ['--only-db' => true] : []);

            if ($exitCode !== 0) {
                $this->dispatch('notify', message: 'Backup failed. Check logs for details.');

                return;
            }

            unset($this->backups);
            $this->dispatch('notify', message: $dbOnly ? 'Database backup created.' : 'Full backup created.');
        } catch (\Throwable $e) {
            $this->dispatch('notify', message: 'Backup error: ' . $e->getMessage());
        }
    }

    public function download(string $path): mixed
    {
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path);
    }

    public function confirmRestore(string $path): void
    {
        $this->targetFile        = $path;
        $this->showRestoreModal  = true;
    }

    public function restore(): void
    {
        if (! $this->targetFile) {
            return;
        }

        $localPath = Storage::disk('local')->path($this->targetFile);

        $zip = new \ZipArchive();

        if ($zip->open($localPath) !== true) {
            $this->dispatch('notify', message: 'Could not open the backup archive.');
            $this->showRestoreModal = false;

            return;
        }

        // Find SQL dump inside the archive (spatie stores it in db-dumps/)
        $sqlContent = null;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);

            if ($stat && str_ends_with($stat['name'], '.sql')) {
                $sqlContent = $zip->getFromIndex($i);
                break;
            }
        }

        $zip->close();

        if ($sqlContent === null || $sqlContent === '') {
            $this->dispatch('notify', message: 'No SQL dump found in this backup archive.');
            $this->showRestoreModal = false;

            return;
        }

        // Write to a temp file and restore via mysql CLI
        $tmpFile = tempnam(sys_get_temp_dir(), 'pk_restore_') . '.sql';
        file_put_contents($tmpFile, $sqlContent);

        try {
            $this->runMysqlRestore($tmpFile);
            $this->dispatch('notify', message: 'Database restored successfully!');
        } catch (\Throwable $e) {
            $this->dispatch('notify', message: 'Restore failed: ' . $e->getMessage());
        } finally {
            @unlink($tmpFile);
        }

        $this->showRestoreModal = false;
        $this->targetFile       = null;
    }

    public function confirmDelete(string $path): void
    {
        $this->targetFile       = $path;
        $this->showDeleteModal  = true;
    }

    public function deleteBackup(): void
    {
        if (! $this->targetFile) {
            return;
        }

        Storage::disk('local')->delete($this->targetFile);
        unset($this->backups);
        $this->showDeleteModal = false;
        $this->targetFile      = null;
        $this->dispatch('notify', message: 'Backup deleted.');
    }

    /** @throws \RuntimeException */
    private function runMysqlRestore(string $sqlFile): void
    {
        $db  = config('database.connections.mysql');
        $bin = $this->findMysqlBin();

        $process = new Process([
            $bin,
            '-h', $db['host'] ?? '127.0.0.1',
            '-P', (string) ($db['port'] ?? 3306),
            '-u', $db['username'],
            '-p' . ($db['password'] ?? ''),
            $db['database'],
        ]);

        $process->setInput(fopen($sqlFile, 'rb'));
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException(trim($process->getErrorOutput()) ?: 'mysql exited with code ' . $process->getExitCode());
        }
    }

    private function findMysqlBin(): string
    {
        // Prefer the env-configured mysqldump path (same mysql/ bin dir)
        $envPath = env('MYSQLDUMP_PATH');

        if ($envPath) {
            $candidate = rtrim($envPath, '/\\') . DIRECTORY_SEPARATOR . 'mysql.exe';

            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        $candidates = [
            'C:\\xampp2\\mysql\\bin\\mysql.exe',
            'C:\\xampp\\mysql\\bin\\mysql.exe',
            'C:\\wamp64\\bin\\mysql\\mysql8.0.31\\bin\\mysql.exe',
            'mysql',
        ];

        foreach ($candidates as $path) {
            if ($path === 'mysql') {
                exec('where mysql 2>NUL', $out, $code);

                if ($code === 0) {
                    return 'mysql';
                }
            } elseif (file_exists($path)) {
                return $path;
            }
        }

        throw new \RuntimeException('MySQL client binary not found. Please ensure mysql is available in PATH.');
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1_073_741_824) {
            return round($bytes / 1_073_741_824, 2) . ' GB';
        }

        if ($bytes >= 1_048_576) {
            return round($bytes / 1_048_576, 2) . ' MB';
        }

        if ($bytes >= 1_024) {
            return round($bytes / 1_024, 2) . ' KB';
        }

        return $bytes . ' B';
    }
};
?>

<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Backup & Restore</flux:heading>
            <flux:subheading>Manage database and application backups.</flux:subheading>
        </div>
        <div class="flex flex-wrap gap-2">
            <flux:button
                wire:click="runBackup(true)"
                wire:loading.attr="disabled"
                wire:target="runBackup"
                icon="circle-stack"
                variant="primary"
            >
                <span wire:loading.remove wire:target="runBackup">Backup Database</span>
                <span wire:loading wire:target="runBackup">Running...</span>
            </flux:button>
            <flux:button
                wire:click="runBackup(false)"
                wire:loading.attr="disabled"
                wire:target="runBackup"
                icon="archive-box"
            >
                <span wire:loading.remove wire:target="runBackup">Full Backup</span>
                <span wire:loading wire:target="runBackup">Running...</span>
            </flux:button>
        </div>
    </div>

    {{-- Loading indicator --}}
    <div wire:loading wire:target="runBackup" class="flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-300">
        <flux:icon name="arrow-path" class="size-4 animate-spin" />
        Creating backup — this may take a moment...
    </div>

    {{-- Backup list --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">File</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Created</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Size</th>
                    <th class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-300">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->backups as $backup)
                    <tr wire:key="backup-{{ $backup['name'] }}" class="border-b border-zinc-100 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/50">
                        <td class="px-4 py-3 font-mono text-xs text-zinc-700 dark:text-zinc-300">
                            {{ $backup['name'] }}
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                            {{ $backup['modified']->format('M d, Y H:i') }}
                            <span class="ml-1 text-zinc-400 dark:text-zinc-500">({{ $backup['modified']->diffForHumans() }})</span>
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $backup['size'] }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-2">
                                <flux:button
                                    size="sm"
                                    wire:click="download('{{ $backup['path'] }}')"
                                    icon="arrow-down-tray"
                                >
                                    Download
                                </flux:button>
                                <flux:button
                                    size="sm"
                                    variant="filled"
                                    wire:click="confirmRestore('{{ $backup['path'] }}')"
                                    icon="arrow-uturn-left"
                                >
                                    Restore
                                </flux:button>
                                <flux:button
                                    size="sm"
                                    variant="danger"
                                    wire:click="confirmDelete('{{ $backup['path'] }}')"
                                    icon="trash"
                                >
                                    Delete
                                </flux:button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-12 text-center">
                            <flux:icon name="circle-stack" class="mx-auto mb-3 size-10 text-zinc-300 dark:text-zinc-600" />
                            <p class="text-zinc-400 dark:text-zinc-500">No backups found. Create your first backup above.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Restore Confirmation Modal --}}
    <flux:modal wire:model="showRestoreModal" class="w-full max-w-md">
        <div class="space-y-4">
            <flux:heading>Restore Database</flux:heading>

            <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-800 dark:bg-amber-950">
                <div class="flex gap-2">
                    <flux:icon name="exclamation-triangle" class="mt-0.5 size-4 shrink-0 text-amber-600 dark:text-amber-400" />
                    <p class="text-sm text-amber-700 dark:text-amber-300">
                        <strong>Warning:</strong> This will overwrite the current database with the selected backup.
                        All data created after the backup date will be permanently lost.
                    </p>
                </div>
            </div>

            @if ($targetFile)
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    Restoring from: <span class="font-mono font-medium">{{ basename($targetFile) }}</span>
                </p>
            @endif

            <div class="flex justify-end gap-2">
                <flux:button wire:click="$set('showRestoreModal', false)">Cancel</flux:button>
                <flux:button
                    variant="danger"
                    wire:click="restore"
                    wire:loading.attr="disabled"
                    wire:target="restore"
                >
                    <span wire:loading.remove wire:target="restore">Yes, Restore</span>
                    <span wire:loading wire:target="restore">Restoring...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Delete Confirmation Modal --}}
    <flux:modal wire:model="showDeleteModal" class="w-full max-w-sm">
        <div class="space-y-4">
            <flux:heading>Delete Backup</flux:heading>
            <flux:text>Are you sure you want to permanently delete this backup file?</flux:text>
            <div class="flex justify-end gap-2">
                <flux:button wire:click="$set('showDeleteModal', false)">Cancel</flux:button>
                <flux:button variant="danger" wire:click="deleteBackup">Delete</flux:button>
            </div>
        </div>
    </flux:modal>

</div>
