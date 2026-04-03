<?php

namespace App\Exports;

use App\Models\Customer;
use App\Models\Operator;
use App\Models\ProcessType;
use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithGroupedHeadingRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StatsExport implements FromArray, WithMapping, WithHeadings, ShouldAutoSize, WithColumnFormatting, WithStyles, WithGroupedHeadingRow, WithHeadingRow
{

    public function __construct(public $records, public $columns)
    {
        // dd($this->records->toArray());
    }

    public function array(): array
    {
        return $this->records->toArray();
    }

    public function headings(): array
    {
        $head = [];
        foreach ($this->columns as $key => $value) {
            if ($value->getName() == 'raggruppamento') {
                continue;
            }
            array_push($head, $value->getLabel());
        };
        // dd($head);
        return $head;
    }

    public function map($row): array
    {
        $body = [];
        // dd($this->columns);
        foreach ($this->columns as $key => $value) {
            $colName = $value->getName();
            $subColName = null;
            if (strpos($colName, '.')) {
                $colName = substr($value->getName(), 0, strpos($value->getName(), '.'));
                $subColName = substr($value->getName(), strpos($value->getName(), '.') + 1);
            }

            if ($colName == 'raggruppamento') {
                continue;
            }
            if ($colName == 'customer') {
                try {
                    $id = $row['customer_id'];
                    $customer_name = Customer::find($id)->name;
                } catch (\Throwable $th) {
                    $customer_name = null;
                }
                array_push($body, $customer_name);
                continue;
            }
            if ($colName == 'product') {
                try {
                    $id = $row['product_id'];
                    $product = Product::find($id ?? null)->code ?? '';
                } catch (\Throwable $th) {
                    $product = null;
                }
                array_push($body, $product);
                continue;
            }
            if ($colName == 'operator') {
                try {
                    $id = $row['operator_id'];
                    $operator = Operator::find($id ?? null)->name ?? '';
                } catch (\Throwable $th) {
                    $operator = null;
                }
                array_push($body, $operator);
                continue;
            }
            if ($colName == 'processType') {
                try {
                    $id = $row['process_type_id'];
                    $processType = ProcessType::find($id ?? null)->description ?? '';
                } catch (\Throwable $th) {
                    $processType = null;
                }
                array_push($body, $processType);
                continue;
            }
            if ($colName == 'number') {
                try {
                    array_push($body, $row['number']);
                } catch (\Throwable $th) {
                    array_push($body, null);
                }
                continue;
            }
            if ($colName == 'avg_minutes_trsl') {
                array_push($body, $row['avg_minutes'] > 0 ? sprintf('%02d:%02d:%02d', floor($row['avg_minutes'] / 60), $row['avg_minutes'] % 60, ($row['avg_minutes'] - floor($row['avg_minutes'])) * 60) : null);
                continue;
            }
            if (in_array($colName, ['quantity', 'total_minutes', 'avg_minutes'])) {
                array_push($body, $row[$colName] > 0 ? $row[$colName] : '');
            }


            // dd($colName);

            // if ($subColName) {
            //     try {
            //         array_push($body, $row[$colName][$subColName]);
            //     } catch (\Throwable $th) {
            //         array_push($body, null);
            //         //throw $th;
            //     }
            // } else {
            //     array_push($body, $row[$colName]);
            // }
        }
        // dd($body);
        return $body;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text.
            1    => ['font' => ['bold' => true]],

            // // Styling a specific cell by coordinate.
            // 'B2' => ['font' => ['italic' => true]],

            // // Styling an entire column.
            // 'C'  => ['font' => ['size' => 16]],
        ];
    }

    public function columnFormats(): array
    {
        $format = [];
        $alphabet = range('A', 'Z');
        $index = 0;
        // foreach ($this->typeAttribute as $column) {
        //     if ($column->attribute->col_type == 'date') {
        //         $format[$alphabet[$index]] = NumberFormat::FORMAT_DATE_DDMMYYYY;
        //     }
        //     $index++;
        // }
        return $format;
        // return [
        //     'B' => NumberFormat::FORMAT_DATE_DDMMYYYY,
        //     'C' => NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE,
        // ];
    }
}
