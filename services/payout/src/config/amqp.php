<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | AMQP DSN
    |--------------------------------------------------------------------------
    |
    | Connection string for RabbitMQ. Built from environment variables.
    | Used by the Ecotone AMQP transport for publishing and consuming
    | distributed messages.
    |
    */
    'dsn' => sprintf(
        'amqp+lib://%s:%s@%s:%d/%s',
        env('RABBITMQ_USER', 'guest'),
        urlencode((string) env('RABBITMQ_PASSWORD', 'guest')),
        env('RABBITMQ_HOST', 'rabbitmq'),
        (int) env('RABBITMQ_PORT', 5672),
        urlencode((string) env('RABBITMQ_VHOST', '/')),
    ),
];
