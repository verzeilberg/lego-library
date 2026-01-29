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

class GetSetsController extends AbstractController
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
    public function __invoke(Request $request): JsonResponse
    {
        $queryParams = $request->query->all();
        $data = $this->client->getSets($queryParams);
        return $this->json($data, 200, [], ['groups' => 'lego_set:read']);
    }
}
