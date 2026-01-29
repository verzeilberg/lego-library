<?php

namespace App\Controller\User;

use App\Entity\User\UserData;
use App\Service\FileManager;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Serializer\SerializerInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

#[AsController]
class DeleteUser extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserService            $userService,
        private readonly UploaderHelper         $uploaderHelper,
        private readonly FileManager            $fileManager,
    ) {}

    /**
     * @param Request $request
     * @param Security $security
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    public function __invoke(
        Request $request,
        Security $security,
        SerializerInterface $serializer
    ): JsonResponse {
        $user = $security->getUser();
        if (!$user) {
            return $this->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return $this->json(['result' => 'User deleted successfully']);
    }
}
