<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdminSistema();

$pdo = getConnection();
$planos = $pdo->query('SELECT id, nome FROM planos WHERE ativo = 1')->fetchAll();
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomeEmpresa = trim($_POST['nome_empresa'] ?? '');
    $cnpj        = trim($_POST['cnpj'] ?? '');
    $ie          = trim($_POST['ie'] ?? '');
    $im          = trim($_POST['im'] ?? '');
    $endereco    = trim($_POST['endereco'] ?? '');
    $tipoDoc     = $_POST['tipo_documento'] ?? null;
    $planoId     = (int)($_POST['plano_id'] ?? 0) ?: null;
    $adminNome   = trim($_POST['admin_nome'] ?? '');
    $adminEmail  = trim($_POST['admin_email'] ?? '');
    $adminSenha  = $_POST['admin_senha'] ?? '';

    if ($nomeEmpresa === '') $erros[] = 'Informe o nome da empresa.';
    if ($cnpj === '') $erros[] = 'Informe o CNPJ.';
    if ($adminNome === '' || $adminEmail === '' || strlen($adminSenha) < 6) {
        $erros[] = 'Preencha os dados do Admin do Cliente (senha com no mínimo 6 caracteres).';
    }

    if (!$erros) {
        try {
            $pdo->beginTransaction();

            $chave = gerarChaveAcesso();
            $stmt = $pdo->prepare(
                'INSERT INTO clientes (nome_empresa, cnpj, inscricao_estadual, inscricao_municipal, endereco,
                                        tipo_documento, plano_id, chave_acesso, status, teste_inicio, teste_fim)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, "teste", CURDATE(), DATE_ADD(CURDATE(), INTERVAL 5 DAY))'
            );
            $stmt->execute([$nomeEmpresa, $cnpj, $ie ?: null, $im ?: null, $endereco ?: null, $tipoDoc ?: null, $planoId, $chave]);
            $clienteId = (int)$pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'INSERT INTO usuarios (cliente_id, nome, email, senha_hash, perfil) VALUES (?, ?, ?, ?, "admin_cliente")'
            );
            $stmt->execute([$clienteId, $adminNome, $adminEmail, password_hash($adminSenha, PASSWORD_DEFAULT)]);

            $pdo->commit();
            flash('sucesso', "Cliente criado com sucesso! Chave de acesso: $chave — teste de 5 dias iniciado.");
            redirect('/admin/clientes.php');
        } catch (PDOException $e) {
            $pdo->rollBack();
            $erros[] = ($e->getCode() === '23000')
                ? 'CNPJ ou e-mail já cadastrado.'
                : 'Erro ao salvar: ' . $e->getMessage();
        }
    }
}

$titulo = 'Novo Cliente';
require __DIR__ . '/../includes/header.php';
?>

<h1>Novo Cliente</h1>

<div class="card">
    <?php foreach ($erros as $erro): ?>
        <div class="alert alert-error"><?= e($erro) ?></div>
    <?php endforeach; ?>

    <form method="post" class="form-box" style="max-width:600px;">
        <h2 style="font-size:1rem;">Dados da empresa</h2>

        <label>Nome da empresa</label>
        <input type="text" name="nome_empresa" value="<?= e($_POST['nome_empresa'] ?? '') ?>" required>

        <label>CNPJ</label>
        <input type="text" name="cnpj" value="<?= e($_POST['cnpj'] ?? '') ?>" required>

        <label>Inscrição Estadual (I.E.)</label>
        <input type="text" name="ie" value="<?= e($_POST['ie'] ?? '') ?>">

        <label>Inscrição Municipal (I.M.)</label>
        <input type="text" name="im" value="<?= e($_POST['im'] ?? '') ?>">

        <label>Endereço completo</label>
        <input type="text" name="endereco" value="<?= e($_POST['endereco'] ?? '') ?>">

        <label>Tipo de documento</label>
        <select name="tipo_documento">
            <option value="">—</option>
            <option value="A5">A5</option>
            <option value="A3">A3</option>
        </select>

        <label>Plano</label>
        <select name="plano_id">
            <option value="">—</option>
            <?php foreach ($planos as $p): ?>
                <option value="<?= (int)$p['id'] ?>"><?= e($p['nome']) ?></option>
            <?php endforeach; ?>
        </select>

        <h2 style="font-size:1rem;margin-top:24px;">Admin do Cliente (primeiro acesso)</h2>

        <label>Nome</label>
        <input type="text" name="admin_nome" value="<?= e($_POST['admin_nome'] ?? '') ?>" required>

        <label>E-mail</label>
        <input type="email" name="admin_email" value="<?= e($_POST['admin_email'] ?? '') ?>" required>

        <label>Senha</label>
        <input type="password" name="admin_senha" required minlength="6">

        <button type="submit">Criar cliente e gerar chave</button>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
