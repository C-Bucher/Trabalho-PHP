# Mini-SOC — Sistema de Gestão de Incidentes de Cibersegurança e Vulnerabilidades

Sistema web acadêmico desenvolvido em **PHP 8+ estruturado/modular com PDO**, **MySQL (InnoDB)** e **Bootstrap 5**, simulando a rotina de um pequeno Centro de Operações de Segurança (*Security Operations Center*).

---

## 1. Descrição e Justificativa do Domínio

Equipes de TI e segurança da informação lidam diariamente com alertas, falhas conhecidas (CVEs) e ataques contra servidores, firewalls e estações de trabalho. Sem um registro centralizado, torna-se difícil acompanhar quais incidentes ainda estão abertos, quais ativos são mais visados e quais ações de contenção já foram aplicadas.

O **Mini-SOC** resolve esse problema organizando seis entidades relacionadas em um único painel web:

1. **Usuários (`usuarios`)**: analistas e administradores autenticados no sistema.
2. **Ativos (`ativos`)**: servidores, firewalls e estações monitoradas com endereço IP e criticidade.
3. **Categorias de Ameaça (`categorias_ameaca`)**: taxonomia dos ataques (Phishing, Força Bruta, Injeção SQL, Ransomware, DDoS, Escalação de Privilégio).
4. **Vulnerabilidades (`vulnerabilidades`)**: códigos CVE associados aos ativos monitorados.
5. **Incidentes (`incidentes`)**: entidade central que registra cada evento de segurança, severidade, status, data da ocorrência, ativo afetado, categoria e analista responsável.
6. **Ações de Mitigação (`acoes_mitigacao`)**: histórico das medidas de contenção e resposta executadas em cada incidente.

---

## 2. Requisitos de Ambiente

- **PHP 8.0 ou superior** com extensões `pdo_mysql` e `mbstring` habilitadas (inclusas por padrão no XAMPP e Laragon).
- **MySQL 5.7+ ou MariaDB 10.4+** (InnoDB).
- **Servidor web Apache** (XAMPP / Laragon) ou servidor embutido do PHP (`php -S`).
- Acesso à internet no navegador apenas para carregar o Bootstrap 5 e Bootstrap Icons via CDN.

---

## 3. Passo a Passo de Instalação

### Passo 1: Copiar os arquivos do projeto
Coloque a pasta do projeto dentro do diretório público do seu servidor local:
- **XAMPP**: `C:\xampp\htdocs\mini-soc\`
- **Laragon**: `C:\laragon\www\mini-soc\`

*(O projeto calcula a URL base automaticamente, funcionando em qualquer nome de pasta.)*

### Passo 2: Criar e popular o banco de dados MySQL
1. Inicie o **Apache** e o **MySQL** no painel do XAMPP ou Laragon.
2. Abra o **phpMyAdmin** (`http://localhost/phpmyadmin`) ou o cliente MySQL.
3. Importe/execute os scripts na ordem abaixo:
   - `sql/01_schema.sql` — cria o banco `mini_soc`, as 6 tabelas com chaves estrangeiras (`FOREIGN KEY`) e índices.
   - `sql/02_seed.sql` — insere 3 usuários (com senhas em hash `bcrypt`), 6 ativos, 6 categorias, 8 vulnerabilidades, 12 incidentes e 10 ações de mitigação.

### Passo 3: Configurar a conexão com o banco
Abra o arquivo `config/database.php` e verifique se as constantes correspondem ao seu ambiente:

```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'mini_soc');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### Passo 4: Exportação em PDF (pronta para uso + suporte opcional ao FPDF via Composer)
- **Pronto para uso sem dependências:** o projeto inclui `lib/MiniFPDF.php`, um gerador PDF 1.4 leve e compatível com FPDF que funciona imediatamente no XAMPP/Laragon sem precisar instalar nada extra.
- **Opcional (FPDF oficial via Composer):** caso deseje usar a biblioteca oficial `setasign/fpdf`, basta executar na raiz do projeto:
  ```bash
  composer install
  ```
  O arquivo `relatorios/exportar_pdf.php` detecta automaticamente `vendor/autoload.php` quando presente.

### Passo 5: Acessar o sistema
Abra no navegador:
- `http://localhost/mini-soc/` (ou a pasta em que você copiou os arquivos)

---

## 4. Credenciais de Acesso (Carga Inicial)

| Perfil | E-mail | Senha |
| :--- | :--- | :--- |
| Administrador | `admin@minisoc.local` | `Admin@123` |
| Analista | `analista@minisoc.local` | `Analista@123` |
| Analista | `carla.soc@minisoc.local` | `Analista@123` |

*(No banco de dados, todas as senhas estão armazenadas com hash seguro `password_hash(..., PASSWORD_BCRYPT)`.)*

---

## 5. Manual Rápido de Uso

1. **Login e Sessão (`modules/auth/login.php`)**: informe e-mail e senha. Todas as páginas internas exigem autenticação via `config/auth.php`. Para encerrar, clique em **Sair** na barra superior.
2. **Painel Geral (`index.php`)**: exibe contadores de incidentes totais, abertos/em análise, críticos, ativos e CVEs, além da lista de incidentes recentes.
3. **Incidentes e Busca Avançada (`modules/incidentes/index.php`)**:
   - Cadastre, edite ou exclua incidentes associando-os a ativos, categorias e analistas.
   - Utilize a busca avançada combinando **texto** (título ou IP do ativo), **severidade**, **status** e **intervalo de datas**.
   - Clique em **Exportar PDF** para gerar a listagem em PDF respeitando exatamente os filtros aplicados na tela.
4. **Cadastros Auxiliares**: gerencie **Ativos**, **Vulnerabilidades (CVEs)**, **Categorias de Ameaça**, **Ações de Mitigação** e **Usuários** pelo menu superior. Exclusões protegidas por chave estrangeira exibem aviso amigável sem quebrar a aplicação.
5. **Relatório Consolidado (`relatorios/consolidado.php`)**: apresenta totalizadores gerados por `COUNT` e `GROUP BY` para severidade, status, ativos mais afetados e categorias de ameaça.

---

## 6. Estrutura de Pastas

```text
├── assets/css/style.css           # Ajustes do tema escuro minimalista (Bootstrap 5)
├── config/
│   ├── auth.php                   # Controle de sessão, middleware de login e helpers
│   └── database.php               # Conexão PDO com MySQL
├── docs/
│   ├── dicionario_dados.md        # Dicionário das 6 tabelas do banco
│   ├── historias_usuario.md       # 10 histórias de usuário
│   └── mer.md                     # Diagrama Entidade-Relacionamento (Mermaid)
├── includes/
│   ├── footer.php                 # Rodapé e JS do Bootstrap
│   └── header.php                 # Cabeçalho, navbar responsiva e alertas flash
├── lib/
│   └── MiniFPDF.php               # Gerador PDF compatível com FPDF (zero dependências)
├── modules/
│   ├── ativos/                    # CRUD de ativos monitorados
│   ├── auth/                      # Login e logout
│   ├── categorias/                # CRUD de categorias de ameaça
│   ├── incidentes/                # CRUD principal + busca avançada
│   ├── mitigacoes/                # CRUD de ações de mitigação
│   ├── usuarios/                  # CRUD de usuários com bcrypt
│   └── vulnerabilidades/          # CRUD de CVEs vinculadas a ativos
├── relatorios/
│   ├── consolidado.php            # Relatório gerencial com COUNT / GROUP BY
│   └── exportar_pdf.php           # Exportação da listagem filtrada em PDF
├── sql/
│   ├── 01_schema.sql              # DDL das 6 tabelas e chaves estrangeiras
│   └── 02_seed.sql                # Carga inicial realista para testes
├── composer.json                  # Opcional: setasign/fpdf via Composer
├── index.php                      # Painel inicial (Dashboard)
└── README.md                      # Documentação principal
```

---

## 7. Documentação Complementar

- [Documentação Completa Unificada (Trabalho A3)](docs/DOCUMENTACAO_COMPLETA.md)
- [Histórias de Usuário](docs/historias_usuario.md)
- [Modelo Entidade-Relacionamento (MER)](docs/mer.md)
- [Dicionário de Dados](docs/dicionario_dados.md)
