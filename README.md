# Imbrix

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/232fea1c-ed90-46dd-939a-2b232a36312e/mini.png)](https://insight.sensiolabs.com/projects/232fea1c-ed90-46dd-939a-2b232a36312e)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Babacooll/Imbrix/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Babacooll/Imbrix/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/Babacooll/Imbrix/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Babacooll/Imbrix/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/Babacooll/Imbrix/badges/build.png?b=master)](https://scrutinizer-ci.com/g/Babacooll/Imbrix/build-status/master)

Imbrix is a fully unit tested dependency manager suited for small PHP projects.

[Packagist link](https://packagist.org/packages/babacooll/imbrix)

## Still in progress

- Wrapper for DependencyManager to get *static* access to it (singleton way ?)

## Installation

Imbrix is available through composer : 

```Shell
$ php composer.phar require babacooll/imbrix ~0.0.3
```

## How to use

All you need is to instanciate a new DependencyManager :

```php
<?php

use Imbrix\DependencyManager;

$depManager = new DependencyManager();
```

This DependencyManager will contains both your services and parameters.

#### Services

You can add your services easily with the *addService* method, first parameter being your service name and second a closure returning your service :

```php
<?php

$depManager->addService('myService', function () {
    return new MyService();
});
```

You can retrieve your service with the get method :

```php
<?php

$depManager->get('myService');
```

#### Parameters

Same method exists for parameters, first parameter being your parameter name and second is string value :


```php
<?php

$depManager->addParameter('myParameter', 'value');
```

You can retrieve your parameter with the get method :

```php
<?php

$depManager->get('myParameter');
```

### Injection

You can inject parameters into services and services into services (as many times you need it). All you need is the name of the service/parameter you want to inject :

```php
<?php

// Injection of a parameter into a service

$depManager = new DependencyManager();

$depManager->addParameter('myParameter', 'value');
$depManager->addService('myService', function ($myParameter) {
    return new MyService($myParameter);
});
```

The order of the parameters/service definition does not matter as your service will be instanciate after the whole definition when you do call the *get* method (not before !).

A more complex example as following :

```php
<?php

$depManager = new DependencyManager();

$depManager->addParameter('myParameter', 'value');
$depManager->addService('myService', function ($myParameter) {
    return new MyService($myParameter);
});
$depManager->addService('mySecondService', function ($myService, $myParameter) {
    return new MySecondService($myService, $myParameter);
});

// We suppose MySecondService has both a getMyService() and a getParameter() method and the Service a getParameter()

echo $depManager->get('mySecondService')->getMyService()->getParameter();
echo $depManager->get('mySecondService')->getParameter();

// Both will return "value"

```

Feel free to add feedbacks !
