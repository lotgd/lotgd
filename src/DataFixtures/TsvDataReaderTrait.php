<?php
declare(strict_types=1);

namespace LotGD2\DataFixtures;

use Psr\Log\LoggerInterface;
use SplFileObject;

trait TsvDataReaderTrait
{
    private LoggerInterface $logger;

    /**
     * @return iterable<int, array<string, ?string>>
     */
    public function iterateTsvData(string $filename): iterable
    {
        $file = new SplFileObject($filename);
        $file->setFlags(SplFileObject::READ_CSV);
        $file->setCsvControl("\t", enclosure: "\"", escape: "\\");

        $header = null;
        foreach ($file as $row) {
            if (!$header) {
                $header = $row;
                continue;
            }

            // Reached end of file
            if (count($row) === 1) {
                break;
            }

            try {
                $row = array_combine($header, $row);
                $row = array_map('trim', $row);
                $row = array_map(fn($x) => $x === "NULL" ? NULL : $x, $row);
            } catch (\Throwable $e) {
                $this->logger->error($e->getMessage(), $row);
                continue;
            }

            yield $row;
        }
    }
}