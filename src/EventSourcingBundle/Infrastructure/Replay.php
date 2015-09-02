<?php

namespace Simgroep\EventSourcing\EventSourcingBundle\Infrastructure;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Serializer\SerializerInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Statement;
use Generator;

class Replay
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var SerializerInterface
     */
    private $metadataSerializer;

    /**
     * @var SerializerInterface
     */
    private $payloadSerializer;

    /**
     * @var string
     */
    private $tableName;

    /**
     * @var Statement
     */
    private $replayStatement;

    /**
     * @var Statement
     */
    private $streamsStatement;

    /**
     * @param Connection $connection
     * @param SerializerInterface $metadataSerializer
     * @param SerializerInterface $payloadSerializer
     * @param string $tableName
     */
    public function __construct(
        Connection $connection,
        SerializerInterface  $metadataSerializer,
        SerializerInterface $payloadSerializer,
        $tableName = 'events')
    {
        $this->connection           = $connection;
        $this->metadataSerializer   = $metadataSerializer;
        $this->payloadSerializer    = $payloadSerializer;
        $this->tableName            = $tableName;
    }

    /**
     * Get a list of all streams.
     *
     * @return Generator
     */
    public function streams()
    {
        $statement = $this->prepareStreamsStatement();
        $statement->execute();

        while ($row = $statement->fetch()) {
            yield $row['uuid'];
        }
    }

    /**
     * Prepare the streams statement.
     *
     * @return Statement
     * @throws DBALException
     */
    private function prepareStreamsStatement()
    {
        if (null === $this->streamsStatement) {
            $query = sprintf(
                'SELECT DISTINCT uuid
                   FROM %s
                  ORDER BY id ASC',
                $this->tableName
            );
            $this->streamsStatement = $this->connection->prepare($query);
        }
        return $this->streamsStatement;
    }

    /**
     * Replay events from a specific stream.
     *
     * @param string $stream
     * @param callable $callback
     */
    public function replay($stream, callable $callback)
    {
        $statement = $this->prepareReplayStatement();
        $statement->bindValue(1, $stream);
        $statement->execute();

        while ($row = $statement->fetch()) {
            $callback($this->deserializeEvent($row));
        }
    }

    /**
     * @return Statement
     *
     * @throws DBALException
     */
    private function prepareReplayStatement()
    {
        if (null === $this->replayStatement) {
            $query = sprintf(
                'SELECT uuid, playhead, metadata, payload, recorded_on
                   FROM %s
                  WHERE uuid = ?
                  ORDER BY playhead ASC',
                $this->tableName
            );
            $this->replayStatement = $this->connection->prepare($query);
        }
        return $this->replayStatement;
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
            $this->metadataSerializer->deserialize($metadata, $metadata['payload']),
            $this->payloadSerializer->deserialize($payload, $payload['payload']),
            DateTime::fromString($row['recorded_on'])
        );
    }
}
