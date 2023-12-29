<?php

namespace Nicodemuz\ApiPlatformYamlToAttributes;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class Runner
{
    public function __construct(
        private readonly string $yamlApiPlatformDir,
        private readonly string $yamlSerializerDir,
        private readonly string $doctrineEntityDir,
    ) {
    }

    public function convertApiPlatformConfigurations(): self
    {
        $finder = new Finder();
        $finder
            ->in($this->yamlApiPlatformDir)
            ->files()
            ->name(['*.yaml', '*.yml'])
        ;

        foreach ($finder as $file) {
            $this->handleApiPlatformFile($file);
        }

        return $this;
    }

    public function convertSerializerConfigurations(): self
    {
        $finder = new Finder();
        $finder
            ->in($this->yamlSerializerDir)
            ->files()
            ->name(['*.yaml', '*.yml'])
        ;

        foreach ($finder as $file) {
            $this->handleSerializerFile($file);
        }

        return $this;
    }

    private function handleApiPlatformFile(SplFileInfo $file): void
    {
        $absoluteFilePath = $file->getRealPath();

        $yaml = Yaml::parseFile($absoluteFilePath);

        $fileNamespace = array_keys($yaml)[0];
        $parts = explode('\\', $fileNamespace);
        $fileClass = end($parts);

        $yamlEntity = $yaml[$fileNamespace];

        $filename = $this->doctrineEntityDir . '/' . $fileClass . '.php';

        if (file_exists($filename)) {
            $code = file_get_contents($filename);
            $phpFile = PhpFile::fromCode($code);

            $class = current($phpFile->getClasses());

            if ($class instanceof ClassType) {
                $operations = [];

                if (array_key_exists('collectionOperations', $yamlEntity)) {
                    foreach ($yamlEntity['collectionOperations'] as $key => $field) {
                        $operations[] = $this->handleOperation($phpFile, $class, $key, $field, true);
                    }
                    unset($yamlEntity['collectionOperations']);
                }

                if (array_key_exists('itemOperations', $yamlEntity)) {
                    foreach ($yamlEntity['itemOperations'] as $key => $field) {
                        $operations[] = $this->handleOperation($phpFile, $class, $key, $field, false);
                    }
                    unset($yamlEntity['itemOperations']);
                }

                $apiResourceAttributes = [
                    'operations' => $operations,
                ];

                foreach ($yamlEntity['attributes'] as $key => $value) {
                    $key = $this->snakeToCamel($key);
                    $apiResourceAttributes[$key] = $value;
                }
                unset($yamlEntity['attributes']);

                if (sizeof($yamlEntity) > 0) {
                    dump('Unsupported class key: ', $yamlEntity);
                }

                $this->addUseStatement($phpFile, 'ApiPlatform\Metadata\ApiResource');
                $class->addAttribute('ApiPlatform\Metadata\ApiResource', $apiResourceAttributes);

                $printer = new PsrPrinter();
                $printer->wrapLength = 9999;
                $newCode = $printer->printFile($phpFile);

                file_put_contents($filename, $newCode);
            }
        }
    }

    private function handleSerializerFile(SplFileInfo $file): void
    {
        $absoluteFilePath = $file->getRealPath();

        $yaml = Yaml::parseFile($absoluteFilePath);

        $fileNamespace = array_keys($yaml)[0];
        $parts = explode('\\', $fileNamespace);
        $fileClass = end($parts);

        $yamlEntity = $yaml[$fileNamespace];

        $filename = $this->doctrineEntityDir . '/' . $fileClass . '.php';

        if (file_exists($filename)) {
            $code = file_get_contents($filename);
            $phpFile = PhpFile::fromCode($code);

            $class = current($phpFile->getClasses());

            if ($class instanceof ClassType) {
                if (array_key_exists('attributes', $yamlEntity)) {
                    foreach ($yamlEntity['attributes'] as $key => $field) {
                        $this->handleSerializerAttribute($phpFile, $class, $key, $field);
                    }
                    unset($yamlEntity['attributes']);
                }

                if (sizeof($yamlEntity) > 0) {
                    dump('Unsupported class key: ', $yamlEntity);
                }

                $printer = new PsrPrinter();
                $printer->wrapLength = 9999;
                $newCode = $printer->printFile($phpFile);

                file_put_contents($filename, $newCode);
            }
        }
    }

    public function handleOperation(PhpFile $phpFile, ClassType $class, int|string $key, array $field, bool $isCollection): Literal
    {
        $operationArguments = [];

        if (isset($field['method'])) {
            $method = $field['method'];
            unset($field['method']);
        } else {
            $method = $key;
        }

        if (isset($field['path'])) {
            $operationArguments['uriTemplate'] = $field['path'];
        }
        unset($field['path']);

        if (isset($field['controller'])) {
            $operationArguments['controller'] = $field['controller'];
        }
        unset($field['controller']);

        if (isset($field['swagger_context'])) {
            $operationArguments['openapiContext'] = $field['swagger_context'];
        }
        unset($field['swagger_context']);

        if (isset($field['normalization_context'])) {
            $operationArguments['normalizationContext'] = $field['normalization_context'];
        }
        unset($field['normalization_context']);

        if (isset($field['denormalization_context'])) {
            $operationArguments['denormalizationContext'] = $field['denormalization_context'];
        }
        unset($field['denormalization_context']);

        if (isset($field['security'])) {
            $operationArguments['security'] = $field['security'];
        }
        unset($field['security']);

        if (isset($field['security_post_denormalize'])) {
            $operationArguments['securityPostDenormalize'] = $field['security_post_denormalize'];
        }
        unset($field['security_post_denormalize']);

        if (isset($field['deprecation_reason'])) {
            $operationArguments['deprecationReason'] = $field['deprecation_reason'];
        }
        unset($field['deprecation_reason']);

        if (isset($field['filters'])) {
            $operationArguments['filters'] = $field['filters'];
        }
        unset($field['filters']);

        if (isset($field['validation_groups'])) {
            $operationArguments['validationContext'] = $field['validation_groups'];
        }
        unset($field['validation_groups']);

        if (isset($field['deserialize'])) {
            $operationArguments['deserialize'] = $field['deserialize'];
        }
        unset($field['deserialize']);

        unset($field['resourceClass']); // Unsupported field

        if (sizeof($field) > 0) {
            dump('Unsupported column key: ', $field); die;
        }

        $this->addUseStatement($phpFile, 'ApiPlatform\Metadata\\' . $this->convertMethodToClassName($method, $isCollection));
        return Literal::new($this->convertMethodToClassName($method, $isCollection), $operationArguments);
    }

    public function handleSerializerAttribute(PhpFile $phpFile, ClassType $class, int|string $key, array $field): void
    {
        if ($class->hasProperty($key)) {
            $property = $class->getProperty($key);

            if (isset($field['groups'])) {
                $this->addUseStatement($phpFile, 'Symfony\Component\Serializer\Attribute\Groups');

                $groupsAttributes = [$field['groups']];
                $property->addAttribute('Symfony\Component\Serializer\Attribute\Groups', $groupsAttributes);
            }
            unset($field['groups']);

            if (isset($field['expose'])) {
                if ($field['expose'] === false) {
                    $this->addUseStatement($phpFile, 'Symfony\Component\Serializer\Attribute\Ignore');
                    $property->addAttribute('Symfony\Component\Serializer\Attribute\Ignore');
                }
            }
            unset($field['expose']);

            if (sizeof($field) > 0) {
                dump('Unsupported column key: ', $field); die;
            }
        }
    }

    private function addUseStatement(PhpFile $phpFile, string $name, ?string $alias = null)
    {
        foreach ($phpFile->getNamespaces() as $namespace) {
            $namespace->addUse($name, $alias);
        }
    }

    private function convertMethodToClassName(string $method, bool $isCollection): string
    {
        return match (strtoupper($method)) {
            'GET' => $isCollection ? 'GetCollection' : 'Get',
            'POST' => 'Post',
            'DELETE' => 'Delete',
            'PATCH' => 'Patch',
            'PUT' => 'Put',
            default => 'Get',
        };
    }

    private function snakeToCamel(string $input): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $input))));
    }
}