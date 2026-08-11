<?php
/**
 * Registra, sem dados do formulário, o uso concluído das ferramentas públicas
 * e a navegação anônima nas páginas acompanhadas.
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
$evento = is_array($body) ? (string) ($body['evento'] ?? '') : '';
$visitante = is_array($body) ? (string) ($body['visitante_id'] ?? '') : '';

// Mantém compatibilidade com o evento antigo enviado pelas calculadoras.
if ($evento === '' && in_array($ferramenta, ['calculos', 'juros'], true)) {
    $evento = 'resposta_sucesso';
}

$eventos_permitidos = [
    'resposta_sucesso' => ['calculos', 'juros'],
    'impressao' => ['ia', 'calculos', 'juros'],
    'clique' => ['ia', 'calculos', 'juros'],
    'inicio' => ['ia', 'calculos', 'juros'],
    'resultado' => ['ia', 'calculos', 'juros'],
    'demo_resultado' => ['ia', 'calculos', 'juros'],
    'cadastro' => ['ia', 'calculos', 'juros'],
    'cadastro_criado' => ['ia', 'calculos', 'juros'],
    'page_view' => ['pagina_ia_consumidor', 'pagina_juros', 'pagina_calculos'],
    'ajudinha_open' => ['ajudinha'],
    'ajudinha_link_click' => [
        'ajudinha_link_ia_consumidor',
        'ajudinha_link_juros',
        'ajudinha_link_calculos',
    ],
];

$uuid_valido = preg_match(
    '/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i',
    $visitante
);
if (!$uuid_valido || !isset($eventos_permitidos[$evento]) || !in_array($ferramenta, $eventos_permitidos[$evento], true)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Evento inválido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = new PDOConfig();
    $stmt = $db->prepare('INSERT INTO sos_ferramentas_uso (visitante_id, ferramenta, evento) VALUES (?, ?, ?)');
    $stmt->execute([$visitante, $ferramenta, $evento]);
    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    // A medição nunca pode impedir a página ou a calculadora de funcionar.
    http_response_code(202);
    echo json_encode(['ok' => false], JSON_UNESCAPED_UNICODE);
}
