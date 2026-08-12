<?php

namespace App\Controller\Lego;

use App\Dto\Request\Lego\SetListsRequest;
use App\Repository\Lego\SetListRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class GetSetListsByUserController extends AbstractController
{

    public function __construct(
        private readonly SetListRepository  $setListRepository,
        private readonly UploaderHelper     $uploaderHelper,
        private readonly Security           $security,
    )
    {
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->security->getUser();

        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $limit  = max(1, (int) $request->query->get('limit', 10));
        $page   = max(1, (int) $request->query->get('page', 1));
        $offset = ($page - 1) * $limit;

        $userDataId = $user->getUserData()->getId();
        $userData = $user->getUserData();

        // Fetch own top-level model lists (parentList IS NULL)
        $ownedLists = $this->setListRepository->findBy(
            ['userData' => $userDataId, 'parentList' => null],
            ['publicationDate' => 'DESC']
        );

        // Fetch top-level boards shared with this user
        $sharedLists = $this->setListRepository->findBySharedWithUser($userData, true);

        // Merge and deduplicate by id
        $allLists = array_merge($ownedLists, $sharedLists);
        $seen = [];
        $setListsByUser = [];
        foreach ($allLists as $set) {
            if (!isset($seen[$set->getId()->toString()])) {
                $seen[$set->getId()->toString()] = true;
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
                $setListsByUser[] = new SetListsRequest(
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


        $paginated = array_slice($setListsByUser, $offset, $limit);

        return new JsonResponse($paginated, Response::HTTP_OK);
    }
}
