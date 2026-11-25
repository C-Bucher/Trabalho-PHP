<?php
/**
 * Formulário de Cadastro e Edição de Usuários (com hash seguro de senha via password_hash).
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

exigir_login();

$pdo = conectar_banco();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$editando = ($id > 0);
$erros = [];
$perfis = ['admin', 'analista'];

$dados = [
    'nome'   => '',
    'email'  => '',
    'perfil' => 'analista',
];

if ($editando) {
    $stmt = $pdo->prepare('SELECT id, nome, email, perfil FROM usuarios WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $registro = $stmt->fetch();

    if (!$registro) {
        definir_flash('danger', 'Usuário não encontrado.');
        redirecionar('modules/usuarios/index.php');
    }

    $dados = $registro;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados['nome']   = trim($_POST['nome'] ?? '');
    $dados['email']  = trim($_POST['email'] ?? '');
    $dados['perfil'] = trim($_POST['perfil'] ?? 'analista');
    $senha           = (string) ($_POST['senha'] ?? '');

    if ($dados['nome'] === '') {
        $erros[] = 'O nome é obrigatório.';
    }
    if ($dados['email'] === '' || !filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Informe um e-mail válido.';
    }
    if (!in_array($dados['perfil'], $perfis, true)) {
        $erros[] = 'Perfil inválido.';
    }
    if (!$editando && strlen($senha) < 6) {
        $erros[] = 'A senha deve ter no mínimo 6 caracteres.';
    }
    if ($editando && $senha !== '' && strlen($senha) < 6) {
        $erros[] = 'A nova senha deve ter no mínimo 6 caracteres.';
    }

    if (empty($erros)) {
        try {
            if ($editando) {
                if ($senha !== '') {
                    $hash = password_hash($senha, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare('UPDATE usuarios SET nome = :nome, email = :email, perfil = :perfil, senha_hash = :senha_hash WHERE id = :id');
                    $stmt->execute([
                        ':nome'       => $dados['nome'],
                        ':email'      => $dados['email'],
                        ':perfil'     => $dados['perfil'],
                        ':senha_hash' => $hash,
                        ':id'         => $id,
                    ]);
                } else {
                    $stmt = $pdo->prepare('UPDATE usuarios SET nome = :nome, email = :email, perfil = :perfil WHERE id = :id');
                    $stmt->execute([
                        ':nome'   => $dados['nome'],
                        ':email'  => $dados['email'],
                        ':perfil' => $dados['perfil'],
                        ':id'     => $id,
                    ]);
                }

                // Atualiza os dados da sessão caso o próprio usuário tenha editado o perfil
                if ($id === (int) ($_SESSION['usuario_id'] ?? 0)) {
                    $_SESSION['usuario_nome']   = $dados['nome'];
                    $_SESSION['usuario_email']  = $dados['email'];
                    $_SESSION['usuario_perfil'] = $dados['perfil'];
                }

                definir_flash('success', 'Usuário atualizado com sucesso.');
            } else {
                $hash = password_hash($senha, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare('INSERT INTO usuarios (nome, email, senha_hash, perfil) VALUES (:nome, :email, :senha_hash, :perfil)');
                $stmt->execute([
                    ':nome'       => $dados['nome'],
                    ':email'      => $dados['email'],
                    ':senha_hash' => $hash,
                    ':perfil'     => $dados['perfil'],
                ]);
                definir_flash('success', 'Usuário cadastrado com sucesso.');
            }

            redirecionar('modules/usuarios/index.php');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $erros[] = 'Já existe um usuário cadastrado com este e-mail.';
            } else {
                $erros[] = 'Erro ao salvar usuário.';
            }
        }
    }
}

$tituloPagina = $editando ? 'Editar Usuário' : 'Novo Usuário';
$menuAtivo = 'usuarios';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 fw-semibold mb-0"><?= e($tituloPagina) ?></h1>
    <a href="<?= e(base_url('modules/usuarios/index.php')) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Voltar
    </a>
</div>

<?php if (!empty($erros)): ?>
    <div class="alert alert-danger border-0 mb-4">
        <ul class="mb-0 small">
            <?php foreach ($erros as $erro): ?>
                <li><?= e($erro) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card soc-card">
    <div class="card-body p-4">
        <form method="post" action="" class="row g-3">
            <div class="col-md-6">
                <label for="nome" class="form-label small text-secondary">Nome completo *</label>
                <input type="text" class="form-control" id="nome" name="nome" value="<?= e($dados['nome']) ?>" required>
            </div>
            <div class="col-md-6">
                <label for="email" class="form-label small text-secondary">E-mail *</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= e($dados['email']) ?>" required>
            </div>
            <div class="col-md-6">
                <label for="perfil" class="form-label small text-secondary">Perfil *</label>
                <select class="form-select" id="perfil" name="perfil" required>
                    <option value="analista" <?= $dados['perfil'] === 'analista' ? 'selected' : '' ?>>Analista</option>
                    <option value="admin" <?= $dados['perfil'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
                </select>
            </div>
            <div class="col-md-6">
                <label for="senha" class="form-label small text-secondary">
                    <?= $editando ? 'Nova senha (deixe em branco para manter a atual)' : 'Senha * (mínimo 6 caracteres)' ?>
                </label>
                <input type="password" class="form-control" id="senha" name="senha" <?= $editando ? '' : 'required' ?> minlength="6">
            </div>
            <div class="col-12 d-flex justify-content-end gap-2 pt-2">
                <a href="<?= e(base_url('modules/usuarios/index.php')) ?>" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-light fw-semibold px-4">Salvar</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
