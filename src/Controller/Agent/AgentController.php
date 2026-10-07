<?php

declare(strict_types=1);

namespace App\Controller\Agent;

use App\Entity\Agent;
use App\Entity\User;
use App\Http\EnvelopeResponseFactory;
use App\Repository\AgentRepository;
use App\Security\AgentRegistrationService;
use App\Security\Dto\RegisterAgentDto;
use App\SpaceTraders\Exception\SpaceTradersRegistrationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class AgentController extends AbstractController
{
    public function __construct(
        private readonly AgentRegistrationService $agentRegistrationService,
        private readonly AgentRepository $agentRepository,
        private readonly EnvelopeResponseFactory $envelope,
    ) {
    }

    #[Route('/api/agents', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(#[MapRequestPayload] RegisterAgentDto $dto, #[CurrentUser] User $user): JsonResponse
    {
        try {
            $agent = $this->agentRegistrationService->registerAgent($user, $dto);
        } catch (SpaceTradersRegistrationException $exception) {
            throw new UnprocessableEntityHttpException($exception->getMessage(), $exception);
        }

        return $this->envelope->success($this->toArray($agent), 201);
    }

    #[Route('/api/agents', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function list(#[CurrentUser] User $user): JsonResponse
    {
        $agents = array_map(
            fn (Agent $agent): array => $this->toArray($agent),
            $this->agentRepository->findAllForUser($user),
        );

        return $this->envelope->success($agents);
    }

    /**
     * @return array{id: ?int, symbol: string, faction: string, createdAt: ?string}
     */
    private function toArray(Agent $agent): array
    {
        return [
            'id' => $agent->getId(),
            'symbol' => $agent->getSymbol(),
            'faction' => $agent->getFaction(),
            'createdAt' => $agent->getCreatedAt()?->format(\DATE_ATOM),
        ];
    }
}
