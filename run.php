<?php

use Nicodemuz\ApiPlatformYamlToAttributes\Runner;

require 'vendor/autoload.php';

$runner = new Runner(
    yamlApiPlatformDir: '/home/nico/Projects/lms-platform/symfony/config/api_platform',
    yamlSerializerDir: '/home/nico/Projects/lms-platform/symfony/config/serializer',
    doctrineEntityDir: '/home/nico/Projects/lms-platform/symfony/src/Entity',
);
$runner->convertSerializerConfigurations();
