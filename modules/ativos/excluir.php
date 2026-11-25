<?php
/**
 * Exclusão de ativo monitorado (respeitando integridade referencial).
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

exigir_login();

// Aceita exclusão somente via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirecionar('modules/ativos/index.php');
}

$id = (int) ($_POST['id'] ?? 0);

if ($id > 0) {
    $pdo = conectar_banco();
    try {
        $stmt = $pdo->prepare('DELETE FROM ativos WHERE id = :id');
        $stmt->execute([':id' => $id]);
        definir_flash('success', 'Ativo excluído com sucesso.');
    } catch (PDOException $e) {
        // SQLSTATE 23000 indica violação de chave estrangeira (ON DELETE RESTRICT em incidentes/vulnerabilidades)
        if ($e->getCode() === '23000') {
            definir_flash('warning', 'Não é possível excluir este ativo porque existem incidentes ou vulnerabilidades vinculados a ele.');
        } else {
            definir_flash('danger', 'Erro ao excluir ativo.');
        }
    }
}

redirecionar('modules/ativos/index.php');
