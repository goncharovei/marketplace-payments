<?php

declare(strict_types=1);

namespace App\Presentation\Http\Api\Request\V1;

use Illuminate\Foundation\Http\FormRequest;

final class CreatePayoutRequest extends FormRequest
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
            'orderId' => ['required', 'string', 'uuid'],
            'sellerId' => ['required', 'string', 'max:64'],
            'amount' => ['required', 'integer', 'min:1'],
            'currency' => ['required', 'string', 'size:3'],
        ];
    }
}
