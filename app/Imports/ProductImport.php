<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class ProductImport implements
    ToModel,
    WithHeadingRow,
    WithChunkReading,
    WithValidation,
    SkipsOnFailure,
    SkipsEmptyRows
{
    use SkipsFailures;

    public int $created = 0;
    public int $updated = 0;

    public function model(array $row)
    {
        $product = Product::firstOrNew([
            'name' => trim((string) $row['name']),
            'category_id' => (int) $row['category_id'],
        ]);

        if ($product->exists) {
            $this->updated++;
        } else {
            $this->created++;
        }

        $product->price = (int) $row['price'];

        return $product;
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function rules(): array
    {
        return [
            '*.name' => [
                'required',
                'string',
                'max:255',
            ],
            '*.category_id' => [
                'required',
                'integer',
                'exists:categories,id',
            ],
            '*.price' => [
                'required',
                'integer',
                'min:0',
            ],
        ];
    }
}
