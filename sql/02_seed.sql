-- ============================================================================
-- Mini-SOC — Sistema de Gestão de Incidentes de Cibersegurança e Vulnerabilidades
-- Script 02: Carga Inicial de Dados (Seed)
--
-- Credenciais padrão para testes (documentadas também no README.md):
--   Admin:    admin@minisoc.local     / Admin@123
--   Analista: analista@minisoc.local  / Analista@123
--   Analista: carla.soc@minisoc.local / Analista@123
-- ============================================================================

USE mini_soc;

-- Limpeza segura em ordem reversa de dependências (compatível com phpMyAdmin e FK ativa)
DELETE FROM acoes_mitigacao;
DELETE FROM incidentes;
DELETE FROM vulnerabilidades;
DELETE FROM categorias_ameaca;
DELETE FROM ativos;
DELETE FROM usuarios;

ALTER TABLE acoes_mitigacao AUTO_INCREMENT = 1;
ALTER TABLE incidentes AUTO_INCREMENT = 1;
ALTER TABLE vulnerabilidades AUTO_INCREMENT = 1;
ALTER TABLE categorias_ameaca AUTO_INCREMENT = 1;
ALTER TABLE ativos AUTO_INCREMENT = 1;
ALTER TABLE usuarios AUTO_INCREMENT = 1;

-- ----------------------------------------------------------------------------
-- 1. Usuários (senhas geradas com password_hash(..., PASSWORD_BCRYPT))
-- ----------------------------------------------------------------------------
INSERT INTO usuarios (id, nome, email, senha_hash, perfil, criado_em) VALUES
(1, 'Administrador SOC', 'admin@minisoc.local', '$2y$10$CMrIkWtYyC2BCOlT4KgoiugYqyhig2CnqknIwqgpO3q4VV7fei2zm', 'admin', '2026-01-10 08:00:00'),
(2, 'Lucas Mendes', 'analista@minisoc.local', '$2y$10$BqGESYWo/.Y3c6V9creI5.WtV3QBZfe1tPumkDKymK6TUMlJSIC46', 'analista', '2026-01-12 09:30:00'),
(3, 'Carla Nogueira', 'carla.soc@minisoc.local', '$2y$10$BqGESYWo/.Y3c6V9creI5.WtV3QBZfe1tPumkDKymK6TUMlJSIC46', 'analista', '2026-01-15 14:15:00');

-- ----------------------------------------------------------------------------
-- 2. Ativos Monitorados
-- ----------------------------------------------------------------------------
INSERT INTO ativos (id, nome, tipo, endereco_ip, criticidade) VALUES
(1, 'SRV-WEB-01 (Portal Acadêmico)', 'Servidor Web', '192.168.10.10', 'Critica'),
(2, 'SRV-DB-01 (Banco Principal)', 'Servidor de Banco de Dados', '192.168.10.20', 'Critica'),
(3, 'FW-EDGE-01 (Firewall Perímetro)', 'Firewall', '10.0.0.1', 'Critica'),
(4, 'SRV-MAIL-01 (Servidor SMTP)', 'Servidor de E-mail', '192.168.10.35', 'Alta'),
(5, 'SRV-AD-01 (Active Directory)', 'Controlador de Domínio', '192.168.10.5', 'Critica'),
(6, 'WKS-FIN-04 (Estação Financeiro)', 'Estação de Trabalho', '192.168.20.44', 'Media');

-- ----------------------------------------------------------------------------
-- 3. Categorias de Ameaça
-- ----------------------------------------------------------------------------
INSERT INTO categorias_ameaca (id, nome, descricao) VALUES
(1, 'Phishing', 'Tentativas de engenharia social via e-mail ou páginas falsas para captura de credenciais.'),
(2, 'Força Bruta', 'Múltiplas tentativas automatizadas de autenticação para descoberta de senhas.'),
(3, 'Injeção SQL', 'Exploração de entradas não sanitizadas para manipulação de consultas ao banco de dados.'),
(4, 'Malware / Ransomware', 'Execução de software malicioso com potencial de sequestro ou corrupção de arquivos.'),
(5, 'Negação de Serviço (DDoS)', 'Sobrecarga intencional de tráfego visando indisponibilizar serviços de rede.'),
(6, 'Escalação de Privilégio', 'Uso indevido de falhas ou permissões para obter acesso administrativo não autorizado.');

-- ----------------------------------------------------------------------------
-- 4. Vulnerabilidades Conhecidas (CVEs)
-- ----------------------------------------------------------------------------
INSERT INTO vulnerabilidades (id, codigo_cve, titulo, severidade, ativo_id) VALUES
(1, 'CVE-2024-3094', 'Falha de execução remota em biblioteca de compressão', 'Critica', 1),
(2, 'CVE-2024-21626', 'Escape de container em ambiente de aplicação web', 'Alta', 1),
(3, 'CVE-2023-44487', 'HTTP/2 Rapid Reset suscetível a negação de serviço', 'Alta', 3),
(4, 'CVE-2024-38077', 'Vulnerabilidade de estouro de buffer em serviço de licenciamento', 'Critica', 5),
(5, 'CVE-2023-38545', 'Heap buffer overflow em cliente de transferência de rede', 'Media', 4),
(6, 'CVE-2024-27198', 'Bypass de autenticação em interface administrativa', 'Critica', 2),
(7, 'CVE-2023-4863', 'Falha de processamento de imagem em navegador corporativo', 'Alta', 6),
(8, 'CVE-2024-1086', 'Escalação de privilégio local no subsistema de rede Linux', 'Alta', 2);

-- ----------------------------------------------------------------------------
-- 5. Incidentes de Segurança (12 registros com datas, severidades e status variados)
-- ----------------------------------------------------------------------------
INSERT INTO incidentes (id, titulo, descricao, severidade, status, data_ocorrencia, ativo_id, categoria_id, usuario_id) VALUES
(1, 'Tentativa de SQL Injection no módulo de login', 'WAF registrou 47 requisições contendo payloads UNION SELECT direcionadas ao formulário de autenticação do portal.', 'Critica', 'Contido', '2026-02-04 09:15:00', 1, 3, 2),
(2, 'Ataque de força bruta SSH na interface externa', 'Mais de 1.800 tentativas de login SSH falhas originadas de IPs externos em um intervalo de 15 minutos.', 'Alta', 'Resolvido', '2026-02-07 22:40:00', 3, 2, 1),
(3, 'Campanha de phishing direcionada ao setor financeiro', 'E-mails simulando cobrança de nota fiscal com anexo macro foram entregues à estação WKS-FIN-04.', 'Alta', 'Resolvido', '2026-02-11 11:05:00', 6, 1, 3),
(4, 'Pico anômalo de conexões SYN no perímetro', 'Monitoramento de rede apontou saturação de 85% do link principal devido a SYN flood.', 'Critica', 'Resolvido', '2026-02-15 16:20:00', 3, 5, 1),
(5, 'Execução suspeita de PowerShell ofuscado', 'EDR alertou sobre comando codificado em Base64 tentando baixar binário externo a partir de conta padrão.', 'Critica', 'Em Analise', '2026-02-20 14:50:00', 6, 4, 2),
(6, 'Consultas massivas fora do horário comercial', 'Detecção de extração incomum de registros na tabela de alunos durante a madrugada.', 'Critica', 'Aberto', '2026-02-24 03:12:00', 2, 6, 1),
(7, 'Relay aberto temporário identificado no SMTP', 'Logs indicaram envio de 320 mensagens externas não autorizadas após atualização de regra.', 'Media', 'Contido', '2026-02-28 10:30:00', 4, 1, 3),
(8, 'Tentativa de Kerberoasting no Active Directory', 'Foram observadas solicitações atípicas de tickets TGS para múltiplas contas de serviço.', 'Alta', 'Em Analise', '2026-03-03 17:45:00', 5, 6, 2),
(9, 'Varredura automatizada de diretórios sensíveis', 'Bot automatizado realizou varredura buscando arquivos .env e backups no servidor web.', 'Baixa', 'Resolvido', '2026-03-06 08:25:00', 1, 3, 3),
(10, 'Falhas repetidas de login em conta administrativa do banco', 'Sequência de 95 erros de autenticação na porta 3306 a partir de segmento interno.', 'Alta', 'Aberto', '2026-03-10 19:05:00', 2, 2, 2),
(11, 'Detecção de dropper em anexo compactado', 'Filtro antivírus quarentenou arquivo .zip com script malicioso antes da abertura pelo usuário.', 'Media', 'Resolvido', '2026-03-14 13:18:00', 4, 4, 3),
(12, 'Indisponibilidade intermitente no Portal Acadêmico', 'Alto volume de requisições HTTP/2 simultâneas causou lentidão e erro 503 intermitente.', 'Alta', 'Aberto', '2026-03-18 15:40:00', 1, 5, 1);

-- ----------------------------------------------------------------------------
-- 6. Ações de Mitigação
-- ----------------------------------------------------------------------------
INSERT INTO acoes_mitigacao (id, incidente_id, usuario_id, descricao_acao, data_acao) VALUES
(1, 1, 2, 'Bloqueio dos IPs ofensores no WAF e revisão das consultas com PDO Prepared Statements.', '2026-02-04 10:05:00'),
(2, 2, 1, 'Aplicada regra de rate-limit no firewall e desabilitado acesso SSH direto pela interface WAN.', '2026-02-07 23:10:00'),
(3, 3, 3, 'Domínio remetente inserido em blacklist do gateway de e-mail e alerta enviado aos colaboradores.', '2026-02-11 11:45:00'),
(4, 4, 1, 'Ativada proteção SYN Cookie no firewall de perímetro e mitigação junto à operadora.', '2026-02-15 17:00:00'),
(5, 5, 2, 'Estação WKS-FIN-04 isolada da rede corporativa para coleta forense de memória e logs.', '2026-02-20 15:20:00'),
(6, 7, 3, 'Restringido relay SMTP apenas para sub-redes autenticadas e fila de spam limpa.', '2026-02-28 11:15:00'),
(7, 8, 2, 'Iniciada rotação de senhas das contas de serviço e habilitada auditoria reforçada no AD.', '2026-03-03 18:30:00'),
(8, 9, 3, 'Confirmada ausência de arquivos expostos e bloqueio do User-Agent malicioso no servidor web.', '2026-03-06 09:00:00'),
(9, 11, 3, 'Hash do arquivo malicioso adicionado às regras de bloqueio corporativo e confirmação de quarentena.', '2026-03-14 13:50:00'),
(10, 1, 1, 'Validação final de logs após correção; nenhuma extração de dados identificada.', '2026-02-05 08:40:00');
