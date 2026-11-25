<?php
/**
 * Listagem de Categorias de Ameaça.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

exigir_login();

$pdo = conectar_banco();
$categorias = $pdo->query("
    SELECT
        c.*,
        (SELECT COUNT(*) FROM incidentes i WHERE i.categoria_id = c.id) AS total_incidentes
    FROM categorias_ameaca c
    ORDER BY c.nome ASC
")->fetchAll();

$tituloPagina = 'Categorias de Ameaça';
$menuAtivo = 'categorias';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-semibold mb-1">Categorias de Ameaça</h1>
        <p class="text-secondary small mb-0">Classificação dos tipos de ataques e eventos de cibersegurança.</p>
    </div>
    <a href="<?= e(base_url('modules/categorias/form.php')) ?>" class="btn btn-light btn-sm fw-semibold">
        <i class="bi bi-plus-lg me-1"></i>Nova Categoria
    </a>
</div>

<div class="card soc-card">
    <div class="table-responsive">
        <table class="table table-soc table-hover align-middle">
            <thead>
                <tr>
                    <th class="ps-3">#</th>
                    <th>Nome</th>
                    <th>Descrição</th>
                    <th>Incidentes</th>
                    <th class="text-end pe-3">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categorias)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-secondary py-4">Nenhuma categoria cadastrada.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categorias as $cat): ?>
                        <tr>
                            <td class="ps-3 text-secondary">#<?= (int) $cat['id'] ?></td>
                            <td class="fw-medium text-light"><?= e($cat['nome']) ?></td>
                            <td class="small text-secondary"><?= e($cat['descricao']) ?></td>
                            <td class="small text-secondary"><?= (int) $cat['total_incidentes'] ?></td>
                            <td class="text-end pe-3 text-nowrap">
                                <a href="<?= e(base_url('modules/categorias/form.php?id=' . (int) $cat['id'])) ?>" class="btn btn-outline-secondary btn-sm" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="post" action="<?= e(base_url('modules/categorias/excluir.php')) ?>" class="d-inline" onsubmit="return confirm('Deseja excluir esta categoria de ameaça?');">
                                    <input type="hidden" name="id" value="<?= (int) $cat['id'] ?>">
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
