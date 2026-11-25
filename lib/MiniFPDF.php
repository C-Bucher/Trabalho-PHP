<?php
/**
 * MiniFPDF — Gerador leve de PDF 1.4 em PHP puro com API compatível com FPDF.
 *
 * Permite exportar relatórios em PDF imediatamente em XAMPP/Laragon sem exigir
 * instalação de dependências externas. Caso o usuário instale a biblioteca oficial
 * setasign/fpdf via Composer (composer install), exportar_pdf.php a utilizará
 * automaticamente com a mesma interface.
 */

class MiniFPDF
{
    protected float $k;             // Fator de conversão (mm -> pontos PDF)
    protected float $w;             // Largura da página em mm
    protected float $h;             // Altura da página em mm
    protected float $lMargin = 10.0;
    protected float $tMargin = 10.0;
    protected float $rMargin = 10.0;
    protected float $bMargin = 15.0;
    protected bool $autoPageBreak = true;

    protected float $x = 10.0;
    protected float $y = 10.0;
    protected float $lasth = 0.0;

    protected string $fontKey = 'F1'; // F1 = Helvetica, F2 = Helvetica-Bold
    protected float $fontSizePt = 10.0;
    protected float $lineWidth = 0.2;

    protected string $fillColor = '1.000 1.000 1.000 rg';
    protected string $textColor = '0.000 0.000 0.000 rg';
    protected string $drawColor = '0.000 0.000 0.000 RG';

    protected string $orientation = 'P';
    protected int $page = 0;
    /** @var array<int, string> */
    protected array $pages = [];
    protected bool $inHeader = false;
    protected bool $inFooter = false;

    public function __construct(string $orientation = 'P', string $unit = 'mm', string $size = 'A4')
    {
        $this->k = 72.0 / 25.4; // Unidade padrão em milímetros
        $this->orientation = strtoupper($orientation) === 'L' ? 'L' : 'P';
        $this->setDimensions($this->orientation);
    }

    protected function setDimensions(string $orientation): void
    {
        if ($orientation === 'L') {
            $this->w = 297.0;
            $this->h = 210.0;
        } else {
            $this->w = 210.0;
            $this->h = 297.0;
        }
    }

    public function SetMargins(float $left, float $top, ?float $right = null): void
    {
        $this->lMargin = $left;
        $this->tMargin = $top;
        $this->rMargin = $right ?? $left;
    }

    public function SetAutoPageBreak(bool $auto, float $margin = 15.0): void
    {
        $this->autoPageBreak = $auto;
        $this->bMargin = $margin;
    }

    public function AddPage(string $orientation = ''): void
    {
        if ($this->page > 0 && !$this->inFooter) {
            $this->inFooter = true;
            $this->Footer();
            $this->inFooter = false;
        }

        $ori = $orientation !== '' ? strtoupper($orientation) : $this->orientation;
        $this->orientation = ($ori === 'L') ? 'L' : 'P';
        $this->setDimensions($this->orientation);

        $this->page++;
        $this->pages[$this->page] = '';
        $this->x = $this->lMargin;
        $this->y = $this->tMargin;
        $this->lasth = 0.0;

        $this->out(sprintf('%.2F w', $this->lineWidth * $this->k));
        $this->out($this->drawColor);

        $this->inHeader = true;
        $this->Header();
        $this->inHeader = false;
    }

    public function Header(): void
    {
        // Pode ser sobrescrito em subclasses
    }

    public function Footer(): void
    {
        // Pode ser sobrescrito em subclasses
    }

    public function PageNo(): int
    {
        return $this->page;
    }

    public function GetY(): float
    {
        return $this->y;
    }

    public function SetY(float $y): void
    {
        $this->x = $this->lMargin;
        $this->y = $y >= 0 ? $y : $this->h + $y;
    }

    public function SetFont(string $family, string $style = '', float $size = 0): void
    {
        $isBold = stripos($style, 'B') !== false;
        $this->fontKey = $isBold ? 'F2' : 'F1';
        if ($size > 0) {
            $this->fontSizePt = $size;
        }
    }

    public function SetFillColor(int $r, ?int $g = null, ?int $b = null): void
    {
        $g = $g ?? $r;
        $b = $b ?? $r;
        $this->fillColor = sprintf('%.3F %.3F %.3F rg', $r / 255, $g / 255, $b / 255);
    }

    public function SetTextColor(int $r, ?int $g = null, ?int $b = null): void
    {
        $g = $g ?? $r;
        $b = $b ?? $r;
        $this->textColor = sprintf('%.3F %.3F %.3F rg', $r / 255, $g / 255, $b / 255);
    }

    public function SetDrawColor(int $r, ?int $g = null, ?int $b = null): void
    {
        $g = $g ?? $r;
        $b = $b ?? $r;
        $this->drawColor = sprintf('%.3F %.3F %.3F RG', $r / 255, $g / 255, $b / 255);
        if ($this->page > 0) {
            $this->out($this->drawColor);
        }
    }

    public function SetLineWidth(float $width): void
    {
        $this->lineWidth = $width;
        if ($this->page > 0) {
            $this->out(sprintf('%.2F w', $width * $this->k));
        }
    }

    public function Cell(
        float $w,
        float $h = 0.0,
        string $txt = '',
        mixed $border = 0,
        int $ln = 0,
        string $align = '',
        bool $fill = false
    ): void {
        if ($this->page === 0) {
            $this->AddPage();
        }

        if ($w == 0.0) {
            $w = $this->w - $this->rMargin - $this->x;
        }

        // Quebra de página automática quando o conteúdo atinge a margem inferior
        if ($this->autoPageBreak && !$this->inHeader && !$this->inFooter && ($this->y + $h > $this->h - $this->bMargin)) {
            $xAtual = $this->x;
            $this->AddPage($this->orientation);
            $this->x = $xAtual;
        }

        $k = $this->k;
        $xPt = $this->x * $k;
        $yTopPt = ($this->h - $this->y) * $k;
        $yBottomPt = ($this->h - ($this->y + $h)) * $k;
        $wPt = $w * $k;
        $hPt = $h * $k;

        // Preenchimento e/ou borda retangular
        if ($fill || $border === 1) {
            $op = $fill ? ($border === 1 ? 'B' : 'f') : 'S';
            if ($fill) {
                $this->out($this->fillColor);
            }
            $this->out(sprintf('%.2F %.2F %.2F %.2F re %s', $xPt, $yBottomPt, $wPt, $hPt, $op));
        } elseif (is_string($border) && $border !== '') {
            if (str_contains($border, 'L')) {
                $this->out(sprintf('%.2F %.2F m %.2F %.2F l S', $xPt, $yTopPt, $xPt, $yBottomPt));
            }
            if (str_contains($border, 'T')) {
                $this->out(sprintf('%.2F %.2F m %.2F %.2F l S', $xPt, $yTopPt, $xPt + $wPt, $yTopPt));
            }
            if (str_contains($border, 'R')) {
                $this->out(sprintf('%.2F %.2F m %.2F %.2F l S', $xPt + $wPt, $yTopPt, $xPt + $wPt, $yBottomPt));
            }
            if (str_contains($border, 'B')) {
                $this->out(sprintf('%.2F %.2F m %.2F %.2F l S', $xPt, $yBottomPt, $xPt + $wPt, $yBottomPt));
            }
        }

        // Renderização do texto dentro da célula
        if ($txt !== '') {
            $larguraTexto = $this->estimarLarguraTexto($txt);
            $pad = 1.5; // padding horizontal em mm

            if ($align === 'R') {
                $dx = max($pad, $w - $pad - $larguraTexto);
            } elseif ($align === 'C') {
                $dx = max($pad, ($w - $larguraTexto) / 2.0);
            } else {
                $dx = $pad;
            }

            $tx = ($this->x + $dx) * $k;
            $ty = ($this->h - ($this->y + 0.5 * $h + 0.32 * ($this->fontSizePt / $k))) * $k;
            $escapado = $this->escaparTextoPdf($txt);

            $this->out(sprintf(
                'BT %s /%s %.2F Tf %.2F %.2F Td (%s) Tj ET',
                $this->textColor,
                $this->fontKey,
                $this->fontSizePt,
                $tx,
                $ty,
                $escapado
            ));
        }

        $this->lasth = $h;
        if ($ln > 0) {
            $this->y += $h;
            if ($ln === 1) {
                $this->x = $this->lMargin;
            }
        } else {
            $this->x += $w;
        }
    }

    public function Ln(?float $h = null): void
    {
        $this->x = $this->lMargin;
        $this->y += ($h ?? $this->lasth);
    }

    public function Output(string $dest = 'I', string $name = 'documento.pdf'): string
    {
        if ($this->page === 0) {
            $this->AddPage();
        }

        // Renderiza o rodapé da última página
        $this->inFooter = true;
        $this->Footer();
        $this->inFooter = false;

        $pdf = $this->compilarPdf();

        if (strtoupper($dest) === 'S') {
            return $pdf;
        }

        $disposicao = strtoupper($dest) === 'D' ? 'attachment' : 'inline';
        header('Content-Type: application/pdf');
        header('Content-Disposition: ' . $disposicao . '; filename="' . preg_replace('/[^A-Za-z0-9._-]/', '_', $name) . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;

        return '';
    }

    protected function out(string $cmd): void
    {
        $this->pages[$this->page] .= $cmd . "\n";
    }

    protected function estimarLarguraTexto(string $txt): float
    {
        // Estimativa média de largura de caractere da fonte Helvetica (em mm)
        $larguraMediaPt = $this->fontSizePt * ($this->fontKey === 'F2' ? 0.56 : 0.52);
        return (strlen($txt) * $larguraMediaPt) / $this->k;
    }

    protected function escaparTextoPdf(string $txt): string
    {
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ' '], $txt);
    }

    protected function compilarPdf(): string
    {
        $objetos = [];

        // Objeto 1: Catalog
        $objetos[1] = "<< /Type /Catalog /Pages 2 0 R >>";

        // Objeto 3 e 4: Fontes Type1 padrão com suporte a acentuação (WinAnsiEncoding)
        $objetos[3] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";
        $objetos[4] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>";

        $kidsRefs = [];
        $nextObj = 5;
        $wPt = sprintf('%.2F', $this->w * $this->k);
        $hPt = sprintf('%.2F', $this->h * $this->k);

        foreach ($this->pages as $conteudo) {
            $pageObjId = $nextObj++;
            $streamObjId = $nextObj++;
            $kidsRefs[] = $pageObjId . ' 0 R';

            $objetos[$pageObjId] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$wPt} {$hPt}] "
                . "/Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents {$streamObjId} 0 R >>";

            $len = strlen($conteudo);
            $objetos[$streamObjId] = "<< /Length {$len} >>\nstream\n{$conteudo}endstream";
        }

        // Objeto 2: Árvore de páginas
        $objetos[2] = "<< /Type /Pages /Kids [" . implode(' ', $kidsRefs) . "] /Count " . count($kidsRefs) . " >>";

        ksort($objetos);

        $buffer = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];

        foreach ($objetos as $id => $corpo) {
            $offsets[$id] = strlen($buffer);
            $buffer .= "{$id} 0 obj\n{$corpo}\nendobj\n";
        }

        $xrefPos = strlen($buffer);
        $totalObjs = count($objetos) + 1;
        $buffer .= "xref\n0 {$totalObjs}\n";
        $buffer .= "0000000000 65535 f \n";

        for ($i = 1; $i < $totalObjs; $i++) {
            $buffer .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $buffer .= "trailer\n<< /Size {$totalObjs} /Root 1 0 R >>\n";
        $buffer .= "startxref\n{$xrefPos}\n%%EOF\n";

        return $buffer;
    }
}
