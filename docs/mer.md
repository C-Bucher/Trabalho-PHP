# Modelo Entidade-Relacionamento (MER) — Mini-SOC

O diagrama abaixo representa as 6 tabelas do banco de dados `mini_soc` e seus relacionamentos de chave estrangeira (`FOREIGN KEY`) implementados em `sql/01_schema.sql`.

```mermaid
erDiagram
    usuarios {
        int id PK
        varchar nome
        varchar email UK
        varchar senha_hash
        enum perfil
        datetime criado_em
    }

    ativos {
        int id PK
        varchar nome
        varchar tipo
        varchar endereco_ip
        enum criticidade
    }

    categorias_ameaca {
        int id PK
        varchar nome UK
        text descricao
    }

    vulnerabilidades {
        int id PK
        varchar codigo_cve
        varchar titulo
        enum severidade
        int ativo_id FK
    }

    incidentes {
        int id PK
        varchar titulo
        text descricao
        enum severidade
        enum status
        datetime data_ocorrencia
        int ativo_id FK
        int categoria_id FK
        int usuario_id FK
    }

    acoes_mitigacao {
        int id PK
        int incidente_id FK
        int usuario_id FK
        text descricao_acao
        datetime data_acao
    }

    ativos ||--o{ vulnerabilidades : "possui"
    ativos ||--o{ incidentes : "sofre"
    categorias_ameaca ||--o{ incidentes : "classifica"
    usuarios ||--o{ incidentes : "registra_e_acompanha"
    incidentes ||--o{ acoes_mitigacao : "recebe"
    usuarios ||--o{ acoes_mitigacao : "executa"
```

## Resumo dos Relacionamentos

- **1:N (`ativos` -> `vulnerabilidades`)**: um ativo monitorado pode possuir várias vulnerabilidades (CVEs); cada vulnerabilidade pertence a um ativo (`ON DELETE RESTRICT`).
- **1:N (`ativos` -> `incidentes`)**: um ativo pode estar envolvido em vários incidentes; cada incidente afeta um ativo (`ON DELETE RESTRICT`).
- **1:N (`categorias_ameaca` -> `incidentes`)**: uma categoria de ameaça classifica vários incidentes (`ON DELETE RESTRICT`).
- **1:N (`usuarios` -> `incidentes`)**: um analista pode ser responsável por vários incidentes (`ON DELETE RESTRICT`).
- **1:N (`incidentes` -> `acoes_mitigacao`)**: um incidente pode receber múltiplas ações de mitigação; ao excluir um incidente, suas ações vinculadas são removidas em cascata (`ON DELETE CASCADE`).
- **1:N (`usuarios` -> `acoes_mitigacao`)**: um analista pode registrar diversas ações de mitigação (`ON DELETE RESTRICT`).
