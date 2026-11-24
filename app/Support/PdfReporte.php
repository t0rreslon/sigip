<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\Response;

/**
 * Generador de reportes PDF en tabla, sin dependencias externas.
 * Página A4 horizontal, fuente Helvetica, codificación WinAnsi (admite tildes y ñ).
 *
 * Se usa en las exportaciones PDF de usuarios, instructores, accesos,
 * inventario, préstamos, reservas y asistencia.
 */
class PdfReporte
{
    private const ANCHO = 842;
    private const ALTO = 595;
    private const MARGEN = 30;
    private const ALTO_FILA = 15;
    private const TAM_FUENTE = 8;

    // Anchos de la fuente Helvetica (unidades de 1/1000) para los caracteres ASCII 32-126.
    private const ANCHOS = [
        278, 278, 355, 556, 556, 889, 667, 191, 333, 333, 389, 584, 278, 333, 278, 278,
        556, 556, 556, 556, 556, 556, 556, 556, 556, 556, 278, 278, 584, 584, 584, 556,
        1015, 667, 667, 722, 722, 667, 611, 778, 722, 278, 500, 667, 556, 833, 722, 778,
        667, 778, 722, 667, 611, 722, 667, 944, 667, 667, 611, 278, 278, 278, 469, 556,
        333, 556, 556, 500, 556, 556, 278, 556, 556, 222, 222, 500, 222, 833, 556, 556,
        556, 556, 333, 500, 278, 556, 500, 722, 500, 500, 500, 334, 260, 334, 584,
    ];

    /**
     * @param array<int, array{titulo: string, ancho: int}> $columnas  Anchos en puntos (total ≈ 780).
     * @param iterable<array<int, mixed>> $filas
     * @param array<int, string> $resumen  Líneas que se imprimen antes de la tabla.
     */
    public static function tabla(string $titulo, array $columnas, iterable $filas, array $resumen = []): string
    {
        $paginas = [];
        $contenido = '';
        $y = 0;

        $nuevaPagina = function () use (&$paginas, &$contenido, &$y, $titulo, $columnas, $resumen) {
            if ($contenido !== '') {
                $paginas[] = $contenido;
            }

            $contenido = '';
            $y = self::ALTO - self::MARGEN;

            // Encabezado del reporte.
            $contenido .= self::texto(self::MARGEN, $y - 14, 'SIGIP · Gimnasio Universitario Pascual Bravo', 9, false, '0.42 0.25 0.55');
            $contenido .= self::texto(self::MARGEN, $y - 32, $titulo, 15, true);
            $contenido .= self::texto(self::ANCHO - self::MARGEN - 150, $y - 14, 'Generado: ' . now()->format('d/m/Y h:i a'), 8);
            $y -= 46;

            if (count($paginas) === 0) {
                foreach ($resumen as $linea) {
                    $contenido .= self::texto(self::MARGEN, $y, $linea, 9);
                    $y -= 13;
                }

                $y -= 4;
            }

            // Encabezado de la tabla.
            $contenido .= self::rectangulo(self::MARGEN, $y - self::ALTO_FILA + 4, self::ANCHO - 2 * self::MARGEN, self::ALTO_FILA, '0.93 0.89 0.95');
            $x = self::MARGEN + 4;

            foreach ($columnas as $columna) {
                $contenido .= self::texto($x, $y - 7, self::recortar($columna['titulo'], $columna['ancho'] - 6, true), self::TAM_FUENTE, true);
                $x += $columna['ancho'];
            }

            $y -= self::ALTO_FILA;
        };

        $nuevaPagina();
        $hayFilas = false;

        foreach ($filas as $indice => $fila) {
            $hayFilas = true;

            if ($y - self::ALTO_FILA < self::MARGEN + 20) {
                $nuevaPagina();
            }

            if ($indice % 2 === 1) {
                $contenido .= self::rectangulo(self::MARGEN, $y - self::ALTO_FILA + 4, self::ANCHO - 2 * self::MARGEN, self::ALTO_FILA, '0.97 0.97 0.98');
            }

            $x = self::MARGEN + 4;

            foreach ($columnas as $posicion => $columna) {
                $valor = (string) ($fila[$posicion] ?? '');
                $contenido .= self::texto($x, $y - 7, self::recortar($valor, $columna['ancho'] - 6), self::TAM_FUENTE);
                $x += $columna['ancho'];
            }

            $y -= self::ALTO_FILA;
        }

        if (!$hayFilas) {
            $contenido .= self::texto(self::MARGEN + 4, $y - 9, 'No hay registros para los filtros seleccionados.', 9);
        }

        $paginas[] = $contenido;

        return self::documento($paginas);
    }

    /**
     * Respuesta HTTP que descarga el PDF.
     */
    public static function descargar(string $pdf, string $nombreArchivo): Response
    {
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $nombreArchivo . '"',
            'Cache-Control' => 'no-store',
        ]);
    }

    private static function documento(array $paginas): string
    {
        $objetos = [];
        $total = count($paginas);

        // 1: catálogo, 2: páginas, 3: fuente normal, 4: fuente negrita.
        $objetos[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objetos[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objetos[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        $hijos = [];
        $siguiente = 5;

        foreach ($paginas as $numero => $contenido) {
            $contenido .= self::texto(self::ANCHO - self::MARGEN - 70, self::MARGEN - 12, 'Página ' . ($numero + 1) . ' de ' . $total, 8);

            $idPagina = $siguiente++;
            $idContenido = $siguiente++;
            $hijos[] = "{$idPagina} 0 R";

            $objetos[$idPagina] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . self::ANCHO . ' ' . self::ALTO . '] '
                . '/Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . $idContenido . ' 0 R >>';
            $objetos[$idContenido] = '<< /Length ' . strlen($contenido) . " >>\nstream\n" . $contenido . "\nendstream";
        }

        $objetos[2] = '<< /Type /Pages /Kids [' . implode(' ', $hijos) . '] /Count ' . $total . ' >>';
        ksort($objetos);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $posiciones = [];

        foreach ($objetos as $id => $cuerpo) {
            $posiciones[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$cuerpo}\nendobj\n";
        }

        $inicioXref = strlen($pdf);
        $cantidad = max(array_keys($objetos)) + 1;
        $pdf .= "xref\n0 {$cantidad}\n0000000000 65535 f \n";

        for ($id = 1; $id < $cantidad; $id++) {
            $pdf .= sprintf("%010d 00000 n \n", $posiciones[$id] ?? 0);
        }

        $pdf .= "trailer\n<< /Size {$cantidad} /Root 1 0 R >>\nstartxref\n{$inicioXref}\n%%EOF";

        return $pdf;
    }

    private static function texto(float $x, float $y, string $texto, int $tamano, bool $negrita = false, string $color = '0.12 0.12 0.16'): string
    {
        $fuente = $negrita ? 'F2' : 'F1';

        return sprintf("BT %s rg /%s %d Tf %.2F %.2F Td (%s) Tj ET\n", $color, $fuente, $tamano, $x, $y, self::escapar($texto));
    }

    private static function rectangulo(float $x, float $y, float $ancho, float $alto, string $color): string
    {
        return sprintf("%s rg %.2F %.2F %.2F %.2F re f\n", $color, $x, $y, $ancho, $alto);
    }

    private static function escapar(string $texto): string
    {
        $texto = mb_convert_encoding($texto, 'Windows-1252', 'UTF-8');

        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], $texto);
    }

    private static function anchoTexto(string $texto, bool $negrita = false): float
    {
        $ancho = 0;

        foreach (mb_str_split($texto) as $caracter) {
            $codigo = mb_ord($caracter);
            $ancho += ($codigo >= 32 && $codigo <= 126) ? self::ANCHOS[$codigo - 32] : 556;
        }

        return $ancho * self::TAM_FUENTE / 1000 * ($negrita ? 1.06 : 1);
    }

    private static function recortar(string $texto, float $anchoMaximo, bool $negrita = false): string
    {
        $texto = trim(preg_replace('/\s+/', ' ', $texto));

        if (self::anchoTexto($texto, $negrita) <= $anchoMaximo) {
            return $texto;
        }

        while ($texto !== '' && self::anchoTexto($texto . '...', $negrita) > $anchoMaximo) {
            $texto = mb_substr($texto, 0, -1);
        }

        return $texto . '...';
    }
}
