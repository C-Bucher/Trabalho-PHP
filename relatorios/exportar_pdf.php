<?php
/**
 * Exportação da Listagem de Incidentes em PDF (respeitando os filtros ativos da busca).
 *
 * Usa FPDF oficial (via Composer / vendor/autoload.php) se instalado, ou o gerador
 * compatível em PHP puro (lib/MiniFPDF.php) para funcionar imediatamente em XAMPP/Laragon.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

exigir_login();

// Carrega FPDF oficial se disponível via Composer; caso contrário usa MiniFPDF embutido
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

if (!class_exists('FPDF')) {
    require_once __DIR__ . '/../lib/MiniFPDF.php';
    class_alias('MiniFPDF', 'FPDF');
}

/**
 * Converte texto UTF-8 para Windows-1252 (compatível com fontes Type1 / FPDF).
 */
function pdf_texto(string $texto, int $limite = 0): string
{
    $limpo = trim(preg_replace('/\s+/', ' ', $texto));
    if ($limite > 0 && mb_strlen($limpo, 'UTF-8') > $limite) {
        $limpo = mb_substr($limpo, 0, $limite - 3, 'UTF-8') . '...';
    }

    if (function_exists('iconv')) {
        $convertido = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $limpo);
        if ($convertido !== false) {
            return $convertido;
        }
    }

    return mb_convert_encoding($limpo, 'Windows-1252', 'UTF-8');
}

/**
 * Classe personalizada do relatório com cabeçalho e rodapé em todas as páginas.
 */
class RelatorioIncidentesPDF extends FPDF
{
    public string $resumoFiltros = 'Sem filtros aplicados (todos os incidentes)';
    public string $geradoPor = '';

    public function Header(): void
    {
        $this->SetFont('Helvetica', 'B', 14);
        $this->SetTextColor(25, 32, 44);
        $this->Cell(0, 7, pdf_texto('Mini-SOC — Relatório de Incidentes de Segurança'), 0, 1, 'L');

        $this->SetFont('Helvetica', '', 8.5);
        $this->SetTextColor(95, 105, 120);
        $this->Cell(
            0,
            5,
            pdf_texto('Emitido em: ' . date('d/m/Y H:i') . '   |   Analista: ' . $this->geradoPor),
            0,
            1,
            'L'
        );
        $this->Cell(0, 5, pdf_texto('Filtros: ' . $this->resumoFiltros), 0, 1, 'L');
        $this->Ln(3);

        // Cabeçalho da tabela
        $this->SetFillColor(30, 38, 49);
        $this->SetTextColor(255, 255, 255);
        $this->SetDrawColor(190, 198, 210);
        $this->SetFont('Helvetica', 'B', 8.5);

        $this->Cell(12, 7.5, 'ID', 1, 0, 'C', true);
        $this->Cell(78, 7.5, pdf_texto('Título do Incidente'), 1, 0, 'L', true);
        $this->Cell(55, 7.5, pdf_texto('Ativo (IP)'), 1, 0, 'L', true);
        $this->Cell(38, 7.5, pdf_texto('Categoria'), 1, 0, 'L', true);
        $this->Cell(22, 7.5, pdf_texto('Severidade'), 1, 0, 'C', true);
        $this->Cell(24, 7.5, pdf_texto('Status'), 1, 0, 'C', true);
        $this->Cell(24, 7.5, pdf_texto('Analista'), 1, 0, 'L', true);
        $this->Cell(24, 7.5, pdf_texto('Ocorrência'), 1, 1, 'C', true);

        // Restaura o estilo padrão das linhas do corpo após quebra automática de página
        $this->SetFont('Helvetica', '', 8);
        $this->SetTextColor(30, 35, 42);
        $this->SetDrawColor(215, 220, 228);
    }

    public function Footer(): void
    {
        $this->SetY(-12);
        $this->SetFont('Helvetica', '', 8);
        $this->SetTextColor(120, 128, 140);
        $this->Cell(0, 6, pdf_texto('Mini-SOC — Documento gerado automaticamente   |   Página ' . $this->PageNo()), 0, 0, 'R');
    }
}

$pdo = conectar_banco();
$usuarioAtual = usuario_logado();

// Reaplica exatamente os mesmos filtros da tela de busca avançada
$busca      = trim($_GET['busca'] ?? '');
$severidade = trim($_GET['severidade'] ?? '');
$status     = trim($_GET['status'] ?? '');
$dataInicio = trim($_GET['data_inicio'] ?? '');
$dataFim    = trim($_GET['data_fim'] ?? '');

$sql = "
    SELECT
        i.id,
        i.titulo,
        i.severidade,
        i.status,
        i.data_ocorrencia,
        a.nome AS ativo_nome,
        a.endereco_ip,
        c.nome AS categoria_nome,
        u.nome AS analista_nome
    FROM incidentes i
    INNER JOIN ativos a ON a.id = i.ativo_id
    INNER JOIN categorias_ameaca c ON c.id = i.categoria_id
    INNER JOIN usuarios u ON u.id = i.usuario_id
    WHERE 1=1
";
$params = [];
$filtrosTexto = [];

if ($busca !== '') {
    $sql .= ' AND (i.titulo LIKE :busca_titulo OR a.endereco_ip LIKE :busca_ip)';
    $params[':busca_titulo'] = '%' . $busca . '%';
    $params[':busca_ip']     = '%' . $busca . '%';
    $filtrosTexto[] = 'Busca="' . $busca . '"';
}

$severidadesValidas = ['Baixa', 'Media', 'Alta', 'Critica'];
if ($severidade !== '' && in_array($severidade, $severidadesValidas, true)) {
    $sql .= ' AND i.severidade = :severidade';
    $params[':severidade'] = $severidade;
    $filtrosTexto[] = 'Severidade=' . rotulo_severidade($severidade);
}

$statusValidos = ['Aberto', 'Em Analise', 'Contido', 'Resolvido'];
if ($status !== '' && in_array($status, $statusValidos, true)) {
    $sql .= ' AND i.status = :status';
    $params[':status'] = $status;
    $filtrosTexto[] = 'Status=' . rotulo_status($status);
}

if ($dataInicio !== '') {
    $sql .= ' AND i.data_ocorrencia >= :data_inicio';
    $params[':data_inicio'] = $dataInicio . ' 00:00:00';
    $filtrosTexto[] = 'De=' . date('d/m/Y', strtotime($dataInicio));
}

if ($dataFim !== '') {
    $sql .= ' AND i.data_ocorrencia <= :data_fim';
    $params[':data_fim'] = $dataFim . ' 23:59:59';
    $filtrosTexto[] = 'Até=' . date('d/m/Y', strtotime($dataFim));
}

$sql .= ' ORDER BY i.data_ocorrencia DESC, i.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$incidentes = $stmt->fetchAll();

// Monta o documento PDF em A4 Paisagem (largura útil = 277 mm)
$pdf = new RelatorioIncidentesPDF('L', 'mm', 'A4');
$pdf->SetMargins(10, 12, 10);
$pdf->SetAutoPageBreak(true, 16);
$pdf->geradoPor = $usuarioAtual['nome'] ?? 'Analista';
if (!empty($filtrosTexto)) {
    $pdf->resumoFiltros = implode(' | ', $filtrosTexto);
}

$pdf->AddPage();
$pdf->SetFont('Helvetica', '', 8);
$pdf->SetDrawColor(215, 220, 228);

if (empty($incidentes)) {
    $pdf->SetTextColor(90, 95, 105);
    $pdf->Cell(277, 10, pdf_texto('Nenhum incidente encontrado para os filtros informados.'), 1, 1, 'C');
} else {
    $alternar = false;
    foreach ($incidentes as $inc) {
        $pdf->SetFillColor($alternar ? 245 : 255, $alternar ? 247 : 255, $alternar ? 250 : 255);
        $pdf->SetTextColor(30, 35, 42);

        $ativoLabel = $inc['ativo_nome'] . ' (' . $inc['endereco_ip'] . ')';

        $pdf->Cell(12, 7, '#' . (int) $inc['id'], 1, 0, 'C', true);
        $pdf->Cell(78, 7, pdf_texto($inc['titulo'], 48), 1, 0, 'L', true);
        $pdf->Cell(55, 7, pdf_texto($ativoLabel, 34), 1, 0, 'L', true);
        $pdf->Cell(38, 7, pdf_texto($inc['categoria_nome'], 23), 1, 0, 'L', true);
        $pdf->Cell(22, 7, pdf_texto(rotulo_severidade($inc['severidade'])), 1, 0, 'C', true);
        $pdf->Cell(24, 7, pdf_texto(rotulo_status($inc['status'])), 1, 0, 'C', true);
        $pdf->Cell(24, 7, pdf_texto($inc['analista_nome'], 15), 1, 0, 'L', true);
        $pdf->Cell(24, 7, pdf_texto(formatar_data($inc['data_ocorrencia'])), 1, 1, 'C', true);

        $alternar = !$alternar;
    }

    // Linha de resumo total
    $pdf->Ln(3);
    $pdf->SetFont('Helvetica', 'B', 8.5);
    $pdf->SetTextColor(30, 38, 49);
    $pdf->Cell(277, 6, pdf_texto('Total de registros listados: ' . count($incidentes)), 0, 1, 'R');
}

$pdf->Output('I', 'mini-soc-incidentes-' . date('Ymd-His') . '.pdf');
exit;
