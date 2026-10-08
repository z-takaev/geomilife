## Стек

- PHP 8.5 в среде Laravel Sail.
- Laravel 13.34.0.
- Livewire 4.4.7.
- Alpine.js 3.17.4.
- Filament 5.9.0.
- PostgreSQL 18.
- Redis.
- `spatie/laravel-medialibrary` 11.23.9 для работы с медиафайлами.
- Mailpit для локальной проверки писем.

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
- выполняет миграции и запускает сидеры;
- создаёт символическую ссылку на публичное хранилище.

### Сброс проекта

```bash
sail artisan project:refresh
```

Команда полностью пересоздаёт базу данных, запускает сидеры, удаляет загруженные изображения и обновляет символическую ссылку на хранилище.
