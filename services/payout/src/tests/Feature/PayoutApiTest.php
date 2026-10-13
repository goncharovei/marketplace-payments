<?php

declare(strict_types=1);

use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\PayoutId;

it('creates a payout via API', function (): void {
    $response = $this->postJson('/api/v1/payouts', [
        'orderId' => OrderId::generate()->toString(),
        'sellerId' => 'seller-api-1',
        'amount' => 2500,
        'currency' => 'RUB',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['payoutId', 'status'])
        ->assertJson(['status' => 'completed']);
});

it('returns validation error when amount is missing', function (): void {
    $response = $this->postJson('/api/v1/payouts', [
        'orderId' => OrderId::generate()->toString(),
        'sellerId' => 'seller-api-1',
        'currency' => 'RUB',
    ]);

    $response->assertStatus(422);
});

it('shows a payout by id', function (): void {
    $create = $this->postJson('/api/v1/payouts', [
        'orderId' => OrderId::generate()->toString(),
        'sellerId' => 'seller-api-show',
        'amount' => 2500,
        'currency' => 'RUB',
    ]);
    $payoutId = $create->json('payoutId');

    $response = $this->getJson("/api/v1/payouts/{$payoutId}");

    $response->assertStatus(200)->assertJson([
        'id' => $payoutId,
        'sellerId' => 'seller-api-show',
        'status' => 'completed',
        'amount' => 2250,
        'platformFee' => 250,
        'currency' => 'RUB',
        'method' => 'bank_transfer',
        'retryCount' => 0,
    ]);
});

it('returns 404 for non-existent payout', function (): void {
    $fakeId = PayoutId::generate()->toString();

    $this->getJson("/api/v1/payouts/{$fakeId}")->assertStatus(404);
});

it('shows a payout by order id', function (): void {
    $orderId = OrderId::generate()->toString();

    $create = $this->postJson('/api/v1/payouts', [
        'orderId' => $orderId,
        'sellerId' => 'seller-api-by-order',
        'amount' => 1000,
        'currency' => 'RUB',
    ]);
    $payoutId = $create->json('payoutId');

    $response = $this->getJson("/api/v1/payouts/by-order/{$orderId}");

    $response->assertStatus(200)->assertJson([
        'id' => $payoutId,
        'orderId' => $orderId,
    ]);
});

it('returns 404 when no payout exists for the order', function (): void {
    $orderId = OrderId::generate()->toString();

    $this->getJson("/api/v1/payouts/by-order/{$orderId}")->assertStatus(404);
});

it('returns seller balance after a payout', function (): void {
    $this->postJson('/api/v1/payouts', [
        'orderId' => OrderId::generate()->toString(),
        'sellerId' => 'seller-api-balance',
        'amount' => 2500,
        'currency' => 'RUB',
    ]);

    $response = $this->getJson('/api/v1/sellers/seller-api-balance/balance');

    $response->assertStatus(200)->assertJson([
        'sellerId' => 'seller-api-balance',
        'available' => 0,
        'reserved' => 0,
        'debt' => 0,
        'currency' => 'RUB',
    ]);
});

it('returns zeroed balance for a seller with no history', function (): void {
    $response = $this->getJson('/api/v1/sellers/seller-no-history/balance');

    $response->assertStatus(200)->assertJson([
        'sellerId' => 'seller-no-history',
        'available' => 0,
        'reserved' => 0,
        'debt' => 0,
    ]);
});

it('is idempotent for the same order', function (): void {
    $orderId = OrderId::generate()->toString();

    $first = $this->postJson('/api/v1/payouts', [
        'orderId' => $orderId,
        'sellerId' => 'seller-api-idempotent',
        'amount' => 1000,
        'currency' => 'RUB',
    ]);
    $second = $this->postJson('/api/v1/payouts', [
        'orderId' => $orderId,
        'sellerId' => 'seller-api-idempotent',
        'amount' => 1000,
        'currency' => 'RUB',
    ]);

    expect($second->json('payoutId'))->toBe($first->json('payoutId'));
});
