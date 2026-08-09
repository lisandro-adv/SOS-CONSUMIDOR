<?php

if (LoginFront::verificaRecadastramento()) {
    Msg::add('Atualize seu cadastro');
    redirect(FORUM_CONSUMIDOR_CADASTRO);
}
if (LoginFrontAdv::verificaRecadastramento()) {
    Msg::add('Atualize seu cadastro');
    redirect(FORUM_ADVOGADO_CADASTRO);
}



