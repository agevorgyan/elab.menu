<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RenewSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->vendor !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subscription_plan_id' => 'required|integer|exists:subscription_plans,id',
            'period_months' => 'required|integer|in:1,3,6,12',
            'payment_method' => 'required|string|in:idram,telcell,fastshift,arca,stripe,bank_transfer,cash',
        ];
    }
}
