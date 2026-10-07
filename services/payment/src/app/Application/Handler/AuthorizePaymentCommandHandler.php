<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Command\AuthorizePaymentCommand;
use App\Domain\Exception\PaymentNotFoundException;
use App\Domain\Repository\PaymentRepository;
use App\Domain\ValueObject\ExternalTransactionId;
use App\Domain\ValueObject\PaymentId;
use Ecotone\Modelling\Attribute\CommandHandler;

final readonly class AuthorizePaymentCommandHandler
{
    public function __construct(
        private PaymentRepository $payments,
    ) {}

    #[CommandHandler]
    public function handle(AuthorizePaymentCommand $command): void
    {
        $paymentId = PaymentId::fromString($command->paymentId);
        $payment = $this->payments->findById($paymentId);

        if ($payment === null) {
            throw PaymentNotFoundException::withPaymentId($paymentId);
        }

        $payment->authorize(
            ExternalTransactionId::fromString($command->externalTransactionId),
        );

        $this->payments->save($payment);
    }
}
