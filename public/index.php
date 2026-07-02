<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (($_SESSION['tipo'] ?? null) === 'admin_sistema') {
    redirect('/admin/dashboard.php');
} elseif (($_SESSION['tipo'] ?? null) === 'usuario_cliente') {
    if ($_SESSION['perfil'] === 'caixa') {
        redirect('/caixa/pdv.php');
    }
    redirect('/cliente/dashboard.php');
}
redirect('/public/login.php');
