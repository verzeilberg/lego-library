<?php

namespace App\Service\BrickLink;

use App\Entity\Lego\Color;
use App\Entity\Lego\Part;
use App\Service\Lego\RebrickableClient;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Fills the BrickLink part and color ids of parts/colors that don't have one yet,
 * using the external ids from Rebrickable. Results are stored, so Rebrickable is
 * only asked once per part/color.
 */
readonly class BrickLinkMappingService
{
    // Keep the part_nums query string within URL length limits
    private const PART_CHUNK_SIZE = 100;

    public function __construct(
        private RebrickableClient      $rebrickableClient,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * @param Part[] $parts
     * @param Color[] $colors
     */
    public function map(array $parts, array $colors): void
    {
        $this->mapParts($parts);
        $this->mapColors($colors);
        $this->entityManager->flush();
    }

    /**
     * @param Part[] $parts
     */
    private function mapParts(array $parts): void
    {
        $unmapped = [];
        foreach ($parts as $part) {
            if ($part->getBrickLinkId() === null) {
                $unmapped[$part->getPartNumber()] = $part;
            }
        }

        foreach (array_chunk($unmapped, self::PART_CHUNK_SIZE, true) as $chunk) {
            $results = $this->rebrickableClient->getPartsByNumbers(array_keys($chunk))['results'] ?? [];
            foreach ($results as $row) {
                $brickLinkIds = $row['external_ids']['BrickLink'] ?? [];
                if (isset($chunk[$row['part_num']]) && !empty($brickLinkIds)) {
                    $chunk[$row['part_num']]->setBrickLinkId((string) $brickLinkIds[0]);
                }
            }
        }
    }

    /**
     * @param Color[] $colors
     */
    private function mapColors(array $colors): void
    {
        $unmapped = [];
        foreach ($colors as $color) {
            if ($color->getBrickLinkId() === null) {
                $unmapped[$color->getId()] = $color;
            }
        }

        if (empty($unmapped)) {
            return;
        }

        // All colors fit in one request
        $results = $this->rebrickableClient->getColors()['results'] ?? [];
        foreach ($results as $row) {
            $brickLinkIds = $row['external_ids']['BrickLink']['ext_ids'] ?? [];
            if (isset($unmapped[$row['id']]) && !empty($brickLinkIds)) {
                $unmapped[$row['id']]->setBrickLinkId((int) $brickLinkIds[0]);
            }
        }
    }
}
