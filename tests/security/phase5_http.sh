#!/bin/zsh
set -euo pipefail

repo_dir=${0:A:h:h:h}
test_db="meanma_security_http_test_$(openssl rand -hex 6)"
test_tmp=$(mktemp -d /tmp/meanma-phase5-http.XXXXXX)
server_pid=''
port=18768
checks=0
typeset -a database_env
[[ -n "${MEANMA_TEST_DB_HOST:-}" ]] && database_env+=("MEANMA_DB_HOST=$MEANMA_TEST_DB_HOST")
[[ -n "${MEANMA_TEST_DB_USER:-}" ]] && database_env+=("MEANMA_DB_USER=$MEANMA_TEST_DB_USER")
[[ -n "${MEANMA_TEST_DB_PASSWORD+x}" ]] && database_env+=("MEANMA_DB_PASSWORD=$MEANMA_TEST_DB_PASSWORD")

cleanup() {
  local result=$?
  [[ -n "$server_pid" ]] && { kill "$server_pid" 2>/dev/null || true; wait "$server_pid" 2>/dev/null || true; }
  php "$repo_dir/tests/security/http_fixture.php" drop "$test_db" || true
  rm -rf "$test_tmp"
  return $result
}
trap cleanup EXIT

assert_status() {
  local expected=$1 actual=$2 label=$3
  checks=$((checks + 1))
  [[ "$actual" == "$expected" ]] || { print -u2 "FAIL: $label returned $actual, expected $expected"; tail -n 50 "$test_tmp/server.log" >&2; exit 1; }
}

assert_file() {
  local pattern=$1 file=$2 label=$3
  checks=$((checks + 1))
  grep -Eq "$pattern" "$file" || { print -u2 "FAIL: $label"; exit 1; }
}

assert_absent() {
  local pattern=$1 file=$2 label=$3
  checks=$((checks + 1))
  ! grep -Eqi "$pattern" "$file" || { print -u2 "FAIL: $label"; exit 1; }
}

login_account() {
  local email=$1 password=$2 cookie=$3 prefix=$4
  curl -sS -c "$cookie" "http://127.0.0.1:$port/index.php?loginAction=loginPage" -o "$test_tmp/$prefix-login.html"
  local csrf
  csrf=$(perl -0777 -ne 'print $1 if /name="loginAction" value="login".*?name="_csrf" value="([^"]+)"/s' "$test_tmp/$prefix-login.html")
  curl -sS -b "$cookie" -c "$cookie" -D "$test_tmp/$prefix.headers" -o /dev/null -w '%{http_code}' \
    --data-urlencode 'loginAction=login' --data-urlencode "email=$email" \
    --data-urlencode "password=$password" --data-urlencode "_csrf=$csrf" \
    "http://127.0.0.1:$port/index.php"
}

php "$repo_dir/tests/security/http_fixture.php" create "$test_db"
env "${database_env[@]}" MEANMA_DB_NAME="$test_db" MEANMA_APP_ORIGIN="http://127.0.0.1:$port" \
MEANMA_ALLOW_INSECURE_ORIGIN=1 MEANMA_COOKIE_SECURE=0 MEANMA_DISABLE_EMAIL=1 \
MEANMA_RATE_LIMIT_DIR="$test_tmp/rates" php -S "127.0.0.1:$port" -t "$repo_dir" >"$test_tmp/server.log" 2>&1 &
server_pid=$!
for attempt in {1..20}; do
  curl -sS "http://127.0.0.1:$port/index.php?loginAction=loginPage" -o /dev/null 2>/dev/null && break
  sleep 0.1
done

http_status=$(login_account active@example.test test-password "$test_tmp/normal.cookies" normal)
assert_status 303 "$http_status" 'Normal user login'
http_status=$(curl -sS -b "$test_tmp/normal.cookies" -o "$test_tmp/normal-users.html" -w '%{http_code}' "http://127.0.0.1:$port/index.php?m=users")
assert_status 403 "$http_status" 'Normal user account-management access'

http_status=$(login_account editor@example.test test-password "$test_tmp/editor.cookies" editor)
assert_status 303 "$http_status" 'Editor login'
http_status=$(curl -sS -b "$test_tmp/editor.cookies" -o "$test_tmp/editor-users.html" -w '%{http_code}' "http://127.0.0.1:$port/index.php?m=users")
assert_status 403 "$http_status" 'Editor account-management access'

http_status=$(login_account admin@example.test test-password "$test_tmp/admin.cookies" admin)
assert_status 303 "$http_status" 'Superuser login'
http_status=$(curl -sS -b "$test_tmp/admin.cookies" -o "$test_tmp/admin-users.html" -w '%{http_code}' "http://127.0.0.1:$port/index.php?m=users")
assert_status 200 "$http_status" 'Superuser account-management access'
assert_file 'User management' "$test_tmp/admin-users.html" 'Account list did not render'
assert_absent '\$argon2id\$|passwordAuth|auth_version|session' "$test_tmp/admin-users.html" 'Account list exposed authentication secrets'

curl -sS -b "$test_tmp/admin.cookies" "http://127.0.0.1:$port/index.php?m=users&a=new" -o "$test_tmp/new.html"
app_csrf=$(perl -ne 'print $1 if /name="csrf-token" content="([^"]+)"/' "$test_tmp/new.html")
http_status=$(curl -sS -b "$test_tmp/admin.cookies" -o /dev/null -w '%{http_code}' \
  --data-urlencode 'email=managed@example.test' --data-urlencode 'firstname=Managed' --data-urlencode 'lastname=User' \
  --data-urlencode 'role=0' --data-urlencode 'groups[]=1' --data-urlencode 'active=1' \
  --data-urlencode 'password=temporary-password' \
  "http://127.0.0.1:$port/index.php?m=users&a=create")
assert_status 403 "$http_status" 'Account creation without CSRF'

http_status=$(curl -sS -b "$test_tmp/admin.cookies" -D "$test_tmp/create.headers" -o /dev/null -w '%{http_code}' \
  --data-urlencode "_csrf=$app_csrf" --data-urlencode 'email=managed@example.test' \
  --data-urlencode 'firstname=Managed' --data-urlencode 'lastname=User' --data-urlencode 'role=0' \
  --data-urlencode 'groups[]=1' --data-urlencode 'active=1' --data-urlencode 'password=temporary-password' \
  "http://127.0.0.1:$port/index.php?m=users&a=create")
assert_status 303 "$http_status" 'Account creation'

http_status=$(login_account managed@example.test temporary-password "$test_tmp/managed.cookies" managed)
assert_status 303 "$http_status" 'Administrator-created account login'
assert_file '^Location: index.php\?loginAction=changePassword' "$test_tmp/managed.headers" 'Temporary password did not require a change'
http_status=$(curl -sS -b "$test_tmp/managed.cookies" -H 'Accept: application/json' -o "$test_tmp/bypass.json" -w '%{http_code}' "http://127.0.0.1:$port/ajax.php?action=search")
assert_status 403 "$http_status" 'Mandatory-change AJAX bypass'
assert_file 'password_change_required' "$test_tmp/bypass.json" 'Mandatory-change AJAX response was unclear'
http_status=$(curl -sS -b "$test_tmp/managed.cookies" -D "$test_tmp/bypass.headers" -o /dev/null -w '%{http_code}' "http://127.0.0.1:$port/index.php?m=frequency")
assert_status 302 "$http_status" 'Mandatory-change HTML bypass'
assert_file '^Location: index.php\?loginAction=changePassword' "$test_tmp/bypass.headers" 'Mandatory-change HTML redirect was incorrect'

curl -sS -b "$test_tmp/managed.cookies" "http://127.0.0.1:$port/index.php?loginAction=changePassword" -o "$test_tmp/change.html"
change_csrf=$(perl -ne 'print $1 if /name="_csrf" value="([^"]+)"/' "$test_tmp/change.html")
assert_file 'name="loginAction" value="saveRequiredPassword"' "$test_tmp/change.html" 'Mandatory password form did not render'
assert_file '<a class="navbar-brand" href="index.php">MEANⓂ️A</a>' "$test_tmp/change.html" 'Mandatory password page is missing MEANMA branding'
assert_file 'class="container auth-layout"' "$test_tmp/change.html" 'Mandatory password page is missing the standard content container'
assert_file 'class="input-group auth-password"' "$test_tmp/change.html" 'Mandatory password fields are missing Bootstrap input grouping'
assert_absent 'title="browse corpus"|title="search corpus"|title="frequency lists"|id="logoutLink"' "$test_tmp/change.html" 'Mandatory password page exposed application navigation'
http_status=$(curl -sS -b "$test_tmp/managed.cookies" -o /dev/null -w '%{http_code}' \
  --data-urlencode 'loginAction=saveRequiredPassword' --data-urlencode 'pass1=chosen-password' --data-urlencode 'pass2=chosen-password' \
  "http://127.0.0.1:$port/index.php")
assert_status 403 "$http_status" 'Mandatory password change without CSRF'
http_status=$(curl -sS -b "$test_tmp/managed.cookies" -c "$test_tmp/managed.cookies" -D "$test_tmp/changed.headers" -o /dev/null -w '%{http_code}' \
  --data-urlencode 'loginAction=saveRequiredPassword' --data-urlencode "_csrf=$change_csrf" \
  --data-urlencode 'pass1=chosen-password' --data-urlencode 'pass2=chosen-password' \
  "http://127.0.0.1:$port/index.php")
assert_status 303 "$http_status" 'Mandatory password change completion'
http_status=$(curl -sS -b "$test_tmp/managed.cookies" -o /dev/null -w '%{http_code}' "http://127.0.0.1:$port/index.php")
assert_status 200 "$http_status" 'Application access after mandatory password change'

curl -sS -b "$test_tmp/admin.cookies" "http://127.0.0.1:$port/index.php?m=users&a=edit&email=managed%40example.test" -o "$test_tmp/edit.html"
app_csrf=$(perl -ne 'print $1 if /name="csrf-token" content="([^"]+)"/' "$test_tmp/edit.html")
http_status=$(curl -sS -b "$test_tmp/admin.cookies" -o /dev/null -w '%{http_code}' \
  --data-urlencode "_csrf=$app_csrf" --data-urlencode 'target_email=managed@example.test' \
  --data-urlencode 'new_password=second-temporary-password' \
  "http://127.0.0.1:$port/index.php?m=users&a=resetPassword")
assert_status 303 "$http_status" 'Administrator password reset'
http_status=$(curl -sS -b "$test_tmp/managed.cookies" -H 'Accept: application/json' -o "$test_tmp/revoked.json" -w '%{http_code}' "http://127.0.0.1:$port/ajax.php?action=search")
assert_status 401 "$http_status" 'Session revocation after password reset'

http_status=$(curl -sS -b "$test_tmp/admin.cookies" -o "$test_tmp/audit.html" -w '%{http_code}' "http://127.0.0.1:$port/index.php?m=users&a=audit")
assert_status 200 "$http_status" 'Superuser audit access'
assert_file 'account_created|administrator_password_reset' "$test_tmp/audit.html" 'Audit actions were not displayed'
assert_absent 'temporary-password|chosen-password|second-temporary-password|\$argon2id\$' "$test_tmp/audit.html" 'Audit trail leaked password material'

print "Phase 5 account-management HTTP checks: $checks"
print 'All Phase 5 account-management HTTP checks passed.'
