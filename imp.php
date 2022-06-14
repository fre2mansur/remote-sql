<?php
$data = ['tab', 'col', 'sepC', 'sepR', 'ignoreH', 'ignoreF', 'pk', 'del', 'merge', 'stop', 'rmEnc', 'NL'];
include 'inc.page.php';
$SIDU['navi'] = -1;
head($SIDU);
main($SIDU);
foot($SIDU);

function main($SIDU) {
    $imp = $SIDU['data'];
    if (!$imp['cmd']) {
        $imp['rmEnc'] = $imp['NL'] = $imp['stop'] = $imp['ignoreH'] = 1;
    }
    $tab = explode(',', $imp['tab']);
    $SIDU[4] = $tab[0];
    $err = !$SIDU[1] ? lang(2201) : ($SIDU['eng'] == 'pgsql' && !$SIDU[2] ? lang(2202) : ($SIDU[4] ? '' : lang(2219)));
    echo NL .'<form action="imp.php?id='. "$SIDU[0],$SIDU[1],$SIDU[2]" .'" method="post" enctype="multipart/form-data">';
    echo NL .'<p class="dot"><b>', lang(2203) ,': <i class="red">Table = ', $SIDU[1] , ($SIDU[2] ? ".$SIDU[2]" : '') , ".$SIDU[4]" ,'</i></b></p>';
    if ($err) return print('<p class="err">'. $err .'</p></div></form>');
    $res = $SIDU['dbL']->query('SELECT * FROM '. sidu_keyw($SIDU[4]) .' LIMIT 1');
    $num = $res->columnCount();
    for ($i = 0; $i < $num; $i++) {
        $meta = $res->getColumnMeta($i);
        $imp['cols'][$meta['name']] = $meta['name'];
    }
    if (!$imp['col']) $imp['col'] = implode(NL ,$imp['cols']);
    if ($imp['cmd']) {
        $err = valid_data($SIDU, $imp);
        if ($err) echo '<p class="err">', $err ,'</p>';
        else return save_data($SIDU, $imp);
    }
    echo NL .'<p>', cms_form('textarea', 'col', $imp['col'], array('style'=>'height:100px')) ,'<br>', lang(2231);
    echo NL ,'<br>', lang(2207), ': <input type="file" name="f"></p>';
    echo NL .'<p class="dot"><br><b>', lang(2209) ,':</b></p>';
    $w50 = array('style'=>'width:50px');
    echo NL .'<p class="dot">', lang(2210) ,': ', cms_form('text', 'sepC', ($imp['sepC'] ?: ','), $w50) ,' <i class="green">eg \t , ; « ||| »</i>';
    echo NL .'<br>', lang(2211) ,': ', cms_form('text', 'sepR', ($imp['sepR'] ?: '\n'),   $w50) ,' <i class="green">eg \n</i>';
    echo NL .'<br>', lang(2232) ,' ',  cms_form('text', 'ignoreH', ceil($imp['ignoreH']), $w50) ,' ', lang(2233) ,'; &nbsp; ';
    echo NL . cms_form('text', 'ignoreF', ceil($imp['ignoreF']), $w50) ,' ', lang(2234);
    echo NL ,'<br>', lang(2214) ,': ', cms_form('text', 'pk', $imp['pk'], array('style'=>'width:200px', 'placeholder'=>'eg pk1;pk2'));
    echo NL .'<br>', cms_form('checkbox', 'rmEnc', $imp['rmEnc'], lang(2212));
    echo NL ,'<br>', cms_form('checkbox', 'NL', $imp['NL'], lang(2213));
    echo NL ,'<br>', cms_form('checkbox', 'del', $imp['del'], '<i class="red">'. lang(2215) .'</i>');
    echo NL .'<br>', cms_form('checkbox', 'merge', $imp['merge'], '<i class="green">'. lang(2216) .'</i>');
    echo NL .'<br>', cms_form('checkbox', 'stop', $imp['stop'], lang(2217)) ,'</p>';
    echo NL .'<p class="show" data-src="next">', cms_form('submit', 'cmd', lang(2218), array('class'=>'hideP')) ,'</p>';
    echo NL .'<p class="ac hide"><img src="loading.gif"><br><br><span class="green">', lang(2225) ,'</span><br><br><span class="red">', lang(2226) ,'</span></p>';
    echo cms_form('hidden', 'tab', $SIDU[4]);
    echo cms_form('end');
}
function valid_data($SIDU, &$imp) {
    $imp['sepC'] = trim($imp['sepC']);
    if (!$imp['sepC']) $imp['sepC'] = ',';
    if (!$SIDU[4]) return lang(2219);
    $col = explode(NL, $imp['col']);
    foreach ($col as $c) {
        $c = trim($c);
        if ($c) {
            if (!in_array($c, $imp['cols'])) return lang(2220, $c);
            $arr[] = $c;
        }
    }
    $imp['col'] = implode(NL, $arr);
    if (!$imp['col']) $imp['col'] = implode(NL ,$imp['cols']);
    $arr = explode(NL, $imp['col']);
    $imp['pk'] = cms_clean_str($imp['pk'], 1, 1, 1);
    if ($imp['pk']) {
        $arrPK = explode(';', $imp['pk']);
        foreach ($arrPK as $k => $v) {
            $v = trim($v);
            if ($v == '') unset($arrPK[$k]);
            elseif (!in_array($v, $arr)) return lang(2221, $v);
        }
        $imp['pk'] = implode(';', $arrPK);
    }
    if ($_FILES['f']['error'] || !$_FILES['f']['tmp_name']) return lang(2224);
    //if (substr($_FILES['f']['type'], 0, 4) != 'text') return lang(2222);
    $tran2 = array('\r'=>"\r", '\n'=>"\n");
    $imp['sepC2']= strtr($imp['sepC'], $tran2);
    $imp['file'] = explode(strtr($imp['sepR'], $tran2), trim(file_get_contents($_FILES['f']['tmp_name'])));
}
function save_data($SIDU, $imp) {
    if ($imp['del']) save_data_sql_run($SIDU['dbL'], 0, 'DELETE FROM '.sidu_keyw($SIDU[4]), $imp['stop']);
    $cols = explode(NL, $imp['col']);
    $pk = $imp['pk'] ? explode(';', $imp['pk']) : array();
    $arrPK = array();
    foreach ($cols as $i => $c) {
        if (in_array($c, $pk)) $arrPK[] = $i;
        $cols[$i] = sidu_keyw($c);
    }
    $sql = !$imp['pk'] ? 'INSERT INTO ' : 'UPDATE ';
    $tran = array('""'=>'"', "'"=>"''"); // , '\r\n'=>NL, '\n'=>NL, '\t'=>"\t"
    $add_slash = sidu_slash($SIDU['eng']);
    if ($add_slash) $tran['\\'] = '\\\\';
    if ($imp['NL']) {
        for ($i = 1; $i < 10; $i++) {
            $tran[str_repeat('\\', $i).'r'.str_repeat('\\', $i).'n'] = NL;
            $tran[str_repeat('\\', $i).'n'] = NL;
            $tran[str_repeat('\\', $i).'t'] = "\t";
            $tran[str_repeat('\\', $i).'"'] = '"';
            $tran[str_repeat('\\', $i).'""']= '"';
            $tran[str_repeat('\\', $i)."'"] = "''";
            $tran[str_repeat('\\', $i).'\\']= $add_slash ? '\\\\' : '\\';
        }
    }
//    $tran = !$imp['NL'] ? array() : array('\n'=>NL, '\r'=>"\r", '\\\n'=>NL, '\\\r'=>"\r", "\\'"=>"''", "\\\\\\'"=>"''", "\\\\\\\\\\\\'"=>"''", '\\"'=>'"', '\\\\\\"'=>'"', '\\\\\\\\\\\\"'=>'"');
    if ($SIDU['eng'] == 'mysql') $sql .= '`'. $SIDU[1] .'`.`'. $SIDU[4] .'`';
    elseif ($SIDU['eng'] == 'pgsql') $sql .= '"'. $SIDU[2] .'"."'. $SIDU[4] .'"';
    elseif ($SIDU['eng'] == 'sqlite' || $SIDU['eng'] == 'cubrid') $sql .= '"'. $SIDU[4] .'"';
    else $sql .= $SIDU[4];
    $sql .= $imp['pk'] ? ' SET ' : '('. implode(',', $cols) .') VALUES '. NL;
    $numCM = $numC = count($cols);
    if (!$imp['merge']) $numCM++;
    $numR = count($imp['file']);
    $numL = 200; // each insert max lines
    $numIns = 0;
    $ignoreH = ceil($imp['ignoreH']); if ($ignoreH < 0) $ignoreH = 0;
    $ignoreF = ceil($imp['ignoreF']); if ($ignoreF < 0) $ignoreF = 0;
    $numR -= $ignoreF;
    for ($i = $ignoreH; $i < $numR; $i++) {
        $txt = trim($imp['file'][$i]);
        if ($txt) save_data_sql($SIDU['dbL'], $i, $SQL, $imp, $txt, $numCM, $numC, $arrPK, $cols, $numIns, $numL, $sql, $tran);
    }
    if (!$imp['pk'] && $SQL){
        if (substr($SQL, -2) == ",\n") $SQL = substr($SQL, 0, -2);
        if ($SQL) save_data_sql_run($SIDU['dbL'], $i, $SQL, $imp['stop']);
    }
    echo NL .'<br><p class="ok">', lang(2227) ,'</p><p>Total '. ($i - $ignoreH) .' lines imported</p>';
}
function save_data_sql($dbL, $i, &$SQL, $imp, $txt, $numCM, $numC, $arrPK, $cols, &$numIns, $numL, $sql, $tran) {
    if (strlen($imp['sepC2']) > 1) {
        $arr = explode($imp['sepC2'], $txt, $numCM);
    } else { //php str_getcsv only limit to 1char delimiter
        $arr = str_getcsv($txt, $imp['sepC2']);
        if ($imp['merge']) {
            foreach ($arr as $j => $v) {
                if ($j >= $numC) $arr[$numC - 1] .= $imp['sepC2'] . $v;
            }
        }
    }
    foreach ($arr as $j => $v) $arr[$j] = strtr(trim($v, " \r\n" . ($imp['rmEnc'] ? "\"'" : '')), $tran);
    for ($k = 0; $k < $numC; $k++) {
        $v = isset($arr[$k]) ? $arr[$k] : '';
        $v = (strtoupper($v) == 'NULL') ? 'NULL' : "'".$v."'"; //this will be bug if real text=='null'
        if (!$imp['pk']) $arrD[] = $v;
        elseif (in_array($k, $arrPK)) $arrWhere[] = $cols[$k] . ($v == 'NULL' ? ' IS ' : '=') . $v;
        else $arrD[] = $cols[$k] .'='. $v;
    }
    if (!$imp['pk']) {
        $numIns++;
        if (!$SQL) $SQL = $sql;
        $SQL .= '('. implode(',', $arrD) .')';
        if ($numIns == $numL) {
            save_data_sql_run($dbL, $i, $SQL, $imp['stop']);
            $SQL = '';
            $numIns = 0;
        } else $SQL .= ",\n";
    } else {
        save_data_sql_run($dbL, $i, $sql.implode(',', $arrD).' WHERE '.implode(' AND ', $arrWhere), $imp['stop']);
    }
}
function save_data_sql_run($dbL, $i, $sql, $stop) {
    $dbL->query($sql);
    $err = sidu_err(1);
    if ($err) {
        echo NL .'<p class="err">', lang(2228, $i), '<br>', $err ,'</p><pre>', cms_html8($sql) ,'</pre><br>';
        if ($stop) die('<br><p class="err">'. lang(2229) .'</p><p class="ok">'. lang(2230) .'</p>');
    }
}
