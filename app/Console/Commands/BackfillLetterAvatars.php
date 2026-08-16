<?php

namespace App\Console\Commands;

use App\Models\Family;
use App\Models\Profile;
use App\Models\Room;
use App\Models\User;
use App\Services\LetterAvatarService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off / repeatable backfill: generate and store letter avatars for existing
 * entities whose photo field is empty. Idempotent — already-set photos are
 * skipped, so it is safe to re-run. Processes in chunks to stay light.
 */
class BackfillLetterAvatars extends Command
{
    protected $signature = 'letter-avatar:backfill
        {--entity=all : Which entity to backfill: all|user|room|family|agency}
        {--chunk=200 : Rows per chunk}
        {--limit=0 : Stop after N generations (0 = no limit)}
        {--dry-run : Report what would change without writing}';

    protected $description = 'Generate stored letter avatars for entities with an empty photo field';

    private int $generated = 0;
    private int $limit = 0;
    private bool $dry = false;

    public function handle(LetterAvatarService $service): int
    {
        $entity = (string) $this->option('entity');
        $chunk = max(1, (int) $this->option('chunk'));
        $this->limit = max(0, (int) $this->option('limit'));
        $this->dry = (bool) $this->option('dry-run');

        $targets = $entity === 'all'
            ? ['user', 'room', 'family', 'agency']
            : [$entity];

        foreach ($targets as $t) {
            if ($this->reachedLimit()) {
                break;
            }

            match ($t) {
                'user' => $this->backfillUsers($service, $chunk),
                'room' => $this->backfillRooms($service, $chunk),
                'family' => $this->backfillFamilies($service, $chunk),
                'agency' => $this->backfillAgencies($service, $chunk),
                default => $this->error("Unknown entity: {$t}"),
            };
        }

        $this->info(($this->dry ? '[dry-run] ' : '') . "Done. Generated: {$this->generated}");

        return self::SUCCESS;
    }

    private function reachedLimit(): bool
    {
        return $this->limit > 0 && $this->generated >= $this->limit;
    }

    private function backfillUsers(LetterAvatarService $service, int $chunk): void
    {
        $excluded = config('letter_avatar.excluded_user_ids', []);

        // Profiles with no avatar whose user has a name.
        Profile::query()
            ->where(fn($q) => $q->whereNull('avatar')->orWhere('avatar', ''))
            ->orderBy('id')
            ->chunkById($chunk, function ($profiles) use ($service, $excluded) {
                $userIds = $profiles->pluck('user_id')->filter()->all();
                $names = User::whereIn('id', $userIds)->pluck('name', 'id');

                foreach ($profiles as $profile) {
                    if ($this->reachedLimit()) {
                        return false;
                    }
                    if (in_array((int) $profile->user_id, $excluded, true)) {
                        continue;
                    }

                    $name = trim((string) ($names[$profile->user_id] ?? ''));
                    if ($name === '') {
                        continue;
                    }

                    $this->persist(
                        fn($path) => $profile->update(['avatar' => $path]),
                        $service,
                        'profile',
                        $name,
                        $profile->user_id,
                        "user#{$profile->user_id}"
                    );
                }

                return true;
            });
    }

    private function backfillRooms(LetterAvatarService $service, int $chunk): void
    {
        Room::query()
            ->withoutAppends()
            ->where(fn($q) => $q->whereNull('room_cover')->orWhere('room_cover', ''))
            ->orderBy('id')
            ->chunkById($chunk, function ($rooms) use ($service) {
                foreach ($rooms as $room) {
                    if ($this->reachedLimit()) {
                        return false;
                    }
                    $this->persist(
                        fn($path) => $room->update(['room_cover' => $path]),
                        $service,
                        'rooms',
                        $room->room_name,
                        $room->id,
                        "room#{$room->id}"
                    );
                }

                return true;
            });
    }

    private function backfillFamilies(LetterAvatarService $service, int $chunk): void
    {
        Family::query()
            ->where(fn($q) => $q->whereNull('image')->orWhere('image', ''))
            ->orderBy('id')
            ->chunkById($chunk, function ($families) use ($service) {
                foreach ($families as $family) {
                    if ($this->reachedLimit()) {
                        return false;
                    }
                    $this->persist(
                        fn($path) => $family->update(['image' => $path]),
                        $service,
                        'families',
                        $family->name,
                        $family->id,
                        "family#{$family->id}"
                    );
                }

                return true;
            });
    }

    /**
     * Covers both host agencies (type 1) and shipping agencies (type 2): the
     * shared 'agencies' table is queried directly to bypass per-type global
     * scopes and the SoftDeletes column is respected.
     */
    private function backfillAgencies(LetterAvatarService $service, int $chunk): void
    {
        DB::table('agencies')
            ->whereNull('deleted_at')
            ->where(fn($q) => $q->whereNull('img')->orWhere('img', ''))
            ->orderBy('id')
            ->chunkById($chunk, function ($agencies) use ($service) {
                foreach ($agencies as $agency) {
                    if ($this->reachedLimit()) {
                        return false;
                    }
                    $this->persist(
                        fn($path) => DB::table('agencies')->where('id', $agency->id)->update(['img' => $path]),
                        $service,
                        'agency',
                        $agency->name,
                        $agency->id,
                        "agency#{$agency->id}"
                    );
                }

                return true;
            });
    }

    private function persist(callable $save, LetterAvatarService $service, string $folder, ?string $name, int|string $seed, string $label): void
    {
        if ($this->dry) {
            $this->line("[dry-run] would generate {$label} ({$folder}) name=" . trim((string) $name));
            $this->generated++;
            return;
        }

        $path = $service->generate($folder, $name, $seed);
        if ($path) {
            $save($path);
            $this->generated++;
            if ($this->generated % 50 === 0) {
                $this->info("Generated {$this->generated}...");
            }
        } else {
            $this->warn("Failed to generate for {$label}");
        }
    }
}
