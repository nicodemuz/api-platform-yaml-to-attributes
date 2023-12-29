# API Platform YAML converter

This script converts your API Platform V2 YAML configuration files to API Platform V3 compatible configurations using PHP attributes.

## Sample usage

```php
<?php

use Nicodemuz\ApiPlatformYamlToAttributes\Runner;

require 'vendor/autoload.php';

$runner = new Runner(
    yamlApiPlatformDir: '/path/to/symfony/config/api_platform',
    yamlSerializerDir: '/path/to/symfony/config/serializer',
    doctrineEntityDir: '/path/to/symfony/src/Entity',
);
$runner->convertSerializerConfigurations();
```