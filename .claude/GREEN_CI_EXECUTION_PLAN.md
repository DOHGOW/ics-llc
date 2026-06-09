# GREEN CI EXECUTION PLAN
# ICS Enterprise Ecosystem Platform — First GREEN Build (D-049 Recovery, Section D)

Version: 1.0
Date: 2026-06-05
Status: Analysis/plan only — no pipeline runs performed here.
Author: Lead Architect
Pairs with: ENVIRONMENT_REMEDIATION_PLAN.md, BOOTSTRAP_RECOVERY_PLAN.md, BLOCKER_RESOLUTION_MATRIX.md
CI reference: `.github/workflows/ci.yml` (jobs: `quality`, `secrets`, `engine-parity`)

> Goal: the **first GREEN CI run**. The GitHub Actions runner already supplies PHP 8.3 + intl + MySQL 8 +
> the full extension set — so B1, B4, B6 are satisfied **by the runner**. The remaining work to reach GREEN
> is B3 (skeleton), B2/B7 (deps+lock), B11 (factory), B12 (configs) — all RESOLVABLE NOW.

---

## D.1 WHY CI IS THE FASTEST AUTHORITATIVE PATH

| Blocker | Local Windows/XAMPP | GitHub Actions runner |
|---|---|---|
| B1 PHP 8.3 | must install | ✅ provided (`shivammathur/setup-php@v2`, 8.3) |
| B4 intl | must enable | ✅ in extension list |
| B6 MySQL 8 | must install | ✅ `engine-parity` service `mysql:8.0` |
| B5 Node | must install | not needed (no npm step) |

→ Pushing the bootstrap-recovered branch to CI removes four environment blockers instantly. Local
remediation is for developer ergonomics, not for the first GREEN.

---

## D.2 FIRST COMMAND TO RUN

After the bootstrap recovery branch exists (BOOTSTRAP_RECOVERY_PLAN A+B complete, lock committed):
```
git push origin chore/bootstrap-recovery      # triggers ci.yml on push
```
Locally, the equivalent first command is `composer validate --strict` (already PASSES), then
`composer install` (now succeeds from the committed lock on 8.3).

---

## D.3 EXPECTED FAILURE ORDER (and how to clear each)

The pipeline fails fast at the first unmet gate. Anticipated order **after** runtime+skeleton+lock are in
place:

| Order | CI step | Likely first failure | Clear it by |
|---|---|---|---|
| 1 | composer validate | none (passes today) | — |
| 2 | composer install | none once lock committed | B2/B7 done |
| 3 | composer audit | advisories if not triaged | B.3 triage (raise floor / ignore) |
| 4 | hardcoded-driver gate | a literal driver string slipped in | fix to `config('...')` (D-037) |
| 5 | **Pint** (style) | formatting drift in new code | `./vendor/bin/pint` (auto-fix) then commit |
| 6 | **Larastan** (static) | **most probable real failures** — untyped/null paths in overlay incl. new Billing/Membership code | fix types/null-guards; never weaken the baseline silently |
| 7 | **PHPUnit** | schema/enum/seed/factory assumptions on first-ever run | B11 factory; fix seeders; reconcile enum/casts |
| 8 | engine-parity (MySQL 8) | FULLTEXT/JSON/ENUM/TenantScope-console nuance | validate against MySQL 8 specifically (not sqlite/MariaDB) |
| — | secrets (gitleaks) | committed secret | rotate + remove (none expected; `.env` not committed) |

> Larastan (step 6) is the single most likely source of multiple iterations — the overlay has never been
> statically analysed. Budget for several fix cycles here.

---

## D.4 DEPENDENCY CHAIN (must pass in this order)

```
validate → install(lock) → audit → driver-gate → pint → larastan → phpunit → engine-parity
                                   └────────── all depend on a successful install ──────────┘
```
- `quality` job and `engine-parity` job run in parallel but both require a clean `composer install`.
- `secrets` job is independent.

---

## D.5 TEST EXECUTION EXPECTATIONS (D-049 Step 4)

Once PHPUnit runs, treat these as the acceptance set (report PASSED/FAILED per actual result — never assume):

| Suite | Engine sensitivity | Notes |
|---|---|---|
| RBAC, Escalation, UserLifecycle, Audit immutability, Security headers, Localization | sqlite ok | Sprint-1 conformance; first execution |
| AccountIsolation | sqlite ok (logic) | AccountScope |
| **CrossTenantIsolation** | **MySQL authoritative** | TenantScope filtering — read from engine-parity job (scope bypasses in pure console; trust MySQL job + structural assertions) |
| **Billing A–G** | mixed | first run; watch enum/idempotency/signature/webhook + tenant isolation (test_d via MySQL) |
| **Membership 1–8** | mixed | first run; B11 factory required; research/knowledge elevation + boundary + tenant-stamp |

- **phpunit.xml** defaults to `sqlite :memory:`; the **engine-parity** job re-runs the suite on MySQL 8 —
  the isolation/FULLTEXT/JSON results from the MySQL job are the authoritative ones.

---

## D.6 QUICKEST ROUTE TO FIRST GREEN (checklist)

1. Branch `chore/bootstrap-recovery` off a clean tree.
2. Recover skeleton additively (artisan/public/configs/storage/factories) — BOOTSTRAP_RECOVERY §A.
3. On PHP 8.3: `composer update` → triage `composer audit` (B.3) → commit `composer.lock`.
4. Reconcile `UserFactory` to `core_users` (B11); add standard configs + `vendor:publish` (B12).
5. `php artisan migrate --seed` on MySQL 8 locally OR rely on CI — confirm green migrate.
6. Push → let Actions run; iterate failures in the order of D.3 (expect Larastan + first-run PHPUnit).
7. Achieve GREEN on BOTH `quality` and `engine-parity`; attach the run URL to the go-live checklist.

**Estimated iteration hotspots:** Larastan (multiple), first-run PHPUnit (factory/enum/seed), engine-parity
(FULLTEXT/JSON/TenantScope). Driver-gate/Pint are usually quick.

---

## D.7 DEFINITION OF "FIRST GREEN CI"

- `quality` job: validate ✅, install ✅, audit ✅/triaged, driver-gate ✅, Pint ✅, Larastan ✅, PHPUnit ✅.
- `engine-parity` job (MySQL 8): full suite ✅.
- `secrets` job: gitleaks ✅.
- Result recorded; **R-012 and R-013 closed**; D-049 #3–4 satisfied. (D-049 #5 host spike + #6 sign-off
  still required for production — see certification.)
