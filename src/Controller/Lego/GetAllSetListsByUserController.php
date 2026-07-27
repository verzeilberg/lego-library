<?php

namespace App\Controller\Lego;

use App\Dto\Request\Lego\SetListsRequest;
use App\Repository\Lego\SetListRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

#[AsController]
class GetAllSetListsByUserController extends AbstractController
{
    public function __construct(
        private readonly SetListRepository $setListRepository,
        private readonly UploaderHelper    $uploaderHelper,
        private readonly Security          $security,
    ) {}

    public function __invoke(): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $userData = $user->getUserData();

        $ownedLists = $this->setListRepository->findBy(
            ['userData' => $userData],
            ['publicationDate' => 'DESC']
        );

        $sharedLists = $this->setListRepository->findBySharedWithUser($userData);

        $allLists = array_merge($ownedLists, $sharedLists);
        $seen = [];
        $result = [];
        foreach ($allLists as $set) {
            $key = $set->getId()->toString();
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $path = $this->uploaderHelper->asset($set, 'file');
                $isShared = $set->getUserData() !== $userData;
                $owner = null;
                if ($isShared && $set->getUserData() !== null) {
                    $ownerData = $set->getUserData();
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
                $result[] = new SetListsRequest(
                    $set->getId(),
                    $set->getTitle(),
                    $set->getDescription(),
                    $set->isPublic(),
                    false,
                    $path,
                    $owner,
                    $set->getParentList()?->getId()?->toString(),
                    $isShared,
                );
            }
        }

        return new JsonResponse($result, Response::HTTP_OK);
    }
}
