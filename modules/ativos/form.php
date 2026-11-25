<?php
/**
 * Formulário de Cadastro e Edição de Ativos Monitorados.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

exigir_login();

$pdo = conectar_banco();
// Identifica modo de edição (?id=N) ou criação (sem id) e lista as criticidades válidas do ENUM
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$editando = ($id > 0);
$erros = [];
$criticidades = ['Baixa', 'Media', 'Alta', 'Critica'];

$dados = [
    'nome'        => '',
    'tipo'        => '',
    'endereco_ip' => '',
    'criticidade' => 'Media',
];

// Em edição, carrega os dados atuais do ativo no banco
if ($editando) {
    $stmt = $pdo->prepare('SELECT * FROM ativos WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $registro = $stmt->fetch();

    if (!$registro) {
        definir_flash('danger', 'Ativo não encontrado.');
        redirecionar('modules/ativos/index.php');
    }

    $dados = $registro;
}

// Processamento do envio do formulário via POST (validação + UPDATE/INSERT com Prepared Statements)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados['nome']        = trim($_POST['nome'] ?? '');
    $dados['tipo']        = trim($_POST['tipo'] ?? '');
    $dados['endereco_ip'] = trim($_POST['endereco_ip'] ?? '');
    $dados['criticidade'] = trim($_POST['criticidade'] ?? 'Media');

    if ($dados['nome'] === '') {
        $erros[] = 'O nome do ativo é obrigatório.';
    }
    if ($dados['tipo'] === '') {
        $erros[] = 'O tipo do ativo é obrigatório.';
    }
    // filter_var com FILTER_VALIDATE_IP valida automaticamente endereços IPv4 e IPv6
    if ($dados['endereco_ip'] === '' || filter_var($dados['endereco_ip'], FILTER_VALIDATE_IP) === false) {
        $erros[] = 'Informe um endereço IP válido (IPv4 ou IPv6).';
    }
    if (!in_array($dados['criticidade'], $criticidades, true)) {
        $erros[] = 'Criticidade inválida.';
    }

    if (empty($erros)) {
        if ($editando) {
            $stmt = $pdo->prepare('UPDATE ativos SET nome = :nome, tipo = :tipo, endereco_ip = :ip, criticidade = :criticidade WHERE id = :id');
            $stmt->execute([
                ':nome'        => $dados['nome'],
                ':tipo'        => $dados['tipo'],
                ':ip'          => $dados['endereco_ip'],
                ':criticidade' => $dados['criticidade'],
                ':id'          => $id,
            ]);
            definir_flash('success', 'Ativo atualizado com sucesso.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO ativos (nome, tipo, endereco_ip, criticidade) VALUES (:nome, :tipo, :ip, :criticidade)');
            $stmt->execute([
                ':nome'        => $dados['nome'],
                ':tipo'        => $dados['tipo'],
                ':ip'          => $dados['endereco_ip'],
                ':criticidade' => $dados['criticidade'],
            ]);
            definir_flash('success', 'Ativo cadastrado com sucesso.');
        }

        redirecionar('modules/ativos/index.php');
    }
}

$tituloPagina = $editando ? 'Editar Ativo' : 'Novo Ativo';
$menuAtivo = 'ativos';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 fw-semibold mb-0"><?= e($tituloPagina) ?></h1>
    <a href="<?= e(base_url('modules/ativos/index.php')) ?>" class="btn btn-outline-secondary btn-sm">
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
                <label for="nome" class="form-label small text-secondary">Nome do ativo *</label>
                <input type="text" class="form-control" id="nome" name="nome" value="<?= e($dados['nome']) ?>" placeholder="Ex.: SRV-APP-02" required>
            </div>
            <div class="col-md-6">
                <label for="tipo" class="form-label small text-secondary">Tipo *</label>
                <input type="text" class="form-control" id="tipo" name="tipo" value="<?= e($dados['tipo']) ?>" placeholder="Ex.: Servidor Web, Firewall, Estação" required>
            </div>
            <div class="col-md-6">
                <label for="endereco_ip" class="form-label small text-secondary">Endereço IP *</label>
                <input type="text" class="form-control" id="endereco_ip" name="endereco_ip" value="<?= e($dados['endereco_ip']) ?>" placeholder="Ex.: 192.168.10.50" required>
            </div>
            <div class="col-md-6">
                <label for="criticidade" class="form-label small text-secondary">Criticidade *</label>
                <select class="form-select" id="criticidade" name="criticidade" required>
                    <?php foreach ($criticidades as $crit): ?>
                        <option value="<?= e($crit) ?>" <?= $dados['criticidade'] === $crit ? 'selected' : '' ?>>
                            <?= e(rotulo_severidade($crit)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 d-flex justify-content-end gap-2 pt-2">
                <a href="<?= e(base_url('modules/ativos/index.php')) ?>" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-light fw-semibold px-4">Salvar</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
