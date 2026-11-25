-- ============================================================================
-- Mini-SOC — Sistema de Gestão de Incidentes de Cibersegurança e Vulnerabilidades
-- Script 01: Criação do Banco de Dados e das Tabelas (MySQL / InnoDB)
-- ============================================================================

CREATE DATABASE IF NOT EXISTS mini_soc
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE mini_soc;

-- ----------------------------------------------------------------------------
-- 1. Tabela de Usuários (Analistas e Administradores do SOC)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    perfil ENUM('admin', 'analista') NOT NULL DEFAULT 'analista',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. Tabela de Ativos Monitorados (Servidores, Firewalls, Estações, etc.)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ativos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    tipo VARCHAR(60) NOT NULL,
    endereco_ip VARCHAR(45) NOT NULL,
    criticidade ENUM('Baixa', 'Media', 'Alta', 'Critica') NOT NULL DEFAULT 'Media'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. Tabela de Categorias de Ameaça (Phishing, Ransomware, SQLi, DDoS, etc.)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categorias_ameaca (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(80) NOT NULL UNIQUE,
    descricao TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. Tabela de Vulnerabilidades (CVEs vinculadas a um Ativo)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS vulnerabilidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_cve VARCHAR(30) NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    severidade ENUM('Baixa', 'Media', 'Alta', 'Critica') NOT NULL DEFAULT 'Media',
    ativo_id INT NOT NULL,
    CONSTRAINT fk_vulnerabilidades_ativo
        FOREIGN KEY (ativo_id) REFERENCES ativos(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5. Tabela de Incidentes de Segurança (Entidade Principal)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS incidentes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    descricao TEXT NOT NULL,
    severidade ENUM('Baixa', 'Media', 'Alta', 'Critica') NOT NULL DEFAULT 'Media',
    status ENUM('Aberto', 'Em Analise', 'Contido', 'Resolvido') NOT NULL DEFAULT 'Aberto',
    data_ocorrencia DATETIME NOT NULL,
    ativo_id INT NOT NULL,
    categoria_id INT NOT NULL,
    usuario_id INT NOT NULL,
    CONSTRAINT fk_incidentes_ativo
        FOREIGN KEY (ativo_id) REFERENCES ativos(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_incidentes_categoria
        FOREIGN KEY (categoria_id) REFERENCES categorias_ameaca(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_incidentes_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 6. Tabela de Ações de Mitigação (Histórico de Resposta por Incidente)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS acoes_mitigacao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incidente_id INT NOT NULL,
    usuario_id INT NOT NULL,
    descricao_acao TEXT NOT NULL,
    data_acao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mitigacoes_incidente
        FOREIGN KEY (incidente_id) REFERENCES incidentes(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_mitigacoes_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices auxiliares para acelerar filtros de busca e relatórios consolidados
CREATE INDEX idx_incidentes_severidade ON incidentes(severidade);
CREATE INDEX idx_incidentes_status ON incidentes(status);
CREATE INDEX idx_incidentes_data ON incidentes(data_ocorrencia);
CREATE INDEX idx_ativos_ip ON ativos(endereco_ip);
