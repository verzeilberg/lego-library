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

class GetSetListsPublic extends AbstractController
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

        $setLists = $this->setListRepository->findPublicExcludingUser(
            $user->getUserData()->getId(),
            $limit,
            $offset
        );

        $setLists = array_map(function ($set) {
            $path     = $this->uploaderHelper->asset($set, 'file');
            $userData = $set->getUserData();
            $owner    = null;

            if ($userData !== null) {
                $profilePicture = $userData->getFilePath()
                    ? $this->uploaderHelper->asset($userData, 'file')
                    : null;

                $owner = [
                    'id'             => $userData->getId(),
                    'userName'       => $userData->getUserName(),
                    'profilePicture' => $profilePicture,
                    'geslacht'       => $userData->getGeslacht()?->value,
                ];
            }

            return new SetListsRequest($set->getId(), $set->getTitle(), $set->getDescription(), $set->isPublic(), false, $path, $owner, $set->getParentList()?->getId()?->toString());
        }, $setLists);


        return new JsonResponse($setLists, Response::HTTP_OK);
    }
}
