<?php
// scripts/seed-start.php
// Cria registros de teste (pelo menos 10) para facilitar desenvolvimento

define('SISTEMA_MEMBROS', true);
header('Content-Type: text/plain; charset=UTF-8');

require_once __DIR__ . '/../config/database.php';

// Configuração do usuário alvo (usa admin@local.test por padrão)
$seedEmail = isset($_GET['email']) ? trim($_GET['email']) : 'admin@local.test';
$usuarioId = md5(strtolower($seedEmail));
$quantidade = isset($_GET['qtd']) ? max(1, (int)$_GET['qtd']) : 10;

// Helpers simples
function randEscolha(array $arr) { return $arr[array_rand($arr)]; }
function randNumero($min, $max) { return random_int($min, $max); }
function hoje() { return new DateTime('today'); }
function addDias(DateTime $dt, $dias) { $c = clone $dt; $c->modify("+{$dias} days"); return $c; }

try {
    $pdo = conectarBanco();
    criarTabelas($pdo);

    echo "Seed para usuario_id={$usuarioId} (email={$seedEmail})\n";

    $pdo->beginTransaction();

    // 1) Clientes
    $nomes = ['Ana','Bruno','Carla','Diego','Eduarda','Felipe','Gabriela','Hugo','Isabela','João','Karen','Lucas','Marina','Nicolas','Olivia'];
    $sobrenomes = ['Silva','Souza','Oliveira','Pereira','Lima','Ferreira','Gomes','Costa','Ribeiro','Carvalho','Almeida'];

    $stmtCliente = $pdo->prepare("INSERT INTO clientes (usuario_id, tipo, nome, cpf_cnpj, email, telefone, status, data_criacao) VALUES (?, ?, ?, ?, ?, ?, 'ativo', NOW())");

    // Evitar duplicar: contar existentes
    $countClientes = $pdo->prepare("SELECT COUNT(*) FROM clientes WHERE usuario_id = ?");
    $countClientes->execute([$usuarioId]);
    $existentesClientes = (int)$countClientes->fetchColumn();

    $criadosClientes = 0; $clienteIds = [];
    for ($i = 0; $i < $quantidade; $i++) {
        // Sempre cria pelo menos $quantidade, mas só se não houver muitos já
        if ($existentesClientes + $criadosClientes >= $quantidade) break;

        $nome = randEscolha($nomes) . ' ' . randEscolha($sobrenomes);
        $tipo = randEscolha(['pf','pj']);
        $cpfCnpj = $tipo === 'pf' ? sprintf('%011d', randNumero(10000000000, 99999999999)) : sprintf('%014d', randNumero(10000000000000, 99999999999999));
        $emailCli = strtolower(str_replace(' ', '.', $nome)) . '@teste.local';
        $tel = '(62) 9' . randNumero(1000, 9999) . '-' . randNumero(1000, 9999);
        $stmtCliente->execute([$usuarioId, $tipo, $nome, $cpfCnpj, $emailCli, $tel]);
        $clienteIds[] = (int)$pdo->lastInsertId();
        $criadosClientes++;
    }

    echo "Clientes criados: {$criadosClientes}\n";

    // 2) Processos
    $tribunais = ['TJGO','TJSP','TJMG','TRT-18','TRF1'];
    $statusProc = ['em_andamento','suspenso','arquivado'];

    $stmtProc = $pdo->prepare("INSERT INTO processos (usuario_id, cliente_id, numero_processo, tribunal, vara, tipo_acao, parte_contraria, valor_causa, status, observacoes, data_criacao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, '', NOW())");

    $criadosProc = 0; $processoIds = [];
    foreach ($clienteIds as $cliId) {
        // cria um processo por cliente até atingir $quantidade
        if ($criadosProc >= $quantidade) break;
        $numero = 'PROC-' . randNumero(100000, 999999) . '/' . randNumero(2015, 2026);
        $trib = randEscolha($tribunais);
        $vara = 'Vara ' . randNumero(1, 20);
        $tipoAcao = randEscolha(['Cobrança','Indenização','Contrato','Trabalhista','Cível']);
        $parteContraria = randEscolha(['Banco X','Empresa Y','Pessoa Z']);
        $valor = randNumero(1000, 50000) + (randNumero(0, 99) / 100);
        $status = randEscolha($statusProc);
        $stmtProc->execute([$usuarioId, $cliId, $numero, $trib, $vara, $tipoAcao, $parteContraria, $valor, $status]);
        $processoIds[] = (int)$pdo->lastInsertId();
        $criadosProc++;
    }

    echo "Processos criados: {$criadosProc}\n";

    // 3) Eventos (prazos) - 1 por processo
    $stmtEvt = $pdo->prepare("INSERT INTO eventos (processo_id, descricao, data_inicial, prazo_dias, tipo_contagem, metodologia, data_final, status, ordem, data_criacao) VALUES (?, ?, ?, ?, ?, ?, ?, 'pendente', 0, NOW())");

    $criadosEvt = 0; $hoje = hoje();
    foreach ($processoIds as $pid) {
        if ($criadosEvt >= $quantidade) break;
        $dias = randNumero(2, 25); // alguns caem na janela de 7 dias para dashboard
        $dataInicial = hoje();
        $dataFinal = addDias($dataInicial, $dias);
        $descricao = 'Prazo ' . randEscolha(['Manifestação','Recurso','Juntada','Intimação','Audiência']);
        $tipoCont = randEscolha(['uteis','corridos']);
        $metodo = randEscolha(['exclui_inicio','inclui_inicio']);
        $stmtEvt->execute([$pid, $descricao, $dataInicial->format('Y-m-d'), $dias, $tipoCont, $metodo, $dataFinal->format('Y-m-d')]);
        $criadosEvt++;
    }

    echo "Eventos criados: {$criadosEvt}\n";

    // 4) Honorários + Parcelas (recebimentos)
    $stmtHon = $pdo->prepare("INSERT INTO honorarios (usuario_id, cliente_id, processo_id, descricao, tipo, valor_total, numero_parcelas, valor_parcela, data_criacao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmtPar = $pdo->prepare("INSERT INTO parcelas (honorario_id, numero_parcela, valor, data_vencimento, data_pagamento, status, observacoes, data_criacao) VALUES (?, ?, ?, ?, NULL, 'pendente', '', NOW())");

    $criadosPar = 0; $criadosHon = 0;
    for ($i = 0; $i < min(count($clienteIds), $quantidade); $i++) {
        $cliId = $clienteIds[$i];
        $procId = $processoIds[$i] ?? null;
        $tipoHon = randEscolha(['fixo','parcelado','exito']);
        $valorTotal = randNumero(500, 5000);
        $parcelas = $tipoHon === 'parcelado' ? randNumero(2, 5) : 1;
        $valorParcela = $parcelas > 1 ? round($valorTotal / $parcelas, 2) : $valorTotal;
        $desc = 'Honorário ' . randEscolha(['Inicial','Contestação','Audiência','Recurso']);
        $stmtHon->execute([$usuarioId, $cliId, $procId, $desc, $tipoHon, $valorTotal, $parcelas, $valorParcela]);
        $honId = (int)$pdo->lastInsertId();
        $criadosHon++;
        // Criar pelo menos 1 parcela pendente com vencimento futuro
        for ($p = 1; $p <= $parcelas; $p++) {
            $venc = addDias(hoje(), randNumero(3, 20))->format('Y-m-d');
            $stmtPar->execute([$honId, $p, $valorParcela, $venc]);
            $criadosPar++;
            if ($criadosPar >= $quantidade) break; // garantir no mínimo 10
        }
        if ($criadosPar >= $quantidade) break;
    }

    echo "Honorarios criados: {$criadosHon}\n";
    echo "Parcelas criadas (pendentes): {$criadosPar}\n";

    $pdo->commit();
    echo "\nSucesso: Seed concluído.\n";
    echo "Acesse o dashboard: http://localhost/www/juridico-php/index.php?aba=dashboard\n";
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(500);
    echo 'Erro no seed: ' . $e->getMessage() . "\n";
}
