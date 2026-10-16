<?php

namespace App\Controller\User;

use App\Enum\BoardVisibility;
use App\Repository\Lego\SetListRepository;
use App\Repository\UserDataRepository;
use App\Service\Lego\BoardAccessService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class GetPublicUserBords extends AbstractController
{
    public function __construct(
        private readonly UserDataRepository $userDataRepository,
        private readonly SetListRepository  $setListRepository,
        private readonly UploaderHelper     $uploaderHelper,
        private readonly BoardAccessService $boardAccessService,
        private readonly Security           $security,
    ) {}

    #[Route('/api/public/user/{id}/bords', name: 'api_public_user_bords', methods: ['GET'])]
    public function __invoke(int $id, Request $request): JsonResponse
    {
        $userData = $this->userDataRepository->find($id);

        if (!$userData) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $limit  = max(1, (int) $request->query->get('limit', 10));
        $page   = max(1, (int) $request->query->get('page', 1));
        $offset = ($page - 1) * $limit;

        // Friends also see this user's friends-only boards
        $visibilities = [BoardVisibility::Public->value];
        if ($this->boardAccessService->areFriends($userData, $this->security->getUser()?->getUserData())) {
            $visibilities[] = BoardVisibility::Friends->value;
        }

        $setLists = $this->setListRepository->findBy(
            ['userData' => $userData, 'visibility' => $visibilities, 'parentList' => null],
            ['publicationDate' => 'DESC'],
            $limit,
            $offset
        );

        $result = array_map(function ($set) {
            return [
                'id'          => $set->getId(),
                'title'       => $set->getTitle(),
                'description' => $set->getDescription(),
                'filePath'    => $this->uploaderHelper->asset($set, 'file'),
            ];
        }, $setLists);

        return new JsonResponse($result);
    }
}
