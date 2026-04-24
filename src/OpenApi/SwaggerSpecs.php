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
        $this->flattenSingleRefAllOf($this->specs);

        return $this;
    }

    /**
     * Flatten `allOf: [{ $ref: ... }]` wrappers (used to add nullable to a $ref) into
     * a direct $ref, so the generators can treat them uniformly.
     */
    private function flattenSingleRefAllOf(array &$data): void
    {
        foreach ($data as $key => &$value) {
            if (! is_array($value)) {
                continue;
            }

            if (isset($value['allOf']) && is_array($value['allOf'])
                && count($value['allOf']) === 1 && isset($value['allOf'][0]['$ref'])) {
                $value = ['$ref' => $value['allOf'][0]['$ref']];
                continue;
            }

            $this->flattenSingleRefAllOf($value);
        }
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

        // Merge paths at the HTTP method level so existing methods are preserved
        // when newer specs only replace or add individual methods on the same path.
        // Newer spec wins on same path + same HTTP method (used to deprecate older operations).
        foreach ($otherSpecs['paths'] as $path => $methods) {
            if (! isset($resultSpecs['paths'][$path])) {
                $resultSpecs['paths'][$path] = $methods;
                continue;
            }

            foreach ($methods as $httpMethod => $methodDef) {
                $resultSpecs['paths'][$path][$httpMethod] = $methodDef;
            }
        }

        $resultSpecs['components']['schemas'] = array_merge($resultSpecs['components']['schemas'], $otherSpecs['components']['schemas']);

        return new SwaggerSpecs($resultSpecs);
    }
}
