<?php
/**
 * Formulário de Cadastro e Edição de Ações de Mitigação.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

exigir_login();

$pdo = conectar_banco();
$usuarioAtual = usuario_logado();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$incidentePreSelecionado = isset($_GET['incidente_id']) ? (int) $_GET['incidente_id'] : 0;
$editando = ($id > 0);
$erros = [];

$incidentes = $pdo->query('SELECT id, titulo FROM incidentes ORDER BY id DESC')->fetchAll();
$usuarios   = $pdo->query('SELECT id, nome FROM usuarios ORDER BY nome ASC')->fetchAll();

$dados = [
    'incidente_id'   => $incidentePreSelecionado > 0 ? (string) $incidentePreSelecionado : '',
    'usuario_id'     => (string) ($usuarioAtual['id'] ?? ''),
    'descricao_acao' => '',
    'data_acao'      => date('Y-m-d\TH:i'),
];

if ($editando) {
    $stmt = $pdo->prepare('SELECT * FROM acoes_mitigacao WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $registro = $stmt->fetch();

    if (!$registro) {
        definir_flash('danger', 'Ação de mitigação não encontrada.');
        redirecionar('modules/mitigacoes/index.php');
    }

    $dados = [
        'incidente_id'   => (string) $registro['incidente_id'],
        'usuario_id'     => (string) $registro['usuario_id'],
        'descricao_acao' => $registro['descricao_acao'],
        'data_acao'      => date('Y-m-d\TH:i', strtotime($registro['data_acao'])),
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados['incidente_id']   = (string) ((int) ($_POST['incidente_id'] ?? 0));
    $dados['usuario_id']     = (string) ((int) ($_POST['usuario_id'] ?? 0));
    $dados['descricao_acao'] = trim($_POST['descricao_acao'] ?? '');
    $dados['data_acao']      = trim($_POST['data_acao'] ?? '');

    if ((int) $dados['incidente_id'] <= 0) {
        $erros[] = 'Selecione o incidente relacionado.';
    }
    if ((int) $dados['usuario_id'] <= 0) {
        $erros[] = 'Selecione o analista responsável.';
    }
    if ($dados['descricao_acao'] === '') {
        $erros[] = 'Descreva a ação de mitigação executada.';
    }
    if ($dados['data_acao'] === '' || strtotime($dados['data_acao']) === false) {
        $erros[] = 'Informe uma data e hora válida.';
    }

    if (empty($erros)) {
        $dataSql = date('Y-m-d H:i:s', strtotime($dados['data_acao']));

        if ($editando) {
            $stmt = $pdo->prepare('UPDATE acoes_mitigacao SET incidente_id = :incidente_id, usuario_id = :usuario_id, descricao_acao = :descricao, data_acao = :data_acao WHERE id = :id');
            $stmt->execute([
                ':incidente_id' => (int) $dados['incidente_id'],
                ':usuario_id'   => (int) $dados['usuario_id'],
                ':descricao'    => $dados['descricao_acao'],
                ':data_acao'    => $dataSql,
                ':id'           => $id,
            ]);
            definir_flash('success', 'Ação de mitigação atualizada com sucesso.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO acoes_mitigacao (incidente_id, usuario_id, descricao_acao, data_acao) VALUES (:incidente_id, :usuario_id, :descricao, :data_acao)');
            $stmt->execute([
                ':incidente_id' => (int) $dados['incidente_id'],
                ':usuario_id'   => (int) $dados['usuario_id'],
                ':descricao'    => $dados['descricao_acao'],
                ':data_acao'    => $dataSql,
            ]);
            definir_flash('success', 'Ação de mitigação registrada com sucesso.');
        }

        redirecionar('modules/mitigacoes/index.php');
    }
}

$tituloPagina = $editando ? 'Editar Ação de Mitigação' : 'Nova Ação de Mitigação';
$menuAtivo = 'mitigacoes';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 fw-semibold mb-0"><?= e($tituloPagina) ?></h1>
    <a href="<?= e(base_url('modules/mitigacoes/index.php')) ?>" class="btn btn-outline-secondary btn-sm">
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
                <label for="incidente_id" class="form-label small text-secondary">Incidente *</label>
                <select class="form-select" id="incidente_id" name="incidente_id" required>
                    <option value="">Selecione um incidente...</option>
                    <?php foreach ($incidentes as $inc): ?>
                        <option value="<?= (int) $inc['id'] ?>" <?= $dados['incidente_id'] === (string) $inc['id'] ? 'selected' : '' ?>>
                            #<?= (int) $inc['id'] ?> — <?= e($inc['titulo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="usuario_id" class="form-label small text-secondary">Analista *</label>
                <select class="form-select" id="usuario_id" name="usuario_id" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($usuarios as $usr): ?>
                        <option value="<?= (int) $usr['id'] ?>" <?= $dados['usuario_id'] === (string) $usr['id'] ? 'selected' : '' ?>>
                            <?= e($usr['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="data_acao" class="form-label small text-secondary">Data e hora *</label>
                <input type="datetime-local" class="form-control" id="data_acao" name="data_acao" value="<?= e($dados['data_acao']) ?>" required>
            </div>
            <div class="col-12">
                <label for="descricao_acao" class="form-label small text-secondary">Descrição da ação executada *</label>
                <textarea class="form-control" id="descricao_acao" name="descricao_acao" rows="4" required><?= e($dados['descricao_acao']) ?></textarea>
            </div>
            <div class="col-12 d-flex justify-content-end gap-2 pt-2">
                <a href="<?= e(base_url('modules/mitigacoes/index.php')) ?>" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-light fw-semibold px-4">Salvar</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
