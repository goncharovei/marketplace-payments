<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging;

use Ecotone\Laravel\Config\LaravelConnectionReference;
use Ecotone\Messaging\Attribute\ServiceContext;

final class LaravelDbalConnection
{
    #[ServiceContext]
    public function connection(): LaravelConnectionReference
    {
        return LaravelConnectionReference::defaultConnection('pgsql');
    }
}
