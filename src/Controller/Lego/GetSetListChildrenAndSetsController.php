<?php

namespace App\Controller\Lego;

use App\Dto\Request\Lego\SetListsRequest;
use App\Repository\Lego\SetListRepository;
use App\Service\Lego\SetListService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class GetSetListChildrenAndSetsController extends AbstractController
{

    public function __construct(
        private readonly SetListRepository  $setListRepository,
        private readonly SetListService $setListService,
        private readonly Security           $security,
    )
    {
    }

    /**
     * @param string $id
     * @return JsonResponse
     */
    public function __invoke( string $id,): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $setList = $this->setListRepository->find($id);
        if (!$setList) {
            return new JsonResponse(['message' => 'Set list not found'], Response::HTTP_NOT_FOUND);
        }

        $childLists = $setList->getChildLists();
        $setLinks = $setList->getSetLinks();

        $setLists = $this->setListService->getCombinedListWithSets(
            $childLists,
            $setLinks
        );

        return $this->json($setLists, 200, [], ['groups' => ['setList:read']]);
    }
}
