<?php

namespace App\Console\Commands;

use App\Models\AppFeature;
use Illuminate\Console\Command;

class OpenStatusAppFeature extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'feature:open {slugs*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Open  status of AppFeature';

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
                    "status" => 1,
                ]);
            } else {
                $appFeature->status = 1;
                $appFeature->save();
            }
        }
    }
}
