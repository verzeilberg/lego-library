<?php

namespace App\Controller\Lego;

use App\Entity\Lego\SetList;
use App\Entity\Lego\SetMinifig;
use App\Entity\Lego\UserSetMinifig;
use App\Repository\Lego\SetListSetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Sets how many of a minifig are missing for a set within a board.
 */
#[AsController]
class UpdateSetMinifigStateController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SetListSetRepository   $setListSetRepository,
    ) {}

    public function __invoke(string $listId, string $setNumber, int $setMinifigId, Request $request, Security $security): JsonResponse
    {
        $user = $security->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $setList = $this->entityManager->find(SetList::class, $listId);
        if (!$setList) {
            return new JsonResponse(['message' => 'Board not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$setList->canContribute($user->getUserData())) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        $missingQuantity = $data['missingQuantity'] ?? null;
        if (!is_int($missingQuantity) || $missingQuantity < 0) {
            return new JsonResponse(['message' => 'missingQuantity must be a non-negative integer'], Response::HTTP_BAD_REQUEST);
        }

        $setListSet = $this->setListSetRepository->findBySetNumberAndListId($setNumber, $listId);
        if (!$setListSet) {
            return new JsonResponse(['message' => 'Set not found in board'], Response::HTTP_NOT_FOUND);
        }

        $setMinifig = $this->entityManager->find(SetMinifig::class, $setMinifigId);
        if (!$setMinifig || $setMinifig->getSet()?->getNumber() !== $setListSet->getSet()->getNumber()) {
            return new JsonResponse(['message' => 'Minifig not found in this set'], Response::HTTP_NOT_FOUND);
        }

        $state = $setListSet->getMinifigStates()->findFirst(
            fn(int $key, UserSetMinifig $s) => $s->getSetMinifig()->getId() === $setMinifig->getId()
        );

        if ($missingQuantity === 0) {
            if ($state !== null) {
                $setListSet->removeMinifigState($state);
            }
        } else {
            if ($state === null) {
                $state = (new UserSetMinifig())->setSetMinifig($setMinifig);
                $setListSet->addMinifigState($state);
            }
            $state->setMissingQuantity($missingQuantity);
        }

        $setListSet->setComplete(!$setListSet->hasDefectiveParts() && !$setListSet->hasMissingMinifigs());
        $this->entityManager->flush();

        return new JsonResponse([
            'missingQuantity' => $missingQuantity === 0 ? 0 : $state->getMissingQuantity(),
            'isComplete'      => $setListSet->isComplete(),
        ], Response::HTTP_OK);
    }
}
