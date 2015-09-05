simgroep/event-sourcing
=======================

Provides several components on top of [broadway](https://github.com/qandidate-labs/broadway).

Concurrency
-----------

Updating an aggregate root at the same time may lead to concurrency problems. To
solve this we provide a _CommandGateway_ and a _LockingRepository_, inspired by 
the [Axon Framework](https://github.com/AxonFramework/AxonFramework).

The _LockingRepository_ will prevent access to the repository once load() is
called. When save() is called, the lock will be released. Trying to access the
repository while being locked will result in a _ConcurrencyException_.

The _CommandGateway_ will in turn catch the _ConcurrencyException_, and will try
do dispatch the command again until a specific condition is met (depends on the
used _Scheduler_ implementation), or throw the _ConcurrencyException_ otherwise.

Replaying Events
-----------
The EventSourcingBundle contains commands to make interactive replaying of events available after doing a few simple steps.

#### Mark projectors for replaying
The bundle needs to be aware which projectors are replayable and what there corresponding repositories are.

Projectors are tagged by 'projector.replayable' and the 'repository attribute' is used to define the repository.

For example:
```yml
sim.read_model.projector.municipality_hostname:
        class: SIM\Settings\ReadModel\MunicipalityHostnameProjector
        arguments: [@sim.read_model.repository.municipality_hostname]
        tags:
            - { name: broadway.domain.event_listener }
            - { name: projector.replayable, repository: sim.read_model.repository.municipality_hostname}
```

#### Test if your projectors are correctly tagged as replayable
After tagging your projectors you can test the outcome with the next command:
(don't forget to clear the symfony cache.)

```sh
vagrant@dev:/var/www/someproject-api$ php ./app/console  simgroep:eventsourcing:projectors:list

The following projectors are available for rebuilding:
- sim.read_model.repository.municipality_hostname
```
You can directly see if your projectors are marked for rebuilding their projections.

#### Replay time
Now it's time for the fun part   ...replaying!.
There are three forms of replaying:
 - non-interactive replaying (multi threaded)
 - replay a specific stream of events
 - interactive replaying

###### Non-interactive replaying
Non-interactive replaying is straight forward. 

```sh
php ./app/console simgroep:eventsourcing:events:replay --threads 4 all
```

 - You can define a specific projector (ex: php ./app/console simgroep:eventsourcing:events:replay municipality_hostname).
 - You can define multiple projectors (ex: php ./app/console simgroep:eventsourcing:events:replay 'projector1,projector2'). 
 - A third option is to define all projectors (ex: php ./app/console simgroep:eventsourcing:events:replay all).
 
With "--threads" you can define the number of threads. The maximum number of threads is the number of cpu cores * 2.


Note: "--threads" cannot be used with interactive mode or when replaying a specific stream

###### Replay a specific stream of events
If you want to replay only the events from a specific stream then 

```sh
php ./app/console simgroep:eventsourcing:events:replay --stream 00000000-0000-0000-0000-000000000000 all
```

###### Interactive replaying
When time travelling becomes important for debugging purposes then you can choose for interactive replaying of your event store.

Interactive replaying contains one extra argument (--interact) and a value which contains the stream id.

```sh
php ./app/console simgroep:eventsourcing:events:replay --interact 00000000-0000-0000-0000-000000000000 all
```

Every event of the event store will be replayed, if one those events contains the supplied stream id and hits one of the selected projectors then:

1. The event will be replayed first and the first projection will be updated.
2. The replaying will pause if gives you the option to continue or stop replaying.
3. If multiple projectors are affected by this event then the next projector will be updated and we will go back to step 2.