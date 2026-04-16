<?php

namespace Picqer\BolRetailerV10\Tests\OpenApi;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

class SpecsDownloaderTest extends TestCase
{
    private function normalize(array $spec): array
    {
        $class = new ReflectionClass(\Picqer\BolRetailerV10\OpenApi\SpecsDownloader::class);
        $method = $class->getMethod('normalizeOffersSpec');
        $method->setAccessible(true);

        return $method->invoke(null, $spec);
    }

    private function buildSpec(array $paths = [], array $schemas = []): array
    {
        return [
            'openapi' => '3.0.1',
            'paths' => $paths,
            'components' => ['schemas' => $schemas],
        ];
    }

    // --- Path normalization ---

    public function testDescriptionIsCopiedFromSummary(): void
    {
        $spec = $this->buildSpec([
            '/test' => [
                'get' => [
                    'operationId' => 'get-test',
                    'summary' => 'Get test resource',
                    'responses' => ['200' => ['description' => 'OK']],
                ],
            ],
        ]);

        $result = $this->normalize($spec);

        $this->assertEquals('Get test resource', $result['paths']['/test']['get']['description']);
    }

    public function testExistingDescriptionIsNotOverwritten(): void
    {
        $spec = $this->buildSpec([
            '/test' => [
                'get' => [
                    'operationId' => 'get-test',
                    'summary' => 'Summary text',
                    'description' => 'Original description',
                    'responses' => ['200' => ['description' => 'OK']],
                ],
            ],
        ]);

        $result = $this->normalize($spec);

        $this->assertEquals('Original description', $result['paths']['/test']['get']['description']);
    }

    public function testExternalRefResponsesAreRemoved(): void
    {
        $spec = $this->buildSpec([
            '/test' => [
                'get' => [
                    'operationId' => 'get-test',
                    'summary' => 'Test',
                    'responses' => [
                        '200' => ['description' => 'OK'],
                        '400' => ['$ref' => '../common/responses.yaml#/components/responses/400_BAD_REQUEST'],
                        '500' => ['$ref' => '../common/responses.yaml#/components/responses/500_INTERNAL_SERVER_ERROR'],
                    ],
                ],
            ],
        ]);

        $result = $this->normalize($spec);
        $responses = $result['paths']['/test']['get']['responses'];

        $this->assertArrayHasKey('200', $responses);
        $this->assertArrayNotHasKey('400', $responses);
        $this->assertArrayNotHasKey('500', $responses);
    }

    public function testResponseCode201IsMappedTo200(): void
    {
        $spec = $this->buildSpec([
            '/test' => [
                'post' => [
                    'operationId' => 'create-test',
                    'summary' => 'Create',
                    'responses' => [
                        201 => [
                            'description' => 'Created',
                            'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/Foo']]],
                        ],
                    ],
                ],
            ],
        ], [
            'Foo' => ['type' => 'object', 'properties' => ['id' => ['type' => 'string']]],
        ]);

        $result = $this->normalize($spec);
        $responses = $result['paths']['/test']['post']['responses'];

        $this->assertArrayHasKey('200', $responses);
        $this->assertArrayNotHasKey('201', $responses);
        $this->assertArrayNotHasKey(201, $responses);
    }

    public function testResponseCode204IsMappedTo202WithProcessStatus(): void
    {
        $spec = $this->buildSpec([
            '/test' => [
                'delete' => [
                    'operationId' => 'delete-test',
                    'summary' => 'Delete',
                    'responses' => [
                        204 => [
                            'description' => 'Deleted',
                            'content' => ['application/vnd.retailer.v11+json' => []],
                        ],
                    ],
                ],
            ],
        ]);

        $result = $this->normalize($spec);
        $responses = $result['paths']['/test']['delete']['responses'];

        $this->assertArrayHasKey('202', $responses);
        $this->assertArrayNotHasKey('204', $responses);
        $this->assertArrayNotHasKey(204, $responses);

        $schema = $responses['202']['content']['application/vnd.retailer.v11+json']['schema'];
        $this->assertEquals('#/components/schemas/ProcessStatus', $schema['$ref']);
    }

    // --- Schema normalization ---

    public function testOneOfSchemaIsFlattenedWithUnionOfProperties(): void
    {
        $spec = $this->buildSpec([], [
            'Animal' => [
                'oneOf' => [
                    ['$ref' => '#/components/schemas/Dog'],
                    ['$ref' => '#/components/schemas/Cat'],
                ],
            ],
            'Dog' => [
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string'],
                    'breed' => ['type' => 'string'],
                ],
            ],
            'Cat' => [
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string'],
                    'indoor' => ['type' => 'boolean'],
                ],
            ],
        ]);

        $result = $this->normalize($spec);
        $schemas = $result['components']['schemas'];

        // oneOf is flattened to object with union of properties
        $this->assertEquals('object', $schemas['Animal']['type']);
        $this->assertArrayHasKey('name', $schemas['Animal']['properties']);
        $this->assertArrayHasKey('breed', $schemas['Animal']['properties']);
        $this->assertArrayHasKey('indoor', $schemas['Animal']['properties']);

        // Sub-schemas are removed
        $this->assertArrayNotHasKey('Dog', $schemas);
        $this->assertArrayNotHasKey('Cat', $schemas);
    }

    public function testPrimitiveSchemasAreInlinedAndRemoved(): void
    {
        $spec = $this->buildSpec([], [
            'Container' => [
                'type' => 'object',
                'properties' => [
                    'code' => ['$ref' => '#/components/schemas/CountryCode'],
                ],
            ],
            'CountryCode' => [
                'type' => 'string',
                'enum' => ['NL', 'BE'],
                'description' => 'Country code',
            ],
        ]);

        $result = $this->normalize($spec);
        $schemas = $result['components']['schemas'];

        // Primitive schema is removed
        $this->assertArrayNotHasKey('CountryCode', $schemas);

        // $ref is replaced with inline definition
        $codeProp = $schemas['Container']['properties']['code'];
        $this->assertEquals('string', $codeProp['type']);
        $this->assertEquals(['NL', 'BE'], $codeProp['enum']);
    }

    public function testOverlappingSchemasAreRenamed(): void
    {
        $spec = $this->buildSpec([], [
            'Condition' => [
                'type' => 'object',
                'properties' => ['category' => ['type' => 'string']],
            ],
            'RetailerOffer' => [
                'type' => 'object',
                'properties' => ['offerId' => ['type' => 'string']],
            ],
        ]);

        $result = $this->normalize($spec);
        $schemas = $result['components']['schemas'];

        $this->assertArrayNotHasKey('Condition', $schemas);
        $this->assertArrayHasKey('OffersCondition', $schemas);

        $this->assertArrayNotHasKey('RetailerOffer', $schemas);
        $this->assertArrayHasKey('OffersRetailerOffer', $schemas);
    }

    public function testRefsToRenamedSchemasAreUpdated(): void
    {
        $spec = $this->buildSpec([
            '/test' => [
                'post' => [
                    'operationId' => 'create-test',
                    'summary' => 'Create',
                    'responses' => [
                        200 => [
                            'description' => 'OK',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/RetailerOffer'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], [
            'RetailerOffer' => [
                'type' => 'object',
                'properties' => ['offerId' => ['type' => 'string']],
            ],
        ]);

        $result = $this->normalize($spec);

        $ref = $result['paths']['/test']['post']['responses']['200']['content']['application/json']['schema']['$ref'];
        $this->assertEquals('#/components/schemas/OffersRetailerOffer', $ref);
    }

    public function testInlineObjectInArrayItemsIsExtractedToNamedSchema(): void
    {
        $spec = $this->buildSpec([], [
            'Parent' => [
                'type' => 'object',
                'properties' => [
                    'items' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'quantity' => ['type' => 'integer'],
                                'price' => ['type' => 'number'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $result = $this->normalize($spec);
        $schemas = $result['components']['schemas'];

        // Inline object extracted to named schema
        $this->assertArrayHasKey('ParentItems', $schemas);
        $this->assertArrayHasKey('quantity', $schemas['ParentItems']['properties']);
        $this->assertArrayHasKey('price', $schemas['ParentItems']['properties']);

        // Original property now uses $ref
        $this->assertEquals(
            '#/components/schemas/ParentItems',
            $schemas['Parent']['properties']['items']['items']['$ref']
        );
    }

    public function testInlineObjectPropertyIsExtractedToNamedSchema(): void
    {
        $spec = $this->buildSpec([], [
            'Parent' => [
                'type' => 'object',
                'properties' => [
                    'details' => [
                        'type' => 'object',
                        'properties' => [
                            'min' => ['type' => 'integer'],
                            'max' => ['type' => 'integer'],
                        ],
                    ],
                ],
            ],
        ]);

        $result = $this->normalize($spec);
        $schemas = $result['components']['schemas'];

        // Inline object extracted
        $this->assertArrayHasKey('ParentDetails', $schemas);

        // Original property replaced with $ref
        $this->assertEquals(
            '#/components/schemas/ParentDetails',
            $schemas['Parent']['properties']['details']['$ref']
        );
    }

    public function testMissingTypeObjectIsAdded(): void
    {
        $spec = $this->buildSpec([], [
            'NoType' => [
                'properties' => [
                    'id' => ['type' => 'string'],
                ],
            ],
        ]);

        $result = $this->normalize($spec);

        $this->assertEquals('object', $result['components']['schemas']['NoType']['type']);
    }
}
