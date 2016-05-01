# doctrine-dbal-extensions

# README
------------
[![Build Status](https://travis-ci.org/schwaen/doctrine-dbal-extensions.svg?branch=master)](https://travis-ci.org/schwaen/doctrine-dbal-extensions)[![Latest Stable Version](https://poser.pugx.org/schwaen/doctrine-dbal-extensions/v/stable)](https://packagist.org/packages/schwaen/doctrine-dbal-extensions) [![Total Downloads](https://poser.pugx.org/schwaen/doctrine-dbal-extensions/downloads)](https://packagist.org/packages/schwaen/doctrine-dbal-extensions) [![Latest Unstable Version](https://poser.pugx.org/schwaen/doctrine-dbal-extensions/v/unstable)](https://packagist.org/packages/schwaen/doctrine-dbal-extensions) [![License](https://poser.pugx.org/schwaen/doctrine-dbal-extensions/license)](https://packagist.org/packages/schwaen/doctrine-dbal-extensions)

What is doctrine-dbal-extensions?
------------
doctrine-dbal-extensions is a library with extensions for the doctrine [dbal](http://www.doctrine-project.org/projects/dbal.html) project

Installation
------------
The best way to install this library is to use [composer](https://getcomposer.org/).

```json
{
    "require": {
        "schwaen/doctrine-dbal-extensions": "~1.*"
    }
}
```
Requirements
-----------
- PHP >= 5.4.0
- ext-pdo
- doctrine/dbal ~2.5

Supports
-----------
- [PSR-2](http://www.php-fig.org/psr/psr-2/)
- [PSR-4](http://www.php-fig.org/psr/psr-4/)
- PHP 5.4
- PHP 5.5
- PHP 5.6
- PHP 7.0
- [hhvm](http://hhvm.com/)

Documentation
------------
**Getting the model object**
```php
$config = new \Doctrine\DBAL\Configuration();
$connectionParams = array(
    // other params... like the access
    'wrapperClass' => '\Schwaen\Doctrine\Dbal\Connection',
);
$conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
//variant #1 (need the wrapperClass)
$model1 = $conn->getModel('table_name');
//variant #2 (no need of the wrapperClass)
$model2 = new \Schwaen\Doctrine\Dbal\Model('table_name', $conn);
```

License
-------
This library is available under the [MIT license](LICENSE).


