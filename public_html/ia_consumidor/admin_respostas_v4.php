<?php
require dirname(__FILE__) . '/../include/config_font.inc.php';
require_once dirname(__FILE__) . '/plano_helper.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
header('X-Robots-Tag: noindex, nofollow', true);
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:; script-src 'none'; object-src 'none'; frame-src 'none'; base-uri 'self'; form-action 'self'");
header('X-SOS-Painel-Version: 20260809-4');

if (empty($_SESSION['SESS_IA_CHAT']['id'])) {
    // Mantém o destino solicitado para que o login retorne ao painel.
    $_SESSION['IA_REDIRECT_AFTER_LOGIN'] = '/ia-consumidor-acompanhamento';
    $_SESSION['IA_MSG'] = [
        'tipo'  => 'erro',
        'texto' => 'Faça login para acessar o acompanhamento das respostas.'
    ];
    redirect(IA_CONSUMIDOR . '?tab=login#area-formulario');
}

$user_id = (int) $_SESSION['SESS_IA_CHAT']['id'];
$db = new PDOConfig();
$dados_plano = obterDadosPlano($db, $user_id);

if (($dados_plano['plano_tipo'] ?? '') !== 'ilimitado') {
    http_response_code(403);
    exit('Acesso restrito ao administrador do SOS Responde.');
}

function painelH($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function painelDataValida(string $data): bool
{
    $obj = DateTime::createFromFormat('!Y-m-d', $data);
    return $obj && $obj->format('Y-m-d') === $data;
}

function painelRespostaHtml(string $texto): string
{
    $seguro = painelH($texto);
    $seguro = preg_replace_callback(
        '/\[([^\]]+)\]\((https:\/\/[^\s)]+)\)/i',
        static function (array $m): string {
            return '<a href="' . $m[2] . '" target="_blank" rel="noopener noreferrer">' . $m[1] . '</a>';
        },
        $seguro
    );
    $seguro = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $seguro);
    $seguro = preg_replace('/`([^`]+)`/', '<code>$1</code>', $seguro);
    return nl2br($seguro);
}

function painelCsvSeguro(string $valor): string
{
    return preg_match('/^[=+\-@]/', $valor) ? "'" . $valor : $valor;
}

$busca = trim((string) ($_GET['q'] ?? ''));
$busca = mb_substr($busca, 0, 200, 'UTF-8');
$data_inicio = trim((string) ($_GET['data_inicio'] ?? ''));
$data_fim = trim((string) ($_GET['data_fim'] ?? ''));
$data_inicio = painelDataValida($data_inicio) ? $data_inicio : '';
$data_fim = painelDataValida($data_fim) ? $data_fim : '';

$where = ['1 = 1'];
$params = [];
if ($busca !== '') {
    $where[] = '(COALESCE(c.email, a.email) LIKE :busca OR h.pergunta LIKE :busca OR h.resposta LIKE :busca)';
    $params[':busca'] = '%' . $busca . '%';
}
if ($data_inicio !== '') {
    $where[] = 'h.criado_em >= :data_inicio';
    $params[':data_inicio'] = $data_inicio . ' 00:00:00';
}
if ($data_fim !== '') {
    $where[] = 'h.criado_em <= :data_fim';
    $params[':data_fim'] = $data_fim . ' 23:59:59';
}
$where_sql = implode(' AND ', $where);

$sql_base = "
    FROM historico_ia h
    LEFT JOIN cadastro_ia c ON c.id = h.cadastro_ia_id
    LEFT JOIN cadastro_ia_arquivo_morto a ON a.id = h.cadastro_ia_id
    WHERE {$where_sql}
";

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $stmt_export = $db->prepare("
        SELECT h.id, h.criado_em,
               COALESCE(c.id, a.id) AS usuario_id,
               COALESCE(c.email, a.email) AS email,
               CASE WHEN c.id IS NULL AND a.id IS NOT NULL THEN 1 ELSE 0 END AS usuario_arquivado,
               h.pergunta, h.resposta
        {$sql_base}
        ORDER BY h.criado_em DESC, h.id DESC
    ");
    $stmt_export->execute($params);

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="sos-responde-' . date('Y-m-d-His') . '.csv"');
    echo "\xEF\xBB\xBF";
    $saida = fopen('php://output', 'w');
    fputcsv($saida, ['ID', 'Data e hora', 'ID do usuário', 'E-mail', 'Pergunta', 'Resposta'], ';');
    while ($linha = $stmt_export->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($saida, [
            $linha['id'],
            $linha['criado_em'],
            $linha['usuario_id'],
            painelCsvSeguro((string) $linha['email']),
            painelCsvSeguro((string) $linha['pergunta']),
            painelCsvSeguro((string) $linha['resposta']),
        ], ';');
    }
    fclose($saida);
    exit;
}

$stmt_total = $db->prepare("SELECT COUNT(*) {$sql_base}");
$stmt_total->execute($params);
$total_filtrado = (int) $stmt_total->fetchColumn();

$por_pagina = 25;
$total_paginas = max(1, (int) ceil($total_filtrado / $por_pagina));
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));
$pagina = min($pagina, $total_paginas);
$offset = ($pagina - 1) * $por_pagina;

$stmt_lista = $db->prepare("
    SELECT h.id, h.criado_em,
           COALESCE(c.id, a.id) AS usuario_id,
           COALESCE(c.email, a.email) AS email,
           CASE WHEN c.id IS NULL AND a.id IS NOT NULL THEN 1 ELSE 0 END AS usuario_arquivado,
           h.pergunta, h.resposta
    {$sql_base}
    ORDER BY h.criado_em DESC, h.id DESC
    LIMIT {$por_pagina} OFFSET {$offset}
");
$stmt_lista->execute($params);
$registros = $stmt_lista->fetchAll(PDO::FETCH_ASSOC);

$resumo = $db->query("
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(DATE(h.criado_em) = CURDATE()), 0) AS hoje,
        COALESCE(SUM(h.criado_em >= DATE_SUB(NOW(), INTERVAL 7 DAY)), 0) AS sete_dias,
        COUNT(DISTINCT h.cadastro_ia_id) AS usuarios
    FROM historico_ia h
")->fetch(PDO::FETCH_ASSOC);

$uso_where = ["evento = 'resposta_sucesso'"];
$uso_params = [];
if ($data_inicio !== '') {
    $uso_where[] = 'criado_em >= :uso_data_inicio';
    $uso_params[':uso_data_inicio'] = $data_inicio . ' 00:00:00';
}
if ($data_fim !== '') {
    $uso_where[] = 'criado_em <= :uso_data_fim';
    $uso_params[':uso_data_fim'] = $data_fim . ' 23:59:59';
}
$stmt_uso = $db->prepare("SELECT ferramenta, COUNT(*) AS respostas, COUNT(DISTINCT visitante_id) AS visitantes FROM sos_ferramentas_uso WHERE " . implode(' AND ', $uso_where) . " GROUP BY ferramenta");
$stmt_uso->execute($uso_params);
$uso_ferramentas = [
    'calculos' => ['respostas' => 0, 'visitantes' => 0],
    'juros' => ['respostas' => 0, 'visitantes' => 0],
];
while ($uso = $stmt_uso->fetch(PDO::FETCH_ASSOC)) {
    if (isset($uso_ferramentas[$uso['ferramenta']])) {
        $uso_ferramentas[$uso['ferramenta']] = [
            'respostas' => (int) $uso['respostas'],
            'visitantes' => (int) $uso['visitantes'],
        ];
    }
}

$filtros_query = array_filter([
    'q' => $busca,
    'data_inicio' => $data_inicio,
    'data_fim' => $data_fim,
], static function ($valor): bool {
    return $valor !== '';
});
$url_exportar = '/ia-consumidor-acompanhamento?' . http_build_query(array_merge($filtros_query, ['export' => 'csv']));

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="description" content="Painel restrito de acompanhamento das perguntas e respostas do SOS Responde.">
    <meta name="sos-painel-version" content="20260809-3">
    <title>Acompanhamento de Respostas | SOS Consumidor</title>
    <style>
        :root { --painel-azul: #073b5a; --painel-verde: #07875f; --painel-fundo: #f3f8fa; }
        html { box-sizing: border-box; min-height: 100%; }
        *, *::before, *::after { box-sizing: inherit; }
        html, body { margin: 0; min-height: 100%; width: 100%; }
        body { overflow-x: hidden; background: var(--painel-fundo); color: #173047; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, Helvetica, sans-serif; font-size: 15px; }
        .painel-site-header { background: #fff; border-bottom: 1px solid #dce6eb; }
        .painel-site-header-inner { width: 100%; max-width: 1180px; min-height: 92px; margin: 0 auto; padding: 18px 20px; display: flex; align-items: center; justify-content: space-between; gap: 24px; }
        .painel-site-brand { display: inline-flex; align-items: center; text-decoration: none; }
        .painel-site-brand img { display: block; width: 245px; height: auto; max-height: 72px; object-fit: contain; }
        .painel-site-back { display: inline-flex; align-items: center; gap: 6px; color: #617486; font-size: 14px; text-decoration: none; white-space: nowrap; }
        .painel-site-back:hover { color: var(--painel-azul); text-decoration: underline; }
        .painel-site-footer { margin-top: 8px; border-top: 1px solid #dce6eb; background: #fff; }
        .painel-site-footer-inner { width: 100%; max-width: 1180px; margin: 0 auto; padding: 22px 20px; display: flex; align-items: center; justify-content: space-between; gap: 18px; color: #617486; font-size: 13px; }
        .painel-site-footer-inner a { color: var(--painel-verde); font-weight: 700; text-decoration: none; }
        .painel-site-footer-inner a:hover { text-decoration: underline; }
        .painel-wrap { width: 100%; max-width: 1180px; margin: 0 auto; padding: 28px 20px 48px; }
        .painel-hero { display: flex; align-items: center; justify-content: space-between; gap: 24px; padding: 28px 32px; border-radius: 24px; color: #fff; background: linear-gradient(115deg, #073b5a 0%, #09698a 58%, #07875f 100%); }
        .painel-hero h1 { margin: 0 0 6px; font-size: clamp(24px, 2.5vw, 34px); font-weight: 800; line-height: 1.08; }
        .painel-hero p { margin: 0; font-size: 15px; color: rgba(255,255,255,.86); }
        .painel-voltar { display: inline-flex; align-items: center; white-space: nowrap; border: 1px solid rgba(255,255,255,.45); border-radius: 999px; padding: 11px 16px; color: #fff; font-size: 14px; font-weight: 700; text-decoration: none; }
        .painel-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; margin: 18px 0; }
        .painel-stat, .painel-card { background: #fff; border: 1px solid #dce6eb; box-shadow: 0 8px 24px rgba(7,59,90,.06); }
        .painel-stat { border-radius: 18px; padding: 18px 20px; }
        .painel-stat strong { display: block; color: var(--painel-azul); font-size: 25px; line-height: 1; }
        .painel-stat span { display: block; margin-top: 7px; color: #617486; font-size: 13px; }
        .painel-card { border-radius: 22px; padding: 22px; }
        .painel-uso { margin-bottom: 18px; }
        .painel-uso h2 { margin: 0 0 6px; color: var(--painel-azul); font-size: 20px; }
        .painel-uso-intro { margin: 0 0 16px; color: #617486; font-size: 13px; }
        .painel-uso-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .painel-uso-item { border: 1px solid #dce6eb; border-radius: 16px; padding: 16px 18px; background: #f8fbfc; }
        .painel-uso-item strong { display: block; color: var(--painel-verde); font-size: 28px; line-height: 1; }
        .painel-uso-item h3 { margin: 9px 0 4px; color: var(--painel-azul); font-size: 16px; }
        .painel-uso-item p { margin: 0; color: #617486; font-size: 13px; }
        .painel-filtros { display: grid; grid-template-columns: minmax(240px, 1fr) 170px 170px auto; gap: 12px; align-items: end; }
        .painel-campo label { display: block; margin: 0 0 7px; font-weight: 700; font-size: 13px; }
        .painel-campo input { width: 100%; height: 46px; box-sizing: border-box; border: 1px solid #bdcbd4; border-radius: 12px; padding: 0 14px; background: #fff; font-size: 15px; color: #173047; }
        .painel-botoes { display: flex; gap: 8px; }
        .painel-btn { display: inline-flex; align-items: center; justify-content: center; height: 46px; padding: 0 17px; border: 0; border-radius: 12px; background: var(--painel-verde); color: #fff; font-size: 14px; font-weight: 800; text-decoration: none; cursor: pointer; }
        .painel-btn-sec { background: #e7eff3; color: var(--painel-azul); }
        .painel-lista-topo { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin: 26px 0 12px; }
        .painel-lista-topo h2 { margin: 0; font-size: 19px; }
        .painel-lista-topo p { margin: 3px 0 0; color: #617486; font-size: 14px; }
        .painel-exportar { color: var(--painel-verde); font-weight: 800; text-decoration: none; }
        .painel-item { margin-bottom: 12px; overflow: hidden; }
        .painel-item summary { list-style: none; cursor: pointer; padding: 18px 20px; }
        .painel-item summary::-webkit-details-marker { display: none; }
        .painel-item-head { display: grid; grid-template-columns: minmax(280px, 1.15fr) minmax(300px, 1.85fr) auto; gap: 18px; align-items: center; }
        .painel-email { font-size: 14px; font-weight: 800; color: var(--painel-azul); overflow-wrap: anywhere; }
        .painel-arquivado { display: inline-block; margin-top: 5px; padding: 3px 8px; border-radius: 999px; background: #eef1f3; color: #64717b; font-size: 11px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
        .painel-pergunta { color: #30475a; font-size: 14px; line-height: 1.45; }
        .painel-data { color: #6c7d8b; font-size: 13px; white-space: nowrap; }
        .painel-detalhes { border-top: 1px solid #e3eaee; padding: 20px; background: #fbfdfe; }
        .painel-detalhes h3 { margin: 0 0 8px; font-size: 12px; text-transform: uppercase; letter-spacing: .06em; color: #6c7d8b; }
        .painel-texto { margin: 0 0 20px; font-size: 15px; line-height: 1.58; overflow-wrap: anywhere; }
        .painel-resposta { border-left: 4px solid var(--painel-verde); border-radius: 0 14px 14px 0; padding: 16px 18px; background: #eff9f5; }
        .painel-resposta a { color: #056847; font-weight: 800; text-decoration: underline; }
        .painel-vazio { text-align: center; padding: 44px 20px; color: #617486; }
        .painel-paginacao { display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 22px; }
        .painel-paginacao a, .painel-paginacao span { padding: 9px 13px; border-radius: 10px; background: #fff; border: 1px solid #d6e1e7; color: var(--painel-azul); font-size: 14px; text-decoration: none; font-weight: 700; }
        .painel-paginacao span { background: var(--painel-azul); color: #fff; }
        @media (max-width: 860px) {
            .painel-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .painel-filtros { grid-template-columns: 1fr 1fr; }
            .painel-campo-busca { grid-column: 1 / -1; }
            .painel-item-head { grid-template-columns: 1fr; gap: 7px; }
            .painel-data { white-space: normal; }
        }
        @media (max-width: 580px) {
            .painel-site-header-inner { min-height: 72px; padding: 14px 12px; gap: 12px; }
            .painel-site-brand img { width: 190px; max-height: 54px; }
            .painel-site-back { font-size: 13px; }
            .painel-campo input { font-size: 16px; }
            .painel-site-footer-inner { align-items: flex-start; flex-direction: column; padding: 18px 12px; }
            .painel-wrap { padding: 18px 12px 36px; }
            .painel-hero { align-items: flex-start; flex-direction: column; padding: 24px 22px; border-radius: 18px; }
            .painel-stats, .painel-filtros { grid-template-columns: 1fr; }
            .painel-uso-grid { grid-template-columns: 1fr; }
            .painel-campo-busca { grid-column: auto; }
            .painel-botoes { flex-wrap: wrap; }
            .painel-lista-topo { align-items: flex-start; flex-direction: column; }
        }
    </style>
</head>
<body data-sos-painel-version="20260809-3">
<header class="painel-site-header">
    <div class="painel-site-header-inner">
        <a class="painel-site-brand" href="<?php echo painelH(PROJECT_ROOT); ?>">
            <img src="<?php echo painelH(PROJECT_ROOT . 'img/logo.png'); ?>" alt="SOS Consumidor">
        </a>
        <a class="painel-site-back" href="<?php echo painelH(PROJECT_ROOT); ?>">← Voltar ao site</a>
    </div>
</header>

<main class="painel-wrap">
    <section class="painel-hero">
        <div>
            <h1>Acompanhamento do SOS Responde</h1>
            <p>Todas as perguntas e respostas, em uma área restrita ao administrador.</p>
        </div>
        <a class="painel-voltar" href="<?php echo painelH(IA_CONSUMIDOR_CHAT); ?>">Voltar ao chat</a>
    </section>

    <section class="painel-stats" aria-label="Resumo do atendimento">
        <div class="painel-stat"><strong><?php echo number_format((int) $resumo['total'], 0, ',', '.'); ?></strong><span>Perguntas registradas</span></div>
        <div class="painel-stat"><strong><?php echo number_format((int) $resumo['hoje'], 0, ',', '.'); ?></strong><span>Respondidas hoje</span></div>
        <div class="painel-stat"><strong><?php echo number_format((int) $resumo['sete_dias'], 0, ',', '.'); ?></strong><span>Nos últimos 7 dias</span></div>
        <div class="painel-stat"><strong><?php echo number_format((int) $resumo['usuarios'], 0, ',', '.'); ?></strong><span>Usuários atendidos</span></div>
    </section>

    <section class="painel-card painel-uso" aria-labelledby="painel-uso-titulo">
        <h2 id="painel-uso-titulo">Uso das ferramentas</h2>
        <p class="painel-uso-intro">Considera somente preenchimento enviado, clique e resposta entregue. “Pessoas” representa navegadores distintos identificados por um código anônimo. Os filtros de data também se aplicam a este resumo.</p>
        <div class="painel-uso-grid">
            <article class="painel-uso-item">
                <strong><?php echo number_format($uso_ferramentas['calculos']['visitantes'], 0, ',', '.'); ?></strong>
                <h3>Calculadora de cálculos</h3>
                <p><?php echo number_format($uso_ferramentas['calculos']['respostas'], 0, ',', '.'); ?> resposta<?php echo $uso_ferramentas['calculos']['respostas'] === 1 ? '' : 's'; ?> concluída<?php echo $uso_ferramentas['calculos']['respostas'] === 1 ? '' : 's'; ?></p>
            </article>
            <article class="painel-uso-item">
                <strong><?php echo number_format($uso_ferramentas['juros']['visitantes'], 0, ',', '.'); ?></strong>
                <h3>Calculadora de juros</h3>
                <p><?php echo number_format($uso_ferramentas['juros']['respostas'], 0, ',', '.'); ?> resposta<?php echo $uso_ferramentas['juros']['respostas'] === 1 ? '' : 's'; ?> concluída<?php echo $uso_ferramentas['juros']['respostas'] === 1 ? '' : 's'; ?></p>
            </article>
        </div>
    </section>

    <section class="painel-card">
        <form class="painel-filtros" method="get" action="/ia-consumidor-acompanhamento">
            <div class="painel-campo painel-campo-busca">
                <label for="q">Pesquisar</label>
                <input id="q" name="q" type="search" value="<?php echo painelH($busca); ?>" placeholder="E-mail, pergunta ou trecho da resposta">
            </div>
            <div class="painel-campo">
                <label for="data_inicio">Data inicial</label>
                <input id="data_inicio" name="data_inicio" type="date" value="<?php echo painelH($data_inicio); ?>">
            </div>
            <div class="painel-campo">
                <label for="data_fim">Data final</label>
                <input id="data_fim" name="data_fim" type="date" value="<?php echo painelH($data_fim); ?>">
            </div>
            <div class="painel-botoes">
                <button class="painel-btn" type="submit">Filtrar</button>
                <a class="painel-btn painel-btn-sec" href="/ia-consumidor-acompanhamento">Limpar</a>
            </div>
        </form>
    </section>

    <div class="painel-lista-topo">
        <div>
            <h2>Perguntas e respostas</h2>
            <p><?php echo number_format($total_filtrado, 0, ',', '.'); ?> resultado<?php echo $total_filtrado === 1 ? '' : 's'; ?> encontrado<?php echo $total_filtrado === 1 ? '' : 's'; ?></p>
        </div>
        <a class="painel-exportar" href="<?php echo painelH($url_exportar); ?>">Exportar resultados em CSV</a>
    </div>

    <?php if (!$registros): ?>
        <section class="painel-card painel-vazio">Nenhuma pergunta encontrada com esses filtros.</section>
    <?php else: ?>
        <?php foreach ($registros as $registro): ?>
            <details class="painel-card painel-item">
                <summary>
                    <div class="painel-item-head">
                        <div class="painel-email">
                            <?php echo painelH($registro['email']); ?>
                            <?php if (!empty($registro['usuario_arquivado'])): ?><span class="painel-arquivado">Arquivo morto</span><?php endif; ?>
                        </div>
                        <div class="painel-pergunta"><?php echo painelH(mb_strimwidth($registro['pergunta'], 0, 180, '…', 'UTF-8')); ?></div>
                        <time class="painel-data" datetime="<?php echo painelH(date('c', strtotime($registro['criado_em']))); ?>"><?php echo date('d/m/Y \à\s H:i', strtotime($registro['criado_em'])); ?></time>
                    </div>
                </summary>
                <div class="painel-detalhes">
                    <h3>Pergunta do usuário</h3>
                    <p class="painel-texto"><?php echo nl2br(painelH($registro['pergunta'])); ?></p>
                    <h3>Resposta do SOS Responde</h3>
                    <div class="painel-texto painel-resposta"><?php echo painelRespostaHtml($registro['resposta']); ?></div>
                    <small>Registro #<?php echo (int) $registro['id']; ?> · Usuário #<?php echo (int) $registro['usuario_id']; ?></small>
                </div>
            </details>
        <?php endforeach; ?>

        <?php if ($total_paginas > 1): ?>
            <nav class="painel-paginacao" aria-label="Paginação">
                <?php if ($pagina > 1): ?>
                    <a href="?<?php echo painelH(http_build_query(array_merge($filtros_query, ['pagina' => $pagina - 1]))); ?>">Anterior</a>
                <?php endif; ?>
                <span>Página <?php echo $pagina; ?> de <?php echo $total_paginas; ?></span>
                <?php if ($pagina < $total_paginas): ?>
                    <a href="?<?php echo painelH(http_build_query(array_merge($filtros_query, ['pagina' => $pagina + 1]))); ?>">Próxima</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</main>

<footer class="painel-site-footer">
    <div class="painel-site-footer-inner">
        <span>SOS Consumidor · SOS Responde</span>
        <a href="<?php echo painelH(IA_CONSUMIDOR); ?>">Voltar ao SOS Responde</a>
    </div>
</footer>
</body>
</html>
