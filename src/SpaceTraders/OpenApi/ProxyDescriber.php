<?php

declare(strict_types=1);

namespace App\SpaceTraders\OpenApi;

use App\OpenApi\Describer\AuthenticationErrorResponseDescriber;
use App\SpaceTraders\Response\AugmentationSchema;
use App\SpaceTraders\Response\AugmentInterface;
use App\SpaceTraders\RouteHookInterface;
use Nelmio\ApiDocBundle\Describer\DescriberInterface;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use OpenApi\Annotations as OA;
use OpenApi\Context;
use OpenApi\Serializer;
use OpenApi\Undefined;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Documents a representative subset of the SpaceTraders-proxied routes (see
 * "Proxy design" in doc/spacetraders-proxy.md) by copying their path/parameters/
 * request/response schema straight out of SpaceTraders' own published spec
 * (vendored at resources/spacetraders/openapi.json, see doc/openapi.md) —
 * the schemas are never hand-transcribed, so the contract-fidelity rule in
 * doc/spacetraders-proxy.md ("the back does not add, remove or rename fields") holds
 * in the docs too.
 *
 * Any App\SpaceTraders\Hook that also implements
 * App\SpaceTraders\Response\AugmentInterface adds its extra top-level fields
 * to the matching operation's response schema automatically, via
 * describedAugmentation() — no edit needed here when a new one is added.
 */
#[Autoconfigure(tags: ['nelmio_api_doc.describer.default'])]
final class ProxyDescriber implements DescriberInterface
{
    private const string BASE_PATH = '/api/spacetraders';

    /**
     * The representative subset documented in this first pass (see the
     * "Pick which SpaceTraders operations are in scope" decision in
     * doc/openapi.md). Expand this list to document more operations.
     *
     * @var list<array{method: string, sourcePath: string}>
     */
    private const array OPERATIONS = [
        ['method' => 'get', 'sourcePath' => '/'],
        ['method' => 'post', 'sourcePath' => '/register'],
        ['method' => 'get', 'sourcePath' => '/my/ships'],
    ];

    /**
     * @var array<array-key, mixed>
     */
    private readonly array $spec;

    /**
     * @param iterable<RouteHookInterface> $hooks
     */
    public function __construct(
        #[Autowire('%kernel.project_dir%/resources/spacetraders/openapi.json')]
        string $specPath,
        #[AutowireIterator('app.space_traders_route_hook')]
        private readonly iterable $hooks,
    ) {
        $contents = file_get_contents($specPath);

        if (false === $contents) {
            throw new \RuntimeException(sprintf('Unable to read the vendored SpaceTraders OpenAPI spec at "%s".', $specPath));
        }

        /** @var array<array-key, mixed> $decoded */
        $decoded = json_decode($contents, true, flags: \JSON_THROW_ON_ERROR);
        $this->spec = $decoded;
    }

    public function describe(OA\OpenApi $api): void
    {
        $openApiSerializer = new Serializer();
        $augmentationSchemas = $this->augmentationSchemasByOperation();

        /** @var array<string, true> $referencedSchemaNames */
        $referencedSchemaNames = [];

        foreach (self::OPERATIONS as $operation) {
            $sourceOperation = $this->sourceOperation(
                $operation['sourcePath'],
                $operation['method']
            );

            if (null === $sourceOperation) {
                continue;
            }

            $this->collectSchemaRefs($sourceOperation, $referencedSchemaNames);

            $path = Util::getPath(
                $api,
                $this->targetPath($operation['sourcePath'])
            );
            $targetOperation = Util::getOperation(
                $path,
                $operation['method']
            );

            /** @var OA\Operation $deserialized */
            $deserialized = $openApiSerializer->deserialize(
                json_encode($sourceOperation, \JSON_THROW_ON_ERROR),
                $this->operationClass($operation['method']),
                $this->nestedContext()
            );

            $targetOperation->mergeProperties($deserialized);

            // The front authenticates every proxied call with this back's
            // JWT, never with a SpaceTraders token: SpaceTraders' own
            // security requirements describe the upstream hop, not this
            // contract. Request/response fields are left untouched.
            $targetOperation->security = [[AuthenticationErrorResponseDescriber::SECURITY_SCHEME => []]];

            if (!Undefined::isDefault($targetOperation->operationId)) {
                $targetOperation->operationId = 'spacetraders_'.$targetOperation->operationId;
            }

            $augmentationKey = $operation['method'].'_'.ltrim($operation['sourcePath'], '/');

            if (isset($augmentationSchemas[$augmentationKey])) {
                $this->mergeAugmentation(
                    $targetOperation,
                    $augmentationSchemas[$augmentationKey]
                );
            }
        }

        foreach (array_keys($referencedSchemaNames) as $name) {
            $this->copySchema(
                $api,
                $openApiSerializer,
                $name
            );
        }
    }

    /**
     * @return array<string, AugmentationSchema> keyed by "method /path"
     */
    private function augmentationSchemasByOperation(): array
    {
        $schemas = [];

        foreach ($this->hooks as $hook) {
            if (!$hook instanceof AugmentInterface) {
                continue;
            }

            $schema = $hook->describedAugmentation();
            $schemas[strtolower($schema->method).'_'.ltrim($schema->path, '/')] = $schema;
        }

        return $schemas;
    }

    /**
     * Mirrors how SpaceTradersProxyController receives {path}: the "/"
     * SpaceTraders path (server status) maps to the bare base path with no
     * trailing slash (see SpaceTradersProxyControllerTest), not
     * "/api/spacetraders/".
     */
    private function targetPath(string $sourcePath): string
    {
        return '/' === $sourcePath ? self::BASE_PATH : self::BASE_PATH.$sourcePath;
    }

    /**
     * OA\Operation is abstract; the Serializer needs the concrete
     * method-specific subclass to instantiate.
     *
     * @return class-string<OA\Operation>
     */
    private function operationClass(string $method): string
    {
        return match ($method) {
            'get' => OA\Get::class,
            'post' => OA\Post::class,
            'put' => OA\Put::class,
            'patch' => OA\Patch::class,
            'delete' => OA\Delete::class,
            'options' => OA\Options::class,
            'head' => OA\Head::class,
            'trace' => OA\Trace::class,
            default => throw new \InvalidArgumentException(sprintf('Unsupported HTTP method "%s".', $method)),
        };
    }

    /**
     * @return array<array-key, mixed>|null
     */
    private function sourceOperation(string $path, string $method): ?array
    {
        $paths = $this->spec['paths'] ?? null;

        if (!\is_array($paths)) {
            return null;
        }

        $pathItem = $paths[$path] ?? null;

        if (!\is_array($pathItem)) {
            return null;
        }

        $operation = $pathItem[$method] ?? null;

        return \is_array($operation) ? $operation : null;
    }

    /**
     * @return array<array-key, mixed>|null
     */
    private function schemaDefinition(string $name): ?array
    {
        $components = $this->spec['components'] ?? null;

        if (!\is_array($components)) {
            return null;
        }

        $schemas = $components['schemas'] ?? null;

        if (!\is_array($schemas)) {
            return null;
        }

        $schema = $schemas[$name] ?? null;

        return \is_array($schema) ? $schema : null;
    }

    private function mergeAugmentation(OA\Operation $operation, AugmentationSchema $schema): void
    {
        $bodySchema = $this->successResponseSchema($operation);

        if (null === $bodySchema) {
            return;
        }

        foreach ((new \ReflectionClass($schema->dataClass))->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            Util::getProperty($bodySchema, $property->getName())->type = $this->openApiType($property->getType());
        }
    }

    private function successResponseSchema(OA\Operation $operation): ?OA\Schema
    {
        if (Undefined::isDefault($operation->responses)) {
            return null;
        }

        foreach ($operation->responses as $response) {
            if (1 !== preg_match('/^2\d\d$/', (string) $response->response)) {
                continue;
            }

            $content = $response->content;

            if (!\is_array($content)) {
                continue;
            }

            $mediaType = $content['application/json'] ?? null;

            if (!$mediaType instanceof OA\MediaType || Undefined::isDefault($mediaType->schema)) {
                continue;
            }

            return $mediaType->schema;
        }

        return null;
    }

    private function openApiType(?\ReflectionType $type): string
    {
        if (!$type instanceof \ReflectionNamedType) {
            return 'object';
        }

        return match ($type->getName()) {
            'int' => 'integer',
            'float' => 'number',
            'bool' => 'boolean',
            'string' => 'string',
            default => 'object',
        };
    }

    /**
     * Walks a decoded spec fragment and records every schema name reachable
     * through a "#/components/schemas/..." $ref, transitively (a referenced
     * schema can itself reference others).
     *
     * @param array<string, true> $names
     */
    private function collectSchemaRefs(mixed $node, array &$names): void
    {
        if (!\is_array($node)) {
            return;
        }

        foreach ($node as $key => $value) {
            if ('$ref' === $key && \is_string($value) && str_starts_with($value, '#/components/schemas/')) {
                $name = substr($value, \strlen('#/components/schemas/'));

                if (!isset($names[$name])) {
                    $names[$name] = true;
                    $this->collectSchemaRefs($this->schemaDefinition($name), $names);
                }

                continue;
            }

            $this->collectSchemaRefs($value, $names);
        }
    }

    private function copySchema(OA\OpenApi $api, Serializer $serializer, string $name): void
    {
        $spaceTradersSchemaData = $this->schemaDefinition($name);

        if (null === $spaceTradersSchemaData) {
            return;
        }

        $target = Util::getSchema($api, $name);

        if (!Undefined::isDefault($target->type) || !Undefined::isDefault($target->properties)) {
            return;
        }

        /** @var OA\Schema $deserialized */
        $deserialized = $serializer->deserialize(
            json_encode($spaceTradersSchemaData, \JSON_THROW_ON_ERROR),
            OA\Schema::class,
            $this->nestedContext()
        );
        $target->mergeProperties($deserialized);
    }

    /**
     * Every node the Serializer builds under this context (operations,
     * responses, schemas, properties — the whole subtree, since
     * OpenApi\Serializer threads one Context through all of it) is marked
     * as nested. Without this, OpenApi\Analysis treats each one as a
     * top-level, reusable Components member during its own merge pass and
     * sweeps our per-operation responses/schemas into $api->components,
     * colliding whenever two unrelated operations happen to share a status
     * code or an unnamed inline schema.
     */
    private function nestedContext(): Context
    {
        return new Context(['nested' => true, 'generated' => true]);
    }
}
