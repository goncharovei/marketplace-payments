<?php

declare(strict_types=1);

use App\Application\Command\AuthorizePaymentCommand;
use App\Application\Command\CapturePaymentCommand;
use App\Application\Command\InitiatePaymentCommand;
use App\Application\Command\RefundPaymentCommand;
use App\Domain\Exception\PaymentNotFoundException;
use App\Domain\Model\Payment;
use App\Domain\Model\PaymentStatus;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\PaymentId;
use Ecotone\Modelling\CommandBus;

function initiatePayment(int $amount = 1000): PaymentId
{
    return app(CommandBus::class)->send(new InitiatePaymentCommand(
        orderId: OrderId::generate()->toString(),
        buyerId: 'buyer-1',
        amount: $amount,
        currency: 'RUB',
        method: 'card',
    ));
}

it('runs full payment lifecycle: initiated → authorized → captured → refunded', function (): void {
    $commandBus = app(CommandBus::class);

    $paymentId = initiatePayment(1000);

    $commandBus->send(new AuthorizePaymentCommand(
        paymentId: $paymentId->toString(),
        externalTransactionId: 'ext-abc-123',
    ));

    $commandBus->send(new CapturePaymentCommand($paymentId->toString()));

    $commandBus->send(new RefundPaymentCommand(
        paymentId: $paymentId->toString(),
        amount: 1000,
        currency: 'RUB',
        reason: 'Customer request',
    ));

    $payment = $this->em->find(Payment::class, $paymentId);

    expect($payment->status())->toBe(PaymentStatus::REFUNDED)
        ->and($payment->refundedAmount()->amount())->toBe(1000)
        ->and($payment->refunds())->toHaveCount(1);
});

it('partially refunds a payment', function (): void {
    $commandBus = app(CommandBus::class);

    $paymentId = initiatePayment(1000);

    $commandBus->send(new AuthorizePaymentCommand(
        paymentId: $paymentId->toString(),
        externalTransactionId: 'ext-abc-456',
    ));
    $commandBus->send(new CapturePaymentCommand($paymentId->toString()));
    $commandBus->send(new RefundPaymentCommand(
        paymentId: $paymentId->toString(),
        amount: 300,
        currency: 'RUB',
        reason: 'Partial refund',
    ));

    $payment = $this->em->find(Payment::class, $paymentId);

    expect($payment->status())->toBe(PaymentStatus::PARTIALLY_REFUNDED)
        ->and($payment->refundedAmount()->amount())->toBe(300);
});

it('throws when authorizing a non-existent payment', function (): void {
    $commandBus = app(CommandBus::class);

    expect(fn () => $commandBus->send(new AuthorizePaymentCommand(
        paymentId: PaymentId::generate()->toString(),
        externalTransactionId: 'ext-xyz',
    )))->toThrow(PaymentNotFoundException::class);
});

it('throws when capturing a payment that was not authorized', function (): void {
    $commandBus = app(CommandBus::class);

    $paymentId = initiatePayment(1000);

    expect(fn () => $commandBus->send(new CapturePaymentCommand(
        paymentId: $paymentId->toString(),
    )))->toThrow(DomainException::class);
});
