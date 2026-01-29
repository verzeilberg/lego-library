<?php
namespace App\Controller\Lego;

use App\Dto\Request\Lego\SetListsRequest;
use App\Entity\Lego\SetList;
use App\Repository\Lego\SetListRepository;
use App\Repository\Lego\SetRepository;
use App\Service\Lego\SetListService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Serializer\SerializerInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

/**
 * Handles the adding of a new Lego set list
 */
#[AsController]
class SetListController extends AbstractController
{
    /**
     * @param SetListService $modalListService
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SetListRepository      $setListRepository,
        private readonly UploaderHelper         $uploaderHelper,
    ) {}

    /**
     * Handles the invocation of the user registration process.
     *
     * @param Request $request The HTTP request instance containing attributes.
     * @return JsonResponse The response after user registration.
     */
    public function __invoke(Request $request, Security $security, SerializerInterface $serializer): JsonResponse
    {
        $user = $security->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        //Get the data from request
        $id = $request->get('id');
        $parentId = $request->get('parentId');
        $title = $request->get('title');
        $description = $request->get('description');
        $publicPrivate = $request->get('publicPrivate');
        $imageFile = $request->files->get('file');

        //Create a new modellist
        if (isset($id) && !empty($id)) {
            try {
                $setList = $this->entityManager->find(SetList::class, $id);
            } catch (OptimisticLockException $e) {
                return $this->json(['result' => 'Set list edited unsuccessfully'], $e->getCode());
            } catch (ORMException $e) {
                return $this->json(['result' => 'Set list edited unsuccessfully'], $e->getCode());
            }
        } else {
            $setList = new SetList();
        }

        $setList->setTitle($title);
        $setList->setDescription($description);
        $setList->setPublic($publicPrivate);
        $setList->setFile($imageFile);
        $setList->setPublicationDate(new \DateTimeImmutable());
        if (isset($parentId) && !empty($parentId)) {
            try {
                $setList->setParentList($this->entityManager->find(SetList::class, $parentId));
            } catch (OptimisticLockException $e) {
                return $this->json(['result' => 'Set list added unsuccessfully'], $e->getCode());
            } catch (ORMException $e) {
                return $this->json(['result' => 'Set list added unsuccessfully'], $e->getCode());
            }
        }
        $this->entityManager->persist($setList);

        //Set modellist to user data
        $userData = $user->getUserData();
        $setList->setUserData($userData);
        $this->entityManager->persist($userData);
        $this->entityManager->flush();

        $jsonData = $serializer->serialize($setList, 'json', ['groups' => ['modelList:read']]);
        return new JsonResponse($jsonData, Response::HTTP_OK, [], true);
    }

    /**
     * Retrieves the model lists associated with the authenticated user.
     *
     * @param Request $request The HTTP request object.
     * @param Security $security The security service to retrieve the current user.
     *
     * @return JsonResponse Returns a JSON response containing the user's model lists
     *                      or an error message if the user is not found.
     */
    public function getSetListsByUser(Request $request, Security $security): JsonResponse
    {
        $user = $security->getUser();

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

    public function getModelListById(Request $request, Security $security): JsonResponse
    {

        $user = $security->getUser();

        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $id = $request->get('id');
        $setList = $this->setListRepository->find($id);
        if ($setList->getUserData()->getOwner() === $user) {
            $path = $this->uploaderHelper->asset($setList, 'file');
            $setListRequest = new SetListsRequest($setList->getId(), $setList->getTitle(), $setList->getDescription(), $path);

            return new JsonResponse($setListRequest, Response::HTTP_OK);

        } else {
            return $this->json(['result' => 'Set list fetched unsuccessfully, user is not the owner of the model list'], Response::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * Retrieves the children set lists and associated sets for a given model list ID.
     *
     * This method fetches the children set lists from the SetListRepository
     * and the associated sets from the SetRepository for the specified model list ID.
     * It combines the children set lists and their sets using the SetListService
     * and returns the resulting data as a JSON response.
     *
     * @param string $id The ID of the model list whose children lists and sets are to be retrieved.
     * @param SetListRepository $setListRepository Repository for accessing set list data.
     * @param SetRepository $setRepository Repository for accessing set data.
     * @param SetListService $setListService Service for combining set lists and sets.
     * @param Security $security Security component for retrieving the current user.
     *
     * @return JsonResponse A JSON response containing the combined set list and set data, or an error message if the user is not authenticated.
     */
    public function getSetListChildrenAndSets(
        string            $id,
        SetListRepository $setListRepository,
        SetRepository     $setRepository,
        SetListService    $setListService,
        Security          $security
    ): JsonResponse
    {
        $user = $security->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $setList = $setListRepository->find($id);
        if (!$setList) {
            return new JsonResponse(['message' => 'Set list not found'], Response::HTTP_NOT_FOUND);
        }

        $childLists = $setList->getChildLists();
        /** @var Collection<SetListSet> $setLinks */
        $setLinks = $setList->getSetLinks();

        $setLists = $setListService->getCombinedListWithSets(
            $childLists,
            $setLinks
        );

        return $this->json($setLists, 200, [], ['groups' => ['setList:read']]);

    }

}
