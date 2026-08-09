<?php
/**
 * Diretório de Advogados - Formulário de Auto-Cadastro
 * SOS Consumidor - 2026
 */
require dirname(__FILE__) . '/include/config_font.inc.php';
require PROJECT_PATH . 'admsite/classes/ibge/ibge.inc.php';
require PROJECT_PATH . 'admsite/classes/advogados/advogado_directory.inc.php';

$db = new PDOConfig();

try {
    $parametros = new Parametros($db);
    $parametros->setDados(1);
} catch (Exception $e) {
    Msg::add($e->getMessage());
    redirect(PROJECT_ROOT);
}

$estados        = AdvogadoDirectory::getAllEstados($db);
$especialidades = AdvogadoDirectory::getAllEspecialidades($db);

// Meta tags
$titulo_site      = 'Cadastro de Advogado | SOS Consumidor';
$description_site = 'Cadastre-se gratuitamente no diretório de advogados do SOS Consumidor e seja encontrado por milhares de pessoas que buscam orientação jurídica.';
$meta_keywords    = $parametros->getMetaKeywords() . ', cadastro advogado, registrar advogado';

// Verificar sucesso
$cadastro_sucesso = isset($_GET['sucesso']) && isset($_SESSION['cadastro_adv_sucesso']);
if ($cadastro_sucesso) {
    unset($_SESSION['cadastro_adv_sucesso']);
}

// Dados para repreencher form em caso de erro
$form_data = $_SESSION['cadastro_adv_form'] ?? [];
unset($_SESSION['cadastro_adv_form']);
?>
<?php require PROJECT_PATH . 'head.inc.php'; ?>
<link rel="stylesheet" href="<?php echo PROJECT_ROOT; ?>css/advogados.css">
<body>
<?php require PROJECT_PATH . 'header.inc.php'; ?>

<div class="container-fluid">
<div class="l-content-block">
<div class="row">

<!-- ===== MAIN CONTENT ===== -->
<div class="col-sm-8 col-sm-offset-2">

    <!-- Breadcrumb -->
    <ol class="breadcrumb adv-breadcrumb">
        <li><a href="<?php echo PROJECT_ROOT; ?>">Página Inicial</a></li>
        <li><a href="<?php echo ADVOGADOS_DIR; ?>">Advogados</a></li>
        <li class="active">Cadastro</li>
    </ol>

    <?php
    // Exibir mensagens de erro (se houver)
    if (isset($_SESSION['adv_flash_error'])) {
        echo '<div class="adv-alert adv-alert--error"><i class="fa fa-exclamation-triangle"></i> ' . $_SESSION['adv_flash_error'] . '</div>';
        unset($_SESSION['adv_flash_error']);
    }
    ?>

    <?php if ($cadastro_sucesso): ?>
    <!-- Tela de sucesso -->
    <div style="text-align:center;padding:40px 20px;">
        <div style="font-size:60px;color:#27ae60;margin-bottom:20px;"><i class="fa fa-check-circle"></i></div>
        <h2 style="color:#27ae60;margin-bottom:15px;">Cadastro Realizado com Sucesso!</h2>
        <div class="adv-alert adv-alert--success">
            <strong>Verifique seu email!</strong><br>
            Enviamos um link de confirmação para o email informado. Clique no link para confirmar seu cadastro.<br><br>
            Após a confirmação, seu cadastro será analisado e validado junto à OAB antes da publicação.
        </div>
        <div style="margin-top:20px;">
            <a href="<?php echo ADVOGADOS_DIR; ?>" class="btn btn-primary"><i class="fa fa-search"></i> Ver diretório de advogados</a>
        </div>
    </div>
    <?php else: ?>

    <div class="adv-cadastro-form">
        <h2><i class="fa fa-gavel"></i> Cadastro de Advogado</h2>
        <p class="subtitulo">Preencha o formulário abaixo para se cadastrar no diretório de advogados do SOS Consumidor.</p>

        <div class="aviso-validacao">
            <i class="fa fa-info-circle"></i>
            <strong>Importante:</strong> Seu cadastro será analisado e validado junto à OAB (Cadastro Nacional de Advogados) antes da publicação no diretório.
        </div>

        <form method="POST" action="<?php echo PROJECT_ROOT; ?>cadastro_advogado_db.php" id="form-cadastro-adv">

            <!-- Dados Profissionais -->
            <div class="form-section">
                <h4><i class="fa fa-id-card"></i> Dados Profissionais</h4>
                <div class="row">
                    <div class="col-sm-8">
                        <div class="form-group">
                            <label for="nome_completo">Nome completo *</label>
                            <input type="text" name="nome_completo" id="nome_completo" class="form-control" required maxlength="255" value="<?php echo htmlspecialchars($form_data['nome_completo'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            <label for="cpf">CPF *</label>
                            <input type="text" name="cpf" id="cpf" class="form-control" required maxlength="14" placeholder="000.000.000-00" value="<?php echo htmlspecialchars($form_data['cpf'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-4">
                        <div class="form-group">
                            <label for="oab">Número OAB *</label>
                            <input type="text" name="oab" id="oab" class="form-control" required maxlength="10" value="<?php echo htmlspecialchars($form_data['oab'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            <label for="oab_estado">Seccional OAB (Estado) *</label>
                            <select name="oab_estado" id="oab_estado" class="form-control" required>
                                <option value="">Selecione</option>
                                <?php foreach ($estados as $est): ?>
                                <option value="<?php echo $est['id']; ?>"<?php echo (isset($form_data['oab_estado']) && $form_data['oab_estado'] == $est['id']) ? ' selected' : ''; ?>>
                                    <?php echo $est['nome']; ?> - <?php echo htmlspecialchars($est['nome_full']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            <label for="nome_empresa">Escritório / Empresa</label>
                            <input type="text" name="nome_empresa" id="nome_empresa" class="form-control" maxlength="255" value="<?php echo htmlspecialchars($form_data['nome_empresa'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contato -->
            <div class="form-section">
                <h4><i class="fa fa-phone"></i> Contato</h4>
                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" name="email" id="email" class="form-control" required maxlength="255" value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="site">Site</label>
                            <input type="url" name="site" id="site" class="form-control" maxlength="255" placeholder="https://" value="<?php echo htmlspecialchars($form_data['site'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-2">
                        <div class="form-group">
                            <label for="ddd">DDD</label>
                            <input type="text" name="ddd" id="ddd" class="form-control" maxlength="3" value="<?php echo htmlspecialchars($form_data['ddd'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            <label for="telefone">Telefone</label>
                            <input type="text" name="telefone" id="telefone" class="form-control" maxlength="15" value="<?php echo htmlspecialchars($form_data['telefone'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-sm-2">
                        <div class="form-group">
                            <label for="ddd_celular">DDD Cel.</label>
                            <input type="text" name="ddd_celular" id="ddd_celular" class="form-control" maxlength="3" value="<?php echo htmlspecialchars($form_data['ddd_celular'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            <label for="celular">Celular</label>
                            <input type="text" name="celular" id="celular" class="form-control" maxlength="15" value="<?php echo htmlspecialchars($form_data['celular'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-4">
                        <div class="form-group">
                            <label for="whatsapp">WhatsApp</label>
                            <input type="text" name="whatsapp" id="whatsapp" class="form-control" maxlength="20" placeholder="(00) 00000-0000" value="<?php echo htmlspecialchars($form_data['whatsapp'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Endereço -->
            <div class="form-section">
                <h4><i class="fa fa-map-marker"></i> Endereço</h4>
                <div class="row">
                    <div class="col-sm-3">
                        <div class="form-group">
                            <label for="cep">CEP</label>
                            <input type="text" name="cep" id="cep" class="form-control" maxlength="9" placeholder="00000-000" value="<?php echo htmlspecialchars($form_data['cep'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="endereco">Endereço</label>
                            <input type="text" name="endereco" id="endereco" class="form-control" maxlength="255" value="<?php echo htmlspecialchars($form_data['endereco'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            <label for="bairro">Bairro</label>
                            <input type="text" name="bairro" id="bairro" class="form-control" maxlength="255" value="<?php echo htmlspecialchars($form_data['bairro'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-4">
                        <div class="form-group">
                            <label for="estado_ibge">Estado *</label>
                            <select name="estado_ibge" id="estado_ibge" class="form-control" required>
                                <option value="">Selecione</option>
                                <?php foreach ($estados as $est): ?>
                                <option value="<?php echo $est['id']; ?>"<?php echo (isset($form_data['estado_ibge']) && $form_data['estado_ibge'] == $est['id']) ? ' selected' : ''; ?>>
                                    <?php echo htmlspecialchars($est['nome_full']); ?> (<?php echo $est['nome']; ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            <label for="cidade_ibge">Cidade *</label>
                            <select name="cidade_ibge" id="cidade_ibge" class="form-control" required>
                                <option value="">Selecione o estado primeiro</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Especialidades -->
            <div class="form-section">
                <h4><i class="fa fa-briefcase"></i> Áreas de Atuação</h4>
                <p style="font-size:13px;color:#888;margin-bottom:12px;">Selecione suas áreas de especialidade:</p>
                <div class="esp-checkbox-grid">
                    <?php foreach ($especialidades as $esp): ?>
                    <label>
                        <input type="checkbox" name="especialidades[]" value="<?php echo $esp['id']; ?>"
                            <?php echo (isset($form_data['especialidades']) && in_array($esp['id'], $form_data['especialidades'])) ? ' checked' : ''; ?>>
                        <?php echo htmlspecialchars($esp['nome']); ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Descrição -->
            <div class="form-section">
                <h4><i class="fa fa-pencil"></i> Apresentação</h4>
                <div class="form-group">
                    <label for="descricao">Mini bio / Descrição profissional</label>
                    <textarea name="descricao" id="descricao" class="form-control" rows="4" maxlength="1000" placeholder="Conte um pouco sobre sua experiência e atuação profissional..."><?php echo htmlspecialchars($form_data['descricao'] ?? ''); ?></textarea>
                    <p class="help-block" style="font-size:12px;">Máximo 1000 caracteres.</p>
                </div>
            </div>

            <!-- Senha -->
            <div class="form-section">
                <h4><i class="fa fa-lock"></i> Segurança</h4>
                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="senha">Senha *</label>
                            <input type="password" name="senha" id="senha" class="form-control" required minlength="6" maxlength="50">
                            <p class="help-block" style="font-size:12px;">Mínimo 6 caracteres.</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="senha_confirma">Confirmar senha *</label>
                            <input type="password" name="senha_confirma" id="senha_confirma" class="form-control" required minlength="6" maxlength="50">
                        </div>
                    </div>
                </div>
            </div>

            <!-- reCAPTCHA + Submit -->
            <div style="text-align:center;margin-top:20px;">
                <?php if (defined('RECAPCHA_SITEKEY') && RECAPCHA_SITEKEY): ?>
                <div class="form-group" style="display:inline-block;">
                    <div class="g-recaptcha" data-sitekey="<?php echo RECAPCHA_SITEKEY; ?>"></div>
                    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                </div>
                <br>
                <?php endif; ?>

                <button type="submit" class="btn-cadastrar">
                    <i class="fa fa-check"></i> Cadastrar
                </button>
                <p style="font-size:12px;color:#888;margin-top:10px;">
                    Ao se cadastrar, você concorda com a <a href="<?php echo PROJECT_ROOT; ?>politica-de-privacidade" target="_blank">Política de Privacidade</a> do SOS Consumidor.
                </p>
            </div>
        </form>
    </div>

    <?php endif; ?><!-- fim do if/else cadastro_sucesso -->

</div>
<!-- ===== END MAIN CONTENT ===== -->

</div>
</div>
</div>

<script>
$(document).ready(function(){
    // AJAX cidades por estado
    $('#estado_ibge').change(function(){
        var estado_id = $(this).val();
        var $cidade = $('#cidade_ibge');
        $cidade.html('<option value="">Carregando...</option>');
        if (!estado_id) {
            $cidade.html('<option value="">Selecione o estado primeiro</option>');
            return;
        }
        $.ajax({
            url: '<?php echo PROJECT_ROOT; ?>ajax/cidades_ajax.php',
            type: 'POST',
            data: { uf: estado_id },
            dataType: 'html',
            success: function(data) {
                if (data && data.length > 0) {
                    $cidade.html(data);
                } else {
                    $cidade.html('<option value="">Selecione a cidade</option>');
                }
            },
            error: function() {
                $cidade.html('<option value="">Erro ao carregar cidades</option>');
            }
        });
    });

    // Máscara CPF simples
    $('#cpf').on('input', function(){
        var v = $(this).val().replace(/\D/g, '');
        if (v.length > 11) v = v.substr(0, 11);
        if (v.length > 9) v = v.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
        else if (v.length > 6) v = v.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
        else if (v.length > 3) v = v.replace(/(\d{3})(\d{1,3})/, '$1.$2');
        $(this).val(v);
    });

    // Máscara CEP
    $('#cep').on('input', function(){
        var v = $(this).val().replace(/\D/g, '');
        if (v.length > 8) v = v.substr(0, 8);
        if (v.length > 5) v = v.replace(/(\d{5})(\d{1,3})/, '$1-$2');
        $(this).val(v);
    });

    // Validação senha
    $('#form-cadastro-adv').on('submit', function(e){
        var senha = $('#senha').val();
        var confirma = $('#senha_confirma').val();
        if (senha !== confirma) {
            alert('As senhas não conferem.');
            e.preventDefault();
            return false;
        }
        if (senha.length < 6) {
            alert('A senha deve ter no mínimo 6 caracteres.');
            e.preventDefault();
            return false;
        }
    });

    <?php if (!empty($form_data['estado_ibge'])): ?>
    // Recarregar cidades se voltou com erro
    setTimeout(function(){
        $('#estado_ibge').trigger('change');
        <?php if (!empty($form_data['cidade_ibge'])): ?>
        setTimeout(function(){
            $('#cidade_ibge').val('<?php echo (int)$form_data['cidade_ibge']; ?>');
        }, 1000);
        <?php endif; ?>
    }, 500);
    <?php endif; ?>
});
</script>

<?php require PROJECT_PATH . 'footer.inc.php'; ?>
