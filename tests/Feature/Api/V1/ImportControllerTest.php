<?php

namespace Tests\Feature\Api\V1;

use App\Domains\Brand\Models\Brand;
use App\Domains\Category\Models\Category;
use App\Domains\Transaction\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Storage::fake('local');
    }

    // ==================== CSV Import Tests ====================

    public function test_it_requires_authentication_for_csv_import(): void
    {
        $file = UploadedFile::fake()->create('test.csv', 'text/csv');

        $response = $this->postJson('/api/v1/import/csv', [
            'file' => $file,
        ]);

        $response->assertStatus(401);
    }

    public function test_it_validates_csv_file_is_required(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/import/csv', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_it_validates_csv_file_type(): void
    {
        $file = UploadedFile::fake()->create('test.pdf', 'application/pdf');

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/import/csv', [
                'file' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_it_imports_transactions_from_csv(): void
    {
        $csvContent = "Date,Description,Amount,Category,Brand\n";
        $csvContent .= "2024-01-15,Grocery shopping,-45.67,Food,Walmart\n";
        $csvContent .= "2024-01-16,Gas refill,-35.00,Transport,Shell\n";

        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/import/csv', [
                'file' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'imported' => 2,
            ]);

        $this->assertDatabaseCount('transactions', 2);
        $this->assertDatabaseHas('transactions', [
            'note' => 'Grocery shopping',
            'amount' => 45.67,
        ]);
        $this->assertDatabaseHas('transactions', [
            'note' => 'Gas refill',
            'amount' => 35.00,
        ]);
    }

    public function test_it_creates_brands_and_categories_from_csv(): void
    {
        $csvContent = "Date,Description,Amount,Category,Brand\n";
        $csvContent .= "2024-01-15,Test transaction,-100.00,TestCategory,TestBrand\n";

        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/import/csv', [
                'file' => $file,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('categories', [
            'name' => 'TestCategory',
            'type' => Category::EXPENSES,
        ]);

        $this->assertDatabaseHas('brands', [
            'name' => 'TestBrand',
        ]);
    }

    public function test_it_detects_income_from_positive_amounts(): void
    {
        $csvContent = "Date,Description,Amount,Category,Brand\n";
        $csvContent .= "2024-01-15,Salary,5000.00,Income,Employer\n";

        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/import/csv', [
                'file' => $file,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('categories', [
            'name' => 'Income',
            'type' => Category::INCOME,
        ]);

        $this->assertDatabaseHas('transactions', [
            'amount' => 5000.00,
            'note' => 'Salary',
        ]);
    }

    public function test_it_handles_alternative_column_headers(): void
    {
        $csvContent = "created_at,note,value,cat,merchant\n";
        $csvContent .= "2024-01-15,Test,-100.00,Food,Store\n";

        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/import/csv', [
                'file' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'imported' => 1,
            ]);
    }

    public function test_it_returns_errors_for_invalid_rows(): void
    {
        $csvContent = "Date,Description,Amount,Category,Brand\n";
        $csvContent .= "2024-01-15,Valid transaction,-45.67,Food,Walmart\n";
        $csvContent .= ",Missing date,-45.67,Food,Walmart\n";
        $csvContent .= "2024-01-16,,Missing description,-45.67,Food,Walmart\n";
        $csvContent .= "invalid-date,Test,-45.67,Food,Walmart\n";

        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/import/csv', [
                'file' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'imported' => 1,
            ])
            ->assertJsonCount(3, 'errors');
    }

    public function test_it_handles_empty_csv_file(): void
    {
        $csvContent = "Date,Description,Amount,Category,Brand\n";

        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/import/csv', [
                'file' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'imported' => 0,
                'errors' => [],
            ]);
    }

    // ==================== Excel Import Tests ====================

    public function test_it_requires_authentication_for_excel_import(): void
    {
        $file = UploadedFile::fake()->create('test.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $response = $this->postJson('/api/v1/import/excel', [
            'file' => $file,
        ]);

        $response->assertStatus(401);
    }

    public function test_it_validates_excel_file_type(): void
    {
        $file = UploadedFile::fake()->create('test.pdf', 'application/pdf');

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/import/excel', [
                'file' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_it_imports_transactions_from_excel(): void
    {
        // Create a simple Excel file using PhpSpreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Date');
        $sheet->setCellValue('B1', 'Description');
        $sheet->setCellValue('C1', 'Amount');
        $sheet->setCellValue('D1', 'Category');
        $sheet->setCellValue('E1', 'Brand');
        $sheet->setCellValue('A2', '2024-01-15');
        $sheet->setCellValue('B2', 'Excel transaction');
        $sheet->setCellValue('C2', -99.99);
        $sheet->setCellValue('D2', 'TestCat');
        $sheet->setCellValue('E2', 'TestBrand');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $tempPath = sys_get_temp_dir() . '/test_import.xlsx';
        $writer->save($tempPath);

        $file = new UploadedFile($tempPath, 'test.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/import/excel', [
                'file' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'imported' => 1,
            ]);

        $this->assertDatabaseHas('transactions', [
            'note' => 'Excel transaction',
            'amount' => 99.99,
        ]);

        unlink($tempPath);
    }

    // ==================== Template Download Tests ====================

    public function test_it_requires_authentication_for_template_download(): void
    {
        $response = $this->getJson('/api/v1/import/template');
        $response->assertStatus(401);
    }

    public function test_it_downloads_csv_template(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/api/v1/import/template?format=csv');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertHeader('Content-Disposition', 'attachment; filename=import_template.csv');
    }

    public function test_it_downloads_excel_template(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/api/v1/import/template?format=excel');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('Content-Disposition', 'attachment; filename=import_template.xlsx');
    }

    public function test_it_downloads_xlsx_template(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/api/v1/import/template?format=xlsx');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('Content-Disposition', 'attachment; filename=import_template.xlsx');
    }

    // ==================== Edge Case Tests ====================

    public function test_it_handles_various_amount_formats(): void
    {
        $csvContent = "Date,Description,Amount,Category,Brand\n";
        $csvContent .= "2024-01-15,Test1,-$45.67,Food,Walmart\n";
        $csvContent .= "2024-01-16,Test2,-1,234.56,Food,Walmart\n";
        $csvContent .= "2024-01-17,Test3,1000.00,Food,Walmart\n";

        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/import/csv', [
                'file' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'imported' => 3,
            ]);
    }

    public function test_it_handles_missing_optional_fields(): void
    {
        $csvContent = "Date,Description,Amount\n";
        $csvContent .= "2024-01-15,Test transaction,-45.67\n";

        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/import/csv', [
                'file' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'imported' => 1,
            ]);

        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_it_reuses_existing_brands_and_categories(): void
    {
        $category = Category::factory()->create(['name' => 'ExistingCat', 'type' => Category::EXPENSES]);
        $brand = Brand::factory()->create(['name' => 'ExistingBrand', 'category_id' => $category->id]);

        $csvContent = "Date,Description,Amount,Category,Brand\n";
        $csvContent .= "2024-01-15,Test,-45.67,ExistingCat,ExistingBrand\n";

        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/import/csv', [
                'file' => $file,
            ]);

        $response->assertStatus(200);

        // Should not create new brand or category
        $this->assertDatabaseCount('categories', 1);
        $this->assertDatabaseCount('brands', 1);
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_it_handles_zero_amount(): void
    {
        $csvContent = "Date,Description,Amount,Category,Brand\n";
        $csvContent .= "2024-01-15,Zero transaction,0.00,Food,Walmart\n";

        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/import/csv', [
                'file' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'imported' => 1,
            ]);

        $this->assertDatabaseHas('transactions', [
            'amount' => 0,
            'note' => 'Zero transaction',
        ]);
    }

    public function test_it_handles_large_csv_files(): void
    {
        $csvContent = "Date,Description,Amount,Category,Brand\n";
        for ($i = 0; $i < 100; $i++) {
            $csvContent .= "2024-01-" . (($i % 30) + 1) . ",Transaction {$i},-" . ($i + 1) . ".00,Food,Walmart\n";
        }

        $file = UploadedFile::fake()->createWithContent('large.csv', $csvContent);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/import/csv', [
                'file' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'imported' => 100,
            ]);

        $this->assertDatabaseCount('transactions', 100);
    }

    public function test_it_skips_empty_rows(): void
    {
        $csvContent = "Date,Description,Amount,Category,Brand\n";
        $csvContent .= "2024-01-15,Valid,-45.67,Food,Walmart\n";
        $csvContent .= "\n";
        $csvContent .= "2024-01-16,Another valid,-50.00,Food,Walmart\n";

        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/import/csv', [
                'file' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'imported' => 2,
            ]);
    }

    public function test_it_returns_partial_import_errors(): void
    {
        $csvContent = "Date,Description,Amount,Category,Brand\n";
        $csvContent .= "2024-01-15,Valid,-45.67,Food,Walmart\n";
        $csvContent .= ",Invalid row 1,-45.67,Food,Walmart\n";
        $csvContent .= "2024-01-16,Valid 2,-50.00,Food,Walmart\n";
        $csvContent .= "bad-date,Invalid row 2,-45.67,Food,Walmart\n";
        $csvContent .= "2024-01-17,Valid 3,-55.00,Food,Walmart\n";

        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/import/csv', [
                'file' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'imported' => 3,
            ])
            ->assertJsonCount(2, 'errors');
    }
}
