<?php

declare(strict_types=1);

use App\Domain\ValueObject\OrderId;

it('creates an order via API', function (): void {
    $response = $this->postJson('/api/v1/orders', [
        'buyerId' => 'buyer-1',
        'sellerId' => 'seller-1',
        'items' => [
            ['productId' => 'product-1', 'quantity' => 2, 'amount' => 500, 'currency' => 'RUB'],
        ],
    ]);

    $response->assertStatus(201)->assertJsonStructure(['orderId']);
});

it('returns validation error when items are missing', function (): void {
    $response = $this->postJson('/api/v1/orders', [
        'buyerId' => 'buyer-1',
        'sellerId' => 'seller-1',
        'items' => [],
    ]);

    $response->assertStatus(422);
});

it('shows an order by id', function (): void {
    $createResponse = $this->postJson('/api/v1/orders', [
        'buyerId' => 'buyer-1',
        'sellerId' => 'seller-1',
        'items' => [
            ['productId' => 'product-1', 'quantity' => 1, 'amount' => 1000, 'currency' => 'RUB'],
        ],
    ]);

    $orderId = $createResponse->json('orderId');

    $response = $this->getJson("/api/v1/orders/{$orderId}");

    $response->assertStatus(200)
        ->assertJson([
            'id' => $orderId,
            'buyerId' => 'buyer-1',
            'sellerId' => 'seller-1',
            'status' => 'created',
            'totalAmount' => 1000,
            'currency' => 'RUB',
        ]);
});

it('returns 404 for non-existent order', function (): void {
    $fakeId = OrderId::generate()->toString();

    $response = $this->getJson("/api/v1/orders/{$fakeId}");

    $response->assertStatus(404);
});

it('runs full lifecycle via API: create → pay → paid', function (): void {
    $createResponse = $this->postJson('/api/v1/orders', [
        'buyerId' => 'buyer-1',
        'sellerId' => 'seller-1',
        'items' => [
            ['productId' => 'product-1', 'quantity' => 1, 'amount' => 1000, 'currency' => 'RUB'],
        ],
    ]);
    $orderId = $createResponse->json('orderId');

    $this->postJson("/api/v1/orders/{$orderId}/pay")->assertStatus(200);
    $this->postJson("/api/v1/orders/{$orderId}/paid")->assertStatus(200);

    $showResponse = $this->getJson("/api/v1/orders/{$orderId}");
    $showResponse->assertJson(['status' => 'paid']);
});

it('cancels an order via API', function (): void {
    $createResponse = $this->postJson('/api/v1/orders', [
        'buyerId' => 'buyer-1',
        'sellerId' => 'seller-1',
        'items' => [
            ['productId' => 'product-1', 'quantity' => 1, 'amount' => 1000, 'currency' => 'RUB'],
        ],
    ]);
    $orderId = $createResponse->json('orderId');

    $this->postJson("/api/v1/orders/{$orderId}/cancel", [
        'reason' => 'Test cancellation',
    ])->assertStatus(200);

    $showResponse = $this->getJson("/api/v1/orders/{$orderId}");
    $showResponse->assertJson(['status' => 'cancelled']);
});
