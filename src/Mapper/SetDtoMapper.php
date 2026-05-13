<?php
namespace App\Mapper;

use App\Dto\Request\Lego\SetMinifigDTO;
use App\Dto\Request\Lego\SetPartDTO;
use App\Dto\Request\Lego\SetRequest;
use App\Entity\Lego\Set;
use App\Entity\Lego\SetListSet;
use App\Repository\Lego\SetRatingRepository;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

final readonly class SetDtoMapper
{
    public function __construct(
        private SetRatingRepository $ratingRepository,
        private UploaderHelper      $uploaderHelper,
    ) {}

    public function map(Set $set, SetListSet $listSet, $user): SetRequest
    {
        $dto = new SetRequest();

        // --- scalars ---
        $dto->number                = $set->getNumber();
        $dto->baseNumber            = $set->getBaseNumber();
        $dto->name                  = $set->getName();
        $dto->year                  = $set->getYear();
        $dto->numParts              = $set->getNumParts();
        $dto->rating                = $set->getRating();
        $dto->themeName             = $set->getThemeName();
        $dto->isComplete            = $listSet->isComplete();
        $dto->hasInstructions       = $listSet->hasInstructions();
        $dto->specificParts         = $set->getTotalParts();
        $dto->totalQuantity         = $set->getTotalPartsQuantity();
        $dto->totalMiniFigsParts    = $set->getTotalMiniFigParts();

        // --- images ---
        $images = [];

        if ($listSet->isShowImages() && $set->getFilePath()) {
            $images[] = ['id' => null, 'path' => $this->uploaderHelper->asset($set, 'file')];
        }

        foreach ($listSet->getMediaObjects() as $media) {
            if ($media->getFilePath()) {
                $images[] = ['id' => $media->getId(), 'path' => $this->uploaderHelper->asset($media, 'file')];
            }
        }

        $dto->images = $images;

        // --- list flags ---
        $dto->showParts = $listSet->isShowParts();
        $dto->showMinifigs = $listSet->isShowMinifigs();

        $dto->personalRating =
            $this->ratingRepository->getUserRatingForSet($user, $set) ?? 0;

        // --- build UserSetPart lookup: setPartId -> UserSetPart ---
        $userSetPartMap = [];
        foreach ($listSet->getPartStates() as $userSetPart) {
            $userSetPartMap[(string) $userSetPart->getSetPart()->getId()] = $userSetPart;
        }

        // --- parts ---
        foreach ($set->getSetParts() as $setPart) {
            $part = $setPart->getPart();
            $color = $setPart->getColor();
            $partColor = $setPart->getPartColor();

            $partDto = new SetPartDTO();
            $partDto->setPartId = (string) $setPart->getId();
            $partDto->setNumber = $set->getNumber();
            $partDto->partNumber = $part->getPartNumber();
            $partDto->name = $part->getName();
            $partDto->quantity = $setPart->getQuantity();

            $partDto->colorId = $color->getId();
            $partDto->colorName = $color->getName();
            $partDto->colorHex = $color->getHexColor();
            $partDto->isTransparent = $color->isTrans();

            $imgUrl = $partColor->getImgUrl();
            $partDto->imageUrl = ($imgUrl && !str_starts_with($imgUrl, 'http'))
                ? '/media/lego/' . $imgUrl
                : $imgUrl;

            $userSetPart = $userSetPartMap[(string) $setPart->getId()] ?? null;
            if ($userSetPart !== null) {
                $partDto->missingQuantity     = $userSetPart->getMissingQuantity();
                $partDto->damagedQuantity     = $userSetPart->getDamagedQuantity();
                $partDto->discolouredQuantity = $userSetPart->getDiscolouredQuantity();
            }

            $dto->setParts[] = $partDto;
        }

        // --- minifigs ---
        foreach ($set->getSetMinifigs() as $link) {
            $fig = $link->getMinifig();

            $figDto = new SetMinifigDTO();
            $figDto->id = $fig->getId();
            $figDto->setNumId = $fig->getSetNumId();
            $figDto->name = $fig->getName();
            $figDto->imageUrl = $fig->getImageUrl();
            $figDto->quantity = $link->getQuantity();

            $dto->setMinifigs[] = $figDto;
        }

        return $dto;
    }
}
