# ERP 2026 — Pacote inicial (PHP puro + MySQL)

Base funcional testada de ponta a ponta: Admin do sistema cria cliente → cliente
faz teste de 5 dias → aprovação → Admin Cliente cadastra usuários/caixas/produtos →
operador de Caixa vende, consulta preço e cartão fidelidade no PDV.

## Requisitos
- PHP 8.1+ com extensão `pdo_mysql`
- MySQL ou MariaDB
- Servidor web (Apache/Nginx) ou o servidor embutido do PHP para testes

## Instalação

1. Crie o banco e as tabelas:
   ```bash
   mysql -u root -p < database/schema.sql
   ```
   Isso já cria os planos padrão e um Admin do sistema de exemplo:
   - **E-mail:** admin@erp2026.com
   - **Senha:** admin123 (troque assim que possível — veja abaixo)

2. Configure a conexão em `config/database.php` (host, usuário, senha do banco).

3. Suba o servidor (para testar rapidamente, sem Apache):
   ```bash
   php -S localhost:8000
   ```
   Acesse http://localhost:8000/public/login.php

   Em produção, aponte o *document root* do Apache/Nginx para a raiz do projeto
   e garanta que `config/`, `includes/` e `database/` não sejam acessíveis
   diretamente pelo navegador (bloqueie por `.htaccess` ou configuração do vhost).

## Como trocar a senha do Admin do sistema
```php
php -r "echo password_hash('sua_nova_senha', PASSWORD_DEFAULT);"
```
Copie o hash gerado e atualize a coluna `senha_hash` na tabela `admins`.

## Estrutura de pastas

```
config/       -> conexão com o banco (PDO)
includes/     -> autenticação, funções auxiliares, header/footer
public/       -> login, logout, index (portas de entrada públicas)
admin/        -> painel do Admin do sistema (dono do SaaS)
cliente/      -> painel do Admin Cliente / Gerente (usuários, caixas, produtos, fidelidade)
caixa/        -> PDV usado pelo perfil "caixa"
database/     -> schema.sql
assets/       -> CSS
```

## Fluxo implementado (conforme especificação do produto)

1. **Admin do sistema** cria um **Cliente** (empresa) → sistema gera uma
   **chave de acesso** e inicia o **teste de 5 dias** automaticamente.
2. Ao fim do teste, o Admin do sistema aprova (**OK**) ou reprova (**Não**)
   em `/admin/clientes.php`.
3. **Admin Cliente** (criado junto com a empresa) faz login e cadastra:
   - Usuários (**Gerente** / **Caixa**) em `/cliente/usuarios.php`
   - Caixas/terminais em `/cliente/caixas.php` (mostra a faixa de cobrança:
     até 3 caixas ou acima de 3 — os valores X/Y ainda precisam ser definidos)
   - Produtos em `/cliente/produtos.php`
   - Cartões fidelidade em `/cliente/fidelidade.php`
4. **Caixa** acessa `/caixa/pdv.php` e pode:
   - Vender pelo terminal (monta carrinho e finaliza a venda)
   - Consultar preço de produto
   - Consultar cartão fidelidade

## O que ainda falta (próximos passos sugeridos)

- Definir os valores **X** e **Y** da cobrança por faixa de caixas, e os
  valores das opções de plano "bimestral"/"13 meses" (marcados como
  "a confirmar" no documento de especificação).
- Tela de relatórios/acompanhamento em tempo real para o Admin (mencionada
  nas anotações originais).
- Módulo "Ponto Digital" completo (a tabela `ponto_registros` já existe no
  banco, mas ainda não há tela para bater ponto).
- Impressão de etiqueta e o item "Localmark" citados nas anotações (função
  ainda não especificada — confirmar com você o que significam).
- Job/cron para expirar automaticamente o teste de 5 dias e bloquear
  clientes com assinatura vencida.
- Nesta versão o PDV usa o primeiro caixa ativo do cliente; se quiser que
  cada operador de caixa fique vinculado a um terminal específico, dá para
  adicionar isso no cadastro de usuário.
