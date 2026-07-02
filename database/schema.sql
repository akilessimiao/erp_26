-- =========================================================
-- ERP 2026 - Schema do banco de dados
-- MySQL / MariaDB - charset utf8mb4
-- =========================================================

CREATE DATABASE IF NOT EXISTS erp2026 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE erp2026;

-- ---------------------------------------------------------
-- ADMIN DO SISTEMA (dono do SaaS - "ADMIN" das anotações)
-- ---------------------------------------------------------
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- PLANOS (mensal, anual, etc. - ver documento de especificação)
-- ---------------------------------------------------------
CREATE TABLE planos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    periodicidade ENUM('mensal','bimestral','anual') NOT NULL DEFAULT 'mensal',
    ativo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

INSERT INTO planos (nome, valor, periodicidade) VALUES
    ('Plano Mensal', 79.00, 'mensal'),
    ('Plano Anual', 890.00, 'anual');

-- ---------------------------------------------------------
-- CLIENTES (empresas que assinam o SaaS - "Admin Cliente")
-- ---------------------------------------------------------
CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_empresa VARCHAR(180) NOT NULL,
    cnpj VARCHAR(18) NOT NULL UNIQUE,
    inscricao_estadual VARCHAR(30) DEFAULT NULL,
    inscricao_municipal VARCHAR(30) DEFAULT NULL,
    endereco VARCHAR(255) DEFAULT NULL,
    tipo_documento ENUM('A5','A3') DEFAULT NULL, -- confirmar finalidade (ver especificação, item a revisar)
    plano_id INT DEFAULT NULL,
    chave_acesso VARCHAR(64) NOT NULL UNIQUE, -- gerada pelo ADMIN do sistema no onboarding
    status ENUM('teste','ok','nao_aprovado','ativo','bloqueado') NOT NULL DEFAULT 'teste',
    teste_inicio DATE DEFAULT NULL,
    teste_fim DATE DEFAULT NULL, -- teste_inicio + 5 dias
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (plano_id) REFERENCES planos(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- ASSINATURAS (histórico de cobranças do cliente)
-- ---------------------------------------------------------
CREATE TABLE assinaturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    plano_id INT NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE NOT NULL,
    status ENUM('pendente','paga','vencida','cancelada') NOT NULL DEFAULT 'pendente',
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (plano_id) REFERENCES planos(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- USUÁRIOS DO CLIENTE (admin_cliente, gerente, caixa)
-- ---------------------------------------------------------
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    perfil ENUM('admin_cliente','gerente','caixa') NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_email_cliente (cliente_id, email),
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- CAIXAS / TERMINAIS (por loja do cliente)
-- Regra de cobrança: até 3 = valor X, acima de 3 = valor Y (definir valores)
-- ---------------------------------------------------------
CREATE TABLE caixas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    identificador VARCHAR(50) NOT NULL, -- ex: "Caixa 01", "Terminal Loja Centro"
    loja VARCHAR(150) DEFAULT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- PRODUTOS (para consulta de preço e venda no PDV)
-- ---------------------------------------------------------
CREATE TABLE produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    nome VARCHAR(180) NOT NULL,
    codigo_barras VARCHAR(50) DEFAULT NULL,
    preco DECIMAL(10,2) NOT NULL,
    estoque INT NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    KEY idx_codigo_barras (codigo_barras)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- CARTÃO FIDELIDADE (consulta no caixa)
-- ---------------------------------------------------------
CREATE TABLE cartoes_fidelidade (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    numero_cartao VARCHAR(30) NOT NULL,
    nome_portador VARCHAR(150) DEFAULT NULL,
    pontos INT NOT NULL DEFAULT 0,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    UNIQUE KEY uk_cartao_cliente (cliente_id, numero_cartao)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- VENDAS (registradas pelo terminal/caixa)
-- ---------------------------------------------------------
CREATE TABLE vendas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    caixa_id INT NOT NULL,
    usuario_id INT NOT NULL, -- operador de caixa
    cartao_fidelidade_id INT DEFAULT NULL,
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (caixa_id) REFERENCES caixas(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (cartao_fidelidade_id) REFERENCES cartoes_fidelidade(id)
) ENGINE=InnoDB;

CREATE TABLE venda_itens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venda_id INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade INT NOT NULL DEFAULT 1,
    preco_unitario DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (venda_id) REFERENCES vendas(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- PONTO DIGITAL (registro de ponto dos funcionários)
-- ---------------------------------------------------------
CREATE TABLE ponto_registros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo ENUM('entrada','saida','pausa_inicio','pausa_fim') NOT NULL,
    registrado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Admin inicial do sistema (login: admin@erp2026.com / senha: admin123)
-- Trocar a senha assim que possível!
-- ---------------------------------------------------------
INSERT INTO admins (nome, email, senha_hash) VALUES
    ('Administrador do Sistema', 'admin@erp2026.com', '$2y$10$8FMOYjye2H8WAgvWiJsMQ.gq.XZ2rw34yVKKj81JJf.beg5Wwl5SC');
-- ^ senha em texto puro: admin123 — TROQUE assim que possível (é só um usuário de exemplo para o primeiro acesso).
