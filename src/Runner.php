<?php

namespace Nicodemuz\ApiPlatformYamlToAttributes;

use Nette\PhpGenerator\ClassType;
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

    public function convertSerializerConfigurations(): void
    {
        $finder = new Finder();
        $finder
            ->in($this->yamlSerializerDir)
            ->files()
            ->name('*.yaml')
        ;

        foreach ($finder as $file) {
            $this->handleSerializerFile($file);
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
                dump('Unsupported column key: ', $field, $class); die;
            }
        }
    }

    private function addUseStatement(PhpFile $phpFile, string $name, ?string $alias = null)
    {
        foreach ($phpFile->getNamespaces() as $namespace) {
            $namespace->addUse($name, $alias);
        }
    }
}