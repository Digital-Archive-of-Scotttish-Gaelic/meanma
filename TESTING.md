# MEANMA regression testing

MEANMA uses PHPUnit 9.6 for conventional unit and integration tests while
retaining the Phase 2 and Phase 3A security acceptance scripts. PHPUnit 9.6
supports PHP 7.3 and later. The application itself currently uses PHP 8
functions such as `str_contains`, so PHP 8.0 is the effective minimum; the
production PHP version should still be confirmed before changing this test
dependency to a newer PHPUnit major. Composer resolves the lockfile against a
PHP 8.0 platform so development on a newer PHP version cannot silently add a
test dependency that production cannot install.

## Install dependencies

```sh
composer install
```

No test configuration file contains database, SMTP, eXist, password, token, or
session credentials. Local database credentials continue to come from the
ignored `includes/passwords.php` file. For a dedicated test service, use:

```sh
MEANMA_TEST_DB_HOST=127.0.0.1 \
MEANMA_TEST_DB_USER=meanma_test \
MEANMA_TEST_DB_PASSWORD='local-test-password' \
composer test
```

Use a test-only account. Remote database hosts are rejected unless
`MEANMA_TEST_ALLOW_REMOTE_DATABASE=1` is also explicitly set. Even then, every
suite creates only a cryptographically suffixed database whose name matches a
hard-coded MEANMA test prefix, and validates that name again before dropping
it. Missing test opt-in fails closed. The Composer commands provide the opt-in;
direct script execution requires `MEANMA_TEST_DATABASE=1`.

## Commands

```sh
composer test:unit
composer test:integration
composer test:security
composer test
```

`composer test` is the complete regression command. It runs PHPUnit unit
tests, isolated MySQL integration tests, the extended Phase 2/3B database suite,
the 12-check Phase 2 live HTTP suite, the Phase 3A CSRF/HTTP-method suite, and
the Phase 4 dedicated-login and safe-return-path HTTP suite, and the Phase 5
administrator account-management HTTP suite.

All database suites use generated fixtures and drop their databases in cleanup
handlers. HTTP suites use temporary cookie jars, rate-limit storage, and PHP
server logs, then remove them. `MEANMA_DISABLE_EMAIL=1` prevents email delivery
in the test processes. The suites do not seed or query corpus tables.

## Coverage areas

- Phase 2 authentication containment, active/inactive accounts, legacy roles,
  group authorization, recovery-token binding and single use, and rate limits.
- Phase 3A CSRF generation/validation/rotation, method restrictions, session-ID
  regeneration, logout, controller mutations, and AJAX mutations.
- Phase 3B strict legacy-hash recognition, Argon2id hashing and verification,
  transparent conditional migration, concurrent-change protection, inactive
  account handling, and Argon2id recovery writes.
- Phase 4 dedicated login markup, absence of account enumeration and modal
  remnants, protected-page return paths, open-redirect rejection, AJAX 401
  semantics, and continued availability of password recovery.
- Phase 5 superuser-only account administration, creation and validation,
  Argon2id administrator resets, mandatory password changes, session-version
  revocation, sensitive-operation reauthentication, superuser safeguards, and
  audit records that exclude authentication secrets.
- Corpus-search request parameter and pagination characterization without
  executing corpus queries.
- Frequency-list parameter defaults, filtering, and bounds without contacting
  eXist.
- Rejected HTTP mutations leave isolated fixture tables unchanged.

## External services and known gaps

The default suite does not contact eXist/Elemental, SMTP, or production MySQL.
Consequently it does not verify actual corpus result content, the live
frequency API response, production SMTP delivery, or production deployment
configuration. Dedicated eXist integration tests require a disposable service
and collection; none is configured yet, and the live corpus must never be used.

Representative authenticated AJAX and issue creation are covered. Slip,
citation, translation, entry, and corpus editing need broader fixture schemas
before their successful write paths can be tested safely. Responsive rendering,
keyboard-only interaction, password-manager behavior, and the JavaScript
session-expiry alert remain manual browser acceptance checks. The Phase 5
account list, responsive forms, second-browser session revocation, and
administrator reauthentication should also receive manual acceptance. These are
coverage gaps, not claims of verified behavior.
