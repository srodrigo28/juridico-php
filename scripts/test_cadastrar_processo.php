<?php
// Script de teste CLI para simular POST para cadastrar_processo
// Execute: php scripts/test_cadastrar_processo.php

define('SISTEMA_MEMBROS', true);
require_once __DIR__ . '/../sistemas/config.php';
$pdo = getDBConnection();

// Iniciar sessão compatível
session_name('MEMBROS_SESSION');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// CSRF e usuário de teste
$_SESSION['csrf_token'] = 'test-token-123';
$_SESSION['user_id'] = 'admin';
$_SESSION['user_email'] = 'rodrigoexer2@gmail.com';

// Montar POST simulado
$_POST = [];
$_POST['action'] = 'cadastrar_processo';
$_POST['numero_processo'] = 'TESTE-0001/2026';
$_POST['tribunal'] = 'NACIONAL';
$_POST['cliente_id'] = 1; // cliente existente no dump
$_POST['vara'] = '1ª Vara';
$_POST['parte_contraria'] = 'Fulano de Tal';
$_POST['valor_causa'] = '1000.00';
$_POST['status'] = 'em_andamento';
$_POST['observacoes'] = 'Teste automatizado via CLI';
// Eventos: formato arrays
$_POST['eventos'] = [];
$_POST['eventos'][0] = [
    'descricao' => 'Intimação inicial',
    'data_inicial' => date('d/m/Y'),
    'prazo_dias' => '10',
    'tipo_contagem' => 'uteis',
    'metodologia' => 'exclui_inicio'
];

// CSRF token
$_POST['csrf_token'] = $_SESSION['csrf_token'];

// Nenhum arquivo enviado

// Chamar handler
chdir(__DIR__ . '/..');
require_once 'ajax/handler.php';

// handler já imprime JSON e encerra
