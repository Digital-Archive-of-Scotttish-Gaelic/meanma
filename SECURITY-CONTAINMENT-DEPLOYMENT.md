# Security containment deployment checklist

This checklist applies to the Phase 2 authentication and authorization containment release. It does not migrate legacy password hashes or change account, role, group, permission, or corpus data.

## Required configuration

- Set `MEANMA_APP_ORIGIN` to the application's canonical HTTPS origin, including any deployment subpath and excluding a trailing slash. Password-recovery links are derived only from this value. Recovery requests fail closed when it is absent or invalid.
- Set `MEANMA_RATE_LIMIT_DIR` to an absolute, private, writable directory outside the document root. The web-server user must be able to create and lock files there. For a multi-host deployment, replace or share this file-backed state so every host enforces the same limits.
- Keep secure cookies enabled in production. `MEANMA_COOKIE_SECURE=0` is only for isolated HTTP development and tests.
- Configure database, eXist, and SMTP credentials through the deployment secret mechanism. Do not put new credentials in tracked files.

## Web-server restrictions

The supplied `.htaccess` denies direct web access to internal source, test, documentation, dependency, utility, and data paths and disables directory indexes. On Apache, enable `mod_rewrite` and permit these rules with the appropriate `AllowOverride` setting.

If production does not use Apache or does not honor `.htaccess`, translate the restrictions into the active web-server configuration before deployment. Verify that only the authenticated application entry points and explicitly required login/recovery assets are reachable. In particular, deny direct access to `controllers`, `models`, `views`, `includes`, `tests`, `docs`, `vendor`, `TCPDF`, `xsl`, `utilities`, the corpus command-line tools, `frequencies.xml`, Composer metadata, and repository metadata.

## Recovery and email acceptance test

Before enabling recovery in production:

1. Use an isolated active test account and the production-equivalent SMTP configuration.
2. Confirm the request response is identical for existing and nonexistent email addresses.
3. Confirm the email link uses the configured HTTPS `MEANMA_APP_ORIGIN` rather than the request `Host` header.
4. Confirm the token expires, can be used only once, and cannot reset a different account.
5. Confirm logs, error pages, analytics, and support tooling do not record recovery URLs or tokens.
6. Remove the isolated test account and any test messages according to the site's normal data-handling process.

## Credential remediation prerequisite

The repository history and legacy configuration have contained exposed service credentials. Before production deployment, inventory and rotate the affected database, eXist, and SMTP credentials; revoke the old values; place replacements in the approved secret store; and verify the application with the replacements. Coordinate any later repository-history cleanup separately. Do not rewrite history as part of this containment release.

## Release verification

Run from the repository root against isolated test data:

```sh
php tests/security/run.php
zsh tests/security/http.sh
git diff --check
```

Also run PHP syntax checks over the changed PHP files, confirm no test database remains, and exercise authenticated corpus search, frequency, lexical, editing, and administrative workflows with representative existing roles and groups. Do not use or modify production accounts for acceptance testing.

Deployment remains blocked until the canonical origin, private rate-limit directory, active web-server restrictions, SMTP recovery test, and credential rotations above are complete.
