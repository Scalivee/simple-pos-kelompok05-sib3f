<?php

namespace Tests\Feature;

use App\Exports\SalesReportExport;
use App\Models\Category;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ReportCategoryFilterTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    private Transaction $matchingTransaction;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin);

        $this->category = Category::forceCreate(['name' => 'Minuman']);
        $otherCategory = Category::forceCreate(['name' => 'Makanan']);
        $drink = Product::create(['name' => 'Kopi', 'category_id' => $this->category->id, 'price' => 10000]);
        $otherDrink = Product::create(['name' => 'Teh', 'category_id' => $this->category->id, 'price' => 10000]);
        $food = Product::create(['name' => 'Roti', 'category_id' => $otherCategory->id, 'price' => 10000]);

        // A mixed transaction with two matching products must only be counted once.
        $this->matchingTransaction = $this->sale($admin, [$drink, $otherDrink, $food], '2026-10-15');
        $this->sale($admin, [$food], '2026-10-16');
        $this->sale($admin, [$drink], '2026-09-15');
    }

    public function test_report_combines_category_and_date_filters_without_duplicate_transactions(): void
    {
        $this->get(route('reports.index', $this->filters()))
            ->assertOk()
            ->assertViewHas('totalPenjualan', 30000)
            ->assertViewHas('jumlahTransaksi', 1)
            ->assertViewHas('categoryId', $this->category->id)
            ->assertSee('Semua kategori')
            ->assertSee('category_id='.$this->category->id);

        $this->get(route('reports.index', array_merge($this->filters(), ['category_id' => ''])))
            ->assertOk()
            ->assertViewHas('totalPenjualan', 40000)
            ->assertViewHas('jumlahTransaksi', 2);

        $emptyCategory = Category::forceCreate(['name' => 'Kosong']);
        $this->get(route('reports.index', array_merge($this->filters(), ['category_id' => $emptyCategory->id])))
            ->assertOk()
            ->assertViewHas('totalPenjualan', 0)
            ->assertViewHas('jumlahTransaksi', 0);
    }

    public function test_csv_export_only_contains_transactions_matching_both_filters(): void
    {
        $response = $this->get(route('reports.export.csv', $this->filters()))->assertOk();
        $lines = preg_split('/\r?\n/', trim($response->streamedContent()));

        $this->assertCount(2, $lines);
        $this->assertSame('ID,Tanggal,Kasir,Total', $lines[0]);
        $row = str_getcsv($lines[1]);
        $this->assertSame((string) $this->matchingTransaction->id, $row[0]);
        $this->assertSame('30000', $row[3]);
    }

    public function test_excel_and_pdf_exports_use_the_same_category_filter(): void
    {
        Excel::fake();
        $this->get(route('reports.export.xlsx', $this->filters()))->assertOk();
        Excel::assertDownloaded('laporan-penjualan.xlsx', function (SalesReportExport $export) {
            return $export->query()->pluck('id')->all() === [$this->matchingTransaction->id];
        });

        $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        Pdf::shouldReceive('loadView')->once()->with('reports.pdf', \Mockery::on(function (array $data) {
            return $data['transactions']->pluck('id')->all() === [$this->matchingTransaction->id]
                && (int) $data['totalPenjualan'] === 30000
                && $data['jumlahTransaksi'] === 1
                && $data['categoryName'] === 'Minuman';
        }))->andReturn($pdf);
        $pdf->shouldReceive('download')->with('laporan-penjualan.pdf')->andReturn(response('PDF'));
        $this->get(route('reports.export.pdf', $this->filters()))->assertOk();
    }

    public function test_invalid_categories_are_rejected_on_report_and_exports(): void
    {
        foreach (['reports.index', 'reports.export.csv', 'reports.export.xlsx', 'reports.export.pdf'] as $route) {
            foreach (['invalid', 999999] as $categoryId) {
                $this->getJson(route($route, array_merge($this->filters(), ['category_id' => $categoryId])))
                    ->assertUnprocessable()
                    ->assertJsonValidationErrors('category_id');
            }
        }
    }

    private function filters(): array
    {
        return ['from' => '2026-10-01', 'to' => '2026-10-31', 'category_id' => $this->category->id];
    }

    private function sale(User $user, array $products, string $date): Transaction
    {
        $transaction = Transaction::create(['user_id' => $user->id, 'total' => count($products) * 10000]);
        $transaction->forceFill(['created_at' => $date.' 12:00:00'])->save();

        foreach ($products as $product) {
            $transaction->details()->create(['product_id' => $product->id, 'qty' => 1, 'subtotal' => 10000]);
        }

        return $transaction;
    }
}
