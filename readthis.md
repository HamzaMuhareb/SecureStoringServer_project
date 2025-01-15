# instructions

## Enter these commands before the first run

```sh

cp .env.example .env

composer install

composer require laravel/passport

php artisan passport:keys

php artisan key:generate

php artisan migrate:fresh

php artisan route:clear

php artisan cache:clear

php artisan config:clear

php artisan config:cache

php artisan route:cache

php artisan view:cache

php artisan passport:client --personal

```

## Some other important commands

```sh

php artisan serve

php artisan serve --host=0.0.0.0 --port=8000

```

## main admin profile

`email` is <john@example.com>

and `password` is "password"
