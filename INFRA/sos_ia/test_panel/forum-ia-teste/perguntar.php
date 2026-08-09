<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(int $status, array $body): never {
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest') {
    respond(405, ['error' => 'Método não permitido.']);
}

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && $origin !== 'https://teste.sosconsumidor.com.br') {
    respond(403, ['error' => 'Origem não permitida.']);
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input) || !is_string($input['question'] ?? null) || !is_string($input['csrf'] ?? null)) {
    respond(422, ['error' => 'Envio inválido.']);
}
if (!hash_equals($_SESSION['forum_ia_test_csrf'] ?? '', $input['csrf'])) {
    respond(403, ['error' => 'Sessão de teste expirada. Recarregue a página.']);
}

$question = trim(strip_tags($input['question']));
if (mb_strlen($question) < 10 || mb_strlen($question) > 2000) {
    respond(422, ['error' => 'A pergunta deve ter entre 10 e 2.000 caracteres.']);
}
if (preg_match('/(?<!\d)\d{3}\.?\d{3}\.?\d{3}-?\d{2}(?!\d)|(?<!\d)(?:\d[ -]?){13,19}(?!\d)|\b(?:senha|password|cvv)\s*[:=]/iu', $question)) {
    respond(422, ['error' => 'Remova CPF, senha, CVV e números completos de cartão antes de enviar.']);
}

$now = time();
$window = $_SESSION['forum_ia_test_window'] ?? ['started_at' => $now, 'count' => 0];
if (($now - (int) $window['started_at']) > 86400) {
    $window = ['started_at' => $now, 'count' => 0];
}
if ((int) $window['count'] >= 10) {
    respond(429, ['error' => 'O limite deste painel de teste foi atingido. Tente novamente amanhã.']);
}

$config = require dirname(__DIR__, 2) . '/private/forum-ia-test-config.php';
$request = json_encode(['question' => $question], JSON_UNESCAPED_UNICODE);
$curl = curl_init($config['api_url']);
curl_setopt_array($curl, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $request,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-Internal-Test-Key: ' . $config['api_key']],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 55,
]);
$response = curl_exec($curl);
$httpStatus = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
$curlError = curl_error($curl);
curl_close($curl);

if ($response === false || $curlError !== '') {
    respond(502, ['error' => 'A IA de teste não respondeu. Tente novamente em instantes.']);
}
$data = json_decode($response, true);
if ($httpStatus < 200 || $httpStatus >= 300 || !is_array($data)) {
    respond($httpStatus >= 400 && $httpStatus < 500 ? $httpStatus : 502, ['error' => $data['detail'] ?? 'Não foi possível gerar a resposta.']);
}

$window['count'] = (int) $window['count'] + 1;
$_SESSION['forum_ia_test_window'] = $window;
respond(200, ['answer' => $data['answer'] ?? '', 'sources' => $data['sources'] ?? []]);
