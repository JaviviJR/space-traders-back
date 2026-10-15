<?php

declare(strict_types=1);

namespace App\Tests\SpaceTraders\OpenApi;

use App\SpaceTraders\Hook\Dto\RegisterAgentAugmentationData;
use App\SpaceTraders\OpenApi\ProxyDescriber;
use App\SpaceTraders\Response\AugmentationSchema;
use App\Tests\SpaceTraders\Proxy\FakeRouteHook;
use OpenApi\Annotations as OA;
use OpenApi\Context;
use PHPUnit\Framework\TestCase;

final class ProxyDescriberTest extends TestCase
{
    public function testDocumentsTheRepresentativeOperationsUnderTheProxyBasePath(): void
    {
        $api = $this->describe([]);

        $paths = array_map(static fn (OA\PathItem $path): string => $path->path, $api->paths);

        self::assertContains('/api/spacetraders', $paths);
        self::assertContains('/api/spacetraders/register', $paths);
        self::assertContains('/api/spacetraders/my/ships', $paths);
    }

    public function testCopiesTheStatusResponseSchemaVerbatim(): void
    {
        $api = $this->describe([]);

        $status = $this->operation($api, '/api/spacetraders', 'get');
        $schema = $this->jsonSchema($status, '200');

        self::assertContains('status', $this->propertyNames($schema));
    }

    public function testMergesAHooksAugmentationAsASiblingOfData(): void
    {
        $hook = new FakeRouteHook('register');
        $hook->willDescribeAugmentationWith(new AugmentationSchema('POST', 'register', RegisterAgentAugmentationData::class));

        $api = $this->describe([$hook]);

        $register = $this->operation($api, '/api/spacetraders/register', 'post');
        $schema = $this->jsonSchema($register, '201');
        $properties = $this->propertyNames($schema);

        self::assertContains('data', $properties);
        self::assertContains('agentId', $properties);
        self::assertSame('integer', $this->property($schema, 'agentId')->type);
    }

    public function testCopiesSchemasReferencedByTheDocumentedOperations(): void
    {
        $api = $this->describe([]);

        $names = array_map(static fn (OA\Schema $schema): string => $schema->schema, $api->components->schemas);

        self::assertContains('Agent', $names);
        self::assertContains('Ship', $names);
    }

    /**
     * @param list<FakeRouteHook> $hooks
     */
    private function describe(array $hooks): OA\OpenApi
    {
        $specPath = \dirname(__DIR__, 3).'/resources/spacetraders/openapi.json';
        $describer = new ProxyDescriber($specPath, $hooks);

        $api = new OA\OpenApi(['_context' => new Context(['generated' => true])]);
        $api->openapi = '3.0.0';
        $describer->describe($api);

        return $api;
    }

    private function operation(OA\OpenApi $api, string $path, string $method): OA\Operation
    {
        foreach ($api->paths as $pathItem) {
            if ($path === $pathItem->path) {
                $operation = $pathItem->{$method};
                self::assertInstanceOf(OA\Operation::class, $operation);

                return $operation;
            }
        }

        self::fail(sprintf('No path "%s" was documented.', $path));
    }

    private function jsonSchema(OA\Operation $operation, string $status): OA\Schema
    {
        $responses = $operation->responses;
        self::assertArrayHasKey($status, $responses);

        $response = $responses[$status];

        $content = $response->content;
        self::assertIsArray($content);
        self::assertArrayHasKey('application/json', $content);

        $mediaType = $content['application/json'];
        self::assertInstanceOf(OA\MediaType::class, $mediaType);

        return $mediaType->schema;
    }

    private function property(OA\Schema $schema, string $name): OA\Property
    {
        foreach ($schema->properties as $property) {
            if ($name === $property->property) {
                return $property;
            }
        }

        self::fail(sprintf('No property "%s" on the schema.', $name));
    }

    /**
     * $schema->properties is keyed by property name only for entries the
     * Serializer deserialized straight from JSON; one Util::getProperty()
     * appends (as our augmentation merge does) gets a plain numeric key
     * instead, so array_keys() can't be trusted here — read the ->property
     * field off each value instead.
     *
     * @return list<string>
     */
    private function propertyNames(OA\Schema $schema): array
    {
        return array_map(static fn (OA\Property $property): string => $property->property, $schema->properties);
    }
}
