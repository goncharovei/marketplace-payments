<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Command\RefundPaymentCommand;
use App\Domain\Exception\PaymentNotFoundException;
use App\Domain\Repository\PaymentRepository;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\PaymentId;
use Ecotone\Modelling\Attribute\CommandHandler;

final readonly class RefundPaymentCommandHandler
{
    public function __construct(
        private PaymentRepository $payments,
    ) {}

    #[CommandHandler]
    public function handle(RefundPaymentCommand $command): void
    {
        $paymentId = PaymentId::fromString($command->paymentId);
        $payment = $this->payments->findById($paymentId);

        if ($payment === null) {
            throw PaymentNotFoundException::withPaymentId($paymentId);
        }

        $payment->refund(
            Money::of($command->amount, $command->currency),
            $command->reason,
        );

        $this->payments->save($payment);
    }
}
