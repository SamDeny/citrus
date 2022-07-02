Citrus - PHP Application Framework
==================================

## Work in Progress

**Citrus** is a PHP-written application framework and is inspired by [Cockpit's Lime framework](https://github.com/cockpit-project/cockpit) 
as well as [Laravel](https://laravel.com/) and [Lumen](https://lumen.laravel.com). **Citrus** 
provides the following features, and while it is **mostly** designed as general-purpose framework, 
it is more aimed to fit our own [Crate CMS]() itself.

- CLI Console Command handling
- HTTP Request / Response Handling [\2]
- HTTP Middleware Router [\1]
- Error / Trace Logging [\2]
- File / Database Caching [\2]
- Event Handling [\2]
- Application Container (with Factories and Services) [\2]
- Additional generally-useful Utilities and Structures

[\1] The HTTP middleware router is based on [nikic/FastRoute](https://github.com/nikic/FastRoute). 
Since FastRoute isn't active developed, we took over the parts we need and modified it accordingly 
to fit Citrus' environment.

[\2] Many features of Citrus are inspired by one or more official PSR PHP-recommendations, but do 
**NOT** follow any of those directly. Thus, saying Citrus is PSR-compatible is not true!


Requirements
------------

Citrus is designed as PHP 8.0-only Framework, and requires the following PHP extensions. While some 
of those provide a respective composer-package fallback, it is still highly recommended using the 
extensions itself to increase the overall performance:

- PHP 8.0+
- [Multibyte String](https://www.php.net/manual/en/book.mbstring.php) (required)
- [PHP-DS](https://www.php.net/manual/en/book.ds.php) (php-ds/php-ds fallback is included)
- [Readline](https://www.php.net/manual/en/ref.readline.php) (optional)
- [YAML](https://www.php.net/manual/en/book.yaml.php) (Symfony/Yaml fallback is included)

Extensions where a fallback is provided aren't listed in the `requires` field within the composer 
file, but in the `suggest` one instead.


### Security Requirements 

Citrus requires at least one of the following extensions or packages for the main security features, 
including password hashing and data encryption:

- [Sodium](https://www.php.net/manual/en/book.sodium) (recommended)
- [OpenSSL](https://www.php.net/manual/en/book.openssl.php) (fallback)
- Argon2 for [Password Hashing](https://www.php.net/manual/en/book.password.php) (uses bcrypt as fallback)

MCrypt is obsolete and thus **not** supported.


### Caching Requirements

Citrus provides an own In-File caching environment, which should fit the most purposes and 
applications, however, the following extensions are supported to increase the caching abilities:

- [APCu](https://www.php.net/manual/en/book.apcu.php) - Caching System
- [Memcached] - Caching Server System
