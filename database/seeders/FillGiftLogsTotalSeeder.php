<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class FillGiftLogsTotalSeeder extends Seeder
{
    private const CHUNK_SIZE = 1000;

    public function run(): void
    {
        $this->command->info('🔍 جاري تحميل أسعار الهدايا من جدول gifts...');

        $giftPrices = DB::table('gifts')
            ->select('id', 'price')
            ->get()
            ->pluck('price', 'id')
            ->toArray();

        $this->command->info('✅ تم تحميل ' . count($giftPrices) . ' هدية.');

        $total = DB::table('gift_logs')
            ->whereNull('total')
            ->orWhere('total', 0)
            ->count();

        $this->command->info("📦 عدد السجلات التي تحتاج تحديث: {$total}");

        if ($total === 0) {
            $this->command->warn('لا توجد سجلات تحتاج تحديث.');
            return;
        }

        $bar = $this->command->getOutput()->createProgressBar($total);
        $bar->start();

        $updated = 0;

        DB::table('gift_logs')
            ->select('id', 'giftId', 'giftNum')
            ->where(function ($q) {
                $q->whereNull('total')->orWhere('total', 0);
            })
            ->orderBy('id')
            ->chunkById(self::CHUNK_SIZE, function ($logs) use ($giftPrices, $bar, &$updated) {

                foreach ($logs as $log) {
                    $price = $giftPrices[$log->giftId] ?? null;

                    if ($price === null) {
                        $bar->advance();
                        continue;
                    }

                    $rowTotal = $price;

                    DB::table('gift_logs')
                        ->where('id', $log->id)
                        ->update(['total' => $rowTotal]);

                    $updated++;
                    $bar->advance();
                }
            }, 'id');

        $bar->finish();
        $this->command->newLine(2);
        $this->command->info("✅ تم تحديث {$updated} سجل بنجاح.");
    }
}
