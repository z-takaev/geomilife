## Стек

- PHP 8.5 в среде Laravel Sail.
- Laravel 13.34.0.
- Livewire 4.4.7.
- Filament 5.9.0.
- MySQL 8.4.

## Локальное развёртывание

1. Клонируйте репозиторий и перейдите в директорию проекта:

```bash
git clone <repository-url> geomilife
cd geomilife
```

2. Скопируйте файл окружения:

```bash
cp .env.example .env
```

3. Если директория `vendor` ещё не создана, установите PHP-зависимости через Composer-образ Sail:

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php85-composer:latest \
    composer install --ignore-platform-reqs
```

4. Соберите и запустите контейнеры:

```bash
sail up -d --build
```

5. Установите проект:

```bash
sail artisan project:install
```

## Пользовательские Artisan-команды

### Установка проекта

```bash
sail artisan project:install
```

Команда:

- очищает кэши Laravel;
- удаляет ранее загруженные изображения;
- выполняет миграции и запускает обязательные сидеры;
- создаёт символическую ссылку на публичное хранилище.

Чтобы дополнительно заполнить базу тестовыми данными через фабрики, передайте опцию `--factories`:

```bash
sail artisan project:install --factories
```

### Сброс проекта

```bash
sail artisan project:refresh
```

Команда полностью пересоздаёт базу данных, запускает обязательные сидеры и удаляет загруженные изображения. По умолчанию фабрики не запускаются. Для заполнения базы тестовыми данными используйте опцию `--factories`:

```bash
sail artisan project:refresh --factories
```

Для запуска без подтверждения удаления данных передайте опцию `--force`:

```bash
sail artisan project:refresh --force
```

### Синхронизация городов СДЭК

```bash
sail artisan cdek:sync-cities
```

Команда синхронизирует доступные города доставки с актуальным справочником СДЭК.

### Регистрация вебхука СДЭК

```bash
sail artisan cdek:webhook
```

Команда удаляет ранее зарегистрированный вебхук статусов заказов и регистрирует новый. Для работы в `APP_URL` должен быть указан публичный HTTPS-адрес сайта.

### Получение сообщений Telegram локально

```bash
sail artisan telegram:poll
```

Команда запускает получение сообщений Telegram-бота через long polling. Она доступна только в локальном окружении и требует настройки `TELEGRAM_BOT_TOKEN`.

### Регистрация вебхука Telegram

```bash
sail artisan telegram:webhook
```

Команда регистрирует вебхук Telegram-бота. Для работы необходимо настроить `TELEGRAM_BOT_TOKEN`, `TELEGRAM_WEBHOOK_SECRET` и публичный HTTPS-адрес сайта в `APP_URL`.

### Сверка заказов

```bash
sail artisan orders:reconcile
```

Команда сверяет платежи, отмены, возвраты и отправления заказов. В штатном режиме она запускается планировщиком каждую минуту.
