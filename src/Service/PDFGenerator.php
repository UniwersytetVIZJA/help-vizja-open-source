<?php

namespace App\Service;

use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use setasign\Fpdi\PdfParser\Filter\FilterException;
use setasign\Fpdi\PdfParser\PdfParserException;
use setasign\Fpdi\PdfParser\Type\PdfTypeException;
use setasign\Fpdi\PdfReader\PdfReaderException;
use setasign\Fpdi\Tcpdf\Fpdi;
use function sys_get_temp_dir;
use function tempnam;

final class PDFGenerator
{

    /**
     * @throws CrossReferenceException
     * @throws PdfReaderException
     * @throws PdfParserException
     * @throws PdfTypeException
     * @throws FilterException
     */
    public function generate(string $templatePath, array $fields): string
    {
        $pdf = new Fpdi();

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false, 0);

        $pageCount = $pdf->setSourceFile($templatePath);

        for ($page = 1; $page <= $pageCount; $page++) {
            $tplId = $pdf->importPage($page);
            $size = $pdf->getTemplateSize($tplId);

            $pdf->AddPage($size['orientation'], [$size['width']], $size['height']);

            $pdf->useTemplate($tplId, 0, 0, $size['width'], $size['height'], true);

            $pdf->setFont('dejavusans', '', 10);

            foreach ($fields as $f) {
                if (($f['page'] ?? 1) !== $page) continue;
                $pdf->setXY((float)$f['x'], (float)$f['y']);
                $pdf->Write(0, (string)$f['value']);
            }
        }
        $out = tempnam(sys_get_temp_dir(), 'pdf_' . '.pdf');
        $pdf->Output($out, 'F');

        return $out;
    }
}
