<?php

namespace App\Http\Controllers;

use App\Jobs\AppendEmployees;
use App\Jobs\EmployeesExportFile;
use App\Models\User;
use Illuminate\Bus\Batch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ExportController extends Controller
{
    private int $chunkSize = 10000;
    private string $folderName;
    private string $storagePath;
    private string $fileName = "employees";

    /**
     * @return void
     */
    private function setFolderName(): void
    {
        $this->folderName = now()->toDateString() . '(' . time() . ')';
    }

    /**
     * @return void
     */
    private function setStoragePath(): void
    {
        $this->storagePath = storage_path("app/{$this->folderName}/{$this->fileName}.csv");
    }

    public function __construct()
    {
        $this->setFolderName();
        $this->setStoragePath();
    }

    /**
     * @return RedirectResponse
     * @throws Throwable
     */
    public function export(): RedirectResponse
    {
        $usersCount = User::query()->count();
        $numberOfChunks = ceil($usersCount / $this->chunkSize);

        $batches = [
            new EmployeesExportFile($this->chunkSize, $this->folderName, $this->storagePath)
        ];

        if ($usersCount > $this->chunkSize) {
            for ($numberOfChunks = $numberOfChunks - 1; $numberOfChunks > 0; $numberOfChunks--) {
                $batches[] = new AppendEmployees($numberOfChunks, $this->chunkSize, $this->folderName, $this->storagePath);
            }
        }

        Bus::batch($batches)->name('Export Employees')->then(function (Batch $batch) {
            $path = "exports/{$this->folderName}/{$this->fileName}.csv";
            Storage::disk('public')->put($path, file_get_contents($this->storagePath));
        })->catch(function (Batch $batch, Throwable $e) {
            //
        })->finally(function (Batch $batch) {
            Storage::disk('local')->deleteDirectory($this->folderName);
        })->dispatch();

        return redirect()->back();
    }
}
