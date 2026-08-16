# UTD QA regression suite

Money-accounting and tenant-isolation guards. These run against a **real MySQL**
schema (`meow_qa_test`), not sqlite, because they prove `lockForUpdate()`,
`DECIMAL`/`ENUM` and `UNIQUE(operation_uuid, type)` behaviour that sqlite cannot
reproduce (see `UtdQaTestCase`).

## Running

```sh
# from backend/ — migrates the test DB, then runs the suite
sh tools/utdqa-test.sh
```

## Required setup step: migrate before phpunit

The `meow_qa_test` schema is sometimes provisioned from a dump and drifts behind
the migrations (missing tables/columns). When that happens `UtdQaTestCase`
**skips** the affected guards instead of failing — a false green.

Always bring the test DB to the current schema first:

```sh
php artisan migrate --force
```

`tools/utdqa-test.sh` runs this step for you before invoking
`phpunit -c phpunit.utdqa.xml`. If you run phpunit directly, run the migrate
command yourself first (DB_* must point at `meow_qa_test`).

> No GitHub Actions/CI pipeline exists in this repo yet. When one is added, its
> UTD QA job should reuse `tools/utdqa-test.sh` (or replicate the
> `migrate --force` → `phpunit -c phpunit.utdqa.xml` order) so CI never runs the
> guards against a stale schema.
