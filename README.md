Laravel Command Bus
---------------

Laravel integration package for [command-bus](https://github.com/onliner/command-bus) 

[![Version][version-badge]][version-link]
[![Total Downloads][downloads-badge]][downloads-link]
[![Php][php-badge]][php-link]
[![License][license-badge]](LICENSE)

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```shell
composer require onliner/laravel-command-bus:^0.1.0
```

or add this code line to the `require` section of your `composer.json` file:

```
"onliner/laravel-command-bus": "^0.1.0"
```

Configuration
------------

Publish default configuration file:

```shell
artisan vendor:publish --provider=Onliner\\Laravel\\CommandBus\\Providers\\CommandBusProvider
```

and modify for your needs.

License
-------

Released under the [MIT license](LICENSE).


[version-badge]:    https://img.shields.io/packagist/v/onliner/laravel-command-bus.svg
[version-link]:     https://packagist.org/packages/onliner/laravel-command-bus
[downloads-badge]:  https://poser.pugx.org/onliner/laravel-command-bus/downloads.png
[downloads-link]:   https://packagist.org/packages/onliner/laravel-command-bus
[php-badge]:        https://img.shields.io/badge/php-7.2+-brightgreen.svg
[php-link]:         https://www.php.net/
[license-badge]:    https://img.shields.io/badge/license-MIT-brightgreen.svg
