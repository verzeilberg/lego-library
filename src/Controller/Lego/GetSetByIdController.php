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

class GetSetByIdController extends AbstractController
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

        $id = $request->get('id');
        $setList = $this->setListRepository->find($id);
        if ($setList->getUserData()->getOwner() === $user) {
            $path = $this->uploaderHelper->asset($setList, 'file');
            $setListRequest = new SetListsRequest($setList->getId(), $setList->getTitle(), $setList->getDescription(), $setList->isPublic(), false, $path);

            return new JsonResponse($setListRequest, Response::HTTP_OK);

        } else {
            return $this->json(['result' => 'Set list fetched unsuccessfully, user is not the owner of the model list'], Response::HTTP_UNAUTHORIZED);
        }
    }
}
