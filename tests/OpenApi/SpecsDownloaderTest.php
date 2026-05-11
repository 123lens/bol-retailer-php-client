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

    public function testResponseCode201IsPreserved(): void
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

        $this->assertArrayHasKey('201', $responses);
        $this->assertArrayNotHasKey('200', $responses);

        $schema = $responses['201']['content']['application/json']['schema'];
        $this->assertEquals('#/components/schemas/Foo', $schema['$ref']);
    }

    public function testResponseCode204IsPreservedWithMediaTypeStub(): void
    {
        $spec = $this->buildSpec([
            '/test' => [
                'delete' => [
                    'operationId' => 'delete-test',
                    'summary' => 'Delete',
                    'responses' => [
                        204 => [
                            'description' => 'Deleted',
                        ],
                    ],
                ],
            ],
        ]);

        $result = $this->normalize($spec);
        $responses = $result['paths']['/test']['delete']['responses'];

        $this->assertArrayHasKey('204', $responses);
        $this->assertArrayNotHasKey('202', $responses);
        $this->assertEquals('Deleted', $responses['204']['description']);

        // A v11 media-type stub is injected so the generator emits the Accept header.
        $this->assertArrayHasKey('application/vnd.retailer.v11+json', $responses['204']['content']);
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

    // --- Economic Operator renames ---

    public function testEconomicOperatorAddressIsRenamed(): void
    {
        $spec = $this->buildSpec([], [
            'address' => [
                'type' => 'object',
                'properties' => ['street' => ['type' => 'string']],
            ],
        ]);

        $result = $this->normalize($spec);
        $schemas = $result['components']['schemas'];

        $this->assertArrayNotHasKey('address', $schemas);
        $this->assertArrayHasKey('EconomicOperatorAddress', $schemas);
    }

    public function testEconomicOperatorPageIsRenamed(): void
    {
        $spec = $this->buildSpec([], [
            'page' => [
                'type' => 'object',
                'properties' => ['pageNumber' => ['type' => 'integer']],
            ],
        ]);

        $result = $this->normalize($spec);
        $schemas = $result['components']['schemas'];

        $this->assertArrayNotHasKey('page', $schemas);
        $this->assertArrayHasKey('EconomicOperatorPage', $schemas);
    }

    public function testRefsToRenamedEconomicOperatorAddressAreUpdated(): void
    {
        $spec = $this->buildSpec([], [
            'address' => [
                'type' => 'object',
                'properties' => ['street' => ['type' => 'string']],
            ],
            'EconomicOperator' => [
                'type' => 'object',
                'properties' => ['address' => ['$ref' => '#/components/schemas/address']],
            ],
        ]);

        $result = $this->normalize($spec);

        $this->assertEquals(
            '#/components/schemas/EconomicOperatorAddress',
            $result['components']['schemas']['EconomicOperator']['properties']['address']['$ref']
        );
    }

    // --- Component response inlining ---

    public function testComponentResponseRefIsInlined(): void
    {
        $spec = [
            'openapi' => '3.0.1',
            'paths' => [
                '/test' => [
                    'post' => [
                        'operationId' => 'create-test',
                        'summary' => 'Create',
                        'responses' => [
                            '200' => ['$ref' => '#/components/responses/200_CRUD_SUCCESSFUL'],
                        ],
                    ],
                ],
            ],
            'components' => [
                'schemas' => [
                    'Foo' => ['type' => 'object', 'properties' => ['id' => ['type' => 'string']]],
                ],
                'responses' => [
                    '200_CRUD_SUCCESSFUL' => [
                        'description' => 'Created',
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/Foo'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->normalize($spec);
        $response = $result['paths']['/test']['post']['responses']['200'];

        $this->assertArrayNotHasKey('$ref', $response);
        $this->assertEquals('Created', $response['description']);
        $this->assertEquals(
            '#/components/schemas/Foo',
            $response['content']['application/json']['schema']['$ref']
        );
    }

    public function testUnknownComponentResponseRefIsLeftAlone(): void
    {
        $spec = [
            'openapi' => '3.0.1',
            'paths' => [
                '/test' => [
                    'get' => [
                        'operationId' => 'get-test',
                        'summary' => 'Get',
                        'responses' => [
                            '200' => ['$ref' => '#/components/responses/UNKNOWN'],
                        ],
                    ],
                ],
            ],
            'components' => [
                'schemas' => [],
                'responses' => [
                    'OTHER' => ['description' => 'Other'],
                ],
            ],
        ];

        $result = $this->normalize($spec);
        $response = $result['paths']['/test']['get']['responses']['200'];

        // No matching component response means the ref survives normalization unchanged.
        $this->assertEquals('#/components/responses/UNKNOWN', $response['$ref']);
    }

    // --- External ref resolution ---

    public function testExternalSchemaRefIsRewrittenToLocal(): void
    {
        $class = new ReflectionClass(\Picqer\BolRetailerV10\OpenApi\SpecsDownloader::class);
        $method = $class->getMethod('rewriteExternalRefs');
        $method->setAccessible(true);

        $data = [
            'parameters' => [
                ['schema' => ['$ref' => '../common/models.yaml#/components/schemas/pageSize']],
                ['schema' => ['$ref' => '#/components/schemas/Local']],
                ['schema' => ['$ref' => '../common/responses.yaml#/components/responses/400_BAD_REQUEST']],
            ],
        ];

        $method->invokeArgs(null, [&$data]);

        // common/models.yaml refs get rewritten to local
        $this->assertEquals('#/components/schemas/pageSize', $data['parameters'][0]['schema']['$ref']);

        // Already-local refs stay untouched
        $this->assertEquals('#/components/schemas/Local', $data['parameters'][1]['schema']['$ref']);

        // responses.yaml is not in EXTERNAL_REF_SOURCES — stays as-is (normalizePaths drops it later)
        $this->assertEquals(
            '../common/responses.yaml#/components/responses/400_BAD_REQUEST',
            $data['parameters'][2]['schema']['$ref']
        );
    }

    public function testExpandTransitivelyFollowsInternalRefs(): void
    {
        $class = new ReflectionClass(\Picqer\BolRetailerV10\OpenApi\SpecsDownloader::class);
        $method = $class->getMethod('expandTransitively');
        $method->setAccessible(true);

        $sourceSchemas = [
            'page' => [
                'type' => 'object',
                'properties' => [
                    'pageSize' => ['$ref' => '#/components/schemas/pageSize'],
                    'pageNumber' => ['$ref' => '#/components/schemas/pageNumber'],
                ],
            ],
            'pageSize' => ['type' => 'integer'],
            'pageNumber' => ['type' => 'integer'],
            'unused' => ['type' => 'string'],
        ];

        $result = $method->invoke(null, ['page' => true], $sourceSchemas);

        sort($result);
        $this->assertEquals(['page', 'pageNumber', 'pageSize'], $result);
    }
}
