<?php
/**
 * Exclusão de ação de mitigação via POST.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

exigir_login();

// Permite exclusão somente via requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirecionar('modules/mitigacoes/index.php');
}

$id = (int) ($_POST['id'] ?? 0);

// Remove a ação de mitigação pelo ID usando Prepared Statement
if ($id > 0) {
    $pdo = conectar_banco();
    $stmt = $pdo->prepare('DELETE FROM acoes_mitigacao WHERE id = :id');
    $stmt->execute([':id' => $id]);
    definir_flash('success', 'Ação de mitigação excluída com sucesso.');
}

redirecionar('modules/mitigacoes/index.php');
