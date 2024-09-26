<?php

declare(strict_types=1);

namespace Tests\Integration;

use GraphQL\Type\Schema;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Nuwave\Lighthouse\Schema\SchemaBuilder;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Nuwave\Lighthouse\Testing\UsesTestSchema;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;

abstract class AbstractIntegrationTestCase extends TestCase
{
    use MakesGraphQLRequests;
    use UsesTestSchema;
    use WithWorkbench;

    public const PLACEHOLDER_QUERY = /** @lang GraphQL */ 'type Query';

    protected function getEnvironmentSetUp($app): void
    {
        $config = $app->make(ConfigRepository::class);

        $config->set('lighthouse.schema_cache.enable', false);
    }

    protected function buildSchema(string $schema): Schema
    {
        $this->schema = self::PLACEHOLDER_QUERY . PHP_EOL . $schema;

        $schemaBuilder = $this->app->make(SchemaBuilder::class);

        return $schemaBuilder->schema();
    }
}
