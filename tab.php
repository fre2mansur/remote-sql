<?php
$data = array('desc', 'pgSize', 'pgFm', 'grid', 'where', 'objcmd', 'showStats', 'sort', 'grid', 'data');
include 'inc.page.php';
$SIDU['navi'] = 'navi';
sidu_cook_set_tab($SIDU);
tab_init($SIDU, $conn);
head($SIDU, $conn);
html_tool_obj($SIDU);
$SIDU['data']['desc'] ? tab_desc($SIDU) : sidu_grid_cout($SIDU);
//cms_pr($SIDU);
foot($SIDU);

function navi($SIDU, $conn) {
    $tabs = sidu_menu_tree_tab($SIDU[4], $SIDU['page']['tree']);
    $id = "?id=$SIDU[0],$SIDU[1],$SIDU[2],$SIDU[3],$SIDU[4]";
    echo '<div class="tool icon">';
    echo NL .'<i data-url="exp.php'. $id .'&#38;tab='. $SIDU[4] .'"'. html_hkey('E', lang(3701)) .' class="a xwin i-exp"></i>';
    if ($SIDU[3] == 'r') {
        echo NL .'<i data-url="imp.php'. $id .'&#38;tab='. $SIDU[4] .'"'. html_hkey('I', lang(3703)) .' class="a xwin i-imp"></i>';
        echo NL .'<i title="'. lang(3738) .'" data-src="#objTool" class="show i-xf"></i>';
        echo NL .'<i'. html_hkey('-', lang(3706)) .' class="a confirm objTool i-flus" data-confirm="'. lang(3707, $SIDU[4]) .'" data-cmd="EMPTY"></i>';
        if ($SIDU['eng'] == 'mysql' || $SIDU['eng'] == 'pgsql') {
            echo NL .'<a'. html_hkey('F', 'Search Column') .' href="tab.php?id='. $SIDU[0] .',';
            if ($SIDU['eng'] == 'mysql') {
                echo 'information_schema,0,r,COLUMNS&#38;where[TABLE_SCHEMA]==%27' . $SIDU[1] .'%27&#38;where[TABLE_NAME]==%27'. $SIDU[4] .'%27';
            } else {
                echo $SIDU[1] .',pg_catalog,r,pg_attribute&#38;where[attnum]=>0&#38;where[attisdropped]==FALSE&#38;where[attrelid]=='. $SIDU['tabinfo']['oid'];
            }
            echo '"><i class="i-find"></i></a>';
        }
    }
    if ($SIDU['data']['desc']) {
        echo NL .'<i'. html_hkey('X', lang(3711)) .' class="a confirm objTool i-drop" data-confirm="'. lang(3710, $SIDU[4]) .'" data-cmd="DROP"></i>';
        if ($SIDU['ttl'] > 999) echo NL .'<a id="tchartV" href="tab.php'. $id .'&#38;desc=1&#38;showStats=1" title="'. lang(3709) .'"></a>';
    } else {
        echo NL .'<i'. html_hkey('Z', lang(3713)) .' class="i-grid a tab-cmd" data-cmd="grid"></i>';
        if ($SIDU['page']['gridMode'] < 2) {
            echo NL .'<i class="i-sep"></i>';
            echo NL .'<i title="'. lang(3712) .'" class="i-hide show" data-src="#colShow"></i>';
            echo NL .'<i'. html_hkey('S', lang(3714)) .' class="i-save data-cmd" data-cmd="save"></i>';
            echo NL .'<i id="addRow"'.html_hkey('=', lang(3716)) .' class="i-plus a"></i>';
            echo NL .'<i'.  html_hkey('X', lang(3715)) .' class="i-drop data-cmd confirm" data-cmd="delete"></i>';
        }
        echo NL . trim(cms_form('text', 'pgFm', $SIDU['page']['pgFm'], array('id'=>'pgFm', 'placeholder'=>0)));
        echo trim(cms_form('text', 'pgSize', $SIDU['page']['pgSize'], array('id'=>'pgSize', 'title'=>lang(3717))));
        echo '<i'. html_hkey('G', lang(3718)) .' class="i-run tab-cmd" data-cmd="go"></i>';
        if ($SIDU['page']['pgSize'] == -1 || !$SIDU['page']['pgFm']) {
            echo NL .'<i title="'. lang(3719) .' - Fn+[" class="grey i-arrFirst"></i>';
            echo NL .'<i title="'. lang(3720) .' - Fn+<" class="grey i-arrBack"></i>';
        } else {
            echo NL .'<i'. html_hkey('[', lang(3719)) .' class="tab-cmd i-arrFirst" data-cmd="p1"></i>';
            echo NL .'<i'. html_hkey(',', lang(3720)) .' class="tab-cmd i-arrBack" data-cmd="pback"></i>';
        }
        echo NL .'<span title="'. lang(3721, number_format($SIDU['ttl'])) .'">'. number_format($SIDU['ttl']) .'</span>';
        if ($SIDU['page']['pgSize'] == -1 || $SIDU['page']['pgFm'] + $SIDU['page']['pgSize'] >= $SIDU['ttl']) {
            echo NL .'<i title="'. lang(3722) .' - Fn+>" class="grey i-arrNext"></i>';
            echo NL .'<i title="'. lang(3723) .' - Fn+>" class="grey i-arrLast"></i>';
        } else {
            echo NL .'<i'. html_hkey('.', lang(3722)) .' class="tab-cmd i-arrNext" data-cmd="pnext"></i>';
            echo NL .'<i'. html_hkey(']', lang(3723)) .' class="tab-cmd i-arrLast" data-cmd="plast"></i>';
        }
    }
    html_navi_obj($SIDU);
    if ($SIDU['data']['desc']) echo NL .'('. number_format($SIDU['ttl']) .')' . ($SIDU['ttl'] < 1000 ? '' : ' » <a href="tab.php'. $id .'&#38;desc=1&#38;showStats=1"><i class="i-chart"></i></a>');
    echo NL .'</div><!-- navi -->';
}
function tab_init(&$SIDU, $conn) {
    $no_sidu_fk = &$_SESSION['no_sidu_fk_'. $SIDU[0]][$SIDU[1] .'_'. $SIDU[2]];//possible bug of ses_name but not likely
    if (!$no_sidu_fk && !sidu_val('SELECT 1 FROM sidu_fk LIMIT 1')) $no_sidu_fk = 1;
    $fk = $no_sidu_fk ? array() : sidu_row("SELECT col,ref_tab,ref_cols,where_sort FROM sidu_fk WHERE tab='$SIDU[4]'", '', 'col');
    $tab = sidu_keyw($SIDU[4]);
    if ($SIDU['eng'] == 'mysql') $tab = sidu_keyw($SIDU[1]) .'.'. $tab;
    elseif ($SIDU['eng'] == 'pgsql') $tab = sidu_keyw($SIDU[2]) .'.'. $tab;
    $SIDU['hasOid'] = '';
    $func = 'tab_init_'. $SIDU['eng'];
    $SIDU['cols'] = $func($SIDU, $fk, $tab);
    save_data($SIDU);
    $SIDU['ttl'] = sidu_val('SELECT COUNT(*) FROM '. $tab);
    $SIDU['fks'] = $SIDU['eng'] != 'mysql' ? [] : sidu_row("SELECT COLUMN_NAME AS kid, REFERENCED_TABLE_SCHEMA AS db, REFERENCED_TABLE_NAME AS tab, REFERENCED_COLUMN_NAME AS col FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = '" . $SIDU[1] . "' AND REFERENCED_TABLE_SCHEMA IS NOT NULL AND TABLE_NAME='" . $SIDU[4] . "'", '', 'kid');
    if ($SIDU['data']['desc']) {
        if ($SIDU['eng'] == 'pgsql') {
            $colComm = sidu_list('SELECT objsubid,description FROM pg_description'. NL .'WHERE objoid='. $SIDU['tabinfo']['oid'] .' AND objsubid>0');
            foreach ($colComm as $i => $v) $SIDU['cols'][$SIDU['tabinfo']['col'][$i - 1]]['comm'] = $v;
        }
        return;
    }

    $SIDU[7] = $SIDU['data']['sort'];
    if ($SIDU[7] && substr($SIDU[7], 0, 4) == 'del:') {
        $SIDU[7] = trim(substr($SIDU[7], 4));
        if ($SIDU[5] == $SIDU[7] || $SIDU[5] == $SIDU[7] .' desc') $SIDU[5] = '';
        elseif ($SIDU[6] == $SIDU[7] || $SIDU[6] == $SIDU[7] .' desc') $SIDU[6] = '';
        $SIDU[7] = '';
    }
    sidu_sort($SIDU[5], $SIDU[6], $SIDU[7], $SIDU['page']['sortData']);
    $order = $SIDU[5] ? ' ORDER BY '. $SIDU[5] . ($SIDU[6] ? ','. $SIDU[6] : '') : ($SIDU[6] ? ' ORDER BY '. $SIDU[6] : '');
    if (!is_array($SIDU['data']['where'])) parse_str($SIDU['data']['where'], $arr);
    if (isset($arr['where'])) $SIDU['data']['where'] = cms_clean_str($arr['where'], 1);
    $where = '';
    if (is_array($SIDU['data']['where'])) {
        foreach ($SIDU['data']['where'] as $k => $v) {
            if ($k === '_SIDU_TAB_WHERE_' && strtolower(substr($v, 0, 6)) == 'where ') {
                $SIDU['data']['where'][$k] = $v = substr($v, 6);
            }
            if ($v != '') $where .= ' AND '. ($k === '_SIDU_TAB_WHERE_' ? $v : $k.' '.$v);
        }
    }
    if (!is_array($SIDU['data']['where'])) $SIDU['data']['where'] = [];
    foreach ($SIDU['cols'] as $k => $v) {
        if (!isset($SIDU['data']['where'][$k])) $SIDU['data']['where'][$k] = '';
    }
    if (!isset($SIDU['data']['where']['_SIDU_TAB_WHERE_'])) $SIDU['data']['where']['_SIDU_TAB_WHERE_'] = '';
    if (!$order && (!$where || !stripos($where,' order by '))) $order = ' ORDER BY 1 DESC';
    if ($where){
        $where = ' WHERE'. substr($where, 4);
        $WHERE = explode(' ORDER BY ', strtoupper($where));
        $SIDU['ttl'] = sidu_val('SELECT COUNT(*) FROM '. $tab . substr($where, 0, strlen($WHERE[0])));
    }
    $cmd = $SIDU['data']['cmd'];
    $pgFm = &$SIDU['page']['pgFm'];
    $pgTo = &$SIDU['page']['pgSize'];
    $pgFm = ceil($SIDU['data']['pgFm']);
    if ($cmd == 'p1') {
        $pgFm = 0;
    } elseif ($pgTo != -1) {
        if ($cmd == 'pback') $pgFm -= $pgTo;
        elseif ($cmd == 'pnext') $pgFm += $pgTo;
        elseif ($cmd == 'plast') $pgFm  = $SIDU['ttl'] - $pgTo;
        if ($pgFm > $SIDU['ttl']) $pgFm = $SIDU['ttl'] - $pgTo;
        if ($pgFm < 0) $pgFm = 0;
    }
    $limit = '';
    if ($pgTo != -1 && !stripos($SIDU['data']['where']['_SIDU_TAB_WHERE_'], ' limit ')) {
        $limit = ' LIMIT '. ($SIDU['eng'] == 'cubrid' ? ($pgFm ? $pgFm.',' : '') . $pgTo : $pgTo . ($pgFm ? ' OFFSET '.$pgFm : ''));
    }
    $SIDU['rows'] = sidu_rows('SELECT '. $SIDU['hasOid'] .'* FROM '. $tab . $where . $order . $limit);
    if ($SIDU['eng'] != 'mysql') sidu_grid_align($SIDU['rows'], $SIDU['cols']);
    parse_str($SIDU['data']['grid'], $arr);
    if (isset($arr['grid'])) $SIDU['data']['grid'] = $arr['grid'];
    sidu_grid_width($SIDU);
}

function is_type_int($typ = '') {
    $typ = strtolower($typ);
    $ints = array('int', 'serial', 'bigserial', 'oid', 'float', 'numeric', 'real', 'double', 'smallint', 'bigint', 'tinyint', 'date', 'time', 'datetime', 'timestamp', 'decimal', 'bool');
    foreach ($ints as $i) {
        if (strpos($typ, $i) === 0) return 1;
    }
}
function tab_desc($SIDU) {
    $sortLink = 'tab.php?desc=1&showStats='. $SIDU['data']['showStats'] ."&id=$SIDU[0],$SIDU[1],$SIDU[2],$SIDU[3],$SIDU[4],,";
    echo NL .'<table class="grid">';
    echo NL .'<tr class="th"><td></td>';
    echo NL .'  <td><a href="'. $sortLink .',Field">', lang(3724) ,'</a></td>';
    echo NL .'  <td><a href="'. $sortLink .',Type">',  lang(3725) ,'</a></td>';
    echo NL .'  <td>Null</td>';
    echo NL .'  <td>', lang(3726) ,'</td>';
    echo NL .'  <td>', lang(3727) ,'</td>';
    echo NL .'  <td>', lang(3728) ,'</td>';
    echo NL .'  <td title="', lang(3729) ,'">', lang(3730) ,'</td>';
    echo NL .'  <td title="', lang(3731), '">', lang(3732) ,'</td>';
    echo NL .'  <td title="', lang(3733), '">', lang(3734) ,'</td>';
    echo NL .'  <td title="', lang(3735), '">', lang(3736), '</td>';
    echo NL .'  <td title="Not-NULL or None-Zero count">num</td>';
    echo NL .'  <td>', lang(3737) ,'</td>';
    echo NL .'</tr>';
    $cols = $SIDU['cols'];
    if ($SIDU['ttl'] && ($SIDU['ttl'] < 1000 || $SIDU['data']['showStats'])) {
        $sql = '';
        //next is NOT a complete list -- need a better way fix later
        $pg_odd_type = ['anyarray', 'regproc', 'xid', 'int2vector', 'oidvector', 'inet', 'interval'];
        foreach ($cols as $k => $v) {
            if ($SIDU['eng'] == 'pgsql' && ($v['typ'][0] == '_' || in_array($v['typ'], $pg_odd_type))) {
                $sql .= ',0,0,0,0,0' . NL;
            } else {
                $colz = $coln = sidu_keyw($k);
                if (!is_type_int($v['typ'])) $colz = "length($coln)";
                $sql .= ',count('. ($SIDU['eng'] == 'sqlite' ? '' : 'distinct ') . "$coln)";
                $sql .= ($v['typ'] == 'bool') ? ",'','',''" : ",min($colz),max($colz),". (in_array($v['typ'], array('timestamp', 'date', 'datetime', 'oid', 'timestamptz')) ? 0 : "avg($colz)");
                $sql .= ',count(';
                if ($SIDU['eng'] == 'pgsql' && in_array($v['typ'], array('date', 'datetime', 'timestamp', 'bool', 'timestamptz'))) $sql .= $colz;
                else $sql .= 'CASE WHEN '. ($SIDU['eng'] == 'mysql' ? 'ifnull' : 'coalesce') ."($colz,0)>0 THEN 1 ELSE NULL END";
                $sql .= ')'. NL;
            }
        }
        $sql = 'SELECT '. substr($sql, 1) .' FROM '. sidu_keyw($SIDU[4]);
        $stat= sidu_row($sql, '', 'NUM');
        if ($stat) $stat[0] = reset($stat); // for some unknown reason stat[0] is not stable, sometimes always 0 -- is this still a bug otherwise delete this line
        $i = 0;
        foreach ($cols as $k => $v) {
            $cols[$k]['stat_num'] = $stat ? ceil($stat[5 * $i]) : 0;
            $cols[$k]['stat_min'] = $stat ? ceil($stat[5 * $i + 1]) : 0;
            $cols[$k]['stat_max'] = $stat ? ceil($stat[5 * $i + 2]) : 0;
            $cols[$k]['stat_avg'] = $stat ? ceil($stat[5 * $i + 3]) : 0;
            $cols[$k]['stat_notnull'] = $stat ? ceil($stat[5 * $i + 4]) : '';
            $i++;
            $arrS['Field'][] = $k;
            $arrS['Type'][]  = $v['typ'];
        }
    } else {
        $arrS['Field'] = array_keys($cols);
        foreach ($cols as $v) $arrS['Type'][] = $v['typ'];
    }
    if ($SIDU[7] == 'Type' || $SIDU[7] == 'Field') array_multisort($arrS[$SIDU[7]], SORT_ASC, $cols);
    $i = 0;
    foreach ($cols as $k => $v) {
        echo '<tr><td class="grey">'. ++$i .'</td>'. NL .'  <td><a href="sql.php?id='. $SIDU[0] .'&#38;sql=STATScol:'. $k .'">'. $k .'</a></td>'. NL .'  <td>';
        if (strlen($v['typ']) > 50) echo '<input type="text" value="', cms_html8($v['typ']) ,'" class="bg1">';
        else echo $v['typ'];
        if ($SIDU['eng'] == 'cubrid' && ($v['typ'] == 'STRING' || $v['typ'] == 'CHAR')) echo '('. $v['maxchar'] .')';
        echo '</td>'. NL .'  <td>', ($v['is_null'] == 'NO' ? 'No' : 'Null');
        echo '</td>'. NL .'  <td>', (is_null($v['defa']) ? '<i class="grey">NULL</i>' : cms_html8($v['defa']));
        echo '</td>'. NL .'  <td>';
        if ($v['pk'] == 'PRI' || $v['pk'] == 'p') echo '<span class="blue">PK</span>';
        elseif ($v['pk'] == 'f') echo '<span class="red">FK</span>';
        elseif ($v['pk'] == 'u' || $v['pk'] == 'UNI') echo '<span class="green">UK</span>';
        else echo $v['pk'];
        echo '</td>'. NL .'  <td>'. $v['extra'];
        if (isset($SIDU['fks'][$k])) {
            $fk = $SIDU['fks'][$k];
            echo ' <a href="tab.php?id='. $SIDU[0] .','. $fk['db'] .',0,r,'. $fk['tab'] .'&desc=1" class="small"><i class="green">'. $fk['tab'] .'</i>.<i class="grey">'. $fk['col'] .'</i></a>';
        }
        echo '</td>';
        echo NL .'  <td class="ar">'. (isset($stat) ? $v['stat_num'] : '') .'</td>';
        echo NL .'  <td class="ar">'. (isset($stat) ? $v['stat_min'] : '') .'</td>';
        echo NL .'  <td class="ar">'. (isset($stat) ? $v['stat_max'] : '') .'</td>';
        echo NL .'  <td class="ar">'. (isset($stat) ? $v['stat_avg'] : '') .'</td>';
        echo NL .'  <td class="ar">'. (isset($stat) ? $v['stat_notnull'] : '') .'</td>';
        echo NL .'  <td>'. (isset($v['comm']) ? $v['comm'] : '') .'</td>'. NL .'</tr>';
    }
    echo '</table>';
    if ($SIDU[3] == 'v') return main_desc_view($SIDU);
    $func = 'tab_desc_'. $SIDU['eng'];
    $desc = $comm = $idx = '';
    $help = array(
        'alt' => 'ALTER TABLE <i class="green">'. $SIDU[4] .'</i>',
        'addC'=> '<b>ADD COLUMN</b>',
        'altC'=> '<b>ALTER COLUMN</b>',
        'pk'  => 'PRIMARY KEY',
        'addI'=> 'CREATE INDEX',
        'altI'=> 'ALTER INDEX distributors',
        'delC'=> '<b>DROP COLUMN</b>',
        'rn'  => 'RENAME',
    );
    $chr_col = $func($SIDU, $desc, $comm, $idx, $help);
    echo NL .'<pre>'. $desc . $comm . $chr_col . NL . NL . $idx;
    if ($SIDU['eng'] == 'mysql') {
        tab_desc_my_fk($SIDU);
    }
    echo NL . NL .'********** SQL HELP **********'. NL . $help . NL .'</pre>';
}
function tab_desc_my_fk($SIDU) {
    global $pool, $num, $pool_colors;
    $db   = $SIDU[1];
    $tab  = $SIDU[4];
    $kids = $fks = [];
    my_fk_init_kids($db, $tab, $kids, $fks);
    $num  = [$db . '.' . $tab => 1];
    $pool = [];
    init_num($kids, $db, $tab);
    $pool = [];
    init_num($fks,  $db, $tab, 1);

    echo NL . '=== MySQL Foreign keys ===' . NL;
    $pool = [$db . '.' . $tab]; // reset
    cout_arr($SIDU, $fks,  $db, $tab, 1, '^ ');
    echo NL . '<b style="color:red">' . $tab . '</b>';
    $pool = [$db . '.' . $tab]; // reset
    cout_arr($SIDU, $kids, $db, $tab);
}
function tab_desc_my_sl($desc) {
    $typ = array('char', 'varchar', 'text', 'blob', 'tinyint', 'smallint', 'int', 'bigint', 'enum', 'unsigned', 'set', 'float', 'double', 'real', 'timestamp', 'datetime', 'date', 'time', 'mediumtext', 'longblob', 'longtext');
    foreach ($typ as $t) $mytran[' '.$t] = ' <span class="green">'. $t .'</span>';
    $mytran[' DEFAULT NULL,'] = ',';
    $mytran[' DEFAULT NULL']  = ' ';
    $mytran[' DEFAULT '] = ' <span class="blue">DEFAULT</span> ';
    $mytran[' default '] = ' <span class="blue">default</span> ';
    $mytran[' CHARACTER SET ']   = ' <span class="red">CHARACTER SET</span> ';
    $mytran['CURRENT_TIMESTAMP'] = 'now()'; // those need re-do it is not safe!!!
    $mytran[' decimal('] = ' <span class="red">numeric</span>(';
    $mytran[' int(11)']  = ' <span class="green">int</span>';
    $mytran[' int(10) unsigned'] = ' <span class="green">int unsigned</span>';
    $mytran[' tinyint(4)']  = ' <span class="green">tinyint</span>';
    $mytran[' smallint(6)'] = ' <span class="green">smallint</span>';
    $mytran[' bigint(20)']  = ' <span class="green">bigint</span>';
    $typ = array('PRIMARY KEY', 'UNIQUE KEY', 'KEY');
    foreach ($typ as $t) $mytran[$t] = '<b>'. $t .'</b>';
    return strtr(cms_html8($desc), $mytran);
}
function my_clean_keyw($txt) {
    $arr = explode('`', $txt, 3);
    if (!isset($arr[1])) return $txt;
    return $arr[0] . sidu_keyw($arr[1]) . my_clean_keyw($arr[2]);
}
function main_desc_view($SIDU) {
    if ($SIDU['eng'] == 'sqlite') {
        $sql = sidu_val("SELECT sql FROM sqlite_master WHERE type='view' AND name='$SIDU[4]'");
        return print('<p class="green">'. $sql .'</p>');
    }
    if ($SIDU['eng'] == 'mysql') {
        $sql = sidu_val("SELECT VIEW_DEFINITION FROM information_schema.VIEWS\nWHERE TABLE_SCHEMA='$SIDU[1]' AND TABLE_NAME='$SIDU[4]'");
        $sql = trim(str_replace("`$SIDU[1]`.", '', $sql)); // remove db
        $arr = explode(' AS ', $sql); // remove .`id` AS `id`
        foreach ($arr as $i => $v) { if (isset($arr[$i + 1])) {
            echo '<br>',$arr[$i + 1];
            $col = explode('`', $arr[$i+1], 3);
            $col = '`'. $col[1] .'`';
            $len = strlen($col);
            if (substr($v, -1 - $len) == '.'. $col) $arr[$i + 1] = substr($arr[$i + 1], $len);
            else $arr[$i] .= ' AS ';
        }}
        $sql = strtr(implode('', $arr), array(' from '=>'<br>from ', ' where '=>'<br>where '));
    } elseif ($SIDU['eng'] == 'pgsql') {
        $sql = sidu_val('SELECT pg_get_viewdef('. $SIDU['tabinfo']['oid'] .')');
    } elseif ($SIDU['eng'] == 'cubrid') {
        $sql = sidu_val("SELECT vclass_def FROM db_vclass WHERE vclass_name='$SIDU[4]'");
    }
    echo '<p><br><span class="green">CREATE VIEW '. sidu_keyw($SIDU[4]) .' AS</span><br>'. $sql .'</p>';
}
function save_data($SIDU) {
    $cmd = $SIDU['data']['cmd'];
    if (!in_array($cmd, array('insert', 'delete', 'update'))) return;
    $addSlash = sidu_slash($SIDU['eng']);
    if ($SIDU['data']['data']) { foreach ($SIDU['data']['data'] as $k => $v) {
        $is_pk = 1;
        if (substr($k, 0, 4) == 'KEY.') $k = substr($k, 4);
        else $is_pk = 0;
        $col = $SIDU['cols'][$k];
        if ($cmd == 'insert' && !is_array($v) && !strlen(trim($v)) && ($col['extra'] == 'auto_increment' || substr($col['defa'], 0, 8) == 'nextval(')) continue;
        if ($cmd == 'insert' && $SIDU['eng'] == 'pgsql' && $SIDU['page']['dataEasy']) {
            if (in_array($col['typ'], array('int', 'smallint', 'integer', 'bigint', 'int2', 'int4', 'int8', 'serial', 'bigserial'))) $v = ceil($v);
            elseif ((substr($col['typ'], 0, 8) == 'varchar(' || substr($col['typ'], 0, 5) == 'char(') && $v != 'NULL') $v = trim(substr($v, 0, $col['maxchar'] - 4));
        }
        $v = is_array($v) ? implode(',', $v) : trim($v);
        if ($addSlash) $v = str_replace('\\', '\\\\', $v);
        if ($v != 'NULL' && strtoupper($v) != 'NOW()') $v = "'". str_replace("'", "''", $v) ."'"; // pg int type no need quoted upgrade later
        //$k = sidu_keyw($k);
        if ($is_pk) {
            $k = sidu_keyw($k);
            $PK[] = (substr($v, 0, 12) == "'::md5BLOB::" && strlen($v) == 45 && sidu_is_blob($SIDU['eng'], $col)) ? "md5($k)='".substr($v,12) : $k.($v=='NULL'?' IS ':'=').$v;
        } elseif ($cmd != 'delete') {
            $data[$k] = $v;
        }
    }}
    if (($cmd != 'delete' && !$data) || ($cmd != 'insert' && !isset($PK))) exit;
    $tab = sidu_keyw($SIDU[4]);
    if ($cmd == 'update' || $cmd == 'delete') {
        $where = ' WHERE '. implode(' AND ', $PK);
        if ($cmd == 'update' || $SIDU['page']['hisData']) {
            $old = sidu_row('SELECT * FROM '. $tab .' '. $where);
            if ($old) {
                foreach ($old as $k => $v) {
                    if (is_null($v)) $old[$k] = 'NULL';
                }
                if ($cmd == 'delete') sidu_log('D', $old, 0, $tab);
            }
        }
    }
    if ($cmd == 'update') {
        if (!$old) exit;
        foreach ($old as $k => $v) {
            if ($v != 'NULL' && strtoupper($v) != 'NOW()' && !is_numeric($v)) $v = "'". str_replace("'", "''", $v) ."'";
            if (!isset($data[$k]) || $data[$k] === $v) { // must use === here otherwsie 0 == 'abc' will be true
                unset($data[$k]); unset($old[$k]);
            } else $data[$k] = sidu_keyw($k) .'='. $data[$k];
        }
        if ($old) sidu_log('D', json_encode($old), 0, $tab);
        if (!$data) exit;
    }
    $sql = ($cmd == 'insert' ? 'INSERT INTO ' : ($cmd == 'delete' ? 'DELETE FROM ' : 'UPDATE ')) . $tab;
    if ($cmd == 'insert') {
        $cols = [];
        foreach ($data as $k => $v) $cols[] = sidu_keyw($k);
        $sql .= '('. implode(',', $cols) .') VALUES('. implode(',', $data) .')';
    } elseif ($cmd == 'update') {
        $sql .= ' SET '. implode(',', $data);
    }
    if ($cmd != 'insert') $sql .= $where;
    sidu_run($sql);
    if (sidu_err()) echo '<script>alert("'. strtr(sidu_err(1), array('<'=>'&lt;', '"'=>'\\"', NL=>'')) .'")</script>';
    exit;
}

function my_fk_init_kids($sch, $tab, &$kids, &$fks) {
    $sql = "
    SELECT
        REFERENCED_TABLE_SCHEMA AS sch, REFERENCED_TABLE_NAME AS tab, REFERENCED_COLUMN_NAME AS col,
        CONSTRAINT_NAME AS fk, TABLE_SCHEMA AS kid_sch, TABLE_NAME AS kid_tab, COLUMN_NAME AS kid_col
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE
        (TABLE_SCHEMA = '$sch' AND REFERENCED_TABLE_SCHEMA IS NOT NULL)
        OR
        (REFERENCED_TABLE_SCHEMA = '$sch')
    ";
    $rows = sidu_run($sql);

    foreach ($rows as $row) {
        $sch = $row['sch'];
        $tab = $row['tab'];
        $col = $row['col'];
        $kid_sch = $row['kid_sch'];
        $kid_tab = $row['kid_tab'];
        $kid_col = $row['kid_col'];
        $fks[$kid_sch][$kid_tab][$sch][$tab][]  = $col . ($col == $kid_col ? '' : ' = ' . $kid_col);
        $kids[$sch][$tab][$kid_sch][$kid_tab][] = $kid_col . ($col == $kid_col ? '' : ' = ' . $col);
    }
}

function init_num($arr, $sch, $tab, $reverse = 0) {
    global $pool, $num;
    if (!isset($arr[$sch]) || !isset($arr[$sch][$tab])) {
        return;
    }

    foreach ($arr[$sch][$tab] as $cur_sch => $tabs) {
        foreach ($tabs as $cur_tab => $fks) {
            $sch_tab = $cur_sch . '.' . $cur_tab;
            $num[$sch_tab] = (isset($num[$sch_tab]) ? $num[$sch_tab] : 0) + 1;
            if (!in_array($sch_tab, $pool)) {
                $pool[] = $sch_tab;
                init_num($arr, $cur_sch, $cur_tab, $reverse);
            }
        }
    }
}

function cout_arr($SIDU, $arr, $sch, $tab, $reverse = 0, $pref = '. ') {
    global $pool, $num, $pool_colors;
    if (!isset($arr[$sch]) || !isset($arr[$sch][$tab])) {
        return;
    }
    $colors = ['d9534f', 'f0ad4e', '5bc0de', '5cb85c', '337ab7', 'f26522', '754c24', 'd9ce00', '0e2e42', 'ce1797','672d8b'];
    $num_colors = count($colors);

    $tabP = explode('_', $tab);
    if (count($tabP) == 1) {
        $tabP = $tab;
    } else {
        foreach ($tabP as $x => $t) {
            $tabP[$x] = $t[0];
        }
        $tabP = implode('-', $tabP);
    }
    $tabP = ' <i class="mono" style="color:#ccc">' . $tabP . '</i>';
    foreach ($arr[$sch][$tab] as $cur_sch => $tabs) {
        foreach ($tabs as $cur_tab => $fks) {
            $sch_tab = $cur_sch . '.' . $cur_tab;
            $str = NL . $pref . ' <a href="tab.php?id=' . "$SIDU[0],$cur_sch,0,r,$cur_tab&desc=1" . '">';
            if ($cur_sch != $sch) {
                $str .= '<b style="color:red">' . $cur_sch . '</b>.';
            }

            $str .= '<b';
            $count = isset($num[$sch_tab]) ? $num[$sch_tab] : 0; // this is still buggy -- not get correct num
            if ($count > 1) {
                if (!isset($pool_colors[$sch_tab])) {
                    $pool_colors[$sch_tab] = $pool_colors ? count($pool_colors) : 0;
                }
                $str .= ' style="color:#' . $colors[$pool_colors[$sch_tab] % $num_colors] . '"';
                //$str .= ' :: ' . $count;
            }
            $str .= '>' . $cur_tab . '</b></a>';
            foreach ($fks as $x => $fk) {
                $str .= ' . ' . $fk . $tabP;
            }

            if (!$reverse) {
                echo $str;
            }

            if (!in_array($sch_tab, $pool)) {
                $pool[] = $sch_tab;
                cout_arr($SIDU, $arr, $cur_sch, $cur_tab, $reverse, $pref . '. ');
            }

            if ($reverse) {
                echo $str;
            }
        }
    }
}
