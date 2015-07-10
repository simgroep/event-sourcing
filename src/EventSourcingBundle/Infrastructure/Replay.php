<?php

namespace Simgroep\EventSourcing\EventSourcingBundle\Infrastructure;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Serializer\SerializerInterface;
use Doctrine\DBAL\Connection;

class Replay
{
    private $connection;
    private $serializerMetadata;
    private $serializerPayload;
    private $tableName;
    private $loadStatement;

    /**
     * @param Connection $connection
     * @param SerializerInterface $serializer
     * @param string $tableName
     */
    public function __construct(
        Connection $connection,
        SerializerInterface  $serializerMetadata,
        SerializerInterface $serializerPayload,
        $tableName = 'events')
    {
        $this->connection           = $connection;
        $this->serializerMetadata   = $serializerMetadata;
        $this->serializerPayload    = $serializerPayload;
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
            $this->serializerMetadata->deserialize($metadata, $metadata['payload']),
            $this->serializerPayload->deserialize($payload, $payload['payload']),
            DateTime::fromString($row['recorded_on'])
        );
    }
}