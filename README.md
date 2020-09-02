# Contao 4 manager plugin

[![](https://img.shields.io/travis/contao/manager-plugin/master.svg?style=flat-square)](https://travis-ci.com/contao/manager-plugin/)
[![](https://img.shields.io/coveralls/contao/manager-plugin/master.svg?style=flat-square)](https://coveralls.io/github/contao/manager-plugin)

The Contao managed edition is a self-configuring application, which registers
bundles automatically based on their plugin class. The Contao manager bundle
is required to create this class.  

## The plugin class

It is recommended to create the plugin in `src/ContaoManager/Plugin.php`.

```php
<?php

namespace Vendor\SomeBundle\ContaoManager;

class Plugin
{
}
```

## composer.json

The plugin class then needs to be registered in the `composer.json` extra
section. You also have to add a dev requirement and a conflict as shown below.

```json
{
    "require-dev": {
        "contao/manager-plugin": "^2.0"
    },
    "conflict": {
        "contao/manager-plugin": "<2.0 || >=3.0"
    },
    "extra": {
        "contao-manager-plugin": "Vendor\\SomeBundle\\ContaoManager\\Plugin"
    }
}
```

## Registering bundles

If your bundle uses other bundles, which are not yet registered in the kernel,
you can add them by implementing the `BundlePluginInterface` interface. The
following example registers the `KnpMenuBundle` class:

```php
<?php

namespace Vendor\SomeBundle\ContaoManager;

use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Knp\Bundle\MenuBundle\KnpMenuBundle;

class Plugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(KnpMenuBundle::class),
        ];
    }
}
```

This is the equivalent of registering the `KnpMenuBundle` class in the
`registerBundles()` method of the regular Symfony app kernel, except it is done
automatically as soon as your bundle is installed.

## Configuring the container

If your bundle adds configuration options to the Symfony kernel or if you want
to adjust the existing configuration, you can do so by implementing the
`ConfigPluginInterface`.

```php
<?php

namespace Vendor\SomeBundle\ContaoManager;

use Contao\ManagerPlugin\Config\ConfigPluginInterface;
use Symfony\Component\Config\Loader\LoaderInterface;

class Plugin implements ConfigPluginInterface
{
    public function registerContainerConfiguration(LoaderInterface $loader, array $config)
    {
        $loader->load('@VendorSomeBundle/Resources/config/config.yml');
    }
}
```

You can also add a configuration in a specific environment only:

```php
<?php

namespace Vendor\SomeBundle\ContaoManager;

use Contao\ManagerPlugin\Config\ConfigPluginInterface;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class Plugin implements ConfigPluginInterface
{
    public function registerContainerConfiguration(LoaderInterface $loader, array $config)
    {
        $loader->load(
            function (ContainerBuilder $container) use ($loader) {
                if ('dev' === $container->getParameter('kernel.environment')) {
                    $loader->load('@VendorSomeBundle/Resources/config/config_dev.yml');
                }
            }
        );
    }
}
```

This is the equivalent of adjusting the `app/config/config.yml` file of a
regular Symfony application.

## Adding custom routes

If your bundle adds custom routes to the Symfony router, you can implement the
`RoutingPluginInterface` interface.

```php
<?php

namespace Vendor\SomeBundle\ContaoManager;

use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class Plugin implements RoutingPluginInterface
{
    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel)
    {
        $file = '@VendorSomeBundle/Resources/config/routing.yml';

        return $resolver->resolve($file)->load($file);
    }
}
```

This is the equivalent of adjusting the `app/config/routing.yml` file of a
regular Symfony application.

## Loading dependencies

If your bundle depends on one or more other bundles to be loaded first, so it
can override certain parts of them, you can ensure that these bundles are
loaded first by implementing the `DependentPluginInterface`.

```php
<?php

namespace Vendor\SomeBundle\ContaoManager;

use Contao\ManagerPlugin\Dependency\DependentPluginInterface;

class Plugin implements DependentPluginInterface
{
    public function getPackageDependencies()
    {
        return ['contao/news-bundle'];
    }
}
```

This is the equivalent of adding `requires[] = "news"` in the `autoload.ini`
file of a Contao 3 extension.

## More information

For more information about the Contao managed edition, please read the
[manual][1].

[1]: https://docs.contao.org/dev/getting-started/initial-setup/managed-edition/
