<?php
session_name('MEMBROS_SESSION');

// Proteção do sistema de membros
define('SISTEMA_MEMBROS', true);
// Corrigir caminho para arquivos do sistema (estão dentro da pasta atual)
require_once __DIR__ . '/sistemas/config.php';
require_once __DIR__ . '/sistemas/auth.php';

// Proteger a página - ID do produto Buscador Processual
protegerPagina('5776734');

// Configurar timezone
date_default_timezone_set('America/Sao_Paulo');
setlocale(LC_TIME, 'pt_BR.UTF-8', 'pt_BR', 'portuguese');

// Gerar token CSRF se não existir
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ====================================================================
// PROCESSAR REQUISIÇÕES AJAX (quando é uma busca)
// ====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
    header('Content-Type: application/json');
    
    try {
        // Validar token CSRF
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception('Token de segurança inválido');
        }
        
        $tribunal = $_POST['tribunal'] ?? '';
        $tipo_busca = $_POST['tipo_busca'] ?? 'processo';
        $processos_texto = $_POST['processos'] ?? '';
        $nome_parte = $_POST['nome_parte'] ?? '';
        $termos_busca = $_POST['termos_busca'] ?? '';

        // Validar tipo de busca
        if ($tipo_busca === 'processo') {
            if (empty($processos_texto)) {
                throw new Exception('Informe pelo menos um número de processo');
            }
            // Separar os números de processos por linha
            $processos = array_filter(array_map('trim', explode("\n", $processos_texto)));
        } else {
            if (empty($nome_parte)) {
                throw new Exception('Informe o nome da parte');
            }
            $processos = [$nome_parte]; // Busca por nome retorna como array com um item
        }
        
        if (empty($tribunal)) {
            throw new Exception('Selecione um tribunal');
        }

        if (empty($processos)) {
            throw new Exception('Nenhum dado válido fornecido para busca');
        }

        // Limitar quantidade de processos por busca (apenas para busca por processo)
        if ($tipo_busca === 'processo' && count($processos) > 50) {
            throw new Exception('Máximo de 50 processos por busca');
        }
        
        // Incluir o buscador específico do tribunal
        $buscador_file = __DIR__ . "/buscadores/{$tribunal}.php";
        
        if (!file_exists($buscador_file)) {
            throw new Exception('Tribunal não suportado ou em desenvolvimento');
        }
        
        require_once $buscador_file;
        
        // Executar busca
        $resultados = buscarProcessos($processos, $termos_busca, $tipo_busca);
        
        echo json_encode([
            'success' => true,
            'tribunal' => $tribunal,
            'total' => count($resultados),
            'resultados' => $resultados
        ]);
        
        exit; // Importante: para não renderizar o HTML
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
}

// ====================================================================
// SE NÃO FOR POST, CONTINUA E RENDERIZA O HTML NORMALMENTE
// ====================================================================
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscador Processual</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="public/css/style.css">
    
    <style>
        /* Reset e Base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #2563eb;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
            --light-bg: #f8fafc;
            --dark-text: #1e293b;
            --border-color: #e2e8f0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
            color: var(--dark-text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 2rem 0;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 2rem 0;
        }

        /* Page Header */
        .page-header {
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 0.5rem;
        }

        /* Cards */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
        }

        .card-header {
            border-bottom: 2px solid var(--border-color);
            padding: 1.25rem;
            font-weight: 600;
            border-radius: 12px 12px 0 0 !important;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Formulários */
        .form-control, .form-select {
            border-radius: 8px;
            border: 2px solid var(--border-color);
            padding: 0.75rem;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--dark-text);
            font-size: 0.95rem;
        }

        textarea.form-control {
            resize: vertical;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }

        /* Botões */
        .btn {
            border-radius: 8px;
            padding: 0.625rem 1.25rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-primary {
            background-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .btn-lg {
            padding: 0.875rem 1.5rem;
            font-size: 1.05rem;
        }

        /* Resultados */
        .resultado-item {
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            background-color: white;
            transition: all 0.3s ease;
        }

        .resultado-item:hover {
            border-color: var(--primary-color);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
            transform: translateY(-2px);
        }

        .resultado-item.erro {
            border-left: 4px solid var(--danger-color);
            background-color: rgba(239, 68, 68, 0.05);
        }

        .resultado-item.sucesso {
            border-left: 4px solid var(--success-color);
        }

        .resultado-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--border-color);
        }

        .numero-processo {
            font-family: 'Courier New', monospace;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .situacao-badge {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .badge-ativo {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .badge-baixado {
            background-color: rgba(100, 116, 139, 0.1);
            color: var(--secondary-color);
        }

        .badge-erro {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        .movimentacoes-lista {
            margin-top: 1rem;
        }

        .movimentacao-item {
            padding: 0.75rem;
            background-color: rgba(37, 99, 235, 0.05);
            border-radius: 6px;
            margin-bottom: 0.5rem;
            border-left: 3px solid var(--primary-color);
        }

        .movimentacao-data {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 0.9rem;
        }

        .movimentacao-descricao {
            margin-top: 0.25rem;
            color: var(--dark-text);
            font-size: 0.95rem;
        }

        /* Badge */
        .badge {
            padding: 0.5rem 0.875rem;
            font-weight: 600;
            border-radius: 6px;
            font-size: 0.875rem;
        }

        /* Loading */
        .loading-spinner {
            display: inline-block;
            width: 1.5rem;
            height: 1.5rem;
            border: 3px solid rgba(37, 99, 235, 0.2);
            border-top-color: var(--primary-color);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .loading-content {
            background-color: white;
            padding: 2rem 3rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }

        .loading-content .loading-spinner {
            width: 3rem;
            height: 3rem;
            border-width: 4px;
            margin-bottom: 1rem;
        }

        /* Footer */
        .footer {
            background-color: white;
            border-top: 2px solid var(--border-color);
            padding: 1.5rem 0;
            margin-top: auto;
        }

        .footer p {
            margin: 0;
            color: var(--secondary-color);
            font-size: 0.9rem;
        }

        .footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        /* Alert */
        .alert {
            border-radius: 8px;
            border: none;
            padding: 1rem;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .page-title {
                font-size: 1.5rem;
            }
            
            .resultado-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .numero-processo {
                font-size: 0.95rem;
            }
            
            .user-info {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .card-body {
                padding: 1rem;
            }
        }

        /* Utilidades */
        .text-muted {
            color: var(--secondary-color) !important;
        }

        .small {
            font-size: 0.875rem;
        }

        .fw-semibold {
            font-weight: 600;
        }

        .opacity-25 {
            opacity: 0.25;
        }

        /* Scrollbar customizada para lista de processos */
        div[style*="overflow-y: auto"]::-webkit-scrollbar {
            width: 8px;
        }

        div[style*="overflow-y: auto"]::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.1);
            border-radius: 4px;
        }

        div[style*="overflow-y: auto"]::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 4px;
        }

        div[style*="overflow-y: auto"]::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.5);
        }
    </style>
</head>
<body>
    <?php 
    $aba_ativa = 'buscador';
    include __DIR__ . '/includes/header.php'; 
    ?>

    <!-- Container Principal -->
    <div class="container-fluid main-content">
        <div class="row">
            <div class="col-12">
                <div class="page-header mb-4">
                    <h2 class="page-title">Consulta de Processos</h2>
                    <p class="text-muted">Informe os números dos processos e selecione o tribunal para realizar a busca</p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Formulário de Busca -->
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-search me-2"></i>Parâmetros de Busca</h5>
                    </div>
                    <div class="card-body">
                        <form id="formBusca">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            
                            <div class="mb-3">
                                <label for="tribunal" class="form-label">Tribunal</label>
                                <select class="form-select" id="tribunal" name="tribunal" required>
                                    <option value="">Selecione o tribunal</option>
                                    <option value="tjgo_projudi">TJGO - Projudi</option>
                                    <!-- Outros tribunais serão adicionados aqui -->
                                </select>
                                <small class="text-muted">Mais tribunais serão adicionados em breve</small>
                            </div>

                            <div class="mb-3">
                                <label for="tipo_busca" class="form-label">Tipo de Busca</label>
                                <select class="form-select" id="tipo_busca" name="tipo_busca">
                                    <option value="processo">Por Número de Processo</option>
                                    <option value="parte">Por Nome da Parte</option>
                                </select>
                            </div>

                            <div class="mb-3" id="campo_processos">
                                <label for="processos" class="form-label">Números dos Processos</label>
                                <textarea 
                                    class="form-control" 
                                    id="processos" 
                                    name="processos" 
                                    rows="8" 
                                    placeholder="Digite um número de processo por linha&#10;Exemplo:&#10;5725767-65.2019.8.09.0051&#10;5316834-61.2025.8.09.0051"
                                ></textarea>
                                <small class="text-muted">Um número por linha</small>
                            </div>

                            <div class="mb-3" id="campo_nome_parte" style="display: none;">
                                <label for="nome_parte" class="form-label">Nome da Parte</label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="nome_parte" 
                                    name="nome_parte" 
                                    placeholder="Digite o nome do promovente ou promovido"
                                    maxlength="60"
                                >
                                <small class="text-muted">Digite o nome completo ou parcial da parte</small>
                            </div>

                            <div class="mb-3">
                                <label for="termos_busca" class="form-label">Termos para Filtrar Movimentações (Opcional)</label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="termos_busca" 
                                    name="termos_busca" 
                                    placeholder="Ex: sentença, julgamento, decisão"
                                >
                                <small class="text-muted">Deixe em branco para listar todas as movimentações. Digite os termos separados por vírgula para filtrar.</small>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-search me-2"></i>Buscar Processos
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="btnLimpar">
                                    <i class="bi bi-trash me-2"></i>Limpar
                                </button>
                            </div>
                        </form>

                        <!-- Informações da Busca -->
                        <div id="infoBusca" class="mt-3" style="display: none;">
                            <hr>
                            <div class="alert alert-info mb-0">
                                <small>
                                    <strong>Processando:</strong>
                                    <div id="progressoTexto" class="mt-2"></div>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resultados -->
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Resultados da Busca</h5>
                        <span id="totalProcessos" class="badge bg-light text-dark"></span>
                    </div>
                    <div class="card-body" id="resultados">
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-search display-1 opacity-25"></i>
                            <p class="mt-3">Nenhuma busca realizada ainda</p>
                            <p class="small">Preencha os campos ao lado e clique em "Buscar Processos"</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?= date('Y') ?> Buscador Processual - Todos os direitos reservados</p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="mb-0">Versão 1.0 Beta | <a href="mailto:contato@precifex.com">Suporte</a></p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Buscador Processual - JavaScript

        $(document).ready(function() {
            // Handler do formulário de busca
            $('#formBusca').on('submit', function(e) {
                e.preventDefault();
                realizarBusca();
            });
            
            // Botão limpar
            $('#btnLimpar').on('click', function() {
                $('#formBusca')[0].reset();
                limparResultados();
            });
        });

        /**
         * Realizar busca de processos
         */
        function realizarBusca() {
            const tribunal = $('#tribunal').val();
            const tipo_busca = $('#tipo_busca').val();
            const processos = $('#processos').val();
            const nome_parte = $('#nome_parte').val();
            const csrf_token = $('input[name="csrf_token"]').val();
            
            // Validações
            if (!tribunal) {
                mostrarErro('Selecione um tribunal');
                return;
            }
            
            if (tipo_busca === 'processo' && !processos.trim()) {
                mostrarErro('Informe pelo menos um número de processo');
                return;
            }

            if (tipo_busca === 'parte' && !nome_parte.trim()) {
                mostrarErro('Informe o nome da parte');
                return;
            }
            
            // Contar processos
            let total;
            if (tipo_busca === 'processo') {
                const lista_processos = processos.trim().split('\n').filter(p => p.trim());
                total = lista_processos.length;
            } else {
                total = 1;
            }
            
            if (total === 0) {
                mostrarErro('Nenhum número de processo válido encontrado');
                return;
            }
            
            if (total > 50) {
                mostrarErro('Máximo de 50 processos por busca');
                return;
            }
            
            // Mostrar loading
            mostrarLoading(`Buscando ${total} processo(s)...`);
            
            // Preparar dados
            const termos_busca = $('#termos_busca').val();
            const formData = new FormData();
            formData.append('tribunal', tribunal);
            formData.append('tipo_busca', tipo_busca);
            formData.append('processos', processos);
            formData.append('nome_parte', nome_parte);
            formData.append('termos_busca', termos_busca);
            formData.append('csrf_token', csrf_token);
            
            // Fazer requisição AJAX
            $.ajax({
                url: 'buscador.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                timeout: 300000, // 5 minutos
                success: function(response) {
                    ocultarLoading();
                    
                    if (response.success) {
                        exibirResultados(response);
                    } else {
                        mostrarErro(response.error || 'Erro ao realizar busca');
                    }
                },
                error: function(xhr, status, error) {
                    ocultarLoading();
                    
                    let mensagem = 'Erro ao realizar busca';
                    
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        mensagem = xhr.responseJSON.error;
                    } else if (status === 'timeout') {
                        mensagem = 'Tempo limite excedido. Tente com menos processos';
                    }
                    
                    mostrarErro(mensagem);
                }
            });
        }

        /**
         * Exibir resultados da busca
         */
        function exibirResultados(response) {
            const container = $('#resultados');
            const totalProcessos = $('#totalProcessos');
            
            // Atualizar contador
            totalProcessos.text(`${response.total} processo(s) encontrado(s)`);
            
            // Limpar container
            container.empty();
            
            if (response.total === 0) {
                container.html(`
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-search display-1 opacity-25"></i>
                        <p class="mt-3">Nenhum resultado encontrado</p>
                    </div>
                `);
                return;
            }
            
            // Adicionar cada resultado
            response.resultados.forEach(function(resultado) {
                const html = criarItemResultado(resultado);
                container.append(html);
            });
            
            mostrarSucesso(`Busca concluída! ${response.total} processo(s) encontrado(s)`);
        }

        /**
         * Criar HTML de um item de resultado
         */
        function criarItemResultado(resultado) {
            const temErro = resultado.erro !== null;
            const classeItem = temErro ? 'resultado-item erro' : 'resultado-item sucesso';
            
            // Verificar se é uma lista de processos da busca por nome
            if (resultado.tipo_especial === 'lista_processos') {
                let html = `
                    <div class="resultado-item" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;">
                        <div class="resultado-header" style="border-bottom-color: rgba(255,255,255,0.3);">
                            <div style="width: 100%;">
                                <h5 class="mb-2">
                                    <i class="bi bi-search me-2"></i>📋 Resultado da Busca por Nome
                                </h5>
                                <p class="mb-0">
                                    <i class="bi bi-check-circle me-2"></i>
                                    <strong>Encontrados: ${resultado.total_encontrados} processo(s)</strong>
                                </p>
                                <p class="mb-0 mt-1">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Clique em um processo para ver os detalhes e movimentações
                                </p>
                            </div>
                        </div>
                        <div class="mt-3">
                            <h6 class="mb-2">
                                <i class="bi bi-list-ol me-1"></i>Processos encontrados:
                            </h6>
                            <div id="lista-processos-encontrados">
                `;
                
                resultado.processos.forEach(function(processo, index) {
                    const poloAtivoTexto = processo.polo_ativo || 'Não informado';
                    const poloPassivoTexto = processo.polo_passivo || 'Não informado';
                    const dataDistribuicao = processo.data_distribuicao || 'Não informada';
                    
                    html += `
                        <div class="processo-item-lista" data-numero-parcial="${processo.numero_parcial}" 
                             style="background: rgba(255,255,255,0.15); padding: 1rem; margin-bottom: 0.75rem; border-radius: 8px; cursor: pointer; transition: all 0.3s;"
                             onmouseover="this.style.background='rgba(255,255,255,0.25)'" 
                             onmouseout="this.style.background='rgba(255,255,255,0.15)'"
                             onclick="buscarDetalhesProcesso('${processo.numero_parcial}')">
                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                <div style="flex: 1;">
                                    <div style="font-family: 'Courier New', monospace; font-size: 1.1rem; font-weight: bold; margin-bottom: 0.5rem;">
                                        <i class="bi bi-file-earmark-text me-2"></i>${processo.numero_parcial}
                                    </div>
                                    <div style="font-size: 0.9rem; opacity: 0.9;">
                                        <div><strong>Polo Ativo:</strong> ${poloAtivoTexto}</div>
                                        <div><strong>Polo Passivo:</strong> ${poloPassivoTexto}</div>
                                        <div><strong>Distribuição:</strong> ${dataDistribuicao}</div>
                                    </div>
                                </div>
                                <div>
                                    <i class="bi bi-chevron-right" style="font-size: 1.5rem;"></i>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                html += `
                            </div>
                        </div>
                    </div>
                `;
                
                return html;
            }
            
            // Verificar se é o resultado de um erro
            if (resultado.tipo_especial === 'erro') {
                return `
                    <div class="resultado-item erro">
                        <div class="resultado-header">
                            <div class="numero-processo">
                                <i class="bi bi-exclamation-triangle me-2"></i>Busca por nome
                            </div>
                            <div class="situacao-badge badge-erro">
                                Erro
                            </div>
                        </div>
                        <div class="text-muted mt-2">
                            <i class="bi bi-info-circle me-1"></i>${resultado.erro || 'Nenhum processo encontrado'}
                        </div>
                    </div>
                `;
            }
            
            // Badge da situação
            let badgeClass = 'badge-ativo';
            let situacao = resultado.situacao;
            
            if (temErro) {
                badgeClass = 'badge-erro';
                situacao = resultado.erro;
            } else if (situacao.toLowerCase().includes('baixado')) {
                badgeClass = 'badge-baixado';
            } else if (situacao.toLowerCase().includes('arquivado')) {
                badgeClass = 'badge-baixado';
            }
            
            let html = `
                <div class="${classeItem}">
                    <div class="resultado-header">
                        <div class="numero-processo">
                            <i class="bi bi-file-earmark-text me-2"></i>${resultado.numero_processo}
                        </div>
                        <div class="situacao-badge ${badgeClass}">
                            ${situacao}
                        </div>
                    </div>
            `;
            
            // Adicionar dados do processo
            if (!temErro && resultado.dados_processo) {
                const dados = resultado.dados_processo;
                
                html += `
                    <div class="dados-processo-container mt-3 mb-3" style="background: rgba(37, 99, 235, 0.05); border-left: 4px solid var(--primary-color); padding: 1rem; border-radius: 6px;">
                        <h6 class="mb-3" style="color: var(--primary-color); font-weight: 700;">
                            <i class="bi bi-info-circle me-2"></i>Dados do Processo
                        </h6>
                `;
                
                // Polo Ativo
                if (dados.polo_ativo && dados.polo_ativo.length > 0) {
                    html += `
                        <div class="mb-2">
                            <strong style="color: var(--primary-color);">Polo Ativo (Promovente):</strong><br>
                    `;
                    dados.polo_ativo.forEach(function(nome) {
                        html += `<span style="margin-left: 1rem;">• ${nome}</span><br>`;
                    });
                    html += `</div>`;
                }
                
                // Polo Passivo
                if (dados.polo_passivo && dados.polo_passivo.length > 0) {
                    html += `
                        <div class="mb-2">
                            <strong style="color: var(--primary-color);">Polo Passivo (Promovido):</strong><br>
                    `;
                    dados.polo_passivo.forEach(function(nome) {
                        html += `<span style="margin-left: 1rem;">• ${nome}</span><br>`;
                    });
                    html += `</div>`;
                }
                
                // Outras Informações
                if (dados.outras_informacoes) {
                    const info = dados.outras_informacoes;
                    
                    html += `<div class="mt-3">`;
                    
                    if (info.serventia) {
                        html += `
                            <div class="mb-1">
                                <strong>Serventia:</strong> ${info.serventia}
                            </div>
                        `;
                    }
                    
                    if (info.classe) {
                        html += `
                            <div class="mb-1">
                                <strong>Classe:</strong> ${info.classe}
                            </div>
                        `;
                    }
                    
                    if (info.assunto) {
                        html += `
                            <div class="mb-1">
                                <strong>Assunto:</strong> ${info.assunto}
                            </div>
                        `;
                    }
                    
                    if (info.valor_causa) {
                        html += `
                            <div class="mb-1">
                                <strong>Valor da Causa:</strong> R$ ${info.valor_causa}
                            </div>
                        `;
                    }
                    
                    if (info.fase) {
                        html += `
                            <div class="mb-1">
                                <strong>Fase Processual:</strong> ${info.fase}
                            </div>
                        `;
                    }
                    
                    if (info.data_distribuicao) {
                        html += `
                            <div class="mb-1">
                                <strong>Data de Distribuição:</strong> ${info.data_distribuicao}
                            </div>
                        `;
                    }
                    
                    html += `</div>`;
                }
                
                html += `</div>`; // Fecha dados-processo-container
            }
            
            // Adicionar movimentações se houver
            if (!temErro && resultado.movimentacoes && resultado.movimentacoes.length > 0) {
                html += `
                    <div class="movimentacoes-lista">
                        <h6 class="text-muted mb-2">
                            <i class="bi bi-clock-history me-1"></i>Movimentações Relevantes:
                        </h6>
                `;
                
                resultado.movimentacoes.forEach(function(mov) {
                    html += `
                        <div class="movimentacao-item">
                            <div class="movimentacao-data">
                                <i class="bi bi-calendar3 me-1"></i>${mov.data}
                            </div>
                            <div class="movimentacao-descricao">${mov.descricao}</div>
                        </div>
                    `;
                });
                
                html += `</div>`;
            } else if (!temErro) {
                html += `
                    <div class="text-muted mt-2">
                        <i class="bi bi-info-circle me-1"></i>
                        <small>Nenhuma movimentação encontrada com os termos informados</small>
                    </div>
                `;
            }
            
            html += `</div>`;
            
            return html;
        }

        /**
         * Limpar resultados
         */
        function limparResultados() {
            $('#resultados').html(`
                <div class="text-center text-muted py-5">
                    <i class="bi bi-search display-1 opacity-25"></i>
                    <p class="mt-3">Nenhuma busca realizada ainda</p>
                    <p class="small">Preencha os campos ao lado e clique em "Buscar Processos"</p>
                </div>
            `);
            $('#totalProcessos').text('');
        }

        /**
         * Mostrar loading
         */
        function mostrarLoading(mensagem) {
            const html = `
                <div class="loading-overlay" id="loadingOverlay">
                    <div class="loading-content">
                        <div class="loading-spinner"></div>
                        <h5 class="mt-3 mb-0">${mensagem}</h5>
                        <p class="text-muted small mt-2">Aguarde, isso pode levar alguns minutos...</p>
                    </div>
                </div>
            `;
            $('body').append(html);
            
            // Desabilitar formulário
            $('#formBusca :input').prop('disabled', true);
        }

        /**
         * Ocultar loading
         */
        function ocultarLoading() {
            $('#loadingOverlay').remove();
            
            // Habilitar formulário
            $('#formBusca :input').prop('disabled', false);
        }

        /**
         * Mostrar mensagem de sucesso
         */
        function mostrarSucesso(mensagem) {
            const alert = $(`
                <div class="alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index: 10000;">
                    <i class="bi bi-check-circle me-2"></i>${mensagem}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `);
            
            $('body').append(alert);
            
            setTimeout(function() {
                alert.alert('close');
            }, 5000);
        }

        /**
         * Mostrar mensagem de erro
         */
        function mostrarErro(mensagem) {
            const alert = $(`
                <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index: 10000;">
                    <i class="bi bi-exclamation-triangle me-2"></i>${mensagem}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `);
            
            $('body').append(alert);
            
            setTimeout(function() {
                alert.alert('close');
            }, 5000);
        }

        // Alternar entre busca por processo e por nome
        $('#tipo_busca').on('change', function() {
            const tipo = $(this).val();
            
            if (tipo === 'processo') {
                $('#campo_processos').show();
                $('#campo_nome_parte').hide();
                $('#processos').prop('required', true);
                $('#nome_parte').prop('required', false);
            } else {
                $('#campo_processos').hide();
                $('#campo_nome_parte').show();
                $('#processos').prop('required', false);
                $('#nome_parte').prop('required', true);
            }
        });

        /**
         * Buscar detalhes de um processo específico ao clicar
         */
        function buscarDetalhesProcesso(numeroParcial) {
            // Mostrar loading
            mostrarLoading(`Buscando detalhes do processo ${numeroParcial}...`);
            
            const csrf_token = $('input[name="csrf_token"]').val();
            const tribunal = $('#tribunal').val();
            const termos_busca = $('#termos_busca').val();
            
            const formData = new FormData();
            formData.append('tribunal', tribunal);
            formData.append('tipo_busca', 'processo');
            formData.append('processos', numeroParcial);
            formData.append('termos_busca', termos_busca);
            formData.append('csrf_token', csrf_token);
            
            $.ajax({
                url: 'buscador.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                timeout: 120000,
                success: function(response) {
                    ocultarLoading();
                    
                    if (response.success && response.resultados.length > 0) {
                        // Adicionar resultado abaixo da lista
                        const htmlResultado = criarItemResultado(response.resultados[0]);
                        $('#resultados').append(htmlResultado);
                        
                        // Scroll suave até o resultado
                        $('html, body').animate({
                            scrollTop: $('#resultados').children().last().offset().top - 100
                        }, 500);
                        
                        mostrarSucesso(`Detalhes do processo ${numeroParcial} carregados!`);
                    } else {
                        mostrarErro('Erro ao buscar detalhes do processo');
                    }
                },
                error: function(xhr, status, error) {
                    ocultarLoading();
                    mostrarErro('Erro ao buscar detalhes: ' + (xhr.responseJSON?.error || 'Tente novamente'));
                }
            });
        }
    </script>
</body>
</html>