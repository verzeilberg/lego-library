<?php

namespace App\Service\Lego;

use App\Entity\Lego\Set;
use App\Entity\Lego\SetRating;
use App\Entity\User\UserData;
use App\Repository\Lego\SetRatingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;

class RatingService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SetRatingRepository   $setRatingRepository,
        private readonly SerializerInterface    $serializer
    ) {}

    /**
     * Save or update a rating
     */
    public function saveRating(UserData $user, Set $set, int $value): JsonResponse
    {
        // 1️⃣ Check for existing rating
        $rating = $this->setRatingRepository->findOneBy(['user' => $user, 'set' => $set]);

        if (!$rating) {
            $rating = new SetRating();
            $rating->setUser($user);
            $rating->setSet($set);
        }

        // 2️⃣ Set the value
        $rating->setValue($value);

        // 3️⃣ Persist
        $this->em->persist($rating);
        $this->em->flush();

        // 4️⃣ Optional: Update cached overall rating on Set
        $overall = $this->setRatingRepository->getOverallRatingForSet($set);
        // Round to the nearest 0.5
        $roundedOverall = round($overall * 2) / 2;
        $set->setRating($roundedOverall); // store as smallint 0-5

        $this->em->persist($set);
        $this->em->flush();

        $json = $this->serializer->serialize($set, 'json', [
            'groups' => ['lego_set:read'],
            'enable_max_depth' => true
        ]);

        return new JsonResponse($json, 200, [], true);
    }
}
