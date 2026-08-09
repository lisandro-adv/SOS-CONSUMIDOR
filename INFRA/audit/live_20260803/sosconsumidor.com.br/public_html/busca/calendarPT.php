<?php

function Calendar($dataPesquisa) {

    $month = filter_var(get('month'), FILTER_VALIDATE_INT, array(
        'options' => array('min_range' => 1, 'max_range' => 12)
    ));
    $year = filter_var(get('year'), FILTER_VALIDATE_INT, array(
        'options' => array('min_range' => 1970, 'max_range' => ((int) date('Y')) + 10)
    ));

    // Usa somente o caminho local, sem confiar no cabecalho Host enviado pelo visitante.
    $url = htmlspecialchars(
        isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/busca/',
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );

    if (isset($dataPesquisa) && $dataPesquisa != '') {
        list ($Oano, $Omes, $Odia ) = explode("-", $dataPesquisa);
    } else {
        $Oano = date('Y');
        $Omes = date('m');
        $Odia = date('d');
    }
#echo "$DataPesquisa --- $Oano, $Omes, $Odia ";

    if ($month === false || $month === null)
        $month = (int) date('m');

    if ($year === false || $year === null)
        $year = (int) date('Y');

#-------------------------------------------#
#--------------- CONFIGURACAO --------------#
    /* Largura da tabela */
    $tab_largura = 200;
    $tab_alinhar = "center";
    /* Borda */
    $borda_ = 1;
    /* Cor da celula no final de semana */
    $CorFindi = "#E0E0E0";
    /* Cor da celula durante a semana */
    $CorSemana = "#EAEAEA";
    /* Cor do dia */
    $CorDia = "#CC0000"; //"#C1C1C1";
    /* Cor do dia selecionado */
    $CorDiaSelecionado = "#2C7798";

    /* Cor do dia da semana  D, S, T, Q, Q, S, S */
    $txtdia = "#FFFFFF";
    /* Altura da celula */
    $altura = "25";
    /* Posicao do dia dentro da celula */
    $posicao = "middle";
    /* Alinhamento do dia dentro da Celula */
    $alinhamento = "center";



    /* Grab the month and year */
    if ($month == "" || $year == "") {
        $this_month = date("m");
        $month_name = date("F");
        $this_year = date("Y");
    } else {
        $this_month = date("m", mktime(0, 0, 0, $month, 1, $year));
        $month_name = date("F", mktime(0, 0, 0, $month, 1, $year));
        $this_year = date("Y", mktime(0, 0, 0, $month, 1, $year));
    }
    //Coloca em portugues o nome do mes 
    switch ($month_name) {
        case "January":
            $portuguese_month = "Janeiro";
            break;
        case "February":
            $portuguese_month = "Fevereiro";
            break;
        case "March":
            $portuguese_month = "Mar&ccedil;o";
            break;
        case "April":
            $portuguese_month = "Abril";
            break;
        case "May":
            $portuguese_month = "Maio";
            break;
        case "June":
            $portuguese_month = "Junho";
            break;
        case "July":
            $portuguese_month = "Julho";
            break;
        case "August":
            $portuguese_month = "Agosto";
            break;
        case "September":
            $portuguese_month = "Setembro";
            break;
        case "October":
            $portuguese_month = "Outubro";
            break;
        case "November":
            $portuguese_month = "Novembro";
            break;
        case "December":
            $portuguese_month = "Dezembro";
            break;
    } //Fim CASE

    /* This is for the navigation */
    if ($month == 1) {
        $last_month = 12;
    } else {
        $last_month = $this_month - 1;
    }
    if ($month == 12) {
        $next_month = 1;
    } else {
        $next_month = $this_month + 1;
    }
    /* Same with all this stuff */
    if ($last_month == 12)
        $last_year = $this_year - 1;
    else
        $last_year = $this_year;

    /* DITTO!!! */
    if ($next_month == 1)
        $next_year = $this_year + 1;
    else
        $next_year = $this_year;

    echo "<div id=\"calendar_pt\">";
    echo "<table border=\"0\" width=\"$tab_largura\" cellspacing=\"0\" cellpadding=\"0\" align=$tab_alinhar >";
    echo "  <tr>";
    echo "    <td bgcolor=\"#FFFFFF\" valign=\"top\">";


    echo " <table  width=\"100%\" align=\"center\" border=\"0\" bordercolor=\"#000000\" cellpadding=\"0\" cellspacing=\"$borda_\" bgcolor=\"#FFFFFF\">\n"
    . "<tr bgcolor=\"#FFFFFF\" >\n"
    . "<th align=\"left\" width=\"10%\" bgcolor=\"#FFFFFF\" class=\"mes_calendar\"><A class=\"link_calendario_dia\" href=\"$url?limpa_sql=1&month=$last_month&year=$last_year\"><img src=\"" . PROJECT_ROOT . "busca/ico_anterior.gif\" border=\"0\"></a></th>\n"
#    . "<th align=\"left\" width=\"10%\" bgcolor=\"#ffffff\" class=\"data_noticia\"><A class=\"link_calendario_dia\" href=\"#\" onclick=\"CalendarPassarMes($last_month,$last_year,'$Area');\"><img src=\"img/ico_anterior.gif\" border=\"0\"></a></th>\n"
#    . "<th align=\"left\" width=\"10%\" bgcolor=\"#ffffff\" class=\"data_noticia\"><A class=\"link_calendario_dia\" href=\"$url?month=$last_month&year=$last_year\"><img src=\"img/ico_anterior.gif\" border=\"0\"></a></th>\n"
    . "<th colspan=5 width=\"80%\" bgcolor=\"#FFFFFF\" class=\"mes_calendar\"><div align=center><div id=\"titulo_h3\">$portuguese_month, $this_year</div></div></th>\n"
#    . "<th align=\"right\" width=\"10%\" bgcolor=\"#ffffff\" class=\"data_noticia\"><A class=\"link_calendario_dia\" href=\"#\" onclick=\"CalendarPassarMes($next_month, $next_year,'$Area');\"><img src=\"img/ico_seguinte.gif\" border=\"0\"></a></th>\n"
    . "<th align=\"right\" width=\"10%\" bgcolor=\"#FFFFFF\" class=\"mes_calendar\"><A class=\"link_calendario_dia\" href=\"$url?limpa_sql=1&month=$next_month&year=$next_year\"><img src=\"" . PROJECT_ROOT . "busca/ico_seguinte.gif\" border=\"0\"></a></th>\n"

#    . "<th align=\"right\"  width=\"10%\" bgcolor=\"#ffffff\" class=\"data_noticia\"><A class=\"link_calendario_dia\" href=\"$url?month=$next_month&year=$next_year\"><img src=\"img/ico_siguiente.gif\" border=\"0\"></a></th>\n"
    . "</tr>\n"
    . "<tr bgcolor=\"#2C7798\">\n"
    . "<th width=\"15%\"  height=\"25\"><div align=center><font color=\"$txtdia\">D</div></th>\n"
    . "<th width=\"14%\"><div align=center><font color=\"$txtdia\">S</font></div></th>\n"
    . "<th width=\"14%\"><div align=center><font color=\"$txtdia\">T</font></div></th>\n"
    . "<th width=\"14%\"><div align=center><font color=\"$txtdia\">Q</font></div></th>\n"
    . "<th width=\"14%\"><div align=center><font color=\"$txtdia\">Q</font></div></th>\n"
    . "<th width=\"14%\"><div align=center><font color=\"$txtdia\">S</font></div></th>\n"
    . "<th width=\"15%\"><div align=center><font color=\"$txtdia\">S</font></div></th>\n"
    . "</tr>\n";

    /* Grab the first day of the month, and total days */
    $first_day = date("w", mktime(0, 0, 0, $this_month, 1, $this_year));
    $total_days = date("t", mktime(0, 0, 0, $this_month, 1, $this_year));
    /* Start on the day of the first week */
    $week_num = 1;
    $day_num = 1;
    /* While the day of the week isn't '7' */
    while ($week_num <= 6) {
        echo "<tr >\n";
        /* Loop through the week days */
        for ($i = 0; $i <= 6; $i++) {
            /* If it's the first week then... */
            if ($week_num == 1) {
                /* If it's not the first day yet, then use a space */
                if ($i < $first_day)
                    $the_day = " ";
                /* If it is the first day, then start with a '1' */
                else if ($i == $first_day) {
                    $the_day = 1;
                }
            } else {
                /* If we're past the total days, then use spaces */
                if ($the_day > $total_days)
                    $the_day = " ";
            }
            /* Display the day (or space) */
            if ($the_day == " ") {
                echo "<TD align=\"$alinhamento\" bgcolor=\"#F7F7F7\" height=\"$altura\" valign=\"$posicao\" class=\"calendar\"><a href=\"#\" class=\"link_calendario_dia\"></a></td>\n";
                //echo "<TD bgcolor=\"#F1F1F1\" height=\"$altura\" valign=\"top\" >z&nbsp;</td>\n";
            } else {

                # Coloca todos os campos com 2 digito 
                if ($the_day < 10)
                    $the_day = "0" . $the_day;

                #Data de Hoje                      
                $data_now = date("Y-m-d");
                #Data do Loop  
                $data_loop = $this_year . "-" . $this_month . "-" . $the_day;

                $cor_texto = "";
                $cor_texto_fecha = "";

                #Compara as datas e marca o dia atual no Caleand�rio
                //--- dia do mes
                if ($data_now == $data_loop) {
                    $cor = $CorDia;
                    $cor_texto = "<font color=\"$txtdia\">";
                    $cor_texto_fecha = "</font>";
                } else
                    $cor = $CorSemana;

                //--- dia selecionado
                if ($Odia == $the_day) {
                    $cor = $CorDiaSelecionado;
                    $cor_texto = "<font color=\"$txtdia\">";
                    $cor_texto_fecha = "</font>";
                }

                #Cor Domingo
                if ($i == 0 || $i == 6) {
                    $cor = $CorFindi;
                    $cor_texto = "<font color=\"#555555\">";
                    $cor_texto_fecha = "</font>";
                }
                echo "<td align=\"$alinhamento\" bgcolor=\"$cor\" height=\"$altura\" valign=\"$posicao\" class=\"calendar\">\n";
                #Final de semana
                if ($i == 0 || $i == 6)
                    echo "<a class=\"link_calendario_dia\" href=\"$url?limpa_sql=1&dataPesquisa=$data_loop&&month=$month&year=$year\">$cor_texto $the_day $cor_texto_fecha</a>";
                else
                    echo "<a class=\"link_calendario_dia\" href=\"$url?limpa_sql=1&dataPesquisa=$data_loop&&month=$month&year=$year\">$cor_texto $the_day $cor_texto_fecha</a>";
                echo" </td>\n";
            }
            /* Incrememnt the day of the month */
            if ($the_day != " ")
                $the_day++;
        }
        echo "</tr>\n";
        /* Increment the week number */
        $week_num++;
    }
    /* Finish off with closing out our tags */
    echo " </table>\n";

    echo "</td>";
    echo "</tr> ";
    echo "</table>";
    echo "</div>";
}

//Fim Funcao
?>
		
