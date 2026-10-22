<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Shared helpers for tests that check the generated OpenAPI spec as the
 * front's codegen reads it. For KernelTestCase subclasses: it boots the
 * kernel to run the dump command.
 */
trait DumpsOpenApiSpec
{
    private function dumpedSpec(): mixed
    {
        $tester = new CommandTester((new Application(self::bootKernel()))->find('nelmio:apidoc:dump'));
        $tester->execute(['--format' => 'json']);
        $tester->assertCommandIsSuccessful();

        return json_decode($tester->getDisplay(), true, flags: \JSON_THROW_ON_ERROR);
    }

    /**
     * Walks the decoded spec down $keys, or null as soon as one is missing.
     */
    private function at(mixed $node, string|int ...$keys): mixed
    {
        foreach ($keys as $key) {
            if (!\is_array($node) || !\array_key_exists($key, $node)) {
                return null;
            }

            $node = $node[$key];
        }

        return $node;
    }
}
