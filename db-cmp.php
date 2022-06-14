<?php
$data = array('fm', 'to');
include 'inc.page.php';
head($SIDU);
main();
foot($SIDU);

function main() {
    global $SIDU; // need global
    $fm = $SIDU['data']['fm'];
    $to = $SIDU['data']['to'];
    $arr = array('host', 'db', 'tab', 'task', 'where', 'err', 'pk');
    foreach ($arr as $k) {
        if (!isset($fm[$k])) $fm[$k] = '';
        if (!isset($to[$k])) $to[$k] = '';
    }
    foreach ($SIDU['conn'] as $conn) {
        if (!$fm['host'] || !isset($SIDU['conn'][$fm['host']])) {
            $fm = array('host' => $conn['id'], 'db' => '', 'tab' => '', 'task' => '', 'where' => '', 'err' => '');
        }
        $host[$conn['id']] = $conn['eng'] .': '. $conn['user'] .'@'. $conn['host'] . ($conn['eng'] != 'mysql' && $conn['eng'] != 'pgsql' ? ' {'. $conn['dbs'] .'}' : '') . ($conn['port'] ? ' : '. $conn['port'] : '');
    }
    $fm_dbs = main_host_db($fm['host']);
    if (!$fm['db'] || !in_array($fm['db'], $fm_dbs)) $fm['db'] = $fm_dbs[0];
    if (!$to['host'] || !isset($SIDU['conn'][$to['host']])) {
        $to = array('host' => $fm['host'], 'db' => '', 'tab' => '');
    } elseif ($to['host'] != $fm['host']) {
        $to_dbs = main_host_db($to['host']);
    }
    if (!isset($to_dbs)) $to_dbs = $fm_dbs;
    if (!$to['db']) $to['db'] = $to_dbs[0];
    main_conn($fm['host'], $fm['db']);
    echo NL .'<p class="b">', lang(1451), '</p>', cms_form('form', '', '?id='. $SIDU[0] .'#cmp');
    echo NL .'<p>';
    echo cms_form('select', 'fm[host]', $fm['host'], array('list'=>$host, 'defa'=>'From Host - As Correct Source', 'style'=>'width:65%'));
    echo cms_form('select', 'fm[db]',   $fm['db'],   array('list'=>main_db_arr($fm_dbs), 'defa'=>'From DB', 'style'=>'width:30%'));
    echo cms_form('select', 'to[host]', $to['host'], array('list'=>$host, 'defa'=>'To Host - Will update as per Source', 'style'=>'width:65%'));
    echo cms_form('select', 'to[db]',   $to['db'],   array('list'=>main_db_arr($to_dbs), 'defa'=>'To DB', 'style'=>'width:30%'));
    echo NL .'</p>';
    echo NL .'<p>', lang(1460) ,' <b class="red">', $SIDU['conn'][$SIDU[0]]['host'];
    echo '</b> . <b class="green">', $fm['db'], ($SIDU['eng'] == 'pgsql' ? ' . public' : ''), '</b></p>';
    echo NL .'<div style="height:180px;overflow:auto" class="bg box">';
    $arr_tab = main_db_tab_list($fm['db'], $SIDU['eng']);
    $tabs = array();
    $tree = $SIDU['page']['tree'];
    foreach ($arr_tab as $t) sidu_menu_tree_init($tabs, $t, $tree);
    sidu_menu_tree_tab_del($tabs);
    if ($fm['tab']) {
        echo '<p class="b green">» ', (substr($fm['tab'], 0, 1) == ':' ? substr($fm['tab'], 1) .'_*' : $fm['tab']) ,'</p>';
    }
    echo cms_form('radio', 'fm[tab]', $fm['tab'], array('<i'. ($fm['tab'] ? '' : ' class="b red"') .'>'. lang(1461) .'</i>'));
    foreach ($tabs as $tab => $arr) {
        echo '<br>';
        if (count($arr) > 1) {
            echo NL .'<label><input type="radio" name="fm[tab]" value=":'. $tab .'"'. ($fm['tab'] == ':'.$tab ? ' checked' : '') .'> <b>'. $tab .'_*</b></label>';
        }
        foreach ($arr as $t) echo NL .'<label><input type="radio" name="fm[tab]" value="'. $t .'"'. ($fm['tab'] == $t ? ' checked' : '') .'> ', $t ,'</label>';
    }
    echo '</div><p class="b"><br>', lang(1452), '</p><p>';
    if (!is_array($fm['task'])) $fm['task'] = array('name');
    if (in_array('data', $fm['task'])) $fm['task'] = array('data');
    if (in_array('desc', $fm['task']) || in_array('num', $fm['task'])) $fm['task'][] = 'name';
    $fm['where'] = cms_clean_str($fm['where']);
    if (!$fm['where']) $fm['where'] = 'WHERE 1=1 ORDER BY 1 LIMIT 1000 OFFSET 0';
    if (!$fm['pk']) $fm['pk'] = 'id';
    echo cms_form('checkbox', 'fm[task][]', $fm['task'], array('name'=>lang(1453), 'desc'=>lang(1454), 'num'=>lang(1455).'<br>', 'data'=>lang(1456)));
    echo cms_form('text', 'to[tab]', $to['tab'], array('style'=>'width:100px')) ,' ', lang(1457);
    echo '<br>&nbsp; &nbsp; Keys: &nbsp;', cms_form('text', 'fm[pk]', $fm['pk'], array('style'=>'width:200px')), ' eg. unique_key1, key2';
    echo '<br>&nbsp; &nbsp; ', cms_form('text', 'fm[where]', $fm['where'], array('placeholder'=>'WHERE id>1000 ORDER BY id DESC LIMIT 100', 'style'=>'width:400px')) ,'</p>';
    echo '<p id="waitHide">';
    echo cms_form('submit', 'cmd', lang(1463));
    echo cms_form('submit', 'cmd', lang(1464));
    echo cms_form('checkbox', 'fm[err]', $fm['err'], lang(1459));
    echo '</p></form>';
    if ($SIDU['data']['cmd'] == lang(1464)) main_cmp($fm, $to, $arr_tab, $tabs, $tree);
}
function main_cmp($fm, $to, $arr_tab, $tabs, $tree) {
    echo '<h3 id="cmp">', lang(1465) ,'</h3>';
    if (in_array('data', $fm['task'])) main_data($fm, $to);
    else main_db($fm, $to, $arr_tab, $tabs, $tree);
    echo '<p>Database compare finished.</p>';
}
function main_data($fm, $to) {
    if (!$fm['tab'] || substr($fm['tab'],0,1) == ':') return print('<p class="err">'. lang(1466) .'</p>');

    global $SIDU;
    $tab = sidu_keyw($fm['tab']);
    $to_t = $to['tab'] ? cms_clean_str($to['tab'], 1, 1, 1) : '';
    if (!$to_t) $to_t = $fm['tab'];
    main_conn($to['host'], $to['db']);
    $info_to['desc'] = get_tab_info($to_t, $SIDU['eng']);
    if (!$info_to['desc']) return print('<p class="err">'. lang(1467, $to_t) .'</p>');

    $tab_to = sidu_keyw($to_t);
    $info_to['num'] = sidu_val('SELECT count(*) FROM '. $tab_to);
    $data_to = sidu_rows('SELECT * FROM '. $tab_to .' '. $fm['where']);

    main_conn($fm['host'], $fm['db']);
    $info['desc'] = get_tab_info($fm['tab'], $SIDU['eng']);
    $info['num'] = sidu_val('SELECT count(*) FROM '. $tab);
    $data = sidu_rows('SELECT * FROM '. $tab .' '. $fm['where']);
    $ttl = count($data);
    if ($ttl) {
        $pk = explode(',', str_replace(' ', '', $fm['pk']));
        foreach ($pk as $k) {
            if (!isset($data[0][$k])) return print('<p class="err">Data key ['. $k .'] not found</p>');
        }
    }

    echo '<p>FM <b>'. $fm['tab'] .'</b> TO <b>'. $to_t .'</b></p>';
    if ($info['num'] != $info_to['num']) echo '<p class="red">...', lang(1468) ,': ', $info['num'] .' : '. $info_to['num'] .'</p>';
    elseif (!$fm['err']) echo '<p class="grey">...', lang(1469) ,': ', $info['num'] ,'</p>';
    if ($info['desc'] != $info_to['desc']) echo '<p>...<i class="red">', lang(1470) ,'</i><br><br><b>FM</b>: <i class="grey">', $info['desc'] ,'</i><br><br><b>TO</b>: <i class="grey">'. htmlDiff($info['desc'], $info_to['desc']) .'</i></p>';
    elseif (!$fm['err']) echo '<p class="grey">...', lang(1471) ,'</p>';

    echo '<p>', lang(1472) ,' FM ';
    echo ($fm['host'] == $to['host'] ? ''. ($fm['db'] == $to['db'] ? '' : $fm['db'] .'.') : $SIDU['conn'][$fm['host']]['host'] .'.'. $fm['db'] .'.');
    echo '<b>', $fm['tab'] ,'</b> TO ';
    $toHostDb = $SIDU['conn'][$to['host']]['host'] .'.'. $to['db'];
    echo ($fm['host'] == $to['host'] ? ''. ($fm['db'] == $to['db'] ? '' : $to['db'] .'.') : $toHostDb .'.');
    echo '<b>', $to_t ,'</b></p>';
    if ($data == $data_to) {
        echo '<p class="green">', lang(1473) ,'</p>';
    } else {
        main_data_row($data, $data_to, $pk, $tab_to, $toHostDb, $fm); // now compare row by row
    }
}
function main_data_row($data, $data_to, $pk, $tab_to, $toHostDb, $fm) {
    $sql_ins = $sql_upd = $sql_del = '';
    $i = 0;
    $keys = array_keys($data[0]);
    foreach ($data as $r => $row) {
        $row_to = main_data_row_to($row, $data_to, $pk);
        if ($row !== $row_to) {
            echo "<p class='grey'><b>FM:</b>";
            foreach ($row as $k => $v) echo ' <i class="', ($v == $row_to[$k] ? '' : 'green') ,'">', $k ,':', (is_null($v) ? 'NULL' : cms_html8($v)) ,'</i>;';
            echo '<br><b>TO:</b>';
            if ($row_to === false) {
                echo ' <i class="red">not exists</i>';
                //insert into to tab_to values
                $sql_ins .= ($i++ ? ',' : '') . NL;
                $sql_ins .= '(';
                $j = 0;
                foreach ($row as $v) {
                    if ($j++) $sql_ins .= ',';
                    $sql_ins .= is_null($v) ? 'NULL' : (is_numeric($v) ? $v : "'". str_replace("'", "''", $v) ."'");
                }
                $sql_ins .= ')';
            } else {
                $sql_upd .= 'UPDATE '. $tab_to .' SET ';
                $j = 0;
                foreach ($row_to as $k => $v) {
                    if (!in_array($k, $keys) || $row[$k] != $v) {
                        echo ' <i class="red">', $k ,':', (is_null($v) ? 'NULL' : cms_html8($v)) ,'</i>;';
                        if ($j++) $sql_upd .= ',';
                        $sql_upd .= $k .'='. (!isset($row[$k]) || is_null($row[$k]) ? 'NULL' : (is_numeric($row[$k]) ? $row[$k] : "'". str_replace("'", "''", $row[$k]) ."'"));
                    }
                }
                $sql_upd .= main_data_pk($row, $pk);
            }
            echo '</p>';
        } elseif (!$fm['err']) {
            echo '<i class="grey">OK</i><br>';
        }
    }
    if ($data_to) {
        echo '<p class="green">The following rows not exists in FM</p>';
        foreach ($data_to as $row) {
            echo '<p class="blue">';
            foreach ($row as $k => $v) echo $k .':'. cms_html8($v) .'</i>;';
            echo '</p>';
            $sql_del .= 'DELETE FROM '. $tab_to . main_data_pk($row, $pk);
        }
    }
    echo '<p class="red">Double check WHERE / ORDER BY / LIMIT, before INSERT and DELETE, also make sure both table sturctures are same</p>';
    echo '<div style="height:200px;overflow:auto;background:#eee"><p>## ====== Run in TO : '. $toHostDb .' ====== ##</p>';
    if ($sql_ins) echo '<p>INSERT INTO '. $tab_to . ' VALUES' . cms_html8($sql_ins) .';</p>';
    echo '<p>', cms_html8($sql_del) ,'</p>';
    echo '<p>', cms_html8($sql_upd) ,'</p>';
    echo '</div>';
}
function main_data_pk($row, $pk) {
    $where = '';
    foreach ($pk as $k) {
        $where .= ' AND '. $k . (is_null($row[$k]) ? ' IS NULL' : "='". str_replace("'", "''", $row[$k]) ."'");
    }
    return ' WHERE '. substr($where, 5) .';'. NL;
}
function main_data_row_to($row, &$data_to, $pk) {
    foreach ($data_to as $t => $row_to) {
        $found = 1;
        foreach ($pk as $k) {
            if ($row[$k] != $row_to[$k]) $found = 0;
        }
        if ($found) {
            unset($data_to[$t]);
            return $row_to;
        }
    }
    return false;
}

function main_db($fm, $to, $arr_tab, $tabs, $tree) {
    global $SIDU;
    $task_desc= in_array('desc', $fm['task']);
    $task_num = in_array('num',  $fm['task']);
    $fm_tabs  = main_db_tabs($SIDU['eng'], $tree, $fm, $arr_tab, $task_desc, $task_num);

    main_conn($to['host'], $to['db']); //now switch to to.host===
    $arr_tab_to = main_db_tab_list($to['db'], $SIDU['eng']);
    $to_tabs = main_db_tabs($SIDU['eng'], $tree, $fm, $arr_tab_to, $task_desc, $task_num);

    foreach ($fm_tabs as $t => $info) {
        if (in_array($t, $arr_tab_to)) {
            $err = '';
            $info_to = $to_tabs[$t];
            if ($task_num) {
                if ($info['num'] != $info_to['num']) $err = '<br><i class="red">...'. lang(1468) .': '. $info['num'] .' : '. $info_to['num'] .'</i>';
                elseif (!$fm['err']) $err = '<br><i class="grey">...'. lang(1469) .': '. $info['num'] .'</i>';
                if ($SIDU['eng'] == 'mysql') {
                    if ($info['checksum'] != $info_to['checksum']) $err .= '<br><i class="red">...'. lang(1481) .'</i>';
                    elseif (!$fm['err']) $err .= '<br><i class="grey">...'. lang(1480) .'</i>';
                }
            }
            if ($task_desc) {
                if ($info['desc'] != $info_to['desc']) {
                    $err .= '<br><i class="red">...'. lang(1470) .'</i>';
                    $err .= '<br><br><b>FM</b>: <i class="grey">'. $info['desc'] .'</i>';
                    $err .= '<br><br><b>TO</b>: <i class="grey">'. htmlDiff($info['desc'], $info_to['desc']) .'</i>';
                } elseif (!$fm['err']) {
                    $err .= '<br><i class="grey">...'. lang(1471) .'</i>';
                }
            }
            if ($err) echo '<p><i class="grey">', lang(1476) ,': ', $t ,'</i>', $err ,'</p>';
        } else {
            echo '<p class="red">', lang(1477) ,' TO: ', $t ,'</p>';
        }
    }
    foreach ($to_tabs as $t => $v) {
        if (!in_array($t, $arr_tab)) echo '<p class="green">', lang(1477) ,' FM: ', $t ,'</p>';
    }
}
function main_db_tabs($eng, $tree, $fm, $arr_tab, $task_desc, $task_num) {
    $info = array();
    foreach ($arr_tab as $k => $t) {
        if (main_db_tabs_range($fm['tab'], $t, $tree)) $info[$t] = array();
    }
    if ($task_desc) {
        foreach ($info as $t => $v) $info[$t]['desc'] = get_tab_info($t, $eng);
    }
    if ($task_num) {
        foreach ($info as $t => $v) $info[$t]['num'] = sidu_val('SELECT count(*) FROM '. sidu_keyw($t));
        if ($eng == 'mysql') {
            foreach ($info as $t => $v) {
                $row = sidu_row('checksum table '. sidu_keyw($t));
                $info[$t]['checksum'] = $row['Checksum'];
            }
        }
    }
    return $info;
}
function main_db_tabs_range($tab, $t, $tree) {
    if (!$tab || $tab == $t) return 1;
    if (substr($tab, 0, 1) != ':') return;
    $tab = substr($tab, 1) . $tree;
    if (substr($t, 0, strlen($tab)) == $tab) return 1;
}
function get_tab_info($t, $eng) {
    if ($eng == 'mysql') {
        $desc = sidu_row('SHOW CREATE TABLE '. sidu_keyw($t), '' , 'NUM');
        return $desc ? $desc[1] : '';
    }
    if ($eng == 'pgsql' || $eng == 'cubrid') { // lazy...upgrade later
        return $t .'('. implode(', ', array_keys(sidu_row("SELECT * FROM public.$t LIMIT 1"))) .')';
    }
    if ($eng == 'sqlite') return sidu_val("SELECT sql FROM sqlite_master WHERE name=tbl_name AND name='$t' LIMIT 1");
}

function main_host_db($host) {
    global $SIDU; // need global
    if ($SIDU[0] != $host) main_conn($host);
    if ($SIDU['eng'] == 'mysql') return sidu_enum('SHOW DATABASES');
    if ($SIDU['eng'] == 'pgsql') return sidu_enum('SELECT datname FROM pg_database WHERE datistemplate=false ORDER BY 1');
    return explode(';', $SIDU['conn'][$host]['dbs']); // sqlite cubrid
}
function main_conn($host, $db = '') {
    global $SIDU;
    $conn = $SIDU['conn'][$host];
    $SIDU[0] = $conn['id'];
    $SIDU['eng'] = $conn['eng'];
    $SIDU['dbL'] = sidu_conn($conn, $db);
}
function main_db_arr($arr) {
    $res = array();
    foreach ($arr as $v) $res[$v] = $v;
    return $res;
}
function main_db_tab_list($db, $eng) {
    if ($eng == 'mysql') return sidu_enum("SHOW FULL TABLES FROM $db WHERE Table_type='". ($db == 'information_schema' ? 'SYSTEM VIEW' : 'BASE TABLE')."'");
    if ($eng == 'pgsql') {
        $ns = sidu_val("SELECT oid FROM pg_namespace WHERE nspname='public'");
        return sidu_enum("SELECT relname FROM pg_class\nWHERE relnamespace=$ns AND relkind='r' ORDER BY 1");
    }
    if ($eng == 'sqlite') return sidu_enum("SELECT name FROM sqlite_master WHERE type='table' ORDER BY 1");
    if ($eng == 'cubrid') return sidu_enum("SELECT class_name FROM db_class WHERE class_type='CLASS' and owner_name='PUBLIC' ORDER BY 1");
}

// next 2 func copied from https://github.com/paulgb/simplediff/blob/master/php/simplediff.php
function diff($old, $new) {
    $matrix = array();
    $maxlen = 0;
    foreach ($old as $oindex => $ovalue) {
        $nkeys = array_keys($new, $ovalue);
        foreach ($nkeys as $nindex) {
            $matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ? $matrix[$oindex - 1][$nindex - 1] + 1 : 1;
            if ($matrix[$oindex][$nindex] > $maxlen) {
                $maxlen = $matrix[$oindex][$nindex];
                $omax = $oindex + 1 - $maxlen;
                $nmax = $nindex + 1 - $maxlen;
            }
        }
    }
    if ($maxlen == 0) return array(array('d' => $old, 'i' => $new));
    return array_merge(
        diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
        array_slice($new, $nmax, $maxlen),
        diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen))
    );
}
function htmlDiff($old, $new) {
    $res = '';
    $diff = diff(preg_split('/[\s]+/', $old), preg_split('/[\s]+/', $new));
    foreach ($diff as $k) {
        $res .= !is_array($k) ? $k . ' ' :
            (!empty($k['d']) ? '<del class="blue">' . implode(' ', $k['d']) . '</del> ' : '') .
            (!empty($k['i']) ? '<ins class="green">' . implode(' ',$k['i']) . '</ins> ' : '');
    }
    return $res;
}
