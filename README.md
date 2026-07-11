# PennyWise Rest API

RESTful API для системы учета и анализа персональных финансов.

## Описание проекта

PennyWise Rest API предоставляет backend-инфраструктуру для сервиса учёта личных финансов. API поддерживает управление транзакциями, кошельками, категориями, тегами, счетами, а также предоставляет данные для виджетов дашборда.

## Основные возможности

- **Транзакции** — учёт доходов и расходов
- **Теги** — классификация транзакций по тегам
- **Кошельки** — управление кошельками и балансами
- **Категории** — категоризация доходов и расходов
- **Счета** — управление банковскими счетами
- **Пользователи** — регистрация и управление профилями
- **Настройки пользователей** — персонализация интерфейса
- **Виджеты дашборда** — API для получения данных виджетов
- **Верификация email** — массовая проверка списка строк на валидность (формат + MX-запись), без отправки письма-подтверждения
- **JWT авторизация** — безопасная аутентификация

## Архитектура

### Структура проекта

```
PennyWiseRestApi/
├── docker/                     # Docker конфигурации
│   ├── nginx/
│   │   ├── Dockerfile
│   │   └── nginx.conf          # Reverse proxy + балансировка между репликами app
│   ├── php/
│   │   ├── Dockerfile
│   │   └── entrypoint.sh       # Пишет /tmp/started_at для uptime в /api/v1/health
│   └── mysql/
│       ├── Dockerfile
│       └── my.cnf              # utf8mb4 по умолчанию
├── bin/                         # CLI-скрипты
│   ├── migrate.php              # Раннер миграций (database/migrations/*.sql)
│   └── health-check.php         # Проверка БД для Docker healthcheck
├── src/                         # Исходный код приложения
│   ├── Core/                    # Router, DI-контейнер, Kernel, Request/Response, JWT
│   ├── Middleware/              # Auth (JWT), CORS
│   └── Modules/                 # Модули (Transactions, Tags, Wallets, Accounts, ...)
│       └── {ModuleName}/
│           ├── Controllers/
│           │   └── V1/          # Версионированные контроллеры модуля
│           ├── Models/
│           ├── Services/
│           ├── Repositories/
│           ├── DTO/
│           ├── Validators/
│           ├── Routes/
│           │   └── v1.php       # Версионированные маршруты модуля
│           └── Module.php
├── database/
│   └── migrations/              # SQL-миграции, применяются bin/migrate.php по порядку
├── public/                      # Публичная директория (document root nginx)
│   └── index.php                # Front controller
├── tests/                       # PHPUnit-тесты
├── composer.json
├── docker-compose.yml
└── README.md
```

Версионирование живёт внутри каждого модуля (`Controllers/V1`, `Routes/v1.php`), а не в отдельном сквозном каталоге — это позволяет добавить `V2` конкретному модулю, не трогая остальные, и не дублирует Models/Services на два уровня (общий и модульный).

### Версионирование API

API версионируется через URL: `/api/v1/...`, `/api/v2/...`

### Модульная структура

Каждый функциональный модуль (транзакции, теги, кошельки и т.д.) реализован как отдельный модуль с собственной структурой:
- Контроллеры
- Модели
- Сервисы
- Валидаторы
- Маршруты

## Технологии

- **PHP 8.2+**
- **MySQL 8.0** — основная база данных
- **Nginx** — веб-сервер
- **PHP-FPM** — процесс-менеджер PHP
- **Docker & Docker Compose** — контейнеризация
- **JWT** — аутентификация

## Docker окружение

Проект использует Docker Compose минимум с тремя сервисами:

| Сервис | Описание | Порт |
|--------|----------|------|
| `nginx` | Reverse proxy и балансировщик нагрузки между репликами `app` | 8080 → 80 |
| `app` | PHP-FPM (масштабируемый, без публичного порта) | 9000 (внутр.) |
| `db` | MySQL 8.0 | 3306 |

`app` можно масштабировать на несколько реплик — `nginx` распределяет FastCGI-запросы между ними и исключает из ротации упавшие контейнеры (подробнее — [SDD, раздел 6](docs/SDD.md#6-инфраструктура)).

### Быстрый старт

```bash
# Клонировать репозиторий
git clone https://github.com/SCaeR42/PennyWiseRestApi.git
cd PennyWiseRestApi

# Запустить контейнеры (можно поднять несколько реплик app)
docker compose up -d --scale app=2

# Выполнить миграции
docker compose exec app php bin/migrate.php

# API доступно по адресу
# http://localhost:8080/api/v1/

# Проверить состояние API и живых PHP-воркеров
curl http://localhost:8080/api/v1/health
```

Интерактивная документация (Swagger UI) — [http://localhost:8080/docs/](http://localhost:8080/docs/), спецификация — [`public/openapi.yaml`](public/openapi.yaml) (OpenAPI 3.0.3).

health — [http://api/v1/healths/](http://localhost:8080/api/v1/health)


## Тестирование

Проект покрыт unit- и интеграционными тестами (**PHPUnit 11**, 34 теста). Конфигурация — [`phpunit.xml`](phpunit.xml), тесты находятся в директории [`tests/`](tests/).

### Запуск тестов

```bash
# Локально — запустить все тесты
vendor\bin\phpunit

# Локально — запустить конкретный файл
vendor\bin\phpunit tests/Unit/Core/ValidationTest.php

# Локально — фильтрация по имени теста
vendor\bin\phpunit --filter testMethodName

# В Docker-контейнере
docker compose exec app vendor/bin/phpunit

# Разовый запуск без запущенных контейнеров
docker compose run --rm app vendor/bin/phpunit
```

### Структура тестов

```
tests/
├── Support/
│   └── DnsResolverStub.php              # Мок checkdnsrr() для MxRecordChecker (см. ниже)
└── Unit/
    ├── Core/                             # Router, Container, Jwt, Validation
    └── Modules/
        └── EmailVerification/
            ├── EmailFormatValidatorTest.php
            ├── MxRecordCheckerTest.php          # MX / A-fallback / no_mx_record / lookup_failed / кэш
            └── EmailVerificationServiceTest.php # invalid_format не вызывает DNS-резолвер вообще
```

### Мок DNS-резолвера

`MxRecordChecker` вызывает глобальную `checkdnsrr()` — гонять реальный DNS в юнит-тестах не хочется (флаки, зависимость от сети). `tests/Support/DnsResolverStub.php` объявляет функцию `checkdnsrr()` в том же неймспейсе, что и `MxRecordChecker` (`App\Modules\EmailVerification\Services`): PHP при неквалифицированном вызове функции сначала ищет её в неймспейсе вызывающего файла и только потом откатывается на глобальную. Стаб подключается только через `composer.json → autoload-dev.files`, поэтому в сборке без dev-зависимостей (`composer install --no-dev`) его не существует, и `MxRecordChecker` всегда резолвит через настоящий `checkdnsrr()` — сам класс ради тестируемости не менялся.

`EmailVerificationServiceTest` на этом же стабе явно подтверждает то, что раньше было неявным допущением из чтения кода: для email с невалидным форматом DNS-резолвер вообще не вызывается.

## Документация

- [Software Design Description](docs/SDD.md) — детальное описание архитектуры и дизайна
- [OpenAPI-спецификация](public/openapi.yaml) — интерактивно на `/docs/` запущенного инстанса

## Лицензия

MIT
