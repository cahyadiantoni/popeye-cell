<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PulsaReportExport implements WithMultipleSheets
{
    use Exportable;

    protected $reportCollection;

    public function __construct($reportCollection)
    {
        $this->reportCollection = $reportCollection;
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [];

        // Sheet 1: Detail Data
        $sheets[] = new PulsaReportDetailSheet($this->reportCollection);

        // Sheet 2: Rangkuman
        $sheets[] = new PulsaReportSummarySheet($this->reportCollection);
        
        return $sheets;
    }
}