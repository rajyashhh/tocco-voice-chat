<?php
/**
 * Isolated proof for the atomic conditional balance update used by the game callbacks.
 * Runs on an in-memory SQLite DB — touches NO real database and does NOT boot Laravel.
 *
 * It validates the core invariant the whole 504 fix relies on:
 *   UPDATE users SET di = di - :coin WHERE id = :id AND di >= :coin
 *   - affectedRows == 1  => deduct happened, balance stayed >= 0
 *   - affectedRows == 0  => insufficient funds, balance untouched (no negative balance ever)
 *
 * Run:  php tests/atomic_balance_proof.php
 */

function assertTrue(bool $cond, string $msg): void
{
    if (!$cond) {
        fwrite(STDERR, "FAIL: $msg\n");
        exit(1);
    }
    echo "PASS: $msg\n";
}

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, di INTEGER NOT NULL)');
$pdo->exec('CREATE TABLE coin_game_users (order_id TEXT UNIQUE, user_id INTEGER, coins INTEGER, type INTEGER)');
$pdo->exec('INSERT INTO users (id, di) VALUES (1, 100)');

/** Mirrors type==1 (deduct) in the controller. Returns true on success. */
$deduct = function (int $uid, int $coin) use ($pdo): bool {
    $stmt = $pdo->prepare('UPDATE users SET di = di - :coin WHERE id = :id AND di >= :coin');
    $stmt->execute([':coin' => $coin, ':id' => $uid]);
    return $stmt->rowCount() === 1;
};

/** Mirrors type==2 (add). */
$add = function (int $uid, int $coin) use ($pdo): bool {
    $stmt = $pdo->prepare('UPDATE users SET di = di + :coin WHERE id = :id');
    $stmt->execute([':coin' => $coin, ':id' => $uid]);
    return $stmt->rowCount() === 1;
};

$balance = fn(int $uid) => (int) $pdo->query("SELECT di FROM users WHERE id = $uid")->fetchColumn();

// --- Test 1: serialized deducts never go negative, exactly the affordable number succeed ---
$ok = 0;
for ($i = 0; $i < 5; $i++) {
    if ($deduct(1, 30)) $ok++;
}
// 100 -> 70 -> 40 -> 10 (4th: 10 < 30 fail, 5th fail)  => 3 success, balance 10
assertTrue($ok === 3, "exactly 3 of 5 deducts(30) on balance 100 succeed (got $ok)");
assertTrue($balance(1) === 10, "balance is 10 after deducts, never negative (got {$balance(1)})");

// --- Test 2: a deduct larger than balance is rejected and leaves balance untouched ---
$before = $balance(1);
$res = $deduct(1, 999);
assertTrue($res === false, "deduct(999) on balance 10 is rejected");
assertTrue($balance(1) === $before, "balance unchanged after rejected deduct (got {$balance(1)})");

// --- Test 3: add credits correctly ---
$add(1, 50);
assertTrue($balance(1) === 60, "balance is 60 after add(50) (got {$balance(1)})");

// --- Test 4: idempotency guard via UNIQUE(order_id) — second insert of same order fails ---
$insertOrder = function (string $orderId) use ($pdo): bool {
    try {
        $stmt = $pdo->prepare('INSERT INTO coin_game_users (order_id, user_id, coins, type) VALUES (:o, 1, 30, 0)');
        $stmt->execute([':o' => $orderId]);
        return true;
    } catch (PDOException $e) {
        return false; // duplicate order_id
    }
};
assertTrue($insertOrder('ORDER_A') === true, "first insert of ORDER_A succeeds");
assertTrue($insertOrder('ORDER_A') === false, "duplicate insert of ORDER_A is blocked by UNIQUE(order_id)");

echo "\nAll atomic-balance proofs passed.\n";
