<?php

declare(strict_types=1);

namespace App\Presentation\Http\Api\Request\V1;

use App\Domain\Model\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class InitiatePaymentRequest extends FormRequest
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
            'buyerId' => ['required', 'string', 'max:64'],
            'amount' => ['required', 'integer', 'min:1'],
            'currency' => ['required', 'string', 'size:3'],
            'method' => ['required', 'string', Rule::enum(PaymentMethod::class)],
        ];
    }
}
