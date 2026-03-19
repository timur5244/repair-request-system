# Заявки в ремонтную службу (Laravel + SQLite)

## Запуск

```bash
cd "proj"
php artisan migrate
php artisan db:seed
php artisan serve
```

Откройте `http://127.0.0.1:8000`.

## Docker

### Предварительные требования

- Docker + Docker Compose (плагин `docker compose`)

### Быстрый старт

Из корня репозитория (папка где лежит `docker-compose.yml`):

```bash
chmod +x start.sh stop.sh restart.sh
./start.sh
```

- Если у вас уже запущен другой стек и вы хотите поднять второй (например, dev), используйте:
 
```bash
PROJECT_NAME=repair_requests_dev ./start.sh
```

- Приложение: `http://localhost:8000`
- Adminer: `http://localhost:8080` (SQLite файл будет доступен внутри контейнера по пути `/data/database.sqlite`)

Остановить:

```bash
./stop.sh
```

Или для dev-стека:

```bash
PROJECT_NAME=repair_requests_dev ./stop.sh
```

Перезапуск:

```bash
./restart.sh
```

### Полезные команды

```bash
docker compose -p repair_requests ps
docker compose -p repair_requests logs -f app
docker compose -p repair_requests exec app php artisan test
docker compose -p repair_requests exec app ./race_test.sh
```

### Устранение неполадок

- Если `composer install` падает:
  - проверьте, что `proj/composer.json` допускает PHP 8.2
- Если миграции/сидирование не применились:
  - `docker compose -p repair_requests exec app php artisan migrate --seed --force`
- Если не создаётся `database.sqlite`:
  - `docker compose -p repair_requests exec app ls -la database/`

### Данные SQLite и persistence

SQLite хранится в именованном volume `sqlite_data` и сохраняется между перезапусками контейнеров.

### Dev-режим (hot reload кода)

В `docker-compose.yml` подключён bind-mount `./proj:/var/www`, поэтому изменения в коде на хосте **сразу** видны в контейнере (пересборка не нужна).

При этом папка `/var/www/database` смонтирована отдельным volume `sqlite_data`, чтобы **SQLite файл сохранялся** между перезапусками и не попадал в bind-mount.

## Упрощённая аутентификация

- Страница входа: `/login`
- Можно **выбрать пользователя** из списка (после `db:seed` есть “Диспетчер” и “Мастер 1..3”)
- Или **ввести имя** — будет создан новый пользователь с ролью `dispatcher`

## Модели

- `App\Models\User`: `name`, `email`, `password`, `role` (`dispatcher|master`)
- `App\Models\Request`: `clientName`, `phone`, `address`, `problemText`, `status`, `assignedTo`, timestamps

## Слои (упрощённая “чистая архитектура”)

- `app/Domain/Enums/*` — доменные enum’ы
- `app/Application/Requests/RequestService.php` — сервисный слой (бизнес-операции над заявками)
- `app/Http/Controllers/*` — контроллеры (веб-слой)

## Проверка race condition (“Взять в работу”)

Механизм: optimistic locking через поле `requests.version`.

- Эндпоинт мастера: `POST /master/requests/{id}/take` (параметр `version`)
- При двух параллельных запросах с одинаковой `version`:
  - **первый** успешен
  - **второй** получает **`409 Conflict`** с текстом **“Заявка уже взята в работу”**

### Автотест

```bash
php artisan test --filter MasterTakeInWorkRaceTest
```

### Ручной тест (curl параллельно)

1) Запустите сервер:

```bash
php artisan serve
```

2) В другом окне (готовый скрипт):

```bash
chmod +x race_test.sh
./race_test.sh
```

### Ручной тест (2 терминала, curl руками)

Подготовка (выполнить один раз в терминале A):

```bash
# 1) залогиниться как мастер и сохранить cookies
COOKIE_JAR="$(mktemp)"
LOGIN_PAGE="$(curl -s -c "$COOKIE_JAR" http://127.0.0.1:8000/login)"
CSRF="$(echo "$LOGIN_PAGE" | perl -ne 'if(/name=\"_token\" value=\"([^\"]+)\"/){print $1; exit}')"
MASTER_ID="$(php artisan tinker --execute=\"echo App\\\\Models\\\\User::where('email','master1@example.test')->value('id');\")"
curl -s -b "$COOKIE_JAR" -c "$COOKIE_JAR" -X POST http://127.0.0.1:8000/login \
  -H "Content-Type: application/x-www-form-urlencoded" \
  --data-urlencode "_token=$CSRF" \
  --data-urlencode "user_id=$MASTER_ID" >/dev/null

# 2) создать тестовую заявку в assigned и назначить на мастера, получить её id
REQ_ID="$(php artisan tinker --execute=\"use App\\\\Models\\\\User; use App\\\\Models\\\\Request as R; use App\\\\Domain\\\\Enums\\\\RequestStatus; \\$m=User::where('email','master1@example.test')->firstOrFail(); \\$r=R::create(['clientName'=>'Race Client','phone'=>'+7999','address'=>'Race','problemText'=>'Race','status'=>RequestStatus::Assigned,'assignedTo'=>\\$m->id,'version'=>1]); echo \\$r->id;\")"
echo "REQ_ID=$REQ_ID"
```

Теперь одновременно:

- Терминал A:

```bash
curl -s -b "$COOKIE_JAR" -X POST "http://127.0.0.1:8000/master/requests/$REQ_ID/take" \
  -H "Accept: application/json" -d "version=1" -w "\nHTTP:%{http_code}\n"
```

- Терминал B (быстро запустить то же самое):

```bash
curl -s -b "$COOKIE_JAR" -X POST "http://127.0.0.1:8000/master/requests/$REQ_ID/take" \
  -H "Accept: application/json" -d "version=1" -w "\nHTTP:%{http_code}\n"
```

Ожидание: один ответ **HTTP 200**, второй **HTTP 409** с текстом **“Заявка уже взята в работу”**.

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).


