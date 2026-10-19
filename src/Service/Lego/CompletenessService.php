<?php

namespace App\Service\Lego;

use App\Entity\Lego\SetList;
use App\Entity\Lego\SetListSet;
use App\Entity\Lego\UserSetMinifig;
use App\Entity\Lego\UserSetPart;
use App\Entity\User\UserData;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Calculates how complete sets and boards are.
 *
 * Completeness = (parts in the set - missing - broken) / parts in the set, counting only
 * regular (non-spare) parts; discoloured parts count as present. 100% is only shown when
 * the set is also marked complete, so a set with e.g. only a missing minifig or spare part
 * shows at most 99%.
 */
readonly class CompletenessService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MissingItemsService    $missingItemsService,
    ) {}

    /**
     * Stats of a board including all sub-boards the user may see.
     *
     * @return array{sets: int, incompleteSets: int, partsTotal: int, missingParts: int, brokenParts: int, missingMinifigs: int, completeness: ?int}
     */
    public function boardStats(SetList $board, ?UserData $userData): array
    {
        $boardIds = array_keys($this->missingItemsService->collectBoardPaths($board, $userData));

        /** @var SetListSet[] $setListSets */
        $setListSets = $this->entityManager->createQueryBuilder()
            ->select('sls', 's')
            ->from(SetListSet::class, 'sls')
            ->join('sls.set', 's')
            ->join('sls.setList', 'sl')
            ->where('sl.id IN (:boardIds)')
            ->setParameter('boardIds', $boardIds)
            ->getQuery()
            ->getResult();

        $totals = [
            'sets'            => count($setListSets),
            'incompleteSets'  => 0,
            'partsTotal'      => 0,
            'missingParts'    => 0,
            'brokenParts'     => 0,
            'missingMinifigs' => 0,
            'completeness'    => null,
        ];
        $defectiveRequired = 0;

        foreach ($this->setStats($setListSets) as $stats) {
            $totals['incompleteSets'] += $stats['complete'] ? 0 : 1;
            $totals['partsTotal'] += $stats['partsTotal'];
            $totals['missingParts'] += $stats['missingParts'];
            $totals['brokenParts'] += $stats['brokenParts'];
            $totals['missingMinifigs'] += $stats['missingMinifigs'];
            $defectiveRequired += $stats['defectiveRequired'];
        }

        if ($totals['sets'] > 0) {
            $totals['completeness'] = $this->percentage(
                $totals['partsTotal'],
                $defectiveRequired,
                $totals['incompleteSets'] === 0
            );
        }

        return $totals;
    }

    /**
     * Stats per set within a board.
     *
     * @param iterable<SetListSet> $setListSets
     * @return array<string, array{complete: bool, partsTotal: int, defectiveRequired: int, missingParts: int, brokenParts: int, missingMinifigs: int, completeness: int}>
     *     keyed by SetListSet id
     */
    public function setStats(iterable $setListSets): array
    {
        $setListSets = is_array($setListSets) ? $setListSets : iterator_to_array($setListSets, false);
        if (empty($setListSets)) {
            return [];
        }

        $partRows = $this->entityManager->createQueryBuilder()
            ->select(
                'IDENTITY(usp.setListSet) AS slsId',
                'SUM(CASE WHEN sp.isSpare = false THEN usp.missingQuantity + usp.damagedQuantity ELSE 0 END) AS defectiveRequired',
                'SUM(usp.missingQuantity) AS missingParts',
                'SUM(usp.damagedQuantity) AS brokenParts',
            )
            ->from(UserSetPart::class, 'usp')
            ->join('usp.setPart', 'sp')
            ->where('usp.setListSet IN (:setListSets)')
            ->setParameter('setListSets', $setListSets)
            ->groupBy('usp.setListSet')
            ->getQuery()
            ->getArrayResult();

        $minifigRows = $this->entityManager->createQueryBuilder()
            ->select('IDENTITY(usm.setListSet) AS slsId', 'SUM(usm.missingQuantity) AS missingMinifigs')
            ->from(UserSetMinifig::class, 'usm')
            ->where('usm.setListSet IN (:setListSets)')
            ->setParameter('setListSets', $setListSets)
            ->groupBy('usm.setListSet')
            ->getQuery()
            ->getArrayResult();

        $partsBySet = array_column($partRows, null, 'slsId');
        $minifigsBySet = array_column($minifigRows, 'missingMinifigs', 'slsId');

        $result = [];
        foreach ($setListSets as $setListSet) {
            $id = (string) $setListSet->getId();
            $set = $setListSet->getSet();
            // totalPartsQuantity excludes spares, but is only known when the parts were imported
            $partsTotal = $set->getTotalPartsQuantity() ?: $set->getNumParts();
            $defectiveRequired = (int) ($partsBySet[$id]['defectiveRequired'] ?? 0);

            $result[$id] = [
                'complete'          => $setListSet->isComplete(),
                'partsTotal'        => $partsTotal,
                'defectiveRequired' => $defectiveRequired,
                'missingParts'      => (int) ($partsBySet[$id]['missingParts'] ?? 0),
                'brokenParts'       => (int) ($partsBySet[$id]['brokenParts'] ?? 0),
                'missingMinifigs'   => (int) ($minifigsBySet[$id] ?? 0),
                'completeness'      => $this->percentage($partsTotal, $defectiveRequired, $setListSet->isComplete()),
            ];
        }

        return $result;
    }

    private function percentage(int $partsTotal, int $defective, bool $complete): int
    {
        if ($complete) {
            return 100;
        }

        $percentage = $partsTotal > 0
            ? (int) floor(100 * max(0, $partsTotal - $defective) / $partsTotal)
            : 99; // no part count known (e.g. only a minifig is missing)

        return min(99, $percentage);
    }
}
