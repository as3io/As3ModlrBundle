# As3ModlrBundle
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/as3io/As3ModlrBundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/as3io/As3ModlrBundle/?branch=master) [![Build Status](https://travis-ci.org/as3io/As3ModlrBundle.svg?branch=master)](https://travis-ci.org/as3io/As3ModlrBundle) [![Packagist](https://img.shields.io/packagist/dt/as3/modlr-bundle.svg)](https://packagist.org/packages/as3/modlr-bundle) [![SensioLabsInsight](https://insight.sensiolabs.com/projects/6d7d530c-f405-4815-847a-4f7ff82960c5/mini.png)](https://insight.sensiolabs.com/projects/6d7d530c-f405-4815-847a-4f7ff82960c5)

Integrates the modlr REST libraries with Symfony.

## Installation

To utilize modlr, you **must** install a persister and an api specification.

To utilize search within modlr, you must install a search client.

Available modules can be found [here](http://github.com/as3io), or you can write your own!

### Install packages with Composer

To install this bundle with support for [MongoDB](http://mongodb.org), [elasticsearch](http://elasticsearch.org) and the [JSON Api Spec](http://jsonapi.org), add the following to your composer.json file:
```
{
    "require": {
        "as3/modlr-bundle": "dev-master",
        "as3/modlr-api-jsonapiorg": "dev-master",
        "as3/modlr-search-elastic": "dev-master",
        "as3/modlr-persister-mongodb": "dev-master"
    }
}
```

Then, tell Composer to update and install the requested packages:

```
composer update as3/modlr-bundle --with-dependencies
```

### Register the Bundle

Once installed, register the bundle in your `AppKernel.php`:
```
// app/AppKernel.php
public function registerBundles()
{
    $bundles = array(
        // ...
        new As3\Bundle\ModlrBundle\As3ModlrBundle(),
    );

    // ...
}
```

## Configuration

Modlr requires some configuration in order to work. You can use the default config below to get started:

```
# app/config/config.yml
as3_modlr:
    rest:
        root_endpoint: /api
        debug: "%kernel.debug%"
    adapter:
        type: jsonapiorg
    persisters:
        default:
            type: mongodb
            parameters:
                host: mongodb://localhost:27017
    search_clients:
        default:
            type: elastic
```

To utilize the external REST API, you must also include modlr's routing file. This will allow modlr to handle all requests to `as3_modlr.rest.root_endpoint`. To prevent any routing conflicts, you should add this before any other bundle or your application route definitions.
```
# app/config/routing.yml
as3_modlr_bundle:
    resource: "@As3ModlrBundle/Resources/config/routing.yml"
```

Next you must define your models! By default, modlr's metadata driver will look for YAML files in `app/Resources/As3ModlrBundle/models` and `app/Resources/As3ModlrBundle/mixins`.

You can customize where your definitions are stored by setting the `models_dir` parameter of your metadata driver. See the [bundle configuration](#) documentation for more information.

## Usage

See [modlr documentation](#) for additional information.

This bundle provides commands to rebuild modlr's metadatacache:
```
app/console as3:modlr:metadata:cache:clear [model_type] [--no-warm]
```

Create collection schema:
```
app/console as3:modlr:schema:create [model_type]
```
