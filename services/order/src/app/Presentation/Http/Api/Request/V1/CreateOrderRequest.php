<?php

declare(strict_types=1);

namespace App\Presentation\Http\Api\Request\V1;

use Illuminate\Foundation\Http\FormRequest;

final class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'buyerId' => ['required', 'string', 'max:64'],
            'sellerId' => ['required', 'string', 'max:64'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.productId' => ['required', 'string', 'max:64'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.amount' => ['required', 'integer', 'min:1'],
            'items.*.currency' => ['required', 'string', 'size:3'],
        ];
    }
}
