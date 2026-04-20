<?php

namespace App\Services;

use App\Models\Audit;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPDF;

class PdfReportGenerator
{
    public function generate(Audit $audit): DomPDF
    {
        return Pdf::loadView('pdf.audit', [
            'audit' => $audit,
            'generated_at' => now(),
        ])->setPaper('a4');
    }
}
