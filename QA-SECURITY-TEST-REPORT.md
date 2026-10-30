# QA and Security Test Report

## Status

The test suite and fixtures have been added. The previous run reported **52 passing and 3 failing tests**. This update addresses all three reported failures, but **the suite has not been rerun after these changes**.

The test database now uses MySQL 8.4, matching the engine configured in [docker-compose.yml](./docker-compose.yml). A dedicated `mysql-testing` Compose service uses its own `sistema_aportes_testing` schema on tmpfs, bound only to localhost port 3307; it does not share the development database volume. [phpunit.xml](./phpunit.xml) forces `APP_ENV=testing`, `APP_DEBUG=false`, and the test MySQL connection. Do not remove or change the testing database name to the development database.

## Application map

| Area | Source map |
|---|---|
| Entities and relationships | `Usuario` belongs to `Rol` and optionally `Colegio`; `Afiliado` belongs to `Colegio` and has labor data, postgraduates, payments, contributions, balances, observations, and transfer history. `LoteVerificacion` belongs to a college and has payments. `Pago` belongs to an affiliate and optional batch, and has contributions and balances. `Aporte` references affiliate, college, payment, and registering/verifying users. |
| Roles and access | `SUPER ADMIN`, `ADMIN`, and `USUARIO`. Routes combine `auth`, `debe-cambiar-password`, `rol:*`, and policies. `USUARIO` is scoped to its college (and child colleges for a departmental college); admin/import routes are role-restricted. Login is throttled at five attempts per minute. |
| Batch/payment states | Batch flow: `ABIERTO -> ENVIADO -> EN_VERIFICACION -> VERIFICADO`; a super admin may reopen an `ENVIADO` batch to `ABIERTO`. During confirmation, a payment moves from `EN_LOTE` to `VERIFICADO` or `OBSERVADO`. |
| Money | Amount columns use SQL decimal types and model decimal casts. `SaldoService` converts values to integer centavos for period allocation, then stores decimal amounts. |
| Import/export | CSV imports include bulk affiliate loading and historical transfer, contribution, balance, and observation workflows. Reports export affiliates/contributions as PDF and Excel. `SafeValueBinder` is configured as Laravel Excel's default binder. |

## Added coverage

- Factories for the application models (plus a shared fixture helper) with realistic role, college, affiliate, payment, batch, and state defaults.
- Authentication: guest redirect, successful/inactive login, login throttling, session ID regeneration, logout, hashed and hidden passwords.
- Guest checks across routes declaring `auth`, and role checks for regular/admin access to restricted routes.
- Cross-college affiliate lookup, untrusted extra fields on affiliate creation, SQL-like lookup input, and escaped Blade output.
- Batch creation/closure and ownership, preview/confirmation validation, observed-payment notes, generated contribution periods and balances, and duplicate confirmation.
- Policy matrices for user, affiliate, payment, and batch abilities.
- Centavo calculations and invalid monthly amount handling.
- Historical transfer CSV parsing for UTF-8 BOM, normalized CI/date, duplicate rows, missing headers, and database duplicates.
- Formula-like spreadsheet values and the configured Excel binder.
- Regression tests for the inactive-account finding below and the previously reported test-environment failures.

## Findings

| # | Severity | Location | Result | Regression test |
|---|---|---|---|---|
| 1 | Medium (addressed; rerun pending) | `app/Http/Middleware/UsuarioActivo.php` and `bootstrap/app.php` | Previously, an inactive user with an existing session could still access authenticated pages. The new web middleware logs out and invalidates inactive sessions, then returns 403. | `test_deactivated_account_cannot_continue_using_an_existing_session` |

The SQLite `YEAR()` error was an engine mismatch in the test setup, not an application vulnerability. The report compatibility test now runs against MySQL. The `SafeValueBinder` is configured globally for Excel exports; its regression test checks formula-like cell types after writing and reopening an XLSX. The returned carriage return is normalized to a line feed, so the test asserts the normalized value rather than byte-for-byte equality. Blade renders affiliate names with escaped `{{ }}`, and the inspected search paths use parameterized query-builder values. Deployment-only facts (HTTPS enforcement, production debug settings, and proxy/security headers) still need environment-owner validation.

## Coverage gaps

This is a focused initial suite, not exhaustive coverage of every request in the original checklist. Remaining work includes full CRUD/validation matrices for every resource; all importers and malformed/oversized upload cases; end-to-end report/PDF/XLSX response assertions and report filter scoping; pagination and invalid sort/filter cases; CSRF behavior outside Laravel's normal testing bypass; all route-role combinations; request-time behavior for all inactive roles; migration rollback/seed reproducibility; console commands; N+1 checks; deeper boundary/leap-year/large-value money cases; concurrent database execution; production error-page and cookie/header checks; and Larastan.

## Commands

Start the isolated MySQL test service and run the complete suite inside the app container:

```powershell
docker compose --profile testing up -d --wait mysql-testing
docker compose exec app php artisan test
```

If the app container is not running, start it first. It will also start the normal development MySQL service, but tests connect only to `mysql-testing`:

```powershell
docker compose up -d app
```

Run targeted areas:

```powershell
docker compose exec app php artisan test --filter=InactiveAccountRevocationTest
docker compose exec app php artisan test --filter=ReportCompatibilityTest
docker compose exec app php artisan test --filter=SafeValueBinderTest
docker compose exec app php artisan test --filter=AuthenticationTest
docker compose exec app php artisan test --filter=ProtectedRoutesTest
docker compose exec app php artisan test --filter=RoleAuthorizationTest
docker compose exec app php artisan test --filter=AffiliateAccessTest
docker compose exec app php artisan test --filter=LoteWorkflowTest
docker compose exec app php artisan test --filter=LoteConfirmationTest
docker compose exec app php artisan test --filter=ImportadorTransferenciasHistoricasTest
docker compose exec app php artisan test --filter=DomainPolicyMatrixTest
docker compose exec app php artisan test --filter=SaldoServiceTest
```

Stop and remove the temporary MySQL test service after testing:

```powershell
docker compose --profile testing stop mysql-testing
docker compose --profile testing rm -f mysql-testing
```

No `.env.testing` file is required because PHPUnit forces the test connection values. The test service's database is ephemeral; starting it again creates a fresh schema.

Run tests directly with the host PHP runtime only if you change the test DB host to `127.0.0.1` and port to `3307`; keep the database name `sistema_aportes_testing`.

Additional original test categories remain outside this focused suite; see Coverage gaps above.
