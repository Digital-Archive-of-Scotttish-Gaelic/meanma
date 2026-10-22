#!/bin/zsh

set -euo pipefail

repo_dir=${0:A:h:h:h}
test_db="meanma_security_http_test_$(openssl rand -hex 6)"
test_tmp=$(mktemp -d /tmp/meanma-security-csrf.XXXXXX)
server_pid=''
port=18766
checks=0
typeset -a database_env
[[ -n "${MEANMA_TEST_DB_HOST:-}" ]] && database_env+=("MEANMA_DB_HOST=$MEANMA_TEST_DB_HOST")
[[ -n "${MEANMA_TEST_DB_USER:-}" ]] && database_env+=("MEANMA_DB_USER=$MEANMA_TEST_DB_USER")
[[ -n "${MEANMA_TEST_DB_PASSWORD+x}" ]] && database_env+=("MEANMA_DB_PASSWORD=$MEANMA_TEST_DB_PASSWORD")

cleanup() {
  local exit_status=$?
  if [[ -n "$server_pid" ]]; then
    kill "$server_pid" 2>/dev/null || true
    wait "$server_pid" 2>/dev/null || true
  fi
  php "$repo_dir/tests/security/http_fixture.php" drop "$test_db" || true
  rm -rf "$test_tmp"
  return $exit_status
}
trap cleanup EXIT

assert_status() {
  local expected=$1
  local actual=$2
  local label=$3
  checks=$((checks + 1))
  if [[ "$actual" != "$expected" ]]; then
    print -u2 "FAIL: $label returned $actual, expected $expected"
    tail -n 50 "$test_tmp/server.log" >&2
    exit 1
  fi
}

assert_equal() {
  local expected=$1
  local actual=$2
  local label=$3
  checks=$((checks + 1))
  if [[ "$actual" != "$expected" ]]; then
    print -u2 "FAIL: $label was $actual, expected $expected"
    exit 1
  fi
}

login_session() {
  local cookie_file=$1
  local prefix=$2
  curl -sS -b "$cookie_file" -c "$cookie_file" "http://127.0.0.1:$port/index.php" -o "$test_tmp/$prefix-login.html"
  local login_csrf
  login_csrf=$(perl -0777 -ne 'print $1 if /name="loginAction" value="login".*?name="_csrf" value="([^"]+)"/s' "$test_tmp/$prefix-login.html")
  [[ -n "$login_csrf" ]]
  curl -sS -b "$cookie_file" -c "$cookie_file" -o "$test_tmp/$prefix-home.html" \
    --data-urlencode 'loginAction=login' \
    --data-urlencode 'email=active@example.test' \
    --data-urlencode 'password=test-password' \
    --data-urlencode "_csrf=$login_csrf" \
    "http://127.0.0.1:$port/index.php"
}

php "$repo_dir/tests/security/http_fixture.php" create "$test_db"

env "${database_env[@]}" MEANMA_DB_NAME="$test_db" \
MEANMA_APP_ORIGIN="http://127.0.0.1:$port" \
MEANMA_ALLOW_INSECURE_ORIGIN=1 \
MEANMA_COOKIE_SECURE=0 \
MEANMA_DISABLE_EMAIL=1 \
MEANMA_RATE_LIMIT_DIR="$test_tmp/rates" \
php -S "127.0.0.1:$port" -t "$repo_dir" >"$test_tmp/server.log" 2>&1 &
server_pid=$!

for attempt in {1..20}; do
  if curl -sS "http://127.0.0.1:$port/index.php" -o "$test_tmp/anonymous.html" 2>/dev/null; then
    break
  fi
  sleep 0.1
done

anonymous_app_csrf=$(perl -ne 'print $1 if /name="csrf-token" content="([^"]+)"/' "$test_tmp/anonymous.html")
login_csrf=$(perl -0777 -ne 'print $1 if /name="loginAction" value="login".*?name="_csrf" value="([^"]+)"/s' "$test_tmp/anonymous.html")
[[ -n "$anonymous_app_csrf" && -n "$login_csrf" ]]

http_status=$(curl -sS -o "$test_tmp/login-get.html" -w '%{http_code}' \
  "http://127.0.0.1:$port/index.php?loginAction=login")
assert_status 405 "$http_status" 'GET login mutation'

http_status=$(curl -sS -o "$test_tmp/recovery-write-get.html" -w '%{http_code}' \
  "http://127.0.0.1:$port/index.php?loginAction=savePassword")
assert_status 405 "$http_status" 'GET password replacement'

http_status=$(curl -sS -X POST -o "$test_tmp/recovery-form-post.html" -w '%{http_code}' \
  --data-urlencode 'loginAction=forgotPassword' "http://127.0.0.1:$port/index.php")
assert_status 405 "$http_status" 'POST recovery form display'

http_status=$(curl -sS -o "$test_tmp/recovery-request-get.html" -w '%{http_code}' \
  "http://127.0.0.1:$port/index.php?loginAction=sendEmail")
assert_status 405 "$http_status" 'GET recovery email request'

http_status=$(curl -sS -b "$test_tmp/anonymous.cookies" -c "$test_tmp/anonymous.cookies" \
  -o "$test_tmp/login-missing.html" -w '%{http_code}' \
  --data-urlencode 'loginAction=login' \
  --data-urlencode 'email=active@example.test' \
  --data-urlencode 'password=test-password' \
  "http://127.0.0.1:$port/index.php")
assert_status 403 "$http_status" 'Login without CSRF token'

curl -sS -c "$test_tmp/first.cookies" "http://127.0.0.1:$port/index.php" -o "$test_tmp/first-login.html"
session_before=$(awk '$6 == "MEANMASESSID" {print $7}' "$test_tmp/first.cookies")
first_pre_login_csrf=$(perl -ne 'print $1 if /name="csrf-token" content="([^"]+)"/' "$test_tmp/first-login.html")
login_session "$test_tmp/first.cookies" first
session_after=$(awk '$6 == "MEANMASESSID" {print $7}' "$test_tmp/first.cookies")
post_login_csrf=$(perl -ne 'print $1 if /name="csrf-token" content="([^"]+)"/' "$test_tmp/first-home.html")
[[ -n "$post_login_csrf" ]]
checks=$((checks + 1))
[[ "$session_before" != "$session_after" ]] || { print -u2 'FAIL: Login did not regenerate the session ID'; exit 1; }
checks=$((checks + 1))
[[ "$first_pre_login_csrf" != "$post_login_csrf" ]] || { print -u2 'FAIL: Login did not rotate the application CSRF token'; exit 1; }

http_status=$(curl -sS -b "$test_tmp/first.cookies" -o "$test_tmp/read.html" -w '%{http_code}' \
  "http://127.0.0.1:$port/index.php")
assert_status 200 "$http_status" 'Authenticated read-only request'

http_status=$(curl -sS -b "$test_tmp/first.cookies" -o "$test_tmp/get-write.json" -w '%{http_code}' \
  -H 'Accept: application/json' "http://127.0.0.1:$port/ajax.php?action=raiseIssue&description=get&userEmail=active@example.test&status=new")
assert_status 405 "$http_status" 'GET mutation'

http_status=$(curl -sS -I -b "$test_tmp/first.cookies" -o "$test_tmp/head-write.headers" -w '%{http_code}' \
  -H 'Accept: application/json' "http://127.0.0.1:$port/ajax.php?action=raiseIssue")
assert_status 405 "$http_status" 'HEAD mutation'

http_status=$(curl -sS -X POST -b "$test_tmp/first.cookies" -o "$test_tmp/missing.json" -w '%{http_code}' \
  -H 'Accept: application/json' "http://127.0.0.1:$port/ajax.php?action=raiseIssue&description=missing&userEmail=active@example.test&status=new")
assert_status 403 "$http_status" 'AJAX mutation without CSRF token'

http_status=$(curl -sS -X POST -b "$test_tmp/first.cookies" -o "$test_tmp/invalid.json" -w '%{http_code}' \
  -H 'Accept: application/json' --data-urlencode '_csrf=invalid' \
  "http://127.0.0.1:$port/ajax.php?action=raiseIssue&description=invalid&userEmail=active@example.test&status=new")
assert_status 403 "$http_status" 'AJAX mutation with invalid CSRF token'

http_status=$(curl -sS -X POST -b "$test_tmp/first.cookies" -o "$test_tmp/array-token.json" -w '%{http_code}' \
  -H 'Accept: application/json' --data-urlencode '_csrf[]=invalid' \
  "http://127.0.0.1:$port/ajax.php?action=raiseIssue&description=array&userEmail=active@example.test&status=new")
assert_status 403 "$http_status" 'AJAX mutation with non-scalar CSRF token'
assert_equal 0 "$(php "$repo_dir/tests/security/http_fixture.php" issue-count "$test_db")" 'Rejected requests database row count'

login_session "$test_tmp/second.cookies" second
http_status=$(curl -sS -X POST -b "$test_tmp/second.cookies" -o "$test_tmp/cross-session.json" -w '%{http_code}' \
  -H 'Accept: application/json' --data-urlencode "_csrf=$post_login_csrf" \
  "http://127.0.0.1:$port/ajax.php?action=raiseIssue&description=cross&userEmail=active@example.test&status=new")
assert_status 403 "$http_status" 'Cross-session CSRF token'
assert_equal 0 "$(php "$repo_dir/tests/security/http_fixture.php" issue-count "$test_db")" 'Cross-session rejection database row count'

http_status=$(curl -sS -X POST -b "$test_tmp/first.cookies" -o "$test_tmp/valid.json" -w '%{http_code}' \
  -H 'Accept: application/json' --data-urlencode "_csrf=$post_login_csrf" \
  "http://127.0.0.1:$port/ajax.php?action=raiseIssue&description=valid&userEmail=active@example.test&status=new")
assert_status 200 "$http_status" 'AJAX mutation with valid CSRF token'
assert_equal 1 "$(php "$repo_dir/tests/security/http_fixture.php" issue-count "$test_db")" 'Valid AJAX mutation database row count'

http_status=$(curl -sS -b "$test_tmp/first.cookies" -o "$test_tmp/get-controller.html" -w '%{http_code}' \
  "http://127.0.0.1:$port/index.php?m=writers&a=save&id=1")
assert_status 405 "$http_status" 'GET controller mutation'

http_status=$(curl -sS -X POST -b "$test_tmp/first.cookies" -o "$test_tmp/controller-missing.html" -w '%{http_code}' \
  "http://127.0.0.1:$port/index.php?m=writers&a=save&id=1")
assert_status 403 "$http_status" 'Controller mutation without CSRF token'

logout_csrf=$(perl -0777 -ne 'print $1 if /name="loginAction" value="logout".*?name="_csrf" value="([^"]+)"/s' "$test_tmp/first-home.html")
[[ -n "$logout_csrf" ]]
http_status=$(curl -sS -b "$test_tmp/first.cookies" -o "$test_tmp/logout-get.html" -w '%{http_code}' \
  "http://127.0.0.1:$port/index.php?loginAction=logout")
assert_status 405 "$http_status" 'GET logout'

http_status=$(curl -sS -X POST -b "$test_tmp/first.cookies" -o "$test_tmp/logout-missing.html" -w '%{http_code}' \
  --data-urlencode 'loginAction=logout' "http://127.0.0.1:$port/index.php")
assert_status 403 "$http_status" 'Logout without CSRF token'

http_status=$(curl -sS -b "$test_tmp/first.cookies" -c "$test_tmp/first.cookies" -o /dev/null -w '%{http_code}' \
  --data-urlencode 'loginAction=logout' --data-urlencode "_csrf=$logout_csrf" \
  "http://127.0.0.1:$port/index.php")
assert_status 303 "$http_status" 'Logout with valid CSRF token'

http_status=$(curl -sS -b "$test_tmp/first.cookies" -o /dev/null -w '%{http_code}' \
  "http://127.0.0.1:$port/index.php?m=frequency")
assert_status 302 "$http_status" 'Anonymous access after logout'

print "Phase 3A CSRF and method checks: $checks"
print 'All Phase 3A CSRF and method checks passed.'
