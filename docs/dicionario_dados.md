# Dicionário de Dados — Mini-SOC

Banco de dados: `mini_soc` | SGBD: MySQL / InnoDB | Charset: `utf8mb4_unicode_ci`

---

## 1. Tabela `usuarios`
Armazena os analistas e administradores autorizados a acessar o sistema.

| Campo | Tipo | PK / FK | Nulo | Descrição |
| :--- | :--- | :--- | :--- | :--- |
| `id` | `INT AUTO_INCREMENT` | PK | Não | Identificador único do usuário. |
| `nome` | `VARCHAR(100)` | — | Não | Nome completo do usuário. |
| `email` | `VARCHAR(120)` | UNIQUE | Não | E-mail utilizado para autenticação (login). |
| `senha_hash` | `VARCHAR(255)` | — | Não | Hash bcrypt da senha gerado via `password_hash()`. |
| `perfil` | `ENUM('admin','analista')` | — | Não | Perfil de acesso do usuário (padrão: `analista`). |
| `criado_em` | `DATETIME` | — | Não | Data e hora de criação do cadastro (`CURRENT_TIMESTAMP`). |

---

## 2. Tabela `ativos`
Inventário de servidores, firewalls e estações de trabalho monitorados pelo SOC.

| Campo | Tipo | PK / FK | Nulo | Descrição |
| :--- | :--- | :--- | :--- | :--- |
| `id` | `INT AUTO_INCREMENT` | PK | Não | Identificador único do ativo. |
| `nome` | `VARCHAR(100)` | — | Não | Nome/hostname descritivo do ativo. |
| `tipo` | `VARCHAR(60)` | — | Não | Categoria física/lógica (ex.: Servidor Web, Firewall). |
| `endereco_ip` | `VARCHAR(45)` | INDEX | Não | Endereço IPv4 ou IPv6 do ativo monitorado. |
| `criticidade` | `ENUM('Baixa','Media','Alta','Critica')` | — | Não | Nível de importância do ativo para a operação. |

---

## 3. Tabela `categorias_ameaca`
Taxonomia utilizada para classificar o tipo de ataque ou ameaça de cada incidente.

| Campo | Tipo | PK / FK | Nulo | Descrição |
| :--- | :--- | :--- | :--- | :--- |
| `id` | `INT AUTO_INCREMENT` | PK | Não | Identificador único da categoria. |
| `nome` | `VARCHAR(80)` | UNIQUE | Não | Nome da categoria de ameaça (ex.: Phishing, Injeção SQL). |
| `descricao` | `TEXT` | — | Sim | Descrição detalhada da natureza da ameaça. |

---

## 4. Tabela `vulnerabilidades`
Registro de falhas de segurança conhecidas (CVEs) associadas aos ativos monitorados.

| Campo | Tipo | PK / FK | Nulo | Descrição |
| :--- | :--- | :--- | :--- | :--- |
| `id` | `INT AUTO_INCREMENT` | PK | Não | Identificador único da vulnerabilidade. |
| `codigo_cve` | `VARCHAR(30)` | — | Não | Código padronizado da falha (ex.: `CVE-2024-3094`). |
| `titulo` | `VARCHAR(150)` | — | Não | Resumo técnico da vulnerabilidade. |
| `severidade` | `ENUM('Baixa','Media','Alta','Critica')` | — | Não | Grau de risco atribuído à falha. |
| `ativo_id` | `INT` | FK (`ativos.id`) | Não | Referência ao ativo afetado pela vulnerabilidade. |

---

## 5. Tabela `incidentes`
Entidade principal do sistema, responsável pelo registro e triagem dos incidentes de segurança.

| Campo | Tipo | PK / FK | Nulo | Descrição |
| :--- | :--- | :--- | :--- | :--- |
| `id` | `INT AUTO_INCREMENT` | PK | Não | Identificador único do incidente. |
| `titulo` | `VARCHAR(150)` | — | Não | Título resumido do incidente detectado. |
| `descricao` | `TEXT` | — | Não | Relato técnico e evidências do ocorrido. |
| `severidade` | `ENUM('Baixa','Media','Alta','Critica')` | INDEX | Não | Nível de severidade do incidente. |
| `status` | `ENUM('Aberto','Em Analise','Contido','Resolvido')` | INDEX | Não | Estágio atual do tratamento do incidente. |
| `data_ocorrencia` | `DATETIME` | INDEX | Não | Data e hora em que o incidente foi observado. |
| `ativo_id` | `INT` | FK (`ativos.id`) | Não | Ativo de infraestrutura impactado. |
| `categoria_id` | `INT` | FK (`categorias_ameaca.id`) | Não | Categoria de ameaça associada ao incidente. |
| `usuario_id` | `INT` | FK (`usuarios.id`) | Não | Analista responsável pelo registro/tratamento. |

---

## 6. Tabela `acoes_mitigacao`
Histórico das ações de contenção, erradicação ou recuperação executadas em um incidente.

| Campo | Tipo | PK / FK | Nulo | Descrição |
| :--- | :--- | :--- | :--- | :--- |
| `id` | `INT AUTO_INCREMENT` | PK | Não | Identificador único da ação de mitigação. |
| `incidente_id` | `INT` | FK (`incidentes.id`) | Não | Incidente ao qual a ação pertence (`ON DELETE CASCADE`). |
| `usuario_id` | `INT` | FK (`usuarios.id`) | Não | Analista que executou a ação. |
| `descricao_acao` | `TEXT` | — | Não | Detalhamento técnico da medida aplicada. |
| `data_acao` | `DATETIME` | — | Não | Data e hora da execução da ação (`CURRENT_TIMESTAMP`). |
