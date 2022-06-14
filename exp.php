<?php
ini_set('max_execution_time', 0); //no limit
$data = ['sql', 'tab', 'db', 'drop', 'desc', 'data', 'ext', 'zip', 'where', 'sepC', 'sepR', 'col', 'sql_slash', 'clean', 'null', 'csvHead', 'fname'];
include 'inc.page.php';
$SIDU['navi'] = -1;
main($SIDU);

function main($SIDU) {
    $mode = "DB = $SIDU[1]". ($SIDU[2] ? ".$SIDU[2]" : '');
    if ($SIDU['data']['sql']) $SIDU['data']['tabs'][0] = $mode = 'SQL';
    valid_data($SIDU);
    if ($SIDU['data']['cmd']) main_cout($SIDU, $mode);
    else main_form($SIDU, $mode);
}
function main_cout_str($str, $fp, $is_data = 0) {
    if ($fp) fwrite($fp, $str);
    else echo $is_data ? cms_html8($str) : $str;
}
function main_cout($SIDU, $mode) {
    $exp = $SIDU['data'];
    $file  = $exp['fname'] ?: ($mode == 'SQL' ? 'sidu-sql' :
        str_replace('/', '_', $SIDU[1]) . ($SIDU[2] ? "_$SIDU[2]" : '')
        . (isset($exp['sql'][1]) ? '' : '_'.$exp['tabs'][0])
    );
    $file .= '_'. date('Ymd_His.') . $exp['ext'];
    $fp = $exp['zip'] ? fopen('/tmp/'. $file, 'w') : '';
    if (!$exp['zip'] || $exp['ext'] == 'html') main_cout_str('<html>'.NL.'<head>'.NL.'<meta charset="utf-8">'.NL.'<title>SIDU Export: '.$file.'</title>'.NL.'<style>*{font-family:monospace}'.NL.'.r{text-align:right}', $fp);
    if ($exp['ext'] == 'html') main_cout_str(NL.'.n{color:#888;font-style:italic}'.NL.'.th td{background:#ddd}'.NL.'td{vertical-align:top;border:solid 1px #ccc}', $fp);
    if (!$exp['zip'] || $exp['ext'] == 'html') main_cout_str(NL.'</style>'.NL.'</head>'.NL.'<body><pre>', $fp);
    if ($exp['ext'] != 'csv') main_cout_str(NL.'/*SIDU Export Start-------------------'.date('Y-m-d H:i:s').'*/', $fp);
    if ($mode != 'SQL') {
        if ($exp['db']) {
            if ($SIDU['eng'] == 'mysql') main_cout_str(NL.'USE '.sidu_keyw($SIDU[1]).';', $fp);
            elseif ($SIDU['eng'] == 'pgsql') main_cout_str(NL.'SET search_path to '.sidu_keyw($SIDU[2]).';', $fp);
        }
        if ($exp['drop']) {
            foreach ($exp['tabs'] as $t) {
                if (($SIDU['eng'] != 'sqlite' && $SIDU['eng'] != 'cubrid') || $t != 'sqlite_master') main_cout_str(NL.'DROP '.($SIDU[3]=='r' ? 'TABLE ' : 'VIEW ').sidu_keyw($t).';', $fp);
            }
            main_cout_str(NL, $fp);
        }
        if ($exp['desc']) {
            $typ = ($SIDU[3] == 'r') ? 'TABLE' : 'VIEW';
            foreach ($exp['tabs'] as $t) main_cout_desc($SIDU, $typ, $t, $fp, $exp['sql_slash']);
            main_cout_str(NL, $fp);
        }
    }
    if (!$exp['data']) return main_cout_str(NL.'/*SIDU Export End-------------------*/</pre>'.NL.'</body></html>', $fp);
    if ($exp['ext'] == 'html') main_cout_str('</pre>', $fp);
    $rep_clean = array();
    foreach ($exp['clean'] as $v) {
        if ($v == 1) {
            for ($i = 1; $i < 10; $i++) {
                $rep_clean[str_repeat('\\', $i).'r'.str_repeat('\\', $i).'n'] = NL;
                $rep_clean[str_repeat('\\', $i).'n'] = NL;
            }
        } elseif ($v == 2) {
            for ($i = 1; $i < 10; $i++) $rep_clean[str_repeat('\\', $i).'t'] = "\t";
        } elseif ($v == 3) {
            for ($i = 1; $i < 10; $i++) $rep_clean[str_repeat('\\', $i)."'"] = "'";
        } elseif ($v == 4) {
            for ($i = 1; $i < 10; $i++) $rep_clean[str_repeat('\\', $i).'"'] = '"';
        } elseif ($v == 5) {
            for ($i = 1; $i < 10; $i++) $rep_clean[str_repeat('\\', $i).'\\'] = '\\';
        }
    }
    foreach ($exp['sql'] as $i => $v) {
        //if ($exp['ext'] == 'html') main_cout_str(NL.NL.($exp['ext'] == 'html' ? '<br>' : '/* ').nl2br(($exp['zip'] && $exp['ext'] != 'html' ? $v : cms_html8($v)), 0).($exp['ext'] == 'html' ? '' : ' */').NL, $fp);
        main_cout_data2($SIDU, $exp, $v, $exp['tabs'][$i], $fp, $rep_clean);
    }
    // main_cout_str("\n".($exp['ext']=='html' ? '<p>' : '').'/*SIDU Export End-------------------*/'.($exp['ext']=='html' ? '</p>' : ''),$fp);
    if ($exp['ext'] != 'html' && !$exp['zip']) main_cout_str(NL.'</pre>', $fp);
    if ($exp['ext'] == 'html' || !$exp['zip']) main_cout_str(NL.NL.'</body></html>', $fp);
    if (!$fp) return;
    fclose($fp);
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        $tmp = $file;
        $file .= '.zip';
        if ($zip->open('/tmp/'. $file, ZIPARCHIVE::CREATE) !== true) return;
        $zip->addFile('/tmp/'. $tmp, $tmp);
        $zip->close();
    }
    header('Expires: 0');
    header('Content-Description: File Transfer');
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="'. $file .'"');
    $fp = fopen('/tmp/'. $file, 'rb');
    if ($fp) {
        while(!feof($fp)) {
            print(fread($fp, 1024 * 8));
            flush();
            if (connection_status() != 0) {
                fclose($fp);
                die();
            }
        }
        fclose($fp);
    }
}
function main_cout_desc($SIDU, $typ, $tab, $fp, $sql_slash) {
    if ($typ == 'VIEW') {
        if ($SIDU['eng'] == 'mysql') {
            $sql = sidu_val("SELECT VIEW_DEFINITION FROM information_schema.VIEWS\nWHERE TABLE_SCHEMA='$SIDU[1]' AND TABLE_NAME='$tab'");
            $sql = trim(str_replace('/* ALGORITHM=UNDEFINED */', '', $sql));
            main_cout_str(NL.'CREATE VIEW '.sidu_keyw($tab).' AS '.$sql.';', $fp);
        } elseif ($SIDU['eng'] == 'pgsql') {
            $oid = sidu_val("SELECT a.oid FROM pg_class a,pg_namespace b\nWHERE a.relkind='v' AND a.relnamespace=b.oid\nAND a.relname='$tab' AND b.nspname='$SIDU[2]'");
            $sql = sidu_val("SELECT pg_get_viewdef($oid)");
            main_cout_str(NL.'CREATE VIEW '.sidu_keyw($tab).' AS '.$sql, $fp);
        } elseif ($SIDU['eng'] == 'sqlite') {
            $sql = sidu_val("SELECT sql FROM sqlite_master WHERE type='view' AND name='$tab'");
            main_cout_str(NL.$sql.';', $fp);
        } // cb not available yet
        return;
    }
    if ($SIDU['eng'] == 'pgsql') {
        exp_tab_desc_pgsql($SIDU, $tab, $sql_slash, $fp);
        return;
    }
    if ($SIDU['eng'] != 'mysql' && $SIDU['eng'] != 'sqlite') return; // cubrid not ready yet
    if ($SIDU['eng'] == 'mysql') {
        $desc = sidu_row("SHOW CREATE TABLE `$SIDU[1]`.`$tab`");
        $desc = array_pop($desc);
    } elseif ($SIDU['eng'] == 'sqlite') {
        $desc = sidu_val("SELECT sql FROM sqlite_master WHERE name=tbl_name AND name='$tab' LIMIT 1");
    }
    main_cout_str(NL.$desc.';', $fp);
    if ($SIDU['eng'] == 'sqlite') {
        $arr = sidu_enum("SELECT sql FROM sqlite_master WHERE type='index' AND tbl_name='$tab' AND sql IS NOT NULL");
        foreach ($arr as $s) main_cout_str(NL.$s.';', $fp);
    }
}
function main_cout_data2($SIDU, $exp, $sql, $tab, $fp, $rep_clean) {
    $pos = stripos($sql, ' FROM '); //if newline etc not working :D
    if (strtoupper(substr($sql, 0, 6)) == 'SELECT' && !stripos($sql, 'limit ') && $pos) {
        $num = sidu_val('SELECT count(*)'. substr($sql, $pos));
        for ($i = 0; $i <= $num; $i += 1000) {
            $limit = ' LIMIT '. ($SIDU['eng'] == 'cubrid' ? ($i ? $i.',' : '') . 1000 : 1000 . ($i ? ' OFFSET '.$i : ''));
            main_cout_data($SIDU, $exp, $sql . $limit, $tab, $fp, $rep_clean, $i);
        }
    } else {
        main_cout_data($SIDU, $exp, $sql, $tab, $fp, $rep_clean, 0);
    }
    if ($exp['ext'] == 'html') main_cout_str(NL.'</table>', $fp);
}
function main_cout_data($SIDU, $exp, $v, $tab, $fp, $rep_clean, $pgno = 0) {
    $res = $SIDU['dbL']->query($v);
    $err = sidu_err(1);
    if ($err) return main_cout_str(NL.($exp['ext'] == 'html' ? '' : '/* ').'<i style="color:red">'.$err.'</i>'.($exp['ext'] == 'html' ? '' : ' */').NL, $fp);

    $rows = $res->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) return main_cout_str(NL.'/* There is no data to export */', $fp);
    $cols = array();
    foreach ($rows[0] as $c => $v) {
        $cols[$c] = array('col'=>$c, 'typ'=>'char', 'pk'=>'', 'extra'=>'', 'maxchar'=>'');
    }
    if ($exp['ext'] == 'html') {
        sidu_grid_align($rows, $cols);
        if (!$pgno) {
            main_cout_str(NL.'<table style="border:solid 1px #888">', $fp);
            main_cout_str(NL.'<tr class="th">', $fp);
            foreach ($cols as $c => $v) main_cout_str('<td'.($v['is_int'] ? ' class="r"' : '').'>'.$c.'</td>', $fp);
            main_cout_str('</tr>', $fp);
        }
    } else {
        if ($exp['ext'] == 'sql') {
            $tran["'"] = "''";
            if ($exp['sql_slash']) $tran["\\"] = "\\\\";
        } else {
            $tran = array("\r" => '\r', "\n" => '\n'); //csv always in one line per row
        }
        $num = count($cols) - 1;
        if ($exp['ext'] == 'sql') {
            foreach ($cols as $c => $v) $COL[] = sidu_keyw($c);
            $head = NL.'INSERT INTO '.sidu_keyw($tab).'('.implode(',', $COL).') VALUES'.NL;
            $ttl  = count($rows) - 1;
            $size = 1000;//($is_sl ? 1 : 1000);//commit at each 1000 lines for select
        }
    }
    if ($exp['ext'] == 'html') {
        foreach ($rows as $r) {
            main_cout_str(NL.'<tr>', $fp);
            foreach ($r as $c => $v) main_cout_str('<td'.(is_null($v) ? ' class="n"' : '').($cols[$c]['is_int'] ? ' class="r"' : '').'>'.(is_null($v) ? 'NULL' : ($v === '' ? '&nbsp;' : nl2br(cms_html8(main_clean($v, $rep_clean)), 0))).'</td>', $fp);
            main_cout_str('</tr>', $fp);
        }
    } elseif ($exp['ext'] == 'json') {
        foreach ($rows as $i => $r) {
            foreach ($r as $j => $v) $r[$j] = main_clean($v, $rep_clean);
            main_cout_str(NL.json_encode($r).',', $fp, 1);
        }
    } else { // sql csv
        $tran2 = array('\n'=>"\n", '\r'=>"\r", '\t'=>"\t");
        if ($exp['ext'] == 'sql' || $exp['sepC'] == '') $exp['sepC'] = ',';
        else $exp['sepC'] = strtr($exp['sepC'], $tran2);
        $exp['csvEnc'] = "'";
        if ($exp['ext'] == 'csv') {
            if ($exp['sepR'] == '') $exp['sepR'] = '\n';
            $exp['sepR'] = strtr($exp['sepR'], $tran2);
            $exp['csvEnc'] = '"';
        }
        if ($exp['ext'] == 'csv' && $exp['csvHead'] && !$pgno) main_cout_str(implode(',', array_keys($cols)), $fp, 1);

        $txt_null = ($exp['ext'] == 'csv' && !$exp['null']) ? '' : 'NULL';
        foreach ($rows as $i => $r) {
            if ($exp['ext'] == 'sql' && ($i % $size) == 0) main_cout_str($head, $fp, 1);
            main_cout_str(($exp['ext'] == 'sql' ? '(' : $exp['sepR']), $fp, 1);
            $j = 0;
            foreach ($r as $v) {
                if (is_null($v)) main_cout_str($txt_null, $fp);
                elseif (is_numeric($v)) main_cout_str($v, $fp);
                else {
                    $v = main_clean($v, $rep_clean);
                    if (isset($tran)) $v = strtr($v, $tran);
                    if ('csv' == $exp['ext']) {
                        $v = str_replace('"', '""', $v);
                        if (strpos($v, ',') !== false) $v = '"' . $v . '"';
                    } else {
                        if ($exp['csvEnc'] == '"') $v = str_replace('"', '\"', $v);
                        $v = $exp['csvEnc'] . $v . $exp['csvEnc'];
                    }
                    main_cout_str($v, $fp, 1);
                }
                if ($j++ < $num) main_cout_str($exp['sepC'], $fp, 1);
            }
            if ($exp['ext'] == 'sql') main_cout_str(')'.($i == $ttl || ($i % $size) == ($size - 1) ? ';' : ',').NL, $fp);
        }
    }
}
function main_clean($v, $rep_clean) {
    if (!is_array($rep_clean) || is_null($v) || is_numeric($v)) return $v;
    return strtr($v, $rep_clean);
}
function main_form($SIDU, $mode) {
    head($SIDU);
    echo NL ."<form action='exp.php' method='post'>";
    echo cms_form('hidden', 'id', "$SIDU[0],$SIDU[1],$SIDU[2],$SIDU[3],$SIDU[4]");
    echo NL .'<p class="dot"><b>SIDU ', lang(1501) ,':</b> <i class="b red">', $mode ,'</i></p>', NL;
    $obj = ($SIDU[3] == 'r') ? lang(1502) : lang(1503);
    $exp = $SIDU['data'];
    if ($mode == 'SQL')  echo '<p class="green">', nl2br(cms_html8($exp['sql'][0])) ,'</p>', cms_form('hidden', 'sql', $exp['sql'][0]);
    elseif ($exp['tab']) echo '<p>', $obj ,' = <span class="green">', str_replace(',', ', ', $exp['tab']) ,'</span></p>', cms_form('hidden', 'tab', $exp['tab']);
    elseif (!$SIDU[4]) return print('<p class="err">'. lang(1504, $obj) .'</p></form>');
    $arr_ext = ['html'=>'HTML', 'csv'=>'CSV', 'sql'=>'SQL', 'json'=>'json'];
    if ($mode != 'SQL') {
        echo NL .'<p class="dot b">', lang(1505) ,'</p>'. NL .'<p>';
        if ($SIDU['eng'] != 'sqlite') {
            echo cms_form('checkbox', 'db', $exp['db'], 'Use '.($SIDU['eng'] == 'mysql' ? 'DB' : 'Sch').' &nbsp; ');
        }
        echo cms_form('checkbox', 'drop', $exp['drop'], lang(1506, $obj).' &nbsp; ');
        echo cms_form('checkbox', 'desc', $exp['desc'], lang(1507, $obj).' &nbsp; ');
        echo cms_form('checkbox', 'data', $exp['data'], ['list'=>lang(1508, $obj), 'class'=>'show', 'data-src'=>'#data']) ,'</p>';
    }

    $arr_clean = [1=>'...\\\r...\\\n, \r\n, ...\\\n, \n » <i class="green">newLine</i> &nbsp;', '...\\\t, \t » <i class="green">TAB</i> &nbsp;', "...\\\', \' » <b class='green'>'</b> &nbsp;", '...\\\", \" » <b class="green">"</b> &nbsp;', '...\\\\, \\ » <b class="green">\\</b>'];
    echo NL .'<p class="dot b">', lang(1509);
    echo cms_form('text', 'fname', $exp['fname'], ['placeholder' => 'optional file name', 'style'=>'width:200px']);
    echo '</p>'. NL .'<p>';
    echo cms_form('radio', 'ext', $exp['ext'], ['list'=>$arr_ext, 'class_cbox'=>' &nbsp; ', 'class'=>'expExt']);
    echo cms_form('checkbox', 'zip', $exp['zip'], lang(1510)) .'</p>';
    echo NL .'<div id="data"><p class="dot b">', lang(1519) ,': ( ... upto 9 )</p>';
    echo NL .'<p>'. cms_form('checkbox', 'clean[]', $exp['clean'], $arr_clean) .'</p>';
    echo NL .'<div id="csv"><p class="dot b">', lang(1514) ,'</p>';
    echo NL .'<p>', lang(1515), ' ', cms_form('text', 'sepC', $exp['sepC'], array('style'=>'width:50px'));
    echo ' <i class="green">eg , » \t</i> &nbsp; &nbsp; ', lang(1516) ,' ';
    echo cms_form('text', 'sepR', $exp['sepR'], ['style'=>'width:50px']) ,' <i class="green">eg \n</i><br>';
    echo cms_form('checkbox', 'null', $exp['null'], 'Show NULL if value is null') .'<br>';
    echo cms_form('checkbox', 'csvHead', $exp['csvHead'], 'Include field names at first line') .'</p>';
    echo NL .'</div><!-- csv -->';
    echo NL .'<div id="sql" class="hide"><p class="dot b">', lang(1512) ,'</p>';
    echo NL .'<p>', cms_form('checkbox', 'sql_slash', ($SIDU['eng']=='mysql' ? 1 : 0), lang(1513)) ,'</p></div>';
    echo '</div><!-- data -->';
    if ($mode != 'SQL' && !isset($exp['sql'][1])) {
        echo NL .'<p class="b dot">', lang(1511, $obj) ,': <i class="red">', $exp['tabs'][0] ,'</i></p>'. NL .'<p>';
        foreach ($exp['cols'] as $c) echo '<label><input type="checkbox" name="col[]" value="'. $c .'"', (!$exp['col'] || in_array($c, $exp['col']) ? ' checked="checked"' : '') ,'> ', $c ,' &nbsp; </label>';
        echo '</p>'. NL .'<p>where ', cms_form('text', 'where', $exp['where'], ['style'=>'width:300px']) ,'</p>';
    }
    echo NL .'<p class="dot"></p>';
    echo NL .'<p>', cms_form('submit', 'cmd', lang(1501)) ,'</p>';
    echo NL .'<p>Check your server setting for max size of export, for really big table eg &gt;50MB, consider using SQLdump</p></div></form>';
    foot($SIDU);
}
function valid_data(&$SIDU) {
    $exp = &$SIDU['data'];
    if (!$exp['db'] && !$exp['drop'] && !$exp['desc'] && !$exp['data']) $exp['data'] = 1;
    if ($exp['drop']) $exp['desc'] = 1;
    if ($exp['ext'] != 'html' && $exp['ext'] != 'sql' && $exp['ext'] != 'json') $exp['ext'] = 'csv';
    if (!$exp['cmd']) $exp['null'] = $exp['csvHead'] = 1; // default show null as 'NULL'
    $exp['where'] = trim(stripslashes($exp['where']));
    $exp['sepC']  = trim($exp['sepC']);
    if ($exp['sepC'] == '') $exp['sepC'] = ',';
    $exp['sepR'] = trim($exp['sepR']);
    if ($exp['sepR'] == '') $exp['sepR'] = '\n';
    if ($exp['sql']) {
        $exp['sql'] = array($exp['sql']);
        return;
    }
    $exp['tabs'] = explode(',', $exp['tab']);
    if (!strlen($exp['tabs'][0])) return 'err';
    $db = ($SIDU['eng'] == 'mysql') ? sidu_keyw($SIDU[1]) .'.' : ($SIDU['eng'] == 'pgsql' ? sidu_keyw($SIDU[2]) .'.' : '');
    foreach ($exp['tabs'] as $t) $exp['sql'][] = 'SELECT * FROM '. $db . sidu_keyw($t);
    if (!isset($exp['sql'][1])) {
        $exp['cols'] = array_keys(sidu_row($exp['sql'][0] .' LIMIT 1'));
        if ($exp['col'] && $exp['cols'] != $exp['col']) {
            foreach ($exp['col'] as $i => $c) $exp['col'][$i] = sidu_keyw($c);
            $exp['sql'][0] = 'SELECT '. implode(',', $exp['col']) . substr($exp['sql'][0], 8);
        }
        if ($exp['where']) $exp['sql'][0] .= ' WHERE '. $exp['where'];
    }
}
