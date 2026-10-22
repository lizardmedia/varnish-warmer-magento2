### 2.1. ###
* fix for incorrect method for purging process
* adding logging for error request
* minor refactor

### 2.2 ###
* major refactor
* adding missing return types/types hints
* support for PHP 7.3
* fix for case when urls uses store codes inside after domain

### 2.2.1 ###
* fix for cache purging headers

### 2.3 ###
* support for PHP 7.4

### 2.3.1 ###
* fix for magento installation bug (lack of using proxy in console command class)

### 3.0.0 ###
* support for PHP 8.1 - 8.5 and Magento 2.4.6 - 2.4.9
* HTTP requests sent with Guzzle (already required by Magento) instead of the abandoned react/http-client, so the module installs with composer/composer 2.10+ and react/promise 3; a new request starts as soon as one finishes instead of waiting for the whole batch
* requests time out after 60 seconds (10 seconds to connect) instead of hanging the process
* security: the URL and store view given in the admin panel reach the shell as escaped arguments, every admin action requires the "Cache handling" permission and "purge a group of URLs" runs only the listed processes
* purging can be switched off (Purging > Enable purging) when Magento invalidates Varnish by cache tags and the module should only warm the cache
* every URL is warmed for the cache variant guests of its store view get: without a cookie and with the X-Magento-Vary cookie the storefront gives them
* only URL rewrites of the selected store view are purged and warmed, with its store code in the URL when the store view uses one
* HTTP basic authentication for staging environments (General > HTTP authentication)
* the lock is released when an operation fails, a URL is warmed once regardless of the number of purge hosts, spaces around purge hosts are ignored
* the lock warning in the admin panel and the progress bar no longer fail with an error
* response code of every warmed URL is logged
* queue progress serialized with Magento\Framework\Serialize\Serializer\Json instead of Zend\Json
* BC break: see "Upgrading from 2.x" in README
