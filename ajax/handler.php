<?php
// Proteção contra acesso direto
if (!defined('SISTEMA_MEMBROS')) {
    die('Acesso negado');
}

header('Content-Type: application/json');

try {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        throw new Exception('Token de segurança inválido');
    }
    
    $action = $_POST['action'] ?? '';
    $usuario_id = $_SESSION['user_id'];
    
    switch ($action) {
        // ========== CLIENTES ==========
        case 'cadastrar_cliente':
            $stmt = $pdo->prepare("
                INSERT INTO clientes (usuario_id, tipo, nome, cpf_cnpj, email, telefone, celular, 
                                    cep, endereco, numero, complemento, bairro, cidade, estado, 
                                    status, observacoes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $usuario_id,
                $_POST['tipo'] ?? 'pf',
                $_POST['nome'] ?? '',
                $_POST['cpf_cnpj'] ?? null,
                $_POST['email'] ?? null,
                $_POST['telefone'] ?? null,
                $_POST['celular'] ?? null,
                $_POST['cep'] ?? null,
                $_POST['endereco'] ?? null,
                $_POST['numero'] ?? null,
                $_POST['complemento'] ?? null,
                $_POST['bairro'] ?? null,
                $_POST['cidade'] ?? null,
                $_POST['estado'] ?? null,
                $_POST['status'] ?? 'ativo',
                $_POST['observacoes'] ?? null
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Cliente cadastrado com sucesso']);
            break;

        case 'obter_cliente':
            $cliente_id = (int)($_POST['cliente_id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$cliente_id, $usuario_id]);
            $cliente = $stmt->fetch();
            if (!$cliente) {
                throw new Exception('Cliente não encontrado ou sem permissão');
            }
            echo json_encode(['success' => true, 'cliente' => $cliente]);
            break;

        case 'atualizar_cliente':
            $cliente_id = (int)($_POST['cliente_id'] ?? 0);

            // Verificar se pertence ao usuário
            $stmt = $pdo->prepare("SELECT id FROM clientes WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$cliente_id, $usuario_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Cliente não encontrado ou sem permissão');
            }

            $stmt = $pdo->prepare("UPDATE clientes SET 
                tipo = ?, nome = ?, cpf_cnpj = ?, email = ?, telefone = ?, celular = ?,
                cep = ?, endereco = ?, numero = ?, complemento = ?, bairro = ?, cidade = ?, estado = ?,
                status = ?, observacoes = ?
                WHERE id = ? AND usuario_id = ?
            ");
            $stmt->execute([
                $_POST['tipo'] ?? 'pf',
                $_POST['nome'] ?? '',
                $_POST['cpf_cnpj'] ?? null,
                $_POST['email'] ?? null,
                $_POST['telefone'] ?? null,
                $_POST['celular'] ?? null,
                $_POST['cep'] ?? null,
                $_POST['endereco'] ?? null,
                $_POST['numero'] ?? null,
                $_POST['complemento'] ?? null,
                $_POST['bairro'] ?? null,
                $_POST['cidade'] ?? null,
                $_POST['estado'] ?? null,
                $_POST['status'] ?? 'ativo',
                $_POST['observacoes'] ?? null,
                $cliente_id,
                $usuario_id
            ]);

            echo json_encode(['success' => true, 'message' => 'Cliente atualizado com sucesso']);
            break;
            
        case 'excluir_cliente':
            $cliente_id = (int)($_POST['cliente_id'] ?? 0);
            
            // Verificar se pertence ao usuário
            $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$cliente_id, $usuario_id]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Cliente excluído com sucesso']);
            } else {
                throw new Exception('Cliente não encontrado ou sem permissão');
            }
            break;
            
        // ========== PROCESSOS ==========
        case 'cadastrar_processo':
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("
                INSERT INTO processos (usuario_id, cliente_id, numero_processo, tribunal, vara, 
                                     tipo_acao, parte_contraria, valor_causa, status, observacoes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $usuario_id,
                $_POST['cliente_id'] ?? null,
                $_POST['numero_processo'] ?? '',
                $_POST['tribunal'] ?? '',
                $_POST['vara'] ?? null,
                $_POST['tipo_acao'] ?? null,
                $_POST['parte_contraria'] ?? null,
                $_POST['valor_causa'] ?? null,
                $_POST['status'] ?? 'em_andamento',
                $_POST['observacoes'] ?? null
            ]);
            
            $processo_id = $pdo->lastInsertId();
            
            // Processar eventos se houver
            if (isset($_POST['eventos']) && is_array($_POST['eventos'])) {
                $calculadora = new CalculadoraDatas($pdo);
                $ordem = 1;
                
                foreach ($_POST['eventos'] as $evento) {
                    if (empty($evento['descricao']) || empty($evento['data_inicial']) || empty($evento['prazo_dias'])) {
                        continue;
                    }
                    
                    // Calcular data final
                    $tipo_contagem = ($evento['tipo_contagem'] === 'corridos') ? 
                        CalculadoraDatas::CONTAGEM_CORRIDOS : CalculadoraDatas::CONTAGEM_UTEIS;
                    $metodologia = ($evento['metodologia'] === 'inclui_inicio') ? 
                        CalculadoraDatas::METODOLOGIA_INICIO_INCLUSO : CalculadoraDatas::METODOLOGIA_INICIO_EXCLUSO;
                    
                    $resultado = $calculadora->calcularDataFinal(
                        $evento['data_inicial'],
                        (int)$evento['prazo_dias'],
                        $tipo_contagem,
                        $metodologia,
                        $_POST['tribunal'] ?? 'NACIONAL'
                    );
                    
                    $stmt_evento = $pdo->prepare("
                        INSERT INTO eventos (processo_id, descricao, data_inicial, prazo_dias, 
                                           tipo_contagem, metodologia, data_final, ordem)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $data_inicial_obj = DateTime::createFromFormat('d/m/Y', $evento['data_inicial']);
                    
                    $stmt_evento->execute([
                        $processo_id,
                        $evento['descricao'],
                        $data_inicial_obj->format('Y-m-d'),
                        (int)$evento['prazo_dias'],
                        $evento['tipo_contagem'] ?? 'uteis',
                        $evento['metodologia'] ?? 'exclui_inicio',
                        $resultado['data_final']->format('Y-m-d'),
                        $ordem++
                    ]);
                }
            }
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Processo cadastrado com sucesso', 'processo_id' => $processo_id]);
            break;
            
        case 'excluir_processo':
            $processo_id = (int)($_POST['processo_id'] ?? 0);
            
            $stmt = $pdo->prepare("DELETE FROM processos WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$processo_id, $usuario_id]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Processo excluído com sucesso']);
            } else {
                throw new Exception('Processo não encontrado ou sem permissão');
            }
            break;
            
        case 'atualizar_status_evento':
            $evento_id = (int)($_POST['evento_id'] ?? 0);
            $status = $_POST['status'] ?? 'pendente';
            
            // Verificar permissão
            $stmt = $pdo->prepare("
                SELECT e.id FROM eventos e
                INNER JOIN processos p ON e.processo_id = p.id
                WHERE e.id = ? AND p.usuario_id = ?
            ");
            $stmt->execute([$evento_id, $usuario_id]);
            
            if (!$stmt->fetch()) {
                throw new Exception('Evento não encontrado ou sem permissão');
            }
            
            $stmt = $pdo->prepare("UPDATE eventos SET status = ? WHERE id = ?");
            $stmt->execute([$status, $evento_id]);
            
            echo json_encode(['success' => true, 'message' => 'Status atualizado']);
            break;
            
        case 'calcular_data':
            $calculadora = new CalculadoraDatas($pdo);
            
            $data_inicial = $_POST['data_inicial'] ?? '';
            $prazo_dias = (int)($_POST['prazo_dias'] ?? 0);
            $tipo_contagem_str = $_POST['tipo_contagem'] ?? 'uteis';
            $metodologia_str = $_POST['metodologia'] ?? 'exclui_inicio';
            $tribunal = $_POST['tribunal'] ?? 'NACIONAL';
            
            $tipo_contagem = ($tipo_contagem_str === 'corridos') ? 
                CalculadoraDatas::CONTAGEM_CORRIDOS : CalculadoraDatas::CONTAGEM_UTEIS;
            $metodologia = ($metodologia_str === 'inclui_inicio') ? 
                CalculadoraDatas::METODOLOGIA_INICIO_INCLUSO : CalculadoraDatas::METODOLOGIA_INICIO_EXCLUSO;
            
            $resultado = $calculadora->calcularDataFinal($data_inicial, $prazo_dias, $tipo_contagem, $metodologia, $tribunal);
            
            $data_final_obj = $resultado['data_final'];
            $diasSemana = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'];
            $diaSemana = $diasSemana[(int)$data_final_obj->format('w')];
            $data_formatada = $data_final_obj->format('d/m/Y') . ' (' . $diaSemana . ')';
            
            echo json_encode(['success' => true, 'data_final' => $data_formatada]);
            break;
            
        // ========== FINANCEIRO ==========
        case 'cadastrar_honorario':
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("
                INSERT INTO honorarios (usuario_id, cliente_id, processo_id, descricao, tipo, 
                                      valor_total, numero_parcelas, valor_parcela)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $valor_total = (float)str_replace(['.', ','], ['', '.'], $_POST['valor_total']);
            $numero_parcelas = (int)($_POST['numero_parcelas'] ?? 1);
            $valor_parcela = $valor_total / $numero_parcelas;
            
            $stmt->execute([
                $usuario_id,
                $_POST['cliente_id'] ?? null,
                $_POST['processo_id'] ?? null,
                $_POST['descricao'] ?? '',
                $_POST['tipo'] ?? 'fixo',
                $valor_total,
                $numero_parcelas,
                $valor_parcela
            ]);
            
            $honorario_id = $pdo->lastInsertId();
            
            // Criar parcelas
            $data_vencimento = new DateTime($_POST['data_primeira_parcela'] ?? 'now');
            
            for ($i = 1; $i <= $numero_parcelas; $i++) {
                $stmt_parcela = $pdo->prepare("
                    INSERT INTO parcelas (honorario_id, numero_parcela, valor, data_vencimento, status)
                    VALUES (?, ?, ?, ?, 'pendente')
                ");
                
                $stmt_parcela->execute([
                    $honorario_id,
                    $i,
                    $valor_parcela,
                    $data_vencimento->format('Y-m-d')
                ]);
                
                $data_vencimento->modify('+1 month');
            }
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Honorário cadastrado com sucesso']);
            break;
            
        case 'registrar_pagamento':
            $parcela_id = (int)($_POST['parcela_id'] ?? 0);
            
            // Verificar permissão
            $stmt = $pdo->prepare("
                SELECT par.id FROM parcelas par
                INNER JOIN honorarios h ON par.honorario_id = h.id
                WHERE par.id = ? AND h.usuario_id = ?
            ");
            $stmt->execute([$parcela_id, $usuario_id]);
            
            if (!$stmt->fetch()) {
                throw new Exception('Parcela não encontrada ou sem permissão');
            }
            
            $stmt = $pdo->prepare("
                UPDATE parcelas 
                SET status = 'pago', data_pagamento = CURDATE()
                WHERE id = ?
            ");
            $stmt->execute([$parcela_id]);
            
            echo json_encode(['success' => true, 'message' => 'Pagamento registrado']);
            break;
            
        default:
            throw new Exception('Ação não reconhecida');
    }
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
