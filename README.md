Citrus - PHP Application Framework
==================================

Citrus is a PHP-written application framework providing the following features:

- CLI Console Command handling
- HTTP Request/Response Handling (PSR x compatible)
- HTTP Middleware Router (PSR x compatible)
- Error / Trace Logging (PSR x compatible)
- File / Database Caching (PSR x compatible)
- Application Framework with Factories and Services
- Application Container using PHP-DI
- Additional generally-usefull Utilities and Structures

The HTTP middleware router is based on [nikic/FastRoute](https://github.com/nikic/FastRoute). Since 
FastRoute isn't further developed, we took over the parts we need and modified it accordingly to 
fit Citrus' environment.
