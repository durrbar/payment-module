<?php

namespace Modules\Payment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'tran_id' => 'required|string',
            'provider' => [
                'string',
                Rule::in(array_keys(config('payment.providers', [])))
            ]
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'provider.in' => "Driver for provider ':input' is not defined in the configuration.",
        ];
    }

}
