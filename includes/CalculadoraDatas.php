<?php
// Proteção contra acesso direto
if (!defined('SISTEMA_MEMBROS')) {
    die('Acesso negado');
}

class CalculadoraDatas {
    private $pdo;
    
    const CONTAGEM_UTEIS = 1;
    const CONTAGEM_CORRIDOS = 2;
    const METODOLOGIA_INICIO_EXCLUSO = 1;
    const METODOLOGIA_INICIO_INCLUSO = 2;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    private function dataLocalToIso($data) {
        $partes = explode('/', $data);
        if (count($partes) !== 3) throw new Exception("Data inválida: $data");
        return $partes[2] . '-' . $partes[1] . '-' . $partes[0];
    }
    
    public function obterTribunais() {
        // Buscar do banco (compartilhado)
        try {
            $dsn = "mysql:host=77.37.126.7;port=3306;dbname=calculadora;charset=utf8mb4";
            $pdo_calculadora = new PDO($dsn, 'usuario', 'senha');
            $sql = "SELECT DISTINCT abrangencia FROM feriados WHERE abrangencia != 'NACIONAL' ORDER BY abrangencia";
            $stmt = $pdo_calculadora->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
    
    private function obterFeriados($dataInicial, $dataFinal, $abrangencia = 'NACIONAL') {
        // Buscar do banco (compartilhado)
        try {
            $dsn = "mysql:host=77.37.126.7;port=3306;dbname=calculadora;charset=utf8mb4";
            $pdo_calculadora = new PDO($dsn, 'usuario', 'senha');
            
            $feriados = [];
            $params = [
                ':dataInicial' => $dataInicial,
                ':dataFinal' => $dataFinal
            ];

            // Sempre obter feriados nacionais
            $sqlNacional = "SELECT * FROM feriados WHERE data BETWEEN :dataInicial AND :dataFinal AND abrangencia = 'NACIONAL'";
            $stmtNacional = $pdo_calculadora->prepare($sqlNacional);
            $stmtNacional->execute($params);
            $feriadosNacionais = $stmtNacional->fetchAll();

            foreach ($feriadosNacionais as $feriado) {
                $feriados[$feriado['data']] = $feriado;
            }

            if ($abrangencia !== 'NACIONAL') {
                $sqlEspecifico = "SELECT * FROM feriados WHERE data BETWEEN :dataInicial AND :dataFinal AND abrangencia = :abrangencia";
                $params[':abrangencia'] = $abrangencia;
                $stmtEspecifico = $pdo_calculadora->prepare($sqlEspecifico);
                $stmtEspecifico->execute($params);
                $feriadosEspecificos = $stmtEspecifico->fetchAll();

                foreach ($feriadosEspecificos as $feriado) {
                    $feriados[$feriado['data']] = $feriado; 
                }
            }
            
            $feriadosArray = array_values($feriados);
            usort($feriadosArray, function($a, $b) {
                return strtotime($a['data']) - strtotime($b['data']);
            });

            return $feriadosArray;
        } catch (PDOException $e) {
            return [];
        }
    }
    
    private function ehFimDeSemana(DateTime $data) {
        $diaSemana = (int)$data->format('w');
        return $diaSemana === 0 || $diaSemana === 6;
    }
    
    public function calcularDataFinal($dataInicial, $dias, $tipoContagem, $metodologia, $abrangencia = 'NACIONAL') {
        $dataInicialIso = $this->dataLocalToIso($dataInicial);
        $dataInicialObj = DateTime::createFromFormat('Y-m-d', $dataInicialIso);
        
        if (!$dataInicialObj || $dias <= 0) {
            throw new Exception("Parâmetros inválidos");
        }
        
        if ($tipoContagem == self::CONTAGEM_CORRIDOS) {
            return $this->calcularDataFinalCorridos($dataInicialObj, $dias, $metodologia);
        } else {
            return $this->calcularDataFinalUteis($dataInicialObj, $dias, $metodologia, $abrangencia);
        }
    }
    
    private function calcularDataFinalCorridos(DateTime $dataInicial, $dias, $metodologia) {
        $dataFinal = clone $dataInicial;
        
        if ($metodologia == self::METODOLOGIA_INICIO_INCLUSO) {
            $dataFinal->add(new DateInterval("P{$dias}D"));
            $dataFinal->sub(new DateInterval("P1D"));
        } else {
            $dataFinal->add(new DateInterval("P{$dias}D"));
        }
        
        return ['data_final' => $dataFinal];
    }
    
    private function calcularDataFinalUteis(DateTime $dataInicial, $dias, $metodologia, $abrangencia) {
        $data = clone $dataInicial;
        
        $intervaloMaximo = $dias * 3 + 30;
        $dataFinalEstimada = clone $dataInicial;
        $dataFinalEstimada->add(new DateInterval("P{$intervaloMaximo}D"));
        
        $feriados = $this->obterFeriados(
            $dataInicial->format('Y-m-d'),
            $dataFinalEstimada->format('Y-m-d'),
            $abrangencia
        );
        
        $listaFeriados = [];
        foreach ($feriados as $feriado) {
            $listaFeriados[$feriado['data']] = $feriado['descricao'];
        }
        
        $diasContados = 0;
        $currentDate = clone $dataInicial;

        if ($metodologia == self::METODOLOGIA_INICIO_INCLUSO) {
            $dataString = $currentDate->format('Y-m-d');
            $isFeriado = isset($listaFeriados[$dataString]);
            $isFimDeSemana = $this->ehFimDeSemana($currentDate);
            
            if (!$isFimDeSemana && !$isFeriado) {
                $diasContados++;
            }
        }

        while ($diasContados < $dias) {
            $currentDate->add(new DateInterval('P1D'));
            $dataString = $currentDate->format('Y-m-d');
            
            $isFeriado = isset($listaFeriados[$dataString]);
            $isFimDeSemana = $this->ehFimDeSemana($currentDate);
            
            if (!$isFimDeSemana && !$isFeriado) {
                $diasContados++;
            }
        }
        
        return ['data_final' => $currentDate];
    }
}
