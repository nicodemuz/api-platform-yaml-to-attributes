<?php

use Nicodemuz\ApiPlatformYamlToAttributes\Runner;

require 'vendor/autoload.php';

$runner = new Runner(
    yamlApiPlatformDir: '/path/to/symfony/config/api_platform',
    yamlSerializerDir: '/path/to/symfony/config/serializer',
    doctrineEntityDir: '/path/to/symfony/src/Entity',
);
$runner->convertSerializerConfigurations()->convertApiPlatformConfigurations();
