<?php
// Proteção contra acesso direto
if (!defined('SISTEMA_MEMBROS')) {
    die('Acesso negado');
}

/**
 * Obter estatísticas para o dashboard
 */
function obterEstatisticas($pdo, $usuario_id) {
    $stats = [];
    
    // Total de clientes ativos
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM clientes WHERE usuario_id = ? AND status = 'ativo'");
    $stmt->execute([$usuario_id]);
    $stats['total_clientes'] = $stmt->fetch()['total'];
    
    // Total de processos em andamento
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM processos WHERE usuario_id = ? AND status = 'em_andamento'");
    $stmt->execute([$usuario_id]);
    $stats['total_processos'] = $stmt->fetch()['total'];
    
    // Prazos próximos (7 dias)
    $data_limite = date('Y-m-d', strtotime('+7 days'));
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM eventos e
        INNER JOIN processos p ON e.processo_id = p.id
        WHERE p.usuario_id = ? 
        AND e.data_final <= ? 
        AND e.status = 'pendente'
    ");
    $stmt->execute([$usuario_id, $data_limite]);
    $stats['prazos_proximos'] = $stmt->fetch()['total'];
    
    // Contas a receber (pendentes)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total, COALESCE(SUM(valor), 0) as valor_total
        FROM parcelas par
        INNER JOIN honorarios h ON par.honorario_id = h.id
        WHERE h.usuario_id = ? AND par.status = 'pendente'
    ");
    $stmt->execute([$usuario_id]);
    $receber = $stmt->fetch();
    $stats['contas_pendentes'] = $receber['total'];
    $stats['valor_receber'] = $receber['valor_total'];
    
    // Contas vencidas
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total, COALESCE(SUM(valor), 0) as valor_total
        FROM parcelas par
        INNER JOIN honorarios h ON par.honorario_id = h.id
        WHERE h.usuario_id = ? 
        AND par.status = 'pendente'
        AND par.data_vencimento < CURDATE()
    ");
    $stmt->execute([$usuario_id]);
    $vencidas = $stmt->fetch();
    $stats['contas_vencidas'] = $vencidas['total'];
    $stats['valor_vencido'] = $vencidas['valor_total'];
    
    return $stats;
}

/**
 * Formatar valor para real brasileiro
 */
function formatarMoeda($valor) {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

/**
 * Formatar data para padrão brasileiro
 */
function formatarData($data) {
    if (empty($data)) return '';
    return date('d/m/Y', strtotime($data));
}

/**
 * Formatar data e hora para padrão brasileiro
 */
function formatarDataHora($dataHora) {
    if (empty($dataHora)) return '';
    return date('d/m/Y H:i', strtotime($dataHora));
}

/**
 * Calcular status de uma parcela
 */
function calcularStatusParcela($data_vencimento, $data_pagamento) {
    if ($data_pagamento) {
        return 'pago';
    }
    
    if (strtotime($data_vencimento) < strtotime('today')) {
        return 'vencido';
    }
    
    return 'pendente';
}

/**
 * Obter classe CSS baseada no status
 */
function obterClasseStatus($status) {
    $classes = [
        'ativo' => 'success',
        'inativo' => 'secondary',
        'em_andamento' => 'primary',
        'suspenso' => 'warning',
        'arquivado' => 'secondary',
        'pendente' => 'warning',
        'cumprido' => 'success',
        'perdido' => 'danger',
        'pago' => 'success',
        'vencido' => 'danger'
    ];
    
    return $classes[$status] ?? 'secondary';
}

/**
 * Validar CPF
 */
function validarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }
    
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }
    return true;
}

/**
 * Validar CNPJ
 */
function validarCNPJ($cnpj) {
    $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
    
    if (strlen($cnpj) != 14) {
        return false;
    }
    
    // Validação simplificada
    if (preg_match('/(\d)\1{13}/', $cnpj)) {
        return false;
    }
    
    return true;
}

/**
 * Formatar CPF
 */
function formatarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
}

/**
 * Formatar CNPJ
 */
function formatarCNPJ($cnpj) {
    $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
    return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
}

/**
 * Formatar CPF ou CNPJ conforme o tamanho
 */
function formatarCpfCnpj($valor) {
    if ($valor === null) return '';
    $digitos = preg_replace('/\D/', '', (string)$valor);
    if ($digitos === '') return '';
    if (strlen($digitos) <= 11) {
        return formatarCPF($digitos);
    }
    return formatarCNPJ($digitos);
}

/**
 * Sanitizar string
 */
function sanitizar($string) {
    return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
}

/**
 * Gerar número de processo aleatório (para testes)
 */
function gerarNumeroProcesso() {
    return sprintf('%07d-%02d.%04d.%d.%02d.%04d', 
        rand(1, 9999999), 
        rand(10, 99), 
        date('Y'), 
        rand(1, 9), 
        rand(1, 99), 
        rand(1000, 9999)
    );
}

/**
 * Buscar nome do cliente por ID
 */
function buscarNomeCliente($pdo, $cliente_id) {
    if (!$cliente_id) return 'N/A';
    
    $stmt = $pdo->prepare("SELECT nome FROM clientes WHERE id = ?");
    $stmt->execute([$cliente_id]);
    $cliente = $stmt->fetch();
    
    return $cliente ? $cliente['nome'] : 'Cliente não encontrado';
}

/**
 * Contar dias úteis entre duas datas
 */
function contarDiasUteis($data_inicial, $data_final) {
    $inicio = new DateTime($data_inicial);
    $fim = new DateTime($data_final);
    $dias = 0;
    
    while ($inicio <= $fim) {
        $dia_semana = (int)$inicio->format('w');
        if ($dia_semana != 0 && $dia_semana != 6) {
            $dias++;
        }
        $inicio->modify('+1 day');
    }
    
    return $dias;
}

/**
 * Obter cor do badge baseado em dias restantes
 */
function obterCorPrazo($data_final) {
    $hoje = new DateTime();
    $final = new DateTime($data_final);
    $diff = $hoje->diff($final);
    $dias = $diff->days;
    
    if ($final < $hoje) {
        return 'danger'; // Vencido
    } elseif ($dias <= 3) {
        return 'danger'; // Muito próximo
    } elseif ($dias <= 7) {
        return 'warning'; // Próximo
    } else {
        return 'success'; // No prazo
    }
}
