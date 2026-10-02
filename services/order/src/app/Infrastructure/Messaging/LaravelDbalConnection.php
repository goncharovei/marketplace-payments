<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging;

use Ecotone\Laravel\Config\LaravelConnectionReference;
use Ecotone\Messaging\Attribute\ServiceContext;

/**
 * Points Ecotone to the default Laravel DB connection (PostgreSQL).
 */
final class LaravelDbalConnection
{
    #[ServiceContext]
    public function connection(): LaravelConnectionReference
    {
        return LaravelConnectionReference::defaultConnection('pgsql');
    }
}
