<?php

namespace App\Controller\Lego;

use App\Dto\Request\Lego\SetListsRequest;
use App\Repository\Lego\SetListRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class GetSetListByIdController extends AbstractController
{
    public function __construct(
        private readonly SetListRepository $setListRepository,
        private readonly UploaderHelper    $uploaderHelper,
        private readonly Security          $security,
    ) {}

    #[Route('/api/set-list/{id}', name: 'api_set_list_by_id', methods: ['GET'])]
    public function __invoke(string $id): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_UNAUTHORIZED);
        }

        $setList = $this->setListRepository->find($id);
        if (!$setList) {
            return new JsonResponse(['message' => 'Set list not found'], Response::HTTP_NOT_FOUND);
        }

        $userData = $user->getUserData();
        $isShared = $setList->getUserData() !== $userData;
        $ownerData = $setList->getUserData();

        // Include sharedWith list so the ShareModal can manage sharing (owner or shared user)
        $sharedWith = $setList->getSharedWith()->map(fn($ud) => $ud->getId())->getValues();

        $owner = null;
        if ($ownerData !== null) {
            $owner = [
                'id'             => $ownerData->getId(),
                'userName'       => $ownerData->getUserName(),
                'firstName'      => $ownerData->getFirstName(),
                'lastName'       => $ownerData->getLastName(),
                'profilePicture' => $ownerData->getFilePath()
                    ? $this->uploaderHelper->asset($ownerData, 'file')
                    : null,
            ];
        }

        $path = $this->uploaderHelper->asset($setList, 'file');

        return new JsonResponse(new SetListsRequest(
            $setList->getId(),
            $setList->getTitle(),
            $setList->getDescription(),
            $setList->isPublic(),
            false,
            $path,
            $owner,
            $setList->getParentList()?->getId()?->toString(),
            $isShared,
            $sharedWith,
        ));
    }
}
