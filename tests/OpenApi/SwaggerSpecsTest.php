<?php

namespace Picqer\BolRetailerV10\Tests\OpenApi;

use PHPUnit\Framework\TestCase;
use Picqer\BolRetailerV10\OpenApi\SwaggerSpecs;

class SwaggerSpecsTest extends TestCase
{
    public function testMergeAddsNewPath(): void
    {
        $base = new SwaggerSpecs([
            'paths' => [
                '/existing' => ['get' => ['operationId' => 'get-existing']],
            ],
            'components' => ['schemas' => []],
        ]);

        $other = new SwaggerSpecs([
            'paths' => [
                '/new' => ['get' => ['operationId' => 'get-new']],
            ],
            'components' => ['schemas' => []],
        ]);

        $merged = $base->merge($other)->getSpecs();

        $this->assertArrayHasKey('/existing', $merged['paths']);
        $this->assertArrayHasKey('/new', $merged['paths']);
    }

    public function testMergeAddsNewMethodToExistingPath(): void
    {
        $base = new SwaggerSpecs([
            'paths' => [
                '/offers' => ['get' => ['operationId' => 'get-offers']],
            ],
            'components' => ['schemas' => []],
        ]);

        $other = new SwaggerSpecs([
            'paths' => [
                '/offers' => ['post' => ['operationId' => 'post-offers']],
            ],
            'components' => ['schemas' => []],
        ]);

        $merged = $base->merge($other)->getSpecs();

        $this->assertArrayHasKey('get', $merged['paths']['/offers']);
        $this->assertArrayHasKey('post', $merged['paths']['/offers']);
    }

    public function testMergeOverridesExistingMethodWithNewerSpec(): void
    {
        $base = new SwaggerSpecs([
            'paths' => [
                '/offers' => ['post' => ['operationId' => 'post-offer', 'source' => 'v10']],
            ],
            'components' => ['schemas' => []],
        ]);

        $other = new SwaggerSpecs([
            'paths' => [
                '/offers' => ['post' => ['operationId' => 'create-offer', 'source' => 'v11']],
            ],
            'components' => ['schemas' => []],
        ]);

        $merged = $base->merge($other)->getSpecs();

        // v11 replaces v10 for same path + same HTTP method
        $this->assertEquals('create-offer', $merged['paths']['/offers']['post']['operationId']);
        $this->assertEquals('v11', $merged['paths']['/offers']['post']['source']);
    }

    public function testMergePreservesMethodsOnlyInBaseSpec(): void
    {
        $base = new SwaggerSpecs([
            'paths' => [
                '/offers/{id}' => [
                    'get' => ['operationId' => 'get-offer', 'source' => 'v10'],
                    'put' => ['operationId' => 'put-offer', 'source' => 'v10'],
                    'delete' => ['operationId' => 'delete-offer', 'source' => 'v10'],
                ],
            ],
            'components' => ['schemas' => []],
        ]);

        $other = new SwaggerSpecs([
            'paths' => [
                '/offers/{id}' => [
                    'get' => ['operationId' => 'get-offer', 'source' => 'v11'],   // overrides
                    'delete' => ['operationId' => 'delete-offer', 'source' => 'v11'], // overrides
                    'patch' => ['operationId' => 'update-offer', 'source' => 'v11'],  // new
                ],
            ],
            'components' => ['schemas' => []],
        ]);

        $merged = $base->merge($other)->getSpecs();

        // Overridden by v11
        $this->assertEquals('v11', $merged['paths']['/offers/{id}']['get']['source']);
        $this->assertEquals('v11', $merged['paths']['/offers/{id}']['delete']['source']);

        // PUT only exists in v10 — must be preserved
        $this->assertArrayHasKey('put', $merged['paths']['/offers/{id}']);
        $this->assertEquals('v10', $merged['paths']['/offers/{id}']['put']['source']);

        // PATCH is new in v11
        $this->assertArrayHasKey('patch', $merged['paths']['/offers/{id}']);
        $this->assertEquals('v11', $merged['paths']['/offers/{id}']['patch']['source']);
    }

    public function testLoadFlattensSingleRefAllOf(): void
    {
        $spec = [
            'paths' => [],
            'components' => [
                'schemas' => [
                    'Wrapper' => [
                        'type' => 'object',
                        'properties' => [
                            'inner' => [
                                'nullable' => true,
                                'allOf' => [['$ref' => '#/components/schemas/Other']],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $tmp = tempnam(sys_get_temp_dir(), 'spec') . '.json';
        file_put_contents($tmp, json_encode($spec));

        $loaded = (new SwaggerSpecs())->load($tmp)->getSpecs();
        unlink($tmp);

        $innerProp = $loaded['components']['schemas']['Wrapper']['properties']['inner'];
        $this->assertEquals(['$ref' => '#/components/schemas/Other'], $innerProp);
    }

    public function testMergeCombinesSchemas(): void
    {
        $base = new SwaggerSpecs([
            'paths' => [],
            'components' => ['schemas' => ['Foo' => ['type' => 'object']]],
        ]);

        $other = new SwaggerSpecs([
            'paths' => [],
            'components' => ['schemas' => ['Bar' => ['type' => 'object']]],
        ]);

        $merged = $base->merge($other)->getSpecs();

        $this->assertArrayHasKey('Foo', $merged['components']['schemas']);
        $this->assertArrayHasKey('Bar', $merged['components']['schemas']);
    }
}
