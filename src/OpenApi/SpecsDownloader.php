<?php

namespace Picqer\BolRetailerV10\OpenApi;

use Symfony\Component\Yaml\Yaml;

class SpecsDownloader
{
    private const SPECS = [
        [
            'source' => 'https://api.bol.com/retailer/public/apispec/Retailer%20API%20-%20v10',
            'target' => 'retailer.json',
        ],
        [
            'source' => 'https://api.bol.com/retailer/public/apispec/Shared%20API%20-%20v10',
            'target' => 'shared.json',
        ],
        [
            'source' => 'https://api.bol.com/registry/api-definitions/offers/offers-v11.yaml',
            'target' => 'offers.json',
            'normalize' => true,
        ],
    ];

    // Schemas that exist in both retailer.json (v10) and offers.yaml (v11) with different structures.
    // These get an "Offers" prefix so both versions coexist after merge.
    // Stock is excluded because it has an identical structure in both versions.
    private const OVERLAPPING_SCHEMAS = [
        'Condition' => 'OffersCondition',
        'CreateOfferRequest' => 'OffersCreateOfferRequest',
        'Fulfilment' => 'OffersFulfilment',
        'Pricing' => 'OffersPricing',
        'Product' => 'OffersProduct',
        'RetailerOffer' => 'OffersRetailerOffer',
    ];


    public static function run(): void
    {
        foreach (static::SPECS as $spec) {
            $sourceContent = file_get_contents($spec['source']);

            if (self::isYamlSource($spec['source'])) {
                $parsed = Yaml::parse($sourceContent);
            } else {
                $parsed = json_decode($sourceContent, true);
            }

            if ($spec['normalize'] ?? false) {
                $parsed = self::normalizeOffersSpec($parsed);
            }

            $sourceTidied = json_encode($parsed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . $spec['target'], $sourceTidied);
        }
    }

    private static function isYamlSource(string $source): bool
    {
        return str_ends_with($source, '.yaml') || str_ends_with($source, '.yml');
    }

    private static function normalizeOffersSpec(array $spec): array
    {
        $schemas = &$spec['components']['schemas'];

        // Step 1: Collect primitive/enum schemas (no properties, no oneOf) for inlining
        $primitiveSchemas = [];
        foreach ($schemas as $name => $schema) {
            if (! isset($schema['properties']) && ! isset($schema['oneOf'])) {
                $inlined = $schema;
                unset($inlined['example']);
                $primitiveSchemas[$name] = $inlined;
            }
        }

        // Step 2: Flatten oneOf schemas into flat objects with union of all sub-schema properties
        $consumedSubSchemas = self::flattenOneOfSchemas($schemas, $primitiveSchemas);

        // Step 3: Extract inline anonymous objects to named schemas
        self::extractInlineObjects($schemas);

        // Step 4: Rename overlapping schemas and build the full rename map
        $renameMap = self::OVERLAPPING_SCHEMAS;
        foreach ($renameMap as $oldName => $newName) {
            if (isset($schemas[$oldName])) {
                $schemas[$newName] = $schemas[$oldName];
                unset($schemas[$oldName]);
            }
        }

        // Step 5: Remove primitive schemas and consumed oneOf sub-schemas
        $schemasToRemove = array_merge(
            array_keys($primitiveSchemas),
            $consumedSubSchemas
        );
        foreach ($schemasToRemove as $name) {
            unset($schemas[$name]);
        }

        // Step 6: Deep-walk entire spec to resolve all $ref (apply rename + inline primitives)
        self::resolveAllRefs($spec, $renameMap, $primitiveSchemas);

        // Step 7: Add type: object where missing
        foreach ($schemas as &$schema) {
            if (isset($schema['properties']) && ! isset($schema['type'])) {
                $schema['type'] = 'object';
            }
        }
        unset($schema);

        // Step 8: Normalize path methods
        self::normalizePaths($spec);

        return $spec;
    }

    /**
     * Flatten oneOf schemas into regular object schemas with union of all sub-schema properties.
     * This makes them compatible with the model generator which expects properties on every schema.
     *
     * Flatten oneOf schemas into regular object schemas with union of all sub-schema properties.
     * Returns the names of sub-schemas that were consumed and can be removed.
     */
    private static function flattenOneOfSchemas(array &$schemas, array $primitiveSchemas): array
    {
        $consumedSubSchemas = [];

        foreach ($schemas as $name => &$schema) {
            if (! isset($schema['oneOf'])) {
                continue;
            }

            $mergedProperties = [];
            foreach ($schema['oneOf'] as $subRef) {
                if (! isset($subRef['$ref'])) {
                    continue;
                }
                $subName = substr($subRef['$ref'], strrpos($subRef['$ref'], '/') + 1);
                $consumedSubSchemas[] = $subName;

                if (! isset($schemas[$subName]['properties'])) {
                    continue;
                }

                foreach ($schemas[$subName]['properties'] as $propName => $propDef) {
                    if (isset($propDef['$ref'])) {
                        $refName = substr($propDef['$ref'], strrpos($propDef['$ref'], '/') + 1);
                        if (isset($primitiveSchemas[$refName])) {
                            $propDef = $primitiveSchemas[$refName];
                        }
                    }
                    if (! isset($mergedProperties[$propName])) {
                        $mergedProperties[$propName] = $propDef;
                    }
                }
            }

            $schema = [
                'type' => 'object',
                'properties' => $mergedProperties,
            ];
        }
        unset($schema);

        return $consumedSubSchemas;
    }

    /**
     * Extract inline anonymous objects from array items and object properties into named schemas.
     */
    private static function extractInlineObjects(array &$schemas): void
    {
        $newSchemas = [];

        foreach ($schemas as $schemaName => &$schema) {
            if (! isset($schema['properties'])) {
                continue;
            }

            foreach ($schema['properties'] as $propName => &$propDef) {
                // Extract inline objects from array items
                if (isset($propDef['type']) && $propDef['type'] === 'array'
                    && isset($propDef['items']['properties'])) {
                    $extractedName = self::generateExtractedSchemaName($schemaName, $propName);
                    $newSchemas[$extractedName] = $propDef['items'];
                    if (! isset($newSchemas[$extractedName]['type'])) {
                        $newSchemas[$extractedName]['type'] = 'object';
                    }
                    $propDef['items'] = ['$ref' => '#/components/schemas/' . $extractedName];
                }

                // Extract inline objects from direct properties
                if (isset($propDef['properties']) && isset($propDef['type']) && $propDef['type'] === 'object') {
                    $extractedName = self::generateExtractedSchemaName($schemaName, $propName);
                    $newSchemas[$extractedName] = $propDef;
                    $propDef = ['$ref' => '#/components/schemas/' . $extractedName];
                }
            }
        }
        unset($schema, $propDef);

        // Add extracted schemas
        foreach ($newSchemas as $name => $schema) {
            $schemas[$name] = $schema;
        }
    }

    private static function generateExtractedSchemaName(string $parentSchema, string $propertyName): string
    {
        return $parentSchema . ucfirst($propertyName);
    }

    /**
     * Recursively walk the spec and resolve all $ref values:
     * - Apply rename map for overlapping schemas
     * - Inline primitive schemas by replacing the $ref with the schema definition
     */
    private static function resolveAllRefs(array &$data, array $renameMap, array $primitiveSchemas): void
    {
        foreach ($data as $key => &$value) {
            if (! is_array($value)) {
                continue;
            }

            // If this node is a $ref object, resolve it
            if (isset($value['$ref']) && is_string($value['$ref'])
                && str_starts_with($value['$ref'], '#/components/schemas/')) {
                $refName = substr($value['$ref'], strlen('#/components/schemas/'));

                // Inline primitive schemas
                if (isset($primitiveSchemas[$refName])) {
                    $data[$key] = $primitiveSchemas[$refName];
                    continue;
                }

                // Apply rename map
                if (isset($renameMap[$refName])) {
                    $value['$ref'] = '#/components/schemas/' . $renameMap[$refName];
                }
            }

            // Recurse into child arrays/objects
            self::resolveAllRefs($value, $renameMap, $primitiveSchemas);
        }
    }

    /**
     * Normalize path method definitions to match the retailer.json layout.
     */
    private static function normalizePaths(array &$spec): void
    {
        foreach ($spec['paths'] as &$methods) {
            foreach ($methods as &$methodDef) {
                if (! is_array($methodDef)) {
                    continue;
                }

                // Use summary as description when description is missing
                if (! isset($methodDef['description']) && isset($methodDef['summary'])) {
                    $methodDef['description'] = $methodDef['summary'];
                }

                if (! isset($methodDef['responses'])) {
                    continue;
                }

                // Normalize responses: cast codes to strings and drop external $refs.
                $newResponses = [];
                foreach ($methodDef['responses'] as $code => $response) {
                    $code = (string) $code;

                    // Remove external $ref responses (e.g. ../common/responses.yaml#/...)
                    if (isset($response['$ref']) && ! str_starts_with($response['$ref'], '#/')) {
                        continue;
                    }

                    $newResponses[$code] = $response;
                }

                $methodDef['responses'] = $newResponses;
            }
        }
    }
}
