<?php
/**
 * Sistema de Autenticação - CORRIGIDO
 * PRECIFEX - auth.php
 */

// Definir constante para permitir inclusão
if (!defined('SISTEMA_MEMBROS')) {
    define('SISTEMA_MEMBROS', true);
}

// Incluir configurações
require_once __DIR__ . '/config.php';

// Função para logs específicos do sistema de email
function logSistemaEmail($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logFile = __DIR__ . '/webhook_licencas.log';
    $logMessage = "[$timestamp] [EMAIL-$level] $message\n";
    @file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

// PHPMailer - Incluir arquivos
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    require_once(__DIR__.'/phpmailer/src/Exception.php');
    require_once(__DIR__.'/phpmailer/src/PHPMailer.php');
    require_once(__DIR__.'/phpmailer/src/SMTP.php');
} catch (Exception $e) {
    // Falha silenciosa do PHPMailer
}

/**
 * NOVA FUNÇÃO: Verificar se email possui QUALQUER licença ativa
 */
function emailTemLicencaAtiva($email) {
    try {
        $pdo = getDBConnection();
        if (!$pdo) {
            logSistema("Erro: Falha na conexão com BD para verificar licenças", 'ERROR');
            return false;
        }
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM licencas 
            WHERE email = :email 
            AND status_licenca = 'ativa'
        ");
        
        $stmt->execute([':email' => $email]);
        $result = $stmt->fetch();
        
        $temLicenca = $result['total'] > 0;
        
        if ($temLicenca) {
            logSistema("Email com licença ativa confirmada: {$email}", 'INFO');
        } else {
            logSistema("Email SEM licença ativa: {$email}", 'WARN');
        }
        
        return $temLicenca;
        
    } catch (Exception $e) {
        logSistema("Erro ao verificar licenças do email: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * Verificar se email possui licença ativa para um produto específico
 */
function verificarLicencaAtiva($email, $produto_id) {
    try {
        $pdo = getDBConnection();
        if (!$pdo) {
            logSistema("Erro: Falha na conexão com BD para verificar licença", 'ERROR');
            return false;
        }
        
        $stmt = $pdo->prepare("
            SELECT email, produto_id, status_licenca, produto_nome 
            FROM licencas 
            WHERE email = :email 
            AND produto_id = :produto_id 
            AND status_licenca = 'ativa'
        ");
        
        $stmt->execute([
            ':email' => $email,
            ':produto_id' => $produto_id
        ]);
        
        $licenca = $stmt->fetch();
        
        if ($licenca) {
            logSistema("Licença ativa encontrada: {$email} - Produto: {$produto_id}", 'INFO');
            return $licenca;
        }
        
        logSistema("Licença não encontrada ou inativa: {$email} - Produto: {$produto_id}", 'WARN');
        return false;
        
    } catch (Exception $e) {
        logSistema("Erro ao verificar licença: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * Obter todas as licenças ativas de um email
 */
function obterLicencasUsuario($email) {
    try {
        $pdo = getDBConnection();
        if (!$pdo) {
            return [];
        }
        
        $stmt = $pdo->prepare("
            SELECT produto_id, produto_nome, status_licenca
            FROM licencas 
            WHERE email = :email 
            AND status_licenca = 'ativa'
            ORDER BY produto_nome
        ");
        
        $stmt->execute([':email' => $email]);
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        logSistema("Erro ao obter licenças do usuário: " . $e->getMessage(), 'ERROR');
        return [];
    }
}

/**
 * Verificar se usuário já tem senha cadastrada
 */
function usuarioTemSenha($email) {
    try {
        $pdo = getDBConnection();
        if (!$pdo) {
            return false;
        }
        
        $stmt = $pdo->prepare("
            SELECT email FROM usuarios_sistema 
            WHERE email = :email AND senha IS NOT NULL
        ");
        
        $stmt->execute([':email' => $email]);
        return $stmt->fetch() !== false;
        
    } catch (Exception $e) {
        logSistema("Erro ao verificar senha do usuário: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * FUNÇÃO CORRIGIDA: Criar token APENAS para emails com licença ativa
 */
function criarTokenSenha($email) {
    try {
        // VALIDAÇÃO CRÍTICA: Verificar se email tem licença ativa ANTES de criar token
        if (!emailTemLicencaAtiva($email)) {
            logSistema("TENTATIVA BLOQUEADA: Token solicitado para email sem licença: {$email}", 'WARN');
            logSistemaEmail("TENTATIVA BLOQUEADA: Token solicitado para email sem licença: {$email}", 'WARN');
            return false; // NÃO criar token para quem não tem licença
        }
        
        $pdo = getDBConnection();
        if (!$pdo) {
            return false;
        }
        
        $token = gerarToken();
        $expiry = date('Y-m-d H:i:s', time() + TOKEN_EXPIRY);
        
        // Inserir ou atualizar token
        $stmt = $pdo->prepare("
            INSERT INTO usuarios_sistema (email, token_senha, token_expiry, criado_em)
            VALUES (:email, :token, :expiry, NOW())
            ON DUPLICATE KEY UPDATE 
                token_senha = :token,
                token_expiry = :expiry,
                atualizado_em = NOW()
        ");
        
        $result = $stmt->execute([
            ':email' => $email,
            ':token' => $token,
            ':expiry' => $expiry
        ]);
        
        if ($result) {
            logSistema("Token criado para email com licença válida: {$email}", 'INFO');
            logSistemaEmail("Token criado para email com licença válida: {$email}", 'INFO');
            return $token;
        }
        
        return false;
        
    } catch (Exception $e) {
        logSistema("Erro ao criar token: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * Validar token de criação de senha
 */
function validarTokenSenha($token) {
    try {
        $pdo = getDBConnection();
        if (!$pdo) {
            return false;
        }
        
        $stmt = $pdo->prepare("
            SELECT email, token_expiry 
            FROM usuarios_sistema 
            WHERE token_senha = :token 
            AND token_expiry > NOW()
        ");
        
        $stmt->execute([':token' => $token]);
        $usuario = $stmt->fetch();
        
        if ($usuario) {
            // VALIDAÇÃO ADICIONAL: Verificar se ainda tem licença ativa
            if (!emailTemLicencaAtiva($usuario['email'])) {
                logSistema("Token válido MAS email perdeu licença: {$usuario['email']}", 'WARN');
                return false;
            }
            
            logSistema("Token válido para: {$usuario['email']}", 'INFO');
            return $usuario['email'];
        }
        
        logSistema("Token inválido ou expirado: {$token}", 'WARN');
        return false;
        
    } catch (Exception $e) {
        logSistema("Erro ao validar token: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * FUNÇÃO CORRIGIDA: Definir senha APENAS para emails com licença
 */
function definirSenhaUsuario($email, $senha, $token = null) {
    try {
        // VALIDAÇÃO CRÍTICA: Verificar licença ativa
        if (!emailTemLicencaAtiva($email)) {
            logSistema("TENTATIVA BLOQUEADA: Definir senha para email sem licença: {$email}", 'ERROR');
            return false;
        }
        
        $pdo = getDBConnection();
        if (!$pdo) {
            return false;
        }
        
        // Se tem token, validar primeiro
        if ($token) {
            $emailToken = validarTokenSenha($token);
            if (!$emailToken || $emailToken !== $email) {
                logSistema("Token inválido para definição de senha: {$email}", 'ERROR');
                return false;
            }
        }
        
        $senhaHash = hashSenha($senha);
        
        $stmt = $pdo->prepare("
            INSERT INTO usuarios_sistema (email, senha, criado_em)
            VALUES (:email, :senha, NOW())
            ON DUPLICATE KEY UPDATE 
                senha = :senha,
                token_senha = NULL,
                token_expiry = NULL,
                atualizado_em = NOW()
        ");
        
        $result = $stmt->execute([
            ':email' => $email,
            ':senha' => $senhaHash
        ]);
        
        if ($result) {
            logSistema("Senha definida para email com licença válida: {$email}", 'INFO');
            return true;
        }
        
        return false;
        
    } catch (Exception $e) {
        logSistema("Erro ao definir senha: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * FUNÇÃO CORRIGIDA: Fazer login com validação de licença
 */
function fazerLogin($email, $senha) {
    try {
        // Validar email
        if (!validarEmail($email)) {
            return ['success' => false, 'message' => 'Email inválido'];
        }
        
        // VALIDAÇÃO CRÍTICA: Verificar licença ativa ANTES de qualquer coisa
        if (!emailTemLicencaAtiva($email)) {
            logSistema("TENTATIVA BLOQUEADA: Login para email sem licença: {$email}", 'WARN');
            return ['success' => false, 'message' => 'Nenhuma licença ativa encontrada para este email'];
        }
        
        // Verificar se tem senha cadastrada
        if (!usuarioTemSenha($email)) {
            return ['success' => false, 'message' => 'Usuário precisa definir senha primeiro'];
        }
        
        $pdo = getDBConnection();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Erro interno do sistema'];
        }
        
        // Buscar dados do usuário
        $stmt = $pdo->prepare("
            SELECT email, senha 
            FROM usuarios_sistema 
            WHERE email = :email
        ");
        
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch();
        
        if (!$usuario || !verificarSenha($senha, $usuario['senha'])) {
            logSistema("Tentativa de login inválida: {$email}", 'WARN');
            return ['success' => false, 'message' => 'Email ou senha incorretos'];
        }
        
        // Obter licenças (já validamos que tem pelo menos uma ativa)
        $licencas = obterLicencasUsuario($email);
        
        // Criar sessão
        $_SESSION['user_id'] = md5($email);
        $_SESSION['user_email'] = $email;
        $_SESSION['user_licencas'] = $licencas;
        $_SESSION['login_time'] = time();
        
        // Atualizar último login
        $stmt = $pdo->prepare("
            UPDATE usuarios_sistema 
            SET ultimo_login = NOW() 
            WHERE email = :email
        ");
        $stmt->execute([':email' => $email]);
        
        logSistema("Login realizado com sucesso: {$email}", 'INFO');
        return ['success' => true, 'message' => 'Login realizado com sucesso'];
        
    } catch (Exception $e) {
        logSistema("Erro no login: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Erro interno do sistema'];
    }
}

/**
 * Fazer logout do usuário
 */
function fazerLogout() {
    if (isset($_SESSION['user_email'])) {
        logSistema("Logout realizado: {$_SESSION['user_email']}", 'INFO');
    }
    
    // Limpar dados da sessão
    $_SESSION = array();
    
    // Destruir cookie da sessão
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destruir sessão
    session_destroy();
}

/**
 * Verificar acesso a produto específico
 */
function verificarAcessoProduto($produto_id) {
    if (!estaLogado()) {
        return false;
    }
    
    $email = $_SESSION['user_email'];
    return verificarLicencaAtiva($email, $produto_id) !== false;
}

/**
 * Middleware para proteger páginas
 * Aceita um produto_id OU array de produto_ids
 */
function protegerPagina($produto_id = null) {
    if (!estaLogado()) {
        logSistema("Acesso negado - não logado", 'WARN');
        redirecionarPara(LOGIN_URL . '?erro=login_required');
    }
    
    // Se especificou produto(s), verificar acesso
    if ($produto_id) {
        // Converter para array se for string
        $produtos = is_array($produto_id) ? $produto_id : [$produto_id];
        
        // Verificar se tem acesso a PELO MENOS UM dos produtos
        $temAcesso = false;
        foreach ($produtos as $pid) {
            if (verificarAcessoProduto($pid)) {
                $temAcesso = true;
                break;
            }
        }
        
        if (!$temAcesso) {
            $produtos_str = implode(', ', $produtos);
            logSistema("Acesso negado aos produtos [{$produtos_str}] para: {$_SESSION['user_email']}", 'WARN');
            redirecionarPara(DASHBOARD_URL . '?erro=acesso_negado');
        }
    }
}

/**
 * Criar estrutura da tabela se não existir
 */
function criarTabelaUsuarios() {
    try {
        $pdo = getDBConnection();
        if (!$pdo) {
            return false;
        }
        
        $sql = "
            CREATE TABLE IF NOT EXISTS usuarios_sistema (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) UNIQUE NOT NULL,
                senha VARCHAR(255) NULL,
                token_senha VARCHAR(64) NULL,
                token_expiry DATETIME NULL,
                ultimo_login DATETIME NULL,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_email (email),
                INDEX idx_token (token_senha)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $pdo->exec($sql);
        logSistema("Tabela usuarios_sistema verificada/criada", 'INFO');
        return true;
        
    } catch (Exception $e) {
        logSistema("Erro ao criar tabela: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * FUNÇÃO CORRIGIDA: Enviar email APENAS para quem tem licença
 */
function enviarEmailDefinirSenhaAutomatico($email, $produto_nome = 'PRECIFEX') {
    try {
        // Verificar se email é válido
        if (!validarEmail($email)) {
            logSistema("Email inválido para envio automático: {$email}", 'ERROR');
            return false;
        }
        
        // VALIDAÇÃO CRÍTICA: Verificar licença ativa ANTES de enviar email
        if (!emailTemLicencaAtiva($email)) {
            logSistema("ENVIO BLOQUEADO: Email sem licença ativa: {$email}", 'WARN');
            logSistemaEmail("ENVIO BLOQUEADO: Email sem licença ativa: {$email}", 'WARN');
            return false; // NÃO enviar email para quem não tem licença
        }
        
        // Verificar se usuário já tem senha cadastrada
        if (usuarioTemSenha($email)) {
            logSistema("Usuário já tem senha cadastrada, não enviando email: {$email}", 'INFO');
            return true; // Retorna true pois não é erro
        }
        
        // Criar token para definição de senha (já valida licença internamente)
        $token = criarTokenSenha($email);
        
        if (!$token) {
            logSistema("Erro ao criar token para envio automático: {$email}", 'ERROR');
            return false;
        }
        
        // Gerar link de acesso
        $linkAcesso = (defined('BASE_URL') ? BASE_URL : 'https://precifex.com/sistemas') . "/index.php?token=" . $token;
        
        // Conteúdo personalizado para pós-compra
        $conteudo = '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                <div style="background: #ffffff; color: #000000; padding: 40px 30px; text-align: center; border-radius: 10px 10px 0 0; border: 1px solid #ddd;">
                    <h1 style="margin: 0; font-size: 28px;">🎉 Parabéns pela sua compra!</h1>
                    <p style="margin: 15px 0 0 0; font-size: 16px; opacity: 0.9;">Bem-vindo à plataforma ' . htmlspecialchars($produto_nome) . '</p>
                </div>
                
                <div style="background: #ffffff; padding: 40px 30px; border-radius: 0 0 10px 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                    <p style="font-size: 16px; line-height: 1.6; color: #000000; margin-bottom: 20px;">
                        Sua compra foi processada com sucesso! 🚀
                    </p>
                    
                    <p style="font-size: 16px; line-height: 1.6; color: #000000; margin-bottom: 25px;">
                        Para acessar sua área de membros exclusiva, você precisa definir uma senha de acesso. 
                        Clique no botão abaixo para criar sua senha:
                    </p>
                    
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="' . $linkAcesso . '" 
                           style="display: inline-block; 
       color: #000000; 
       background: #ffffff; 
       border: 2px solid #1A2536;
       border-radius: 10px; 
       padding: 16px 32px; 
       font-weight: 600; 
       text-decoration: none; 
       font-size: 16px;
       box-shadow: 0 4px 15px rgba(26, 37, 54, 0.3);">
                            🔐 DEFINIR MINHA SENHA
                        </a>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 25px 0;">
                        <p style="margin: 0; font-size: 14px; color: #333333;">
                            <strong>⏰ Importante:</strong> Este link é válido por 24 horas. 
                            Caso expire, você pode solicitar um novo link acessando nossa plataforma.
                        </p>
                    </div>
                    
                    <p style="font-size: 14px; line-height: 1.6; color: #333333; margin-top: 30px;">
                        Se você não conseguir clicar no botão, copie e cole este link no seu navegador:<br>
                        <span style="word-break: break-all; font-family: monospace; background: #f8f9fa; padding: 8px; border-radius: 4px; display: inline-block; margin-top: 8px;">
                            ' . $linkAcesso . '
                        </span>
                    </p>
                    
                    <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
                    
                    <p style="font-size: 14px; color: #333333; margin: 0;">
                        Qualquer dúvida, entre em contato conosco.<br>
                        Equipe PRECIFEX
                    </p>
                </div>
            </div>
        ';
        
        $html = '<!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Bem-vindo à ' . htmlspecialchars($produto_nome) . '</title>
        </head>
        <body style="margin: 0; padding: 20px; background-color: #f5f5f5;">
            ' . $conteudo . '
        </body>
        </html>';

        // Configurar e enviar email via PHPMailer
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            $mail = new PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host = 'smtp.umbler.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'contato@precifex.com';
            $mail->Password = '#X58cR@04125';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            
            $mail->setFrom('contato@precifex.com', 'PRECIFEX');
            $mail->addAddress($email);
            
            $mail->isHTML(true);
            $mail->Subject = '🎉 Bem-vindo à plataforma ' . $produto_nome . ' - Defina sua senha';
            $mail->Body = $html;
            
            $mail->send();
            
            logSistema("Email automático enviado com sucesso para: {$email}", 'INFO');
            logSistemaEmail("Email automático enviado com sucesso para: {$email}", 'SUCCESS');
            return true;
            
        } else {
            logSistema("PHPMailer não disponível para envio automático: {$email}", 'ERROR');
            return false;
        }
        
    } catch (Exception $e) {
        logSistema("Erro ao enviar email automático para {$email}: " . $e->getMessage(), 'ERROR');
        logSistemaEmail("Erro ao enviar email automático para {$email}: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * FUNÇÃO CORRIGIDA: Processar compra e enviar email COM validação
 */
function processarCompraEEnviarEmail($email, $produto_nome = 'PRECIFEX', $dadosCompra = []) {
    try {
        logSistemaEmail("=== INICIANDO PROCESSAMENTO DE COMPRA ===");
        logSistemaEmail("Email: {$email}, Produto: {$produto_nome}");
        
        // Verificar se email é válido
        if (!validarEmail($email)) {
            logSistemaEmail("Email inválido: {$email}", 'ERROR');
            return false;
        }
        
        // VALIDAÇÃO CRÍTICA: Verificar se tem licença ativa
        if (!emailTemLicencaAtiva($email)) {
            logSistemaEmail("PROCESSAMENTO BLOQUEADO: Email sem licença ativa: {$email}", 'WARN');
            return false; // NÃO processar para quem não tem licença
        }
        
        // Verificar se usuário já tem senha cadastrada
        if (usuarioTemSenha($email)) {
            logSistemaEmail("Usuário já tem senha cadastrada: {$email}", 'INFO');
            return true; // Não é erro, usuário já está configurado
        }
        
        logSistemaEmail("Usuário com licença válida, prosseguindo...");
        
        // Usar a função já corrigida
        return enviarEmailDefinirSenhaAutomatico($email, $produto_nome);
        
    } catch (Exception $e) {
        logSistemaEmail("Erro ao processar compra para {$email}: " . $e->getMessage(), 'ERROR');
        return false;
    } finally {
        logSistemaEmail("=== FIM DO PROCESSAMENTO ===");
    }
}

/**
 * NOVA FUNÇÃO: Limpar usuários sem licença ativa (usar com cuidado)
 */
function limparUsuariosSemLicenca($executar = false) {
    try {
        $pdo = getDBConnection();
        if (!$pdo) {
            return false;
        }
        
        // Buscar usuários que não têm licença ativa
        $stmt = $pdo->prepare("
            SELECT u.email, u.criado_em
            FROM usuarios_sistema u
            LEFT JOIN licencas l ON u.email = l.email AND l.status_licenca = 'ativa'
            WHERE l.email IS NULL
        ");
        
        $stmt->execute();
        $usuariosSemLicenca = $stmt->fetchAll();
        
        logSistema("Encontrados " . count($usuariosSemLicenca) . " usuários sem licença ativa", 'INFO');
        
        if ($executar && !empty($usuariosSemLicenca)) {
            // CUIDADO: Isso vai DELETAR os registros!
            $stmt = $pdo->prepare("
                DELETE FROM usuarios_sistema 
                WHERE email NOT IN (
                    SELECT DISTINCT email FROM licencas WHERE status_licenca = 'ativa'
                )
            ");
            
            $resultado = $stmt->execute();
            $removidos = $stmt->rowCount();
            
            logSistema("LIMPEZA EXECUTADA: {$removidos} usuários sem licença removidos", 'WARN');
            return $removidos;
        }
        
        return count($usuariosSemLicenca);
        
    } catch (Exception $e) {
        logSistema("Erro na limpeza de usuários: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * Criar tabela para logs de emails (opcional)
 */
function criarTabelaLogsEmails() {
    try {
        $pdo = getDBConnection();
        if (!$pdo) {
            return false;
        }
        
        $sql = "
            CREATE TABLE IF NOT EXISTS logs_emails_enviados (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL,
                tipo ENUM('pos_compra', 'manual', 'recuperacao') DEFAULT 'pos_compra',
                produto_nome VARCHAR(255) NULL,
                enviado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_email (email),
                INDEX idx_tipo (tipo),
                INDEX idx_enviado (enviado_em)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $pdo->exec($sql);
        return true;
        
    } catch (Exception $e) {
        logSistema("Erro ao criar tabela de logs de emails: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

// Criar tabelas automaticamente
criarTabelaUsuarios();
criarTabelaLogsEmails();
// Criar tabela licencas necessária para validação de acesso
try {
    $pdoTmp = getDBConnection();
    if ($pdoTmp) {
        $sqlLicencas = "
            CREATE TABLE IF NOT EXISTS licencas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL,
                produto_id VARCHAR(32) NOT NULL,
                produto_nome VARCHAR(255) NULL,
                status_licenca ENUM('ativa','inativa') DEFAULT 'ativa',
                data_expiracao DATE NULL,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_email (email),
                INDEX idx_produto (produto_id),
                INDEX idx_status (status_licenca),
                INDEX idx_expira (data_expiracao)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        $pdoTmp->exec($sqlLicencas);
    }
} catch (Exception $e) {
    // silencioso em local
}
?>