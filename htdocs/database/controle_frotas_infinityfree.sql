CREATE TABLE filiais (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    nome        VARCHAR(100)    NOT NULL,
    cnpj        VARCHAR(18)     NOT NULL,
    cidade      VARCHAR(80)     NOT NULL,
    estado      CHAR(2)         NOT NULL,
    endereco    VARCHAR(200)    NOT NULL,
    imagem      VARCHAR(255)    DEFAULT NULL,
    ativo       TINYINT(1)      NOT NULL DEFAULT 1,
    criado_em   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_filial_cnpj (cnpj)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE usuarios (
    id                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    nome                VARCHAR(100)    NOT NULL,
    cpf                 VARCHAR(14)     NOT NULL,
    rg                  VARCHAR(20)     DEFAULT NULL,
    email               VARCHAR(150)    NOT NULL,
    telefone            VARCHAR(20)     DEFAULT NULL,
    foto                VARCHAR(255)    DEFAULT NULL,
    senha_hash          VARCHAR(255)    NOT NULL,
    perfil              ENUM('admin','gerente','motorista') NOT NULL,
    filial_id           INT UNSIGNED    DEFAULT NULL,
    ativo               TINYINT(1)      NOT NULL DEFAULT 1,
    token_recuperacao   VARCHAR(100)    DEFAULT NULL,
    token_expiracao     DATETIME        DEFAULT NULL,
    criado_em           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuario_cpf   (cpf),
    UNIQUE KEY uq_usuario_email (email),
    CONSTRAINT fk_usuario_filial
        FOREIGN KEY (filial_id) REFERENCES filiais(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE motoristas (
    usuario_id      INT UNSIGNED                                                NOT NULL,
    cnh_numero      VARCHAR(20)                                                 NOT NULL,
    cnh_categoria   ENUM('A','B','C','D','E','AB','AC','AD','AE')               NOT NULL,
    cnh_validade    DATE                                                        NOT NULL,
    PRIMARY KEY (usuario_id),
    UNIQUE KEY uq_motorista_cnh (cnh_numero),
    CONSTRAINT fk_motorista_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE veiculos (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    placa                   VARCHAR(8)      NOT NULL,
    modelo                  VARCHAR(80)     NOT NULL,
    marca                   VARCHAR(60)     NOT NULL,
    chassi                  VARCHAR(17)     NOT NULL,
    renavam                 VARCHAR(11)     NOT NULL,
    ano                     YEAR            NOT NULL,
    categoria_cnh_exigida   ENUM('A','B','C','D','E') NOT NULL DEFAULT 'B',
    odometro_inicial        INT UNSIGNED    NOT NULL DEFAULT 0,
    odometro_atual          INT UNSIGNED    NOT NULL DEFAULT 0,
    status                  ENUM('disponivel','reservado','em_transito','manutencao') NOT NULL DEFAULT 'disponivel',
    filial_id               INT UNSIGNED    DEFAULT NULL,
    ativo                   TINYINT(1)      NOT NULL DEFAULT 1,
    criado_em               DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_veiculo_placa   (placa),
    UNIQUE KEY uq_veiculo_chassi  (chassi),
    UNIQUE KEY uq_veiculo_renavam (renavam),
    CONSTRAINT fk_veiculo_filial
        FOREIGN KEY (filial_id) REFERENCES filiais(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE planos_manutencao (
    id                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    veiculo_id          INT UNSIGNED    NOT NULL,
    tipo_servico        VARCHAR(100)    NOT NULL,
    km_limite           INT UNSIGNED    NOT NULL,
    data_agendamento    DATE            DEFAULT NULL,
    status              ENUM('pendente','concluido','cancelado') NOT NULL DEFAULT 'pendente',
    observacoes         TEXT            DEFAULT NULL,
    criado_em           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_manutencao_veiculo
        FOREIGN KEY (veiculo_id) REFERENCES veiculos(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE viagens (
    id                          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    veiculo_id                  INT UNSIGNED    NOT NULL,
    motorista_id                INT UNSIGNED    NOT NULL,
    gerente_id                  INT UNSIGNED    NOT NULL,
    filial_id                   INT UNSIGNED    DEFAULT NULL,
    descricao_rota              TEXT            NOT NULL,
    data_partida                DATETIME        NOT NULL,
    previsao_retorno            DATETIME        NOT NULL,
    retorno_real                DATETIME        DEFAULT NULL,
    status                      ENUM('agendada','em_andamento','concluida','cancelada') NOT NULL DEFAULT 'agendada',
    odometro_inicial            INT UNSIGNED    DEFAULT NULL,
    odometro_final              INT UNSIGNED    DEFAULT NULL,
    combustivel_inicial         DECIMAL(4,1)    DEFAULT NULL,
    combustivel_final           DECIMAL(4,1)    DEFAULT NULL,
    observacoes_encerramento    TEXT            DEFAULT NULL,
    criado_em                   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_viagem_veiculo
        FOREIGN KEY (veiculo_id)   REFERENCES veiculos(id)            ON UPDATE CASCADE,
    CONSTRAINT fk_viagem_motorista
        FOREIGN KEY (motorista_id) REFERENCES motoristas(usuario_id)  ON UPDATE CASCADE,
    CONSTRAINT fk_viagem_gerente
        FOREIGN KEY (gerente_id)   REFERENCES usuarios(id)            ON UPDATE CASCADE,
    CONSTRAINT fk_viagem_filial
        FOREIGN KEY (filial_id)    REFERENCES filiais(id)             ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE checklists_viagem (
    id                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    viagem_id           INT UNSIGNED    NOT NULL,
    pneus               ENUM('conforme','nao_conforme') NOT NULL,
    luzes_setas         ENUM('conforme','nao_conforme') NOT NULL,
    estepe_ferramentas  ENUM('conforme','nao_conforme') NOT NULL,
    fluidos             ENUM('conforme','nao_conforme') NOT NULL,
    observacoes         TEXT            DEFAULT NULL,
    foto_caminho        VARCHAR(255)    DEFAULT NULL,
    preenchido_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_checklist_viagem (viagem_id),
    CONSTRAINT fk_checklist_viagem
        FOREIGN KEY (viagem_id) REFERENCES viagens(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE despesas_viagem (
    id                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    viagem_id           INT UNSIGNED    NOT NULL,
    categoria           ENUM('combustivel','pedagio','manutencao_emergencia') NOT NULL,
    valor               DECIMAL(10,2)   NOT NULL,
    odometro_momento    INT UNSIGNED    DEFAULT NULL,
    comprovante_caminho VARCHAR(255)    DEFAULT NULL,
    lancado_em          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_despesa_viagem
        FOREIGN KEY (viagem_id) REFERENCES viagens(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE configuracoes_globais (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    chave           VARCHAR(80)     NOT NULL,
    valor           VARCHAR(255)    NOT NULL,
    descricao       VARCHAR(200)    DEFAULT NULL,
    atualizado_em   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    atualizado_por  INT UNSIGNED    DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_config_chave (chave),
    CONSTRAINT fk_config_usuario
        FOREIGN KEY (atualizado_por) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE logs_auditoria (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    usuario_id      INT UNSIGNED    DEFAULT NULL,
    acao            VARCHAR(80)     NOT NULL,
    entidade        VARCHAR(60)     NOT NULL,
    entidade_id     INT UNSIGNED    DEFAULT NULL,
    detalhes        TEXT            DEFAULT NULL,
    ip_origem       VARCHAR(45)     DEFAULT NULL,
    registrado_em   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_log_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO filiais (nome, cnpj, cidade, estado, endereco) VALUES
('Matriz São Paulo',        '12.345.678/0001-90', 'São Paulo',        'SP', 'Av. Paulista, 1000 - Bela Vista'),
('Filial Campinas',         '12.345.678/0002-71', 'Campinas',         'SP', 'Rua das Flores, 200 - Centro'),
('Filial Rio de Janeiro',   '12.345.678/0003-52', 'Rio de Janeiro',   'RJ', 'Av. Atlântica, 500 - Copacabana');

INSERT INTO usuarios (nome, cpf, rg, email, telefone, senha_hash, perfil, filial_id) VALUES
('Carlos Admin Silva',      '111.111.111-11', '11.111.111-1', 'admin@frotas.com',          '(11) 91111-1111', '$2y$12$RDiYnEIXY8RFpAqVeN9hOuiyjuO3k4IzbwPj9vpnpbwrlK2Yvu.vK', 'admin',     1),
('Ana Gerente Lima',        '222.222.222-22', '22.222.222-2', 'gerente.sp@frotas.com',     '(11) 92222-2222', '$2y$12$RDiYnEIXY8RFpAqVeN9hOuiyjuO3k4IzbwPj9vpnpbwrlK2Yvu.vK', 'gerente',   1),
('Bruno Gerente Costa',     '333.333.333-33', '33.333.333-3', 'gerente.cp@frotas.com',     '(19) 93333-3333', '$2y$12$RDiYnEIXY8RFpAqVeN9hOuiyjuO3k4IzbwPj9vpnpbwrlK2Yvu.vK', 'gerente',   2),
('Marcos Motorista Souza',  '444.444.444-44', '44.444.444-4', 'motorista1@frotas.com',     '(11) 94444-4444', '$2y$12$RDiYnEIXY8RFpAqVeN9hOuiyjuO3k4IzbwPj9vpnpbwrlK2Yvu.vK', 'motorista', 1),
('Júlia Motorista Rocha',   '555.555.555-55', '55.555.555-5', 'motorista2@frotas.com',     '(11) 95555-5555', '$2y$12$RDiYnEIXY8RFpAqVeN9hOuiyjuO3k4IzbwPj9vpnpbwrlK2Yvu.vK', 'motorista', 1),
('Pedro Motorista Alves',   '666.666.666-66', '66.666.666-6', 'motorista3@frotas.com',     '(19) 96666-6666', '$2y$12$RDiYnEIXY8RFpAqVeN9hOuiyjuO3k4IzbwPj9vpnpbwrlK2Yvu.vK', 'motorista', 2);

INSERT INTO motoristas (usuario_id, cnh_numero, cnh_categoria, cnh_validade) VALUES
(4, '12345678900', 'B', '2027-06-30'),
(5, '98765432100', 'B', '2026-11-15'),
(6, '55544433300', 'C', '2025-03-10');

INSERT INTO veiculos (placa, modelo, marca, chassi, renavam, ano, categoria_cnh_exigida, odometro_inicial, odometro_atual, status, filial_id) VALUES
('ABC1D23', 'HB20',     'Hyundai',    '9BWZZZ377VT004251', '12345678901', 2022, 'B',  15000,  42350, 'disponivel',  1),
('DEF2E45', 'Onix',     'Chevrolet',  '9BGRD19Z0HG123456', '98765432101', 2023, 'B',   5000,  28700, 'reservado',   1),
('GHI3F67', 'S10',      'Chevrolet',  '9BG3B9AE7GB123789', '11223344556', 2021, 'C',  30000,  87400, 'em_transito', 2),
('JKL4G89', 'Sprinter', 'Mercedes',   '9BM3849B0GE987654', '44556677889', 2020, 'D',  80000, 135600, 'manutencao',  1),
('MNO5H01', 'Kwid',     'Renault',    '9BWAB05GXKT112233', '77889900112', 2023, 'B',   1000,  12800, 'disponivel',  3);

INSERT INTO planos_manutencao (veiculo_id, tipo_servico, km_limite, status, observacoes) VALUES
(1, 'Troca de óleo e filtros',              45000,  'pendente',  'Óleo 5W30 sintético — ALERTA PRÓXIMO (42350/45000 km)'),
(1, 'Troca de pastilhas de freio',          60000,  'pendente',  NULL),
(2, 'Revisão dos 30.000 km',                30000,  'pendente',  'Revisão completa em concessionária autorizada'),
(3, 'Troca de pneus (aro 17)',              90000,  'pendente',  NULL),
(4, 'Manutenção corretiva — freio traseiro',135600, 'pendente',  'Veículo retido — freio traseiro com defeito'),
(5, 'Revisão dos 15.000 km',                15000,  'pendente',  NULL);

INSERT INTO configuracoes_globais (chave, valor, descricao, atualizado_por) VALUES
('alerta_km_manutencao',         '500', 'KM de antecedência para alertar manutenção preventiva',       1),
('tolerancia_odometro_km',       '5',   'Divergência máxima (KM) aceita no check-in sem bloquear',     1),
('tempo_checkout_alerta_h',      '1',   'Horas de atraso no check-out para disparar alerta gerencial',  1),
('tempo_envio_alerta_email_min', '1',   'Minutos máximos para envio de alerta crítico por e-mail',      1);

INSERT INTO viagens (veiculo_id, motorista_id, gerente_id, filial_id, descricao_rota, data_partida, previsao_retorno, retorno_real, status, odometro_inicial, odometro_final, combustivel_inicial, combustivel_final, observacoes_encerramento) VALUES
(3, 6, 3, 2, 'Entrega de materiais — Campinas → Santos',    '2026-06-15 08:00:00', '2026-06-15 18:00:00', '2026-06-15 17:45:00', 'concluida',    87000, 87400, 80.0, 28.0, 'Entrega realizada sem intercorrências.'),
(3, 4, 2, 1, 'Visita a cliente — São Paulo → Santo André',  '2026-06-18 09:00:00', '2026-06-18 17:00:00', NULL,                  'em_andamento', 87400,  NULL, 90.0, NULL, NULL),
(1, 5, 2, 1, 'Coleta de peças — São Paulo → Guarulhos',     '2026-06-20 07:30:00', '2026-06-20 15:00:00', NULL,                  'agendada',      NULL,  NULL, NULL, NULL, NULL);

INSERT INTO checklists_viagem (viagem_id, pneus, luzes_setas, estepe_ferramentas, fluidos, observacoes) VALUES
(1, 'conforme', 'conforme', 'conforme', 'conforme', 'Veículo em boas condições gerais de uso.');

INSERT INTO despesas_viagem (viagem_id, categoria, valor, odometro_momento) VALUES
(1, 'combustivel', 180.00, 87200),
(1, 'pedagio',      38.50, 87050),
(1, 'pedagio',      38.50, 87350);

INSERT INTO logs_auditoria (usuario_id, acao, entidade, entidade_id, detalhes, ip_origem) VALUES
(1, 'INSERT', 'filiais',            1, 'Cadastro da Matriz São Paulo',                        '192.168.1.10'),
(1, 'INSERT', 'veiculos',           1, 'Cadastro do veículo ABC1D23 — Hyundai HB20 2022',     '192.168.1.10'),
(1, 'INSERT', 'veiculos',           4, 'Cadastro do veículo JKL4G89 — Mercedes Sprinter 2020','192.168.1.10'),
(1, 'INSERT', 'usuarios',           4, 'Cadastro do motorista Marcos Motorista Souza',         '192.168.1.10'),
(1, 'INSERT', 'usuarios',           5, 'Cadastro da motorista Júlia Motorista Rocha',          '192.168.1.10'),
(2, 'INSERT', 'viagens',            1, 'Agendamento: Campinas → Santos (veículo GHI3F67)',     '192.168.1.20'),
(2, 'UPDATE', 'viagens',            1, 'Status alterado: agendada → em_andamento',             '192.168.1.20'),
(4, 'INSERT', 'checklists_viagem',  1, 'Checklist preenchido (viagem 1) — 4/4 itens conformes','192.168.1.30'),
(4, 'INSERT', 'despesas_viagem',    1, 'Despesa lançada: combustível R$ 180,00 (viagem 1)',    '192.168.1.30'),
(2, 'UPDATE', 'viagens',            1, 'Status alterado: em_andamento → concluida',            '192.168.1.20'),
(2, 'UPDATE', 'veiculos',           4, 'Status alterado para manutencao (JKL4G89)',             '192.168.1.20'),
(2, 'INSERT', 'viagens',            2, 'Agendamento: São Paulo → Santo André (veículo GHI3F67)','192.168.1.20'),
(2, 'INSERT', 'viagens',            3, 'Agendamento: São Paulo → Guarulhos (veículo ABC1D23)', '192.168.1.20');
