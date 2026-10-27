<?php

namespace App\Jobs;
use App\Models\Product;
use App\Models\ImportError;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
class ProcessProductImport implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 60];

    public int $timeout = 60;

    public function __construct(private string $csvPath) {}

public function handle(): void
{
    $lines = file(Storage::path($this->csvPath), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $rows = array_map('str_getcsv', $lines);
    $header = array_shift($rows);

    foreach ($rows as $index => $row) {
        $rowNumber = $index + 2;

        if (count($row) !== count($header)) {
            ImportError::create([
                'row_number' => $rowNumber,
                'error_message' => 'Jumlah kolom tidak sesuai dengan header CSV.',
            ]);

            continue;
        }

        $validator = Validator::make(array_combine($header, $row), [
            'name' => ['required', 'string'],
            'category_id' => ['required', 'exists:categories,id'],
            'price' => ['required', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            ImportError::create([
                'row_number' => $rowNumber,
                'error_message' => implode('; ', $validator->errors()->all()),
            ]);

            continue;
        }

        $data = $validator->validated();

        Product::updateOrCreate(
            ['name' => $data['name'], 'category_id' => $data['category_id']],
            ['price' => $data['price']],
        );
    }

    Storage::delete($this->csvPath);
}

}
