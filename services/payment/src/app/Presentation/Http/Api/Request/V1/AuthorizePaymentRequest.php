<?php

declare(strict_types=1);

namespace App\Presentation\Http\Api\Request\V1;

use Illuminate\Foundation\Http\FormRequest;

final class AuthorizePaymentRequest extends FormRequest
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
            'externalTransactionId' => ['required', 'string', 'max:255'],
        ];
    }
}
