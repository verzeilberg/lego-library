<?php

namespace App\Controller\Lego;

use App\Repository\Lego\SetListRepository;
use App\Service\Lego\BoardAccessService;
use App\Service\Lego\MissingItemsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns all missing parts and minifigs of every set in a board, including the sets
 * in all (visible) sub-boards, recursively.
 */
class GetBoardMissingPartsController extends AbstractController
{
    public function __construct(
        private readonly SetListRepository   $setListRepository,
        private readonly Security            $security,
        private readonly BoardAccessService  $boardAccessService,
        private readonly MissingItemsService $missingItemsService,
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

        // board id => path relative to the requested board ("Sub > Sub sub")
        $boardPaths = $this->missingItemsService->collectBoardPaths($setList, $userData);
        $userSetParts = $this->missingItemsService->findMissingParts(array_keys($boardPaths));

        $parts = [];
        foreach ($userSetParts as $userSetPart) {
            $setListSet = $userSetPart->getSetListSet();
            $set = $setListSet->getSet();
            $setPart = $userSetPart->getSetPart();
            $partColor = $setPart->getPartColor();

            $imgUrl = $partColor->getImgUrl();

            $parts[] = [
                'board'               => $boardPaths[(string) $setListSet->getSetList()->getId()],
                'setNumber'           => $set->getNumber(),
                'setName'             => $set->getName(),
                'partNumber'          => $partColor->getPart()->getPartNumber(),
                'name'                => $partColor->getPart()->getName(),
                'colorId'             => $partColor->getColor()->getId(),
                'colorName'           => $partColor->getColor()->getName(),
                'isSpare'             => $setPart->isSpare(),
                'quantity'            => $setPart->getQuantity(),
                'missingQuantity'     => $userSetPart->getMissingQuantity(),
                'damagedQuantity'     => $userSetPart->getDamagedQuantity(),
                'discolouredQuantity' => $userSetPart->getDiscolouredQuantity(),
                'imageUrl'            => ($imgUrl && !str_starts_with($imgUrl, 'http'))
                    ? '/media/lego/' . $imgUrl
                    : $imgUrl,
            ];
        }

        $userSetMinifigs = $this->missingItemsService->findMissingMinifigs(array_keys($boardPaths));

        $minifigs = [];
        foreach ($userSetMinifigs as $userSetMinifig) {
            $setListSet = $userSetMinifig->getSetListSet();
            $set = $setListSet->getSet();
            $setMinifig = $userSetMinifig->getSetMinifig();
            $minifig = $setMinifig->getMinifig();

            $minifigs[] = [
                'board'           => $boardPaths[(string) $setListSet->getSetList()->getId()],
                'setNumber'       => $set->getNumber(),
                'setName'         => $set->getName(),
                'minifigNumber'   => $minifig->getSetNumId(),
                'name'            => $minifig->getName(),
                'quantity'        => $setMinifig->getQuantity(),
                'missingQuantity' => $userSetMinifig->getMissingQuantity(),
                'imageUrl'        => $minifig->getImageUrl(),
            ];
        }

        return new JsonResponse([
            'board'    => $setList->getTitle(),
            'parts'    => $parts,
            'minifigs' => $minifigs,
        ]);
    }
}
