<?php
require dirname(__FILE__) . '/include/config_font.inc.php';
require PROJECT_PATH . 'admsite/classes/ibge/ibge.inc.php';
$db = new PDOConfig();

#echo PROJECT_ROOT;
#header('Location: http://www.sosconsumidor.com.br/');
#header("location:PROJECT_ROOT");
#die;
try {

    $parametros = new Parametros($db);
    $parametros->setDados(1);

    $data = date("Y-m-d");

    $forum = ajustaStringBD(get('forum'));


    ############################################################
    $contato = new Institucional($db);
    $contato->setDados(Institucional::CONTATO);
    ############################################################
    #$fale_conosco = new Institucional($db);
    #$fale_conosco->setDados(Institucional::FALE_CONOSCO);

    #print_r2($noticias_array);
    $titulo_site = TITULO_SITE_DEFAULT;
    $meta_keywords = $parametros->getMetaKeywords();
    $description_site = $parametros->getMetaDescription();
} catch (Exception $e) {
    #die($e->getMessage());
    Msg::add($e->getMessage());
    redirect(PROJECT_ROOT);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <?php include PROJECT_PATH . 'head.inc.php'; ?>
</head>
<body>
<?php include PROJECT_PATH . 'header.inc.php'; ?>
<div class="c-breadcrumb">
    <div class="l-content-block">
        <div class="c-breadcrumb__list">
            <div class="c-breadcrumb__item"><a href="<?php echo PROJECT_ROOT; ?>">Página Inicial</a></div>
            <div class="c-breadcrumb__item active">Contatos</div>
        </div>
    </div>
</div>
<div class="container-fluid">
    <div class="l-content-block">
        <div class="row">
            <div class="col-sm-9">
                <main class="c-main-content">
                    <h1 class="o-tit o-tit--22">Contato</h1>
                    <?php if ($contato->getTexto()) { ?>
                        <div role="alert" class="alert alert-warning">
                            <?php echo $contato->getTexto(); ?>
                        </div>
                        <hr class="o-hr">
                    <?php } ?>

                    <?php if ($contato->getTexto2()) { ?>
                        <div role="alert" class="alert alert-warning">
                            <strong class="desize-corpo-12-vermelho">
                                <?php echo $contato->getTexto2(); ?>
                            </strong>
                        </div>
                        <hr class="o-hr">
                    <?php } ?>
                    <div class="row">
                        <div class="col-sm-6">
                            <a id="QueroFazerUmaPergunta" href="<?php echo FORUM_CONSUMIDOR; ?>" class="o-btn o-btn--sm o-btn--primary">Quero fazer uma pergunta</a>
                            <button id="MostrarFormcontato" type="button" class="o-btn o-btn--sm o-btn--secondary">Falar
                                com os editores
                            </button>


                            <?php
                                        Msg::verifica();
                            ?>

                            <form
                                id="formcontato"
                                name="formcontato"
                                action="<?php echo PROJECT_ROOT . 'contatos_ler_db.php'; ?>"
                                method="post"
                                style="display:none">
                                <div class="form-group">
                                    <label for="txtnome">Name</label>
                                    <input type="text" id="txtnome" name="txtnome" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="txtemail">Email</label>
                                    <input type="email" id="txtemail" name="txtemail" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="txttelefone">Telefone</label>
                                    <input type="text" id="txttelefone" name="txttelefone" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="txtestado">Estado</label>
                                    <?php
                                    $select = new Select('txtestado', NULL, 'form-control');
                                    $select->addOption('', 'Selecione ...');
                                    $select->addArray(Ibge_estados::getAll($db));
                                    echo $select->dump();
                                    ?>
                                </div>
                                <div class="form-group">
                                    <label for="txtcidade">Cidade</label>
                                    <?php
                                    $select = new Select('txtcidade', NULL, 'form-control');
                                    $select->addOption('', 'Selecione estado ...');
                                    echo $select->dump();
                                    ?>
                                </div>
                                <div class="form-group">
                                    <label for="txtmensagem">Mensagem</label>
                                    <textarea id="txtmensagem" name="txtmensagem" rows="5"
                                              class="form-control"></textarea>
                                </div>

                                <div class="form-group">
                                    <div class="g-recaptcha"
                                         data-sitekey="<?php echo RECAPCHA_SITEKEY; ?>"></div>
                                </div>

                                <div class="form-group">
                                    <button type="submit" class="o-btn o-btn--sm o-btn--primary">Enviar</button>
                                </div>
                            </form>
                        </div>
                        <!-- Publicidade-->
                        <div class="o-advertising" style="padding-top:2.5em">
                            
                            <!-- central -->
                            
                            <script>
                            </script>
                        </div>
                    </div>
                </main>
            </div>
            <div class="col-sm-3">
                <?php include PROJECT_PATH . 'menu_lateral.inc.php'; ?>
            </div>
        </div>
    </div>
</div>
<?php include PROJECT_PATH . 'footer.inc.php'; ?>
<script src="https://www.google.com/recaptcha/api.js"></script>
<script>
    function MostrarFormcontato() {
        $("#formcontato").show();
        $("#MostrarFormcontato").hide();
        $("#QueroFazerUmaPergunta").hide();
    }
    function mascara(o,f){
        v_obj=o
        v_fun=f
        setTimeout("execmascara()",1)
    }
    function execmascara(){
        v_obj.value=v_fun(v_obj.value)
    }
    function mtel(v){
        v=v.replace(/\D/g,"");             //Remove tudo o que não é dígito
        v=v.replace(/^(\d{2})(\d)/g,"($1) $2"); //Coloca parênteses em volta dos dois primeiros dígitos
        v=v.replace(/(\d)(\d{4})$/,"$1-$2");    //Coloca hífen entre o quarto e o quinto dígitos
        return v;
    }
    $(function () {
        $("#txttelefone").keyup(function () {
            //cidades($(this).val());
            mascara(this, mtel)
        });

        $("#MostrarFormcontato").click(MostrarFormcontato);
        //$("#txttelefone").mask("(99)9999-9999?9");
        $('#txtestado').change(function () {
            cidades($(this).val());
        });
        $("#formcontato").validate({
            submitHandler: function (form) {
                if (grecaptcha.getResponse() == '') {
                    $().toastmessage('showErrorToast', 'Não sou um Robô');
                } else {
                    form.submit();
                }
            },
            rules: {
                txtnome: "required",
                txtemail: {
                    required: true,
                    email: true
                },
                txttelefone: "required",
                txtestado: "required",
                txtcidade: "required",
                txtmensagem: "required"
            },
            messages: {
                txtnome: "Nome Obrigatório",
                txtemail: {
                    required: "E-mail Obrigatório",
                    email: "E-mail inválido"
                },
                txttelefone: "Telefone Obrigatório",
                txtestado: "Estado Obrigatório",
                txtcidade: "Cidade Obrigatório",
                txtmensagem: "Mensagem Obrigatório"
            },
            invalidHandler: function (form, validator) {
                var errors = validator.numberOfInvalids();
                if (errors) {
                    removeToastMsg(toastMessageContato);
                    toastMessageContato = $().toastmessage('showErrorToast', validator.errorList[0].message);
                    //validator.errorList[0].element.focus(); //Set Focus
                    return false;
                }
            },
            errorPlacement: function (error, element) {
            }
        });
    });
</script>
</body>
</html>
