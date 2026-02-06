<?php
// src/Controller/GetSetController.php
namespace App\Controller\Lego;

use App\Repository\Lego\SetListRepository;
use App\Repository\Lego\SetListSetRepository;
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
        private readonly RebrickableClient    $client,
        private readonly SetRepository        $setRepository,
        private readonly SetListRepository    $setListRepository,
        private readonly SetListSetRepository $setListSetRepository
    )
    {
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getSets(Request $request): JsonResponse
    {
        // Optional query parameters: page, page_size, year, theme_id, etc.
        $queryParams = $request->query->all();

        $data = $this->client->getSets($queryParams);

        return $this->json($data, 200, [], ['groups' => 'lego_set:read']);
    }

    /**
     * @param Request $request
     * @param Security $security
     * @return JsonResponse
     */
    public function getSetById(
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

        // Serialize entity fully, including setParts and relations
        $json = $serializer->serialize($set, 'json', [
            'groups' => ['lego_set:read'],
            'enable_max_depth' => true
        ]);

        return new JsonResponse($json, 200, [], true);
    }


    /**
     * @param Request $request
     * @param Security $security
     * @return JsonResponse
     */
    public function getPartsBySetId(Request $request, Security $security): JsonResponse
    {
        $user = $security->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], 404);
        }

        $setNumber = $request->get('id');
        $data = $this->client->getPartsBySetId($setNumber);

        return new JsonResponse($data);
    }

    public function getThemes(Request $request, Security $security)
    {
        $user = $security->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], 404);
        }

        $data = $this->client->getThemes();

        return new JsonResponse($data);
    }

    public function getThemeById(Request $request, Security $security)
    {
        $user = $security->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], 404);
        }

        $themeId = $request->get('id');
        $data = $this->client->getThemeById($themeId);

        return new JsonResponse($data);
    }
}
