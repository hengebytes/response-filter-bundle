# Response Filter Bundle
This bundle provides a way to filter the response of async web services core bundle.


## Add the bundle to your Kernel
```php
// config/bundles.php
return [
    // ...
    ResponseFilterBundle\ResponseFilterBundle::class => ['all' => true],
];
```

## Generate migration
```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

## Routes
```yaml
# config/routes/response_filter.yaml
response_filter:
    resource: '@ResponseFilterBundle/Resources/config/routes.yaml'
    prefix: /admin
```

## Assets
```bash
php bin/console assets:install --symlink
```

## Override templates
```bash
php bin/console make:twig:template templates/bundles/ResponseFilterBundle
```
or manually create the file `templates/bundles/ResponseFilterBundle/layout.html.twig`
