<?php
/**
 * Formulário de Cadastro e Edição de Categorias de Ameaça.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

exigir_login();

$pdo = conectar_banco();
// Identifica se o formulário está editando (?id=N) ou criando uma nova categoria
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$editando = ($id > 0);
$erros = [];

$dados = [
    'nome'      => '',
    'descricao' => '',
];

// Em edição, carrega os dados da categoria pelo ID
if ($editando) {
    $stmt = $pdo->prepare('SELECT * FROM categorias_ameaca WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $registro = $stmt->fetch();

    if (!$registro) {
        definir_flash('danger', 'Categoria não encontrada.');
        redirecionar('modules/categorias/index.php');
    }

    $dados = $registro;
}

// Processamento do POST: valida nome obrigatório e trata violação de UNIQUE (SQLSTATE 23000)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados['nome']      = trim($_POST['nome'] ?? '');
    $dados['descricao'] = trim($_POST['descricao'] ?? '');

    if ($dados['nome'] === '') {
        $erros[] = 'O nome da categoria é obrigatório.';
    }

    if (empty($erros)) {
        try {
            if ($editando) {
                $stmt = $pdo->prepare('UPDATE categorias_ameaca SET nome = :nome, descricao = :descricao WHERE id = :id');
                $stmt->execute([
                    ':nome'      => $dados['nome'],
                    ':descricao' => $dados['descricao'],
                    ':id'        => $id,
                ]);
                definir_flash('success', 'Categoria atualizada com sucesso.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO categorias_ameaca (nome, descricao) VALUES (:nome, :descricao)');
                $stmt->execute([
                    ':nome'      => $dados['nome'],
                    ':descricao' => $dados['descricao'],
                ]);
                definir_flash('success', 'Categoria cadastrada com sucesso.');
            }
            redirecionar('modules/categorias/index.php');
        } catch (PDOException $e) {
            // SQLSTATE 23000 ocorre quando o nome já existe (restrição UNIQUE na coluna nome)
            if ($e->getCode() === '23000') {
                $erros[] = 'Já existe uma categoria cadastrada com esse nome.';
            } else {
                $erros[] = 'Erro ao salvar categoria.';
            }
        }
    }
}

$tituloPagina = $editando ? 'Editar Categoria de Ameaça' : 'Nova Categoria de Ameaça';
$menuAtivo = 'categorias';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 fw-semibold mb-0"><?= e($tituloPagina) ?></h1>
    <a href="<?= e(base_url('modules/categorias/index.php')) ?>" class="btn btn-outline-secondary btn-sm">
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
            <div class="col-12">
                <label for="nome" class="form-label small text-secondary">Nome da categoria *</label>
                <input type="text" class="form-control" id="nome" name="nome" value="<?= e($dados['nome']) ?>" placeholder="Ex.: Phishing, Ransomware, Injeção SQL" required>
            </div>
            <div class="col-12">
                <label for="descricao" class="form-label small text-secondary">Descrição</label>
                <textarea class="form-control" id="descricao" name="descricao" rows="3"><?= e($dados['descricao']) ?></textarea>
            </div>
            <div class="col-12 d-flex justify-content-end gap-2 pt-2">
                <a href="<?= e(base_url('modules/categorias/index.php')) ?>" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-light fw-semibold px-4">Salvar</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
