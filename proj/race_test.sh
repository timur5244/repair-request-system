#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${BASE_URL:-http://127.0.0.1:8000}"
MASTER_EMAIL="${MASTER_EMAIL:-master1@example.test}"

echo "[1/4] Создаём заявку и назначаем на мастера (${MASTER_EMAIL})..."

php artisan tinker --execute="
use App\\Models\\User;
use App\\Models\\Request as R;
use App\\Domain\\Enums\\RequestStatus;

\$m = User::where('email', '${MASTER_EMAIL}')->firstOrFail();
\$r = R::create([
  'clientName' => 'Race Client',
  'phone' => '+79990000000',
  'address' => 'Race Address',
  'problemText' => 'Race test',
  'status' => RequestStatus::Assigned,
  'assignedTo' => \$m->id,
  'version' => 1,
]);
echo \$r->id;
"

REQ_ID="$(php artisan tinker --execute="echo App\\Models\\Request::query()->orderByDesc('id')->value('id');")"

echo "[2/4] Логинимся как мастер через /login (cookie jar)..."

COOKIE_JAR="$(mktemp)"
LOGIN_PAGE="$(curl -s -c "$COOKIE_JAR" "$BASE_URL/login")"
CSRF="$(echo "$LOGIN_PAGE" | perl -ne 'if(/name=\"_token\" value=\"([^\"]+)\"/){print $1; exit}')"
MASTER_ID="$(php artisan tinker --execute="echo App\\Models\\User::where('email','${MASTER_EMAIL}')->value('id');")"

curl -s -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
  -X POST "$BASE_URL/login" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  --data-urlencode "_token=$CSRF" \
  --data-urlencode "user_id=$MASTER_ID" >/dev/null

echo "[3/4] Делаем 2 параллельных запроса 'Взять в работу' (ожидаем: один 200, второй 409)..."

TAKE_URL="$BASE_URL/master/requests/$REQ_ID/take"

curl -s -b "$COOKIE_JAR" -X POST "$TAKE_URL" -H "Accept: application/json" -d "version=1" -w "\nHTTP:%{http_code}\n" > /tmp/race_take_1.txt &
curl -s -b "$COOKIE_JAR" -X POST "$TAKE_URL" -H "Accept: application/json" -d "version=1" -w "\nHTTP:%{http_code}\n" > /tmp/race_take_2.txt &
wait

echo "---- response #1 ----"
cat /tmp/race_take_1.txt
echo "---- response #2 ----"
cat /tmp/race_take_2.txt

echo "[4/4] Готово. Проверьте, что один ответ 200, второй 409 с текстом 'Заявка уже взята в работу'."
rm -f "$COOKIE_JAR"

