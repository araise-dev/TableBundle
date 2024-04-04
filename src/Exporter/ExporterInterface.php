<?php

declare(strict_types=1);

namespace araise\TableBundle\Exporter;


use araise\TableBundle\Table\Table;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

interface ExporterInterface
{
    public function createSpreadsheet(Table $table, $spreadsheet = new Spreadsheet()): Spreadsheet;
}
