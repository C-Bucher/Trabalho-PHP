<?php
/**
 * Exclusão de usuário (impede autoexclusão e respeita integridade referencial).
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

exigir_login();

// Aceita exclusão apenas via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirecionar('modules/usuarios/index.php');
}

$id = (int) ($_POST['id'] ?? 0);
$usuarioAtual = usuario_logado();

// Regra de segurança: impede que o usuário autenticado exclua a própria conta ativa
if ($id === $usuarioAtual['id']) {
    definir_flash('warning', 'Você não pode excluir o próprio usuário autenticado.');
    redirecionar('modules/usuarios/index.php');
}

if ($id > 0) {
    $pdo = conectar_banco();
    try {
        $stmt = $pdo->prepare('DELETE FROM usuarios WHERE id = :id');
        $stmt->execute([':id' => $id]);
        definir_flash('success', 'Usuário excluído com sucesso.');
    } catch (PDOException $e) {
        // SQLSTATE 23000 ocorre quando o analista possui incidentes ou mitigações vinculadas (ON DELETE RESTRICT)
        if ($e->getCode() === '23000') {
            definir_flash('warning', 'Não é possível excluir este usuário porque ele possui incidentes ou ações de mitigação vinculados.');
        } else {
            definir_flash('danger', 'Erro ao excluir usuário.');
        }
    }
}

redirecionar('modules/usuarios/index.php');
