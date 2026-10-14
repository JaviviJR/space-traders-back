<?php

declare(strict_types=1);

namespace App\Controller\Agent;

use App\Agent\Dto\AgentResponseData;
use App\Entity\Agent;
use App\Entity\User;
use App\Http\EnvelopeResponseFactory;
use App\Http\Meta;
use App\Repository\AgentRepository;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class AgentController extends AbstractController
{
    public function __construct(
        private readonly AgentRepository $agentRepository,
        private readonly EnvelopeResponseFactory $envelope,
    ) {
    }

    #[Route('/api/agents', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Tag(name: 'Account')]
    public function list(#[CurrentUser] User $user): JsonResponse
    {
        $agents = array_map(
            fn (Agent $agent): AgentResponseData => $this->toResponseData($agent),
            $this->agentRepository->findAllForUser($user),
        );

        $total = \count($agents);

        return $this->envelope->
                success($agents, meta: new Meta($total, 1, $total));
    }

    private function toResponseData(Agent $agent): AgentResponseData
    {
        return new AgentResponseData(
            $agent->getId(),
            $agent->getSymbol(),
            $agent->getFaction(),
            $agent->getCreatedAt()?->format(\DATE_ATOM),
        );
    }
}
