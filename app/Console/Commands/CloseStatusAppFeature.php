<?php

namespace App\Console\Commands;

use App\Models\AppFeature;
use Illuminate\Console\Command;

class CloseStatusAppFeature extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'feature:close {slugs*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'close  status of AppFeature';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $slugs = $this->argument('slugs');
        foreach ($slugs as $slug) {
            $appFeature  = AppFeature::where('slug', $slug)->first();
            if (!$appFeature) {
                AppFeature::create([
                    "name" => $slug,
                    "slug" => $slug,
                    "status" => 0,
                ]);
            } else {
                $appFeature->status = 0;
                $appFeature->save();
            }
        }
    }
}
