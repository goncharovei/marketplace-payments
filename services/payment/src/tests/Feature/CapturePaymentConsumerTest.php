<?php

declare(strict_types=1);

use App\Application\Command\InitiatePaymentCommand;
use App\Application\Dto\CapturePaymentMessage;
use App\Application\Handler\CapturePaymentConsumer;
use App\Domain\Model\Payment;
use App\Domain\Model\PaymentStatus;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\PaymentId;
use Ecotone\Modelling\CommandBus;

function initiatePaymentForOrder(string $orderId, int $amount = 1000): PaymentId
{
    return app(CommandBus::class)->send(new InitiatePaymentCommand(
        orderId: $orderId,
        buyerId: 'buyer-1',
        amount: $amount,
        currency: 'RUB',
        method: 'card',
    ));
}

it('captures a payment by order id', function (): void {
    $orderId = OrderId::generate()->toString();
    $paymentId = initiatePaymentForOrder($orderId, 2500);

    app(CapturePaymentConsumer::class)->handle(new CapturePaymentMessage(
        orderId: $orderId,
        amount: 2500,
        currency: 'RUB',
    ));

    $payment = $this->em->find(Payment::class, $paymentId);

    expect($payment->status())->toBe(PaymentStatus::CAPTURED)
        ->and($payment->externalId())->not->toBeNull();
});
