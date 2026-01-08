<?php
// Proteção contra acesso direto
if (!defined('SISTEMA_MEMBROS')) {
    die('Acesso negado');
}

/**
 * Buscar processos no TJGO Projudi
 */
function buscarProcessos($processos, $termos_busca = '', $tipo_busca = 'processo') {
    $resultados = [];
    
    foreach ($processos as $item) {
        if ($tipo_busca === 'processo') {
            $resultado = consultarProcessoPorNumero($item, $termos_busca);
        } else {
            $resultado = consultarProcessoPorNome($item, $termos_busca);
        }
        $resultados = array_merge($resultados, is_array($resultado) && isset($resultado[0]) ? $resultado : [$resultado]);
        
        // Aguardar entre requisições para não sobrecarregar
        sleep(2);
    }
    
    return $resultados;
}

/**
 * Consultar processo por número
 */
function consultarProcessoPorNumero($numero_processo, $termos_busca = '') {
    $resultado = [
        'numero_processo' => $numero_processo,
        'situacao' => 'Não disponível',
        'movimentacoes' => [],
        'erro' => null
    ];
    
    try {
        $url = "https://projudi.tjgo.jus.br/BuscaProcesso";
        
        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n" .
                           "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n",
                'content' => http_build_query([
                    'ProcessoNumero' => $numero_processo,
                    'PaginaAtual' => '2'
                ]),
                'timeout' => 30,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ];
        
        $context = stream_context_create($opts);
        $html = @file_get_contents($url, false, $context);
        
        if ($html === false) {
            throw new Exception('Erro ao acessar o site do tribunal');
        }
        
        if (stripos($html, 'Nenhum processo encontrado') !== false) {
            $resultado['erro'] = 'Processo não encontrado';
            return $resultado;
        }
        
        $resultado = extrairInformacoes($html, $numero_processo, $termos_busca);
        
    } catch (Exception $e) {
        $resultado['erro'] = $e->getMessage();
    }
    
    return $resultado;
}

/**
 * Consultar processo por nome da parte
 */
/**
 * Consultar processo por nome da parte
 */
/**
 * Consultar processo por nome da parte
 */
/**
 * Consultar processo por nome da parte
 */
function consultarProcessoPorNome($nome_parte, $termos_busca = '') {
    $resultados = [];
    
    try {
        $url = "https://projudi.tjgo.jus.br/BuscaProcesso";
        
        // Limpar o nome
        $nome_limpo = trim($nome_parte);
        
        // Usar CURL para melhor controle
        $ch = curl_init();
        
        // Dados do formulário
        $postData = http_build_query([
            'NomeParte' => $nome_limpo,
            'PaginaAtual' => '4',
            'ProcessoNumero' => '',
            'Inquerito' => '',
            'CpfCnpjParte' => '',
            'PesquisarNomeExato' => 'false',
            'g-recaptcha-response' => '' // Vazio - vamos tentar sem
        ]);
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
                'Accept-Encoding: gzip, deflate, br',
                'Content-Type: application/x-www-form-urlencoded',
                'Origin: https://projudi.tjgo.jus.br',
                'Referer: https://projudi.tjgo.jus.br/BuscaProcesso?PaginaAtual=4',
                'Connection: keep-alive',
                'Upgrade-Insecure-Requests: 1',
                'Sec-Fetch-Dest: document',
                'Sec-Fetch-Mode: navigate',
                'Sec-Fetch-Site: same-origin',
                'Sec-Fetch-User: ?1'
            ],
            CURLOPT_ENCODING => '', // Suporta gzip/deflate automaticamente
            CURLOPT_COOKIEJAR => '/tmp/projudi_cookies.txt',
            CURLOPT_COOKIEFILE => '/tmp/projudi_cookies.txt'
        ]);
        
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($html === false || !empty($error)) {
            throw new Exception('Erro ao conectar: ' . $error);
        }
        
        if ($httpCode !== 200) {
            throw new Exception('Código HTTP inválido: ' . $httpCode);
        }
        
        // DEBUG: Salvar HTML
        $debug_file = __DIR__ . '/debug_busca_nome.html';
        file_put_contents($debug_file, $html);
        
        // Verificar se retornou o formulário (proteção anti-bot)
        if (stripos($html, 'g-recaptcha-response') !== false && 
            stripos($html, 'widget-container') !== false) {
            
            return [[
                'tipo_especial' => 'erro',
                'numero_processo' => 'Busca por nome',
                'situacao' => 'Bloqueio de segurança',
                'movimentacoes' => [],
                'erro' => 'O tribunal está usando proteção anti-bot (Cloudflare Turnstile). A busca automática por nome não é possível no momento. Por favor, use a busca por número de processo.'
            ]];
        }
        
        // Verificar mensagens de erro
        if (stripos($html, 'Nenhum processo encontrado') !== false || 
            stripos($html, 'não foram encontrados') !== false) {
            return [[
                'tipo_especial' => 'erro',
                'numero_processo' => 'Busca por nome',
                'situacao' => 'Nenhum processo encontrado',
                'movimentacoes' => [],
                'erro' => 'Nenhum processo encontrado para esta parte'
            ]];
        }
        
        // Extrair lista
        $resultados = extrairListaProcessos($html, $termos_busca);
        
    } catch (Exception $e) {
        return [[
            'tipo_especial' => 'erro',
            'numero_processo' => $nome_parte,
            'situacao' => 'Erro na busca',
            'movimentacoes' => [],
            'erro' => $e->getMessage()
        ]];
    }
    
    return $resultados;
}

/**
 * Extrair lista de processos da busca por nome
 */
/**
 * Extrair lista de processos da busca por nome
 */
/**
 * Extrair lista de processos da busca por nome
 */
/**
 * Extrair lista de processos da busca por nome (SEM buscar detalhes)
 */
/**
 * Extrair lista de processos da busca por nome (SEM buscar detalhes)
 */
function extrairListaProcessos($html, $termos_busca = '') {
    $processos_encontrados = [];
    
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    libxml_clear_errors();
    
    $xpath = new DOMXPath($dom);
    
    // Buscar tabela de resultados
    $tbody = $xpath->query("//tbody[@id='tabListaProcesso']");
    
    if ($tbody->length > 0) {
        // Buscar todas as linhas que começam com "TabelaLinha"
        $linhas = $xpath->query(".//tr[starts-with(@class, 'TabelaLinha')]", $tbody->item(0));
        
        foreach ($linhas as $linha) {
            $processo_info = [];
            
            // Pegar todas as células TD da linha
            $todas_colunas = $xpath->query(".//td", $linha);
            
            if ($todas_colunas->length >= 4) {
                // Coluna 0: Número da linha (ignorar)
                // Coluna 1: Checkbox (contém ID do processo)
                // Coluna 2: Número do processo (parcial)
                // Coluna 3: Partes (Polo Ativo/Passivo)
                // Coluna 4: Data de distribuição
                
                // Extrair número parcial (coluna 2)
                $col_numero = $todas_colunas->item(2);
                if ($col_numero) {
                    $numero_parcial = trim($col_numero->textContent);
                    // Remover ícones e espaços
                    $numero_parcial = preg_replace('/\s+/', '', $numero_parcial);
                    if (!empty($numero_parcial)) {
                        $processo_info['numero_parcial'] = $numero_parcial;
                    }
                }
                
                // Extrair partes (coluna 3)
                $col_partes = $todas_colunas->item(3);
                if ($col_partes) {
                    // Buscar Polo Ativo
                    $divs = $xpath->query(".//div", $col_partes);
                    foreach ($divs as $div) {
                        $texto = trim($div->textContent);
                        
                        // Identificar se é label ou valor
                        $bold = $xpath->query(".//b", $div);
                        
                        if ($bold->length > 0) {
                            // É um label (Polo Ativo ou Polo Passivo)
                            $label = trim($bold->item(0)->textContent);
                            
                            // Pegar o próximo div que não tem <b> (é o valor)
                            $proximo_div = $div->nextSibling;
                            while ($proximo_div && $proximo_div->nodeType !== 1) {
                                $proximo_div = $proximo_div->nextSibling;
                            }
                            
                            if ($proximo_div) {
                                $valor = trim($proximo_div->textContent);
                                
                                if (stripos($label, 'Polo Ativo') !== false) {
                                    $processo_info['polo_ativo'] = $valor;
                                } elseif (stripos($label, 'Polo Passivo') !== false) {
                                    $processo_info['polo_passivo'] = $valor;
                                }
                            }
                        }
                    }
                }
                
                // Extrair data de distribuição (coluna 4)
                if ($todas_colunas->length >= 5) {
                    $col_data = $todas_colunas->item(4);
                    if ($col_data) {
                        $processo_info['data_distribuicao'] = trim($col_data->textContent);
                    }
                }
                
                // Extrair ID do processo do checkbox (coluna 1)
                $col_checkbox = $todas_colunas->item(1);
                if ($col_checkbox) {
                    $input = $xpath->query(".//input[@type='checkbox'][@name='processos']", $col_checkbox);
                    if ($input->length > 0) {
                        $processo_info['id_processo'] = $input->item(0)->getAttribute('value');
                    }
                }
            }
            
            // Só adicionar se encontrou pelo menos o número
            if (!empty($processo_info['numero_parcial'])) {
                $processos_encontrados[] = $processo_info;
            }
        }
    }
    
    $total_encontrados = count($processos_encontrados);
    
    if ($total_encontrados === 0) {
        return [[
            'tipo_especial' => 'erro',
            'numero_processo' => 'Busca por nome',
            'situacao' => 'Nenhum processo encontrado',
            'movimentacoes' => [],
            'erro' => 'Nenhum processo encontrado para esta parte'
        ]];
    }
    
    // Retornar apenas a lista (sem buscar detalhes)
    return [[
        'tipo_especial' => 'lista_processos',
        'total_encontrados' => $total_encontrados,
        'processos' => $processos_encontrados
    ]];
}

/**
 * Extrair informações do HTML
 */
/**
 * Extrair informações do HTML
 */
function extrairInformacoes($html, $numero_processo, $termos_busca = '') {
    $resultado = [
        'numero_processo' => $numero_processo,
        'situacao' => 'Não disponível',
        'dados_processo' => [], // NOVO: array para dados do processo
        'movimentacoes' => [],
        'erro' => null
    ];
    
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    libxml_clear_errors();
    
    $xpath = new DOMXPath($dom);
    
    // ===== EXTRAIR DADOS DO PROCESSO =====
    
    // Polo Ativo (Promovente)
    $polo_ativo = [];
    $fieldset_ativo = $xpath->query("//fieldset//legend[contains(text(), 'Polo Ativo')]");
    if ($fieldset_ativo->length > 0) {
        $fieldset_pai = $fieldset_ativo->item(0)->parentNode;
        $spans_nomes = $xpath->query(".//span[contains(@class, 'nomes')]", $fieldset_pai);
        
        foreach ($spans_nomes as $span) {
            $nome = trim($span->textContent);
            if (!empty($nome)) {
                $polo_ativo[] = $nome;
            }
        }
    }
    
    // Polo Passivo (Promovido)
    $polo_passivo = [];
    $fieldset_passivo = $xpath->query("//fieldset//legend[contains(text(), 'Polo Passivo')]");
    if ($fieldset_passivo->length > 0) {
        $fieldset_pai = $fieldset_passivo->item(0)->parentNode;
        $fieldsets_internos = $xpath->query(".//fieldset[@class='VisualizaDados']", $fieldset_pai);
        
        foreach ($fieldsets_internos as $fieldset_interno) {
            $spans_nomes = $xpath->query(".//span[contains(@class, 'nomes')]", $fieldset_interno);
            foreach ($spans_nomes as $span) {
                $nome = trim($span->textContent);
                if (!empty($nome)) {
                    $polo_passivo[] = $nome;
                }
            }
        }
    }
    
    // Outras Informações
    $outras_info = [];
    $fieldset_outras = $xpath->query("//fieldset//legend[contains(text(), 'Outras Informações')]");
    if ($fieldset_outras->length > 0) {
        $fieldset_pai = $fieldset_outras->item(0)->parentNode;
        
        // Serventia
        $divs = $xpath->query(".//div[contains(text(), 'Serventia')]/following-sibling::span[1]", $fieldset_pai);
        if ($divs->length > 0) {
            $outras_info['serventia'] = trim($divs->item(0)->textContent);
        }
        
        // Classe
        $divs = $xpath->query(".//div[contains(text(), 'Classe')]/following-sibling::span[1]", $fieldset_pai);
        if ($divs->length > 0) {
            $outras_info['classe'] = trim($divs->item(0)->textContent);
        }
        
        // Assunto(s)
        $divs = $xpath->query(".//div[contains(text(), 'Assunto')]/following-sibling::span[1]", $fieldset_pai);
        if ($divs->length > 0) {
            $outras_info['assunto'] = trim($divs->item(0)->textContent);
        }
        
        // Valor da Causa
        $divs = $xpath->query(".//div[contains(text(), 'Valor da Causa')]/following-sibling::span[1]", $fieldset_pai);
        if ($divs->length > 0) {
            $outras_info['valor_causa'] = trim($divs->item(0)->textContent);
        }
        
        // Fase Processual
        $divs = $xpath->query(".//div[contains(text(), 'Fase Processual')]/following-sibling::span[1]", $fieldset_pai);
        if ($divs->length > 0) {
            $outras_info['fase'] = trim($divs->item(0)->textContent);
        }
        
        // Data de Distribuição
        $divs = $xpath->query(".//div[contains(text(), 'Dt. Distribuição')]/following-sibling::span[1]", $fieldset_pai);
        if ($divs->length > 0) {
            $outras_info['data_distribuicao'] = trim($divs->item(0)->textContent);
        }
        
        // Status
        $divs = $xpath->query(".//div[contains(text(), 'Status')]/following-sibling::span[1]", $fieldset_pai);
        if ($divs->length > 0) {
            $resultado['situacao'] = trim($divs->item(0)->textContent);
        }
    }
    
    // Montar array de dados do processo
    $resultado['dados_processo'] = [
        'polo_ativo' => $polo_ativo,
        'polo_passivo' => $polo_passivo,
        'outras_informacoes' => $outras_info
    ];
    
    // ===== EXTRAIR SITUAÇÃO (mantém lógica anterior como fallback) =====
    if ($resultado['situacao'] === 'Não disponível') {
        $situacao_nodes = $xpath->query("//div[contains(text(), 'Status')]/following-sibling::span");
        if ($situacao_nodes->length > 0) {
            $resultado['situacao'] = trim($situacao_nodes->item(0)->textContent);
        }
    }
    
    // ===== EXTRAIR MOVIMENTAÇÕES (código anterior mantido) =====
    
    // Preparar termos de busca
    $termos_array = [];
    if (!empty($termos_busca)) {
        $termos_array = array_map('trim', explode(',', $termos_busca));
        $termos_array = array_filter($termos_array);
    }
    
    // Buscar movimentações
    $tbody = $xpath->query("//tbody[@id='tabListaProcesso']");
    
    if ($tbody->length > 0) {
        $linhas = $xpath->query(".//tr[contains(@class, 'filtro-entrada')]", $tbody->item(0));
        
        foreach ($linhas as $linha) {
            $colunas = $xpath->query(".//td", $linha);
            
            if ($colunas->length >= 3) {
                $td_movimentacao = $xpath->query(".//td[contains(@class, 'filtro_coluna_movimentacao')]", $linha);
                
                if ($td_movimentacao->length > 0) {
                    $movimentacao = trim($td_movimentacao->item(0)->textContent);
                } else {
                    $movimentacao = trim($colunas->item(0)->textContent);
                }
                
                $incluir = false;
                
                if (empty($termos_array)) {
                    $incluir = true;
                } else {
                    foreach ($termos_array as $termo) {
                        if (stripos($movimentacao, $termo) !== false) {
                            $incluir = true;
                            break;
                        }
                    }
                }
                
                if ($incluir) {
                    $data = trim($colunas->item(2)->textContent);
                    $resultado['movimentacoes'][] = [
                        'descricao' => $movimentacao,
                        'data' => $data
                    ];
                }
            }
        }
    }
    
    return $resultado;
}