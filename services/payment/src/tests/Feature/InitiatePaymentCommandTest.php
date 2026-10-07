<?php

declare(strict_types=1);

use App\Application\Command\InitiatePaymentCommand;
use App\Domain\Model\Payment;
use App\Domain\Model\PaymentMethod;
use App\Domain\Model\PaymentStatus;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\PaymentId;
use Ecotone\Modelling\CommandBus;

it('initiates a payment via the command bus', function (): void {
    $commandBus = app(CommandBus::class);

    $paymentId = $commandBus->send(new InitiatePaymentCommand(
        orderId: OrderId::generate()->toString(),
        buyerId: 'buyer-1',
        amount: 1000,
        currency: 'RUB',
        method: 'card',
    ));

    expect($paymentId)->toBeInstanceOf(PaymentId::class);

    $payment = $this->em->find(Payment::class, $paymentId);

    expect($payment)->not->toBeNull()
        ->and($payment->status())->toBe(PaymentStatus::INITIATED)
        ->and($payment->amount()->amount())->toBe(1000)
        ->and($payment->amount()->currency())->toBe('RUB')
        ->and($payment->method())->toBe(PaymentMethod::CARD)
        ->and($payment->buyerId()->toString())->toBe('buyer-1');
});
