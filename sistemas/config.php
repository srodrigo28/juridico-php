<?php
/**
 * Configurações do Sistema de Membros
 * PRECIFEX - config.php
 * Versão corrigida - Janeiro 2026
 */

// Impedir acesso direto
if (!defined('SISTEMA_MEMBROS')) {
    define('SISTEMA_MEMBROS', true);
}

// Configuração de timezone
date_default_timezone_set('America/Sao_Paulo');

// Detectar ambiente (local vs produção) - DECLARAR APENAS UMA VEZ
$__host = $_SERVER['HTTP_HOST'] ?? '';
$__isLocal = preg_match('/^(localhost|127\\.0\\.0\\.1)(:\\d+)?$/', $__host) === 1;

// Configurações do banco de dados
if ($__isLocal) {
    // Ambiente local
    define('DB_HOST', 'localhost');
    define('DB_PORT', '3306');
    define('DB_NAME', 'adv');
    define('DB_USER', 'root');
    define('DB_PASS', '');

    // define('DB_HOST', '77.37.126.7');
    // define('DB_PORT', '3306');
    // define('DB_NAME', 'adv');
    // define('DB_USER', 'srodrigo');
    // define('DB_PASS', '@dV#sRnAt98!');

    
    // Habilitar modo debug em ambiente local
    if (!defined('DEBUG_MODE')) {
        define('DEBUG_MODE', true);
    }
} else {
    // Produção
    define('DB_HOST', '77.37.126.7');
    define('DB_PORT', '3306');
    define('DB_NAME', 'adv');
    define('DB_USER', 'srodrigo');
    define('DB_PASS', '@dV#sRnAt98!');
    
    // Desabilitar debug em produção
    if (!defined('DEBUG_MODE')) {
        define('DEBUG_MODE', false);
    }
}

// Configurações de segurança
define('SALT_SENHA', 'JLP_SISTEMAS_2025_SALT_HASH');
define('TOKEN_EXPIRY', 24 * 60 * 60); // 24 horas para token de criação de senha

// URLs do sistema (usando a variável já declarada)
    if ($__isLocal) {
    // Base local (ajustada para ambiente local do XAMPP)
    $__scheme = 'http://';
    // Se você mantém projeto em c:/xampp/htdocs/www/v2, use esta base
    $__baseLocal = $__scheme . $__host . '/www/v2';
    define('BASE_URL', $__baseLocal);
    define('LOGIN_URL', BASE_URL . '/login.php');
    define('DASHBOARD_URL', BASE_URL . '/index.php?aba=dashboard');
    define('LOGOUT_URL', BASE_URL . '/sistemas/logout.php');
} else {
    // Produção - AJUSTE CONFORME SEU DOMÍNIO
    define('BASE_URL', 'https://adv.precifex.com');
    define('LOGIN_URL', BASE_URL . '/login.php');
    define('DASHBOARD_URL', BASE_URL . '/views/dashboard.php');
    define('LOGOUT_URL', BASE_URL . '/sistemas/logout.php');
}

// Produtos disponíveis
$PRODUTOS_SISTEMA = [
    
    // Produtos gratuitos disponíveis para todos os usuários logados
    'calculadora' => [
        'nome' => 'Calculadora de Datas',
        'descricao' => 'Realize cálculos de prazos de forma rápida e precisa, considerando feriados e diferentes metodologias de contagem',
        'url' => 'https://precifex.com/calculadora/',
        'icone' => '🗓️',
        'ativo' => true,
        'gratuito' => true
    ],

    'profissionais' => [
        'nome' => 'Banco de Peritos',
        'descricao' => 'Encontre o profissional ideal (contador, engenheiro, psicólogo, grafotécnico, documentoscópico e outros)',
        'url' => 'https://precifex.com/profissionais/',
        'icone' => '👷‍♂️',
        'ativo' => true,
        'gratuito' => true
    ],

    'simulador' => [
        'nome' => 'Simulador de Financiamento de Imóvel',
        'descricao' => 'Calcule sua prestação e receba assessoria GRATUITA para conseguir as menores taxas',
        'url' => 'https://precifex.com/simulador/',
        'icone' => '🏠',
        'ativo' => true,
        'gratuito' => true
    ],
     
    // Produtos pagos
    '4737273' => [
        'nome' => 'Pesquisa de Preços',
        'descricao' => 'Sistema completo para pesquisa de preços públicos com milhões de registros atualizados',
        'url' => 'https://precifex.com/precos/',
        'icone' => '🔍',
        'ativo' => true
    ],

    '5776734' => [
        'nome' => 'Precifex Jurídico',
        'descricao' => 'Sistema de Gestão para advogados e escritórios de advocacia',
        'url' => 'https://precifex.com/juridico/',
        'icone' => '⚖️',
        'ativo' => true
    ],
    
    '5692415' => [
        'nome' => 'Atualizador de Valores',
        'descricao' => 'Ferramenta completa para atualização de valores com correção monetária, juros remuneratórios e juros de mora',
        'url' => 'https://precifex.com/atualizador',
        'icone' => '💰',
        'ativo' => true
    ],

    '5329128' => [
        'nome' => 'Sistema de Revisão de Plano de Saúde',
        'descricao' => 'Revisão de planos de saúde com cálculo de diferenças, reajustes ANS/FIPE e correção monetária',
        'url' => 'https://precifex.com/planodesaude',
        'icone' => '🏥',
        'ativo' => true
    ],

    '2222222' => [
        'nome' => 'Sistema de Revisão Bancária',
        'descricao' => 'Identifique juros abusivos e recalcule prestações e saldo devedor',
        'url' => 'https://precifex.com/revisaobancaria',
        'icone' => '🏦',
        'ativo' => true
    ],

    '3333333' => [
        'nome' => 'Buscador Processual',
        'descricao' => 'Sistema de Consulta Automatizada de Processos',
        'url' => 'https://precifex.com/buscadorprocessual',
        'icone' => '⚖️',
        'ativo' => true
    ],
    
    '6666666' => [
        'nome' => 'Calculadora de Aposentadoria INSS',
        'descricao' => 'Sistema de cálculo de renda mensal inicial de aposentadoria (RMI)',
        'url' => 'https://precifex.com/inss',
        'icone' => '🪙',
        'ativo' => true
    ],

    '9999999' => [
        'nome' => 'Análise de Leilões',
        'descricao' => 'Sistema inteligente para identificar oportunidades de investimento em leilão de imóveis',
        'url' => 'https://precifex.com/leilao',
        'icone' => '🏚️',
        'ativo' => true
    ],

    '7777777' => [
        'nome' => 'Sistema de Diagnóstico para Clínicas',
        'descricao' => 'Sistema de questionário diagnóstico para clínicas, estruturado por setores',
        'url' => 'https://precifex.com/clinicas',
        'icone' => '🏥',
        'ativo' => true
    ],

    // Planilhas
    '1974234' => [
        'nome' => 'Planilha de Revisão de Plano de Saúde',
        'descricao' => 'Planilha para identificar reajustes abusivos e recalcular mensalidades em planos de saúde',
        'url' => BASE_URL . '/arquivos.php?id=1974234',
        'icone' => '📊',
        'ativo' => true,
        'tipo' => 'planilha'
    ],

    '4854478' => [
        'nome' => 'Planilha Master de Revisão Bancária',
        'descricao' => 'Planilha para identificar juros abusivos e recalcular contratos bancários',
        'url' => BASE_URL . '/arquivos.php?id=4854478',
        'icone' => '📊',
        'ativo' => true,
        'tipo' => 'planilha'
    ],

    '1979826' => [
        'nome' => 'Planilha de Revisão do PASEP',
        'descricao' => 'Planilha para refazer a evolução da conta com base na legislação ou conforme teses jurídicas',
        'url' => BASE_URL . '/arquivos.php?id=1979826',
        'icone' => '📊',
        'ativo' => true,
        'tipo' => 'planilha'
    ],

    '5345142' => [
        'nome' => 'Planilha de Saques Indevidos PASEP',
        'descricao' => 'Planilha para cálculo de conversão de moeda, atualização monetária e juros dos débitos que saíram da conta',
        'url' => BASE_URL . '/arquivos.php?id=5345142',
        'icone' => '📊',
        'ativo' => true,
        'tipo' => 'planilha'
    ],

    '2006880' => [
        'nome' => 'Planilha de Evolução de Empréstimo/Financiamento',
        'descricao' => 'Planilha para simular prestações e saldo devedor com Tabela Price, SAC e Juros Simples',
        'url' => BASE_URL . '/arquivos.php?id=2006880',
        'icone' => '📊',
        'ativo' => true,
        'tipo' => 'planilha'
    ],

    '2616761' => [
        'nome' => 'Planilha de Controle Financeiro',
        'descricao' => 'Planilha para controle de entradas (receitas) e saídas (despesas) de dinheiro',
        'url' => BASE_URL . '/arquivos.php?id=2616761',
        'icone' => '📊',
        'ativo' => true,
        'tipo' => 'planilha'
    ],

    // Cursos externos (Hotmart)
    '3106737' => [
        'nome' => 'Treinamento em Perícia Contábil',
        'descricao' => 'Sessão individual de 1 hora para ensinar a analisar contratos, extratos e realizar cálculos',
        'url' => 'https://hotmart.com/pt-br/club/joaolucasprotasio/products/3106737',
        'icone' => '💹',
        'ativo' => true,
        'externa' => true
    ],

    '2939459' => [
        'nome' => 'Curso Básico de Excel e Matemática Financeira em 30 min',
        'descricao' => 'Aprenda o básico do Excel e das fórmulas de matemática financeira (prestações, juros, saldo)',
        'url' => 'https://hotmart.com/pt-br/club/joaolucasprotasio/products/2939459',
        'icone' => '📉',
        'ativo' => true,
        'externa' => true
    ],

    '2981646' => [
        'nome' => 'Curso de Perícia Contábil Judicial',
        'descricao' => 'Aprenda perícia com videoaulas gravadas, materiais pdf e planilhas de cálculos',
        'url' => 'https://hotmart.com/pt-br/club/joaolucasprotasio/products/2981646',
        'icone' => '⚖️',
        'ativo' => true,
        'externa' => true
    ],

    '5280057' => [
        'nome' => 'Método S.O.S: Venda 10 a 15 Novos Shows Por Mês',
        'descricao' => 'Curso para aprender a criar anúncios nas redes sociais e outras dicas para vender mais shows musicais',
        'url' => 'https://hotmart.com/pt-br/club/joaolucasprotasio/products/5280057',
        'icone' => '🎤',
        'ativo' => true,
        'externa' => true
    ],

    '5574119' => [
        'nome' => 'Exclusão do ICMS da Base de Cálculo do PIS/COFINS',
        'descricao' => 'Planilha e videoaula para calcular e recuperar valores de PIS/COFINS pagos indevidamente',
        'url' => 'https://hotmart.com/pt-br/club/joaolucasprotasio/products/5574119',
        'icone' => '💲',
        'ativo' => true,
        'externa' => true
    ],
  
    // Arquivos e guias
    '4879402' => [
        'nome' => 'Checklist para Cadastramento como Perito Judicial',
        'descricao' => 'Guia completo para cadastramento como perito judicial',
        'url' => BASE_URL . '/arquivos.php?id=4879402',
        'icone' => '📋',
        'ativo' => true,
        'tipo' => 'arquivo'
    ],

    '3030198' => [
        'nome' => 'Passo a Passo do Perito Judicial: Da Nomeação ao Recebimento dos Honorários',
        'descricao' => 'Descubra exatamente o que fazer após receber sua primeira nomeação com este guia prático completo',
        'url' => BASE_URL . '/arquivos.php?id=3030198',
        'icone' => '📋',
        'ativo' => true,
        'tipo' => 'arquivo'
    ],

    '5091645' => [
        'nome' => 'Decifrando Microfilmagem e Extrato PASEP',
        'descricao' => 'Passo a passo para interpretar corretamente microfilmagens e extratos',
        'url' => BASE_URL . '/arquivos.php?id=5091645',
        'icone' => '📋',
        'ativo' => true,
        'tipo' => 'arquivo'
    ],

    '5255689' => [
        'nome' => 'Método para Melhorar Comunicação e Concentração de Crianças',
        'descricao' => 'Técnicas práticas para desenvolvimento infantil',
        'url' => BASE_URL . '/arquivos.php?id=5255689',
        'icone' => '📋',
        'ativo' => true,
        'tipo' => 'arquivo'
    ],

    '4923859' => [
        'nome' => 'Planner -5kg em 30 Dias Sem Mudar Seu Cardápio',
        'descricao' => 'Descubra como perder até 5kg em 30 dias adaptando seus próprios hábitos',
        'url' => BASE_URL . '/arquivos.php?id=4923859',
        'icone' => '📋',
        'ativo' => true,
        'tipo' => 'arquivo'
    ],

    '5112381' => [
        'nome' => 'Registre sua marca no INPI em 5 passos',
        'descricao' => 'Passo a passo para pesquisar e registrar Marca no INPI',
        'url' => BASE_URL . '/arquivos.php?id=5112381',
        'icone' => '📋',
        'ativo' => true,
        'tipo' => 'arquivo'
    ],

    '5410833' => [
        'nome' => 'Script de Vendas de Shows para Órgãos Públicos',
        'descricao' => 'Roteiro especializado para cantores e bandas que desejam vender shows para órgãos públicos',
        'url' => BASE_URL . '/arquivos.php?id=5410833',
        'icone' => '📋',
        'ativo' => true,
        'tipo' => 'arquivo'
    ],

    '5412054' => [
        'nome' => 'Script de Vendas de Shows',
        'descricao' => 'Roteiro especializado para cantores e bandas que desejam vender shows',
        'url' => BASE_URL . '/arquivos.php?id=5412054',
        'icone' => '📋',
        'ativo' => true,
        'tipo' => 'arquivo'
    ],

    '6294808' => [
        'nome' => 'Ansiedade Desarmada',
        'descricao' => 'Guia prático para identificar sinais, controlar crises e voltar a respirar',
        'url' => BASE_URL . '/arquivos.php?id=6294808',
        'icone' => '🧠',
        'ativo' => true,
        'tipo' => 'arquivo'
    ]
];

/**
 * Obter produtos disponíveis para o usuário
 * Inclui produtos gratuitos + produtos que o usuário possui
 */
function obterProdutosUsuario($produtosUsuario = []) {
    global $PRODUTOS_SISTEMA;
    
    $produtosDisponiveis = [];
    
    foreach ($PRODUTOS_SISTEMA as $id => $produto) {
        if (!$produto['ativo']) continue;
        
        // Incluir se é gratuito OU se o usuário possui o produto
        if (isset($produto['gratuito']) && $produto['gratuito']) {
            $produtosDisponiveis[$id] = $produto;
        } elseif (in_array($id, $produtosUsuario)) {
            $produtosDisponiveis[$id] = $produto;
        }
    }
    
    return $produtosDisponiveis;
}

/**
 * Conexão com banco de dados - MELHORADA COM SINGLETON
 */
function getDBConnection() {
    static $pdo = null;
    
    // Reutilizar conexão existente (evita múltiplas conexões)
    if ($pdo !== null) {
        return $pdo;
    }
    
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 10,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false
        ]);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Erro de conexão DB: " . $e->getMessage());
        
        // Em modo debug, mostrar erro detalhado
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            die("<h3>Erro de Conexão com Banco de Dados</h3><p>" . htmlspecialchars($e->getMessage()) . "</p>");
        }
        
        return false;
    }
}

/**
 * Log de sistema
 */
function logSistema($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logFile = __DIR__ . '/logs/sistema.log';
    
    // Criar diretório de logs se não existir
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    $logMessage = "[$timestamp] [$level] $message\n";
    @file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

/**
 * Gerar hash seguro para senhas
 */
function hashSenha($senha) {
    return password_hash($senha . SALT_SENHA, PASSWORD_ARGON2ID);
}

/**
 * Verificar senha
 */
function verificarSenha($senha, $hash) {
    return password_verify($senha . SALT_SENHA, $hash);
}

/**
 * Gerar token seguro
 */
function gerarToken($tamanho = 32) {
    return bin2hex(random_bytes($tamanho));
}

/**
 * Sanitizar entrada de dados
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validar email
 */
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Redirecionar com segurança
 */
function redirecionarPara($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * Verificar se está logado
 */
function estaLogado() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_email']);
}

/**
 * Inicializar sessão se necessário
 */
function iniciarSessao() {
    if (session_status() === PHP_SESSION_NONE) {
            // Configurações de sessão
            ini_set('session.use_strict_mode', 1);

            // Detectar HTTPS para cookie_secure
            $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
                     || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

            // Definir vida útil da sessão para 6 horas (21600 segundos)
            $lifetime = 6 * 60 * 60; // 6 horas

            // Ajustar garbage collector e cookie lifetime
            ini_set('session.gc_maxlifetime', (string)$lifetime);

            // Usar cookie params antes de iniciar a sessão
            session_name('MEMBROS_SESSION');
            session_set_cookie_params([
                'lifetime' => $lifetime,
                'path' => '/',
                'domain' => '',
                'secure' => $https ? true : false,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            session_start();
        
            // Regenerar ID da sessão periodicamente (5 minutos)
            if (!isset($_SESSION['last_regeneration'])) {
                $_SESSION['last_regeneration'] = time();
            } elseif (time() - $_SESSION['last_regeneration'] > 300) {
                session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
            }
    }
}

/**
 * Atualizar licenças expiradas
 * NOTA: Esta função deve ser chamada apenas onde necessário, não automaticamente
 */
function atualizarLicencasExpiradas() {
    $pdo = getDBConnection();
    if (!$pdo) return false;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE licencas 
            SET status_licenca = 'inativa', atualizado_em = NOW() 
            WHERE status_licenca = 'ativa' 
            AND data_expiracao IS NOT NULL 
            AND data_expiracao < CURDATE()
        ");
        
        $stmt->execute();
        $count = $stmt->rowCount();
        
        if ($count > 0) {
            logSistema("Licenças expiradas atualizadas: $count registros", 'INFO');
        }
        
        return $count;
    } catch (PDOException $e) {
        logSistema("Erro ao atualizar licenças: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

// Inicializar sistema
iniciarSessao();

// Configurações de erro baseadas no ambiente
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// ❌ REMOVIDO: Execução automática
// Chame atualizarLicencasExpiradas() apenas onde for necessário
// Exemplo: na página de login, dashboard, ou via CRON

?>