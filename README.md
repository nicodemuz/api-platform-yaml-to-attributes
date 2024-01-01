# API Platform YAML converter

This script converts your API Platform V2 YAML configuration files to API Platform V3 compatible configurations using PHP attributes.

## Installation

You can simply clone this repository and run the script. This script does not have to be part of your project files.

```bash
git clone https://github.com/nicodemuz/api-platform-yaml-to-attributes.git;
cd api-platform-yaml-to-attributes;
composer install;
```

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
$runner->convertSerializerConfigurations()->convertApiPlatformConfigurations();
```

## Notes

This script was hacked together in a few hours. Use at own risk. Commit your work before executing.

The script does not support all API Platform V3 configuration parameters. If a configuration parameter is unsupported, no changes will be executed. Please modify `Runner.php` file accordingly. Pull requests will be gladly accepted.

## Authors

* Nico Hiort af Ornäs