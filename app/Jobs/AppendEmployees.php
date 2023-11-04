<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AppendEmployees implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public $chunkIndex, public $chunkSize, public $folder, public $storagePath)
    {
        //
    }

    /**
     * @return void
     */
    public function handle(): void
    {
        $users = User::query()->skip($this->chunkIndex * $this->chunkSize)->take($this->chunkSize)->get()
            ->map(function ($user) {
                return [
                    $user->id,
                    $user->name,
                    $user->age,
                    $user->salary,
                ];
            });

        $open = fopen($this->storagePath, 'a+');
        foreach ($users as $user) {
            fputcsv($open, $user);
        }
        fclose($open);
    }
}
