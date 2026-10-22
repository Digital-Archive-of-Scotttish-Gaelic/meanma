# Phase 5 deployment

Run `php migrations/20261022_phase5_user_management.php` before deploying the
Phase 5 application code. The command checks the live schema and is safe to
repeat. Back up the `user`, `userGroupMembers`, and new audit table first. The
migration is additive: existing account identifiers,
passwords, roles, activation state, groups, and memberships are not rewritten.

The application checks `auth_version` on every protected request. Sessions
created by an older release do not contain that version and will be required
to sign in again after deployment. This is intentional and prevents a stale
session from adopting a newer version after a security-sensitive account
change.

Rollback requires restoring the pre-deployment application version. Leave the
two user columns and audit table in place during application rollback; older
code ignores them. Removing them is destructive and should occur only after a
separate backup and retention decision for the audit trail.

After deployment, manually verify superuser reauthentication, account creation,
forced password change, session revocation in a second browser, and SMTP
password recovery. Continue to provide `MEANMA_APP_ORIGIN`, secure session
cookies, and protected rate-limit storage.
