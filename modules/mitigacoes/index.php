<?php
/**
 * Listagem de Ações de Mitigação, com filtro opcional por incidente (?incidente_id=).
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

exigir_login();

$pdo = conectar_banco();
// Filtro opcional recebido pela URL (?incidente_id=N) para exibir apenas mitigações de um incidente específico
$incidenteId = isset($_GET['incidente_id']) ? (int) $_GET['incidente_id'] : 0;

// Consulta o histórico de ações de mitigação juntando com o título do incidente e o nome do analista
$sql = "
    SELECT
        m.id,
        m.incidente_id,
        m.descricao_acao,
        m.data_acao,
        i.titulo AS incidente_titulo,
        u.nome AS analista_nome
    FROM acoes_mitigacao m
    INNER JOIN incidentes i ON i.id = m.incidente_id
    INNER JOIN usuarios u ON u.id = m.usuario_id
";
$params = [];

// Se um incidente foi especificado na URL, restringe a listagem usando Prepared Statement
if ($incidenteId > 0) {
    $sql .= ' WHERE m.incidente_id = :incidente_id';
    $params[':incidente_id'] = $incidenteId;
}

// Ordena da ação mais recente para a mais antiga
$sql .= ' ORDER BY m.data_acao DESC, m.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$mitigacoes = $stmt->fetchAll();

$tituloPagina = 'Ações de Mitigação';
$menuAtivo = 'mitigacoes';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h4 fw-semibold mb-1">Ações de Mitigação</h1>
        <p class="text-secondary small mb-0">
            Histórico de medidas de contenção e resposta executadas pelos analistas.
            <?php if ($incidenteId > 0): ?>
                <span class="ms-2">
                    <a href="<?= e(base_url('modules/mitigacoes/index.php')) ?>" class="link-secondary">Mostrar todas</a>
                </span>
            <?php endif; ?>
        </p>
    </div>
    <a href="<?= e(base_url('modules/mitigacoes/form.php' . ($incidenteId > 0 ? '?incidente_id=' . $incidenteId : ''))) ?>" class="btn btn-light btn-sm fw-semibold">
        <i class="bi bi-plus-lg me-1"></i>Nova Ação
    </a>
</div>

<div class="card soc-card">
    <div class="table-responsive">
        <table class="table table-soc table-hover align-middle">
            <thead>
                <tr>
                    <th class="ps-3">#</th>
                    <th>Incidente</th>
                    <th>Descrição da Ação</th>
                    <th>Analista</th>
                    <th>Data da Ação</th>
                    <th class="text-end pe-3">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($mitigacoes)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-secondary py-4">Nenhuma ação de mitigação registrada.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($mitigacoes as $mit): ?>
                        <tr>
                            <td class="ps-3 text-secondary">#<?= (int) $mit['id'] ?></td>
                            <td>
                                <a href="<?= e(base_url('modules/incidentes/form.php?id=' . (int) $mit['incidente_id'])) ?>" class="text-decoration-none text-light small fw-medium">
                                    #<?= (int) $mit['incidente_id'] ?> — <?= e($mit['incidente_titulo']) ?>
                                </a>
                            </td>
                            <td class="small text-secondary"><?= e($mit['descricao_acao']) ?></td>
                            <td class="small text-secondary text-nowrap"><?= e($mit['analista_nome']) ?></td>
                            <td class="small text-secondary text-nowrap"><?= e(formatar_data($mit['data_acao'])) ?></td>
                            <td class="text-end pe-3 text-nowrap">
                                <a href="<?= e(base_url('modules/mitigacoes/form.php?id=' . (int) $mit['id'])) ?>" class="btn btn-outline-secondary btn-sm" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="post" action="<?= e(base_url('modules/mitigacoes/excluir.php')) ?>" class="d-inline" onsubmit="return confirm('Deseja excluir esta ação de mitigação?');">
                                    <input type="hidden" name="id" value="<?= (int) $mit['id'] ?>">
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
