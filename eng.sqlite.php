<?php
function db_sqlite(&$SIDU, $dbs, $conn) {
    if ($SIDU[1]) $dbs = array($SIDU[1]);
    foreach ($dbs as $db) {
        $SIDU['dbL'] = sidu_conn($conn, $db);
        $stat = stat($db);
        $arr[$db]['size'] = $stat['size'];
        if (function_exists('posix_getpwuid')) {
            $arr[$db]['owner'] = posix_getpwuid($stat['uid']);
            $arr[$db]['group'] = ($stat['uid'] == $stat['gid']) ? $arr[$db]['owner'] : posix_getpwuid($stat['gid']);
        } else {
            $arr[$db]['owner']['name'] = $arr[$db]['group']['name'] = getenv('USERNAME'); // windows
        }
        $arr[$db]['ts'] = date('Y-m-d H:i:s', $stat['mtime']);
        $rows = sidu_list('SELECT type,count(*) FROM sqlite_master GROUP BY 1');
        foreach ($rows as $typ => $num) {
            if ($typ == 'table') $typ = 'r';
            elseif ($typ == 'view') $typ = 'v';
            else $typ = 'other';
            $arr[$db]['sch'][0][$typ] = $num;
        }
        $arr[$db]['sch'][0]['r'] = (isset($arr[$db]['sch'][0]['r']) ? $arr[$db]['sch'][0]['r'] : 0) + 1;
    }
    return $arr;
}
function db_tab_sqlite($SIDU) {
    $arr[0] = array('Table'=>'name', 'Rows'=>'Rows', 'Definition'=>'sql', 'PK'=>'PK');
    $rows = sidu_rows("SELECT name,sql FROM sqlite_master WHERE type='table'".($SIDU[4] ? " AND name LIKE '$SIDU[4]%'" : '').' ORDER BY name');
    foreach ($rows as $r) {
        $r['Rows'] = sidu_val("SELECT count(*) FROM \"$r[name]\"");
        $r['PK'] = sidu_sl_pk($r['name']);
        foreach ($arr[0] as $k => $v) $data[$k] = $r[$v];
        $arr[] = $data;
    }
    $num = sidu_val('SELECT count(*) FROM sqlite_master');
    $arr[] = array('Table'=>'sqlite_master', 'Rows'=>$num, 'Definition'=>'create table sqlite_master(type text,name text,tbl_name text,rootpage int,sql text)', 'PK'=>'');
    array_unshift($rows, array('Table'=>'Table', 'Rows'=>'Rows', 'Definition'=>'Definition', 'PK'=>'PK'));
    return $arr;
}
function db_tab_new_sqlite() {
    return 'CREATE TABLE tab(
  id <i class="green">int</i> <b>PRIMARY KEY</b>,
  ccy <i class="green">text</i>,
  price <i class="green">real</i>
)';
}
function db_view_sqlite($SIDU) {
    $arr[0] = array('View'=>'View', 'Rows'=>'Rows', 'Definition'=>'Definition');
    $rows = sidu_rows("SELECT name,sql FROM sqlite_master WHERE type='view'".($SIDU[4] != '' ? " AND name LIKE '$SIDU[4]%'" : ''));
    foreach ($rows as $r) {
        $num = sidu_val("SELECT count(*) FROM $r[name]");
        $arr[] = array('View'=>$r['name'], 'Rows'=>$num, 'Definition'=>$r['sql']);
    }
    return $arr;
}
function menu_sqlite(&$SIDU, $conn) {
    $tree = $SIDU['page']['tree'];
    foreach ($conn['dbs'] as $db) {
        $SIDU['dbL'] = sidu_conn($conn, $db);
        $arr[$db][0]['r'] = array();
        $rows = sidu_rows('SELECT type,name FROM sqlite_master ORDER BY 2');
        foreach ($rows as $r) {
            if ($r['type'] == 'table') sidu_menu_tree_init($arr[$db][0]['r'], $r['name'], $tree);
            elseif ($r['type'] == 'view') sidu_menu_tree_init($arr[$db][0]['v'], $r['name'], $tree);
        }
        sidu_menu_tree_init($arr[$db][0]['r'], 'sqlite_master', $tree);
    }
    return $arr;
}
function sidu_log_sqlite() {
    return 'CREATE TABLE sidu_log(
  ts text,
  ms int NOT NULL DEFAULT 0,
  typ text,
  txt text,
  info text
)';
}
function tab_init_sqlite(&$SIDU, $fk, $tab) {
    $rows = sidu_rows("pragma table_info($tab)");
    $cols = $SIDU['pk'] = array();
    foreach ($rows as $r) {
        $c = $r['name'];
        $cols[$c] = array(
            'col'=>$c,
            'typ'=>($r['type'] == 'integer' ? 'int' : $r['type']),
            'is_null'=>($r['notnull'] ? 'NO' : 'YES'),
            'defa' => trim($r['dflt_value'], "'"),
            'maxchar' => '',
            'extra' => '',
            'comm' => '',
            'pk' => $r['pk'] ? 'PRI' : '',
            'is_int' => '',
            'pos' => $r['cid'] + 1,
            'fk'=> isset($fk[$c]) ? $fk[$c] : array()
        );
        if ($r['pk']) $SIDU['pk'][] = $c;
    }
    return $cols;
}
function tab_desc_sqlite($SIDU, &$desc, &$comm, &$idx, &$help) {
    $desc = sidu_val("SELECT sql FROM sqlite_master WHERE name=tbl_name AND name='$SIDU[4]' LIMIT 1");
    $desc = tab_desc_my_sl($desc);
    $rows = sidu_enum("SELECT sql FROM sqlite_master WHERE type='index' AND tbl_name='$SIDU[4]' AND sql IS NOT NULL");
    foreach ($rows as $r) $idx .= '<i class="green">'. $r .';</i>'. NL;
    $help = "$help[alt] <b>$help[rn] TO</b> new_name
$help[alt] $help[addC] col type";
}
