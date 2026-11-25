<?php
/**
 * Cabeçalho HTML padrão com Bootstrap 5 (tema escuro) e barra de navegação.
 * Espera as variáveis opcionais: $tituloPagina (string) e $menuAtivo (string).
 */

// Valores padrão caso a página que incluiu o header não tenha definido título ou menu ativo
$tituloPagina = $tituloPagina ?? 'Mini-SOC';
$menuAtivo = $menuAtivo ?? '';
// Recupera dados do analista autenticado na sessão para exibir no canto superior direito
$usuario = usuario_logado();
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($tituloPagina) ?> — Mini-SOC</title>
    <!-- Bootstrap 5.3 e Bootstrap Icons via CDN + folha de estilo personalizada do tema escuro -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(base_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
<!-- Barra de navegação fixa no topo com links para os 6 módulos CRUD e Relatório Consolidado -->
<nav class="navbar navbar-expand-lg soc-navbar sticky-top">
    <div class="container-xl">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= e(base_url('index.php')) ?>">
            <i class="bi bi-shield-lock text-secondary"></i>
            <span>Mini-SOC</span>
        </a>

        <button class="navbar-toggler border-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSoc" aria-controls="navbarSoc" aria-expanded="false" aria-label="Alternar navegação">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSoc">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 gap-lg-1">
                <li class="nav-item">
                    <a class="nav-link <?= $menuAtivo === 'painel' ? 'active' : '' ?>" href="<?= e(base_url('index.php')) ?>">
                        <i class="bi bi-speedometer2 me-1"></i>Painel
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $menuAtivo === 'incidentes' ? 'active' : '' ?>" href="<?= e(base_url('modules/incidentes/index.php')) ?>">
                        <i class="bi bi-exclamation-triangle me-1"></i>Incidentes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $menuAtivo === 'ativos' ? 'active' : '' ?>" href="<?= e(base_url('modules/ativos/index.php')) ?>">
                        <i class="bi bi-hdd-network me-1"></i>Ativos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $menuAtivo === 'vulnerabilidades' ? 'active' : '' ?>" href="<?= e(base_url('modules/vulnerabilidades/index.php')) ?>">
                        <i class="bi bi-bug me-1"></i>Vulnerabilidades
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $menuAtivo === 'categorias' ? 'active' : '' ?>" href="<?= e(base_url('modules/categorias/index.php')) ?>">
                        <i class="bi bi-tags me-1"></i>Categorias
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $menuAtivo === 'mitigacoes' ? 'active' : '' ?>" href="<?= e(base_url('modules/mitigacoes/index.php')) ?>">
                        <i class="bi bi-check2-square me-1"></i>Mitigações
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $menuAtivo === 'relatorios' ? 'active' : '' ?>" href="<?= e(base_url('relatorios/consolidado.php')) ?>">
                        <i class="bi bi-bar-chart-line me-1"></i>Relatório
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $menuAtivo === 'usuarios' ? 'active' : '' ?>" href="<?= e(base_url('modules/usuarios/index.php')) ?>">
                        <i class="bi bi-people me-1"></i>Usuários
                    </a>
                </li>
            </ul>

            <?php if ($usuario !== null): ?>
                <div class="d-flex align-items-center gap-3">
                    <div class="text-end d-none d-xl-block lh-sm">
                        <div class="small fw-semibold text-light"><?= e($usuario['nome']) ?></div>
                        <div class="text-secondary" style="font-size: 0.74rem;"><?= e(ucfirst($usuario['perfil'])) ?></div>
                    </div>
                    <a href="<?= e(base_url('modules/auth/logout.php')) ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-box-arrow-right me-1"></i>Sair
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="container-xl py-4">
    <?php exibir_flash(); ?>
