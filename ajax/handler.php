<?php
// Proteção contra acesso direto
if (!defined('SISTEMA_MEMBROS')) {
    die('Acesso negado');
}

header('Content-Type: application/json');

// Registro de debug temporário para diagnosticar falhas de cadastro via UI
function _log_debug($msg){
    $path = __DIR__ . '/../logs/processos_debug.log';
    $t = date('Y-m-d H:i:s');
    @file_put_contents($path, "[{$t}] " . $msg . "\n", FILE_APPEND | LOCK_EX);
}


try {
        // Log request basic info for debugging (only for process/event actions)
        $action_preview = $_POST['action'] ?? '';
        if (strpos($action_preview, 'process') !== false || strpos($action_preview, 'evento') !== false) {
            $summary = "ACTION=" . ($action_preview) . " USER=" . ($_SESSION['user_id'] ?? 'null') . " POST_KEYS=" . implode(',', array_keys($_POST));
            _log_debug($summary);
        }
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            _log_debug('CSRF token missing or mismatch. session=' . ($_SESSION['csrf_token'] ?? 'null') . ' post=' . ($_POST['csrf_token'] ?? 'null'));
            throw new Exception('Token de segurança inválido');
    }
    
    $action = $_POST['action'] ?? '';
    $usuario_id = $_SESSION['user_id'];
    // Helpers para processos
    require_once __DIR__ . '/../includes/processos_helper.php';
    
    switch ($action) {
        // ========== USUÁRIO (Perfil) ==========
        case 'obter_usuario':
            // Buscar perfil do usuário (se não existir, retornar defaults)
            $stmt = $pdo->prepare("SELECT * FROM usuarios_perfil WHERE usuario_id = ?");
            $stmt->execute([$usuario_id]);
            $perfil = $stmt->fetch();
            if (!$perfil) {
                $perfil = [
                    'usuario_id' => $usuario_id,
                    'email' => $_SESSION['user_email'] ?? '',
                    'nome' => $_SESSION['user_name'] ?? '',
                    'telefone' => null,
                    'oab' => null,
                    'escritorio' => null,
                    'cep' => null,
                    'endereco' => null,
                    'cidade' => null,
                    'estado' => null,
                ];
            }
            echo json_encode(['success' => true, 'usuario' => $perfil]);
            break;

        case 'atualizar_usuario':
            // Upsert de perfil do usuário
            $email = $_SESSION['user_email'] ?? '';
            $nome = $_POST['nome'] ?? null;
            $telefone = $_POST['telefone'] ?? null;
            $oab = $_POST['oab'] ?? null;
            $escritorio = $_POST['escritorio'] ?? null;
            $cep = $_POST['cep'] ?? null;
            $endereco = $_POST['endereco'] ?? null;
            $cidade = $_POST['cidade'] ?? null;
            $estado = $_POST['estado'] ?? null;

            // Garantir que a coluna CEP exista (migração leve)
            try {
                $check = $pdo->query("SHOW COLUMNS FROM usuarios_perfil LIKE 'cep'");
                if ($check->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE usuarios_perfil ADD COLUMN cep VARCHAR(9) NULL AFTER escritorio");
                }
            } catch (Exception $e) { /* ignore */ }

            $stmt = $pdo->prepare("INSERT INTO usuarios_perfil (usuario_id, email, nome, telefone, oab, escritorio, cep, endereco, cidade, estado)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE email = VALUES(email), nome = VALUES(nome), telefone = VALUES(telefone), oab = VALUES(oab), escritorio = VALUES(escritorio), cep = VALUES(cep), endereco = VALUES(endereco), cidade = VALUES(cidade), estado = VALUES(estado)");
            $stmt->execute([$usuario_id, $email, $nome, $telefone, $oab, $escritorio, $cep, $endereco, $cidade, $estado]);

            // Atualizar nome na sessão (se fornecido)
            if (!empty($nome)) {
                $_SESSION['user_name'] = $nome;
            }
            echo json_encode(['success' => true, 'message' => 'Perfil atualizado com sucesso']);
            break;
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
            $newId = (int)$pdo->lastInsertId();
            $nomeCliente = $_POST['nome'] ?? '';
            echo json_encode(['success' => true, 'message' => 'Cliente cadastrado com sucesso', 'id' => $newId, 'cliente' => ['id' => $newId, 'nome' => $nomeCliente]]);
            break;

        case 'buscar_clientes':
            $q = trim($_POST['q'] ?? '');
            $limit = 20;
            if ($q === '') {
                // Retornar os clientes ativos mais recentes
                $stmt = $pdo->prepare("SELECT id, nome FROM clientes WHERE usuario_id = ? AND status = 'ativo' ORDER BY nome LIMIT ?");
                $stmt->execute([$usuario_id, $limit]);
            } else {
                $like = '%' . str_replace(['%','_'], ['\\%','\\_'], $q) . '%';
                $stmt = $pdo->prepare("SELECT id, nome FROM clientes WHERE usuario_id = ? AND status = 'ativo' AND nome LIKE ? ORDER BY nome LIMIT ?");
                $stmt->execute([$usuario_id, $like, $limit]);
            }
            $rows = $stmt->fetchAll();
            echo json_encode(['success' => true, 'clientes' => $rows]);
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
            // Log detalhado para debug
            _log_debug('cadastrar_processo: POST=' . json_encode($_POST, JSON_UNESCAPED_UNICODE));
            
            // Validação centralizada (reuso) — retorna erros por campo
            $val = validar_processo_input($_POST, $pdo, $usuario_id);
            if (!$val['valid']){
                _log_debug('cadastrar_processo: VALIDACAO FALHOU=' . json_encode($val['errors'], JSON_UNESCAPED_UNICODE));
                // Retornar estrutura de erros para frontend consumir
                echo json_encode(['success' => false, 'errors' => $val['errors'], 'message' => 'Dados inválidos']);
                break;
            }

            $pdo->beginTransaction();
            _log_debug('cadastrar_processo: Iniciando INSERT processo');
            
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
            _log_debug('cadastrar_processo: Processo inserido ID=' . $processo_id);

            // Salvar uploads relacionados ao processo (se houver)
            if (!empty($_FILES['uploads']['name'][0])) {
                $uploadDir = __DIR__ . '/../public/uploads/processos/';
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0755, true);
                }
                $ins = $pdo->prepare("INSERT INTO uploads (usuario_id, cliente_id, processo_id, evento_id, nome, arquivo, mime, tamanho, observacoes) VALUES (?, ?, ?, NULL, ?, ?, ?, ?, NULL)");
                foreach ($_FILES['uploads']['name'] as $idx => $origName) {
                    $tmp = $_FILES['uploads']['tmp_name'][$idx] ?? null;
                    $err = $_FILES['uploads']['error'][$idx] ?? UPLOAD_ERR_NO_FILE;
                    $titulo = $_POST['upload_titulo'][$idx] ?? null;
                    if ($err === UPLOAD_ERR_OK && $tmp) {
                        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                        $allowed = ['pdf','png','jpg','jpeg','doc','docx','xls','xlsx'];
                        if (in_array($ext, $allowed)) {
                            $newName = uniqid('proc_') . '.' . $ext;
                            if (move_uploaded_file($tmp, $uploadDir . $newName)) {
                                $mime = mime_content_type($uploadDir . $newName) ?: null;
                                $tamanho = filesize($uploadDir . $newName) ?: null;
                                $ins->execute([
                                    $_SESSION['user_id'],
                                    !empty($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : null,
                                    (int)$processo_id,
                                    $titulo,
                                    $newName,
                                    $mime,
                                    $tamanho
                                ]);
                            }
                        }
                    }
                }
            }
            
            // Processar eventos se houver
            if (isset($_POST['eventos']) && is_array($_POST['eventos'])) {
                _log_debug('cadastrar_processo: Processando eventos');
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
            _log_debug('cadastrar_processo: SUCESSO processo_id=' . $processo_id);
            echo json_encode(['success' => true, 'message' => 'Processo cadastrado com sucesso', 'processo_id' => $processo_id]);
            break;

        case 'obter_processo':
            $processo_id = (int)($_POST['processo_id'] ?? 0);
            $proc = getProcessoById($pdo, $processo_id, $usuario_id);
            if (!$proc) { throw new Exception('Processo não encontrado ou sem permissão'); }
            echo json_encode(['success' => true, 'processo' => $proc]);
            break;

        case 'atualizar_processo':
            $processo_id = (int)($_POST['processo_id'] ?? 0);
            // Verificar se pertence ao usuário
            $stmt = $pdo->prepare("SELECT id FROM processos WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$processo_id, $usuario_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Processo não encontrado ou sem permissão');
            }

            $stmt = $pdo->prepare("UPDATE processos SET 
                cliente_id = ?, numero_processo = ?, tribunal = ?, vara = ?, tipo_acao = ?, parte_contraria = ?,
                valor_causa = ?, status = ?, observacoes = ?
                WHERE id = ? AND usuario_id = ?
            ");
            // Normalizar valor_causa
            $valor_causa = $_POST['valor_causa'] ?? null;
            if (is_string($valor_causa)) {
                $valor_causa = (float)str_replace(['.', ','], ['', '.'], $valor_causa);
            }
            $stmt->execute([
                (!empty($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : null),
                $_POST['numero_processo'] ?? '',
                $_POST['tribunal'] ?? '',
                $_POST['vara'] ?? null,
                $_POST['tipo_acao'] ?? null,
                $_POST['parte_contraria'] ?? null,
                $valor_causa,
                $_POST['status'] ?? 'em_andamento',
                $_POST['observacoes'] ?? null,
                $processo_id,
                $usuario_id
            ]);

            echo json_encode(['success' => true, 'message' => 'Processo atualizado com sucesso']);
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

        case 'cadastrar_evento':
            // Adicionar movimentação (evento/prazo) a um processo existente do usuário
            $processo_id = (int)($_POST['processo_id'] ?? 0);
            $descricao = trim($_POST['descricao'] ?? '');
            $data_inicial_str = trim($_POST['data_inicial'] ?? ''); // dd/mm/yyyy
            $prazo_dias = (int)($_POST['prazo_dias'] ?? 0);
            $tipo_contagem = ($_POST['tipo_contagem'] ?? 'uteis') === 'corridos' ? 'corridos' : 'uteis';
            $metodologia = ($_POST['metodologia'] ?? 'exclui_inicio') === 'inclui_inicio' ? 'inclui_inicio' : 'exclui_inicio';
            $data_final_str = trim($_POST['data_final'] ?? ''); // opcional dd/mm/yyyy

            if (!$processo_id || !$descricao || !$data_inicial_str || $prazo_dias <= 0) {
                throw new Exception('Dados insuficientes para cadastrar a movimentação');
            }

            // Verificar permissão: processo pertence ao usuário
            $stmt = $pdo->prepare("SELECT id, tribunal FROM processos WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$processo_id, $usuario_id]);
            $procRow = $stmt->fetch();
            if (!$procRow) { throw new Exception('Processo não encontrado ou sem permissão'); }

            // Converter data_inicial
            $data_inicial_obj = DateTime::createFromFormat('d/m/Y', $data_inicial_str);
            if (!$data_inicial_obj) { throw new Exception('Data inicial inválida'); }

            // Calcular data_final se não informada
            if ($data_final_str) {
                $data_final_obj = DateTime::createFromFormat('d/m/Y', $data_final_str);
                if (!$data_final_obj) { throw new Exception('Data final inválida'); }
            } else {
                $calculadora = new CalculadoraDatas($pdo);
                $tipoConst = ($tipo_contagem === 'corridos') ? CalculadoraDatas::CONTAGEM_CORRIDOS : CalculadoraDatas::CONTAGEM_UTEIS;
                $metodoConst = ($metodologia === 'inclui_inicio') ? CalculadoraDatas::METODOLOGIA_INICIO_INCLUSO : CalculadoraDatas::METODOLOGIA_INICIO_EXCLUSO;
                $resultado = $calculadora->calcularDataFinal(
                    $data_inicial_str,
                    $prazo_dias,
                    $tipoConst,
                    $metodoConst,
                    $procRow['tribunal'] ?? 'NACIONAL'
                );
                $data_final_obj = $resultado['data_final'];
            }

            // Ordem: próximo índice
            $stmtOrd = $pdo->prepare("SELECT COALESCE(MAX(ordem),0)+1 AS prox FROM eventos WHERE processo_id = ?");
            $stmtOrd->execute([$processo_id]);
            $ordem = (int)$stmtOrd->fetchColumn();

            // Inserir evento
            $stmtIns = $pdo->prepare("INSERT INTO eventos (processo_id, descricao, data_inicial, prazo_dias, tipo_contagem, metodologia, data_final, status, ordem) VALUES (?, ?, ?, ?, ?, ?, ?, 'pendente', ?)");
            $stmtIns->execute([
                $processo_id,
                $descricao,
                $data_inicial_obj->format('Y-m-d'),
                $prazo_dias,
                $tipo_contagem,
                $metodologia,
                $data_final_obj->format('Y-m-d'),
                $ordem
            ]);

            // Obter id do evento criado
            $evento_id = $pdo->lastInsertId();

            // Salvar uploads relacionados ao evento (se houver)
            if (!empty($_FILES['uploads_evento']['name'][0])) {
                $uploadDirEvt = __DIR__ . '/../public/uploads/eventos/';
                if (!is_dir($uploadDirEvt)) {
                    @mkdir($uploadDirEvt, 0755, true);
                }
                $insEvt = $pdo->prepare("INSERT INTO uploads (usuario_id, cliente_id, processo_id, evento_id, nome, arquivo, mime, tamanho, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL)");
                foreach ($_FILES['uploads_evento']['name'] as $idx => $origName) {
                    $tmp = $_FILES['uploads_evento']['tmp_name'][$idx] ?? null;
                    $err = $_FILES['uploads_evento']['error'][$idx] ?? UPLOAD_ERR_NO_FILE;
                    $titulo = $_POST['upload_titulo_evento'][$idx] ?? null;
                    if ($err === UPLOAD_ERR_OK && $tmp) {
                        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                        $allowed = ['pdf','png','jpg','jpeg','doc','docx','xls','xlsx'];
                        if (in_array($ext, $allowed)) {
                            $newName = uniqid('evt_') . '.' . $ext;
                            if (move_uploaded_file($tmp, $uploadDirEvt . $newName)) {
                                $mime = mime_content_type($uploadDirEvt . $newName) ?: null;
                                $tamanho = filesize($uploadDirEvt . $newName) ?: null;
                                $insEvt->execute([
                                    $_SESSION['user_id'],
                                    null,
                                    (int)$processo_id,
                                    (int)$evento_id,
                                    $titulo,
                                    $newName,
                                    $mime,
                                    $tamanho
                                ]);
                            }
                        }
                    }
                }
            }

            echo json_encode(['success' => true, 'message' => 'Movimentação cadastrada com sucesso']);
            break;

        case 'obter_evento':
            $evento_id = (int)($_POST['evento_id'] ?? 0);
            $ev = getEventoById($pdo, $evento_id, $usuario_id);
            if (!$ev) { throw new Exception('Evento não encontrado ou sem permissão'); }
            $di = new DateTime($ev['data_inicial']);
            $df = new DateTime($ev['data_final']);
            echo json_encode(['success' => true, 'evento' => [
                'id' => (int)$ev['id'],
                'processo_id' => (int)$ev['processo_id'],
                'descricao' => $ev['descricao'],
                'data_inicial' => $di->format('d/m/Y'),
                'prazo_dias' => (int)$ev['prazo_dias'],
                'tipo_contagem' => $ev['tipo_contagem'],
                'metodologia' => $ev['metodologia'],
                'data_final' => $df->format('d/m/Y'),
                'status' => $ev['status'],
                'tribunal' => $ev['tribunal']
            ]]);
            break;

        case 'atualizar_evento':
            $evento_id = (int)($_POST['evento_id'] ?? 0);
            // Permissão
            $stmt = $pdo->prepare("SELECT e.id, p.tribunal FROM eventos e INNER JOIN processos p ON e.processo_id = p.id WHERE e.id = ? AND p.usuario_id = ?");
            $stmt->execute([$evento_id, $usuario_id]);
            $row = $stmt->fetch();
            if (!$row) { throw new Exception('Evento não encontrado ou sem permissão'); }

            $descricao = trim($_POST['descricao'] ?? '');
            $data_inicial_str = trim($_POST['data_inicial'] ?? '');
            $prazo_dias = (int)($_POST['prazo_dias'] ?? 0);
            $tipo_contagem = ($_POST['tipo_contagem'] ?? 'uteis') === 'corridos' ? 'corridos' : 'uteis';
            $metodologia = ($_POST['metodologia'] ?? 'exclui_inicio') === 'inclui_inicio' ? 'inclui_inicio' : 'exclui_inicio';
            $data_final_str = trim($_POST['data_final'] ?? '');
            if (!$descricao || !$data_inicial_str || $prazo_dias <= 0) { throw new Exception('Dados insuficientes'); }
            $data_inicial_obj = DateTime::createFromFormat('d/m/Y', $data_inicial_str);
            if (!$data_inicial_obj) { throw new Exception('Data inicial inválida'); }
            if ($data_final_str) {
                $data_final_obj = DateTime::createFromFormat('d/m/Y', $data_final_str);
                if (!$data_final_obj) { throw new Exception('Data final inválida'); }
            } else {
                $calculadora = new CalculadoraDatas($pdo);
                $tipoConst = ($tipo_contagem === 'corridos') ? CalculadoraDatas::CONTAGEM_CORRIDOS : CalculadoraDatas::CONTAGEM_UTEIS;
                $metodoConst = ($metodologia === 'inclui_inicio') ? CalculadoraDatas::METODOLOGIA_INICIO_INCLUSO : CalculadoraDatas::METODOLOGIA_INICIO_EXCLUSO;
                $resultado = $calculadora->calcularDataFinal(
                    $data_inicial_str,
                    $prazo_dias,
                    $tipoConst,
                    $metodoConst,
                    $row['tribunal'] ?? 'NACIONAL'
                );
                $data_final_obj = $resultado['data_final'];
            }
            $stmtU = $pdo->prepare("UPDATE eventos SET descricao = ?, data_inicial = ?, prazo_dias = ?, tipo_contagem = ?, metodologia = ?, data_final = ? WHERE id = ?");
            $stmtU->execute([
                $descricao,
                $data_inicial_obj->format('Y-m-d'),
                $prazo_dias,
                $tipo_contagem,
                $metodologia,
                $data_final_obj->format('Y-m-d'),
                $evento_id
            ]);
            echo json_encode(['success' => true, 'message' => 'Evento atualizado com sucesso']);
            break;

        case 'excluir_evento':
            $evento_id = (int)($_POST['evento_id'] ?? 0);
            // Permissão
            $stmt = $pdo->prepare("SELECT e.id FROM eventos e INNER JOIN processos p ON e.processo_id = p.id WHERE e.id = ? AND p.usuario_id = ?");
            $stmt->execute([$evento_id, $usuario_id]);
            if (!$stmt->fetch()) { throw new Exception('Evento não encontrado ou sem permissão'); }
            $stmtDel = $pdo->prepare("DELETE FROM eventos WHERE id = ?");
            $stmtDel->execute([$evento_id]);
            echo json_encode(['success' => true, 'message' => 'Evento excluído']);
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

        case 'obter_resumo_processo':
            $processo_id = (int)($_POST['processo_id'] ?? 0);
            $res = getResumoProcesso($pdo, $processo_id, $usuario_id, 14);
            if ($res === false) { throw new Exception('Processo não encontrado ou sem permissão'); }
            echo json_encode(['success' => true, 'resumo' => $res['resumo'], 'proximo' => $res['proximo']]);
            break;

        case 'obter_eventos_processo':
            $processo_id = (int)($_POST['processo_id'] ?? 0);
            $eventos = getEventosPorProcesso($pdo, $processo_id, $usuario_id);
            if ($eventos === false) { throw new Exception('Processo não encontrado ou sem permissão'); }
            echo json_encode(['success' => true, 'eventos' => $eventos]);
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

        case 'obter_honorario':
            $honorario_id = (int)($_POST['honorario_id'] ?? 0);
            $stmt = $pdo->prepare("SELECT h.*, c.nome AS cliente_nome FROM honorarios h LEFT JOIN clientes c ON h.cliente_id=c.id WHERE h.id = ? AND h.usuario_id = ?");
            $stmt->execute([$honorario_id, $usuario_id]);
            $hon = $stmt->fetch();
            if (!$hon) { throw new Exception('Honorário não encontrado ou sem permissão'); }
            // Resumos
            $stmt2 = $pdo->prepare("SELECT COUNT(*) AS qtd, COALESCE(SUM(valor),0) AS soma, COALESCE(SUM(CASE WHEN status='pago' THEN valor ELSE 0 END),0) AS soma_paga FROM parcelas WHERE honorario_id = ?");
            $stmt2->execute([$honorario_id]);
            $resumos = $stmt2->fetch();
            echo json_encode(['success'=>true, 'honorario'=>$hon, 'resumos'=>$resumos]);
            break;

        case 'atualizar_honorario':
            $honorario_id = (int)($_POST['honorario_id'] ?? 0);
            // Permissão
            $stmt = $pdo->prepare("SELECT id FROM honorarios WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$honorario_id, $usuario_id]);
            if (!$stmt->fetch()) { throw new Exception('Honorário não encontrado ou sem permissão'); }
            // Por segurança, permitir editar apenas a descrição neste momento
            $descricao = $_POST['descricao'] ?? '';
            $stmt = $pdo->prepare("UPDATE honorarios SET descricao = ? WHERE id = ?");
            $stmt->execute([$descricao, $honorario_id]);
            echo json_encode(['success'=>true, 'message'=>'Honorário atualizado com sucesso']);
            break;

        case 'excluir_honorario':
            $honorario_id = (int)($_POST['honorario_id'] ?? 0);
            // Bloquear se houver parcelas pagas
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM parcelas p INNER JOIN honorarios h ON p.honorario_id=h.id WHERE h.id = ? AND h.usuario_id = ? AND p.status='pago'");
            $stmt->execute([$honorario_id, $usuario_id]);
            if ((int)$stmt->fetchColumn() > 0) {
                throw new Exception('Não é possível excluir: existem parcelas pagas');
            }
            // Excluir honorário (CASCADE remove parcelas)
            $stmt = $pdo->prepare("DELETE FROM honorarios WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$honorario_id, $usuario_id]);
            if ($stmt->rowCount() === 0) { throw new Exception('Honorário não encontrado ou sem permissão'); }
            echo json_encode(['success'=>true, 'message'=>'Honorário excluído com sucesso']);
            break;

        case 'obter_parcela':
            $parcela_id = (int)($_POST['parcela_id'] ?? 0);
            $stmt = $pdo->prepare("
                SELECT par.*, h.descricao AS honorario_descricao, c.nome AS cliente_nome
                FROM parcelas par
                INNER JOIN honorarios h ON par.honorario_id = h.id
                LEFT JOIN clientes c ON h.cliente_id = c.id
                WHERE par.id = ? AND h.usuario_id = ?
            ");
            $stmt->execute([$parcela_id, $usuario_id]);
            $parcela = $stmt->fetch();
            if (!$parcela) {
                throw new Exception('Parcela não encontrada ou sem permissão');
            }
            echo json_encode(['success' => true, 'parcela' => $parcela]);
            break;

        case 'atualizar_parcela':
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

            $valor = $_POST['valor'] ?? null;
            if (is_string($valor)) {
                $valor = (float)str_replace(['.', ','], ['', '.'], $valor);
            }
            $data_vencimento = $_POST['data_vencimento'] ?? null; // yyyy-mm-dd
            $status = $_POST['status'] ?? 'pendente';
            $data_pagamento = $_POST['data_pagamento'] ?? null;
            $observacoes = $_POST['observacoes'] ?? null;

            // Regras para data_pagamento
            if ($status === 'pago' && empty($data_pagamento)) {
                $data_pagamento = date('Y-m-d');
            }
            if ($status !== 'pago') {
                $data_pagamento = null;
            }

            $stmt = $pdo->prepare("UPDATE parcelas SET valor = ?, data_vencimento = ?, status = ?, data_pagamento = ?, observacoes = ? WHERE id = ?");
            $stmt->execute([$valor, $data_vencimento, $status, $data_pagamento, $observacoes, $parcela_id]);

            echo json_encode(['success' => true, 'message' => 'Parcela atualizada com sucesso']);
            break;

        case 'excluir_parcela':
            $parcela_id = (int)($_POST['parcela_id'] ?? 0);
            // Bloquear exclusão se já paga
            $stmt = $pdo->prepare("
                SELECT par.status FROM parcelas par
                INNER JOIN honorarios h ON par.honorario_id = h.id
                WHERE par.id = ? AND h.usuario_id = ?
            ");
            $stmt->execute([$parcela_id, $usuario_id]);
            $row = $stmt->fetch();
            if (!$row) {
                throw new Exception('Parcela não encontrada ou sem permissão');
            }
            if ($row['status'] === 'pago') {
                throw new Exception('Não é possível excluir uma parcela já paga');
            }

            $stmt = $pdo->prepare("DELETE FROM parcelas WHERE id = ?");
            $stmt->execute([$parcela_id]);
            echo json_encode(['success' => true, 'message' => 'Parcela excluída com sucesso']);
            break;
            
        default:
            throw new Exception('Ação não reconhecida');
    }
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Log exception
    _log_debug('EXCEPTION: ' . $e->getMessage() . ' TRACE: ' . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
