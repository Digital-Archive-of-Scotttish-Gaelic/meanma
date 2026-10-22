#!/bin/zsh
set -euo pipefail

repo_dir=${0:A:h:h:h}
test_db="meanma_security_http_test_$(openssl rand -hex 6)"
test_tmp=$(mktemp -d /tmp/meanma-login-http.XXXXXX)
server_pid=''
port=18767
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

pass() { checks=$((checks + 1)); }

assert_status() {
  local expected=$1 actual=$2 label=$3
  if [[ "$actual" != "$expected" ]]; then
    print -u2 "FAIL: $label returned $actual, expected $expected"
    tail -n 50 "$test_tmp/server.log" >&2
    exit 1
  fi
  pass
}

assert_contains() {
  local pattern=$1 file=$2 label=$3
  if ! grep -Eq "$pattern" "$file"; then
    print -u2 "FAIL: $label"
    exit 1
  fi
  pass
}

assert_not_contains() {
  local pattern=$1 file=$2 label=$3
  if grep -Eqi "$pattern" "$file"; then
    print -u2 "FAIL: $label"
    exit 1
  fi
  pass
}

login_request() {
  local cookie_file=$1 next=$2 headers=$3
  curl -sS -c "$cookie_file" "http://127.0.0.1:$port/index.php?loginAction=loginPage" -o "$test_tmp/login-request.html"
  local csrf
  csrf=$(perl -0777 -ne 'print $1 if /name="loginAction" value="login".*?name="_csrf" value="([^"]+)"/s' "$test_tmp/login-request.html")
  [[ -n "$csrf" ]]
  curl -sS -b "$cookie_file" -c "$cookie_file" -D "$headers" -o /dev/null -w '%{http_code}' \
    --data-urlencode 'loginAction=login' \
    --data-urlencode 'email=active@example.test' \
    --data-urlencode 'password=test-password' \
    --data-urlencode "_csrf=$csrf" \
    --data-urlencode "next=$next" \
    "http://127.0.0.1:$port/index.php"
}

php "$repo_dir/tests/security/http_fixture.php" create "$test_db"
env "${database_env[@]}" MEANMA_DB_NAME="$test_db" \
MEANMA_APP_ORIGIN="http://127.0.0.1:$port" MEANMA_ALLOW_INSECURE_ORIGIN=1 \
MEANMA_COOKIE_SECURE=0 MEANMA_DISABLE_EMAIL=1 MEANMA_RATE_LIMIT_DIR="$test_tmp/rates" \
php -S "127.0.0.1:$port" -t "$repo_dir" >"$test_tmp/server.log" 2>&1 &
server_pid=$!

for attempt in {1..20}; do
  if curl -sS "http://127.0.0.1:$port/index.php?loginAction=loginPage" -o "$test_tmp/login.html" 2>/dev/null; then break; fi
  sleep 0.1
done

assert_contains '<form method="post" action="index.php" class="auth-form">' "$test_tmp/login.html" 'Dedicated login form is missing'
assert_contains '<nav class="navbar navbar-dark fixed-top auth-navbar"' "$test_tmp/login.html" 'Authentication header is missing'
assert_contains '<a class="navbar-brand" href="index.php">MEANⓂ️A</a>' "$test_tmp/login.html" 'MEANMA branding is missing from the authentication header'
assert_contains 'bootstrap/4\.4\.1/css/bootstrap\.min\.css' "$test_tmp/login.html" 'Authentication page is not using the application Bootstrap version'
assert_contains 'class="container auth-layout"' "$test_tmp/login.html" 'Authentication page is missing the standard content container'
assert_contains 'class="card auth-card"' "$test_tmp/login.html" 'Authentication form is not presented in a Bootstrap card'
assert_contains 'class="form-group"' "$test_tmp/login.html" 'Login form is missing Bootstrap form spacing'
assert_contains 'class="input-group auth-password"' "$test_tmp/login.html" 'Password field is missing Bootstrap input grouping'
assert_contains 'type="email"[^>]+autocomplete="username"' "$test_tmp/login.html" 'Email field semantics are missing'
assert_contains 'type="password"[^>]+autocomplete="current-password"' "$test_tmp/login.html" 'Password field semantics are missing'
assert_contains 'data-password-toggle="password"' "$test_tmp/login.html" 'Password visibility control is missing'
assert_not_contains 'title="browse corpus"|title="search corpus"|title="frequency lists"|id="logoutLink"|id="loggedInAs"' "$test_tmp/login.html" 'Login page exposed authenticated application navigation'
assert_not_contains 'loginModal|<select|active@example\.test|inactive@example\.test|HTTP Tester' "$test_tmp/login.html" 'Login page exposes modal or account data'

http_status=$(curl -sS -D "$test_tmp/root.headers" -o /dev/null -w '%{http_code}' "http://127.0.0.1:$port/index.php")
assert_status 302 "$http_status" 'Anonymous application root'
assert_contains '^Location: index.php\?loginAction=loginPage&next=%2Findex.php' "$test_tmp/root.headers" 'Root redirect did not preserve an internal destination'

http_status=$(curl -sS -D "$test_tmp/corpus.headers" -o /dev/null -w '%{http_code}' "http://127.0.0.1:$port/index.php?m=corpus&a=xsearch&q=test")
assert_status 302 "$http_status" 'Anonymous protected destination'
assert_contains '^Location: index.php\?loginAction=loginPage&next=%2Findex.php%3Fm%3Dcorpus%26a%3Dxsearch%26q%3Dtest' "$test_tmp/corpus.headers" 'Protected destination was not preserved safely'

http_status=$(curl -sS -H 'Accept: application/json' -o "$test_tmp/ajax.json" -w '%{http_code}' "http://127.0.0.1:$port/ajax.php?action=search")
assert_status 401 "$http_status" 'Anonymous AJAX request'
assert_contains '"reason":"login_required"' "$test_tmp/ajax.json" 'AJAX response lacks an authentication reason'

http_status=$(login_request "$test_tmp/safe.cookies" '/index.php?m=frequency' "$test_tmp/safe.headers")
assert_status 303 "$http_status" 'Login with safe destination'
assert_contains '^Location: /index.php\?m=frequency' "$test_tmp/safe.headers" 'Safe destination was not retained'

http_status=$(curl -sS -b "$test_tmp/safe.cookies" -D "$test_tmp/authenticated-login.headers" -o /dev/null -w '%{http_code}' \
  "http://127.0.0.1:$port/index.php?loginAction=loginPage")
assert_status 302 "$http_status" 'Authenticated login page visit'
assert_contains '^Location: index.php' "$test_tmp/authenticated-login.headers" 'Authenticated login page did not return to the application'

http_status=$(login_request "$test_tmp/external.cookies" 'https://evil.example/steal' "$test_tmp/external.headers")
assert_status 303 "$http_status" 'Login with external destination'
assert_contains '^Location: index.php' "$test_tmp/external.headers" 'External destination was not rejected'
assert_not_contains '^Location: https?://|^Location: //' "$test_tmp/external.headers" 'Login produced an external redirect'

curl -sS -c "$test_tmp/invalid.cookies" "http://127.0.0.1:$port/index.php?loginAction=loginPage" -o "$test_tmp/invalid-login.html"
invalid_csrf=$(perl -0777 -ne 'print $1 if /name="loginAction" value="login".*?name="_csrf" value="([^"]+)"/s' "$test_tmp/invalid-login.html")
http_status=$(curl -sS -b "$test_tmp/invalid.cookies" -o "$test_tmp/invalid-result.html" -w '%{http_code}' \
  --data-urlencode 'loginAction=login' --data-urlencode 'email=unknown@example.test' \
  --data-urlencode 'password=wrong-password' --data-urlencode "_csrf=$invalid_csrf" \
  "http://127.0.0.1:$port/index.php")
assert_status 200 "$http_status" 'Invalid login'
assert_contains 'Login was unsuccessful\. Check your details and try again\.' "$test_tmp/invalid-result.html" 'Invalid login did not use the generic error'
assert_contains 'class="alert alert-danger" role="alert"' "$test_tmp/invalid-result.html" 'Invalid login error is not presented accessibly'
assert_not_contains 'unknown@example\.test|not found|does not exist' "$test_tmp/invalid-result.html" 'Invalid login disclosed account existence'

http_status=$(curl -sS -o "$test_tmp/recovery.html" -w '%{http_code}' "http://127.0.0.1:$port/index.php?loginAction=forgotPassword")
assert_status 200 "$http_status" 'Password recovery form'
assert_contains 'name="loginAction" value="sendEmail"' "$test_tmp/recovery.html" 'Password recovery flow is unavailable'
assert_contains '<a class="navbar-brand" href="index.php">MEANⓂ️A</a>' "$test_tmp/recovery.html" 'Recovery page is missing MEANMA branding'
assert_contains 'class="container auth-layout"' "$test_tmp/recovery.html" 'Recovery page is missing the standard content container'
assert_contains 'class="form-group"' "$test_tmp/recovery.html" 'Recovery form is missing Bootstrap form spacing'
assert_not_contains 'title="browse corpus"|title="search corpus"|title="frequency lists"|id="logoutLink"' "$test_tmp/recovery.html" 'Recovery page exposed authenticated application navigation'
assert_not_contains 'active@example\.test|inactive@example\.test|HTTP Tester' "$test_tmp/recovery.html" 'Recovery page exposes account data'

recovery_token=$(php "$repo_dir/tests/security/http_fixture.php" set-recovery "$test_db")
http_status=$(curl -sS -c "$test_tmp/recovery.cookies" -o "$test_tmp/reset.html" -w '%{http_code}' \
  --get --data-urlencode 'loginAction=resetPassword' --data-urlencode 'email=active@example.test' \
  --data-urlencode "token=$recovery_token" "http://127.0.0.1:$port/index.php")
assert_status 200 "$http_status" 'Valid password recovery link'
assert_contains '<a class="navbar-brand" href="index.php">MEANⓂ️A</a>' "$test_tmp/reset.html" 'Password-reset page is missing MEANMA branding'
assert_contains 'class="input-group auth-password"' "$test_tmp/reset.html" 'Password-reset fields are missing Bootstrap input grouping'
assert_not_contains 'title="browse corpus"|title="search corpus"|title="frequency lists"|id="logoutLink"' "$test_tmp/reset.html" 'Password-reset page exposed authenticated application navigation'
reset_csrf=$(perl -ne 'print $1 if /name="_csrf" value="([^"]+)"/' "$test_tmp/reset.html")
[[ -n "$reset_csrf" ]]
http_status=$(curl -sS -b "$test_tmp/recovery.cookies" -D "$test_tmp/recovery-complete.headers" -o /dev/null -w '%{http_code}' \
  --data-urlencode 'loginAction=savePassword' --data-urlencode 'email=active@example.test' \
  --data-urlencode "token=$recovery_token" --data-urlencode 'pass1=recovered-test-password' \
  --data-urlencode 'pass2=recovered-test-password' --data-urlencode "_csrf=$reset_csrf" \
  "http://127.0.0.1:$port/index.php")
assert_status 303 "$http_status" 'Password recovery completion'
assert_contains '^Location: index.php\?loginAction=loginPage&passwordChanged=1' "$test_tmp/recovery-complete.headers" 'Recovery completion did not return to login'

print "Phase 4 login-page HTTP checks: $checks"
print 'All Phase 4 login-page HTTP checks passed.'
