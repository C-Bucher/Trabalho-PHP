<?php
/**
 * Formulário de Cadastro e Edição de Vulnerabilidades (CVEs).
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

exigir_login();

$pdo = conectar_banco();
// Define se o formulário está em modo de edição (?id=N) ou criação
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$editando = ($id > 0);
$erros = [];
$severidades = ['Baixa', 'Media', 'Alta', 'Critica'];

// Carrega os ativos disponíveis para vincular a CVE via chave estrangeira (ativo_id)
$ativos = $pdo->query('SELECT id, nome, endereco_ip FROM ativos ORDER BY nome ASC')->fetchAll();

$dados = [
    'codigo_cve' => '',
    'titulo'     => '',
    'severidade' => 'Media',
    'ativo_id'   => '',
];

// Em edição, carrega os dados atuais da vulnerabilidade
if ($editando) {
    $stmt = $pdo->prepare('SELECT * FROM vulnerabilidades WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $registro = $stmt->fetch();

    if (!$registro) {
        definir_flash('danger', 'Vulnerabilidade não encontrada.');
        redirecionar('modules/vulnerabilidades/index.php');
    }

    $dados = [
        'codigo_cve' => $registro['codigo_cve'],
        'titulo'     => $registro['titulo'],
        'severidade' => $registro['severidade'],
        'ativo_id'   => (string) $registro['ativo_id'],
    ];
}

// Processa o envio do formulário via POST (normaliza código CVE para maiúsculas, valida e persiste)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados['codigo_cve'] = strtoupper(trim($_POST['codigo_cve'] ?? ''));
    $dados['titulo']     = trim($_POST['titulo'] ?? '');
    $dados['severidade'] = trim($_POST['severidade'] ?? 'Media');
    $dados['ativo_id']   = (string) ((int) ($_POST['ativo_id'] ?? 0));

    if ($dados['codigo_cve'] === '') {
        $erros[] = 'O código CVE é obrigatório (ex.: CVE-2024-3094).';
    }
    if ($dados['titulo'] === '') {
        $erros[] = 'O título da vulnerabilidade é obrigatório.';
    }
    if (!in_array($dados['severidade'], $severidades, true)) {
        $erros[] = 'Severidade inválida.';
    }
    if ((int) $dados['ativo_id'] <= 0) {
        $erros[] = 'Selecione o ativo afetado.';
    }

    if (empty($erros)) {
        if ($editando) {
            $stmt = $pdo->prepare('UPDATE vulnerabilidades SET codigo_cve = :cve, titulo = :titulo, severidade = :sev, ativo_id = :ativo_id WHERE id = :id');
            $stmt->execute([
                ':cve'      => $dados['codigo_cve'],
                ':titulo'   => $dados['titulo'],
                ':sev'      => $dados['severidade'],
                ':ativo_id' => (int) $dados['ativo_id'],
                ':id'       => $id,
            ]);
            definir_flash('success', 'Vulnerabilidade atualizada com sucesso.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO vulnerabilidades (codigo_cve, titulo, severidade, ativo_id) VALUES (:cve, :titulo, :sev, :ativo_id)');
            $stmt->execute([
                ':cve'      => $dados['codigo_cve'],
                ':titulo'   => $dados['titulo'],
                ':sev'      => $dados['severidade'],
                ':ativo_id' => (int) $dados['ativo_id'],
            ]);
            definir_flash('success', 'Vulnerabilidade cadastrada com sucesso.');
        }

        redirecionar('modules/vulnerabilidades/index.php');
    }
}

$tituloPagina = $editando ? 'Editar Vulnerabilidade' : 'Nova Vulnerabilidade';
$menuAtivo = 'vulnerabilidades';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 fw-semibold mb-0"><?= e($tituloPagina) ?></h1>
    <a href="<?= e(base_url('modules/vulnerabilidades/index.php')) ?>" class="btn btn-outline-secondary btn-sm">
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
            <div class="col-md-4">
                <label for="codigo_cve" class="form-label small text-secondary">Código CVE *</label>
                <input type="text" class="form-control" id="codigo_cve" name="codigo_cve" value="<?= e($dados['codigo_cve']) ?>" placeholder="Ex.: CVE-2024-3094" required>
            </div>
            <div class="col-md-8">
                <label for="titulo" class="form-label small text-secondary">Título *</label>
                <input type="text" class="form-control" id="titulo" name="titulo" value="<?= e($dados['titulo']) ?>" required>
            </div>
            <div class="col-md-6">
                <label for="ativo_id" class="form-label small text-secondary">Ativo afetado *</label>
                <select class="form-select" id="ativo_id" name="ativo_id" required>
                    <option value="">Selecione um ativo...</option>
                    <?php foreach ($ativos as $ativo): ?>
                        <option value="<?= (int) $ativo['id'] ?>" <?= $dados['ativo_id'] === (string) $ativo['id'] ? 'selected' : '' ?>>
                            <?= e($ativo['nome']) ?> (<?= e($ativo['endereco_ip']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="severidade" class="form-label small text-secondary">Severidade *</label>
                <select class="form-select" id="severidade" name="severidade" required>
                    <?php foreach ($severidades as $sev): ?>
                        <option value="<?= e($sev) ?>" <?= $dados['severidade'] === $sev ? 'selected' : '' ?>>
                            <?= e(rotulo_severidade($sev)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 d-flex justify-content-end gap-2 pt-2">
                <a href="<?= e(base_url('modules/vulnerabilidades/index.php')) ?>" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-light fw-semibold px-4">Salvar</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
