<?php
/**
 * Listagem de Usuários (Analistas e Administradores do Mini-SOC).
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

exigir_login();

$pdo = conectar_banco();
// Obtém o usuário autenticado para marcar o selo "Você" e ocultar o botão de autoexclusão
$usuarioAtual = usuario_logado();

// Seleciona apenas campos públicos (nunca traz senha_hash para a listagem)
$usuarios = $pdo->query('SELECT id, nome, email, perfil, criado_em FROM usuarios ORDER BY nome ASC')->fetchAll();

$tituloPagina = 'Usuários';
$menuAtivo = 'usuarios';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-semibold mb-1">Usuários do Sistema</h1>
        <p class="text-secondary small mb-0">Analistas e administradores com acesso ao Mini-SOC.</p>
    </div>
    <a href="<?= e(base_url('modules/usuarios/form.php')) ?>" class="btn btn-light btn-sm fw-semibold">
        <i class="bi bi-plus-lg me-1"></i>Novo Usuário
    </a>
</div>

<div class="card soc-card">
    <div class="table-responsive">
        <table class="table table-soc table-hover align-middle">
            <thead>
                <tr>
                    <th class="ps-3">#</th>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Perfil</th>
                    <th>Criado em</th>
                    <th class="text-end pe-3">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($usuarios)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-secondary py-4">Nenhum usuário cadastrado.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($usuarios as $usr): ?>
                        <tr>
                            <td class="ps-3 text-secondary">#<?= (int) $usr['id'] ?></td>
                            <td class="fw-medium text-light">
                                <?= e($usr['nome']) ?>
                                <?php if ((int) $usr['id'] === $usuarioAtual['id']): ?>
                                    <span class="badge text-bg-secondary ms-1">Você</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-secondary"><?= e($usr['email']) ?></td>
                            <td>
                                <span class="badge rounded-pill <?= $usr['perfil'] === 'admin' ? 'badge-sev-baixa' : 'badge-sev-media' ?>">
                                    <?= e(ucfirst($usr['perfil'])) ?>
                                </span>
                            </td>
                            <td class="small text-secondary text-nowrap"><?= e(formatar_data($usr['criado_em'])) ?></td>
                            <td class="text-end pe-3 text-nowrap">
                                <a href="<?= e(base_url('modules/usuarios/form.php?id=' . (int) $usr['id'])) ?>" class="btn btn-outline-secondary btn-sm" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if ((int) $usr['id'] !== $usuarioAtual['id']): ?>
                                    <form method="post" action="<?= e(base_url('modules/usuarios/excluir.php')) ?>" class="d-inline" onsubmit="return confirm('Deseja excluir este usuário?');">
                                        <input type="hidden" name="id" value="<?= (int) $usr['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Excluir">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
