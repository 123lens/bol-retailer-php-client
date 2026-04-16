<?php

namespace Picqer\BolRetailerV10\OpenApi;

class SwaggerSpecs
{
    private $specs = [];

    public function __construct($specs = [])
    {
        $this->specs = $specs;
    }

    public function load(string $file): SwaggerSpecs
    {
        $content = file_get_contents($file);
        $content = $this->replaceErroneousCharacters($content);

        $this->specs = json_decode($content, true);

        return $this;
    }

    private function replaceErroneousCharacters(string $content): string
    {
        $replacements = [
            hex2bin('e28082') => ' ', // 'ENSP' space
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    public function getSpecs(): array
    {
        return $this->specs;
    }

    public function merge(SwaggerSpecs $specs): SwaggerSpecs
    {
        $resultSpecs = $this->specs;
        $otherSpecs = $specs->getSpecs();

        foreach ($otherSpecs['paths'] as $path => $methods) {
            if (! isset($resultSpecs['paths'][$path])) {
                $resultSpecs['paths'][$path] = $methods;
                continue;
            }

            foreach ($methods as $httpMethod => $methodDef) {
                if (! isset($resultSpecs['paths'][$path][$httpMethod])) {
                    // New method on existing path — just add it
                    $resultSpecs['paths'][$path][$httpMethod] = $methodDef;
                } else {
                    // Same path + same HTTP method already exists.
                    $existingOperationId = $resultSpecs['paths'][$path][$httpMethod]['operationId'] ?? '';
                    $newOperationId = $methodDef['operationId'] ?? '';

                    if ($newOperationId !== '' && $newOperationId !== $existingOperationId) {
                        // Different operationId: keep both as separate client methods
                        // by aliasing the path with # so they point to the same URL.
                        $aliasPath = $path . '#' . $newOperationId;
                        $resultSpecs['paths'][$aliasPath][$httpMethod] = $methodDef;
                    }
                    // Same operationId: skip the duplicate, keep the existing one.
                }
            }
        }

        $resultSpecs['components']['schemas'] = array_merge($resultSpecs['components']['schemas'], $otherSpecs['components']['schemas']);

        return new SwaggerSpecs($resultSpecs);
    }
}
