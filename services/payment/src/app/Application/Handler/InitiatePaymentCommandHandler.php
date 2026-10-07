<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Command\InitiatePaymentCommand;
use App\Domain\Model\Payment;
use App\Domain\Model\PaymentMethod;
use App\Domain\Repository\PaymentRepository;
use App\Domain\ValueObject\BuyerId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\PaymentId;
use Ecotone\Modelling\Attribute\CommandHandler;

final readonly class InitiatePaymentCommandHandler
{
    public function __construct(
        private PaymentRepository $payments,
    ) {}

    #[CommandHandler]
    public function handle(InitiatePaymentCommand $command): PaymentId
    {
        $payment = Payment::initiate(
            $this->payments->nextIdentity(),
            OrderId::fromString($command->orderId),
            BuyerId::fromString($command->buyerId),
            Money::of($command->amount, $command->currency),
            PaymentMethod::from($command->method),
        );

        $this->payments->save($payment);

        return $payment->id();
    }
}
