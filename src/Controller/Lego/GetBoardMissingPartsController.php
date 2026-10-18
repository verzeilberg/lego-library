<?php

namespace App\Controller\Lego;

use App\Entity\Lego\SetList;
use App\Entity\Lego\UserSetPart;
use App\Entity\User\UserData;
use App\Repository\Lego\SetListRepository;
use App\Service\Lego\BoardAccessService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns all missing parts of every set in a board, including the sets
 * in all (visible) sub-boards, recursively.
 */
class GetBoardMissingPartsController extends AbstractController
{
    public function __construct(
        private readonly SetListRepository      $setListRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly Security               $security,
        private readonly BoardAccessService     $boardAccessService,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $setList = $this->setListRepository->find($id);
        if (!$setList) {
            return new JsonResponse(['message' => 'Set list not found'], Response::HTTP_NOT_FOUND);
        }

        $userData = $user->getUserData();
        if (!$this->boardAccessService->canView($setList, $userData)) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        // board id => path relative to the requested board ("Sub > Sub sub")
        $boardPaths = [];
        $this->collectBoards($setList, $userData, $setList->getTitle(), $boardPaths);

        /** @var UserSetPart[] $userSetParts */
        $userSetParts = $this->entityManager->createQueryBuilder()
            ->select('usp', 'sls', 'sl', 's', 'sp', 'pc', 'p', 'c')
            ->from(UserSetPart::class, 'usp')
            ->join('usp.setListSet', 'sls')
            ->join('sls.setList', 'sl')
            ->join('sls.set', 's')
            ->join('usp.setPart', 'sp')
            ->join('sp.partColor', 'pc')
            ->join('pc.part', 'p')
            ->join('pc.color', 'c')
            ->where('sl.id IN (:boardIds)')
            ->andWhere('usp.missingQuantity > 0')
            ->setParameter('boardIds', array_keys($boardPaths))
            ->orderBy('s.number', 'ASC')
            ->addOrderBy('p.partNumber', 'ASC')
            ->getQuery()
            ->getResult();

        $parts = [];
        foreach ($userSetParts as $userSetPart) {
            $setListSet = $userSetPart->getSetListSet();
            $set = $setListSet->getSet();
            $setPart = $userSetPart->getSetPart();
            $partColor = $setPart->getPartColor();

            $imgUrl = $partColor->getImgUrl();

            $parts[] = [
                'board'               => $boardPaths[(string) $setListSet->getSetList()->getId()],
                'setNumber'           => $set->getNumber(),
                'setName'             => $set->getName(),
                'partNumber'          => $partColor->getPart()->getPartNumber(),
                'name'                => $partColor->getPart()->getName(),
                'colorId'             => $partColor->getColor()->getId(),
                'colorName'           => $partColor->getColor()->getName(),
                'isSpare'             => $setPart->isSpare(),
                'quantity'            => $setPart->getQuantity(),
                'missingQuantity'     => $userSetPart->getMissingQuantity(),
                'damagedQuantity'     => $userSetPart->getDamagedQuantity(),
                'discolouredQuantity' => $userSetPart->getDiscolouredQuantity(),
                'imageUrl'            => ($imgUrl && !str_starts_with($imgUrl, 'http'))
                    ? '/media/lego/' . $imgUrl
                    : $imgUrl,
            ];
        }

        return new JsonResponse([
            'board' => $setList->getTitle(),
            'parts' => $parts,
        ]);
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
