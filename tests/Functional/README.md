# Functional tests

The live HTTP characterization tests remain in `tests/security/` because they
are also the security acceptance suite. They start PHP's development server,
create a generated MySQL test database, exercise requests with `curl`, and
remove all temporary resources on exit.

They must not be pointed at a production database or eXist collection. Run
them through `composer test:security` so the required database-test opt-in is
set consistently.
