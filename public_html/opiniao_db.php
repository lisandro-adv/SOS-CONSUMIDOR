<?php
require_once dirname(__FILE__) . '/admsite/classes/config.inc.php';
require_once dirname(__FILE__) . '/admsite/classes/formulario_contatos/formulario_contatos.inc.php';
require_once PROJECT_PATH . 'recaptcha/autoload.php';
require_once PROJECT_PATH . 'mail/classtemplatepowerex.php';
require PROJECT_PATH . 'admsite/classes/parametros/parametros.inc.php';
require PROJECT_PATH . 'admsite/classes/ibge/ibge.inc.php';
require PROJECT_PATH . 'admsite/classes/vendor/autoload.php';

$siteKey = RECAPCHA_SITEKEY;
$secret = RECAPCHA_SECRET;
$db = new PDOConfig();
$db->beginTransaction();
try {

    $recaptcha = new \ReCaptcha\ReCaptcha($secret);

    $txtnome = ajustaStringBD(request('txtnome'));
    $estado_id = AjustaInteiroGravar(request('txtestado'));
    $cidade_id = AjustaInteiroGravar(request('txtcidade'));
    $txtmensagem = ajustaStringBD(request('txtmensagem'));
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

    $Ibge_estados_obj = new Ibge_estados($db);
    if ($estado_id > 0) {
        $Ibge_estados_obj->setDados($estado_id);
    }

    $Ibge_cidades_obj = new Ibge_cidades($db);
    if ($cidade_id > 0) {
        $Ibge_cidades_obj->setDados($cidade_id);
    }

    $Opiniao_obj = new Opiniao($db);

    $Opiniao_obj->save(0,
        $txtnome,
        $txtmensagem,
        $cidade_id,
        $estado_id,
        0,
        Opiniao::ORIGEM_OPINIAO);

    /**
     * Envia E-mail
     */

    $data = date("d/m/Y");
    $hora = date("H:i");
    $ip = $_SERVER["REMOTE_ADDR"];


    $parametros = new Parametros($db);
    $parametros->setDados(PARAMETROS);
    $addReplyTo = $parametros->getEmail();

    $msg = EmailTeamplate::opiniao(new TemplatePower(PROJECT_PATH . 'mail/opiniao.html'), Opiniao::getOrigemLabel(Opiniao::ORIGEM_OPINIAO), $txtnome, $Ibge_cidades_obj->getNome(), $Ibge_estados_obj->getNome(), $txtmensagem, PROJECT_ROOT);


    if (!Email::enviar('Uma nova Opinião sobre o site', $addReplyTo, 'Uma nova Opinião sobre o site', $addReplyTo, $addReplyTo, $msg)) {
        throw new Exception('Não foi possivel enviar o E-mail o processo foi cancelado, tente novamente.');
    }
    /**
     * FIM Envia E-mail
     */


    $db->commit();
    Msg::add("Dados gravados com sucesso");
} catch (Exception $e) {
    $db->rollBack();
    #die($e->getMessage());
    Msg::add($e->getMessage());
}
redirect(INSTITUCIONAL_LER . '?page=' . AjustaInteiroGravar(request('page')));