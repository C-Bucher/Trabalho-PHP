<?php
/**
 * Tela de autenticação do Mini-SOC.
 * Valida credenciais com PDO + password_verify() e regenera o ID de sessão no login.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

// Se já estiver autenticado, redireciona direto para o painel
if (esta_logado()) {
    redirecionar('index.php');
}

$erro = '';
$emailInformado = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailInformado = trim($_POST['email'] ?? '');
    $senha = (string) ($_POST['senha'] ?? '');

    if ($emailInformado === '' || $senha === '') {
        $erro = 'Informe o e-mail e a senha para entrar.';
    } else {
        $pdo = conectar_banco();
        $stmt = $pdo->prepare('SELECT id, nome, email, senha_hash, perfil FROM usuarios WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $emailInformado]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha, $usuario['senha_hash'])) {
            // Proteção contra fixação de sessão
            session_regenerate_id(true);

            $_SESSION['usuario_id']     = (int) $usuario['id'];
            $_SESSION['usuario_nome']   = $usuario['nome'];
            $_SESSION['usuario_email']  = $usuario['email'];
            $_SESSION['usuario_perfil'] = $usuario['perfil'];

            definir_flash('success', 'Bem-vindo(a), ' . $usuario['nome'] . '!');
            redirecionar('index.php');
        } else {
            $erro = 'E-mail ou senha inválidos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Mini-SOC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(base_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
<div class="login-wrapper">
    <div class="card soc-card login-card shadow-lg">
        <div class="card-body p-4 p-sm-5">
            <div class="text-center mb-4">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle border border-secondary-subtle mb-3" style="width: 54px; height: 54px; background-color: rgba(148,163,184,0.08);">
                    <i class="bi bi-shield-lock fs-3 text-light"></i>
                </div>
                <h1 class="h4 fw-semibold mb-1">Mini-SOC</h1>
                <p class="text-secondary small mb-0">Gestão de Incidentes e Vulnerabilidades</p>
            </div>

            <?php exibir_flash(); ?>

            <?php if ($erro !== ''): ?>
                <div class="alert alert-danger border-0 py-2 small" role="alert">
                    <i class="bi bi-exclamation-circle me-1"></i><?= e($erro) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="" novalidate>
                <div class="mb-3">
                    <label for="email" class="form-label small text-secondary">E-mail corporativo</label>
                    <input
                        type="email"
                        class="form-control"
                        id="email"
                        name="email"
                        value="<?= e($emailInformado) ?>"
                        placeholder="admin@minisoc.local"
                        required
                        autofocus
                    >
                </div>

                <div class="mb-4">
                    <label for="senha" class="form-label small text-secondary">Senha</label>
                    <input
                        type="password"
                        class="form-control"
                        id="senha"
                        name="senha"
                        placeholder="••••••••"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-light w-100 fw-semibold">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Entrar no Painel
                </button>
            </form>

            <hr class="border-secondary-subtle my-4">
            <div class="small text-secondary text-center">
                Acesso de demonstração:<br>
                <code>admin@minisoc.local</code> / <code>Admin@123</code>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
