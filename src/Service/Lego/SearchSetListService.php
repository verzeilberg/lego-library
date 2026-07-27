<?php

namespace App\Service\Lego;

use App\Dto\Request\Lego\SetListsRequest;
use App\Entity\Lego\SetList;
use App\Repository\Lego\SetListRepository;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

readonly class SearchSetListService
{
    public function __construct(
        private SetListRepository $setListRepository,
        private UploaderHelper    $uploaderHelper,
    ) {}

    /**
     * Search public boards by title, description, or contained set name/number.
     * Returns mapped SetListsRequest DTOs including owner info.
     *
     * @return SetListsRequest[]
     */
    public function searchPublic(string $query, int $limit, int $offset, ?string $excludeUserDataId = null): array
    {
        $setLists = $this->setListRepository->searchPublic($query, $limit, $offset, $excludeUserDataId);

        return array_map(function (SetList $setList) {
            $filePath = $this->uploaderHelper->asset($setList, 'file');
            $userData = $setList->getUserData();
            $owner    = null;

            if ($userData !== null) {
                $profilePicture = $userData->getFilePath()
                    ? $this->uploaderHelper->asset($userData, 'file')
                    : null;

                $owner = [
                    'id'             => $userData->getId(),
                    'userName'       => $userData->getUserName(),
                    'profilePicture' => $profilePicture,
                    'geslacht'       => $userData->getGeslacht()?->value,
                ];
            }

            return new SetListsRequest(
                $setList->getId(),
                $setList->getTitle(),
                $setList->getDescription(),
                $setList->isPublic(),
                false,
                $filePath,
                $owner,
                $setList->getParentList()?->getId()?->toString(),
            );
        }, $setLists);
    }
}
