<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function answer(int $status, array $body): never {
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    answer(405, ['error' => 'Método não permitido.']);
}

$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) answer(422, ['error' => 'Envio inválido.']);

$modalidades = [
    'credito-pessoal-nao-consignado' => [
        'label' => 'Empréstimo normal',
        'serie' => 25464, 'serie_anual' => 20742,
        'conceito' => 'novas operações de crédito livre para pessoas físicas',
    ],
    'consignado-trabalhador-privado' => [
        'label' => 'Empréstimo consignado para trabalhador do setor privado',
        'serie' => 25466, 'serie_anual' => 20744,
        'conceito' => 'novas operações de crédito livre para pessoas físicas',
    ],
    'consignado-servidor-publico' => [
        'label' => 'Empréstimo consignado para servidor público',
        'serie' => 25467, 'serie_anual' => 20745,
        'conceito' => 'novas operações de crédito livre para pessoas físicas',
    ],
    'consignado-inss' => [
        'label' => 'Empréstimo consignado para aposentados e pensionistas do INSS',
        'serie' => 25468, 'serie_anual' => 20746,
        'conceito' => 'novas operações de crédito livre para pessoas físicas',
    ],
    'financiamento-veiculo' => [
        'label' => 'Financiamento para compra de veículo',
        'serie' => 25471, 'serie_anual' => 20749,
        'conceito' => 'novas operações de crédito livre para pessoas físicas',
    ],
    'credito-pessoal-total' => [
        'label' => 'Crédito pessoal total',
        'serie' => 25470, 'serie_anual' => 20748,
        'conceito' => 'novas operações de crédito livre para pessoas físicas',
    ],
    'credito-livre-pf-total' => [
        'label' => 'Todas as operações de crédito livre para pessoa física',
        'serie' => 25462, 'serie_anual' => 20740,
        'conceito' => 'novas operações de crédito livre para pessoas físicas',
    ],
];

function project_database(): ?PDO {
    $credentials = dirname(__DIR__, 2) . '/private/sos-db-credentials.php';
    if (!is_readable($credentials)) return null;
    try {
        require_once $credentials;
        return new PDO(
            'mysql:host=' . DB_SERVER . ';dbname=' . DB_BASE . ';charset=utf8mb4',
            BD_USER,
            DB_PASSWORD,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    } catch (Throwable $e) {
        error_log('SOS juros: banco indisponível: ' . $e->getMessage());
        return null;
    }
}

$modalidade = (string) ($input['modalidade'] ?? '');
$mes = (int) ($input['mes'] ?? 0);
$ano = (int) ($input['ano'] ?? 0);
$taxaMensal = $input['taxa_mensal'] ?? null;
$taxaAnual = $input['taxa_anual'] ?? null;

if (!isset($modalidades[$modalidade]) || $mes < 1 || $mes > 12 || $ano < 2000 || $ano > ((int) date('Y') + 1)) {
    answer(422, ['error' => 'Escolha uma modalidade e informe um mês e ano válidos.']);
}

$toNumber = static function ($value): ?float {
    if ($value === null || $value === '') return null;
    if (!is_int($value) && !is_float($value) && !is_string($value)) return null;
    $text = str_replace(',', '.', trim((string) $value));
    if ($text === '' || !is_numeric($text)) return null;
    return (float) $text;
};
$taxaMensal = $toNumber($taxaMensal);
$taxaAnual = $toNumber($taxaAnual);
if (($taxaMensal === null && $taxaAnual === null) || ($taxaMensal !== null && $taxaAnual !== null) || ($taxaMensal !== null && ($taxaMensal < 0 || $taxaMensal > 1000)) || ($taxaAnual !== null && ($taxaAnual < 0 || $taxaAnual > 100000))) {
    answer(422, ['error' => 'Informe exatamente uma taxa: a mensal ou a anual.']);
}

$series = (int) $modalidades[$modalidade]['serie'];
$cacheDir = __DIR__ . '/cache';
if (!is_dir($cacheDir)) @mkdir($cacheDir, 0750, true);
$cacheFile = $cacheDir . '/sgs-' . $series . '.json';
$raw = is_readable($cacheFile) && (time() - (int) @filemtime($cacheFile) < 86400)
    ? (string) @file_get_contents($cacheFile)
    : '';
if ($raw === '') {
    $url = 'https://api.bcb.gov.br/dados/serie/bcdata.sgs.' . $series . '/dados?formato=json';
    $curl = curl_init($url);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 20, CURLOPT_HTTPHEADER => ['Accept: application/json']]);
    $raw = curl_exec($curl);
    $curlError = curl_error($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);
    if (!is_string($raw) || $curlError !== '' || $status < 200 || $status >= 300) {
        answer(502, ['error' => 'Não foi possível consultar os dados do Banco Central agora. Tente novamente em instantes.']);
    }
    @file_put_contents($cacheFile, $raw, LOCK_EX);
}
$seriesData = json_decode($raw, true);
if (!is_array($seriesData)) answer(502, ['error' => 'Os dados recebidos do Banco Central não puderam ser lidos.']);

$reference = sprintf('%02d/%04d', $mes, $ano);
$averageMonthly = null;
$averageAnnual = null;
$seriesDate = null;
$annualSeriesDate = null;
$annualSeries = (int) $modalidades[$modalidade]['serie_anual'];
$database = project_database();
if ($database instanceof PDO) {
    try {
        $stmt = $database->prepare('SELECT rate_monthly, source_payload_date FROM sos_juros_bacen_rates WHERE series_id = ? AND reference_date = ? LIMIT 1');
        $stmt->execute([$series, sprintf('%04d-%02d-01', $ano, $mes)]);
        $stored = $stmt->fetch();
        if (is_array($stored)) {
            $averageMonthly = (float) $stored['rate_monthly'];
            $seriesDate = (string) $stored['source_payload_date'];
        }
    } catch (Throwable $e) {
        error_log('SOS juros: falha ao ler taxa persistida: ' . $e->getMessage());
    }
}
foreach ($seriesData as $row) {
    if ($averageMonthly !== null) break;
    if (!is_array($row) || (string) ($row['data'] ?? '') !== '01/' . $reference) continue;
    $value = $toNumber($row['valor'] ?? null);
    if ($value !== null && $value >= 0) {
        $averageMonthly = $value;
        $seriesDate = (string) $row['data'];
    }
    break;
}
if ($averageMonthly === null) {
    answer(422, ['error' => 'Não há taxa média disponível para essa modalidade nesse mês no Banco Central.']);
}

if ($database instanceof PDO) {
    try {
        $stmt = $database->prepare('SELECT rate_annual, source_payload_date FROM sos_juros_bacen_annual_rates WHERE series_id = ? AND reference_date = ? LIMIT 1');
        $stmt->execute([$annualSeries, sprintf('%04d-%02d-01', $ano, $mes)]);
        $stored = $stmt->fetch();
        if (is_array($stored)) {
            $averageAnnual = (float) $stored['rate_annual'];
            $annualSeriesDate = (string) $stored['source_payload_date'];
        }
    } catch (Throwable $e) {
        error_log('SOS juros: falha ao ler taxa anual persistida: ' . $e->getMessage());
    }
}
if ($averageAnnual === null) {
    $annualCacheFile = $cacheDir . '/sgs-annual-' . $annualSeries . '.json';
    $annualRaw = is_readable($annualCacheFile) && (time() - (int) @filemtime($annualCacheFile) < 86400)
        ? (string) @file_get_contents($annualCacheFile)
        : '';
    if ($annualRaw === '') {
        $annualUrl = 'https://api.bcb.gov.br/dados/serie/bcdata.sgs.' . $annualSeries . '/dados?formato=json';
        $curl = curl_init($annualUrl);
        curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 20, CURLOPT_HTTPHEADER => ['Accept: application/json']]);
        $annualRaw = curl_exec($curl);
        $annualError = curl_error($curl);
        $annualStatus = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);
        if (!is_string($annualRaw) || $annualError !== '' || $annualStatus < 200 || $annualStatus >= 300) {
            answer(502, ['error' => 'Não foi possível consultar a taxa anual oficial do Banco Central agora.']);
        }
        @file_put_contents($annualCacheFile, $annualRaw, LOCK_EX);
    }
    $annualData = json_decode($annualRaw, true);
    if (is_array($annualData)) foreach ($annualData as $row) {
        if (!is_array($row) || (string) ($row['data'] ?? '') !== '01/' . $reference) continue;
        $value = $toNumber($row['valor'] ?? null);
        if ($value !== null && $value >= 0) { $averageAnnual = $value; $annualSeriesDate = (string) $row['data']; }
        break;
    }
}
if ($averageAnnual === null) {
    answer(422, ['error' => 'Não há taxa anual oficial disponível para essa modalidade nesse mês no Banco Central.']);
}

$effectiveAnnual = static fn(float $monthly): float => (pow(1 + ($monthly / 100), 12) - 1) * 100;
$monthlyFromAnnual = static fn(float $annual): float => (pow(1 + ($annual / 100), 1 / 12) - 1) * 100;
$userMonthly = $taxaMensal ?? $monthlyFromAnnual((float) $taxaAnual);
$userAnnual = $taxaAnual ?? $effectiveAnnual($userMonthly);
$differencePercent = (($userMonthly / $averageMonthly) - 1) * 100;
$differenceAnnualPercent = (($userAnnual / $averageAnnual) - 1) * 100;
$amount = $toNumber($input['valor'] ?? null);
$installments = (int) ($input['parcelas'] ?? 0);
$simulation = null;
if ($amount !== null && $amount > 0 && $amount <= 10000000 && $installments >= 1 && $installments <= 480) {
    $paymentForRate = static function (float $principal, float $monthlyPercent, int $numberOfInstallments): float {
        $rate = $monthlyPercent / 100;
        if ($rate <= 0) return $principal / $numberOfInstallments;
        return $principal * $rate * pow(1 + $rate, $numberOfInstallments) / (pow(1 + $rate, $numberOfInstallments) - 1);
    };
    $contractPayment = $paymentForRate($amount, $userMonthly, $installments);
    $bcbPayment = $paymentForRate($amount, $averageMonthly, $installments);
    $simulation = [
        'parcelas' => $installments,
        'valor_contratado' => round($amount, 2),
        'valor_parcela_contrato' => round($contractPayment, 2),
        'total_contrato' => round($contractPayment * $installments, 2),
        'valor_parcela_bcb' => round($bcbPayment, 2),
        'total_bcb' => round($bcbPayment * $installments, 2),
        // Mantidos para compatibilidade com consumidores da resposta anterior.
        'valor_parcela' => round($contractPayment, 2),
        'total_estimado' => round($contractPayment * $installments, 2),
    ];
}

$label = $differencePercent <= 0 ? 'Abaixo da média' : ($differencePercent <= 30 ? 'Próxima da média' : ($differencePercent <= 50 ? 'Acima da média' : 'Diferença expressiva'));
answer(200, [
    'modalidade' => $modalidades[$modalidade]['label'],
    'referencia' => $reference,
    'taxa_contrato_mensal' => round($userMonthly, 4),
    'taxa_contrato_anual_efetiva' => round($userAnnual, 4),
    'taxa_media_bcb_mensal' => round($averageMonthly, 4),
    'taxa_media_bcb_anual' => round($averageAnnual, 4),
    'taxa_media_bcb_anual_efetiva' => round($averageAnnual, 4),
    'diferenca_percentual' => round($differencePercent, 2),
    'diferenca_percentual_mensal' => round($differencePercent, 2),
    'diferenca_percentual_anual' => round($differenceAnnualPercent, 2),
    'faixa' => $label,
    'simulacao' => $simulation,
    'metodologia' => 'Taxas médias oficiais das novas operações de crédito com recursos livres, divulgadas pelo Banco Central em séries mensal e anual correspondentes à modalidade e ao período informados. As duas séries são apresentadas separadamente; a taxa anual não é convertida da mensal.',
    'fonte' => ['title' => 'Banco Central — SGS', 'url' => 'https://www3.bcb.gov.br/sgspub/consultarvalores/telaCvsSelecionarSeries.paint?seriesRetiradas=11', 'serie_mensal' => $series, 'serie_anual' => $annualSeries, 'data_serie_mensal' => $seriesDate, 'data_serie_anual' => $annualSeriesDate],
]);
