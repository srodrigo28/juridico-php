<?php
// Logout
define('SISTEMA_MEMBROS', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

fazerLogout();
redirecionarPara(LOGIN_URL);
