<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * White-label baseline CATALOG seeder.
 *
 * Ships the master-app catalog (store wares: avatar frames / chat bubbles /
 * entry effects, emojis + categories, VIP levels + privileges + their links,
 * CP relations) so a FRESH client install is never empty. Data lives as JSON
 * under database/seeders/data/ and is inserted idempotently (updateOrInsert),
 * filtered to the table's real columns so model-appended attributes never break
 * the insert.
 *
 * IMPORTANT (white-label provisioning): the image FILES referenced by these
 * rows (show_img/img2, emoji svga, vip imgs, privilege imgs, cp images) must
 * also be copied into the CLIENT's object storage under the same relative paths
 * — run the asset-sync step alongside this seeder.
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $dir = database_path('seeders/data');

        $ovipTable = (new \Modules\Vip\Entities\OVip)->getTable();

        // Single-PK catalogs (match by id).
        $this->seedById("$dir/countries.json", 'countries');
        $this->seedById("$dir/cats.json", 'emoji_categories');
        $this->seedById("$dir/emojis.json", 'emojis');
        $this->seedById("$dir/store_wares.json", 'wares');
        $this->seedById("$dir/ovips.json", $ovipTable);
        $this->seedById("$dir/vip_privileges.json", 'vip_privileges');
        $this->seedById("$dir/cp_relations.json", 'cp_relations');

        // Pivot (composite match).
        $this->seedPivot("$dir/vip_prev.json", 'vip_prev', ['o_vip_id', 'o_vip_privilege_id']);
    }

    private function seedById(string $file, string $table, string $key = 'id'): void
    {
        foreach ($this->rows($file, $table) as $r) {
            DB::table($table)->updateOrInsert([$key => $r[$key]], $r);
        }
    }

    private function seedPivot(string $file, string $table, array $keys): void
    {
        foreach ($this->rows($file, $table) as $r) {
            $match = [];
            foreach ($keys as $k) {
                $match[$k] = $r[$k] ?? null;
            }
            DB::table($table)->updateOrInsert($match, $r);
        }
    }

    /** Load JSON rows and keep only columns that actually exist on the table. */
    private function rows(string $file, string $table): array
    {
        if (!file_exists($file) || !Schema::hasTable($table)) {
            return [];
        }
        $rows = json_decode(file_get_contents($file), true) ?: [];
        $cols = array_flip(Schema::getColumnListing($table));
        return array_map(function ($r) use ($cols) {
            $r = array_intersect_key($r, $cols);
            // toJson() emits ISO-8601 timestamps (with T/Z) that MySQL rejects on
            // raw insert — drop them and let the DB defaults apply.
            unset($r['created_at'], $r['updated_at'], $r['deleted_at']);
            // JSON columns decode back to arrays via toJson(); re-encode so the
            // raw DB insert stores them instead of throwing "Array to string".
            foreach ($r as $k => $v) {
                if (is_array($v)) {
                    $r[$k] = json_encode($v);
                }
            }
            return $r;
        }, $rows);
    }
}
