<?php

declare(strict_types=1);

namespace App\Controller\Agent;

use App\Agent\Dto\AgentResponseData;
use App\Entity\Agent;
use App\Entity\User;
use App\Http\EnvelopeResponseFactory;
use App\Repository\AgentRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
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
    #[OA\Get(operationId: 'listAgents')]
    #[OA\Tag(name: 'Account')]
    #[OA\Response(
        response: 200,
        description: 'List of agents belonging to the authenticated user',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: AgentResponseData::class)),
                ),
            ],
        ),
    )]
    public function list(#[CurrentUser] User $user): JsonResponse
    {
        $agents = array_map(
            fn (Agent $agent): AgentResponseData => $this->toResponseData($agent),
            $this->agentRepository->findAllForUser($user),
        );

        return $this->envelope->success($agents);
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
