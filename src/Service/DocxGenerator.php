<?php

namespace App\Service;

use PhpOffice\PhpWord\Exception\CopyFileException;
use PhpOffice\PhpWord\Exception\CreateTemporaryFileException;
use PhpOffice\PhpWord\TemplateProcessor;
use function count;
use function htmlspecialchars;
use function is_array;
use function is_object;
use function sys_get_temp_dir;
use function tempnam;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

final class DocxGenerator
{
    /**
     * @throws CopyFileException
     * @throws CreateTemporaryFileException
     */
    public function generate(string $templatePath, array $vars, array $blocks = []): string
    {
        $tp = new TemplateProcessor($templatePath);

        foreach ($vars as $key => $value) {
            if (is_array($value) || is_object($value)) {
                continue;
            }

            $value = htmlspecialchars(
                (string)($value ?? ''),
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );

            $tp->setValue((string)$key, $value);
        }

        foreach ($blocks as $blockName => $rows) {
            if (empty($rows)) {
                $tp->cloneBlock((string)$blockName, 0, true, false);
                continue;
            }

            $safeRows = [];

            foreach ($rows as $row) {
                $safeRow = [];

                foreach ($row as $key => $value) {
                    $safeRow[$key] = htmlspecialchars(
                        (string)($value ?? ''),
                        ENT_QUOTES | ENT_SUBSTITUTE,
                        'UTF-8'
                    );
                }

                $safeRows[] = $safeRow;
            }

            $tp->cloneBlock(
                (string)$blockName,
                count($safeRows),
                true,
                false,
                $safeRows
            );
        }

        $out = tempnam(sys_get_temp_dir(), 'docx_') . '.docx';
        $tp->saveAs($out);

        return $out;
    }
}
