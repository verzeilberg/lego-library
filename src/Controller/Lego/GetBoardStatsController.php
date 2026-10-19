<?php

namespace App\Controller\Lego;

use App\Repository\Lego\SetListRepository;
use App\Service\Lego\BoardAccessService;
use App\Service\Lego\CompletenessService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Completeness stats of a board, including all (visible) sub-boards.
 */
class GetBoardStatsController extends AbstractController
{
    public function __construct(
        private readonly SetListRepository   $setListRepository,
        private readonly Security            $security,
        private readonly BoardAccessService  $boardAccessService,
        private readonly CompletenessService $completenessService,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $setList = $this->setListRepository->find($id);
        if (!$setList) {
            return new JsonResponse(['message' => 'Set list not found'], Response::HTTP_NOT_FOUND);
        }

        $userData = $user->getUserData();
        if (!$this->boardAccessService->canView($setList, $userData)) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse($this->completenessService->boardStats($setList, $userData));
    }
}
