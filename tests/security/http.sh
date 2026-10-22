#!/bin/zsh

set -euo pipefail

repo_dir=${0:A:h:h:h}
test_db="meanma_security_http_test_$(openssl rand -hex 6)"
test_tmp=$(mktemp -d /tmp/meanma-security-http.XXXXXX)
server_pid=''
port=18765
typeset -a database_env
[[ -n "${MEANMA_TEST_DB_HOST:-}" ]] && database_env+=("MEANMA_DB_HOST=$MEANMA_TEST_DB_HOST")
[[ -n "${MEANMA_TEST_DB_USER:-}" ]] && database_env+=("MEANMA_DB_USER=$MEANMA_TEST_DB_USER")
[[ -n "${MEANMA_TEST_DB_PASSWORD+x}" ]] && database_env+=("MEANMA_DB_PASSWORD=$MEANMA_TEST_DB_PASSWORD")

cleanup() {
  if [[ -n "$server_pid" ]]; then
    kill "$server_pid" 2>/dev/null || true
    wait "$server_pid" 2>/dev/null || true
  fi
  php "$repo_dir/tests/security/http_fixture.php" drop "$test_db" || true
  rm -rf "$test_tmp"
}
trap cleanup EXIT

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
  if curl -sS "http://127.0.0.1:$port/index.php" -o "$test_tmp/login.html" 2>/dev/null; then
    break
  fi
  sleep 0.1
done
if grep -q 'list-group list-group-flush' "$test_tmp/login.html"; then
  print -u2 'FAIL: application home controller rendered beneath anonymous login'
  exit 1
fi

assert_status() {
  local expected=$1
  local actual=$2
  local label=$3
  if [[ "$actual" != "$expected" ]]; then
    print -u2 "FAIL: $label returned $actual, expected $expected"
    print -u2 -- "--- server log ---"
    tail -n 40 "$test_tmp/server.log" >&2
    exit 1
  fi
}

http_status=$(curl -sS -o "$test_tmp/corpus.html" -D "$test_tmp/corpus.headers" -w '%{http_code}' \
  "http://127.0.0.1:$port/index.php?m=corpus&a=xsearch")
assert_status 302 "$http_status" 'Anonymous corpus search'
grep -qi '^Location: index.php' "$test_tmp/corpus.headers"

http_status=$(curl -sS -o "$test_tmp/frequency.html" -w '%{http_code}' \
  "http://127.0.0.1:$port/index.php?m=frequency")
assert_status 302 "$http_status" 'Anonymous frequency list'

http_status=$(curl -sS -o "$test_tmp/ajax.json" -w '%{http_code}' -H 'Accept: application/json' \
  "http://127.0.0.1:$port/ajax.php?action=search&q=test")
assert_status 401 "$http_status" 'Anonymous AJAX search'
grep -q 'authentication_required' "$test_tmp/ajax.json"

http_status=$(curl -sS -o "$test_tmp/print.html" -w '%{http_code}' \
  "http://127.0.0.1:$port/printSlip.php")
assert_status 302 "$http_status" 'Anonymous direct application script'

http_status=$(curl -sS -o "$test_tmp/builder.html" -w '%{http_code}' \
  "http://127.0.0.1:$port/build-frequencies.php")
assert_status 404 "$http_status" 'Web access to CLI corpus utility'

curl -sS -c "$test_tmp/active.cookies" \
  "http://127.0.0.1:$port/index.php" -o "$test_tmp/active-login.html"
csrf=$(perl -0777 -ne 'print $1 if /name="loginAction" value="login".*?name="_csrf" value="([^"]+)"/s' \
  "$test_tmp/active-login.html")
[[ -n "$csrf" ]]
http_status=$(curl -sS -b "$test_tmp/active.cookies" -c "$test_tmp/active.cookies" \
  -o "$test_tmp/active-home.html" -w '%{http_code}' \
  --data-urlencode 'loginAction=login' \
  --data-urlencode 'email=active@example.test' \
  --data-urlencode 'password=test-password' \
  --data-urlencode "_csrf=$csrf" \
  "http://127.0.0.1:$port/index.php")
assert_status 200 "$http_status" 'Existing active account login'
grep -q 'logged in as HTTP Tester' "$test_tmp/active-home.html"
application_csrf=$(perl -ne 'print $1 if /name="csrf-token" content="([^"]+)"/' "$test_tmp/active-home.html")
[[ -n "$application_csrf" ]]

http_status=$(curl -sS -b "$test_tmp/active.cookies" -o "$test_tmp/forbidden-group.json" \
  -w '%{http_code}' -H 'Accept: application/json' \
  "http://127.0.0.1:$port/ajax.php?action=loadSlipData&id=1&groupId=99")
assert_status 403 "$http_status" 'Authenticated cross-group request'

http_status=$(curl -sS -b "$test_tmp/active.cookies" -o "$test_tmp/allowed-group.json" \
  -w '%{http_code}' -H 'Accept: application/json' -X POST \
  --data-urlencode "_csrf=$application_csrf" \
  "http://127.0.0.1:$port/ajax.php?action=setGroup&groupId=1")
assert_status 200 "$http_status" 'Authenticated existing-group request'

curl -sS -c "$test_tmp/inactive.cookies" \
  "http://127.0.0.1:$port/index.php" -o "$test_tmp/inactive-login.html"
csrf=$(perl -0777 -ne 'print $1 if /name="loginAction" value="login".*?name="_csrf" value="([^"]+)"/s' \
  "$test_tmp/inactive-login.html")
http_status=$(curl -sS -b "$test_tmp/inactive.cookies" -c "$test_tmp/inactive.cookies" \
  -o "$test_tmp/inactive-result.html" -w '%{http_code}' \
  --data-urlencode 'loginAction=login' \
  --data-urlencode 'email=inactive@example.test' \
  --data-urlencode 'password=test-password' \
  --data-urlencode "_csrf=$csrf" \
  "http://127.0.0.1:$port/index.php")
assert_status 200 "$http_status" 'Inactive account rejection'
grep -q 'Login was unsuccessful' "$test_tmp/inactive-result.html"
if grep -q 'logged in as HTTP Tester' "$test_tmp/inactive-result.html"; then
  print -u2 'FAIL: inactive account was authenticated'
  exit 1
fi

http_status=$(curl -sS -o "$test_tmp/unsafe-reset.html" -w '%{http_code}' \
  --data-urlencode 'loginAction=savePassword' \
  --data-urlencode 'email=active@example.test' \
  --data-urlencode 'pass1=attacker-password' \
  --data-urlencode 'pass2=attacker-password' \
  "http://127.0.0.1:$port/index.php")
assert_status 200 "$http_status" 'Unauthorised password replacement rejection page'
grep -q 'password could not be changed' "$test_tmp/unsafe-reset.html"

curl -sS -c "$test_tmp/post-attack.cookies" \
  "http://127.0.0.1:$port/index.php" -o "$test_tmp/post-attack-login.html"
csrf=$(perl -0777 -ne 'print $1 if /name="loginAction" value="login".*?name="_csrf" value="([^"]+)"/s' \
  "$test_tmp/post-attack-login.html")
http_status=$(curl -sS -b "$test_tmp/post-attack.cookies" -c "$test_tmp/post-attack.cookies" \
  -o "$test_tmp/post-attack-home.html" -w '%{http_code}' \
  --data-urlencode 'loginAction=login' \
  --data-urlencode 'email=active@example.test' \
  --data-urlencode 'password=test-password' \
  --data-urlencode "_csrf=$csrf" \
  "http://127.0.0.1:$port/index.php")
assert_status 200 "$http_status" 'Original password after rejected takeover attempt'
grep -q 'logged in as HTTP Tester' "$test_tmp/post-attack-home.html"

print 'HTTP security checks: 12'
print 'All HTTP security checks passed.'
