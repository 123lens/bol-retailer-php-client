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

    public function testMergeKeepsBothOperationsWhenOperationIdsDiffer(): void
    {
        $base = new SwaggerSpecs([
            'paths' => [
                '/offers' => ['post' => ['operationId' => 'post-offer']],
            ],
            'components' => ['schemas' => []],
        ]);

        $other = new SwaggerSpecs([
            'paths' => [
                '/offers' => ['post' => ['operationId' => 'create-offer']],
            ],
            'components' => ['schemas' => []],
        ]);

        $merged = $base->merge($other)->getSpecs();

        // v10 stays on original path
        $this->assertEquals('post-offer', $merged['paths']['/offers']['post']['operationId']);

        // v11 goes to aliased path
        $this->assertArrayHasKey('/offers#create-offer', $merged['paths']);
        $this->assertEquals('create-offer', $merged['paths']['/offers#create-offer']['post']['operationId']);
    }

    public function testMergeSkipsDuplicateWhenOperationIdsMatch(): void
    {
        $base = new SwaggerSpecs([
            'paths' => [
                '/offers/{id}' => ['get' => ['operationId' => 'get-offer', 'source' => 'v10']],
            ],
            'components' => ['schemas' => []],
        ]);

        $other = new SwaggerSpecs([
            'paths' => [
                '/offers/{id}' => ['get' => ['operationId' => 'get-offer', 'source' => 'v11']],
            ],
            'components' => ['schemas' => []],
        ]);

        $merged = $base->merge($other)->getSpecs();

        // v10 version is kept
        $this->assertEquals('v10', $merged['paths']['/offers/{id}']['get']['source']);

        // No aliased path created
        $this->assertArrayNotHasKey('/offers/{id}#get-offer', $merged['paths']);
    }

    public function testMergeCombinesNewAndConflictingMethodsOnSamePath(): void
    {
        $base = new SwaggerSpecs([
            'paths' => [
                '/offers/{id}' => [
                    'get' => ['operationId' => 'get-offer'],
                    'put' => ['operationId' => 'put-offer'],
                    'delete' => ['operationId' => 'delete-offer'],
                ],
            ],
            'components' => ['schemas' => []],
        ]);

        $other = new SwaggerSpecs([
            'paths' => [
                '/offers/{id}' => [
                    'get' => ['operationId' => 'get-offer'],     // same operationId → skip
                    'delete' => ['operationId' => 'delete-offer'], // same operationId → skip
                    'patch' => ['operationId' => 'update-offer'],  // new method → add
                ],
            ],
            'components' => ['schemas' => []],
        ]);

        $merged = $base->merge($other)->getSpecs();

        // v10 methods preserved
        $this->assertArrayHasKey('get', $merged['paths']['/offers/{id}']);
        $this->assertArrayHasKey('put', $merged['paths']['/offers/{id}']);
        $this->assertArrayHasKey('delete', $merged['paths']['/offers/{id}']);

        // v11 new method added
        $this->assertArrayHasKey('patch', $merged['paths']['/offers/{id}']);
        $this->assertEquals('update-offer', $merged['paths']['/offers/{id}']['patch']['operationId']);

        // No aliased paths (operationIds matched for get/delete)
        $this->assertArrayNotHasKey('/offers/{id}#get-offer', $merged['paths']);
        $this->assertArrayNotHasKey('/offers/{id}#delete-offer', $merged['paths']);
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
