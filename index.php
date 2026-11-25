<?php
/**
 * Painel Principal (Dashboard) do Mini-SOC.
 * Exibe indicadores rápidos e os incidentes mais recentes.
 */

// Carrega conexão com o banco (PDO) e funções de sessão/autenticação
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

// Garante que apenas usuários autenticados acessem o dashboard
exigir_login();

$pdo = conectar_banco();

// Consulta agregada em uma única query para montar os KPIs de incidentes (total, ativos e críticos)
$resumo = $pdo->query("
    SELECT
        COUNT(*) AS total_incidentes,
        SUM(CASE WHEN status IN ('Aberto', 'Em Analise') THEN 1 ELSE 0 END) AS incidentes_ativos,
        SUM(CASE WHEN severidade = 'Critica' THEN 1 ELSE 0 END) AS incidentes_criticos
    FROM incidentes
")->fetch();

// Contagens rápidas de ativos monitorados e vulnerabilidades (CVEs) cadastradas
$totalAtivos = (int) $pdo->query('SELECT COUNT(*) FROM ativos')->fetchColumn();
$totalVulns  = (int) $pdo->query('SELECT COUNT(*) FROM vulnerabilidades')->fetchColumn();

// Busca os 6 incidentes mais recentes cruzando com as tabelas ativos e categorias_ameaca (INNER JOIN)
$ultimosIncidentes = $pdo->query("
    SELECT
        i.id,
        i.titulo,
        i.severidade,
        i.status,
        i.data_ocorrencia,
        a.nome AS ativo_nome,
        a.endereco_ip,
        c.nome AS categoria_nome
    FROM incidentes i
    INNER JOIN ativos a ON a.id = i.ativo_id
    INNER JOIN categorias_ameaca c ON c.id = i.categoria_id
    ORDER BY i.data_ocorrencia DESC
    LIMIT 6
")->fetchAll();

// Define metadados usados pelo layout em includes/header.php (título da aba e item ativo do menu)
$tituloPagina = 'Painel Geral';
$menuAtivo = 'painel';
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h4 fw-semibold mb-1">Painel de Operações de Segurança</h1>
        <p class="text-secondary small mb-0">Visão geral dos incidentes, ativos monitorados e vulnerabilidades registradas.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= e(base_url('modules/incidentes/form.php')) ?>" class="btn btn-light btn-sm fw-semibold">
            <i class="bi bi-plus-lg me-1"></i>Novo Incidente
        </a>
        <a href="<?= e(base_url('relatorios/consolidado.php')) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-bar-chart-line me-1"></i>Relatório Consolidado
        </a>
    </div>
</div>

<!-- Cards de KPI -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="soc-kpi h-100">
            <div class="soc-kpi-label">Total de Incidentes</div>
            <div class="soc-kpi-value"><?= (int) ($resumo['total_incidentes'] ?? 0) ?></div>
            <div class="small text-secondary mt-1">Registrados no SOC</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="soc-kpi h-100">
            <div class="soc-kpi-label">Em Aberto / Análise</div>
            <div class="soc-kpi-value text-warning"><?= (int) ($resumo['incidentes_ativos'] ?? 0) ?></div>
            <div class="small text-secondary mt-1">Requerem atenção</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="soc-kpi h-100">
            <div class="soc-kpi-label">Severidade Crítica</div>
            <div class="soc-kpi-value text-danger"><?= (int) ($resumo['incidentes_criticos'] ?? 0) ?></div>
            <div class="small text-secondary mt-1">Prioridade máxima</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="soc-kpi h-100">
            <div class="soc-kpi-label">Ativos &amp; CVEs</div>
            <div class="soc-kpi-value"><?= $totalAtivos ?> <span class="fs-6 fw-normal text-secondary">/ <?= $totalVulns ?> CVEs</span></div>
            <div class="small text-secondary mt-1">Infraestrutura monitorada</div>
        </div>
    </div>
</div>

<!-- Últimos incidentes -->
<div class="card soc-card">
    <div class="card-header d-flex justify-content-between align-items-center py-3">
        <span><i class="bi bi-clock-history me-2 text-secondary"></i>Incidentes Recentes</span>
        <a href="<?= e(base_url('modules/incidentes/index.php')) ?>" class="btn btn-outline-secondary btn-sm">
            Ver todos <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-soc table-hover align-middle">
            <thead>
                <tr>
                    <th class="ps-3">#</th>
                    <th>Incidente</th>
                    <th>Ativo / IP</th>
                    <th>Categoria</th>
                    <th>Severidade</th>
                    <th>Status</th>
                    <th class="pe-3">Data</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ultimosIncidentes)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-4">Nenhum incidente registrado até o momento.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($ultimosIncidentes as $inc): ?>
                        <tr>
                            <td class="ps-3 text-secondary">#<?= (int) $inc['id'] ?></td>
                            <td>
                                <a href="<?= e(base_url('modules/incidentes/form.php?id=' . (int) $inc['id'])) ?>" class="text-decoration-none text-light fw-medium">
                                    <?= e($inc['titulo']) ?>
                                </a>
                            </td>
                            <td>
                                <div class="small"><?= e($inc['ativo_nome']) ?></div>
                                <div class="text-secondary" style="font-size: 0.78rem;"><?= e($inc['endereco_ip']) ?></div>
                            </td>
                            <td class="small text-secondary"><?= e($inc['categoria_nome']) ?></td>
                            <td><?= badge_severidade($inc['severidade']) ?></td>
                            <td><?= badge_status($inc['status']) ?></td>
                            <td class="pe-3 small text-secondary text-nowrap"><?= e(formatar_data($inc['data_ocorrencia'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
