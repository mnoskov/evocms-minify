### Пакет для автоматической компиляции *.less и *.scss файлов, минификации *.css и *.js для EvolutionCMS 3

<img src="https://img.shields.io/badge/PHP-%3E=7.3-green.svg?php=7.3"> <img src="https://img.shields.io/badge/EVO-%3E%3D3.1.3-green">

## Установка

```
php -d="memory_limit=-1" artisan package:installrequire mnoskov/evocms-minify "*"
```

Если путь для генерируемых файлов отличается от `/theme/compiled`, нужно получить конфиг из пакета в `/core/custom/config/minify.php` и изменить путь в нем.
```
php artisan vendor:publish --provider="EvolutionCMS\Minify\MinifyServiceProvider"
```

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
