<?php
$sos_tools_root = defined('PROJECT_ROOT') ? rtrim((string) PROJECT_ROOT, '/') . '/' : '/';
?>
<style id="sos-free-tools-home">
    .sos-free-tools{margin:24px 0 30px;padding:22px;border:1px solid #d7e7ee;border-radius:16px;background:linear-gradient(135deg,#f3f9fc,#f1faf6);box-shadow:0 8px 22px rgba(24,50,71,.08)}
    .sos-free-tools__head{display:flex;align-items:end;justify-content:space-between;gap:16px;margin-bottom:16px}
    .sos-free-tools__head h2{margin:0;color:#07557f;font-size:25px;line-height:1.15}
    .sos-free-tools__head p{max-width:520px;margin:5px 0 0;color:#536879;font-size:14px}
    .sos-free-tools__badge{flex:0 0 auto;padding:6px 10px;border-radius:999px;background:#087f5b;color:#fff;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.04em}
    .sos-free-tools__grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
    .sos-free-tool{display:flex;flex-direction:column;min-height:190px;padding:18px;border:1px solid #dce8ee;border-radius:13px;background:#fff;color:#183247;text-decoration:none;transition:transform .16s ease,box-shadow .16s ease,border-color .16s ease}
    .sos-free-tool:hover,.sos-free-tool:focus-visible{transform:translateY(-2px);border-color:#80c8ae;box-shadow:0 9px 20px rgba(7,85,127,.13);outline:none;text-decoration:none}
    .sos-free-tool__icon{display:grid;place-items:center;width:46px;height:46px;margin-bottom:13px;border-radius:13px;background:#e8f4fa;color:#07557f;font-size:25px;font-weight:800}
    .sos-free-tool:nth-child(2) .sos-free-tool__icon{background:#edf8f3;color:#087f5b}
    .sos-free-tool:nth-child(3) .sos-free-tool__icon{background:#fff6df;color:#9a6500}
    .sos-free-tool strong{display:block;color:#07557f;font-size:18px;line-height:1.2}
    .sos-free-tool span{display:block;margin:7px 0 15px;color:#536879;font-size:14px;line-height:1.4}
    .sos-free-tool em{margin-top:auto;color:#087f5b;font-size:13px;font-style:normal;font-weight:800}
    @media(max-width:767px){.sos-free-tools{padding:18px 15px}.sos-free-tools__head{align-items:flex-start}.sos-free-tools__badge{display:none}.sos-free-tools__grid{grid-template-columns:1fr}.sos-free-tool{min-height:0}}
</style>
<section class="sos-free-tools" aria-labelledby="sos-free-tools-title">
    <div class="sos-free-tools__head">
        <div>
            <h2 id="sos-free-tools-title">Resolva agora com ferramentas gratuitas</h2>
            <p>Compare taxas, faça cálculos e entenda o próximo passo sem informar CPF ou dados do contrato.</p>
        </div>
        <span class="sos-free-tools__badge">Grátis</span>
    </div>
    <div class="sos-free-tools__grid">
        <a class="sos-free-tool" href="<?php echo htmlspecialchars($sos_tools_root . 'ia-consumidor/#demonstracao', ENT_QUOTES, 'UTF-8'); ?>" data-sos-tool="ia" data-sos-source="home_card" data-sos-impression>
            <span class="sos-free-tool__icon" aria-hidden="true">?</span>
            <strong>SOS Responde com IA</strong>
            <span>Veja exemplos de orientação e experimente como o assistente organiza sua dúvida de consumo.</span>
            <em>Ver demonstração →</em>
        </a>
        <a class="sos-free-tool" href="<?php echo htmlspecialchars($sos_tools_root . 'juros/', ENT_QUOTES, 'UTF-8'); ?>" data-sos-tool="juros" data-sos-source="home_card" data-sos-impression>
            <span class="sos-free-tool__icon" aria-hidden="true">%</span>
            <strong>Comparador de juros</strong>
            <span>Compare a taxa do empréstimo ou financiamento com a média oficial do Banco Central.</span>
            <em>Comparar minha taxa →</em>
        </a>
        <a class="sos-free-tool" href="<?php echo htmlspecialchars($sos_tools_root . 'calculos/', ENT_QUOTES, 'UTF-8'); ?>" data-sos-tool="calculos" data-sos-source="home_card" data-sos-impression>
            <span class="sos-free-tool__icon" aria-hidden="true">=</span>
            <strong>Calculadoras do consumidor</strong>
            <span>Atualize boleto e dívida, simule parcelas, juros, 13º salário, férias e outros valores.</span>
            <em>Escolher um cálculo →</em>
        </a>
    </div>
</section>
