// k6 load test for the Lucky Gift hot path (FairLuck V7).
//
// Goal: reproduce peak production load — ~800 concurrent users, each sending
// ~100 lucky-gift hits/minute, across many rooms — and verify latency + zero
// money errors after the refactor.
//
// RUN (against STAGING only — never production without sign-off):
//   k6 run \
//     -e BASE_URL=https://staging.example.com \
//     -e GIFT_ID=123 \
//     -e TOKENS_FILE=loadtest/tokens.json \
//     -e VUS=800 -e DURATION=3m \
//     loadtest/lucky_gift_load.js
//
// tokens.json: a JSON array of objects describing pre-funded test users/rooms:
//   [ { "token": "Bearer xxx", "room_id": 1001, "to_uid": "31051" }, ... ]
// Provide at least as many entries as VUS for a clean 1:1 mapping (it wraps if fewer).
//
// PRE-REQS on staging:
//   • throttle:lucky-gift middleware raised/disabled for the test window
//   • test users funded with a large `di` balance
//   • FairLuck vault seeded to a NORMAL balance (e.g. V7_wallet_target)
//   • run `php artisan fairluck:reconcile` BEFORE and AFTER → drift must be 0

import http from 'k6/http';
import { check, sleep } from 'k6';
import { Counter, Rate, Trend } from 'k6/metrics';
import { SharedArray } from 'k6/data';

const VUS = parseInt(__ENV.VUS || '800', 10);
const DURATION = __ENV.DURATION || '3m';
const BASE_URL = __ENV.BASE_URL || 'http://localhost';
const GIFT_ID = __ENV.GIFT_ID || '1';
const PATH = __ENV.PATH || '/api/v1/gifts/v2/send-lucky-gift-combo';
const HITS_PER_MIN = parseInt(__ENV.HITS_PER_MIN || '100', 10);

const accounts = new SharedArray('accounts', function () {
  const file = __ENV.TOKENS_FILE || 'loadtest/tokens.json';
  return JSON.parse(open(file));
});

const bizErrors = new Counter('lucky_business_errors'); // status:0 responses
const okRate = new Rate('lucky_ok');
const winTrend = new Trend('lucky_win_coins');

export const options = {
  scenarios: {
    peak: {
      executor: 'constant-vus',
      vus: VUS,
      duration: DURATION,
    },
  },
  thresholds: {
    // "faster than a blink": p95 under 300ms, p99 under 800ms.
    http_req_duration: ['p(95)<300', 'p(99)<800'],
    http_req_failed: ['rate<0.01'],
    lucky_ok: ['rate>0.98'],
  },
};

// Per-VU pacing to approximate HITS_PER_MIN requests per user per minute.
const intervalSec = 60 / HITS_PER_MIN;

export default function () {
  const acct = accounts[(__VU - 1) % accounts.length];

  const payload = {
    id: GIFT_ID,
    toUid: acct.to_uid,
    num: 1,
    count: 1,
  };
  if (acct.room_id) payload.room_id = acct.room_id;
  if (acct.owner_id) payload.owner_id = acct.owner_id;

  const res = http.post(`${BASE_URL}${PATH}`, payload, {
    headers: {
      Authorization: acct.token,
      Accept: 'application/json',
    },
    tags: { name: 'send-lucky-gift-combo' },
  });

  const httpOk = check(res, { 'http 200': (r) => r.status === 200 });

  let bizOk = false;
  if (httpOk) {
    try {
      const body = res.json();
      bizOk = body && (body.status === 1 || body.status === true);
      if (!bizOk) bizErrors.add(1);
      if (body && body.data && typeof body.data.total_user_win === 'number') {
        winTrend.add(body.data.total_user_win);
      }
    } catch (e) {
      bizErrors.add(1);
    }
  }
  okRate.add(bizOk);

  sleep(intervalSec);
}
