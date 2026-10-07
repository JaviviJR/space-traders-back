<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\User;
use App\Security\AgentRegistrationService;
use App\Security\Dto\RegisterAgentDto;
use App\SpaceTraders\Exception\SpaceTradersRegistrationException;
use App\SpaceTraders\SpaceTradersClientInterface;
use App\Tests\SpaceTraders\FakeSpaceTradersClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AgentRegistrationServiceTest extends KernelTestCase
{
    public function testRegisteringAnAgentWhenSpaceTradersResponseHasNoDataThrows(): void
    {
        self::bootKernel();
        $user = $this->persistUser('agent-no-data@example.com');

        /** @var FakeSpaceTradersClient $fake */
        $fake = static::getContainer()->get(SpaceTradersClientInterface::class);
        $fake->willReturn([]);

        $service = static::getContainer()->get(AgentRegistrationService::class);

        $this->expectException(SpaceTradersRegistrationException::class);
        $this->expectExceptionMessage('SpaceTraders agent registration failed with status 0.');

        $service->registerAgent($user, new RegisterAgentDto('NO-DATA-AGENT', 'COSMIC'));
    }

    public function testRegisteringAnAgentWhenSpaceTradersResponseHasNoTokenThrows(): void
    {
        self::bootKernel();
        $user = $this->persistUser('agent-no-token@example.com');

        /** @var FakeSpaceTradersClient $fake */
        $fake = static::getContainer()->get(SpaceTradersClientInterface::class);
        $fake->willReturn(['data' => ['symbol' => 'NO-TOKEN-AGENT']]);

        $service = static::getContainer()->get(AgentRegistrationService::class);

        $this->expectException(SpaceTradersRegistrationException::class);

        $service->registerAgent($user, new RegisterAgentDto('NO-TOKEN-AGENT', 'COSMIC'));
    }

    private function persistUser(string $email): User
    {
        $user = new User($email, 'hashed-password');

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }
}
