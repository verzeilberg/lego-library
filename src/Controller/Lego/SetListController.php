<?php
namespace App\Controller\Lego;

use App\Dto\Request\Lego\SetListsRequest;
use App\Entity\Lego\SetList;
use App\Repository\Lego\SetListRepository;
use App\Repository\Lego\SetRepository;
use App\Repository\FriendshipRepository;
use App\Repository\UserDataRepository;
use App\Service\EmailService;
use App\Service\Lego\SetListService;
use App\Service\PushNotificationService;
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
        private readonly EntityManagerInterface  $entityManager,
        private readonly SetListRepository       $setListRepository,
        private readonly UploaderHelper          $uploaderHelper,
        private readonly UserDataRepository      $userDataRepository,
        private readonly FriendshipRepository    $friendshipRepository,
        private readonly PushNotificationService $pushNotificationService,
        private readonly EmailService            $emailService,
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
        $isNew = !isset($id) || empty($id);
        if (!$isNew) {
            try {
                $setList = $this->entityManager->find(SetList::class, $id);
            } catch (OptimisticLockException $e) {
                return $this->json(['result' => 'Set list edited unsuccessfully'], $e->getCode());
            } catch (ORMException $e) {
                return $this->json(['result' => 'Set list edited unsuccessfully'], $e->getCode());
            }

            if (!$setList) {
                return $this->json(['result' => 'Set list not found'], Response::HTTP_NOT_FOUND);
            }

            // Editing: owner or shared user can edit (including the top-level shared board itself)
            $userData = $user->getUserData();
            $isOwner = $setList->getUserData() === $userData;
            $isInSharedBoard = !$isOwner && (
                $setList->isSharedWith($userData) ||
                ($setList->getParentList() !== null && $setList->getParentList()->isSharedWith($userData))
            );
            if (!$isOwner && !$isInSharedBoard) {
                return $this->json(['result' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
            }
        } else {
            $setList = new SetList();

            // Creating: check parent board access
            if (isset($parentId) && !empty($parentId)) {
                $parentBoard = $this->entityManager->find(SetList::class, $parentId);
                if (!$parentBoard) {
                    return $this->json(['result' => 'Parent board not found'], Response::HTTP_NOT_FOUND);
                }
                $userData = $user->getUserData();
                if ($parentBoard->getUserData() !== $userData && !$parentBoard->isSharedWith($userData)) {
                    return $this->json(['result' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
                }
            }
        }

        $setList->setTitle($title);
        $setList->setDescription($description);
        $setList->setPublic(filter_var($publicPrivate, FILTER_VALIDATE_BOOLEAN));
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
        if ($isNew) {
            $setList->setUserData($userData);
        }
        $this->entityManager->persist($userData);
        $this->entityManager->flush();

        $setList->setContentUrl($this->uploaderHelper->asset($setList, 'file'));

        if ($isNew && filter_var($publicPrivate, FILTER_VALIDATE_BOOLEAN)) {
            $senderName = $userData->getUserName()
                ?? trim($userData->getFirstName() . ' ' . $userData->getLastName());
            $friends = $this->friendshipRepository->getFriendUserDataList($userData);

            $pushTokens = array_filter(array_map(fn($f) => $f->getNotificationPref('newBoardPush') ? $f->getPushToken() : null, $friends));
            $this->pushNotificationService->send(array_values($pushTokens), $senderName . ' heeft een nieuw bord toegevoegd', $title ?? 'Nieuw bord');

            foreach ($friends as $friend) {
                if ($friend->getNotificationPref('newBoardEmail') && ($email = $friend->getOwner()?->getEmail())) {
                    $this->emailService->send('social/new-board', $email, $senderName . ' heeft een nieuw bord toegevoegd', [
                        'senderName'    => $senderName,
                        'recipientName' => trim($friend->getFirstName() . ' ' . $friend->getLastName()),
                        'boardTitle'    => $title ?? 'Nieuw bord',
                    ]);
                }
            }
        }

        $jsonData = $serializer->serialize($setList, 'json', ['groups' => ['modelList:read']]);
        return new JsonResponse($jsonData, Response::HTTP_OK, [], true);
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
