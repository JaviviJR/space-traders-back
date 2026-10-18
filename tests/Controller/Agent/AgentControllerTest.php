<?php

declare(strict_types=1);

namespace App\Tests\Controller\Agent;

use App\Entity\User;
use App\Factory\AgentFactory;
use App\Factory\UserFactory;
use App\Repository\UserRepository;
use App\Tests\Support\AuthenticatesClient;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\HttpOptions;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Attribute\ResetDatabase;

#[ResetDatabase]
final class AgentControllerTest extends WebTestCase
{
    use AuthenticatesClient;
    use HasBrowser;

    public function testListingAgentsOnlyReturnsTheCurrentUsersAgents(): void
    {
        // A single browser is reused for both users: JWT auth is carried
        // explicitly per request via the Authorization header, not through
        // the client's cookie jar, so one KernelBrowser is enough — and
        // WebTestCase only supports booting one kernel per test anyway.
        $browser = $this->browser();

        UserFactory::createOne(['email' => 'agent-owner@example.com']);
        $this->login($browser, 'agent-owner@example.com');
        AgentFactory::createOne(['user' => $this->userByEmail('agent-owner@example.com'), 'symbol' => 'OWNER-AGENT']);

        UserFactory::createOne(['email' => 'agent-other@example.com']);
        $otherToken = $this->login($browser, 'agent-other@example.com');
        AgentFactory::createOne(['user' => $this->userByEmail('agent-other@example.com'), 'symbol' => 'OTHER-AGENT']);

        $browser
            ->get('/api/agents', HttpOptions::create()->withHeader('Authorization', 'Bearer '.$otherToken))
            ->assertSuccessful()
        ;

        /** @var array{data: list<array{symbol: string}>} $data */
        $data = $browser->json()->decoded();
        self::assertCount(1, $data['data']);
        self::assertSame('OTHER-AGENT', $data['data'][0]['symbol']);
    }

    public function testListingAgentsReturnsAllAgentsForTheCurrentUserInOrder(): void
    {
        $browser = $this->browser();

        UserFactory::createOne(['email' => 'multi-agent-owner@example.com']);
        $token = $this->login($browser, 'multi-agent-owner@example.com');
        $user = $this->userByEmail('multi-agent-owner@example.com');
        AgentFactory::createOne(['user' => $user, 'symbol' => 'AGENT-ONE']);
        AgentFactory::createOne(['user' => $user, 'symbol' => 'AGENT-TWO']);
        AgentFactory::createOne(['user' => $user, 'symbol' => 'AGENT-THREE']);

        $browser
            ->get('/api/agents', HttpOptions::create()->withHeader('Authorization', 'Bearer '.$token))
            ->assertSuccessful()
        ;

        /** @var array{data: list<array{symbol: string}>} $data */
        $data = $browser->json()->decoded();

        self::assertCount(3, $data['data']);
        self::assertSame(
            ['AGENT-ONE', 'AGENT-TWO', 'AGENT-THREE'],
            array_column($data['data'], 'symbol'),
        );
    }

    private function userByEmail(string $email): User
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findByEmail($email);
        self::assertNotNull($user);

        return $user;
    }
}
