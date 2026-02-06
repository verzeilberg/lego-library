<?php

namespace App\Controller\Lego;


use App\Repository\Lego\SetRepository;
use App\Service\Lego\RatingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

class CreateRateForSetController extends AbstractController
{

    public function __construct(
        private readonly RatingService $ratingService,
        private readonly SetRepository $setRepository
    )
    {

    }

    public function __invoke(
        Request $request,
        Security $security
    ): JsonResponse
    {

        $user = $security->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], 404);
        }

        $userData = $user->getUserData();

        $setNumber = $request->attributes->get('dto')->setId;
        $set = $this->setRepository->findOneBy(['number' => $setNumber]);
        if (!$set) {
            return new JsonResponse(['message' => 'Set not found: '. $setNumber], 404);
        }

        $rating = $request->attributes->get('dto')->rating;

        return $this->ratingService->saveRating($userData, $set, $rating);
    }
}
