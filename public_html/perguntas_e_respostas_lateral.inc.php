<?php

if (!isset($home)) {
    $style_fonte = 'style="font-weight: normal"';
} else {
    $style_fonte = "";
}
?>

<h3 class="o-tit o-tit--22 o-tit--upper">Perguntas e Respostas</h3>
<ul class="o-links-list o-links-list--consumer">
    <?php foreach ($perguntas_e_respostas_array as $perguntas_e_resposta_array):
        $area_obj = new PerguntasERespostasAreas($db);
        $area_obj->setDados($perguntas_e_resposta_array['area_id']);

        $grupo_obj = new PerguntasERespostasGrupo($db);
        $grupo_obj->setDados($area_obj->getGrupoId());

        $link = PerguntasERespostas::getURL($perguntas_e_resposta_array['pergunta'], $perguntas_e_resposta_array['id']);
    ?>
    <li class="o-links-list__item">
        <a <?php echo $style_fonte; ?> href="<?php echo $link; ?>"><?php echo $perguntas_e_resposta_array['pergunta']; ?></a>
    </li>
    <?php endforeach; ?>
</ul>
<a href="<?php echo PERGUNTAS_E_RESPOSTAS_MAIS; ?>" class="o-btn o-btn--sm o-btn--secondary">Ver mais perguntas e respostas</a>