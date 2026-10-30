<?php

namespace Tests\Unit;

use App\Support\SafeValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\Attributes\DataProvider;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SafeValueBinderTest extends TestCase
{
    #[DataProvider('formulaLikeValues')]
    public function test_formula_like_text_is_stored_as_a_string(string $value): void
    {
        $cell = (new Spreadsheet())->getActiveSheet()->getCell('A1');

        (new SafeValueBinder())->bindValue($cell, $value);

        $this->assertSame(DataType::TYPE_STRING, $cell->getDataType());
        $this->assertSame(
            str_replace("\r", "\n", $value),
            $cell->getValue()
        );
        $this->assertFalse($cell->getDataType() === DataType::TYPE_FORMULA);
    }

    public static function formulaLikeValues(): array
    {
        return [
            'equals prefix' => ['=HYPERLINK("https://example.invalid")'],
            'plus prefix' => ['+cmd|/C calc'],
            'minus prefix' => ['-malicious-formula'],
            'at prefix' => ['@SUM(A1:A2)'],
            'tab prefix' => ["\t=1+1"],
            'carriage return prefix' => ["\r=1+1"],
        ];
    }

    public function test_laravel_excel_uses_the_safe_default_binder(): void
    {
        $this->assertSame(
            SafeValueBinder::class,
            config('excel.value_binder.default')
        );
    }

    public function test_formula_like_values_remain_text_after_xlsx_round_trip(): void
    {
        Storage::fake('local');
        Storage::disk('local')->makeDirectory('tests');

        $values = array_column(self::formulaLikeValues(), 0);
        $spreadsheet = new Spreadsheet();

        foreach ($values as $index => $value) {
            $cell = $spreadsheet->getActiveSheet()->getCell('A'.($index + 1));
            (new SafeValueBinder())->bindValue($cell, $value);
        }

        $path = Storage::disk('local')->path('tests/safe-value-binder.xlsx');
        (new Xlsx($spreadsheet))->save($path);

        $reloaded = IOFactory::load($path);

        foreach ($values as $index => $value) {
            $cell = $reloaded->getActiveSheet()->getCell('A'.($index + 1));

            $this->assertSame(
                DataType::TYPE_STRING,
                $cell->getDataType(),
                "Cell A".($index + 1)." must be stored as text."
            );
            $this->assertNotSame(DataType::TYPE_FORMULA, $cell->getDataType());
            $this->assertSame(
                str_replace("\r", "\n", $value),
                $cell->getValue()
            );
        }
    }
}