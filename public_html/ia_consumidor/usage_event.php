<?php
/**
 * Registra, sem dados do formulário, o uso concluído das ferramentas públicas.
 * O visitante é identificado apenas por um UUID anônimo mantido no navegador.
 */
require dirname(__FILE__) . '/../include/config_font.inc.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['ok' => false, 'error' => 'Método não permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$body = json_decode((string) file_get_contents('php://input'), true);
$ferramenta = is_array($body) ? (string) ($body['ferramenta'] ?? '') : '';
$visitante = is_array($body) ? (string) ($body['visitante_id'] ?? '') : '';

$ferramentas_permitidas = ['calculos', 'juros'];
if (!in_array($ferramenta, $ferramentas_permitidas, true) || !preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i', $visitante)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Evento inválido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = new PDOConfig();
    $stmt = $db->prepare('INSERT INTO sos_ferramentas_uso (visitante_id, ferramenta, evento) VALUES (?, ?, ?)');
    $stmt->execute([$visitante, $ferramenta, 'resposta_sucesso']);
    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    // A medição nunca pode impedir a calculadora de entregar o resultado.
    http_response_code(202);
    echo json_encode(['ok' => false], JSON_UNESCAPED_UNICODE);
}
