<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Command\CapturePaymentCommand;
use App\Domain\Exception\PaymentNotFoundException;
use App\Domain\Repository\PaymentRepository;
use App\Domain\ValueObject\ExternalTransactionId;
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
            throw PaymentNotFoundException::withPaymentId($paymentId);
        }

        // Emulate an external payment gateway response.
        // In production, this comes from the provider's webhook or API call.
        if ($payment->status()->canBeAuthorized()) {
            $payment->authorize(ExternalTransactionId::fromString(
                'ext-'.bin2hex(random_bytes(8)),
            ));
        }

        $payment->capture();
        $this->payments->save($payment);
    }
}
