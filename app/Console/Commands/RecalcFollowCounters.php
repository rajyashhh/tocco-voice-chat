<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalcFollowCounters extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:recalc-follow-counters {--chunk=5000 : Users per batch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate users.number_of_fans / number_of_followings / number_of_friends from the follows table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $chunk = max(1, (int) $this->option('chunk'));

        $bounds = DB::table('users')->selectRaw('MIN(id) as min_id, MAX(id) as max_id')->first();
        if (!$bounds || $bounds->min_id === null) {
            $this->info('No users found.');
            return 0;
        }

        $corrected = 0;
        $from = (int) $bounds->min_id;
        $maxId = (int) $bounds->max_id;

        while ($from <= $maxId) {
            $to = $from + $chunk - 1;

            $corrected += DB::update("
                UPDATE users u
                SET u.number_of_fans = (
                        SELECT COUNT(*) FROM follows f
                        WHERE f.followed_user_id = u.id
                    ),
                    u.number_of_followings = (
                        SELECT COUNT(*) FROM follows f
                        WHERE f.user_id = u.id
                    ),
                    u.number_of_friends = (
                        SELECT COUNT(*) FROM follows f
                        JOIN follows f2
                            ON f2.user_id = f.followed_user_id
                           AND f2.followed_user_id = f.user_id
                           AND f2.status = 1
                        JOIN (SELECT id FROM users WHERE deleted_at IS NULL) fu
                            ON fu.id = f.followed_user_id
                        WHERE f.user_id = u.id AND f.status = 1
                    )
                WHERE u.id BETWEEN ? AND ?
            ", [$from, $to]);

            $from = $to + 1;
        }

        $this->info("Corrected {$corrected} users.");

        return 0;
    }
}
