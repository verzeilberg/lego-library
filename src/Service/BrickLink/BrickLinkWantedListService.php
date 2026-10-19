<?php

namespace App\Service\BrickLink;

use App\Entity\Lego\UserSetMinifig;
use App\Entity\Lego\UserSetPart;

/**
 * Builds a BrickLink wanted list (XML, for "Upload" on bricklink.com) from missing parts.
 *
 * Quantity per part = missing + broken. Items with the same BrickLink part and color
 * are combined. Minifigs can't be mapped (Rebrickable has no BrickLink minifig ids),
 * they are returned as unmapped so the user can add them by hand.
 */
readonly class BrickLinkWantedListService
{
    public function __construct(
        private BrickLinkMappingService $mappingService,
    ) {}

    /**
     * @param UserSetPart[] $userSetParts
     * @param UserSetMinifig[] $userSetMinifigs
     * @return array{xml: string, itemCount: int, totalQuantity: int, unmapped: array<int, array<string, mixed>>}
     */
    public function build(array $userSetParts, array $userSetMinifigs = []): array
    {
        $this->mappingService->map(
            array_map(fn(UserSetPart $p) => $p->getPart(), $userSetParts),
            array_map(fn(UserSetPart $p) => $p->getColor(), $userSetParts),
        );

        $items = [];
        $unmapped = [];
        foreach ($userSetParts as $userSetPart) {
            $part = $userSetPart->getPart();
            $color = $userSetPart->getColor();
            $quantity = $userSetPart->getMissingQuantity() + $userSetPart->getDamagedQuantity();
            $setNumber = $userSetPart->getSetListSet()->getSet()->getNumber();

            if ($quantity <= 0) {
                continue;
            }

            if ($part->getBrickLinkId() === null || $color->getBrickLinkId() === null) {
                $unmapped[] = [
                    'type'      => 'part',
                    'number'    => $part->getPartNumber(),
                    'name'      => $part->getName(),
                    'colorName' => $color->getName(),
                    'quantity'  => $quantity,
                    'setNumber' => $setNumber,
                ];
                continue;
            }

            $key = $part->getBrickLinkId() . '|' . $color->getBrickLinkId();
            $items[$key] ??= [
                'itemId'   => $part->getBrickLinkId(),
                'colorId'  => $color->getBrickLinkId(),
                'quantity' => 0,
                'sets'     => [],
            ];
            $items[$key]['quantity'] += $quantity;
            $items[$key]['sets'][$setNumber] = true;
        }

        foreach ($userSetMinifigs as $userSetMinifig) {
            $minifig = $userSetMinifig->getSetMinifig()->getMinifig();
            $unmapped[] = [
                'type'      => 'minifig',
                'number'    => $minifig->getSetNumId(),
                'name'      => $minifig->getName(),
                'colorName' => null,
                'quantity'  => $userSetMinifig->getMissingQuantity(),
                'setNumber' => $userSetMinifig->getSetListSet()->getSet()->getNumber(),
            ];
        }

        ksort($items, SORT_NATURAL);

        return [
            'xml'           => $this->toXml($items),
            'itemCount'     => count($items),
            'totalQuantity' => array_sum(array_column($items, 'quantity')),
            'unmapped'      => $unmapped,
        ];
    }

    /**
     * @param array<string, array{itemId: string, colorId: int, quantity: int, sets: array<string, true>}> $items
     */
    private function toXml(array $items): string
    {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;
        $inventory = $doc->appendChild($doc->createElement('INVENTORY'));

        foreach ($items as $item) {
            $element = $inventory->appendChild($doc->createElement('ITEM'));
            $element->appendChild($doc->createElement('ITEMTYPE', 'P'));
            $element->appendChild($doc->createElement('ITEMID'))->appendChild($doc->createTextNode($item['itemId']));
            $element->appendChild($doc->createElement('COLOR', (string) $item['colorId']));
            $element->appendChild($doc->createElement('MINQTY', (string) $item['quantity']));
            $element->appendChild($doc->createElement('REMARKS'))
                ->appendChild($doc->createTextNode('Sets: ' . implode(', ', array_keys($item['sets']))));
            $element->appendChild($doc->createElement('NOTIFY', 'N'));
        }

        return $doc->saveXML();
    }
}
