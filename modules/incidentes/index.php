<?php
/**
 * Listagem de Incidentes de Segurança com Busca Avançada (PDO + WHERE 1=1)
 * e botão para exportação da listagem filtrada em PDF.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

exigir_login();

$pdo = conectar_banco();

// Captura e sanitiza filtros enviados via GET
$busca      = trim($_GET['busca'] ?? '');
$severidade = trim($_GET['severidade'] ?? '');
$status     = trim($_GET['status'] ?? '');
$dataInicio = trim($_GET['data_inicio'] ?? '');
$dataFim    = trim($_GET['data_fim'] ?? '');

// Montagem dinâmica e segura da query com Prepared Statements
$sql = "
    SELECT
        i.id,
        i.titulo,
        i.descricao,
        i.severidade,
        i.status,
        i.data_ocorrencia,
        a.nome AS ativo_nome,
        a.endereco_ip,
        c.nome AS categoria_nome,
        u.nome AS analista_nome,
        (SELECT COUNT(*) FROM acoes_mitigacao m WHERE m.incidente_id = i.id) AS total_mitigacoes
    FROM incidentes i
    INNER JOIN ativos a ON a.id = i.ativo_id
    INNER JOIN categorias_ameaca c ON c.id = i.categoria_id
    INNER JOIN usuarios u ON u.id = i.usuario_id
    WHERE 1=1
";
$params = [];

// Filtro 1 (Texto - LIKE): título do incidente ou IP do ativo
if ($busca !== '') {
    $sql .= ' AND (i.titulo LIKE :busca_titulo OR a.endereco_ip LIKE :busca_ip)';
    $params[':busca_titulo'] = '%' . $busca . '%';
    $params[':busca_ip']     = '%' . $busca . '%';
}

// Filtro 2 (Seleção - =): severidade
$severidadesValidas = ['Baixa', 'Media', 'Alta', 'Critica'];
if ($severidade !== '' && in_array($severidade, $severidadesValidas, true)) {
    $sql .= ' AND i.severidade = :severidade';
    $params[':severidade'] = $severidade;
}

// Filtro 3 (Seleção - =): status
$statusValidos = ['Aberto', 'Em Analise', 'Contido', 'Resolvido'];
if ($status !== '' && in_array($status, $statusValidos, true)) {
    $sql .= ' AND i.status = :status';
    $params[':status'] = $status;
}

// Filtro 4 (Data - intervalo): data_ocorrencia inicial e final
if ($dataInicio !== '') {
    $sql .= ' AND i.data_ocorrencia >= :data_inicio';
    $params[':data_inicio'] = $dataInicio . ' 00:00:00';
}
if ($dataFim !== '') {
    $sql .= ' AND i.data_ocorrencia <= :data_fim';
    $params[':data_fim'] = $dataFim . ' 23:59:59';
}

$sql .= ' ORDER BY i.data_ocorrencia DESC, i.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$incidentes = $stmt->fetchAll();

// Mantém os filtros ativos na URL do botão Exportar PDF
$queryPdf = http_build_query(array_filter([
    'busca'       => $busca,
    'severidade'  => $severidade,
    'status'      => $status,
    'data_inicio' => $dataInicio,
    'data_fim'    => $dataFim,
], static fn($v) => $v !== ''));

$urlPdf = base_url('relatorios/exportar_pdf.php') . ($queryPdf !== '' ? '?' . $queryPdf : '');
$temFiltros = ($queryPdf !== '');

$tituloPagina = 'Incidentes de Segurança';
$menuAtivo = 'incidentes';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h4 fw-semibold mb-1">Incidentes de Segurança</h1>
        <p class="text-secondary small mb-0">Registro, triagem, busca avançada e exportação em PDF dos incidentes monitorados.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= e($urlPdf) ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i>Exportar PDF
        </a>
        <a href="<?= e(base_url('modules/incidentes/form.php')) ?>" class="btn btn-light btn-sm fw-semibold">
            <i class="bi bi-plus-lg me-1"></i>Novo Incidente
        </a>
    </div>
</div>

<!-- Formulário de Busca Avançada (Texto, Seleção e Intervalo de Data) -->
<div class="card soc-card mb-4">
    <div class="card-body">
        <form method="get" action="" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="busca" class="form-label small text-secondary">Título do incidente ou IP do ativo</label>
                <input
                    type="text"
                    class="form-control form-control-sm"
                    id="busca"
                    name="busca"
                    value="<?= e($busca) ?>"
                    placeholder="Ex.: SQL Injection ou 192.168.10.10"
                >
            </div>

            <div class="col-6 col-md-2">
                <label for="severidade" class="form-label small text-secondary">Severidade</label>
                <select class="form-select form-select-sm" id="severidade" name="severidade">
                    <option value="">Todas</option>
                    <?php foreach ($severidadesValidas as $sev): ?>
                        <option value="<?= e($sev) ?>" <?= $severidade === $sev ? 'selected' : '' ?>>
                            <?= e(rotulo_severidade($sev)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <label for="status" class="form-label small text-secondary">Status</label>
                <select class="form-select form-select-sm" id="status" name="status">
                    <option value="">Todos</option>
                    <?php foreach ($statusValidos as $st): ?>
                        <option value="<?= e($st) ?>" <?= $status === $st ? 'selected' : '' ?>>
                            <?= e(rotulo_status($st)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <label for="data_inicio" class="form-label small text-secondary">Data inicial</label>
                <input
                    type="date"
                    class="form-control form-control-sm"
                    id="data_inicio"
                    name="data_inicio"
                    value="<?= e($dataInicio) ?>"
                >
            </div>

            <div class="col-6 col-md-2">
                <label for="data_fim" class="form-label small text-secondary">Data final</label>
                <input
                    type="date"
                    class="form-control form-control-sm"
                    id="data_fim"
                    name="data_fim"
                    value="<?= e($dataFim) ?>"
                >
            </div>

            <div class="col-12 d-flex justify-content-between align-items-center pt-1">
                <span class="small text-secondary">
                    Exibindo <strong><?= count($incidentes) ?></strong> incidente(s)<?= $temFiltros ? ' com filtros aplicados' : '' ?>.
                </span>
                <div class="d-flex gap-2">
                    <?php if ($temFiltros): ?>
                        <a href="<?= e(base_url('modules/incidentes/index.php')) ?>" class="btn btn-outline-secondary btn-sm">
                            Limpar
                        </a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-secondary btn-sm px-3">
                        <i class="bi bi-search me-1"></i>Filtrar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tabela de Incidentes -->
<div class="card soc-card">
    <div class="table-responsive">
        <table class="table table-soc table-hover align-middle">
            <thead>
                <tr>
                    <th class="ps-3">#</th>
                    <th>Título</th>
                    <th>Ativo / IP</th>
                    <th>Categoria</th>
                    <th>Severidade</th>
                    <th>Status</th>
                    <th>Analista</th>
                    <th>Ocorrência</th>
                    <th class="text-end pe-3">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($incidentes)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-secondary py-4">
                            Nenhum incidente encontrado para os critérios informados.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($incidentes as $inc): ?>
                        <tr>
                            <td class="ps-3 text-secondary">#<?= (int) $inc['id'] ?></td>
                            <td>
                                <div class="fw-medium text-light"><?= e($inc['titulo']) ?></div>
                                <div class="text-secondary small text-truncate" style="max-width: 280px;">
                                    <?= e($inc['descricao']) ?>
                                </div>
                            </td>
                            <td>
                                <div class="small"><?= e($inc['ativo_nome']) ?></div>
                                <div class="text-secondary" style="font-size: 0.78rem;"><?= e($inc['endereco_ip']) ?></div>
                            </td>
                            <td class="small text-secondary"><?= e($inc['categoria_nome']) ?></td>
                            <td><?= badge_severidade($inc['severidade']) ?></td>
                            <td><?= badge_status($inc['status']) ?></td>
                            <td class="small text-secondary"><?= e($inc['analista_nome']) ?></td>
                            <td class="small text-secondary text-nowrap"><?= e(formatar_data($inc['data_ocorrencia'])) ?></td>
                            <td class="text-end pe-3 text-nowrap">
                                <a
                                    href="<?= e(base_url('modules/mitigacoes/index.php?incidente_id=' . (int) $inc['id'])) ?>"
                                    class="btn btn-outline-secondary btn-sm"
                                    title="Ver ações de mitigação (<?= (int) $inc['total_mitigacoes'] ?>)"
                                >
                                    <i class="bi bi-check2-square"></i>
                                    <span class="ms-1"><?= (int) $inc['total_mitigacoes'] ?></span>
                                </a>
                                <a
                                    href="<?= e(base_url('modules/incidentes/form.php?id=' . (int) $inc['id'])) ?>"
                                    class="btn btn-outline-secondary btn-sm"
                                    title="Editar incidente"
                                >
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form
                                    method="post"
                                    action="<?= e(base_url('modules/incidentes/excluir.php')) ?>"
                                    class="d-inline"
                                    onsubmit="return confirm('Deseja realmente excluir este incidente e suas ações de mitigação?');"
                                >
                                    <input type="hidden" name="id" value="<?= (int) $inc['id'] ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Excluir incidente">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
