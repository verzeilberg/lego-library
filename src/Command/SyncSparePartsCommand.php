<?php

namespace App\Command;

use App\Entity\Lego\Set;
use App\Entity\Lego\SetPart;
use App\Entity\Lego\UserSetPart;
use App\Service\Lego\RebrickableClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Backfills SetPart::isSpare for sets imported before the flag existed,
 * using the is_spare information from Rebrickable.
 */
#[AsCommand(
    name: 'app:lego:sync-spare-parts',
    description: 'Mark spare parts of already imported sets using Rebrickable data',
)]
class SyncSparePartsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RebrickableClient      $rebrickableClient,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('set', null, InputOption::VALUE_REQUIRED, 'Only sync this set number (e.g. 60179-1)')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would change without saving');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');

        $setRepository = $this->entityManager->getRepository(Set::class);
        $sets = $input->getOption('set')
            ? array_filter([$setRepository->find($input->getOption('set'))])
            : $setRepository->findAll();

        if (empty($sets)) {
            $io->warning('No sets found.');
            return Command::SUCCESS;
        }

        $totalMarked = 0;
        foreach ($sets as $index => $set) {
            if ($index > 0) {
                // Stay below the Rebrickable rate limit
                sleep(1);
            }

            try {
                $rows = $this->rebrickableClient->getPartsBySetId($set->getBaseNumber())['results'] ?? [];
            } catch (\Throwable $e) {
                $io->error(sprintf('%s: could not fetch parts (%s)', $set->getNumber(), $e->getMessage()));
                continue;
            }

            $marked = $this->syncSet($set, $rows, $io);
            $totalMarked += $marked;
            $io->writeln(sprintf('%s: %d spare part(s) marked', $set->getNumber(), $marked));
        }

        if ($dryRun) {
            $io->note('Dry run, nothing saved.');
            return Command::SUCCESS;
        }

        $this->entityManager->flush();
        $io->success(sprintf('%d spare part(s) marked in %d set(s).', $totalMarked, count($sets)));

        return Command::SUCCESS;
    }

    private function syncSet(Set $set, array $rows, SymfonyStyle $io): int
    {
        $spareRows = [];
        $regularKeys = [];
        foreach ($rows as $row) {
            $key = $row['part']['part_num'] . '-' . $row['color']['id'];
            if ($row['is_spare']) {
                $spareRows[$key] = (int) $row['quantity'];
            } else {
                $regularKeys[$key] = true;
            }
        }

        /** @var array<string, SetPart[]> $setPartsByKey */
        $setPartsByKey = [];
        foreach ($set->getSetParts() as $setPart) {
            $key = $setPart->getPart()->getPartNumber() . '-' . $setPart->getColor()->getId();
            $setPartsByKey[$key][] = $setPart;
        }

        $marked = 0;
        foreach ($spareRows as $key => $spareQuantity) {
            $candidates = $setPartsByKey[$key] ?? [];

            foreach ($candidates as $candidate) {
                if ($candidate->isSpare()) {
                    continue 2;
                }
            }

            // Only a single row while Rebrickable also has a regular row:
            // that row is the regular part, the spare was never imported.
            if (count($candidates) < 2 && isset($regularKeys[$key])) {
                if (count($candidates) === 1) {
                    $io->writeln(sprintf('  %s: spare %s not imported, skipped', $set->getNumber(), $key));
                }
                continue;
            }

            $spare = $this->pickSpareCandidate($candidates, $spareQuantity);
            if ($spare !== null) {
                $spare->setIsSpare(true);
                $marked++;
            }
        }

        return $marked;
    }

    /**
     * Prefers a row with the spare quantity and without user part states,
     * so missing/damaged markings stay on the regular part.
     *
     * @param SetPart[] $candidates
     */
    private function pickSpareCandidate(array $candidates, int $spareQuantity): ?SetPart
    {
        if (empty($candidates)) {
            return null;
        }

        $userSetPartRepository = $this->entityManager->getRepository(UserSetPart::class);
        usort($candidates, function (SetPart $a, SetPart $b) use ($spareQuantity, $userSetPartRepository) {
            $score = fn(SetPart $p) =>
                ($p->getQuantity() === $spareQuantity ? 0 : 2)
                + ($userSetPartRepository->count(['setPart' => $p]) === 0 ? 0 : 1);

            return $score($a) <=> $score($b);
        });

        return $candidates[0];
    }
}
