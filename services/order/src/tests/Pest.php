<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

use Doctrine\ORM\EntityManagerInterface;
use Ecotone\Modelling\DistributedBus;
use Mockery\MockInterface;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->beforeEach(function (): void {
        // Start a Doctrine transaction before each Feature test.
        $this->em = app(EntityManagerInterface::class);
        $this->em->getConnection()->beginTransaction();

        // Prevent Feature tests from publishing to real RabbitMQ.
        // Tests that assert on distributed publishing override this mock
        // via $this->app->instance(DistributedBus::class, ...).
        /** @var DistributedBus&MockInterface $distributedBus */
        $distributedBus = Mockery::mock(DistributedBus::class);
        $distributedBus->shouldReceive('convertAndPublishEvent')->zeroOrMoreTimes();
        $distributedBus->shouldReceive('convertAndSendCommand')->zeroOrMoreTimes();

        $this->app->instance(DistributedBus::class, $distributedBus);
    })
    ->afterEach(function (): void {
        // Roll back all changes made during the test, keeping the DB clean.
        if (isset($this->em)) {
            $this->em->getConnection()->rollBack();
            $this->em->clear();
        }
    })
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});
