<?php
declare(strict_types=1);

namespace EonX\EasyWebhook\Stores;

use Doctrine\DBAL\Connection;
use EonX\EasyRandom\Interfaces\RandomGeneratorInterface;
use EonX\EasyWebhook\Interfaces\Stores\DataCleanerInterface;

abstract class AbstractDoctrineDbalStore extends AbstractStore
{
    public function __construct(
        RandomGeneratorInterface $random,
        protected Connection $conn,
        DataCleanerInterface $dataCleaner,
        protected string $table,
    ) {
        parent::__construct($random, $dataCleaner);
    }

    protected function existsInDb(string $id): bool
    {
        $sql = \sprintf('SELECT id FROM %s WHERE id = :id', $this->table);

        return \is_array($this->conn->fetchAssociative($sql, \compact('id')));
    }
}
