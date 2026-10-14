<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\SpaceTraders\SpaceTradersGatewayInterface;
use App\Tests\SpaceTraders\FakeSpaceTradersGateway;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Shared helper for functional tests that stage a SpaceTraders response.
 * Fetched by interface, not by the fake's own class: PHPStan validates
 * getContainer()->get() against the dev container (see phpstan.dist.neon),
 * where only the interface is registered — the when@test rebind to
 * FakeSpaceTradersGateway in config/services.yaml doesn't exist there, so
 * PHPStan has no way to know this call ever returns the fake.
 *
 * @method static \Symfony\Component\DependencyInjection\ContainerInterface getContainer()
 * @method static void                                                      assertInstanceOf(string $expected, mixed $actual, string $message = '')
 */
trait UsesFakeSpaceTradersGateway
{
    /**
     * The client reboots the kernel (and its container) between requests by
     * default, which would hand later requests a fresh FakeSpaceTradersGateway
     * instance and silently drop whatever response the caller stages on the
     * one returned here. Disabling it is this method's job, not login()'s:
     * the need is tied to keeping this fake alive across requests, not to
     * authenticating.
     */
    private function fakeSpaceTradersGateway(KernelBrowser $client): FakeSpaceTradersGateway
    {
        $client->disableReboot();

        $gateway = static::getContainer()->get(SpaceTradersGatewayInterface::class);

        // PHPStan only sees the dev container, where this interface resolves
        // to the real gateway; the when@test rebind to the fake is invisible
        // to it, so it treats this as impossible instead of test-only truth.
        // @phpstan-ignore staticMethod.impossibleType
        self::assertInstanceOf(FakeSpaceTradersGateway::class, $gateway);

        return $gateway;
    }
}
