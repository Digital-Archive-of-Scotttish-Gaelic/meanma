# Password hashing migration

## Legacy format

Legacy MEANMA passwords are stored as lowercase hexadecimal MD5 digests:

```text
md5($salt . $plaintextPassword)
```

The database `salt` string is prepended byte-for-byte to the submitted password
and PHP's `md5()` default hexadecimal encoding is used. There is no separator or
character-set conversion. A legacy record is accepted only when its password is
exactly 32 lowercase hexadecimal characters and its salt is non-empty. Empty
salts, uppercase digests, non-hexadecimal values, truncated values, and unknown
formats are rejected. A hash recognized by PHP's password API is always checked
with `password_verify()` and never falls back to MD5 after failure.

## Modern format and transparent migration

New hashes use native `password_hash()` with Argon2id and explicit parameters:

- Memory: 65,536 KiB (64 MiB)
- Time cost: 3
- Threads: 1

On the PHP 8.4 development host, five hash-and-verify runs took 220.5–227.9 ms
with a 224.8 ms median. Production latency and memory capacity must be measured
before deployment; parameters can be increased in a later coordinated release.

After a successful active-account login, MEANMA verifies the original stored
credential, creates an Argon2id hash, and conditionally updates only `password`
and `salt`. The update matches email, the exact old hash, the exact old salt,
and `isActive = 1`. A concurrent password reset/change or account deactivation
therefore prevents the migration from overwriting newer state. Failed rehashes
are logged without storing partial credentials; the already verified login may
continue and retry migration on a later login.

Failed checks for missing, inactive, malformed, and legacy accounts perform an
Argon2id work factor so migrated-account existence is not exposed by the large
timing difference between MD5 and Argon2id. Login responses remain generic and
the existing rate limits remain in force.

The modern-hash marker is the native encoded password string in `password` and
an empty `salt`. Existing schemas use `password varchar(255) NOT NULL` and
`salt varchar(50) NOT NULL`. Argon2id hashes produced by this configuration are
97 characters, so no schema migration is required. Existing legacy records are
left untouched until successful authentication or password recovery.

Password recovery retains its Phase 2 token controls and now writes Argon2id
hashes. It still conditionally consumes the account-bound recovery authorization
and changes only `password`, `salt`, and `passwordAuth`.

## Deployment requirements

Before deployment, verify all web and CLI PHP runtimes report `argon2id`:

```sh
php -r 'var_export(in_array("argon2id", password_algos(), true)); echo PHP_EOL;'
```

Deployment must not proceed unless this prints `true`. The application does not
downgrade new password writes to bcrypt or MD5 when Argon2id is unavailable.
Also confirm the production `user.password` column is at least `varchar(255)`,
deploy the dual-verification code before any account migrates, run the complete
regression suite against an isolated database, and exercise SMTP recovery in a
non-production environment.

Do not roll back to MD5-only code after deployment: migrated accounts cannot be
verified by it. A rollback must retain `passwordhasher` and dual verification.
No bulk hash rewrite is possible because plaintext passwords are unavailable.

For an account whose legacy record is malformed or whose password is unknown,
use the existing email recovery flow. A valid, single-use recovery token sets a
fresh Argon2id password without reactivating the account or changing roles,
groups, identity, or profile data. Confirm canonical origin and SMTP delivery
before relying on this procedure. This increment intentionally adds no
administrator password-management interface.
