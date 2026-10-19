<?php

namespace App\Controller\Lego;

use App\Repository\Lego\SetListRepository;
use App\Repository\Lego\SetListSetRepository;
use App\Service\BrickLink\BrickLinkWantedListService;
use App\Service\Lego\BoardAccessService;
use App\Service\Lego\MissingItemsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * BrickLink wanted list (XML) of the missing and broken parts of a single set in a board.
 */
class GetSetBrickLinkWantedListController extends AbstractController
{
    public function __construct(
        private readonly SetListRepository          $setListRepository,
        private readonly SetListSetRepository       $setListSetRepository,
        private readonly Security                   $security,
        private readonly BoardAccessService         $boardAccessService,
        private readonly MissingItemsService        $missingItemsService,
        private readonly BrickLinkWantedListService $wantedListService,
    ) {}

    public function __invoke(string $listId, string $setNumber): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $setList = $this->setListRepository->find($listId);
        if (!$setList) {
            return new JsonResponse(['message' => 'Set list not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->boardAccessService->canView($setList, $user->getUserData())) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        $setListSet = $this->setListSetRepository->findBySetNumberAndListId($setNumber, $listId);
        if (!$setListSet) {
            return new JsonResponse(['message' => 'Set not found in board'], Response::HTTP_NOT_FOUND);
        }

        try {
            $wantedList = $this->wantedListService->build(
                $this->missingItemsService->findMissingParts(setListSet: $setListSet, includeBroken: true),
                $this->missingItemsService->findMissingMinifigs(setListSet: $setListSet),
            );
        } catch (\Throwable $e) {
            return new JsonResponse(['message' => 'Could not fetch BrickLink ids from Rebrickable: ' . $e->getMessage()], Response::HTTP_BAD_GATEWAY);
        }

        return new JsonResponse(['name' => $setListSet->getSet()->getNumber()] + $wantedList);
    }
}
