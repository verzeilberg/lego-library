<?php

namespace App\Service\Lego;

use App\Dto\Request\Lego\CreateSetRequest;
use App\Entity\Lego\Set;
use App\Entity\Lego\SetListSet;
use App\Entity\Media\MediaObject;
use App\Repository\Lego\SetListRepository;
use App\Repository\Lego\SetListSetRepository;
use App\Repository\Lego\SetRepository;
use App\Repository\Lego\ThemeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SetService
{
    public function __construct(
        private readonly SetListRepository      $setListRepository,
        private readonly SetRepository          $setRepository,
        private readonly SetListSetRepository   $setListSetRepository,
        private readonly PartService            $partService,
        private readonly MinifigService         $minifigService,
        private readonly ThemeRepository        $themeRepository,
        private readonly RebrickableClient      $rebrickableClient,
        private readonly HttpClientInterface    $httpClient,
        private readonly EntityManagerInterface $entityManager,
    )
    {}

    /**
     * @param CreateSetRequest $request
     * @return JsonResponse
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface|ORMException
     */
    public function createSetByNumber(CreateSetRequest $request): JsonResponse
    {
        //Check if a set list exists
        $setList = $this->setListRepository->find($request->getId());
        if (null === $setList) {
            return new JsonResponse(['message' => 'Set list not found'], 404);
        }

        //Check if a set already exists
        $legoSet = $this->setRepository->findOneBy(['baseNumber' => $request->getLegoNmbr()]);

        if ($legoSet !== null) {

            $link = new SetListSet();
            $link->setSet($legoSet);
            $link->setSetList($setList);
            $link->setShowImages($request->isAddLegoImages());
            $link->setShowMinifigs($request->isAddLegoMinifigs());
            $link->setShowParts($request->isAddLegoParts());

            $this->entityManager->persist($link);
            $this->entityManager->flush();

            return new JsonResponse(
                [
                    'message' => 'Set added to list successfully',
                    'set' => $legoSet->getNumber(),
                ]
            );
        }

        //Try to get set from Rebrickable API
        try {
            $legoSet = $this->rebrickableClient->getSetById($request->getLegoNmbr());
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'No set matches the given set number'], 404);
        }

        //Get theme from Rebrickable API
        $legoTheme = $this->rebrickableClient->getThemeById($legoSet['theme_id']);
        //Get or create the theme
        $theme = $this->themeRepository->getOrCreateByThemeId($legoTheme['id'], $legoTheme['name'], $legoTheme['parent_id']);

        $fullNumber = $legoSet['set_num'];
        $baseNumber = explode('-', $fullNumber)[0];

        //Create the set
        $set = new Set();
        $set->setNumber($fullNumber);
        $set->setBaseNumber($baseNumber);
        $set->setName($legoSet['name']);
        $set->setYear($legoSet['year']);
        $set->setNumParts($legoSet['num_parts']);
        $set->setTheme($theme);

        //Create an image and attach to a ssset.
        $file = $this->createFileFromUrlStream($legoSet['set_img_url']);
        $set->setFile($file);

        // Create the join entity to link the set and the list
        $link = new SetListSet();
        $link->setSet($set);
        $link->setSetList($setList);
        $link->setShowImages($request->isAddLegoImages());
        $link->setShowMinifigs($request->isAddLegoMinifigs());
        $link->setShowParts($request->isAddLegoParts());

        $this->entityManager->persist($set);
        $this->entityManager->persist($link);
        $this->entityManager->persist($set);

        //Check if the lego parts should be added
        $legoParts = $this->rebrickableClient->getPartsBySetId($request->getLegoNmbr());
        $set = $this->partService->createParts($set, $legoParts['results']);

        $legoMiniFigs = $this->rebrickableClient->getMiniFigsBySetNumber($request->getLegoNmbr());
        $set = $this->minifigService->createMinifigs($set, $legoMiniFigs['results']);

        $this->entityManager->flush();

        return new JsonResponse(
            [
                'message' => 'Set created successfully',
                'set' => $set->getNumber(),
            ]
        );

    }

    /**
     * @param string $bordId
     * @param string $setId
     * @return JsonResponse
     */
    public function deleteSetFromSetList(string $bordId, string $setId): JsonResponse
    {
        $set = $this->setRepository->find($setId);
        if (!$set) {
            return new JsonResponse(['message' => 'Set not found'], 404);
        }

        $setList = $this->setListRepository->find($bordId);
        if (!$setList) {
            return new JsonResponse(['message' => 'Set list not found'], 404);
        }

        // Find SetListSet by Set and SetList
        $setListSet = $this->setListSetRepository->findOneBy([
            'set' => $set,
            'setList' => $setList,
        ]);

        if (!$setListSet) {
            return new JsonResponse(['message' => 'Set list set not found'], 404);
        }

        $this->entityManager->remove($setListSet);
        $this->entityManager->flush();

        return new JsonResponse(
            [
                'message' => 'Set removed successfully from the set list',
            ]
        );
    }

    /**
     * Creates a Symfony UploadedFile from a remote URL using streams
     * to avoid loading the whole file into memory.
     */
    private function createFileFromUrlStream(string $url): UploadedFile
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'lego_');
        $tempFile = fopen($tempPath, 'w+b');

        if (!$tempFile) {
            throw new \RuntimeException('Could not create temporary file');
        }

        $response = $this->httpClient->request('GET', $url, [
            'buffer' => false,
        ]);

        foreach ($this->httpClient->stream($response) as $chunk) {
            if ($chunk->isTimeout()) {
                continue;
            }

            fwrite($tempFile, $chunk->getContent());
        }

        fclose($tempFile);

        return new UploadedFile(
            $tempPath,
            basename($url),
            null,
            null,
            true
        );
    }

}
