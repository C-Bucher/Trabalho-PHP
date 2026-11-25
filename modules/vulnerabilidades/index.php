<?php
/**
 * Listagem de Vulnerabilidades (CVEs) vinculadas aos Ativos Monitorados.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

exigir_login();

$pdo = conectar_banco();
$vulnerabilidades = $pdo->query("
    SELECT
        v.id,
        v.codigo_cve,
        v.titulo,
        v.severidade,
        a.nome AS ativo_nome,
        a.endereco_ip
    FROM vulnerabilidades v
    INNER JOIN ativos a ON a.id = v.ativo_id
    ORDER BY FIELD(v.severidade, 'Critica', 'Alta', 'Media', 'Baixa'), v.id DESC
")->fetchAll();

$tituloPagina = 'Vulnerabilidades (CVEs)';
$menuAtivo = 'vulnerabilidades';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-semibold mb-1">Vulnerabilidades</h1>
        <p class="text-secondary small mb-0">Falhas conhecidas (CVEs) mapeadas nos ativos monitorados.</p>
    </div>
    <a href="<?= e(base_url('modules/vulnerabilidades/form.php')) ?>" class="btn btn-light btn-sm fw-semibold">
        <i class="bi bi-plus-lg me-1"></i>Nova Vulnerabilidade
    </a>
</div>

<div class="card soc-card">
    <div class="table-responsive">
        <table class="table table-soc table-hover align-middle">
            <thead>
                <tr>
                    <th class="ps-3">#</th>
                    <th>Código CVE</th>
                    <th>Título</th>
                    <th>Severidade</th>
                    <th>Ativo / IP</th>
                    <th class="text-end pe-3">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($vulnerabilidades)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-secondary py-4">Nenhuma vulnerabilidade cadastrada.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($vulnerabilidades as $vuln): ?>
                        <tr>
                            <td class="ps-3 text-secondary">#<?= (int) $vuln['id'] ?></td>
                            <td><code class="text-light"><?= e($vuln['codigo_cve']) ?></code></td>
                            <td class="fw-medium text-light"><?= e($vuln['titulo']) ?></td>
                            <td><?= badge_severidade($vuln['severidade']) ?></td>
                            <td>
                                <div class="small"><?= e($vuln['ativo_nome']) ?></div>
                                <div class="text-secondary" style="font-size: 0.78rem;"><?= e($vuln['endereco_ip']) ?></div>
                            </td>
                            <td class="text-end pe-3 text-nowrap">
                                <a href="<?= e(base_url('modules/vulnerabilidades/form.php?id=' . (int) $vuln['id'])) ?>" class="btn btn-outline-secondary btn-sm" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="post" action="<?= e(base_url('modules/vulnerabilidades/excluir.php')) ?>" class="d-inline" onsubmit="return confirm('Deseja excluir esta vulnerabilidade?');">
                                    <input type="hidden" name="id" value="<?= (int) $vuln['id'] ?>">
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
