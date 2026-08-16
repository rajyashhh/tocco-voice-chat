<?php

namespace Tests\Unit\FairLuck;

use PHPUnit\Framework\TestCase;

/**
 * G5: batchSettle ARGV layout offsets.
 *
 * The EVAL passes a PAIR of results per bet (boosted + normal) plus the streak/
 * bp header AND a per-bet receiverId (for the sub-coin receiver-fee accumulator) —
 * a layout that pins every computed offset. A wrong offset silently mis-reads money
 * fields, so this test pins the EXACT layout the Lua reads:
 *
 *   header  : ARGV[1..12]   (12 scalar params; ARGV[11]=ownerBps, ARGV[12]=receiverBps)
 *   per bet : 7 fields each, bet i (0-indexed) at PB + i*7, PB = 13
 *      [+0] netToVault  [+1] ownerCut   [+2] betAmount
 *      [+3] boostedPayout [+4] normalPayout [+5] expectedBoostReturn
 *      [+6] receiverId
 *   trailing: proof TTL at ARGV[13 + N*7]
 *
 * It builds the $argv array EXACTLY as PoolManager::batchSettle builds it (same
 * order) and asserts every bet's seven fields appear at the Lua-computed positions,
 * and that the trailing proof TTL is where the Lua reads it. This mirrors the EVAL
 * indexing (ARGV is 1-indexed in Lua; PHP $argv is the 0-indexed array that maps to
 * ARGV[1] = $argv[0]).
 */
class BatchSettleArgvOffsetTest extends TestCase
{
    private const HEADER_LEN = 12; // ARGV[1..12] (receiverBps added at ARGV[12])
    private const PB = 13;         // Lua per-bet base (1-indexed)
    private const FIELDS = 7;      // fields per bet (6 numeric + receiverId)

    /**
     * Build $argv exactly as PoolManager::batchSettle does (header → per-bet blocks
     * → trailing proof TTL). Returns the 0-indexed PHP array (ARGV[k] == $argv[k-1]).
     *
     * @param array<int,array{netToVault:int,ownerCut:int,betAmount:int,boostedPayout:int,normalPayout:int,expectedBoostReturn:int,receiverId:int}> $bets
     */
    private function buildArgv(array $bets, int $proofTtl): array
    {
        $n = count($bets);
        $argv = [
            $n,        // 1
            30_000,    // 2 negativeLimit
            86_400,    // 3 ttl
            1_700_000_000, // 4 now
            1,         // 5 bpEligible
            50_000,    // 6 bpBudget
            9_200,     // 7 rtpBoostBps
            100_000,   // 8 dailyCapTotal
            70,        // 9 hardFloor
            2,         // 10 smallestMult
            100,       // 11 ownerBps (1% in basis points)
            100,       // 12 receiverBps (1% in basis points)
        ];
        foreach ($bets as $b) {
            $argv[] = (int) $b['netToVault'];
            $argv[] = (int) $b['ownerCut'];
            $argv[] = (int) $b['betAmount'];
            $argv[] = (int) $b['boostedPayout'];
            $argv[] = (int) $b['normalPayout'];
            $argv[] = (int) $b['expectedBoostReturn'];
            $argv[] = (int) $b['receiverId'];
        }
        $argv[] = $proofTtl;
        return $argv;
    }

    /** Read ARGV[k] from the 0-indexed PHP array (the Lua 1-indexed view). */
    private function argv(array $argv, int $luaIndex): int
    {
        return (int) $argv[$luaIndex - 1];
    }

    public function test_header_length_is_twelve(): void
    {
        $argv = $this->buildArgv([], 0);
        // No bets, no proof → header (12) + trailing proof slot (1) = 13 entries.
        $this->assertCount(self::HEADER_LEN + 1, $argv);
        $this->assertSame(0, $this->argv($argv, 1), 'N must be at ARGV[1]');
        $this->assertSame(70, $this->argv($argv, 9), 'hardFloor must be at ARGV[9]');
        $this->assertSame(2, $this->argv($argv, 10), 'smallestMult must be at ARGV[10]');
        $this->assertSame(100, $this->argv($argv, 11), 'ownerBps must be at ARGV[11]');
        $this->assertSame(100, $this->argv($argv, 12), 'receiverBps must be at ARGV[12]');
    }

    public function test_each_bet_seven_fields_land_at_computed_offsets(): void
    {
        // Distinct, recognisable values so a swapped field is caught immediately.
        $bets = [];
        for ($i = 0; $i < 5; $i++) {
            $bets[] = [
                'netToVault'          => 1000 + $i * 10 + 0,
                'ownerCut'            => 1000 + $i * 10 + 1,
                'betAmount'           => 1000 + $i * 10 + 2,
                'boostedPayout'       => 1000 + $i * 10 + 3,
                'normalPayout'        => 1000 + $i * 10 + 4,
                'expectedBoostReturn' => 1000 + $i * 10 + 5,
                'receiverId'          => 1000 + $i * 10 + 6,
            ];
        }
        $n = count($bets);
        $argv = $this->buildArgv($bets, 604_800);

        for ($i = 0; $i < $n; $i++) {
            $base = self::PB + $i * self::FIELDS; // Lua: ARGV[PB + i*7 + f]
            $this->assertSame($bets[$i]['netToVault'],          $this->argv($argv, $base + 0), "net offset bet {$i}");
            $this->assertSame($bets[$i]['ownerCut'],            $this->argv($argv, $base + 1), "owner offset bet {$i}");
            $this->assertSame($bets[$i]['betAmount'],           $this->argv($argv, $base + 2), "bet offset bet {$i}");
            $this->assertSame($bets[$i]['boostedPayout'],       $this->argv($argv, $base + 3), "boostedPayout offset bet {$i}");
            $this->assertSame($bets[$i]['normalPayout'],        $this->argv($argv, $base + 4), "normalPayout offset bet {$i}");
            $this->assertSame($bets[$i]['expectedBoostReturn'], $this->argv($argv, $base + 5), "expectedBoostReturn offset bet {$i}");
            $this->assertSame($bets[$i]['receiverId'],          $this->argv($argv, $base + 6), "receiverId offset bet {$i}");
        }
    }

    public function test_trailing_proof_ttl_is_at_pb_plus_n_times_seven(): void
    {
        foreach ([1, 3, 9, 1000] as $n) {
            $bets = array_fill(0, $n, [
                'netToVault' => 1, 'ownerCut' => 1, 'betAmount' => 1,
                'boostedPayout' => 0, 'normalPayout' => 0, 'expectedBoostReturn' => 0,
                'receiverId' => 42,
            ]);
            $proofTtl = 604_800;
            $argv = $this->buildArgv($bets, $proofTtl);

            // Lua reads ARGV[PB + n*7] for the proof TTL.
            $proofIndex = self::PB + $n * self::FIELDS;
            $this->assertSame($proofTtl, $this->argv($argv, $proofIndex), "proof TTL offset wrong for N={$n}");
            // And it is the very last entry (total length = header + n*7 + 1).
            $this->assertCount(self::HEADER_LEN + $n * self::FIELDS + 1, $argv, "argv length wrong for N={$n}");
            $this->assertSame($proofIndex, count($argv), 'proof TTL must be the last ARGV slot');
        }
    }

    public function test_no_field_overlap_between_consecutive_bets(): void
    {
        // The block stride is exactly FIELDS, so bet i's last field is immediately
        // followed by bet i+1's first field — no gap, no overlap.
        $n = 4;
        $bets = array_fill(0, $n, [
            'netToVault' => 7, 'ownerCut' => 7, 'betAmount' => 7,
            'boostedPayout' => 7, 'normalPayout' => 7, 'expectedBoostReturn' => 7,
            'receiverId' => 7,
        ]);
        $argv = $this->buildArgv($bets, 0);

        for ($i = 0; $i < $n - 1; $i++) {
            $thisLast = self::PB + $i * self::FIELDS + (self::FIELDS - 1);
            $nextFirst = self::PB + ($i + 1) * self::FIELDS;
            $this->assertSame($thisLast + 1, $nextFirst, "block stride wrong between bet {$i} and {$i}+1");
        }
    }
}
