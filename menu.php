<?php
include 'inc.page.php';
menu($SIDU, $conn);

function menu(&$SIDU, $conn) {
    if ($SIDU[1]) return menu_db($SIDU);
    unset($_SESSION['no_sidu_fk_'. $SIDU[0]]); //each refresh win will reset this
    $arr = isset($conn['dbs']) ? explode(';', trim($conn['dbs'])) : [];
    $dbs = array();
    foreach ($arr as $v) {
        $v = trim($v);
        if ($v) $dbs[] = $v . ($SIDU['eng'] == 'sqlite' || $SIDU['eng'] == 'cubrid' ? '' : '%');
    }
    if (!$dbs) $dbs[] = '%';
    if (!is_array($conn)) {
        exit('System Error - You need clear cookie, and restart browser');
    }
    $conn['dbs'] = $dbs;
    $func= 'menu_'. $SIDU['eng'];
    $arr = $func($SIDU, $conn);
    menu_tree_cout($arr, $conn);
}
/* only for mysql and pgsql */
function menu_db($SIDU) {
    $str = cms_dec($SIDU[6]);
    $id  = "$SIDU[0],$SIDU[1],$SIDU[2],$SIDU[3],";
    $id2 = $SIDU[4] .'_';
    $oid = 0;
    if ($SIDU['eng'] == 'mysql') {
        $arr2 = menu_mysql($SIDU, $SIDU[1], $SIDU[3]);
    } else {
        $oid = $SIDU[5];
        $arr2 = menu_pgsql($SIDU, $SIDU['conn'][$SIDU[0]], $oid, $SIDU[3]);
        if ($SIDU[3] == 'S') return print(json_encode(array($arr2, '')));
        if ($SIDU[3] == 'f') return menu_db_cout_pgF($arr2, $oid, $str, $id, $id2);
    }
    $arr3 = array();
    foreach ($arr2 as $v) sidu_menu_tree_init($arr3, $v, $SIDU['page']['tree']);
    echo json_encode(menu_db_cout($arr3, $SIDU[3], $str, $id, $id2, $oid));
}
function menu_db_cout($arr, $typ, $str, $id = '', $id2 = '', $oid = 0) {
    sidu_menu_tree_tab_del($arr);
    $oidStr = $oid ? '&#38;oid='. $oid : '';
    $num = count($arr) - 1;
    $i = $ttl = 0;
    $res = '';
    //ksort($arr);
    foreach ($arr as $k => $v) {
        $numX = count($v);
        $last = ($i++ == $num) ? 'Last' : '';
        if ($num && $numX != 1) {
            $res .= NL . NL .'<br>'. $str .'<i class="i-tr'. ($last ? ' i-trLast' : '') .'" data-id="'. $id2 . str_replace('$', '---', $k) .'"></i><i class="i-folder"></i>'
                . '<a href="db.php?id='. $id . $k . $oidStr .'">'. $k .'</a> <b>('. $numX .')</b>'
                . NL .'<span class="hide" id="t'. $id2 . str_replace('$', '---', $k) .'">';
        }
        foreach ($v as $k2 => $v2) {
            $lastX = ($k2 == $numX - 1) ? 'Last' : '';
            $res .= NL . ($num && $numX != 1 ? '  <br>'.$str.'<i class="i-trLine' . $last .'"></i>' : NL.'<br>'.$str)
                . '<i class="i-trJoin'. ($numX == 1 ? $last : $lastX) .'"></i>'
                . '<a href="tab.php?id='. $id . $v2 . $oidStr .'&#38;desc=1" title="info"><i class="i-x'. $typ .'"></i></a> '
                . '<a href="tab.php?id='. $id . $v2 . $oidStr .'" title="'. $v2 .'">'. $v2 .'</a>';
        }
        if ($num && $numX != 1) $res .= NL .'</span>';
        $ttl += $numX;
    }
    return array($ttl, $res);
}
function menu_db_cout_pgF($arr, $oid, $str, $id, $id2) {
    $num = count($arr) - 1;
    $ttl = $i = 0;
    $res = '';
    foreach ($arr as $k => $v) {
        $last = ($i++ == $num) ? 'Last' : '';
        $res .= NL .'<br>'. $str .'<i class="i-trJoin'. $last .'"></i><i class="i-zf"></i><a href="db.php?id='. "$id$k&#38;oid=$oid" .'">'. $k .'</a> <b>('. $v .')</b>';
        $ttl += $v;
    }
    echo json_encode(array($ttl, $res));
}
function menu_tree_cout($arr, $conn) {
    echo '<i class="i-fref" id="fref"'. html_hkey('W', 'Refresh') .'></i><i class="i-eng', $conn['eng'] ,'"></i>';
    echo '<a href="db.php?id=', $conn['id'] ,'">', ($conn['eng'] == 'sqlite' ? 'SQLite' : $conn['user'].'@'.$conn['host']) ,'(PDO)</a>';
    $arrT= array('r'=>lang(2404), 'v'=>lang(2405), 'f'=>lang(2406), 'p'=>lang(2407), 't'=>lang(2408), 'S'=>lang(2409));
    $ndb = count($arr);
    $i = 0;

    foreach ($arr as $k => $v) sidu_menu_tree_init($db_names, $k, '_');
    ksort($db_names);
    foreach ($db_names as $group => $names) {
        $num_names = count($names);
        if ($num_names > 1) {
            echo NL, '<br><span class="show green" data-src="next"><i class="i-folder"></i>' . $group . ' <b>(' . $num_names . ')</b> </span><span class="hide">';
        }
        foreach ($names as $db) {
            $sch = $arr[$db];
            $last = (++$i == $ndb) ? 'Last' : '';
            $tc1 = $tc2 = '<i class="i-trLine'. $last .'"></i>';
            $nS = count($sch); $k = 0;
            echo NL, NL, '<br><i class="i-tr', ($last ? ' i-trLast' : ''), '" data-id="', $i ,'"></i><i class="i-db"></i>';
            echo '<a href="db.php?id=', $conn['id'] ,',', $db ,'">', $db, '</a>';
            if ($conn['eng'] == 'pgsql') echo ' <b>(', $nS ,')</b>';
            echo NL, '<span class="hide" id="t', $i ,'">';
            foreach ($sch as $s => $Sch) {
                if ($conn['eng'] == 'pgsql' || $conn['eng'] == 'cubrid') $lastS = (++$k == $nS) ? 'Last' : '';
                if ($conn['eng'] == 'pgsql') {
                    echo NL . NL .'<br>'. $tc1 .'<i class="i-tr'. ($lastS ? ' i-trLast' : '') .'" data-id="'. "$i-$k" .'"></i><i class="i-sch"></i>';
                    echo '<a href="db.php?id=', $conn['id'] ,",$db,$s", ($conn['eng'] == 'pgsql' ? '&#38;oid='.$Sch['r'] : '') ,'">', $s ,'</a>';
                    echo NL .'<span class="hide" id="t', "$i-$k" ,'">';
                    $tc2 = $tc1 .'<i class="i-trLine'. $lastS .'"></i>';
                }
                $nT = count($Sch); $j = 0;
                foreach ($Sch as $t => $typ) {
                    $lastT = (++$j == $nT) ? 'Last' : '';
                    $tc3 = $tc2 .'<i class="i-trLine'. $lastT .'"></i>';
                    echo NL, '  <br>', $tc2, '<i class="i-tr', ($lastT ? ' i-trLast' : ''),'" data-id="', $i.'-'.$k.'-'.$j;
                    echo '"></i><i class="i-z'. $t .'"></i><a href="db.php?id='. $conn['id'] .",$db,$s,$t" .'">';
                    echo ($conn['eng'] == 'cubrid' && $s ? 'SYS ' : '') . $arrT[$t] .'</a> ';
                    $id2 = "$i-$k-$j";
                    if ($conn['eng'] == 'sqlite' || $conn['eng'] == 'cubrid') {
                        $res = menu_db_cout($typ, $t, $tc3, "$conn[id],$db,$s,$t,");
                        echo '<b>('. $res[0] .')</b><span class="hide" id="t'. $id2 .'">'. $res[1] . '</span>';
                    } else {
                        echo '<span class="hide load" id="t'. $id2 .'" data-load="id='. $conn['id'] .",$db,$s,$t,$id2,$typ,";
                        echo cms_enc($tc3) . ($conn['eng'] == 'pgsql' ? '&#38;oid='.$typ : '') .'"><br><b class="load"></b></span>';
                    }
                }
                if ($conn['eng'] == 'pgsql') echo NL, '</span>';
            }
            echo NL, '</span>';
        }
        if ($num_names > 1) {
            echo NL, '</span>';
        }
    }
}
