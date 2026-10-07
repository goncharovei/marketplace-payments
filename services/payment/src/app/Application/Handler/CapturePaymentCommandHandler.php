<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Command\CapturePaymentCommand;
use App\Domain\Exception\PaymentNotFoundException;
use App\Domain\Repository\PaymentRepository;
use App\Domain\ValueObject\PaymentId;
use Ecotone\Modelling\Attribute\CommandHandler;

final readonly class CapturePaymentCommandHandler
{
    public function __construct(
        private PaymentRepository $payments,
    ) {}

    #[CommandHandler]
    public function handle(CapturePaymentCommand $command): void
    {
        $paymentId = PaymentId::fromString($command->paymentId);
        $payment = $this->payments->findById($paymentId);

        if ($payment === null) {
            throw PaymentNotFoundException::withId($paymentId);
        }

        $payment->capture();
        $this->payments->save($payment);
    }
}
