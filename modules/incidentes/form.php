<?php
/**
 * Formulário de Cadastro e Edição de Incidentes de Segurança.
 * Carrega opções de ativos, categorias de ameaça e analistas diretamente do MySQL.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

exigir_login();

$pdo = conectar_banco();
$usuarioAtual = usuario_logado();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$editando = ($id > 0);
$erros = [];

$severidades = ['Baixa', 'Media', 'Alta', 'Critica'];
$listaStatus = ['Aberto', 'Em Analise', 'Contido', 'Resolvido'];

// Carrega listas relacionadas para preencher os <select>
$ativos     = $pdo->query('SELECT id, nome, endereco_ip FROM ativos ORDER BY nome')->fetchAll();
$categorias = $pdo->query('SELECT id, nome FROM categorias_ameaca ORDER BY nome')->fetchAll();
$usuarios   = $pdo->query('SELECT id, nome FROM usuarios ORDER BY nome')->fetchAll();

// Valores padrão do formulário
$dados = [
    'titulo'          => '',
    'descricao'       => '',
    'severidade'      => 'Media',
    'status'          => 'Aberto',
    'data_ocorrencia' => date('Y-m-d\TH:i'),
    'ativo_id'        => '',
    'categoria_id'    => '',
    'usuario_id'      => (string) ($usuarioAtual['id'] ?? ''),
];

// Se estiver editando, carrega o registro atual
if ($editando) {
    $stmt = $pdo->prepare('SELECT * FROM incidentes WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $registro = $stmt->fetch();

    if (!$registro) {
        definir_flash('danger', 'Incidente não encontrado.');
        redirecionar('modules/incidentes/index.php');
    }

    $dados = [
        'titulo'          => $registro['titulo'],
        'descricao'       => $registro['descricao'],
        'severidade'      => $registro['severidade'],
        'status'          => $registro['status'],
        'data_ocorrencia' => date('Y-m-d\TH:i', strtotime($registro['data_ocorrencia'])),
        'ativo_id'        => (string) $registro['ativo_id'],
        'categoria_id'    => (string) $registro['categoria_id'],
        'usuario_id'      => (string) $registro['usuario_id'],
    ];
}

// Processamento do envio (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados['titulo']          = trim($_POST['titulo'] ?? '');
    $dados['descricao']       = trim($_POST['descricao'] ?? '');
    $dados['severidade']      = trim($_POST['severidade'] ?? 'Media');
    $dados['status']          = trim($_POST['status'] ?? 'Aberto');
    $dados['data_ocorrencia'] = trim($_POST['data_ocorrencia'] ?? '');
    $dados['ativo_id']        = (string) ((int) ($_POST['ativo_id'] ?? 0));
    $dados['categoria_id']    = (string) ((int) ($_POST['categoria_id'] ?? 0));
    $dados['usuario_id']      = (string) ((int) ($_POST['usuario_id'] ?? 0));

    if ($dados['titulo'] === '') {
        $erros[] = 'O título do incidente é obrigatório.';
    }
    if ($dados['descricao'] === '') {
        $erros[] = 'A descrição do incidente é obrigatória.';
    }
    if (!in_array($dados['severidade'], $severidades, true)) {
        $erros[] = 'Severidade inválida.';
    }
    if (!in_array($dados['status'], $listaStatus, true)) {
        $erros[] = 'Status inválido.';
    }
    if ($dados['data_ocorrencia'] === '' || strtotime($dados['data_ocorrencia']) === false) {
        $erros[] = 'Informe uma data e hora de ocorrência válida.';
    }
    if ((int) $dados['ativo_id'] <= 0) {
        $erros[] = 'Selecione o ativo afetado.';
    }
    if ((int) $dados['categoria_id'] <= 0) {
        $erros[] = 'Selecione a categoria de ameaça.';
    }
    if ((int) $dados['usuario_id'] <= 0) {
        $erros[] = 'Selecione o analista responsável.';
    }

    if (empty($erros)) {
        $dataSql = date('Y-m-d H:i:s', strtotime($dados['data_ocorrencia']));

        if ($editando) {
            $sql = "
                UPDATE incidentes
                SET titulo = :titulo,
                    descricao = :descricao,
                    severidade = :severidade,
                    status = :status,
                    data_ocorrencia = :data_ocorrencia,
                    ativo_id = :ativo_id,
                    categoria_id = :categoria_id,
                    usuario_id = :usuario_id
                WHERE id = :id
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':titulo'          => $dados['titulo'],
                ':descricao'       => $dados['descricao'],
                ':severidade'      => $dados['severidade'],
                ':status'          => $dados['status'],
                ':data_ocorrencia' => $dataSql,
                ':ativo_id'        => (int) $dados['ativo_id'],
                ':categoria_id'    => (int) $dados['categoria_id'],
                ':usuario_id'      => (int) $dados['usuario_id'],
                ':id'              => $id,
            ]);
            definir_flash('success', 'Incidente atualizado com sucesso.');
        } else {
            $sql = "
                INSERT INTO incidentes
                    (titulo, descricao, severidade, status, data_ocorrencia, ativo_id, categoria_id, usuario_id)
                VALUES
                    (:titulo, :descricao, :severidade, :status, :data_ocorrencia, :ativo_id, :categoria_id, :usuario_id)
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':titulo'          => $dados['titulo'],
                ':descricao'       => $dados['descricao'],
                ':severidade'      => $dados['severidade'],
                ':status'          => $dados['status'],
                ':data_ocorrencia' => $dataSql,
                ':ativo_id'        => (int) $dados['ativo_id'],
                ':categoria_id'    => (int) $dados['categoria_id'],
                ':usuario_id'      => (int) $dados['usuario_id'],
            ]);
            definir_flash('success', 'Incidente registrado com sucesso.');
        }

        redirecionar('modules/incidentes/index.php');
    }
}

// Na edição, exibe o histórico de mitigações deste incidente
$mitigacoes = [];
if ($editando) {
    $stmtMit = $pdo->prepare("
        SELECT m.id, m.descricao_acao, m.data_acao, u.nome AS analista_nome
        FROM acoes_mitigacao m
        INNER JOIN usuarios u ON u.id = m.usuario_id
        WHERE m.incidente_id = :incidente_id
        ORDER BY m.data_acao DESC
    ");
    $stmtMit->execute([':incidente_id' => $id]);
    $mitigacoes = $stmtMit->fetchAll();
}

$tituloPagina = $editando ? 'Editar Incidente #' . $id : 'Novo Incidente';
$menuAtivo = 'incidentes';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-semibold mb-1"><?= e($tituloPagina) ?></h1>
        <p class="text-secondary small mb-0">Preencha os dados do evento de segurança e associe-o ao ativo e categoria.</p>
    </div>
    <a href="<?= e(base_url('modules/incidentes/index.php')) ?>" class="btn btn-outline-secondary btn-sm">
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

<div class="card soc-card mb-4">
    <div class="card-body p-4">
        <form method="post" action="" class="row g-3">
            <div class="col-md-8">
                <label for="titulo" class="form-label small text-secondary">Título do incidente *</label>
                <input type="text" class="form-control" id="titulo" name="titulo" value="<?= e($dados['titulo']) ?>" maxlength="150" required>
            </div>

            <div class="col-md-4">
                <label for="data_ocorrencia" class="form-label small text-secondary">Data e hora da ocorrência *</label>
                <input type="datetime-local" class="form-control" id="data_ocorrencia" name="data_ocorrencia" value="<?= e($dados['data_ocorrencia']) ?>" required>
            </div>

            <div class="col-md-4">
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

            <div class="col-md-4">
                <label for="categoria_id" class="form-label small text-secondary">Categoria de ameaça *</label>
                <select class="form-select" id="categoria_id" name="categoria_id" required>
                    <option value="">Selecione uma categoria...</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= (int) $cat['id'] ?>" <?= $dados['categoria_id'] === (string) $cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label for="usuario_id" class="form-label small text-secondary">Analista responsável *</label>
                <select class="form-select" id="usuario_id" name="usuario_id" required>
                    <option value="">Selecione um analista...</option>
                    <?php foreach ($usuarios as $usr): ?>
                        <option value="<?= (int) $usr['id'] ?>" <?= $dados['usuario_id'] === (string) $usr['id'] ? 'selected' : '' ?>>
                            <?= e($usr['nome']) ?>
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

            <div class="col-md-6">
                <label for="status" class="form-label small text-secondary">Status *</label>
                <select class="form-select" id="status" name="status" required>
                    <?php foreach ($listaStatus as $st): ?>
                        <option value="<?= e($st) ?>" <?= $dados['status'] === $st ? 'selected' : '' ?>>
                            <?= e(rotulo_status($st)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12">
                <label for="descricao" class="form-label small text-secondary">Descrição técnica / evidências *</label>
                <textarea class="form-control" id="descricao" name="descricao" rows="4" required><?= e($dados['descricao']) ?></textarea>
            </div>

            <div class="col-12 d-flex justify-content-end gap-2 pt-2">
                <a href="<?= e(base_url('modules/incidentes/index.php')) ?>" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-light fw-semibold px-4">
                    <i class="bi bi-check-lg me-1"></i><?= $editando ? 'Salvar Alterações' : 'Registrar Incidente' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($editando): ?>
    <div class="card soc-card">
        <div class="card-header d-flex justify-content-between align-items-center py-3">
            <span><i class="bi bi-check2-square me-2 text-secondary"></i>Ações de Mitigação Vinculadas</span>
            <a href="<?= e(base_url('modules/mitigacoes/form.php?incidente_id=' . $id)) ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Adicionar Mitigação
            </a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($mitigacoes)): ?>
                <div class="p-4 text-center text-secondary small">Nenhuma ação de mitigação registrada para este incidente.</div>
            <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($mitigacoes as $mit): ?>
                        <li class="list-group-item bg-transparent border-secondary-subtle px-4 py-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small fw-semibold text-light"><?= e($mit['analista_nome']) ?></span>
                                <span class="small text-secondary"><?= e(formatar_data($mit['data_acao'])) ?></span>
                            </div>
                            <p class="small text-secondary mb-0"><?= e($mit['descricao_acao']) ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
