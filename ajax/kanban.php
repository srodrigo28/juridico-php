<?php
// AJAX CRUD para Tarefas
// Garante constante e inclui configs
if (!defined('SISTEMA_MEMBROS')) {
    define('SISTEMA_MEMBROS', true);
}
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../sistemas/config.php';
require_once __DIR__ . '/../config/database.php';

$sessStarted = function_exists('session_status') ? (session_status() === PHP_SESSION_ACTIVE) : false;
if (!$sessStarted) { @session_start(); }

$pdo = conectarBanco();
criarTabelas($pdo);

// Determinar usuário pelo email da sessão (mais estável em ambiente multiusuário)
$USER_EMAIL = $_SESSION['user_email'] ?? null;
if (empty($USER_EMAIL)) { http_response_code(401); echo json_encode(['ok' => false, 'error' => 'Não autenticado']); exit; }

// Criar tabela do Kanban se não existir
$pdo->exec("CREATE TABLE IF NOT EXISTS kanban_cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_email VARCHAR(255) NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    prioridade ENUM('alta','media','baixa') NOT NULL DEFAULT 'media',
    coluna ENUM('tarefas','doing','done') NOT NULL DEFAULT 'tarefas',
    data_prevista DATE NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_email (user_email),
    INDEX idx_coluna (coluna),
    INDEX idx_prioridade (prioridade),
    INDEX idx_prevista (data_prevista),
    CONSTRAINT fk_kanban_email FOREIGN KEY (user_email) REFERENCES usuarios_perfil(email) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Tentar ajustar schema existente: adicionar data_prevista e remover campo antigo 'data'
try {
    $pdo->exec("ALTER TABLE kanban_cards ADD COLUMN data_prevista DATE NULL");
} catch (Throwable $e) { /* coluna já existe */ }
try {
    $pdo->exec("ALTER TABLE kanban_cards DROP COLUMN data");
} catch (Throwable $e) { /* coluna pode não existir */ }
// Remover usuario_id e sua FK/índice se existirem (limpeza do schema)
try { $pdo->exec("ALTER TABLE kanban_cards DROP FOREIGN KEY fk_kanban_usuario"); } catch (Throwable $e) { /* fk inexistente */ }
try { $pdo->exec("DROP INDEX idx_usuario ON kanban_cards"); } catch (Throwable $e) { /* índice inexistente */ }
try { $pdo->exec("ALTER TABLE kanban_cards DROP COLUMN usuario_id"); } catch (Throwable $e) { /* coluna inexistente */ }
// Adicionar user_email se não existir
try {
    $pdo->exec("ALTER TABLE kanban_cards ADD COLUMN user_email VARCHAR(255) NOT NULL");
    $pdo->exec("CREATE INDEX idx_user_email ON kanban_cards(user_email)");
} catch (Throwable $e) { /* coluna já existe */ }
// Tentar criar FK por email
try {
    $pdo->exec("ALTER TABLE kanban_cards ADD CONSTRAINT fk_kanban_email FOREIGN KEY (user_email) REFERENCES usuarios_perfil(email) ON DELETE CASCADE");
} catch (Throwable $e) { /* fk já existe ou não suportada */ }

function json_ok($data = []) { echo json_encode(['ok' => true, 'data' => $data]); exit; }
function json_err($msg, $code = 400) { http_response_code($code); echo json_encode(['ok' => false, 'error' => $msg]); exit; }

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

try {
    switch ($action) {
        case 'list': {
            $stmt = $pdo->prepare("SELECT id, titulo, descricao, prioridade, coluna, data_prevista, DATE(criado_em) AS data_cadastro 
                FROM kanban_cards WHERE user_email = :email ORDER BY 
                CASE prioridade WHEN 'alta' THEN 3 WHEN 'media' THEN 2 ELSE 1 END DESC, criado_em ASC");
            $stmt->execute([':email' => $USER_EMAIL]);
            $rows = $stmt->fetchAll();
            json_ok(['cards' => $rows]);
        }
        case 'create': {
            $titulo = trim($_POST['titulo'] ?? '');
            $descricao = trim($_POST['descricao'] ?? '');
            $prioridade = $_POST['prioridade'] ?? 'media';
            $coluna = $_POST['coluna'] ?? 'tarefas';
            $dataPrevista = $_POST['data_prevista'] ?? null;
            if (strlen($titulo) < 3) json_err('Título inválido');
            if (!in_array($prioridade, ['alta','media','baixa'])) json_err('Prioridade inválida');
            if (!in_array($coluna, ['tarefas','doing','done'])) json_err('Coluna inválida');
            $stmt = $pdo->prepare("INSERT INTO kanban_cards (user_email, titulo, descricao, prioridade, coluna, data_prevista) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$USER_EMAIL, $titulo, $descricao, $prioridade, $coluna, $dataPrevista]);
            $id = (int)$pdo->lastInsertId();
            json_ok(['id' => $id, 'data_cadastro' => date('Y-m-d')]);
        }
        case 'update': {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) json_err('ID inválido');
            $titulo = trim($_POST['titulo'] ?? '');
            $descricao = trim($_POST['descricao'] ?? '');
            $prioridade = $_POST['prioridade'] ?? 'media';
            $dataPrevista = $_POST['data_prevista'] ?? null;
            if (strlen($titulo) < 3) json_err('Título inválido');
            if (!in_array($prioridade, ['alta','media','baixa'])) json_err('Prioridade inválida');
            $stmt = $pdo->prepare("UPDATE kanban_cards SET titulo=?, descricao=?, prioridade=?, data_prevista=? WHERE id=? AND user_email=?");
            $stmt->execute([$titulo, $descricao, $prioridade, $dataPrevista, $id, $USER_EMAIL]);
            json_ok();
        }
        case 'move': {
            $id = (int)($_POST['id'] ?? 0);
            $coluna = $_POST['coluna'] ?? '';
            if ($id <= 0) json_err('ID inválido');
            if (!in_array($coluna, ['tarefas','doing','done'])) json_err('Coluna inválida');
            $stmt = $pdo->prepare("UPDATE kanban_cards SET coluna=? WHERE id=? AND user_email=?");
            $stmt->execute([$coluna, $id, $USER_EMAIL]);
            json_ok();
        }
        case 'delete': {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) json_err('ID inválido');
            $stmt = $pdo->prepare("DELETE FROM kanban_cards WHERE id=? AND user_email=?");
            $stmt->execute([$id, $USER_EMAIL]);
            json_ok();
        }
        default: json_err('Ação inválida', 404);
    }
} catch (Throwable $e) {
    json_err('Erro: ' . $e->getMessage(), 500);
}
