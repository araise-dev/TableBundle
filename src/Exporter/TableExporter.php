<?php

declare(strict_types=1);

namespace araise\TableBundle\Exporter;

use araise\CoreBundle\Manager\FormatterManager;
use araise\TableBundle\Table\Column;
use araise\TableBundle\Table\Table;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Contracts\Translation\TranslatorInterface;

class TableExporter implements ExporterInterface
{
    private array $reports = [];

    public function __construct(
        protected FormatterManager $formatterManager,
        protected TranslatorInterface $translator
    ) {
    }

    public function createSpreadsheet(Table $table, $spreadsheet = new Spreadsheet()): Spreadsheet
    {
        $sheet = $spreadsheet->getActiveSheet();

        $tableColumns = $table->getColumns();
        $this->createHeader($sheet, $tableColumns);

        $this->createTable($sheet, $table, $tableColumns);

        for ($index = 1, $indexMax = count($tableColumns); $index < $indexMax; ++$index) {
            $sheet->getColumnDimensionByColumn($index)->setAutoSize(true);
            $sheet->getColumnDimensionByColumn($index)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    /**
     * @param Column[] $tableColumns
     */
    protected function createHeader(Worksheet $sheet, array $tableColumns): void
    {
        foreach ($tableColumns as $column) {
            if ($column->getOption(Column::OPT_EXPORT)[Column::OPT_EXPORT_EXPORTABLE] === false) {
                continue;
            }

            $value = '';
            if ($column->getOption(Column::OPT_LABEL)) {
                $value = $this->translator->trans(
                    $column->getOption(Column::OPT_LABEL)
                );
            }

            $columnIndex = $sheet->getColumnDimensionByColumn(array_search($column->getIdentifier(), array_keys($tableColumns)) + 1)->getColumnIndex();
            $cell = $sheet->getCell($columnIndex.'1');
            $cell->setValueExplicit($value, DataType::TYPE_STRING2);
            $cell->getStyle()->getFont()->setBold(true);
        }
    }

    /**
     * @param Column[] $tableColumns
     */
    protected function createTable(Worksheet $sheet, Table $table, array $tableColumns): void
    {
        foreach ($table->getRows() as $rowIndex => $row) {
            $colIndex = 1;
            foreach ($tableColumns as $column) {
                if ($column->getOption(Column::OPT_EXPORT)[Column::OPT_EXPORT_EXPORTABLE] === false) {
                    continue;
                }
                $data = $column->getContent($row);
                if ($column->getOption(Column::OPT_FORMATTER)) {
                    if (is_callable($column->getOption(Column::OPT_FORMATTER))) {
                        $formatter = $column->getOption(Column::OPT_FORMATTER);
                        $data = $formatter($data);
                    } else {
                        $formatter = $this->formatterManager->getFormatter($column->getOption(Column::OPT_FORMATTER));
                        $formatter->processOptions($column->getOption(Column::OPT_FORMATTER_OPTIONS));
                        $data = $formatter->getString($data);
                    }
                }
                $columnIndex = $sheet->getColumnDimensionByColumn($colIndex)->getColumnIndex();
                $cell = $sheet->getCell($columnIndex.($rowIndex + 2));

                $cell->setValueExplicit($data, DataType::TYPE_STRING2);
                ++$colIndex;
            }
        }
    }
}
