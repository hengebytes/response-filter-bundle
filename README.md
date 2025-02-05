# Response Filter Bundle
This bundle provides a way to filter the response of async web services core bundle.


## Add the bundle to your Kernel

```php
// config/bundles.php
return [
    // ...
    Hengebytes\ResponseFilterBundle\HBResponseFilterBundle::class => ['all' => true],
];
```

## Generate migration
```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

## Routes
```yaml
# config/routes/hb_response_filter.yaml
hb_response_filter:
    resource: '@HBResponseFilterBundle/Resources/config/routes.yaml'
    prefix: /admin
```

## Assets
```bash
php bin/console assets:install --symlink
```

## Override templates
```bash
php bin/console make:twig:template templates/bundles/HBResponseFilterBundle
```
or manually create the file `templates/bundles/HBResponseFilterBundle/layout.html.twig`
