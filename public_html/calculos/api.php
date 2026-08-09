<?php
declare(strict_types=1);

require_once __DIR__ . '/csrf.php';
sos_start_session();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

const CALCULATION_MAXIMUM = 1.0e20;

function respond(int $status, array $body): never {
    http_response_code($status);
    try {
        echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        error_log('SOS cálculos: falha ao serializar resposta: ' . $e->getMessage());
        http_response_code(500);
        echo '{"error":"Não foi possível gerar a resposta."}';
    }
    exit;
}

function bounded_result(float $value): float {
    if (!is_finite($value) || abs($value) > CALCULATION_MAXIMUM) {
        respond(422, ['error' => 'O resultado excede o limite máximo permitido (1e20).', 'code' => 'RESULT_LIMIT_EXCEEDED', 'limit' => CALCULATION_MAXIMUM]);
    }
    return $value;
}

function compound_amount(float $principal, float $ratePercent, int $periods): float {
    $base = 1.0 + ($ratePercent / 100.0);
    if (!is_finite($principal) || !is_finite($base) || $principal <= 0 || $base < 1.0 || $periods < 0) {
        respond(422, ['error' => 'Parâmetros inválidos para juros compostos.']);
    }
    $logResult = log($principal) + ($periods * log($base));
    if (!is_finite($logResult) || $logResult > log(CALCULATION_MAXIMUM)) {
        respond(422, ['error' => 'O resultado excede o limite máximo permitido (1e20).', 'code' => 'RESULT_LIMIT_EXCEEDED', 'limit' => CALCULATION_MAXIMUM]);
    }
    return bounded_result($principal * pow($base, $periods));
}

function database(): ?PDO {
    static $pdo;
    if ($pdo instanceof PDO) return $pdo;
    $credentials = dirname(__DIR__, 2) . '/private/sos-db-credentials.php';
    if (!is_readable($credentials)) return null;
    try {
        require_once $credentials;
        return $pdo = new PDO(
            'mysql:host=' . DB_SERVER . ';dbname=' . DB_BASE . ';charset=utf8mb4',
            BD_USER,
            DB_PASSWORD,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    } catch (Throwable $e) {
        error_log('SOS cálculos: banco indisponível: ' . $e->getMessage());
        return null;
    }
}

function number_value(mixed $value): ?float {
    if ($value === null || $value === '') return null;
    if (is_int($value) || is_float($value)) return is_finite((float) $value) ? (float) $value : null;
    if (!is_string($value)) return null;
    $text = trim((string) $value);
    if (preg_match('/^-?\d{1,3}(?:\.\d{3})*,\d+$/', $text) === 1) {
        $text = str_replace('.', '', $text);
        $text = str_replace(',', '.', $text);
    } elseif (preg_match('/^-?\d+,\d+$/', $text) === 1) {
        $text = str_replace(',', '.', $text);
    } elseif (preg_match('/^-?\d+(?:\.\d+)?$/', $text) !== 1) {
        return null;
    }
    $number = (float) $text;
    return is_finite($number) ? $number : null;
}

function optional_number(array $input, string $key, float $default = 0.0): ?float {
    $raw = $input[$key] ?? null;
    if ($raw === null || $raw === '') return $default;
    return number_value($raw);
}

function integer_value(mixed $value): ?int {
    if (is_int($value)) return $value;
    if (is_float($value)) return is_finite($value) && floor($value) === $value ? (int) $value : null;
    if (is_string($value) && preg_match('/^-?\d+$/', trim($value)) === 1) return (int) trim($value);
    return null;
}

function valid_month(string $value): bool {
    return preg_match('/^(19\d{2}|20\d{2}|2100)-(0[1-9]|1[0-2])$/', $value) === 1;
}

function valid_date(string $value): bool {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date instanceof DateTimeImmutable && $date->format('Y-m-d') === $value && (int) $date->format('Y') >= 1900 && (int) $date->format('Y') <= 2100;
}

function month_label(string $month): string {
    $date = DateTimeImmutable::createFromFormat('!Y-m', $month);
    if (!$date) return $month;
    $names = [1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril', 5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto', 9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'];
    return $names[(int) $date->format('n')] . ' de ' . $date->format('Y');
}

function expected_months(string $start, string $end): array {
    $months = [];
    $cursor = new DateTimeImmutable($start . '-01');
    $last = new DateTimeImmutable($end . '-01');
    while ($cursor <= $last) {
        $months[] = $cursor->format('Y-m');
        $cursor = $cursor->modify('+1 month');
    }
    return $months;
}

function complete_monthly_rows(array $rows, string $start, string $end): array {
    $expected = expected_months($start, $end);
    $expectedKeys = array_fill_keys($expected, true);
    $byMonth = [];
    foreach ($rows as $row) {
        if (!is_array($row) || !is_string($row['date'] ?? null) || !isset($row['value'])) return [];
        $month = substr($row['date'], 0, 7);
        if (!isset($expectedKeys[$month]) || isset($byMonth[$month])) return [];
        $value = (float) $row['value'];
        if (!is_finite($value) || abs($value) > 10000) return [];
        $byMonth[$month] = $row;
    }
    if (count($byMonth) !== count($expected)) return [];
    return array_map(static fn(string $month): array => $byMonth[$month], $expected);
}

function series_definitions(): array {
    return [
        'ipca' => ['name' => 'IPCA', 'code' => 433, 'unit' => '% ao mês'],
        'inpc' => ['name' => 'INPC', 'code' => 188, 'unit' => '% ao mês'],
        'igpm' => ['name' => 'IGP-M', 'code' => 189, 'unit' => '% ao mês'],
        'igpdi' => ['name' => 'IGP-DI', 'code' => 190, 'unit' => '% ao mês'],
        'tr' => ['name' => 'TR', 'code' => 7811, 'unit' => '% ao mês'],
        'selic' => ['name' => 'Selic', 'code' => 4390, 'unit' => '% ao mês'],
    ];
}

function source_catalog(): array {
    $rows = [];
    $pdo = database();
    if ($pdo instanceof PDO) {
        try {
            $query = $pdo->query('SELECT s.slug, s.name, s.unit, s.periodicity, s.last_period, src.name AS source_name, src.source_url, MAX(o.collected_at) AS collected_at FROM sos_calculos_series s JOIN sos_calculos_sources src ON src.id=s.source_id LEFT JOIN sos_calculos_observations o ON o.series_id=s.id GROUP BY s.id ORDER BY s.name');
            foreach ($query ?: [] as $row) {
                $rows[] = [
                    'slug' => $row['slug'], 'name' => $row['name'], 'unit' => $row['unit'],
                    'periodicity' => $row['periodicity'], 'last_period' => $row['last_period'],
                    'source' => $row['source_name'], 'source_url' => $row['source_url'],
                    'collected_at' => $row['collected_at'],
                ];
            }
        } catch (Throwable $e) {
            error_log('SOS cálculos: catálogo indisponível: ' . $e->getMessage());
        }
    }
    if (!$rows) {
        foreach (series_definitions() as $slug => $definition) {
            $rows[] = ['slug' => $slug, 'name' => $definition['name'], 'unit' => $definition['unit'], 'periodicity' => 'monthly', 'last_period' => null, 'source' => 'Banco Central do Brasil — SGS', 'source_url' => 'https://www3.bcb.gov.br/sgspub/', 'collected_at' => null];
        }
    }
    return $rows;
}

function load_series(string $slug, string $start, string $end): array {
    $definitions = series_definitions();
    if (!isset($definitions[$slug])) throw new InvalidArgumentException('Índice não disponível.');
    $startDate = $start . '-01';
    $endDate = (new DateTimeImmutable($end . '-01'))->modify('last day of this month')->format('Y-m-d');
    $pdo = database();
    if ($pdo instanceof PDO) {
        try {
            $stmt = $pdo->prepare('SELECT o.reference_date, o.value FROM sos_calculos_observations o JOIN sos_calculos_series s ON s.id=o.series_id WHERE s.slug=? AND o.reference_date BETWEEN ? AND ? AND o.status="published" ORDER BY o.reference_date');
            $stmt->execute([$slug, $startDate, $endDate]);
            $rows = $stmt->fetchAll();
            $cached = array_map(static fn(array $row): array => ['date' => $row['reference_date'], 'value' => (float) $row['value'], 'source' => 'Banco Central do Brasil — SGS'], $rows);
            $cached = complete_monthly_rows($cached, $start, $end);
            if ($cached) return $cached;
        } catch (Throwable $e) {
            error_log('SOS cálculos: falha ao ler série persistida: ' . $e->getMessage());
        }
    }
    $url = 'https://api.bcb.gov.br/dados/serie/bcdata.sgs.' . $definitions[$slug]['code'] . '/dados?formato=json&dataInicial=' . rawurlencode((new DateTimeImmutable($startDate))->format('d/m/Y')) . '&dataFinal=' . rawurlencode((new DateTimeImmutable($endDate))->format('d/m/Y'));
    $curl = curl_init($url);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 20, CURLOPT_HTTPHEADER => ['Accept: application/json']]);
    $raw = curl_exec($curl);
    $error = curl_error($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);
    if (!is_string($raw) || $error !== '' || $status < 200 || $status >= 300) throw new RuntimeException('Não foi possível consultar o índice oficial agora.');
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) throw new RuntimeException('A fonte retornou dados inválidos.');
    $rows = [];
    foreach ($decoded as $row) {
        if (!is_array($row) || !preg_match('~^(\d{2})/(\d{2})/(\d{4})$~', (string) ($row['data'] ?? ''), $m)) continue;
        $date = sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
        if ($date < $startDate || $date > $endDate) continue;
        $value = number_value($row['valor'] ?? null);
        if ($value !== null) $rows[] = ['date' => $date, 'value' => $value, 'source' => 'Banco Central do Brasil — SGS'];
    }
    return complete_monthly_rows($rows, $start, $end);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (($_GET['action'] ?? 'catalog') === 'catalog') respond(200, ['series' => source_catalog(), 'source' => 'Banco Central do Brasil — SGS']);
    respond(404, ['error' => 'Recurso não encontrado.']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(405, ['error' => 'Método não permitido.']);
$contentType = strtolower(trim(explode(';', (string) ($_SERVER['CONTENT_TYPE'] ?? ''))[0]));
if ($contentType !== 'application/json') respond(415, ['error' => 'O conteúdo deve ser enviado como JSON.']);
if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 16384) respond(413, ['error' => 'O envio excede o limite permitido.']);
$rawInput = (string) file_get_contents('php://input', false, null, 0, 16385);
if (strlen($rawInput) > 16384) respond(413, ['error' => 'O envio excede o limite permitido.']);
$input = json_decode($rawInput, true);
if (!is_array($input)) respond(422, ['error' => 'Envio inválido.']);
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($input['csrf_token'] ?? null);
if (!is_string($csrfToken) || !sos_validate_csrf($csrfToken)) {
    respond(403, ['error' => 'Token CSRF ausente ou inválido.', 'code' => 'CSRF_INVALID']);
}
session_write_close();
$action = is_string($input['action'] ?? null) ? $input['action'] : '';

try {
    if ($action === 'update_value') {
        $slug = is_string($input['indice'] ?? null) ? $input['indice'] : '';
        $value = number_value($input['valor'] ?? null);
        $start = is_string($input['inicio'] ?? null) ? $input['inicio'] : '';
        $end = is_string($input['fim'] ?? null) ? $input['fim'] : '';
        $interest = optional_number($input, 'juros_mensais');
        if ($value === null || $value <= 0 || $value > 100000000000 || !valid_month($start) || !valid_month($end) || $start > $end || $interest === null || $interest < 0 || $interest > 100) respond(422, ['error' => 'Confira valor, índice, datas e juros informados.']);
        $expected = count(expected_months($start, $end));
        if ($expected > 1200) respond(422, ['error' => 'O período máximo permitido é de 1.200 meses.']);
        $rows = load_series($slug, $start, $end);
        if (count($rows) < $expected) respond(422, ['error' => 'A fonte ainda não possui todos os meses escolhidos para esse índice.']);
        $indexFactor = 1.0;
        foreach ($rows as $row) { $indexFactor = bounded_result($indexFactor * (1 + ($row['value'] / 100))); }
        $interestFactor = compound_amount(1.0, $interest, count($rows));
        $factor = bounded_result($indexFactor * $interestFactor);
        $corrected = bounded_result($value * $factor);
        respond(200, ['type' => 'update_value', 'valor_original' => round($value, 2), 'valor_atualizado' => round($corrected, 2), 'fator' => round($factor, 8), 'variacao_indice' => round(($indexFactor - 1) * 100, 4), 'juros_mensais' => $interest, 'meses' => count($rows), 'indice' => series_definitions()[$slug]['name'], 'periodo' => month_label($start) . ' a ' . month_label($end), 'fonte' => 'Banco Central do Brasil — SGS', 'fonte_url' => 'https://www3.bcb.gov.br/sgspub/']);
    }

    if ($action === 'boleto') {
        $value = number_value($input['valor'] ?? null);
        $due = is_string($input['vencimento'] ?? null) ? $input['vencimento'] : '';
        $payment = is_string($input['pagamento'] ?? null) ? $input['pagamento'] : '';
        $fine = optional_number($input, 'multa');
        $daily = optional_number($input, 'juros_dia');
        if ($value === null || $value <= 0 || $value > 100000000 || !valid_date($due) || !valid_date($payment) || $payment < $due || $fine === null || $fine < 0 || $fine > 100 || $daily === null || $daily < 0 || $daily > 10) respond(422, ['error' => 'Confira valor, datas, multa e juros do boleto.']);
        $days = (new DateTimeImmutable($due))->diff(new DateTimeImmutable($payment))->days;
        $fineValue = $days > 0 ? bounded_result($value * ($fine / 100)) : 0.0;
        $interestValue = bounded_result($value * ($daily / 100) * $days);
        $total = bounded_result($value + $fineValue + $interestValue);
        respond(200, ['type' => 'boleto', 'valor_original' => round($value, 2), 'dias_atraso' => $days, 'multa' => round($fineValue, 2), 'juros' => round($interestValue, 2), 'total' => round($total, 2), 'observacao' => 'A multa só é aplicada após o vencimento e os juros simples diários incidem sobre o valor original. Confira se os percentuais são permitidos no contrato e na lei aplicável.']);
    }

    if ($action === 'interest') {
        $principal = number_value($input['valor'] ?? null);
        $rate = number_value($input['taxa'] ?? null);
        $periods = integer_value($input['periodos'] ?? null);
        $mode = is_string($input['modo'] ?? null) ? $input['modo'] : 'compound';
        if ($principal === null || $principal <= 0 || $principal > CALCULATION_MAXIMUM || $rate === null || $rate < 0 || $rate > 1000 || $periods === null || $periods < 1 || $periods > 1200 || !in_array($mode, ['simple', 'compound'], true)) respond(422, ['error' => 'Confira valor, taxa e número de períodos.']);
        $amount = $mode === 'simple' ? bounded_result($principal * (1 + ($rate / 100) * $periods)) : compound_amount($principal, $rate, $periods);
        $interest = bounded_result($amount - $principal);
        respond(200, ['type' => 'interest', 'principal' => round($principal, 2), 'juros' => round($interest, 2), 'montante' => round($amount, 2), 'taxa' => $rate, 'periodos' => $periods, 'modo' => $mode === 'simple' ? 'simples' : 'compostos']);
    }

    if ($action === 'loan') {
        $principal = number_value($input['valor'] ?? null);
        $rate = number_value($input['taxa'] ?? null);
        $periods = integer_value($input['parcelas'] ?? null);
        $system = is_string($input['sistema'] ?? null) ? $input['sistema'] : 'price';
        if ($principal === null || $principal <= 0 || $principal > 100000000 || $rate === null || $rate < 0 || $rate > 100 || $periods === null || $periods < 1 || $periods > 480 || !in_array($system, ['price', 'sac'], true)) respond(422, ['error' => 'Confira valor, taxa, parcelas e sistema de amortização.']);
        $monthly = $rate / 100;
        $total = 0.0;
        $interestTotal = 0.0;
        $schedule = [];
        $payment = $principal / $periods;
        if ($system === 'price' && $monthly > 1.0e-12) {
            $denominator = -expm1(-$periods * log1p($monthly));
            if (!is_finite($denominator) || $denominator <= 0) respond(422, ['error' => 'Não foi possível calcular as parcelas com essa taxa.']);
            $payment = bounded_result($principal * $monthly / $denominator);
        }
        $amort = $principal / $periods;
        for ($n = 1; $n <= $periods; $n++) {
            $remaining = $periods - $n + 1;
            if ($system === 'price') {
                if ($monthly > 1.0e-12) {
                    $logFactor = log1p($monthly);
                    $amortization = bounded_result($payment * exp(-$remaining * $logFactor));
                    $interest = bounded_result($payment - $amortization);
                    $balance = $remaining === 1
                        ? 0.0
                        : bounded_result($payment * -expm1(-($remaining - 1) * $logFactor) / $monthly);
                } else {
                    $amortization = $amort;
                    $interest = 0.0;
                    $balance = bounded_result($principal * ($remaining - 1) / $periods);
                }
                $installment = $payment;
            } else {
                $balanceBefore = bounded_result($principal * $remaining / $periods);
                $amortization = $amort;
                $interest = bounded_result($balanceBefore * $monthly);
                $installment = bounded_result($amortization + $interest);
                $balance = bounded_result($principal * ($remaining - 1) / $periods);
            }
            $total = bounded_result($total + $installment); $interestTotal = bounded_result($interestTotal + $interest);
            if ($n <= 12 || $n === $periods) $schedule[] = ['parcela' => $n, 'valor' => round($installment, 2), 'amortizacao' => round($amortization, 2), 'juros' => round($interest, 2), 'saldo' => round($balance, 2)];
        }
        respond(200, ['type' => 'loan', 'sistema' => strtoupper($system), 'valor' => round($principal, 2), 'parcelas' => $periods, 'taxa_mensal' => $rate, 'primeira_parcela' => $schedule[0]['valor'], 'total' => round($total, 2), 'juros_total' => round($interestTotal, 2), 'schedule' => $schedule]);
    }

    if ($action === 'cash_vs_installments') {
        $cash = number_value($input['vista'] ?? null);
        $entry = optional_number($input, 'entrada');
        $installment = number_value($input['parcela'] ?? null);
        $periods = integer_value($input['parcelas'] ?? null);
        $rate = optional_number($input, 'taxa_comparacao');
        if ($cash === null || $cash <= 0 || $cash > CALCULATION_MAXIMUM || $entry === null || $entry < 0 || $entry > $cash || $installment === null || $installment <= 0 || $installment > CALCULATION_MAXIMUM || $periods === null || $periods < 1 || $periods > 480 || $rate === null || $rate < 0 || $rate > 100) respond(422, ['error' => 'Confira os valores da compra e o número de parcelas.']);
        $monthly = $rate / 100; $present = $entry; $total = bounded_result($entry + $installment * $periods); $discount = 1.0;
        for ($n = 1; $n <= $periods; $n++) { $discount *= 1 + $monthly; $present = bounded_result($present + ($installment / $discount)); }
        respond(200, ['type' => 'cash_vs_installments', 'vista' => round($cash, 2), 'total_parcelado' => round($total, 2), 'valor_presente_parcelado' => round($present, 2), 'diferenca_nominal' => round($total - $cash, 2), 'diferenca_valor_presente' => round($present - $cash, 2), 'melhor_nominal' => $cash <= $total ? 'à vista' : 'parcelado', 'melhor_valor_presente' => $cash <= $present ? 'à vista' : 'parcelado']);
    }

    if ($action === 'rate_convert') {
        $monthly = number_value($input['mensal'] ?? null);
        $annual = number_value($input['anual'] ?? null);
        if (($monthly === null) === ($annual === null) || ($monthly !== null && ($monthly < 0 || $monthly > 10000)) || ($annual !== null && ($annual < 0 || $annual > 1000000))) {
            respond(422, ['error' => 'Informe somente a taxa mensal ou a taxa anual.']);
        }
        if ($monthly !== null) {
            $convertedAnnual = bounded_result((compound_amount(1.0, $monthly, 12) - 1) * 100);
            respond(200, ['type' => 'rate_convert', 'direction' => 'mensal_para_anual', 'mensal' => round($monthly, 8), 'anual' => round($convertedAnnual, 8), 'formula' => 'i anual = (1 + i mensal)^12 − 1', 'explicacao' => 'No regime composto, cada mês incorpora os juros ao saldo. Por isso, os juros do mês seguinte incidem também sobre os juros anteriores.']);
        }
        $convertedMonthly = (pow(1 + $annual / 100, 1 / 12) - 1) * 100;
        respond(200, ['type' => 'rate_convert', 'direction' => 'anual_para_mensal', 'mensal' => round($convertedMonthly, 8), 'anual' => round($annual, 8), 'formula' => 'i mensal = (1 + i anual)^(1/12) − 1', 'explicacao' => 'A conversão inversa encontra a taxa mensal equivalente que, aplicada por 12 meses, produz a taxa anual informada.']);
    }

    if ($action === 'thirteenth') {
        $salary = number_value($input['salario'] ?? null);
        $months = integer_value($input['meses'] ?? null);
        if ($salary === null || $salary <= 0 || $salary > 10000000 || $months === null || $months < 1 || $months > 12) respond(422, ['error' => 'Informe salário e quantidade de meses válidos.']);
        $gross = round($salary * $months / 12, 2);
        $first = round($gross / 2, 2);
        $second = round($gross - $first, 2);
        respond(200, ['type' => 'thirteenth', 'salario' => round($salary, 2), 'meses' => $months, 'bruto' => $gross, 'primeira_parcela' => $first, 'segunda_parcela_bruta' => $second, 'observacao' => 'INSS e IRPF não foram descontados nesta estimativa; eles dependem da tabela e da situação do trabalhador na competência.']);
    }

    if ($action === 'vacation') {
        $salary = number_value($input['salario'] ?? null);
        $days = integer_value($input['dias'] ?? null);
        if ($salary === null || $salary <= 0 || $salary > 10000000 || $days === null || $days < 1 || $days > 30) respond(422, ['error' => 'Informe salário e dias de férias válidos.']);
        $base = round($salary * $days / 30, 2);
        $third = round($base / 3, 2);
        respond(200, ['type' => 'vacation', 'salario' => round($salary, 2), 'dias' => $days, 'remuneracao' => $base, 'terco_constitucional' => $third, 'total_bruto' => round($base + $third, 2), 'observacao' => 'Esta é uma estimativa bruta. Abonos, médias de variáveis, INSS e IRPF podem alterar o valor final.']);
    }

    respond(404, ['error' => 'Calculadora não encontrada.']);
} catch (InvalidArgumentException $e) {
    respond(422, ['error' => $e->getMessage()]);
} catch (RuntimeException $e) {
    respond(502, ['error' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log('SOS cálculos: ' . $e->getMessage());
    respond(500, ['error' => 'Não foi possível concluir o cálculo agora.']);
}
