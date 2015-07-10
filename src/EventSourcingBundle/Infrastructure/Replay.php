<?php

namespace Simgroep\EventSourcing\EventSourcingBundle\Infrastructure;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Spray\Serializer\SerializerInterface;
use Doctrine\DBAL\Connection;

class Replay
{
    private $connection;
    private $serializer;
    private $encryptionSerializer;
    private $tableName;
    private $loadStatement;

    /**
     * @param Connection $connection
     * @param SerializerInterface $serializer
     * @param string $tableName
     */
    public function __construct(
        Connection $connection,
        SerializerInterface $serializer,
        $encryptionSerializer,
        $tableName = 'events')
    {
        $this->connection           = $connection;
        $this->serializer           = $serializer;
        $this->encryptionSerializer = $encryptionSerializer;
        $this->tableName            = $tableName;
    }

    /**
     * @param $callback
     */
    public function replay($callback)
    {
        $statement = $this->prepareLoadStatement();
        $statement->execute();

        while ($row = $statement->fetch()) {
            $callback($this->deserializeEvent($row));
        }

    }

    /**
     * @return \Doctrine\DBAL\Driver\Statement
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function prepareLoadStatement()
    {
        if (null === $this->loadStatement) {
            $query = 'SELECT uuid, playhead, metadata, payload, recorded_on
                FROM ' . $this->tableName . '
                ORDER BY id ASC';
            $this->loadStatement = $this->connection->prepare($query);
        }
        return $this->loadStatement;
    }

    /**
     * @param $row
     * @return DomainMessage
     */
    protected function deserializeEvent($row)
    {
        $metadata = json_decode($row['metadata'], true);
        $payload  = json_decode($row['payload'], true);

        return new DomainMessage(
            $row['uuid'],
            $row['playhead'],
            $this->serializer->deserialize($metadata, $metadata['payload']),
            $this->encryptionSerializer->deserialize($payload, $payload['payload']),
            DateTime::fromString($row['recorded_on'])
        );
    }
}