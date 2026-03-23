<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class ImportSheet implements FromArray, WithTitle
{
    public function __construct(
        private array  $data,
        private string $sheetTitle = '',
    ) {}

    public function array(): array  { return $this->data; }
    public function title(): string { return $this->sheetTitle; }
}
