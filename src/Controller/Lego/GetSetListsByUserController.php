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

        $userDataId = $user->getUserData()->getId();

        // Fetch only top-level model lists (parentList IS NULL)
        $setListsByUser = $this->setListRepository->findBy(
            ['userData' => $userDataId, 'parentList' => null],
            ['publicationDate' => 'DESC']
        );

        $setListsByUser = array_map(function ($set) {
            $path = $this->uploaderHelper->asset($set, 'file');
            return new SetListsRequest($set->getId(), $set->getTitle(), $set->getDescription(), $set->isPublic(), false, $path);
        }, $setListsByUser);


        return new JsonResponse($setListsByUser, Response::HTTP_OK);
    }
}
