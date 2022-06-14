<?php
$data = ['sql', 'mode', 'fmt', 'chart', 'chartOption'];
include 'inc.page.php';
run_sqls($SIDU, $conn);//inc reset cookie
$SIDU['navi'] = 'navi';
if (!isset($SIDU['sql'])) exit;
head($SIDU, $conn);
main($SIDU, $conn);
foot($SIDU);

function navi($SIDU) {
    $id = "?id=$SIDU[0],$SIDU[1],$SIDU[2],$SIDU[3],$SIDU[4]";
    echo '<div class="tool icon">';
    if (!isset($SIDU['no_exp'])) {
        echo NL . '<span id="texp" data-url="exp.php'. $id .'&#38;sql='. urlencode($SIDU['sql'][0]) .'"'. html_hkey('E', lang(3101)) .' class="xwin"><i class="i-exp"></i></span>';
        echo NL . '<i class="show i-chart"'. html_hkey('C', lang(3102)) .' data-src="#chart"></i>';
        echo NL . '<i class="i-sep"></i>';
    }
    echo NL . count($SIDU['sql']) .'sql';
    echo NL . ($SIDU['err'] ? '<b class="red">'.count($SIDU['err']).'</b>' : 0) .'err';
    $time = array_sum($SIDU['time']);
    echo NL . ($time > 1000 ? round($time / 1000, 1) : $time .'m') .'s';
    echo NL . number_format(array_sum($SIDU['row'])) .'r';
    html_navi_obj($SIDU);
    echo NL . '<i class="i-sep"></i>';
    echo NL . date('Y-m-d H:i:s');
    echo NL . '</div><!-- navi -->';
}
function main($SIDU, $conn) {
    $chart = 0;
    foreach ($SIDU['sql'] as $i => $s) {
        echo NL . NL .'<div class="p">';
        if (isset($SIDU['res'][$i]) && $SIDU['res'][$i]) {
            $SIDU['rows'] = $SIDU['res'][$i];
            $SIDU['cols'] = $SIDU['res_cols'][$i];
            sidu_grid_align($SIDU['rows'], $SIDU['cols']);
            sidu_grid_width($SIDU);
            if (!$chart) {
                cout_sql_chart($SIDU);
                $chart = 1;
            }
            sidu_grid_cout($SIDU, 1);
        }
        echo NL .'<i class="grey hideP hand" title="'. lang(3103) .'">'. str_replace('<', '&lt;', cms_html8(strlen($s) > 200 ? substr($s, 0, 150) .' ... '. substr($s, -30) : $s)) .'</i><br>';
        if (isset($SIDU['err'][$i])) echo NL .'<span class="red">'. str_replace('<', '&lt;', $SIDU['err'][$i]) .'</span><br>';
        echo NL .lang(($SIDU['row'][$i] && isset($SIDU['res'][$i]) && count($SIDU['res'][$i]) ? 3105 : 3104), '<u>'. $SIDU['row'][$i] .'</u>');
        echo ' : '. lang(3106, ($SIDU['time'][$i] > 1000 ? '<u class="blue">'.round($SIDU['time'][$i]/1000,1).'</u>s' : '<u>'.$SIDU['time'][$i].'</u>ms')) . ' : Mem ';
        $memo = memory_get_usage();
        if ($memo < 1024) {
            echo '<i class="grey">' . $memo . 'B</i>';
        } elseif ($memo < 1048576) {
            $memo = round($memo / 1024, 2);
            echo '<i' . ($memo > 500 ? ' class="green"' : '') . '>' . $memo . 'KB</i>';
        } else {
            echo '<i class="blue">' . round($memo / 1048576, 2) . 'MB</i>';
        }
        echo NL .'</div>';
    }
}
function cout_sql_chart($SIDU){
    $fmts = [
        'sxy' => '<b class="red">Serial</b>-<b class="green">xAxis</b>-yVal',
        'xsy' => '<b class="green">x</b><b class="red">s</b>y',
        'xss' => '<b class="green">x</b><b class="red">sss</b>...',
        'sxx' => '<b class="red">s</b><b class="green">xxx</b>...'
    ];
    $fmt = $SIDU['data']['fmt'] ?: 'xss';
    if (!isset($fmts[$fmt])) {
        $fmt = 'sxy';
    }
    $charts= ['bar', 'barV', 'barS', 'line', 'pie'];
    $chart = $SIDU['data']['chart'];
    if (!in_array($chart, $charts)) {
        $chart = '';
    }
    echo '<form action="sql.php?id='. $SIDU[0] .'" method="post" id="chart"';
    echo ($chart ? '' : ' class="hide"') . '>Show charts: &nbsp; &nbsp; ';
    echo cms_form('hidden', 'sql', $SIDU['sql'][0]);
    echo cms_form('hidden', 'ajax', 1);
    echo cms_form('hidden', 'chart', 'bar', ['id'=>'chartTyp']);
    echo cms_form('radio', 'fmt', $fmt, ['list' => $fmts, 'class_no_hidden']);
    foreach ($charts as $c) {
        echo ' ', cms_form('button', 'chart', $c);
    }
    echo NL .'<i class="show blue" data-src="next">...</i><p';
    echo ($SIDU['data']['chartOption'] ? '' : ' class="hide"') . '>Chart Options: ';
    $option = $SIDU['data']['chartOption'];
    echo cms_form('text', 'chartOption', $option, ['style'=>'width:300px']);
    echo ' eg. <i class="small"><i class="green">w</i>=500 <i class="grey">&</i> <i class="green">xKey</i>=year <i class="grey">&</i> <i class="green">ySum</i>=1 <i class="grey">&</i> <i class="green">yUnit</i>=MB <i class="grey">&</i> <i class="green">xformat</i>=date|M</i><br>&nbsp;</p></form>';
    if (!$chart) {
        return;
    }
    $num = count($SIDU['cols']);
    if (!$num) {
        return;
    }

    $init = ['chart'=>$chart, 'xAngle'=>45, 'css'=>1, 'gapB'=>30, 'fmt'=>$fmt, 'yFormat'=>'format'];
    if (!isset($init['xSkip'])) {
        $init['xSkipMax'] = 50;
    }
    if ($chart != 'pie') {
        $init['w'] = 1000;
    }
    if ($chart != 'line') {
        $init['valShow'] = 1;
    }
    $arr = explode('&', $option);
    foreach ($arr as $o) {
        $o = trim($o);
        if ($o) {
            $arr2 = explode('=', $o);
            $v = isset($arr2[1]) ? trim($arr2[1]) : '';
            if (strlen($v)) {
                $init[$arr2[0]] = $v;
            }
        }
    }
    $data = [];
    if (1 == $num) {
        foreach ($SIDU['rows'] as $r) {
            $data[] = reset($r);
        }
    } elseif (2 == $num) {
        foreach ($SIDU['rows'] as $r) {
            $data[reset($r)] = next($r);
        }
    } elseif ('sxy' == $fmt || 'xsy' == $fmt) {
        foreach ($SIDU['rows'] as $r) {
            $data[reset($r)][next($r)] = next($r);
        }
    } else {
        foreach ($SIDU['rows'] as $r) {
            $v1 = array_shift($r);
            foreach ($r as $c => $v) {
                $c = $SIDU['cols'][$c + 1]['col'];
                $data[$v1][$c] = $v;
            }
        }
    }
    include 'Chart.php';
    echo \Topnew\Chart::Svg($data, $init);
}
function run_sqls(&$SIDU, $conn) {
    sidu_cook_copy($SIDU);
    $sql = trim($SIDU['data']['sql']);
    if (!$sql) return;
    sidu_use_db($SIDU[1], $SIDU[2]);
    if ($sql == 'show vars') {
        $sql = [$SIDU['eng'] == 'pgsql' ? 'SHOW ALL' : 'SHOW VARIABLES'];
    } elseif ($sql == 'FLUSH ALL') {
        $sql = ['FLUSH LOGS', 'FLUSH HOSTS', 'FLUSH PRIVILEGES', 'FLUSH TABLES', 'FLUSH STATUS', 'FLUSH USER_RESOURCES', 'FLUSH TABLES WITH READ LOCK']; //'FLUSH DES_KEY_FILE', 'FLUSH QUERY CACHE'
    } elseif (substr($sql, 0, 9) == 'STATScol:') {
        $sql = ['SELECT '. sidu_keyw(substr($sql, 9)) .',count(*) FROM '. sidu_keyw($SIDU[4]) .' GROUP BY 1 ORDER BY 2 DESC,1 LIMIT 20'];
    } elseif (substr($sql, 0, 8) == 'SIDUhis:') {
        $cid = $conn['cid'];
        $his = ceil(substr($sql, 8));
        if (!isset($_SESSION['siduhis'][$cid][$his])) return;
        $his = explode(' ', $_SESSION['siduhis'][$cid][$his], 5);
        $sql = [$his[4]];
    } elseif ($sql == 'show process') {
        $sql = ($SIDU['eng'] == 'mysql') ? 'SHOW PROCESSLIST' : 'SELECT * FROM pg_stat_activity';
    } elseif (substr($sql, 0, 13) == '/*SIDU_SQL1*/') {
        $sql = [trim(substr($sql, 13))];
    } elseif (substr($sql, 0, 12) == '/*SIDU_CSV*/') {
        return run_sqls_csv($SIDU, $sql);
    } elseif (substr($sql, 0, 13) == '/*SIDU_JSON*/') {
        return run_sqls_json($SIDU, $sql);
    }
    if (!is_array($sql)) {
        $arr = explode("\n", $sql);
        if (count($arr) < 2) $arr = explode("\r", $arr[0]);
        $sql = [];
        $i = 0;
        foreach ($arr as $l) {
            $l = rtrim($l);
            if (strlen($l)) {
                $sql[$i] = (isset($sql[$i])) ? $sql[$i] ."\n". $l : $l;
                if (substr($l, -1) == ';') {
                    $sql[$i] = substr($sql[$i], 0, -1);
                    $i++;
                }
            }
        }
    }
    $SIDU['sql'] = $sql;
    $SIDU['err'] = $SIDU['time'] = $SIDU['res'] = $SIDU['row'] = $SIDU['res_cols'] = [];
    foreach ($sql as $i => $s) {
        $time_start = microtime(true);
        $res = $SIDU['dbL']->query($s);
        $time_end = microtime(true);
        $time = round(($time_end - $time_start) * 1000);
        $err = sidu_err(1);
        if ($SIDU['page']['hisSQL']) sidu_log('S', $s, $time, $err);
        $SIDU['time'][$i] = $time;
        if ($err) $SIDU['err'][$i] = $err;
        elseif (!$i || $SIDU['data']['mode'] == 'runM') {
            //if ($r) { // FETCH_NUM need to get colname:
                $x = $res->columnCount();
                for ($j = 0 ; $j < $x; $j++) {
                    $col = $res->getColumnMeta($j); // do we need other than name? fix later as this func is still in experimental stage
                    $SIDU['res_cols'][$i][$j] = ['col'=>$col['name'], 'typ'=>'char', 'pk'=>'', 'extra'=>'', 'maxchar'=>''];
                }
            //}
            $r = $res->fetchAll(PDO::FETCH_NUM); // $r = $res->fetchAll(PDO::FETCH_ASSOC); will lost if colname same
            /*if ($SIDU['eng'] == 'pgsql' && isset($r[0]) && $r[0] == []) {
                $r = null; // fix pdo pgsql rowCount bug -- this no longer exist in pg 13?
            }*/
            $SIDU['res'][$i] = $r;
        }
        $SIDU['row'][$i] = $err ? 0 : $res->rowCount();
    }
    // reset cook at end of run sqls
    $cook = null;
    if ($SIDU['eng'] == 'mysql') {
        $db = sidu_val('SELECT database()');
        $cook = isset($SIDU['cook'][$SIDU[0]]) ? $SIDU['cook'][$SIDU[0]] : [null, null];
        $cook = ($db == $cook[1]) ? null : [$SIDU[0], $db, '', '', ''];
    } elseif ($SIDU['eng'] == 'pgsql') {
        $sch = sidu_val('SHOW search_path');
        if (substr($sch, 0, 8) == '"$user",') $sch = substr($sch, 8);
        $sch = str_replace('"', '', $sch);
        $cook = isset($SIDU['cook'][$SIDU[0]]) ? $SIDU['cook'][$SIDU[0]] : null;
        $cook = ($sch == $cook[2]) ? null : [$SIDU[0], $SIDU[1], $sch, '', ''];
    }
    if ($cook) {
        $SIDU['cook'][$SIDU[0]] = $cook;
        foreach ($cook as $i => $v) $SIDU[$i] = $v;
        sidu_cook_set('COOK', $SIDU['cook']);
    }
}
function run_sqls_csv(&$SIDU, $sql) {
    $arr = explode('/*SIDU_CSV*/', $sql, 2);
    $sql = trim($arr[1]);
    $arr = explode(NL, $sql);
    $col = $row = [];
    foreach ($arr as $i => $v) {
        $arr2 = str_getcsv($v);
        if (!$i) {
            foreach ($arr2 as $x => $y) {
                $col[$x]['col'] = $y;
            }
            $num = count($arr2);
        } else {
            $row[] = array_pad(array_slice($arr2, 0, $num), $num, null);
        }
    }
    $SIDU['res'][0] = $row;
    $SIDU['res_cols'][0] = $col;
    $SIDU['row'][0] = count($row);
    $SIDU['time'][0] = 0;
    $SIDU['no_exp'] = 1;
    $SIDU['sql'] = ['SIDU CSV'];
}
function run_sqls_json(&$SIDU, $sql) {
    $arr = explode('/*SIDU_JSON*/', $sql, 2);
    $json = json_decode(trim($arr[1]), 1);
    $col = $row = $colname = [];
    foreach ($json as $k => $v) {
        foreach ($v as $k2 => $v2) {
            if (!in_array($k2, $colname)) {
                $col[] = ['col' => $k2];
                $colname[] = $k2;
            }
        }
    }
    foreach ($json as $k => $v) {
        foreach ($colname as $i => $k2) {
            $v2 = isset($v[$k2]) ? $v[$k2] : null;
            $row[$k][$i] = $v2 && is_array($v2) ? json_encode($v2) : $v2;
        }
    }
    $SIDU['res'][0] = $row;
    $SIDU['res_cols'][0] = $col;
    $SIDU['row'][0] = count($row);
    $SIDU['time'][0] = 0;
    $SIDU['no_exp'] = 1;
    $SIDU['sql'] = ['SIDU JSON'];
}
