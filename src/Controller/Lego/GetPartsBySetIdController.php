<?php
namespace App\Controller\Lego;

use App\Dto\Request\Lego\CreateSetRequest;
use App\Service\Lego\SetService;
use App\Service\Lego\RebrickableClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

class GetPartsBySetIdController extends AbstractController
{
    public function __construct(

        private readonly RebrickableClient $rebrickableClient,
        private readonly Security $security,
    )
    {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], 404);
        }

        $setNumber  = $request->get('id');
        $data       = $this->rebrickableClient->getPartsBySetId($setNumber);

        return new JsonResponse($data);
    }
}
