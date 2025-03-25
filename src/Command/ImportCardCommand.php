<?php

namespace App\Command;

use App\Entity\Artist;
use App\Entity\Card;
use App\Repository\ArtistRepository;
use App\Repository\CardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'import:card',
    description: 'Import Magic The Gathering cards from CSV file',
)]
class ImportCardCommand extends Command
{
    private const BATCH_SIZE = 100;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface        $logger,
        private readonly CardRepository         $cardRepository,
        private readonly ArtistRepository       $artistRepository,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Limit the number of cards to import', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '2G');
        $io = new SymfonyStyle($input, $output);
        $filepath = __DIR__ . '/../../data/cards.csv';
        $limit = $input->getOption('limit') ? (int)$input->getOption('limit') : null;

        $start = microtime(true);
        $this->logger->info('Starting card import', ['file' => $filepath, 'limit' => $limit]);

        $handle = fopen($filepath, 'r');
        if ($handle === false) {
            $error = 'Failed to open file: ' . $filepath;
            $this->logger->error($error);
            $io->error($error);
            return Command::FAILURE;
        }

        $csvHeader = fgetcsv($handle);
        $uuidInDatabase = $this->cardRepository->getAllUuids();
        $this->logger->info('Found existing cards', ['count' => count($uuidInDatabase)]);

        // Count total lines for progress bar
        $totalLines = 0;
        while (!feof($handle)) {
            $totalLines += substr_count(fread($handle, 8192), "\n");
        }
        rewind($handle);
        fgetcsv($handle); // Skip header again

        $progressBar = new ProgressBar($output, $totalLines);
        $progressBar->setFormat('debug');
        $progressBar->start();

        $i = 0;
        $imported = 0;
        $skipped = 0;
        $artistsCreated = 0;
        $memoryStart = memory_get_usage();

        $artistCache = [];

        try {
            $this->entityManager->beginTransaction();

            while (($row = $this->readCSV($handle, $csvHeader)) !== false) {
                $i++;
                $progressBar->advance();

                if (!in_array($row['uuid'], $uuidInDatabase)) {
                    // Handle artist
                    $artistName = $row['artist'] ?? null;
                    $artist = null;

                    if ($artistName) {
                        if (isset($artistCache[$artistName])) {
                            $artist = $artistCache[$artistName];
                        } else {
                            $artist = $this->artistRepository->findOneBy(['name' => $artistName]);

                            if (!$artist) {
                                $artist = new Artist();
                                $artist->setName($artistName);
                                $artist->setArtistExternalId(md5($artistName)); // Utilisation d'un hash comme ID externe
                                $this->entityManager->persist($artist);
                                $artistsCreated++;

                                $this->logger->debug('Created new artist', ['name' => $artistName]);
                            }

                            $artistCache[$artistName] = $artist;
                        }
                    }

                    $this->addCard($row, $artist);
                    $imported++;
                } else {
                    $skipped++;
                }

                if ($i % self::BATCH_SIZE === 0) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();

                    // Restore artist cache after clear
                    foreach ($artistCache as $name => $artist) {
                        $artistCache[$name] = $this->entityManager->merge($artist);
                    }

                    $memoryUsage = memory_get_usage();
                    $this->logger->info('Import progress', [
                        'processed' => $i,
                        'imported' => $imported,
                        'skipped' => $skipped,
                        'artists_created' => $artistsCreated,
                        'memory_usage' => $this->formatBytes($memoryUsage - $memoryStart)
                    ]);

                    $this->entityManager->beginTransaction();
                }

                if ($limit && $i >= $limit) {
                    $this->logger->info('Import limit reached', ['limit' => $limit]);
                    break;
                }
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $this->logger->error('Import failed', [
                'error' => $e->getMessage(),
                'line' => $i
            ]);
            throw $e;
        } finally {
            fclose($handle);
        }

        $progressBar->finish();
        $end = microtime(true);
        $timeElapsed = $end - $start;
        $memoryPeak = memory_get_peak_usage() - $memoryStart;

        $summary = [
            'total_processed' => $i,
            'imported' => $imported,
            'skipped' => $skipped,
            'artists_created' => $artistsCreated,
            'time_elapsed' => round($timeElapsed, 2) . 's',
            'memory_peak' => $this->formatBytes($memoryPeak)
        ];

        $this->logger->info('Import completed', $summary);
        $io->newLine(2);
        $io->success(sprintf(
            'Processed %d cards in %.2f seconds (Imported: %d, Skipped: %d, Artists created: %d, Memory peak: %s)',
            $i,
            $timeElapsed,
            $imported,
            $skipped,
            $artistsCreated,
            $this->formatBytes($memoryPeak)
        ));

        return Command::SUCCESS;
    }

    private function readCSV(mixed $handle, array $csvHeader): array|false
    {
        $row = fgetcsv($handle);
        if ($row === false) {
            return false;
        }
        return array_combine($csvHeader, $row);
    }

    private function addCard(array $row, ?Artist $artist): void
    {
        $uuid = $row['uuid'];

        $card = new Card();
        $card->setUuid($uuid);
        $card->setManaValue($row['manaValue']);
        $card->setManaCost($row['manaCost']);
        $card->setName($row['name']);
        $card->setRarity($row['rarity']);
        $card->setSetCode($row['setCode']);
        $card->setSubtype($row['subtypes']);
        $card->setText($row['text']);
        $card->setType($row['type']);

        if ($artist) {
            $card->setArtist($artist);
        }

        $this->entityManager->persist($card);
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        return round($bytes / (1024 ** $pow), 2) . ' ' . $units[$pow];
    }
}
