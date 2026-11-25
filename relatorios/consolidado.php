<?php
/**
 * Relatório Consolidado do Mini-SOC.
 * Utiliza agregações SQL (COUNT, GROUP BY, LEFT JOIN) para consolidar
 * incidentes por severidade, por status, por categoria e ativos mais afetados.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

exigir_login();

$pdo = conectar_banco();

// 1. Totais gerais
$totais = $pdo->query("
    SELECT
        COUNT(*) AS total_incidentes,
        SUM(CASE WHEN status = 'Resolvido' THEN 1 ELSE 0 END) AS resolvidos,
        SUM(CASE WHEN status IN ('Aberto', 'Em Analise') THEN 1 ELSE 0 END) AS pendentes,
        SUM(CASE WHEN severidade IN ('Critica', 'Alta') THEN 1 ELSE 0 END) AS criticos_altos
    FROM incidentes
")->fetch();

$totalIncidentes = (int) ($totais['total_incidentes'] ?? 0);
$resolvidos      = (int) ($totais['resolvidos'] ?? 0);
$pendentes       = (int) ($totais['pendentes'] ?? 0);
$criticosAltos   = (int) ($totais['criticos_altos'] ?? 0);
$taxaResolucao   = $totalIncidentes > 0 ? round(($resolvidos / $totalIncidentes) * 100, 1) : 0;

// 2. Incidentes agrupados por severidade (COUNT + GROUP BY)
$porSeveridade = $pdo->query("
    SELECT severidade, COUNT(*) AS total
    FROM incidentes
    GROUP BY severidade
    ORDER BY FIELD(severidade, 'Critica', 'Alta', 'Media', 'Baixa')
")->fetchAll();

// 3. Incidentes agrupados por status (COUNT + GROUP BY)
$porStatus = $pdo->query("
    SELECT status, COUNT(*) AS total
    FROM incidentes
    GROUP BY status
    ORDER BY FIELD(status, 'Aberto', 'Em Analise', 'Contido', 'Resolvido')
")->fetchAll();

// 4. Ativos com mais ocorrências de incidentes (LEFT JOIN + COUNT + GROUP BY)
$ativosMaisAfetados = $pdo->query("
    SELECT
        a.id,
        a.nome,
        a.tipo,
        a.endereco_ip,
        a.criticidade,
        COUNT(i.id) AS total_incidentes,
        (SELECT COUNT(*) FROM vulnerabilidades v WHERE v.ativo_id = a.id) AS total_cves
    FROM ativos a
    LEFT JOIN incidentes i ON i.ativo_id = a.id
    GROUP BY a.id, a.nome, a.tipo, a.endereco_ip, a.criticidade
    ORDER BY total_incidentes DESC, a.nome ASC
")->fetchAll();

// 5. Incidentes por categoria de ameaça (COUNT + GROUP BY)
$porCategoria = $pdo->query("
    SELECT
        c.nome,
        COUNT(i.id) AS total
    FROM categorias_ameaca c
    LEFT JOIN incidentes i ON i.categoria_id = c.id
    GROUP BY c.id, c.nome
    ORDER BY total DESC, c.nome ASC
")->fetchAll();

$tituloPagina = 'Relatório Consolidado';
$menuAtivo = 'relatorios';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h4 fw-semibold mb-1">Relatório Consolidado de Segurança</h1>
        <p class="text-secondary small mb-0">Indicadores gerenciais agregados via consultas SQL (<code>COUNT</code> e <code>GROUP BY</code>).</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(base_url('relatorios/exportar_pdf.php')) ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i>Exportar Incidentes (PDF)
        </a>
        <a href="<?= e(base_url('modules/incidentes/index.php')) ?>" class="btn btn-light btn-sm fw-semibold">
            <i class="bi bi-funnel me-1"></i>Ir para Busca Avançada
        </a>
    </div>
</div>

<!-- Cards de Resumo -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="soc-kpi h-100">
            <div class="soc-kpi-label">Total de Incidentes</div>
            <div class="soc-kpi-value"><?= $totalIncidentes ?></div>
            <div class="small text-secondary mt-1">Base histórica completa</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="soc-kpi h-100">
            <div class="soc-kpi-label">Críticos e Altos</div>
            <div class="soc-kpi-value text-danger"><?= $criticosAltos ?></div>
            <div class="small text-secondary mt-1">Maior impacto operacional</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="soc-kpi h-100">
            <div class="soc-kpi-label">Abertos / Em Análise</div>
            <div class="soc-kpi-value text-warning"><?= $pendentes ?></div>
            <div class="small text-secondary mt-1">Em tratamento pelo SOC</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="soc-kpi h-100">
            <div class="soc-kpi-label">Taxa de Resolução</div>
            <div class="soc-kpi-value text-success"><?= number_format($taxaResolucao, 1, ',', '.') ?>%</div>
            <div class="small text-secondary mt-1"><?= $resolvidos ?> de <?= $totalIncidentes ?> resolvido(s)</div>
        </div>
    </div>
</div>

<!-- Severidade e Status lado a lado -->
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card soc-card h-100">
            <div class="card-header py-3">
                <i class="bi bi-exclamation-diamond me-2 text-secondary"></i>Incidentes por Severidade
            </div>
            <div class="table-responsive">
                <table class="table table-soc align-middle">
                    <thead>
                        <tr>
                            <th class="ps-3">Severidade</th>
                            <th>Quantidade</th>
                            <th class="pe-3" style="width: 45%;">Participação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($porSeveridade)): ?>
                            <tr><td colspan="3" class="text-center text-secondary py-4">Sem dados registrados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($porSeveridade as $linha): ?>
                                <?php $pct = $totalIncidentes > 0 ? round(($linha['total'] / $totalIncidentes) * 100, 1) : 0; ?>
                                <tr>
                                    <td class="ps-3"><?= badge_severidade($linha['severidade']) ?></td>
                                    <td class="fw-semibold text-light"><?= (int) $linha['total'] ?></td>
                                    <td class="pe-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 6px; background-color: #252c37;">
                                                <div class="progress-bar bg-secondary" style="width: <?= $pct ?>%"></div>
                                            </div>
                                            <span class="small text-secondary text-nowrap"><?= number_format($pct, 1, ',', '.') ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card soc-card h-100">
            <div class="card-header py-3">
                <i class="bi bi-activity me-2 text-secondary"></i>Incidentes por Status
            </div>
            <div class="table-responsive">
                <table class="table table-soc align-middle">
                    <thead>
                        <tr>
                            <th class="ps-3">Status</th>
                            <th>Quantidade</th>
                            <th class="pe-3" style="width: 45%;">Participação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($porStatus)): ?>
                            <tr><td colspan="3" class="text-center text-secondary py-4">Sem dados registrados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($porStatus as $linha): ?>
                                <?php $pct = $totalIncidentes > 0 ? round(($linha['total'] / $totalIncidentes) * 100, 1) : 0; ?>
                                <tr>
                                    <td class="ps-3"><?= badge_status($linha['status']) ?></td>
                                    <td class="fw-semibold text-light"><?= (int) $linha['total'] ?></td>
                                    <td class="pe-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 6px; background-color: #252c37;">
                                                <div class="progress-bar bg-secondary" style="width: <?= $pct ?>%"></div>
                                            </div>
                                            <span class="small text-secondary text-nowrap"><?= number_format($pct, 1, ',', '.') ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Ativos com mais ocorrências e Categorias -->
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card soc-card h-100">
            <div class="card-header py-3">
                <i class="bi bi-hdd-network me-2 text-secondary"></i>Ativos com Mais Ocorrências
            </div>
            <div class="table-responsive">
                <table class="table table-soc table-hover align-middle">
                    <thead>
                        <tr>
                            <th class="ps-3">Ativo</th>
                            <th>IP</th>
                            <th>Criticidade</th>
                            <th>CVEs</th>
                            <th class="text-end pe-3">Incidentes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ativosMaisAfetados)): ?>
                            <tr><td colspan="5" class="text-center text-secondary py-4">Nenhum ativo cadastrado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($ativosMaisAfetados as $ativo): ?>
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-medium text-light"><?= e($ativo['nome']) ?></div>
                                        <div class="small text-secondary"><?= e($ativo['tipo']) ?></div>
                                    </td>
                                    <td><code class="text-light"><?= e($ativo['endereco_ip']) ?></code></td>
                                    <td><?= badge_severidade($ativo['criticidade']) ?></td>
                                    <td class="small text-secondary"><?= (int) $ativo['total_cves'] ?></td>
                                    <td class="text-end pe-3 fw-semibold text-light"><?= (int) $ativo['total_incidentes'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card soc-card h-100">
            <div class="card-header py-3">
                <i class="bi bi-tags me-2 text-secondary"></i>Por Categoria de Ameaça
            </div>
            <div class="table-responsive">
                <table class="table table-soc align-middle">
                    <thead>
                        <tr>
                            <th class="ps-3">Categoria</th>
                            <th class="text-end pe-3">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($porCategoria)): ?>
                            <tr><td colspan="2" class="text-center text-secondary py-4">Sem categorias.</td></tr>
                        <?php else: ?>
                            <?php foreach ($porCategoria as $cat): ?>
                                <tr>
                                    <td class="ps-3 small text-light"><?= e($cat['nome']) ?></td>
                                    <td class="text-end pe-3 fw-semibold text-light"><?= (int) $cat['total'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
