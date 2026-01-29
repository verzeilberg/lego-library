<?php

namespace App\Service\Lego;

use App\Dto\Request\Lego\SetListsRequest;
use App\Entity\Lego\SetListSet;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

readonly class SetListService
{
    public function __construct(
        private UploaderHelper $uploaderHelper,
    ) {}

    /**
     * @param iterable $setLists iterable<SetList>
     * @param iterable $setLinks iterable<SetListSet>
     */
    public function getCombinedListWithSets(
        iterable $setLists,
        iterable $setLinks
    ): array {
        $combined = [];

        // Child lists
        foreach ($setLists as $list) {
            $combined[] = new SetListsRequest(
                $list->getId(),
                $list->getTitle(),
                $list->getDescription(),
                $list->isPublic(),
                false,
                $this->uploaderHelper->asset($list, 'file'),
            );
        }

        // Sets inside this list (via join entity)
        foreach ($setLinks as $link) {

            $set = $link->getSet();
            $imagePath = null;
            if ($link->isShowImages() && $set->getFilePath()) {
                $imagePath = $this->uploaderHelper->asset($set, 'file');
            }

            $combined[] = new SetListsRequest(
                $set->getBaseNumber(),
                $set->getName(),
                '',
                true,
                true,
                $imagePath,
            );
        }

        return $combined;
    }
}
