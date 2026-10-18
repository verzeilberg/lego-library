<?php

namespace App\Controller\Lego;

use App\Entity\Lego\SetList;
use App\Repository\Lego\SetListSetRepository;
use App\Repository\Lego\SetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class UpdateSetInstructionsController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SetListSetRepository   $setListSetRepository,
        private readonly SetRepository          $setRepository,
    ) {}

    public function __invoke(string $listId, string $setNumber, Request $request, Security $security): JsonResponse
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
        $hasInstructions = $data['hasInstructions'] ?? null;
        if (!is_bool($hasInstructions)) {
            return new JsonResponse(['message' => 'hasInstructions must be a boolean'], Response::HTTP_BAD_REQUEST);
        }

        $set = $this->setRepository->find($setNumber);
        if (!$set) {
            return new JsonResponse(['message' => 'Set not found'], Response::HTTP_NOT_FOUND);
        }

        $setListSet = $this->setListSetRepository->findOneBy([
            'set' => $set,
            'setList' => $setList,
        ]);

        if (!$setListSet) {
            return new JsonResponse(['message' => 'Set not found in board'], Response::HTTP_NOT_FOUND);
        }

        $setListSet->setInstructions($hasInstructions);
        $this->entityManager->flush();

        return new JsonResponse(['hasInstructions' => $setListSet->hasInstructions()], Response::HTTP_OK);
    }
}
