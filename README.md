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