<?php

declare(strict_types=1);

use App\Application\Dto\OrderCompletedMessage;
use App\Application\Handler\OrderCompletedConsumer;
use App\Domain\Model\Payout;
use App\Domain\Model\PayoutStatus;
use App\Domain\ValueObject\OrderId;

it('creates a payout when order.completed is received', function (): void {
    $orderId = OrderId::generate()->toString();

    app(OrderCompletedConsumer::class)->handle(new OrderCompletedMessage(
        orderId: $orderId,
        sellerId: 'seller-consumer',
        amount: 1500,
        currency: 'RUB',
    ));

    $payout = $this->em->getRepository(Payout::class)
        ->findOneBy(['orderId' => OrderId::fromString($orderId)]);

    expect($payout)->not->toBeNull()
        ->and($payout->status())->toBe(PayoutStatus::COMPLETED)
        ->and($payout->amount()->amount())->toBe(1350)
        ->and($payout->platformFee()->amount())->toBe(150);
});
