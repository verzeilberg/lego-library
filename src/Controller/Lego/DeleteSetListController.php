<?php

namespace App\Controller\Lego;

use App\Entity\Lego\SetList;
use App\Service\FileManager;
use App\Service\UserService;
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
class DeleteSetListController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserService            $userService,
        private readonly UploaderHelper         $uploaderHelper,
        private readonly FileManager            $fileManager,
    )
    {
    }

    /**
     * @param Request $request
     * @param Security $security
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    public function __invoke(
        SetList             $setList,
        Request             $request,
        Security            $security,
        SerializerInterface $serializer
    ): JsonResponse
    {
        $user = $security->getUser();
        if (!$user) {
            return $this->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            if (!$setList->getUserData() || $setList->getUserData()->getOwner() === $user) {
                $this->entityManager->remove($setList);
                $this->entityManager->flush();
            } else {
                return $this->json(['result' => 'Set list deleted unsuccessfully, user is not the owner of the model list'], Response::HTTP_UNAUTHORIZED);
            }
        } catch (OptimisticLockException $exception) {
            return $this->json(['result' => 'Set list deleted unsuccessfully'], $exception->getCode());
        } catch (ORMException $exception) {
            return $this->json(['result' => 'Set list deleted unsuccessfully'], $exception->getCode());
        }

        return $this->json(['result' => 'Set list successfully deleted']);
    }
}
