[![Latest Stable Version](https://poser.pugx.org/lizardmedia/module-varnish-warmer/v/stable)](https://packagist.org/packages/lizardmedia/module-varnish-warmer)
[![Total Downloads](https://poser.pugx.org/lizardmedia/module-varnish-warmer/downloads)](https://packagist.org/packages/lizardmedia/module-varnish-warmer)
[![License](https://poser.pugx.org/lizardmedia/module-varnish-warmer/license)](https://packagist.org/packages/lizardmedia/module-varnish-warmer)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/lizardmedia/varnish-warmer-magento2/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/lizardmedia/varnish-warmer-magento2/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/lizardmedia/varnish-warmer-magento2/badges/build.png?b=master)](https://scrutinizer-ci.com/g/lizardmedia/varnish-warmer-magento2/build-status/master)

# Magento2 Varnish Cache Warmer 

Magento2 module for handling Varnish Cache cleaning and regenerating using admin panel
and CLI. It uses multiple process to run the commands to minimize the time required
to run.

## Getting Started

These instructions will get you a copy of the project up and running on your local machine for development and testing purposes. See deployment for notes on how to deploy the project on a live system.

### Prerequisites

##### For release 1.0
* Magento 2.2
* PHP 7.1

##### For realease 2.0
* Magento 2.3
* PHP 7.1/7.2

##### For realease 2.2
* Magento 2.3
* PHP 7.1/7.2/7.3

##### For realease 2.3
* Magento 2.3/2.4
* PHP 7.1/7.2/7.3/7.4

##### For release 3.0
* Magento 2.4.6 - 2.4.9
* PHP 8.1 - 8.5

### Installing

#### Download the module

##### Using composer (suggested)

Simply run

```
composer require lizardmedia/module-varnish-warmer
```

##### Downloading ZIP

Download a ZIP version of the module and unpack it into your project into
```
app/code/LizardMedia/VarnishWarmer
```
If you use ZIP file you will need to install the dependencies of the module
manually
```
composer require guzzlehttp/guzzle:^7.5
```

#### Install the module

Run this command
```
bin/magento module:enable LizardMedia_VarnishWarmer
bin/magento setup:upgrade
```

## Usage

#### Admin panel

The commands can be run in backround from admin panel using ``Lizard Media Varnish Warmer``
menu tab.

#### CLI

The commands can be run using Magento built-in CLI:
* ``lm-varnish:cache-purge-homepage`` - purges and regenerates homepage
* ``lm-varnish:cache-purge-general`` - purges and regenerates homepage and categories
* ``lm-varnish:cache-purge-wildcard`` - purges * and regenerates homepage, categories and products
* ``lm-varnish:cache-purge-all`` - purges and regenerates homepage, categories and products
* ``lm-varnish:cache-purge-force`` - purges * without regenerating. Omits lock check
* ``lm-varnish:regenerate-products-cache`` - purges and regenerates products
* ``lm-varnish:cache-refresh-url`` - purges and regenerates single URL given as argument

## Command options ##
* store - sets store view ID to get a correct base URL, e.g.
```
php bin/magento lm-varnish:cache-refresh-url "*" --store=1
```

## Configuration

``Stores > Configuration > LizardMedia Varnish > Varnish Cache Warmer``

* General
  * number of concurrent regeneration and purge requests
  * exclude 301 redirects from warming
  * HTTP authentication username and password - fill in when the store is behind HTTP basic authentication,
  e.g. on staging
* Purging
  * Enable purging - set to No when Magento already invalidates Varnish by cache tags and the module should
  only warm the cache
  * custom purge hosts (comma separated, one per Varnish server) and the Host header sent with them

### Cache variants

Every URL is warmed once without a cookie and, when guests of its store view get an ``X-Magento-Vary`` cookie,
once more with that cookie. The cookie is calculated the same way the storefront does it, including values other
modules add to the HTTP context with plugins on ``Magento\Framework\App\Http\Context::getVaryString()``.
To warm more variants add an after plugin on
``LizardMedia\VarnishWarmer\Model\HttpContext\VaryCookieProvider::getVaryCookies()``.

## Upgrading from 2.x

* Remove composer patches applied to this module, everything they fixed is included.
* If purging was commented out with a patch, set ``lm_varnish/purge/enabled`` to ``0`` instead
(``bin/magento config:set lm_varnish/purge/enabled 0`` or ``app/etc/config.php``).
* Classes extending ``LizardMedia\VarnishWarmer\Model\QueueHandler\AbstractQueueHandler`` or replacing
``VarnishUrlRegenerator`` need to be removed or rewritten. Cache variants, response codes and HTTP authentication
are built in now. Queue handlers add PSR-7 requests with ``queue()`` and receive them in ``handleResponse()``.
* Admin roles without full access need ``LizardMedia Varnish Warmer > Cache handling`` to use the admin actions.
* API changes: ``PurgingConfigProviderInterface::isPurgeEnabled()``,
``GeneralConfigProviderInterface::getHttpAuthUsername()`` and ``getHttpAuthPassword()``,
``VarnishUrlRegeneratorInterface::addUrlToRegenerate(string $url, ?int $storeId = null)``.
URL providers return ``store_id`` next to ``request_path``.
* Run ``bin/magento setup:di:compile`` after the upgrade.

## For developers

The number of process used for purging and regenerating Varnish cache can be customized
in the admin panel. The max number of processes is specified in 
``LizardMedia\VarnishWarmer\Model\Config\GeneralConfigProvider``
If you have a powerful server you can modify those numbers to use more resources.

### Tests

CI runs the suite against whole Magento releases the projects use (see ``.github/workflows/tests.yml``).
To do the same locally, pin a release and install (do not commit the resulting ``composer.json`` change):
```
composer config policy.advisories.block false
composer require --no-update magento/product-community-edition:2.4.6-p15
composer update
```

* ``vendor/bin/phpunit`` runs the contract tests: behaviour that must not change between releases.
* ``vendor/bin/phpunit --group known-bug`` runs tests describing known defects. They fail until the
defect is fixed, and the fix removes the test from the ``known-bug`` group.

## Contributing

Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on our code of conduct, and the process for submitting pull requests to us.

## Versioning

We use [SemVer](http://semver.org/) for versioning. For the versions available, see the [tags on this repository](https://github.com/lizardmedia/varnish-warmer-magento2/tags). 

## Authors

* **Maciej Sławik** - *Initial work* - [Lizard Media](https://github.com/maciejslawik)
* **Bartosz Kubicki** - *Fixes & maintenance* - [Lizard Media](https://github.com/bartoszkubicki)

See also the list of [contributors](https://github.com/lizardmedia/varnish-warmer-magento2/contributors) who participated in this project.

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details

