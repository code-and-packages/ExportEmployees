<?php

namespace App\Jobs;

use App\Models\User;
use Generator;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Common\Exception\InvalidArgumentException;
use OpenSpout\Common\Exception\IOException;
use OpenSpout\Common\Exception\UnsupportedTypeException;
use OpenSpout\Writer\Exception\WriterNotOpenedException;
use Rap2hpoutre\FastExcel\FastExcel;

class EmployeesExportFile implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $chunkSize;
    public string $folder;
    private string $storagePath;

    public function __construct($chunkSize, $folder, $storagePath)
    {
        $this->chunkSize = $chunkSize;
        $this->folder = $folder;
        $this->storagePath = $storagePath;
    }

    /**
     * @return void
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function handle(): void
    {
        $users = User::query()->take($this->chunkSize)->get();
        Storage::disk('local')->makeDirectory($this->folder);

        (new FastExcel($this->usersGenerator($users)))->export($this->storagePath, function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'age' => $user->age,
                'salary' => $user->salary,
            ];
        });

    }

    /**
     * @param $users
     * @return Generator
     */
    private function usersGenerator($users): Generator
    {
        foreach ($users as $user) {
            yield $user;
        }
    }

}

