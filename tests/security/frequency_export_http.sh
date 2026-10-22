#!/bin/zsh
set -euo pipefail

repo_dir=${0:A:h:h:h}
test_db="meanma_security_http_test_$(openssl rand -hex 6)"
test_tmp=$(mktemp -d /tmp/meanma-frequency-export-http.XXXXXX)
app_pid=''
api_pid=''
app_port=18770
api_port=18771
checks=0
typeset -a database_env
[[ -n "${MEANMA_TEST_DB_HOST:-}" ]] && database_env+=("MEANMA_DB_HOST=$MEANMA_TEST_DB_HOST")
[[ -n "${MEANMA_TEST_DB_USER:-}" ]] && database_env+=("MEANMA_DB_USER=$MEANMA_TEST_DB_USER")
[[ -n "${MEANMA_TEST_DB_PASSWORD+x}" ]] && database_env+=("MEANMA_DB_PASSWORD=$MEANMA_TEST_DB_PASSWORD")

cleanup() {
  local result=$?
  [[ -n "$app_pid" ]] && { kill "$app_pid" 2>/dev/null || true; wait "$app_pid" 2>/dev/null || true; }
  [[ -n "$api_pid" ]] && { kill "$api_pid" 2>/dev/null || true; wait "$api_pid" 2>/dev/null || true; }
  php "$repo_dir/tests/security/http_fixture.php" drop "$test_db" || true
  rm -rf "$test_tmp"
  return $result
}
trap cleanup EXIT

assert_status() {
  local expected=$1 actual=$2 label=$3
  checks=$((checks + 1))
  [[ "$actual" == "$expected" ]] || { print -u2 "FAIL: $label returned $actual, expected $expected"; tail -n 50 "$test_tmp/app.log" >&2; exit 1; }
}

assert_header() {
  local pattern=$1 file=$2 label=$3
  checks=$((checks + 1))
  grep -Eqi "$pattern" "$file" || { print -u2 "FAIL: $label"; cat "$file" >&2; exit 1; }
}

login_account() {
  local cookie=$1
  curl -sS -c "$cookie" "http://127.0.0.1:$app_port/index.php?loginAction=loginPage" -o "$test_tmp/login.html"
  local csrf
  csrf=$(perl -0777 -ne 'print $1 if /name="loginAction" value="login".*?name="_csrf" value="([^"]+)"/s' "$test_tmp/login.html")
  curl -sS -b "$cookie" -c "$cookie" -o /dev/null \
    --data-urlencode 'loginAction=login' --data-urlencode 'email=active@example.test' \
    --data-urlencode 'password=test-password' --data-urlencode "_csrf=$csrf" \
    "http://127.0.0.1:$app_port/index.php"
}

mkdir "$test_tmp/exports"
php "$repo_dir/tests/security/http_fixture.php" create "$test_db"
MEANMA_FREQUENCY_FIXTURE_LOG="$test_tmp/api.log" php -S "127.0.0.1:$api_port" "$repo_dir/tests/security/frequency_api_fixture.php" >"$test_tmp/api-server.log" 2>&1 &
api_pid=$!
env "${database_env[@]}" MEANMA_DB_NAME="$test_db" MEANMA_APP_ORIGIN="http://127.0.0.1:$app_port" \
MEANMA_ALLOW_INSECURE_ORIGIN=1 MEANMA_COOKIE_SECURE=0 MEANMA_DISABLE_EMAIL=1 \
MEANMA_RATE_LIMIT_DIR="$test_tmp/rates" MEANMA_EXPORT_TMP_DIR="$test_tmp/exports" \
MEANMA_FREQUENCY_API_ORIGIN="http://127.0.0.1:$api_port" \
php -S "127.0.0.1:$app_port" -t "$repo_dir" >"$test_tmp/app.log" 2>&1 &
app_pid=$!

for attempt in {1..30}; do
  curl -sS "http://127.0.0.1:$api_port/frequency/lemma?limit=1" -o /dev/null 2>/dev/null && \
  curl -sS "http://127.0.0.1:$app_port/index.php?loginAction=loginPage" -o /dev/null 2>/dev/null && break
  sleep 0.1
done

http_status=$(curl -sS -D "$test_tmp/anonymous.headers" -o /dev/null -w '%{http_code}' \
  "http://127.0.0.1:$app_port/index.php?m=frequency&a=export&type=lemma")
assert_status 302 "$http_status" 'Anonymous frequency export'
assert_header '^Location: index.php\?loginAction=loginPage' "$test_tmp/anonymous.headers" 'Anonymous export did not redirect to login'

login_account "$test_tmp/user.cookies"
http_status=$(curl -sS -b "$test_tmp/user.cookies" -o "$test_tmp/frequency.html" -w '%{http_code}' \
  "http://127.0.0.1:$app_port/index.php?m=frequency")
assert_status 200 "$http_status" 'Frequency-list interface'
grep -q 'id="frequencyExport" class="btn btn-success"' "$test_tmp/frequency.html" || { print -u2 'FAIL: Excel export button is missing'; exit 1; }
checks=$((checks + 1))
grep -q 'Export to Excel' "$test_tmp/frequency.html" || { print -u2 'FAIL: Excel export label is missing'; exit 1; }
checks=$((checks + 1))
curl -sS -b "$test_tmp/user.cookies" \
  "http://127.0.0.1:$app_port/ajax.php?action=getFrequencies&type=lemma&start=1&limit=1" \
  -o "$test_tmp/existing-frequency.json"
grep -q '"term":"=2+2","frequency":1003' "$test_tmp/existing-frequency.json" || { print -u2 'FAIL: existing frequency API response changed'; exit 1; }
checks=$((checks + 1))
! grep -q 'pos_counts' "$test_tmp/existing-frequency.json" || { print -u2 'FAIL: existing frequency API gained export-only metadata'; exit 1; }
checks=$((checks + 1))
: > "$test_tmp/api.log"
http_status=$(curl -sS -b "$test_tmp/user.cookies" -D "$test_tmp/lemma.headers" -o "$test_tmp/lemma.xlsx" -w '%{http_code}' \
  "http://127.0.0.1:$app_port/index.php?m=frequency&a=export&type=lemma&pos=n&min=2&max=2000&order=asc")
assert_status 200 "$http_status" 'Lemma frequency export'
assert_header '^Content-Type: application/vnd\.openxmlformats-officedocument\.spreadsheetml\.sheet' "$test_tmp/lemma.headers" 'Lemma XLSX content type'
assert_header '^Content-Disposition: attachment; filename="meanma-lemma-frequency-[0-9]{4}-[0-9]{2}-[0-9]{2}\.xlsx"' "$test_tmp/lemma.headers" 'Lemma filename'
assert_header '^X-Content-Type-Options: nosniff' "$test_tmp/lemma.headers" 'XLSX nosniff header'
php "$repo_dir/tests/security/inspect_frequency_export.php" "$test_tmp/lemma.xlsx" lemma 1003
checks=$((checks + 1))

starts=$(php -r '$lines=file($argv[1], FILE_IGNORE_NEW_LINES); echo implode(",", array_map(fn($line)=>json_decode($line,true)["start"], $lines));' "$test_tmp/api.log")
[[ "$starts" == '1,501,1001' ]] || { print -u2 "FAIL: export batches were $starts"; exit 1; }
checks=$((checks + 1))
grep -q '"pos":"n","min":"2","max":"2000","order":"asc"' "$test_tmp/api.log" || { print -u2 'FAIL: filters were not preserved'; exit 1; }
checks=$((checks + 1))
[[ -z "$(find "$test_tmp/exports" -mindepth 1 -maxdepth 1 -print -quit)" ]] || { print -u2 'FAIL: temporary export file was not removed'; exit 1; }
checks=$((checks + 1))

http_status=$(curl -sS -b "$test_tmp/user.cookies" -D "$test_tmp/word.headers" -o "$test_tmp/word.xlsx" -w '%{http_code}' \
  "http://127.0.0.1:$app_port/index.php?m=frequency&a=export&type=word-form&order=asc")
assert_status 200 "$http_status" 'Word-form frequency export'
assert_header 'filename="meanma-word-form-frequency-' "$test_tmp/word.headers" 'Word-form filename'
php "$repo_dir/tests/security/inspect_frequency_export.php" "$test_tmp/word.xlsx" word-form 3
checks=$((checks + 1))

http_status=$(curl -sS -b "$test_tmp/user.cookies" -o "$test_tmp/empty.xlsx" -w '%{http_code}' \
  "http://127.0.0.1:$app_port/index.php?m=frequency&a=export&type=lemma&min=999999")
assert_status 200 "$http_status" 'Empty frequency export'
php "$repo_dir/tests/security/inspect_frequency_export.php" "$test_tmp/empty.xlsx" lemma 0
checks=$((checks + 1))

http_status=$(curl -sS -b "$test_tmp/user.cookies" -o "$test_tmp/invalid.txt" -w '%{http_code}' \
  "http://127.0.0.1:$app_port/index.php?m=frequency&a=export&type=invalid")
assert_status 400 "$http_status" 'Invalid frequency export parameters'
grep -q 'Invalid frequency type' "$test_tmp/invalid.txt" || { print -u2 'FAIL: invalid parameter response was unclear'; exit 1; }
checks=$((checks + 1))

print "Frequency export HTTP checks: $checks"
print 'All frequency export HTTP checks passed.'
