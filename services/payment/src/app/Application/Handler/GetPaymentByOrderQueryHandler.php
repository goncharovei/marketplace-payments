<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Dto\PaymentView;
use App\Application\Query\GetPaymentByOrderQuery;
use App\Domain\Exception\PaymentNotFoundException;
use App\Domain\Model\Payment;
use App\Domain\Model\Refund;
use App\Domain\Repository\PaymentRepository;
use App\Domain\ValueObject\OrderId;
use Ecotone\Modelling\Attribute\QueryHandler;

final readonly class GetPaymentByOrderQueryHandler
{
    public function __construct(
        private PaymentRepository $payments,
    ) {}

    #[QueryHandler]
    public function handle(GetPaymentByOrderQuery $query): PaymentView
    {
        $orderId = OrderId::fromString($query->orderId);
        $payment = $this->payments->findByOrderId($orderId);

        if ($payment === null) {
            throw PaymentNotFoundException::withOrderId($orderId);
        }

        return $this->toView($payment);
    }

    private function toView(Payment $payment): PaymentView
    {
        $refunds = array_map(
            static fn (Refund $refund): array => [
                'id' => $refund->id()->toString(),
                'amount' => $refund->amount()->amount(),
                'currency' => $refund->amount()->currency(),
                'reason' => $refund->reason(),
                'createdAt' => $refund->createdAt()->format(DATE_ATOM),
            ],
            $payment->refunds(),
        );

        return new PaymentView(
            id: $payment->id()->toString(),
            orderId: $payment->orderId()->toString(),
            buyerId: $payment->buyerId()->toString(),
            status: $payment->status()->value,
            amount: $payment->amount()->amount(),
            currency: $payment->amount()->currency(),
            refundedAmount: $payment->refundedAmount()->amount(),
            method: $payment->method()->value,
            externalId: $payment->externalId()?->toString(),
            refunds: $refunds,
            createdAt: $payment->createdAt()->format(DATE_ATOM),
            updatedAt: $payment->updatedAt()->format(DATE_ATOM),
        );
    }
}
