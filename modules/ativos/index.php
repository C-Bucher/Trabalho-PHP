<?php
/**
 * Listagem de Ativos Monitorados.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

exigir_login();

$pdo = conectar_banco();
// Lista todos os ativos ordenados por nome e usa subconsultas correlacionadas para contar incidentes e CVEs de cada um
$ativos = $pdo->query("
    SELECT
        a.*,
        (SELECT COUNT(*) FROM incidentes i WHERE i.ativo_id = a.id) AS total_incidentes,
        (SELECT COUNT(*) FROM vulnerabilidades v WHERE v.ativo_id = a.id) AS total_vulns
    FROM ativos a
    ORDER BY a.nome ASC
")->fetchAll();

$tituloPagina = 'Ativos Monitorados';
$menuAtivo = 'ativos';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-semibold mb-1">Ativos Monitorados</h1>
        <p class="text-secondary small mb-0">Servidores, firewalls e estações acompanhados pelo Mini-SOC.</p>
    </div>
    <a href="<?= e(base_url('modules/ativos/form.php')) ?>" class="btn btn-light btn-sm fw-semibold">
        <i class="bi bi-plus-lg me-1"></i>Novo Ativo
    </a>
</div>

<div class="card soc-card">
    <div class="table-responsive">
        <table class="table table-soc table-hover align-middle">
            <thead>
                <tr>
                    <th class="ps-3">#</th>
                    <th>Nome</th>
                    <th>Tipo</th>
                    <th>Endereço IP</th>
                    <th>Criticidade</th>
                    <th>Incidentes</th>
                    <th>CVEs</th>
                    <th class="text-end pe-3">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ativos)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-secondary py-4">Nenhum ativo cadastrado.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($ativos as $ativo): ?>
                        <tr>
                            <td class="ps-3 text-secondary">#<?= (int) $ativo['id'] ?></td>
                            <td class="fw-medium text-light"><?= e($ativo['nome']) ?></td>
                            <td class="small text-secondary"><?= e($ativo['tipo']) ?></td>
                            <td><code class="text-light"><?= e($ativo['endereco_ip']) ?></code></td>
                            <td><?= badge_severidade($ativo['criticidade']) ?></td>
                            <td class="small text-secondary"><?= (int) $ativo['total_incidentes'] ?></td>
                            <td class="small text-secondary"><?= (int) $ativo['total_vulns'] ?></td>
                            <td class="text-end pe-3 text-nowrap">
                                <a href="<?= e(base_url('modules/ativos/form.php?id=' . (int) $ativo['id'])) ?>" class="btn btn-outline-secondary btn-sm" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="post" action="<?= e(base_url('modules/ativos/excluir.php')) ?>" class="d-inline" onsubmit="return confirm('Deseja excluir este ativo?');">
                                    <input type="hidden" name="id" value="<?= (int) $ativo['id'] ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Excluir">
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
