<?php

namespace App\Service\Lego;

use App\Entity\Lego\SetList;
use App\Entity\Lego\SetListSet;
use App\Entity\Lego\UserSetMinifig;
use App\Entity\Lego\UserSetPart;
use App\Entity\User\UserData;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * Finds missing parts and minifigs of a board (incl. its sub-boards) or of a single set in a board.
 */
readonly class MissingItemsService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BoardAccessService     $boardAccessService,
    ) {}

    /**
     * Returns the board and all its sub-boards (recursively) the user may see,
     * as board id => path relative to the given board ("Board > Sub > Sub sub").
     *
     * @return array<string, string>
     */
    public function collectBoardPaths(SetList $board, ?UserData $userData): array
    {
        $boardPaths = [];
        $this->collectBoards($board, $userData, $board->getTitle(), $boardPaths);

        return $boardPaths;
    }

    /**
     * @param string[] $boardIds
     * @param bool $includeBroken Also return parts that are only broken (not missing)
     * @return UserSetPart[]
     */
    public function findMissingParts(array $boardIds = [], ?SetListSet $setListSet = null, bool $includeBroken = false): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('usp', 'sls', 'sl', 's', 'sp', 'pc', 'p', 'c')
            ->from(UserSetPart::class, 'usp')
            ->join('usp.setListSet', 'sls')
            ->join('sls.setList', 'sl')
            ->join('sls.set', 's')
            ->join('usp.setPart', 'sp')
            ->join('sp.partColor', 'pc')
            ->join('pc.part', 'p')
            ->join('pc.color', 'c')
            ->where($includeBroken ? 'usp.missingQuantity > 0 OR usp.damagedQuantity > 0' : 'usp.missingQuantity > 0')
            ->orderBy('s.number', 'ASC')
            ->addOrderBy('p.partNumber', 'ASC');

        return $this->applyScope($qb, $boardIds, $setListSet)->getQuery()->getResult();
    }

    /**
     * @param string[] $boardIds
     * @return UserSetMinifig[]
     */
    public function findMissingMinifigs(array $boardIds = [], ?SetListSet $setListSet = null): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('usm', 'sls', 'sl', 's', 'sm', 'm')
            ->from(UserSetMinifig::class, 'usm')
            ->join('usm.setListSet', 'sls')
            ->join('sls.setList', 'sl')
            ->join('sls.set', 's')
            ->join('usm.setMinifig', 'sm')
            ->join('sm.minifig', 'm')
            ->where('usm.missingQuantity > 0')
            ->orderBy('s.number', 'ASC');

        return $this->applyScope($qb, $boardIds, $setListSet)->getQuery()->getResult();
    }

    private function applyScope(QueryBuilder $qb, array $boardIds, ?SetListSet $setListSet): QueryBuilder
    {
        if ($setListSet !== null) {
            return $qb->andWhere('sls = :setListSet')->setParameter('setListSet', $setListSet);
        }

        return $qb->andWhere('sl.id IN (:boardIds)')->setParameter('boardIds', $boardIds ?: ['']);
    }

    /**
     * @param array<string, string> $boardPaths
     */
    private function collectBoards(SetList $board, ?UserData $userData, string $path, array &$boardPaths): void
    {
        $boardPaths[(string) $board->getId()] = $path;

        foreach ($board->getChildLists() as $child) {
            // Skip sub-boards this user may not see (e.g. a private sub-board inside a public board)
            if ($this->boardAccessService->canView($child, $userData)) {
                $this->collectBoards($child, $userData, $path . ' > ' . $child->getTitle(), $boardPaths);
            }
        }
    }
}
