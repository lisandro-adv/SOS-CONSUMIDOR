<?php
require dirname(__FILE__) . '/include/config_font.inc.php';
require PROJECT_PATH . 'admsite/classes/ibge/ibge.inc.php';
require PROJECT_PATH . 'admsite/classes/forum_advogado/forum_advogado.inc.php';
require_once PROJECT_PATH . 'mail/classtemplatepowerex.php';
require PROJECT_PATH . 'admsite/classes/parametros/parametros.inc.php';
require PROJECT_PATH . 'admsite/classes/vendor/autoload.php';
require_once PROJECT_PATH . 'recaptcha/autoload.php';


$siteKey = RECAPCHA_SITEKEY;
$secret = RECAPCHA_SECRET;
$db = new PDOConfig();
$adv_id = AjustaInteiroGravar(request('adv_id'));
$page = (AjustaInteiroGravar(request('page')) > 0) ? AjustaInteiroGravar(request('page')) : 1;
$retorno = PESQUISA_ADV . '?page=' . $page;
try {

    ##################################################
    #######             ReCaptcha              #######
    ##################################################
    $recaptcha = new \ReCaptcha\ReCaptcha($secret);
    $g_recaptcha_response = request('g-recaptcha-response');


    if (!isset($_POST['g-recaptcha-response'])) {
        //da erro e sai do script
        throw new Exception('Captcha inválida.');
    }

    $resp = $recaptcha->verify($g_recaptcha_response, $_SERVER['SERVER_NAME']);
    if (!$resp->isSuccess()) {
        //da erro e sai do script
        throw new Exception('Captcha inválida.');
    }
    ##################################################
    ##################################################
    ##################################################

    $cadatro_adv = new Cadastro($db);

    $cadatro_adv->setDados($adv_id);

    $nome_completo = $cadatro_adv->getNomeCompleto();
    $txtemail = $cadatro_adv->getEmail();
    #$txtemail = 'woicickoskiroberto@gmail.com';
    ##################################################
    #######             E-mail                 #######
    ##################################################
    $Template = new TemplatePower(PROJECT_PATH . 'mail/msg_generica.html');

    $data = date("d/m/Y");
    $hora = date("H:i");
    $ip = $_SERVER["REMOTE_ADDR"];

    $txtmensagem = ajustaTextAreaBD(request('txtmensagem'));

    $emailReplyTo = ajustaStringBD(request('email'));
    $nomeReplyTo = ajustaStringBD(request('nome'));

    #var_dump($Template);

    $msg = EmailTeamplate::msgGenerica($Template, $hora, $data, $ip, $nomeReplyTo, $emailReplyTo, $txtmensagem, PROJECT_ROOT);


    #die(Email::enviar('Cadastro - ' . $nome_completo, $txtemail, $nome_completo, $addReplyTo, $addReplyTo, $msg));
    if (Email::enviar('Cadastro - ' . $nome_completo, $txtemail, $nome_completo, $emailReplyTo, $nomeReplyTo, $msg)) {
        Msg::add('E-mail enviado com sucesso');
    } else {
        throw new Exception('Não foi possivel enviar o E-mail, tente novamente.');
    }


} catch (Exception $e) {
    Msg::add($e->getMessage());
}
redirect($retorno);