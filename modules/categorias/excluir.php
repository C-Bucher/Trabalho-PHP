<?php
/**
 * Exclusão de categoria de ameaça (respeitando integridade referencial).
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

exigir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirecionar('modules/categorias/index.php');
}

$id = (int) ($_POST['id'] ?? 0);

if ($id > 0) {
    $pdo = conectar_banco();
    try {
        $stmt = $pdo->prepare('DELETE FROM categorias_ameaca WHERE id = :id');
        $stmt->execute([':id' => $id]);
        definir_flash('success', 'Categoria excluída com sucesso.');
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            definir_flash('warning', 'Não é possível excluir esta categoria pois há incidentes associados a ela.');
        } else {
            definir_flash('danger', 'Erro ao excluir categoria.');
        }
    }
}

redirecionar('modules/categorias/index.php');
