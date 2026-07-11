# Software Design Description

## PennyWise Rest API

**Версия:** 1.0  
**Дата:** 2026-06-29  
**Статус:** Draft

---

## 1. Введение

### 1.1 Назначение

Документ описывает архитектуру и дизайн RESTful API для системы учета и анализа персональных финансов PennyWise.

### 1.2 Область применения

Система предоставляет API для:
- Учёта транзакций (доходы/расходы)
- Управления кошельками и счетами
- Категоризации и тегирования операций
- Визуализации данных через виджеты дашборда
- Управления пользователями и их настройками
- Вспомогательной верификации email-строк (формат + MX-запись)

### 1.3 Глоссарий

| Термин | Описание |
|--------|----------|
| Transaction | Финансовая операция (доход или расход) |
| Wallet | Кошелёк для учёта средств |
| Account | Банковский счёт |
| Category | Категория дохода или расхода |
| Tag | Произвольный тег для классификации |
| Widget | Компонент дашборда с данными |
| MX-запись | DNS-запись домена, указывающая почтовые серверы, ответственные за приём почты для этого домена |

---

## 2. Архитектура системы

### 2.1 Общая схема

```
┌─────────────────────────────────────────────────────────────┐
│                        Client (Web/Mobile)                   │
└───────────────────────────┬─────────────────────────────────┘
                            │ HTTPS
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                         Nginx (Reverse Proxy)                │
│                    Port: 8080 / SSL: 443                     │
└───────────────────────────┬─────────────────────────────────┘
                            │ FastCGI
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                      PHP-FPM Application                     │
│  ┌─────────────────────────────────────────────────────┐   │
│  │                   Router / Dispatcher                 │   │
│  └─────────────────────────────────────────────────────┘   │
│  ┌─────────────┐ ┌─────────────────────┐ ┌─────────────┐  │
│  │ Middleware  │ │       Modules        │ │  Services   │  │
│  │  (Auth,     │ │ (Controllers/V1,     │ │ (Business   │  │
│  │  CORS,      │ │  Routes/v1, ...)     │ │  Logic)     │  │
│  │  Validate)  │ │                       │ │             │  │
│  └─────────────┘ └─────────────────────┘ └─────────────┘  │
│                            │                                │
│  ┌─────────────────────────┴─────────────────────────────┐ │
│  │                    Repository Layer                    │ │
│  └─────────────────────────┬─────────────────────────────┘ │
└────────────────────────────┼───────────────────────────────┘
                             │ PDO
                             ▼
┌─────────────────────────────────────────────────────────────┐
│                       MySQL 8.0 Database                     │
│                        Port: 3306                            │
└─────────────────────────────────────────────────────────────┘
```

### 2.2 Слои архитектуры

| Слой | Ответственность |
|------|-----------------|
| **Presentation** | HTTP обработка, маршрутизация, контроллеры |
| **Application** | Сервисы, бизнес-логика, DTO |
| **Domain** | Модели, сущности, правила валидации |
| **Infrastructure** | Репозитории, работа с БД, внешние сервисы |

### 2.3 Принципы проектирования

- **SOLID** — принципы объектно-ориентированного дизайна
- **DRY** — отсутствие дублирования кода
- **Модульность** — каждый домен как изолированный модуль
- **Версионирование** — обратная совместимость через версии API
- **Dependency Injection** — управление зависимостями через контейнер

---

## 3. Модули системы

### 3.1 Структура модуля

Каждый модуль владеет своим срезом Presentation/Application/Domain/Infrastructure и самостоятельно версионируется — версия API не выносится в отдельный сквозной каталог (`src/Api/V1`), а живёт внутри `Controllers/` и `Routes/` каждого модуля. Это позволяет поднять `V2` для одного модуля, не трогая остальные, и не создаёт дублирующего дерева `Models/`/`Services/` на уровне `src/`.

```
src/Modules/{ModuleName}/
├── Controllers/
│   └── V1/                # Контроллеры версии v1
│       └── {ModuleName}Controller.php
├── Models/               # Eloquent/ActiveRecord модели
│   └── {ModuleName}.php
├── Services/             # Бизнес-логика
│   └── {ModuleName}Service.php
├── Repositories/         # Работа с данными
│   └── {ModuleName}Repository.php
├── DTO/                  # Data Transfer Objects
│   ├── Create{ModuleName}DTO.php
│   └── Update{ModuleName}DTO.php
├── Validators/           # Валидация входных данных
│   └── {ModuleName}Validator.php
├── Routes/
│   └── v1.php             # Маршруты версии v1 (регистрируются с префиксом /api/v1)
└── Module.php            # Точка входа модуля
```

`src/Core/` содержит сквозные, не привязанные к домену вещи: Router/Dispatcher, DI-контейнер, Kernel, базовые классы контроллеров/репозиториев.

### 3.2 Описание модулей

#### 3.2.1 Модуль Users (Пользователи)

**Назначение:** Управление ресурсом пользователя (без выдачи токенов — за это отвечает модуль Auth, см. 3.2.2).

| Метод | Endpoint | Описание |
|-------|----------|----------|
| POST | `/api/v1/users/register` | Регистрация |
| GET | `/api/v1/users/profile` | Получение профиля |
| PUT | `/api/v1/users/profile` | Обновление профиля |
| DELETE | `/api/v1/users/profile` | Удаление аккаунта |

#### 3.2.2 Модуль Auth (Авторизация)

**Назначение:** единая точка входа для аутентификации и жизненного цикла JWT. Все операции входа/выхода идут через этот модуль, а не через Users — это исключает дублирование логики выдачи токенов в двух местах.

| Метод | Endpoint | Описание |
|-------|----------|----------|
| POST | `/api/v1/auth/token` | Получение JWT токена |
| POST | `/api/v1/auth/refresh` | Обновление токена |
| POST | `/api/v1/auth/logout` | Инвалидация токена |

#### 3.2.3 Модуль Transactions (Транзакции)

**Назначение:** Учёт финансовых операций.

| Метод | Endpoint | Описание |
|-------|----------|----------|
| GET | `/api/v1/transactions` | Список транзакций |
| POST | `/api/v1/transactions` | Создание транзакции |
| GET | `/api/v1/transactions/{id}` | Получение транзакции |
| PUT | `/api/v1/transactions/{id}` | Обновление транзакции |
| DELETE | `/api/v1/transactions/{id}` | Удаление транзакции |

#### 3.2.4 Модуль Wallets (Кошельки)

**Назначение:** Управление кошельками.

| Метод | Endpoint | Описание |
|-------|----------|----------|
| GET | `/api/v1/wallets` | Список кошельков |
| POST | `/api/v1/wallets` | Создание кошелька |
| GET | `/api/v1/wallets/{id}` | Получение кошелька |
| PUT | `/api/v1/wallets/{id}` | Обновление кошелька |
| DELETE | `/api/v1/wallets/{id}` | Удаление кошелька |
| GET | `/api/v1/wallets/{id}/balance` | Баланс кошелька |

#### 3.2.5 Модуль Categories (Категории)

**Назначение:** Категоризация доходов и расходов.

| Метод | Endpoint | Описание |
|-------|----------|----------|
| GET | `/api/v1/categories` | Список категорий |
| POST | `/api/v1/categories` | Создание категории |
| GET | `/api/v1/categories/{id}` | Получение категории |
| PUT | `/api/v1/categories/{id}` | Обновление категории |
| DELETE | `/api/v1/categories/{id}` | Удаление категории |

#### 3.2.6 Модуль Tags (Теги)

**Назначение:** Тегирование транзакций.

| Метод | Endpoint | Описание |
|-------|----------|----------|
| GET | `/api/v1/tags` | Список тегов |
| POST | `/api/v1/tags` | Создание тега |
| PUT | `/api/v1/tags/{id}` | Обновление тега |
| DELETE | `/api/v1/tags/{id}` | Удаление тега |

#### 3.2.7 Модуль Accounts (Счета)

**Назначение:** Управление банковскими счетами.

| Метод | Endpoint | Описание |
|-------|----------|----------|
| GET | `/api/v1/accounts` | Список счетов |
| POST | `/api/v1/accounts` | Создание счёта |
| GET | `/api/v1/accounts/{id}` | Получение счёта |
| PUT | `/api/v1/accounts/{id}` | Обновление счёта |
| DELETE | `/api/v1/accounts/{id}` | Удаление счёта |

#### 3.2.8 Модуль Settings (Настройки)

**Назначение:** Пользовательские настройки.

| Метод | Endpoint | Описание |
|-------|----------|----------|
| GET | `/api/v1/settings` | Получение настроек |
| PUT | `/api/v1/settings` | Обновление настроек |

#### 3.2.9 Модуль Dashboard (Виджеты)

**Назначение:** Данные для виджетов дашборда.

| Метод | Endpoint | Описание |
|-------|----------|----------|
| GET | `/api/v1/dashboard/widgets/{name}` | Данные виджета |
| GET | `/api/v1/dashboard/widgets` | Список доступных виджетов |

#### 3.2.10 Модуль System (Health)

**Назначение:** проверка живости приложения и его зависимостей. Без авторизации (нужен для Docker `healthcheck` и внешнего мониторинга).

| Метод | Endpoint | Описание |
|-------|----------|----------|
| GET | `/api/v1/health` | Статус конкретного PHP-инстанса, обработавшего запрос |

**Пример ответа:**
```json
{
  "success": true,
  "data": {
    "status": "ok",
    "instance": "app_7f3a1c9e2b41",
    "db": "ok",
    "uptime": 1345
  }
}
```

`instance` — hostname контейнера (в Docker это его container ID), по которому видно, какая именно реплика `app` ответила.

**Как проверяется живость *всех* PHP-реплик, а не только одной.** Запрос к `/api/v1/health` через `nginx` (порт 8080) всегда попадает только в ту реплику, которую `nginx` выбрал для этого конкретного соединения (см. 6.1.1) — по нему нельзя опросить все реплики разом. Поэтому:
- каждый контейнер `app` проверяет **сам себя** через `healthcheck` в `docker-compose.yml` (6.1), вызывая `/api/v1/health` изнутри собственного контейнера, а не через балансировщик;
- Docker помечает нездоровые контейнеры `unhealthy`, они перестают попадать в DNS-ответ `127.0.0.11` и `nginx` больше не направляет на них трафик;
- чтобы вручную опросить конкретную реплику, к ней можно обратиться по индексу — `docker compose exec --index=1 app curl localhost/api/v1/health`, `--index=2` и т.д. — либо посмотреть агрегированный статус через `docker compose ps` / `docker inspect --format='{{.State.Health.Status}}' <container>`.

#### 3.2.11 Модуль EmailVerification (Верификация email)

**Назначение:** вспомогательная массовая проверка списка строк на то, являются ли они валидными, потенциально доставляемыми email-адресами — без отправки письма-подтверждения. Используется, например, при импорте контактов или предварительной чистке пользовательского ввода.

| Метод | Endpoint | Описание |
|-------|----------|----------|
| POST | `/api/v1/email-verification/verify` | Массовая проверка списка строк на валидность email |

**Алгоритм проверки** (для каждой строки из списка, последовательно):
1. **Формат** — строка проверяется регулярным выражением (упрощённый RFC 5322). Если не проходит — сразу `invalid_format`, DNS не запрашивается.
2. **MX-запись** — если формат валиден, для домена (часть после `@`) выполняется DNS-запрос MX-записей (`dns_get_record($domain, DNS_MX)`).
3. **Fallback на A/AAAA** — если MX-записей нет, по RFC 5321 почта может приниматься и по обычной A/AAAA-записи домена, поэтому перед тем как признать домен недоставляемым, дополнительно проверяется A/AAAA.
4. Полноценная отправка письма и SMTP-хендшейк (`RCPT TO`) **не выполняются** — это осознанное ограничение уровня "лёгкой" верификации, а не полной.

**Оптимизации batch-проверки:**
- Результат DNS-резолва домена кэшируется в рамках одного запроса — если в списке несколько адресов на одном домене (частый случай), DNS запрашивается один раз на домен, а не на email.
- Таймаут на резолв одного домена — 2 секунды; при таймауте/сетевой ошибке домену присваивается статус `lookup_failed` (не `no_mx_record` — это разные вещи: недоставляемый домен vs. невозможность сейчас проверить).
- Размер списка ограничен: **не более 100 строк за один запрос** (см. `MAX_BATCH_SIZE` в валидаторе) — защищает от DoS через дорогие DNS-запросы; для больших списков предполагается постраничная отправка клиентом. Дополнительно рекомендуется закрыть эндпоинт общим `Rate Limiting` (см. 7.1).

**Запрос:**
```json
{
  "emails": [
    "user@gmail.com",
    "invalid-email",
    "test@nonexistent-domain-xyz.invalid"
  ]
}
```

**Ответ:**
```json
{
  "success": true,
  "data": {
    "results": [
      { "email": "user@gmail.com", "domain": "gmail.com", "valid": true, "status": "valid" },
      { "email": "invalid-email", "domain": null, "valid": false, "status": "invalid_format" },
      { "email": "test@nonexistent-domain-xyz.invalid", "domain": "nonexistent-domain-xyz.invalid", "valid": false, "status": "no_mx_record" }
    ],
    "summary": { "total": 3, "valid": 1, "invalid": 2 }
  }
}
```

`status` — одно из: `valid`, `invalid_format`, `no_mx_record`, `lookup_failed`. Поле `valid` — простой алиас `status === "valid"` для клиентов, которым не нужна детализация причины.

Если тело запроса некорректно (не массив, пустой список, либо длина превышает лимит) — эндпоинт возвращает `400` со стандартной структурой ошибки (5.1), например `{ "code": "BATCH_TOO_LARGE", "message": "Maximum 100 emails per request" }`.

**Структура модуля** (отличается от стандартного шаблона 3.1 — сервис не хранит состояние и не имеет своей таблицы в БД, поэтому в нём нет `Models/`/`Repositories/`):
```
src/Modules/EmailVerification/
├── Controllers/
│   └── V1/
│       └── EmailVerificationController.php
├── Services/
│   ├── EmailVerificationService.php   # оркестрация: формат → MX → fallback A/AAAA
│   ├── EmailFormatValidator.php       # regex-проверка формата
│   └── MxRecordChecker.php            # DNS-запрос с таймаутом и кэшем на домен в рамках запроса
├── DTO/
│   └── VerifyEmailsRequestDTO.php
├── Validators/
│   └── VerifyEmailsRequestValidator.php  # структура запроса + лимит batch
├── Routes/
│   └── v1.php
└── Module.php
```

---

## 4. Модель данных

### 4.1 ER-диаграмма

```
┌──────────────┐     ┌──────────────────┐     ┌──────────────┐
│    users     │     │     accounts     │     │  categories  │
├──────────────┤     ├──────────────────┤     ├──────────────┤
│ id (PK)      │     │ id (PK)          │     │ id (PK)      │
│ email        │     │ user_id (FK)     │     │ user_id (FK) │
│ password     │     │ name             │     │ parent_id    │
│ name         │     │ type             │     │ name         │
│ created_at   │     │ requisites       │     │ type         │
│ updated_at   │     │ currency         │     └──────────────┘
└──────────────┘     │ created_at       │
                      └──────────────────┘

┌──────────────┐     ┌──────────────────┐     ┌──────────────┐
│  settings    │     │     wallets      │     │     tags     │
├──────────────┤     ├──────────────────┤     ├──────────────┤
│ id (PK)      │     │ id (PK)          │     │ id (PK)      │
│ user_id (FK) │     │ user_id (FK)     │     │ user_id (FK) │
│ setting_key  │     │ account_id (FK,  │     │ name         │
│ value        │     │   nullable)      │     │ color        │
│ created_at   │     │ name             │     └──────────────┘
│ updated_at   │     │ currency         │
└──────────────┘     │ balance          │
                      │ is_default       │
                      │ created_at       │
                      └──────────────────┘

              ┌──────────────────┐     ┌──────────────────┐
              │   transactions   │     │  transaction_tag │
              ├──────────────────┤     ├──────────────────┤
              │ id (PK)          │     │ transaction_id   │
              │ user_id (FK)     │────►│ tag_id           │
              │ wallet_id (FK)   │     └──────────────────┘
              │ category_id (FK) │
              │ type             │
              │ amount           │
              │ description      │
              │ date             │
              │ created_at       │
              └──────────────────┘
```

**Связи (FK):**
- `accounts.user_id` → `users.id`
- `wallets.user_id` → `users.id`
- `wallets.account_id` → `accounts.id` (nullable — виртуальный/наличный кошелёк может быть не привязан ни к одному счёту)
- `categories.user_id` → `users.id`
- `categories.parent_id` → `categories.id` (self-reference, вложенные категории)
- `tags.user_id` → `users.id`
- `settings.user_id` → `users.id`
- `transactions.user_id` → `users.id`
- `transactions.wallet_id` → `wallets.id`
- `transactions.category_id` → `categories.id`
- `transaction_tag.transaction_id` → `transactions.id`
- `transaction_tag.tag_id` → `tags.id`

Связь транзакций и тегов — только через `transaction_tag` (many-to-many); прямого FK между `transactions` и `tags` нет.

### 4.2 Описание таблиц

#### users
| Поле | Тип | Описание |
|------|-----|----------|
| id | BIGINT UNSIGNED PK | Идентификатор |
| email | VARCHAR(255) UNIQUE | Email |
| password | VARCHAR(255) | Хеш пароля |
| name | VARCHAR(100) | Имя пользователя |
| created_at | TIMESTAMP | Дата создания |
| updated_at | TIMESTAMP | Дата обновления |

#### accounts
| Поле | Тип | Описание |
|------|-----|----------|
| id | BIGINT UNSIGNED PK | Идентификатор |
| user_id | BIGINT UNSIGNED FK | Владелец |
| name | VARCHAR(100) | Название (например, "Тинькофф Дебетовая") |
| type | ENUM('bank','card','cash','e-wallet') | Тип счёта |
| requisites | VARCHAR(255) NULL | Маскированный номер/реквизиты |
| currency | CHAR(3) | Валюта (ISO 4217) |
| created_at | TIMESTAMP | Дата создания |

Представляет реальный источник денег (банковский счёт, карту, наличные). Один счёт может быть привязан к нескольким кошелькам (см. `wallets.account_id`), например при ведении бюджета в разных валютах на одной карте.

#### settings
| Поле | Тип | Описание |
|------|-----|----------|
| id | BIGINT UNSIGNED PK | Идентификатор |
| user_id | BIGINT UNSIGNED FK | Владелец |
| setting_key | VARCHAR(100) | Ключ настройки (`key` — зарезервированное слово в MySQL, поэтому колонка называется `setting_key`) |
| value | TEXT | Значение настройки |
| created_at | TIMESTAMP | Дата создания |
| updated_at | TIMESTAMP | Дата обновления |

#### wallets
| Поле | Тип | Описание |
|------|-----|----------|
| id | BIGINT UNSIGNED PK | Идентификатор |
| user_id | BIGINT UNSIGNED FK | Владелец |
| account_id | BIGINT UNSIGNED FK NULL | Привязанный счёт (`accounts.id`); NULL — виртуальный/наличный кошелёк без привязки |
| name | VARCHAR(100) | Название |
| currency | CHAR(3) | Валюта (ISO 4217) |
| balance | DECIMAL(15,2) | Текущий баланс (денормализованный кэш) |
| is_default | BOOLEAN | Кошелёк по умолчанию |
| created_at | TIMESTAMP | Дата создания |

`balance` не редактируется напрямую — пересчитывается сервисным слоем (`TransactionsService`) в той же БД-транзакции, что и создание/изменение/удаление записи в `transactions`, чтобы исключить рассинхронизацию.

#### transactions
| Поле | Тип | Описание |
|------|-----|----------|
| id | BIGINT UNSIGNED PK | Идентификатор |
| user_id | BIGINT UNSIGNED FK | Владелец |
| wallet_id | BIGINT UNSIGNED FK | Кошелёк |
| category_id | BIGINT UNSIGNED FK | Категория |
| type | ENUM('income','expense') | Тип операции |
| amount | DECIMAL(15,2) | Сумма |
| description | TEXT | Описание |
| date | DATE | Дата операции |
| created_at | TIMESTAMP | Дата создания |

#### categories
| Поле | Тип | Описание |
|------|-----|----------|
| id | BIGINT UNSIGNED PK | Идентификатор |
| user_id | BIGINT UNSIGNED FK | Владелец |
| parent_id | BIGINT UNSIGNED FK NULL | Родительская категория |
| name | VARCHAR(100) | Название |
| type | ENUM('income','expense') | Тип |
| created_at | TIMESTAMP | Дата создания |

#### tags
| Поле | Тип | Описание |
|------|-----|----------|
| id | BIGINT UNSIGNED PK | Идентификатор |
| user_id | BIGINT UNSIGNED FK | Владелец |
| name | VARCHAR(50) | Название |
| color | VARCHAR(7) | HEX цвет |
| created_at | TIMESTAMP | Дата создания |

#### transaction_tag
| Поле | Тип | Описание |
|------|-----|----------|
| transaction_id | BIGINT UNSIGNED FK | Транзакция |
| tag_id | BIGINT UNSIGNED FK | Тег |

---

## 5. API Design

### 5.1 Формат запросов и ответов

**Content-Type:** `application/json`

**Успешный ответ:**
```json
{
  "success": true,
  "data": { ... },
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 100
  }
}
```

**Ответ с ошибкой:**
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Invalid input data",
    "details": [
      { "field": "email", "message": "Email is required" }
    ]
  }
}
```

### 5.2 HTTP коды состояния

| Код | Описание |
|-----|----------|
| 200 | Успех |
| 201 | Создано |
| 204 | Удалено (нет содержимого) |
| 400 | Ошибка валидации |
| 401 | Не авторизован |
| 403 | Доступ запрещён |
| 404 | Не найдено |
| 422 | Ошибка обработки |
| 500 | Внутренняя ошибка |

### 5.3 Аутентификация

Используется JWT (JSON Web Token):

**Заголовок авторизации:**
```
Authorization: Bearer <token>
```

**Структура JWT payload:**
```json
{
  "sub": 1,
  "email": "user@example.com",
  "iat": 1719676800,
  "exp": 1719680400
}
```

### 5.4 Интерактивная документация

Полная спецификация в формате **OpenAPI 3.0.3** лежит в [`public/openapi.yaml`](../public/openapi.yaml) и раздаётся статически через `nginx`. Интерактивный UI (Swagger UI, подключается с CDN) доступен на `/docs/` запущенного инстанса — там же можно выполнять запросы через "Try it out" с реальным JWT из `/api/v1/auth/token`. Спецификация покрывает все 38 эндпоинтов модулей из раздела 3.2, включая схемы запросов/ответов и коды ошибок из 5.1–5.2; проверяется линтером `@redocly/cli lint public/openapi.yaml`.

---

## 6. Инфраструктура

### 6.1 Docker конфигурация

Минимальная конфигурация — три сервиса: `nginx` (reverse proxy и балансировщик), `app` (PHP-FPM, масштабируемый) и `db` (MySQL, свой образ на базе `docker/mysql/Dockerfile` с utf8mb4 по умолчанию). `nginx` — единственный сервис с портом наружу; `app` доступен только внутри сети `pennywise` и может быть поднят в нескольких репликах командой `docker compose up -d --scale app=N`. `dns_opt` на `app` задаёт таймаут резолвера (`timeout:2 attempts:1`), которым пользуется `MxRecordChecker` модуля EmailVerification (3.2.11) — PHP-функции DNS не принимают таймаут per-call. `vendor_data` — именованный том поверх бинд-маунта исходников: `vendor/` не коммитится в git, поэтому при `docker compose up` на чистом клоне он должен переживать поверх `./:/var/www/html`, а не быть стёртым пустой host-директорией.

```yaml
# docker-compose.yml
services:
  app:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    volumes:
      - ./:/var/www/html
      - vendor_data:/var/www/html/vendor
    dns_opt:
      - "timeout:2"
      - "attempts:1"
    depends_on:
      db:
        condition: service_healthy
    networks:
      - pennywise
    healthcheck:
      test: ["CMD", "php", "bin/health-check.php"]
      interval: 10s
      timeout: 3s
      retries: 3
      start_period: 10s
    # docker compose up -d --scale app=N — поднимает N реплик без индивидуальных портов;
    # трафик к ним приходит только через nginx

  nginx:
    build:
      context: .
      dockerfile: docker/nginx/Dockerfile
    ports:
      - "8080:80"
    volumes:
      - ./:/var/www/html
      - vendor_data:/var/www/html/vendor
    depends_on:
      - app
    networks:
      - pennywise

  db:
    build:
      context: .
      dockerfile: docker/mysql/Dockerfile
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MYSQL_DATABASE: pennywise
      MYSQL_USER: ${DB_USER}
      MYSQL_PASSWORD: ${DB_PASSWORD}
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql
    networks:
      - pennywise
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost", "-u", "root", "-p${DB_ROOT_PASSWORD}"]
      interval: 5s
      timeout: 5s
      retries: 10

volumes:
  mysql_data:
  vendor_data:

networks:
  pennywise:
    driver: bridge
```

### 6.1.1 Балансировка между репликами app

`nginx` проксирует FastCGI-запросы на `app` по имени сервиса. Так как `docker compose --scale` не даёт статического списка контейнеров, `nginx` резолвит имя `app` через встроенный Docker DNS (`127.0.0.11`), который отдаёт IP всех живых реплик по кругу (round-robin), — за счёт периодического ре-резолва nginx подхватывает новые реплики и перестаёт стучаться в удалённые:

```nginx
# docker/nginx/nginx.conf (фрагмент)
resolver 127.0.0.11 valid=10s;

server {
    listen 80;

    location ~ \.php$ {
        set $php_upstream app:9000;
        fastcgi_pass $php_upstream;
        fastcgi_param SCRIPT_FILENAME $document_root/index.php;
        fastcgi_param HTTP_AUTHORIZATION $http_authorization;
        include fastcgi_params;
    }
}
```

`fastcgi_param HTTP_AUTHORIZATION` — без этой строки стандартный `fastcgi_params` не прокидывает заголовок `Authorization` в PHP-FPM, и JWT-эндпоинты получают `UNAUTHORIZED` даже с валидным токеном.

Живость каждой реплики контролирует не `nginx` (обычный OSS nginx не умеет active health checks для FastCGI-апстримов), а Docker через `healthcheck` сервиса `app`: нездоровый контейнер помечается `unhealthy` и перестаёт попадать в DNS-ответ, автоматически выпадая из ротации. См. также `GET /api/v1/health` (3.2.10) — эндпоинт, который `healthcheck` дёргает изнутри каждого контейнера.

### 6.2 Переменные окружения

```env
# Database
DB_HOST=db
DB_PORT=3306
DB_NAME=pennywise
DB_USER=pennywise_user
DB_PASSWORD=secret
DB_ROOT_PASSWORD=root_secret

# JWT (HS256 требует секрет длиной от 32 байт — короче firebase/php-jwt отклонит с DomainException)
JWT_SECRET=your-secret-key-of-at-least-32-bytes
JWT_TTL=3600
JWT_REFRESH_TTL=604800

# Application
APP_ENV=production
APP_DEBUG=false
```

---

## 7. Безопасность

### 7.1 Меры защиты

| Мера | Описание |
|------|----------|
| HTTPS | Шифрование трафика |
| JWT | Токены с ограниченным временем жизни |
| Password Hashing | bcrypt для паролей |
| Input Validation | Валидация всех входных данных |
| SQL Injection | Prepared statements |
| XSS | Экранирование вывода |
| CORS | Настройка разрешённых источников |
| Rate Limiting | Ограничение частоты запросов |

### 7.2 Ролевая модель

На начальном этапе система использует простую модель:
- **User** — доступ только к своим данным

---

## 8. Тестирование

### 8.1 Уровни тестирования

| Уровень | Описание | Инструмент |
|---------|----------|------------|
| Unit | Тестирование отдельных классов | PHPUnit |
| Integration | Тестирование взаимодействия компонентов | PHPUnit |
| API | Тестирование endpoints | PHPUnit + HTTP Client |

### 8.2 Покрытие

Целевое покрытие кода: **80%**

### 8.3 Мок внешних зависимостей (DNS)

`MxRecordChecker` (3.2.11) вызывает глобальную `checkdnsrr()` — гонять реальный DNS в юнит-тестах нежелательно (флаки, зависимость от сети, недетерминируемые `lookup_failed`). `tests/Support/DnsResolverStub.php` объявляет функцию `checkdnsrr()` в том же неймспейсе, что и `MxRecordChecker` (`App\Modules\EmailVerification\Services`): PHP при неквалифицированном вызове функции сначала ищет её в текущем неймспейсе вызывающего файла и только потом откатывается на глобальную. Стаб подключается исключительно через `composer.json → autoload-dev.files`, поэтому при сборке без dev-зависимостей (`composer install --no-dev`) его не существует и `MxRecordChecker` всегда резолвит через настоящий `checkdnsrr()` — сам класс ради тестируемости не менялся.

На этом стабе построены:
- `MxRecordCheckerTest` — MX есть → `valid`; MX нет, но есть A/AAAA (fallback) → `valid`; ничего не резолвится → `no_mx_record`; резолвер эмитит warning → `lookup_failed`; кэширование по домену в рамках одного экземпляра (case-insensitive).
- `EmailVerificationServiceTest` — интеграционно подтверждает, что для email с невалидным форматом DNS-резолвер вообще не вызывается (счётчик обращений — 0), а в смешанном батче резолвятся только домены с корректным форматом.

---

## 9. Развёртывание

### 9.1 Требования

- Docker 20.10+
- Docker Compose 2.0+
- Минимум 512MB RAM
- 1GB дискового пространства

### 9.2 Процесс развёртывания

```bash
# 1. Клонирование
git clone https://github.com/SCaeR42/PennyWiseRestApi.git
cd PennyWiseRestApi

# 2. Конфигурация
cp .env.example .env
# Отредактировать .env

# 3. Сборка и запуск (2 реплики app за балансировщиком nginx)
docker compose up -d --build --scale app=2

# 4. Миграции
docker compose exec app php bin/migrate.php

# 5. Проверка API (ответит одна из реплик — см. поле "instance")
curl http://localhost:8080/api/v1/health

# 6. Проверка живости КАЖДОЙ реплики (см. 3.2.10) — Docker healthcheck делает это
#    автоматически, вручную статус смотрится так:
docker compose ps app
```

---

## 10. Будущие улучшения

- [ ] OAuth2 интеграция (Google, GitHub)
- [ ] Двухфакторная аутентификация
- [ ] Экспорт данных (CSV, PDF)
- [ ] Уведомления (Email, Push)
- [ ] Мультивалютность с автоматической конвертацией
- [ ] API для мобильных приложений
- [ ] WebSocket для real-time обновлений
- [ ] Кеширование (Redis)
- [ ] Очереди задач (RabbitMQ)

---

## 11. Приложения

### 11.1 Примеры запросов

**Создание транзакции:**
```bash
curl -X POST http://localhost:8080/api/v1/transactions \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "wallet_id": 1,
    "category_id": 5,
    "type": "expense",
    "amount": 1500.00,
    "description": "Продукты",
    "date": "2026-06-29",
    "tags": [1, 3]
  }'
```

**Получение данных виджета:**
```bash
curl http://localhost:8080/api/v1/dashboard/widgets/balance-chart \
  -H "Authorization: Bearer <token>"
```

**Верификация списка email:**
```bash
curl -X POST http://localhost:8080/api/v1/email-verification/verify \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "emails": ["user@gmail.com", "invalid-email", "test@nonexistent-domain-xyz.invalid"]
  }'
```

---

*Конец документа*
