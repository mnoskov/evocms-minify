evo3

php -d="memory_limit=-1" artisan vendor:publish

## Использование

В качестве входного списка можно передать маску для <a href="https://www.php.net/manual/ru/function.glob.php">glob</a>.
Для авторизованного пользователя будет генерироваться каждый файл по отдельности, для остальных - один минифицированный (для css и js отдельно).

```
@minify([
    'theme/vendor/bootstrap.min.css',
    'theme/vendor/jquery.fancybox.min.css',
    'theme/css/variables.json',
    'theme/css/*.less',
])
```

```
@minify([
    'theme/vendor/jquery.min.js',
    'theme/vendor/bootstrap.min.js',
    'theme/vendor/jquery.fancybox.min.js',
    'theme/js/*.js',
])
```
