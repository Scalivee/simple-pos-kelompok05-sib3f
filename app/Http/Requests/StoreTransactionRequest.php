<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
        ];
    }
    public function withValidator($validator)
{
    $validator->after(function ($validator) {
        $quantities = [];

        foreach ($this->items as $item) {
            $productId = $item['product_id'];
            $qty = $item['qty'];

            if (!isset($quantities[$productId])) {
                $quantities[$productId] = 0;
            }

            $quantities[$productId] += $qty;
        }

        foreach ($quantities as $productId => $totalQty) {
            $product = Product::find($productId);

            if ($product && $totalQty > $product->stock) {
                $validator->errors()->add(
                    'items',
                    "Stok {$product->name} tidak mencukupi. Stok tersedia: {$product->stock}, sedangkan jumlah yang diminta: {$totalQty}."
                );
            }
        }
    });
}
}