<?php
namespace App\Support;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class SafeValueBinder extends DefaultValueBinder
{
    public function bindValue(Cell $cell, mixed $value = null): bool
    {
        if (is_string($value) && !is_numeric($value) && preg_match('/^[=+\-@\t\r]/', $value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }

        return parent::bindValue($cell, $value);
    }
}