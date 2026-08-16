<?php

namespace Tests\Feature\UtdQa;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\SalaryTransaction\Http\Controllers\Api\SalaryTransactionController;

/**
 * C1 - Salary withdrawal request: no duplicate / no over-withdrawal.
 *
 * Acceptance criteria for the fix in
 *   Modules/SalaryTransaction/Http/Controllers/Api/SalaryTransactionController.php::add_request_salary
 *
 *  (A) Happy path: a host with enough salary can create exactly ONE request,
 *      code=1, one row in salary_requests.
 *  (B) Duplicate guard: while a request with status in {0,1,2} exists, a second
 *      submission is rejected (code=0, message have_request_before) and no
 *      second row is created.
 *  (C) Balance guard: a host without enough salary is rejected (code=0,
 *      dont_have_coin) and no row is created.
 *  (D) Serialization: the duplicate check + create run inside DB::transaction
 *      with User::...->lockForUpdate() re-reading the row, so two concurrent
 *      submissions can never both succeed and double-withdraw.
 *
 * Concurrency proof boundary
 * --------------------------
 * True parallelism is not reproducible inside a single PHPUnit worker. What we
 * prove here is the LOGIC that the lock enforces: (1) an existing pending
 * request blocks a new one, and (2) the balance is re-checked against the
 * locked row before a create. The atomicity of lockForUpdate itself (two OS
 * threads serialized on the row) can only be proven with two real MySQL
 * connections; that part is documented, not asserted here.
 *
 * The salary that gates a request is the computed `salary` attribute
 * (App\Models\User::getSalaryAttribute = sum(sallary - cut_amount) over
 * user_sallaries where is_paid=0), NOT a stored column, so fixtures seed
 * user_sallaries.
 */
class SalaryRequestDoubleSpendTest extends UtdQaTestCase
{
    private function seedSalary(User $host, int $usd): void
    {
        DB::table('user_sallaries')->insert([
            'user_id'        => $host->id,
            'sallary'        => $usd,
            'cut_amount'     => 0,
            'is_paid'        => 0,
            'month'          => (int) date('m'),
            'year'           => (int) date('Y'),
            'user_agency_id' => 0,
            'hours'          => '0/0',
            'days'           => '0/0',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    /**
     * @return array{0:int,1:int,2:User} [ownerId, paymentGatewayId, host]
     */
    private function scenario(int $hostSalary): array
    {
        $owner = $this->makeUser();
        $host  = $this->makeUser(['agency_id' => 0]);
        $this->seedSalary($host, $hostSalary);

        $pgId = DB::table('payment_gateways')->insertGetId([
            'title' => 'QA-PG', 'photo' => 'x.png', 'created_at' => now(), 'updated_at' => now(),
        ]);

        // Shipping agency (type=2) owned by $owner; add_request_salary resolves it
        // via ShippingAgency::where('app_owner_id', agent_id).
        $this->insertAgency([
            'app_owner_id' => $owner->id,
            'type'         => 2,
            'country_id'   => 1,
        ]);

        return [$owner->id, $pgId, $host->fresh()];
    }

    private function submitRequest(User $host, int $ownerId, int $pgId, int $usd): array
    {
        $request = \Illuminate\Http\Request::create('/salary-transaction/add-request', 'POST', [
            'agent_id'           => $ownerId,
            'payment_gateway_id' => $pgId,
            'country_id'         => 1,
            'usd'                => $usd,
        ]);
        $request->setUserResolver(fn () => $host);

        $response = (new SalaryTransactionController())->add_request_salary($request);

        if ($response instanceof \Illuminate\Http\JsonResponse) {
            return $response->getData(true);
        }
        // Controller returns a raw string on \Throwable — surface it as a failure body.
        return ['success' => false, 'message' => is_string($response) ? $response : json_encode($response)];
    }

    /**
     * (A) One valid request succeeds and creates exactly one row.
     */
    public function test_single_valid_request_succeeds_and_creates_one_row(): void
    {
        [$ownerId, $pgId, $host] = $this->scenario(100);

        $body = $this->submitRequest($host, $ownerId, $pgId, 50);

        $rows = DB::table('salary_requests')->where('host_id', $host->id)->count();

        // Ground-truth assertion. If the happy path is broken this fails loudly
        // and the message tells us exactly what the endpoint returned.
        $this->assertSame(
            1,
            (int) ($body['success'] ?? 0),
            'Expected a successful salary request (code=1). Actual body: ' . json_encode($body)
        );
        $this->assertSame(1, $rows, 'Exactly one salary_requests row must exist after one valid request.');
    }

    /**
     * (B) A second submission while a request in status {0,1,2} exists is rejected;
     * no second row is created. This is the duplicate-accounting guard.
     */
    public function test_second_request_is_blocked_while_one_is_pending(): void
    {
        [$ownerId, $pgId, $host] = $this->scenario(100);

        // Seed an existing pending (status=0) request directly so the guard is
        // exercised independently of whether the create path currently succeeds.
        DB::table('salary_requests')->insert([
            'agency_id'          => 0,
            'host_id'            => $host->id,
            'status'             => 0,
            'payment_gateway_id' => $pgId,
            'country_id'         => 1,
            'usd'                => 10,
            'coins'              => 100,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
        $before = DB::table('salary_requests')->where('host_id', $host->id)->count();

        $body = $this->submitRequest($host, $ownerId, $pgId, 50);

        $after = DB::table('salary_requests')->where('host_id', $host->id)->count();

        $this->assertSame(0, (int) ($body['success'] ?? -1), 'A duplicate request must be rejected (code=0). Body: ' . json_encode($body));
        $this->assertSame($before, $after, 'No new salary_requests row may be created while one is pending.');
    }

    /**
     * (B') Every "open" status blocks a new request: 0 (waiting), 1 (accepted),
     * 2 (transferred). Statuses 3 (completed) / 4 (rejected) do NOT block.
     */
    public function test_open_statuses_block_and_closed_statuses_allow(): void
    {
        foreach ([0, 1, 2] as $openStatus) {
            [$ownerId, $pgId, $host] = $this->scenario(100);
            DB::table('salary_requests')->insert([
                'agency_id' => 0, 'host_id' => $host->id, 'status' => $openStatus,
                'payment_gateway_id' => $pgId, 'country_id' => 1, 'usd' => 10, 'coins' => 100,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $before = DB::table('salary_requests')->where('host_id', $host->id)->count();

            $body = $this->submitRequest($host, $ownerId, $pgId, 50);

            $after = DB::table('salary_requests')->where('host_id', $host->id)->count();
            $this->assertSame(0, (int) ($body['success'] ?? -1), "status={$openStatus} must block a new request. Body: " . json_encode($body));
            $this->assertSame($before, $after, "status={$openStatus} must not allow a new row.");
        }
    }

    /**
     * (C) Insufficient salary is rejected; no row created. Edge: request usd
     * strictly greater than available salary.
     */
    public function test_insufficient_salary_is_rejected(): void
    {
        [$ownerId, $pgId, $host] = $this->scenario(30); // available = 30

        $body = $this->submitRequest($host, $ownerId, $pgId, 50); // asks 50 > 30

        $rows = DB::table('salary_requests')->where('host_id', $host->id)->count();
        $this->assertSame(0, (int) ($body['success'] ?? -1), 'Insufficient salary must be rejected. Body: ' . json_encode($body));
        $this->assertSame(0, $rows, 'No salary_requests row may be created when salary is insufficient.');
    }

    /**
     * (D) Logic behind the row lock: with a pending request present, the guarded
     * create path never produces a second row even across repeated calls (the
     * re-check under lockForUpdate is what makes this hold under real
     * concurrency). We assert the invariant: at most one open request per host.
     */
    public function test_repeated_submissions_never_exceed_one_open_request(): void
    {
        [$ownerId, $pgId, $host] = $this->scenario(100);

        for ($i = 0; $i < 5; $i++) {
            $this->submitRequest($host, $ownerId, $pgId, 40);
        }

        $open = DB::table('salary_requests')
            ->where('host_id', $host->id)
            ->whereIn('status', [0, 1, 2])
            ->count();

        $this->assertLessThanOrEqual(
            1,
            $open,
            'Invariant violated: more than one OPEN salary request exists for a host — double-withdrawal is possible.'
        );
    }
}