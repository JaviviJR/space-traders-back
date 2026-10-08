<?php

declare(strict_types=1);

namespace App\Tests\Controller\Agent;

use App\Entity\Agent;
use App\Factory\UserFactory;
use App\Repository\UserRepository;
use App\Tests\Support\AuthenticatesClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AgentControllerTest extends WebTestCase
{
    use AuthenticatesClient;

    public function testListingAgentsOnlyReturnsTheCurrentUsersAgents(): void
    {
        // A single client is reused for both users: JWT auth is carried
        // explicitly per request via the Authorization header, not through
        // the client's cookie jar, so one KernelBrowser is enough — and
        // WebTestCase only supports booting one kernel per test anyway.
        $client = static::createClient();

        UserFactory::createOne(['email' => 'agent-owner@example.com']);
        $this->login($client, 'agent-owner@example.com');
        $this->persistAgentFor('agent-owner@example.com', 'OWNER-AGENT');

        UserFactory::createOne(['email' => 'agent-other@example.com']);
        $otherToken = $this->login($client, 'agent-other@example.com');
        $this->persistAgentFor('agent-other@example.com', 'OTHER-AGENT');

        $client->jsonRequest('GET', '/api/agents', [], ['HTTP_AUTHORIZATION' => 'Bearer '.$otherToken]);
        self::assertResponseIsSuccessful();

        /** @var array{data: list<array{symbol: string}>} $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertCount(1, $data['data']);
        self::assertSame('OTHER-AGENT', $data['data'][0]['symbol']);
    }

    private function persistAgentFor(string $userEmail, string $symbol): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findByEmail($userEmail);
        self::assertNotNull($user);

        $entityManager->persist(new Agent($user, $symbol, 'COSMIC', 'fake-encrypted-token'));
        $entityManager->flush();
    }
}
