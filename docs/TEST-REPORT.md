# Verification report

Date: 21 October 2026. Target: local BCA capstone demonstration.

## Completed checks

| Check | Result | Evidence / scope |
|---|---|---|
| PHP syntax | PASS | All nine PHP files checked with PHP 8.2.12 `php -l`, including setup, routes, actions, templates and fixture test |
| Event listing template | PASS | PHP fixture renderer produced three event cards and complete shared layout |
| Login template | PASS | PHP fixture renderer produced complete document and CSRF form |
| Signup template | PASS | PHP fixture renderer produced complete document and CSRF form |
| Application JavaScript syntax | PASS | `node --check public/assets/app.js` |
| E2E suite JavaScript syntax | PASS | `node --check tests/e2e.mjs` |
| Docker Compose configuration | PASS | `docker compose config --quiet` parsed configuration successfully |

The rendering test initially exposed helper redeclaration when loading several views in one process. Shared form helpers were moved to `app/form.php` and loaded with `require_once`. All three fixture cases then passed. Normal HTTP requests render one view per request.

## Blocked / not executed

- An isolated MariaDB 10.4.32 data directory was initialized successfully, and a test database server reached “ready for connections” on local port 3308.
- The workspace rejected PHP’s local TCP connection with `SQLSTATE[HY000] [2002] ... socket ... forbidden by its access permissions`. An HTTP request from the shell was also rejected with the same socket restriction.
- Consequently application schema installation/seeding against the running server, real sign-in, database writes, actual HTTP E2E checks, and CSV data correctness have **not been validated at runtime** here.
- The included E2E suite is supplied to run locally. Its test cases are not recorded as passing.
- Docker configuration was parsed, but the Docker daemon was unavailable. The image build and container startup were **not tested**.
- Browser visual layout, mobile rendering, simultaneous registrations under load, and public deployment were **not tested**.

## Acceptance gate before college presentation

1. Complete README Route A or B and confirm setup succeeds.
2. Run `node tests/e2e.mjs` against a disposable demo database with unchanged sample credentials. Retain its PASS/FAIL output.
3. Complete the README manual walkthrough in admin and student browser sessions.
4. Check the event listing, registration history, dashboard and attendee table on desktop and a narrow/mobile viewport.
5. Open a downloaded CSV in Excel and verify the student, event, attendance and timestamp information against the page.

No claim of full end-to-end verification is made by this delivery. Syntax and fixture tests cannot substitute for a working database integration test.
