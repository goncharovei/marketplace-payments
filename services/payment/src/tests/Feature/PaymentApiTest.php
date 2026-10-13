<?php

declare(strict_types=1);

use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\PaymentId;

it('initiates a payment via API', function (): void {
    $orderId = OrderId::generate()->toString();

    $response = $this->postJson('/api/v1/payments', [
        'orderId' => $orderId,
        'buyerId' => 'buyer-1',
        'amount' => 1000,
        'currency' => 'RUB',
        'method' => 'card',
    ]);

    $response->assertStatus(201)->assertJsonStructure(['paymentId']);
});

it('returns validation error when method is invalid', function (): void {
    $response = $this->postJson('/api/v1/payments', [
        'orderId' => OrderId::generate()->toString(),
        'buyerId' => 'buyer-1',
        'amount' => 1000,
        'currency' => 'RUB',
        'method' => 'crypto',
    ]);

    $response->assertStatus(422);
});

it('shows a payment by id', function (): void {
    $create = $this->postJson('/api/v1/payments', [
        'orderId' => OrderId::generate()->toString(),
        'buyerId' => 'buyer-1',
        'amount' => 1000,
        'currency' => 'RUB',
        'method' => 'card',
    ]);
    $paymentId = $create->json('paymentId');

    $response = $this->getJson("/api/v1/payments/{$paymentId}");

    $response->assertStatus(200)->assertJson([
        'id' => $paymentId,
        'buyerId' => 'buyer-1',
        'status' => 'initiated',
        'amount' => 1000,
        'currency' => 'RUB',
        'refundedAmount' => 0,
        'method' => 'card',
    ]);
});

it('returns 404 for non-existent payment', function (): void {
    $fakeId = PaymentId::generate()->toString();

    $this->getJson("/api/v1/payments/{$fakeId}")->assertStatus(404);
});

it('shows a payment by order id', function (): void {
    $orderId = OrderId::generate()->toString();

    $create = $this->postJson('/api/v1/payments', [
        'orderId' => $orderId,
        'buyerId' => 'buyer-1',
        'amount' => 1000,
        'currency' => 'RUB',
        'method' => 'card',
    ]);
    $paymentId = $create->json('paymentId');

    $response = $this->getJson("/api/v1/payments/by-order/{$orderId}");

    $response->assertStatus(200)->assertJson([
        'id' => $paymentId,
        'orderId' => $orderId,
    ]);
});

it('returns 404 when no payment exists for the order', function (): void {
    $orderId = OrderId::generate()->toString();

    $this->getJson("/api/v1/payments/by-order/{$orderId}")->assertStatus(404);
});

it('runs full payment lifecycle: initiate → authorize → capture', function (): void {
    $create = $this->postJson('/api/v1/payments', [
        'orderId' => OrderId::generate()->toString(),
        'buyerId' => 'buyer-1',
        'amount' => 1000,
        'currency' => 'RUB',
        'method' => 'card',
    ]);
    $paymentId = $create->json('paymentId');

    $this->postJson("/api/v1/payments/{$paymentId}/authorize", [
        'externalTransactionId' => 'ext-test-123',
    ])->assertStatus(200);

    $this->postJson("/api/v1/payments/{$paymentId}/capture")
        ->assertStatus(200);

    $show = $this->getJson("/api/v1/payments/{$paymentId}");
    $show->assertJson(['status' => 'captured']);
});

it('refunds a captured payment partially', function (): void {
    $create = $this->postJson('/api/v1/payments', [
        'orderId' => OrderId::generate()->toString(),
        'buyerId' => 'buyer-1',
        'amount' => 1000,
        'currency' => 'RUB',
        'method' => 'card',
    ]);
    $paymentId = $create->json('paymentId');

    $this->postJson("/api/v1/payments/{$paymentId}/authorize", [
        'externalTransactionId' => 'ext-test-refund',
    ])->assertStatus(200);

    $this->postJson("/api/v1/payments/{$paymentId}/capture")
        ->assertStatus(200);

    $this->postJson("/api/v1/payments/{$paymentId}/refund", [
        'amount' => 400,
        'currency' => 'RUB',
        'reason' => 'Partial refund',
    ])->assertStatus(200);

    $show = $this->getJson("/api/v1/payments/{$paymentId}");
    $show->assertJson([
        'status' => 'partially_refunded',
        'refundedAmount' => 400,
    ]);
});

it('rejects refund larger than captured amount', function (): void {
    $create = $this->postJson('/api/v1/payments', [
        'orderId' => OrderId::generate()->toString(),
        'buyerId' => 'buyer-1',
        'amount' => 1000,
        'currency' => 'RUB',
        'method' => 'card',
    ]);
    $paymentId = $create->json('paymentId');

    $this->postJson("/api/v1/payments/{$paymentId}/authorize", [
        'externalTransactionId' => 'ext-test-over',
    ])->assertStatus(200);

    $this->postJson("/api/v1/payments/{$paymentId}/capture")
        ->assertStatus(200);

    $this->postJson("/api/v1/payments/{$paymentId}/refund", [
        'amount' => 5000,
        'currency' => 'RUB',
        'reason' => 'Too much',
    ])->assertStatus(422);
});
