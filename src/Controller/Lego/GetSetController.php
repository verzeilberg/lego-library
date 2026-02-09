<?php
// src/Controller/GetSetController.php
namespace App\Controller\Lego;

use App\Repository\Lego\SetListRepository;
use App\Repository\Lego\SetListSetRepository;
use App\Repository\Lego\SetRatingRepository;
use App\Repository\Lego\SetRepository;
use App\Service\Lego\RebrickableClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class GetSetController extends AbstractController
{

    public function __construct(
        private readonly SetRepository        $setRepository,
        private readonly SetListRepository    $setListRepository,
        private readonly SetListSetRepository $setListSetRepository,
        private readonly SetRatingRepository  $setRatingRepository
    )
    {
    }

    /**
     * @param Request $request
     * @param Security $security
     * @param UploaderHelper $uploaderHelper
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    public function __invoke(
        Request $request,
        Security $security,
        UploaderHelper $uploaderHelper,
        SerializerInterface $serializer
    ): JsonResponse
    {
        $user = $security->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], 404);
        }

        $setNumber = $request->get('number');
        $set = $this->setRepository->findOneBy(['baseNumber' => $setNumber]);

        if (!$set) {
            return new JsonResponse(['message' => 'Set not found'], 404);
        }

        $listId = $request->get('listId');
        $setList = $this->setListRepository->findOneBy([
            'userData' => $user->getUserData(),
            'id' => $listId
        ]);
        if (!$setList) {
            return new JsonResponse(['message' => 'Set list not found'], 404);
        }

        $setListSet = $this->setListSetRepository->findOneBy([
            'set' => $set,
            'setList' => $setList // must be entity
        ]);
        if (!$setListSet) {
            return new JsonResponse(['message' => 'Set not found in this list'], 404);
        }

        //Get a main image from the set and images from set list set
        $images = [];
        if ($setListSet->isShowImages() && $set->getFilePath()) {
            $images[] = $uploaderHelper->asset($set, 'file');
        }

        foreach ($setListSet->getMediaObjects() as $media) {
            if ($media->getFilePath()) {
                $images[] = $uploaderHelper->asset($media, 'file');
            }
        }

        $set->setImages($images);
        $set->setShowParts($setListSet->isShowParts());
        $set->setShowMinifigs($setListSet->isShowMinifigs());
        $personalRatingForSet = $this->setRatingRepository->getUserRatingForSet($user, $set) ?? 0;
        $set->setPersonalRating($personalRatingForSet);

        // Serialize entity fully, including setParts and relations
        $json = $serializer->serialize($set, 'json', [
            'groups' => ['lego_set:read'],
            'enable_max_depth' => true
        ]);

        return new JsonResponse($json, 200, [], true);
    }
}
