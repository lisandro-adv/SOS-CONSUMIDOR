<?php
require dirname(__FILE__) . "/include/config_font.inc.php";

header("Content-Type: application/xml; charset=utf-8");
$base = "https://www.sosconsumidor.com.br";
$db = new PDOConfig();

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

// Páginas estáticas
$paginas = [
    ["loc" => $base . "/",                      "priority" => "1.0", "changefreq" => "daily"],
    ["loc" => $base . "/noticias",              "priority" => "0.9", "changefreq" => "daily"],
    ["loc" => $base . "/perguntas-e-respostas", "priority" => "0.9", "changefreq" => "weekly"],
    ["loc" => $base . "/ia-consumidor/",         "priority" => "0.8", "changefreq" => "weekly"],
    ["loc" => $base . "/juros/",                "priority" => "0.8", "changefreq" => "monthly"],
    ["loc" => $base . "/calculos/",             "priority" => "0.8", "changefreq" => "monthly"],
    ["loc" => $base . "/calculos/boleto-vencido/", "priority" => "0.7", "changefreq" => "monthly"],
    ["loc" => $base . "/calculos/atualizar-divida/", "priority" => "0.7", "changefreq" => "monthly"],
    ["loc" => $base . "/calculos/emprestimo-financiamento/", "priority" => "0.7", "changefreq" => "monthly"],
    ["loc" => $base . "/calculos/avista-ou-parcelado/", "priority" => "0.7", "changefreq" => "monthly"],
    ["loc" => $base . "/calculos/juros-valor-futuro/", "priority" => "0.7", "changefreq" => "monthly"],
    ["loc" => $base . "/calculos/reajuste-aluguel/", "priority" => "0.7", "changefreq" => "monthly"],
    ["loc" => $base . "/calculos/decimo-terceiro/", "priority" => "0.7", "changefreq" => "monthly"],
    ["loc" => $base . "/calculos/ferias-um-terco/", "priority" => "0.7", "changefreq" => "monthly"],
    ["loc" => $base . "/calculos/converter-juros-mensal-anual/", "priority" => "0.7", "changefreq" => "monthly"],
    ["loc" => $base . "/institucional",         "priority" => "0.6", "changefreq" => "monthly"],
    ["loc" => $base . "/contatos",              "priority" => "0.5", "changefreq" => "monthly"],
    ["loc" => $base . "/newsletter",            "priority" => "0.4", "changefreq" => "monthly"],
];
foreach ($paginas as $p) {
    echo "  <url><loc>" . htmlspecialchars($p["loc"]) . "</loc><priority>" . $p["priority"] . "</priority><changefreq>" . $p["changefreq"] . "</changefreq></url>\n";
}

// Notícias
try {
    $stmt = $db->query("SELECT id, titulo, data_publicacao FROM noticias WHERE ativo = 1 AND data_publicacao <= NOW() ORDER BY data_publicacao DESC LIMIT 5000", PDO::FETCH_ASSOC);
    foreach ($stmt as $row) {
        $slug = gerar_link_seo($row["titulo"], '-', true);
        $loc  = $base . "/noticias-" . $row["id"] . "-" . $slug;
        $lastmod = substr($row["data_publicacao"], 0, 10);
        echo "  <url><loc>" . htmlspecialchars($loc) . "</loc><lastmod>" . $lastmod . "</lastmod><priority>0.8</priority><changefreq>monthly</changefreq></url>\n";
    }
} catch (Exception $e) {}

// Perguntas e Respostas
try {
    $stmt = $db->query("SELECT id, pergunta FROM perguntas_e_respostas WHERE ativo = 1 ORDER BY id DESC LIMIT 2000", PDO::FETCH_ASSOC);
    foreach ($stmt as $row) {
        $slug = gerar_link_seo($row["pergunta"], '-', true);
        $loc  = $base . "/perguntas-e-respostas-detalhes-" . $slug . "-" . $row["id"];
        echo "  <url><loc>" . htmlspecialchars($loc) . "</loc><priority>0.7</priority><changefreq>monthly</changefreq></url>\n";
    }
} catch (Exception $e) {}

echo "</urlset>\n";
