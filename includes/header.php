<?php
/** @var string $titulo */
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo ?? 'ERP 2026') ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header class="topbar">
    <div class="topbar-brand">ERP 2026</div>
    <nav class="topbar-nav">
        <?php if (($_SESSION['tipo'] ?? null) === 'admin_sistema'): ?>
            <span>Olá, <?= e($_SESSION['admin_nome']) ?> (Admin do Sistema)</span>
            <a href="/admin/dashboard.php">Painel</a>
            <a href="/admin/clientes.php">Clientes</a>
            <a href="/public/logout.php">Sair</a>
        <?php elseif (($_SESSION['tipo'] ?? null) === 'usuario_cliente'): ?>
            <span>Olá, <?= e($_SESSION['usuario_nome']) ?> (<?= e($_SESSION['perfil']) ?>)</span>
            <?php if ($_SESSION['perfil'] === 'admin_cliente'): ?>
                <a href="/cliente/dashboard.php">Painel</a>
                <a href="/cliente/usuarios.php">Usuários</a>
            <?php endif; ?>
            <?php if (in_array($_SESSION['perfil'], ['admin_cliente', 'gerente'], true)): ?>
                <a href="/cliente/caixas.php">Caixas</a>
                <a href="/cliente/produtos.php">Produtos</a>
                <a href="/cliente/fidelidade.php">Fidelidade</a>
            <?php endif; ?>
            <?php if ($_SESSION['perfil'] === 'caixa'): ?>
                <a href="/caixa/pdv.php">PDV</a>
            <?php endif; ?>
            <a href="/public/logout.php">Sair</a>
        <?php endif; ?>
    </nav>
</header>
<main class="container">
