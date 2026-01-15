<?php
/**
 * Helpers relacionados a processos
 */
function getPrazosUrgentes(PDO $pdo, $userId, $days = 14){
    $limitDate = (new DateTime())->modify("+{$days} days")->format('Y-m-d');
    $sql = "SELECT e.*, p.numero_processo, c.nome as cliente_nome
            FROM eventos e
            INNER JOIN processos p ON e.processo_id = p.id
            LEFT JOIN clientes c ON p.cliente_id = c.id
            WHERE p.usuario_id = ?
            AND e.status = 'pendente'
            AND e.data_final <= ?
            ORDER BY e.data_final ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId, $limitDate]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getListaClientes(PDO $pdo, $userId){
    $sql = "SELECT id, nome FROM clientes WHERE usuario_id = ? AND status = 'ativo' ORDER BY nome";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getProcessoById(PDO $pdo, $processoId, $userId){
    $stmt = $pdo->prepare("SELECT * FROM processos WHERE id = ? AND usuario_id = ?");
    $stmt->execute([(int)$processoId, $userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getEventoById(PDO $pdo, $eventoId, $userId){
    $stmt = $pdo->prepare("SELECT e.*, p.tribunal, p.usuario_id FROM eventos e INNER JOIN processos p ON e.processo_id = p.id WHERE e.id = ? AND p.usuario_id = ?");
    $stmt->execute([(int)$eventoId, $userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getEventosPorProcesso(PDO $pdo, $processoId, $userId){
    // Validate ownership
    $stmt = $pdo->prepare("SELECT id FROM processos WHERE id = ? AND usuario_id = ?");
    $stmt->execute([(int)$processoId, $userId]);
    if (!$stmt->fetch()) return false;

    $stmt2 = $pdo->prepare("SELECT id, descricao, data_inicial, prazo_dias, tipo_contagem, metodologia, data_final, status
                             FROM eventos WHERE processo_id = ? ORDER BY data_final ASC, ordem ASC, id ASC");
    $stmt2->execute([(int)$processoId]);
    $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    $eventos = [];
    foreach ($rows as $ev){
        $di = new DateTime($ev['data_inicial']);
        $df = new DateTime($ev['data_final']);
        $eventos[] = [
            'id' => (int)$ev['id'],
            'descricao' => $ev['descricao'],
            'data_inicial' => $di->format('d/m/Y'),
            'prazo_dias' => (int)$ev['prazo_dias'],
            'tipo_contagem' => $ev['tipo_contagem'],
            'metodologia' => $ev['metodologia'],
            'data_final' => $df->format('d/m/Y'),
            'status' => $ev['status']
        ];
    }
    return $eventos;
}

function getResumoProcesso(PDO $pdo, $processoId, $userId, $days = 14){
    $stmt = $pdo->prepare("SELECT id FROM processos WHERE id = ? AND usuario_id = ?");
    $stmt->execute([(int)$processoId, $userId]);
    if (!$stmt->fetch()) return false;

    $stmt2 = $pdo->prepare("SELECT 
                    COUNT(*) AS total,
                    SUM(CASE WHEN status='pendente' THEN 1 ELSE 0 END) AS pendentes,
                    SUM(CASE WHEN status='cumprido' THEN 1 ELSE 0 END) AS cumpridos,
                    SUM(CASE WHEN status='pendente' AND data_final <= DATE_ADD(CURDATE(), INTERVAL ? DAY) THEN 1 ELSE 0 END) AS urgentes
                FROM eventos WHERE processo_id = ?");
    $stmt2->execute([(int)$days, (int)$processoId]);
    $res = $stmt2->fetch(PDO::FETCH_ASSOC);

    $stmt3 = $pdo->prepare("SELECT id, descricao, data_final FROM eventos WHERE processo_id = ? AND status = 'pendente' ORDER BY data_final ASC LIMIT 1");
    $stmt3->execute([(int)$processoId]);
    $prox = $stmt3->fetch(PDO::FETCH_ASSOC);
    $proximo = null;
    if ($prox){
        $hoje = new DateTime();
        $df = new DateTime($prox['data_final']);
        $diff = $hoje->diff($df);
        $dias_restantes = ($df < $hoje) ? -$diff->days : $diff->days;
        $diasSemana = ['domingo','segunda-feira','terça-feira','quarta-feira','quinta-feira','sexta-feira','sábado'];
        $dia_semana = $diasSemana[(int)$df->format('w')];
        $proximo = [
            'id' => (int)$prox['id'],
            'descricao' => $prox['descricao'],
            'data_final' => $df->format('d/m/Y'),
            'dias_restantes' => $dias_restantes,
            'dia_semana' => $dia_semana
        ];
    }

    return [
        'resumo' => [
            'total' => (int)($res['total'] ?? 0),
            'pendentes' => (int)($res['pendentes'] ?? 0),
            'cumpridos' => (int)($res['cumpridos'] ?? 0),
            'urgentes' => (int)($res['urgentes'] ?? 0),
        ],
        'proximo' => $proximo
    ];
}

/**
 * Validar dados de criação/atualização de processo
 * Retorna array: ['valid' => bool, 'errors' => [field => message]]
 * @param int|null $processo_id_editando - Se informado, ignora esse ID na verificação de duplicidade (para edição)
 */
function validar_processo_input(array $data, PDO $pdo, $usuario_id, $processo_id_editando = null){
    $errors = [];

    $numero = trim($data['numero_processo'] ?? '');
    if ($numero === '') {
        $errors['numero_processo'] = 'Número do processo é obrigatório.';
    } else {
        // Verificar se já existe um processo com este número para o mesmo usuário
        $sql = "SELECT id FROM processos WHERE numero_processo = ? AND usuario_id = ?";
        $params = [$numero, $usuario_id];
        
        // Se estiver editando, excluir o próprio processo da verificação
        if ($processo_id_editando !== null) {
            $sql .= " AND id != ?";
            $params[] = (int)$processo_id_editando;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->fetch()) {
            $errors['numero_processo'] = 'Já existe um processo cadastrado com este número.';
        }
    }

    $tribunal = trim($data['tribunal'] ?? '');
    if ($tribunal === '') {
        $errors['tribunal'] = 'Tribunal é obrigatório.';
    }

    // cliente (opcional) - se informado, deve pertencer ao usuário
    if (!empty($data['cliente_id'])){
        $cid = (int)$data['cliente_id'];
        $stmt = $pdo->prepare("SELECT id FROM clientes WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$cid, $usuario_id]);
        if (!$stmt->fetch()){
            $errors['cliente_id'] = 'Cliente inválido ou sem permissão.';
        }
    }

    // valor_causa se informado deve ser numérico
    if (isset($data['valor_causa']) && $data['valor_causa'] !== ''){
        $val = str_replace([',',' '], ['.',''], $data['valor_causa']);
        if (!is_numeric($val)) {
            $errors['valor_causa'] = 'Valor da causa inválido.';
        }
    }

    // status permitido
    $allowedStatus = ['em_andamento','suspenso','arquivado'];
    if (isset($data['status']) && $data['status'] !== '' && !in_array($data['status'], $allowedStatus)){
        $errors['status'] = 'Status do processo inválido.';
    }

    // eventos: exigir pelo menos 1 válido
    $validEventCount = 0;
    if (isset($data['eventos']) && is_array($data['eventos'])){
        foreach ($data['eventos'] as $idx => $ev){
            $desc = trim($ev['descricao'] ?? '');
            $di = trim($ev['data_inicial'] ?? '');
            $prazo = $ev['prazo_dias'] ?? '';

            $eventErr = [];
            if ($desc === '') $eventErr[] = 'descrição vazia';
            // validar data no formato dd/mm/YYYY
            $dobj = DateTime::createFromFormat('d/m/Y', $di);
            if (!$dobj || $dobj->format('d/m/Y') !== $di) $eventErr[] = 'data inicial inválida';
            if ($prazo === '' || !is_numeric($prazo) || (int)$prazo <= 0) $eventErr[] = 'prazo inválido';

            if (empty($eventErr)) {
                $validEventCount++;
            } else {
                // registrar erro por índice para feedback detalhado
                $errors["eventos.{$idx}"] = 'Evento inválido: ' . implode(', ', $eventErr);
            }
        }
    }

    if ($validEventCount === 0) {
        $errors['eventos'] = 'É necessário adicionar ao menos um evento/prazo válido para o processo.';
    }

    return ['valid' => empty($errors), 'errors' => $errors];
}
