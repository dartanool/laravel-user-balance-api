# Laravel Balance API

Приложение для управления балансом пользователей с использованием **Laravel** и **PostgreSQL**.  
Поддерживает начисление, списание, перевод средств и получение текущего баланса через **JSON HTTP API**.

---

## Технологии
 PHP 8+, Laravel 10+, PostgreSQL, Docker / Docker Compose, PHPUnit (тесты)

---

## Установка через Docker

1. Клонируем репозиторий:
    ```bash
    git clone <your-repo-url>
    cd <project-folder>
2. Копируем .env и настраиваем:
    ```bash
    cp .env.example .env
3. Поднять контейнеры:
    ```bash
    docker-compose up -d --build
4. Запускаем миграции:
    ```bash
    docker exec -it php-fpm bash 
    php artisan migrate

---

## Структура БД

- users — пользователи
- balances — балансы пользователей
- transactions — история операций (deposit, withdraw, transfer_in, transfer_out)

---

## Тестирование

1. Настройте .env.testing для тестовой базы PostgreSQL:
    ```bash 
    DB_CONNECTION=pgsql
    DB_HOST=127.0.0.1
    DB_PORT=5432
    DB_DATABASE=balance_test
    DB_USERNAME=postgres
    DB_PASSWORD=postgres
    APP_KEY=base64:GENERATED_KEY
2. Запуск тестов
    ```bash 
   docker-compose exec app 
   php artisan test
