<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Command\CapturePaymentCommand;
use App\Application\Dto\CapturePaymentMessage;
use App\Domain\Exception\PaymentNotFoundException;
use App\Domain\Repository\PaymentRepository;
use App\Domain\ValueObject\OrderId;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\Distributed;
use Ecotone\Modelling\CommandBus;
use Psr\Log\LoggerInterface;

final readonly class CapturePaymentConsumer
{
    public function __construct(
        private CommandBus $commandBus,
        private PaymentRepository $payments,
        private LoggerInterface $logger,
    ) {}

    #[Distributed]
    #[CommandHandler('payment.capture')]
    public function handle(CapturePaymentMessage $message): void
    {
        $this->logger->info('Received payment.capture', [
            'orderId' => $message->orderId,
            'amount' => $message->amount,
        ]);

        $orderId = OrderId::fromString($message->orderId);
        $payment = $this->payments->findByOrderId($orderId);

        if ($payment === null) {
            throw PaymentNotFoundException::withOrderId($orderId);
        }

        $this->commandBus->send(new CapturePaymentCommand(
            paymentId: $payment->id()->toString(),
        ));
    }
}
