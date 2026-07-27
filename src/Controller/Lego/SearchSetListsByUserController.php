<?php

namespace App\Controller\Lego;

use App\Dto\Request\Lego\SetListsRequest;
use App\Repository\Lego\SetListRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

#[AsController]
class SearchSetListsByUserController extends AbstractController
{
    public function __construct(
        private readonly SetListRepository $setListRepository,
        private readonly UploaderHelper    $uploaderHelper,
        private readonly Security          $security,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $query  = trim((string) $request->query->get('q', ''));
        $limit  = max(1, (int) $request->query->get('limit', 50));
        $page   = max(1, (int) $request->query->get('page', 1));
        $offset = ($page - 1) * $limit;

        if ($query === '') {
            return new JsonResponse([], Response::HTTP_OK);
        }

        $userData = $user->getUserData();
        $userDataId = $userData->getId();

        $results = $this->setListRepository->searchByUser($userDataId, $query, $limit, $offset);

        $data = array_map(function ($setList) use ($userData) {
            $path = $this->uploaderHelper->asset($setList, 'file');
            $isShared = $setList->getUserData() !== $userData;
            return new SetListsRequest(
                $setList->getId(),
                $setList->getTitle(),
                $setList->getDescription(),
                $setList->isPublic(),
                false,
                $path,
                null,
                $setList->getParentList()?->getId()?->toString(),
                $isShared,
            );
        }, $results);

        return new JsonResponse($data, Response::HTTP_OK);
    }
}
