<?php

namespace App\Http\Requests\Memo;

use App\Enums\PaymentMethod;
use App\Enums\ReturnConditionStatus;
use App\Enums\ReturnType;
use App\Enums\ReturnUnit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'retailer_id' => ['required', 'exists:retailer_shops,id'],
            'road_id' => ['required', 'exists:roads,id'],
            'vehicle_trip_id' => ['nullable', 'exists:vehicle_trips,id'],
            'memo_date' => ['required', 'date'],
            'custom_deduction' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.packaging_type' => ['nullable', 'string'],
            'returns' => ['nullable', 'array'],
            'returns.*.product_id' => ['required', 'exists:products,id'],
            'returns.*.return_type' => ['required', Rule::enum(ReturnType::class)],
            'returns.*.damage_type' => ['nullable', 'string'],
            'returns.*.return_unit' => ['nullable', Rule::enum(ReturnUnit::class)],
            'returns.*.sub_unit_quantity' => ['nullable', 'numeric', 'min:0.001'],
            'returns.*.condition_status' => ['nullable', Rule::enum(ReturnConditionStatus::class)],
            'returns.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'returns.*.unit_deduction_rate' => ['required', 'numeric', 'min:0'],
            'returns.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
