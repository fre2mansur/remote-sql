<?php
function db_mysql($SIDU, $dbs, $conn) {
    if ($SIDU[1]) {
        $where = " WHERE a.SCHEMA_NAME='$SIDU[1]'";
    } else {
        $where = '';
        foreach ($dbs as $db) $where .= " OR a.SCHEMA_NAME LIKE '$db%'";
        if ($where) $where = ' WHERE '. substr($where, 4);
    }
    $rows = sidu_rows("SELECT a.SCHEMA_NAME AS db,a.DEFAULT_CHARACTER_SET_NAME AS enc,\nif(b.TABLE_TYPE='VIEW','v','r') AS typ,count(b.TABLE_NAME) AS num,sum(DATA_LENGTH+INDEX_LENGTH) AS size\nFROM information_schema.SCHEMATA a LEFT JOIN information_schema.TABLES b\non a.SCHEMA_NAME=b.TABLE_SCHEMA$where GROUP BY 1,2,3");
    foreach ($rows as $r) {
        $arr[$r['db']]['sch'][0][$r['typ']] = $r['num'];
        $arr[$r['db']]['enc'] = $r['enc'];
        $arr[$r['db']]['size'] = (isset($arr[$r['db']]['size']) ? $arr[$r['db']]['size'] : 0) + $r['size'];
    }
    return $arr;
}
function db_tab_mysql($SIDU) {
    $arr[0] = array('Table'=>'TABLE_NAME', 'Engine'=>'ENGINE', 'RowFMT'=>'ROW_FORMAT', 'Auto'=>'AUTO_INCREMENT', 'Rows'=>'TABLE_ROWS', 'Avg'=>'AVG_ROW_LENGTH', 'Size'=>'DATA_LENGTH', 'Index'=>'INDEX_LENGTH', 'PK'=>'PK', 'Created'=>'CREATE_TIME', 'Updated'=>'UPDATE_TIME', 'Checked'=>'CHECK_TIME', 'TabColl'=>'TABLE_COLLATION', 'Comment'=>'TABLE_COMMENT');
    $pks = sidu_rows("SELECT TABLE_NAME AS t,COLUMN_NAME AS c FROM information_schema.KEY_COLUMN_USAGE\nWHERE TABLE_SCHEMA='$SIDU[1]'". ($SIDU[4] != '' ? " AND TABLE_NAME LIKE '$SIDU[4]%'" : '') ."\nAND CONSTRAINT_NAME='PRIMARY' ORDER BY TABLE_NAME,ORDINAL_POSITION");
    $pk = array();
    foreach ($pks as $r) $pk[$r['t']][] = $r['c'];
    $rows = sidu_rows("SELECT * FROM information_schema.TABLES WHERE TABLE_SCHEMA='$SIDU[1]'\nAND TABLE_TYPE!='VIEW'". ($SIDU[4] != '' ? " AND TABLE_NAME LIKE '$SIDU[4]%'" : ''));
    foreach ($rows as $r) {
        $t = $r['TABLE_NAME'];
        if ($r['TABLE_TYPE'] != 'BASE TABLE') $r['TABLE_ROWS'] = sidu_val("SELECT COUNT(*) FROM `$SIDU[1]`.`$t`");
        $r['PK'] = isset($pk[$t]) ? implode(',', $pk[$t]) : '';
        foreach ($arr[0] as $k => $v) $data[$k] = $r[$v];
        $arr[] = $data;
    }
    return $arr;
}
function db_tab_new_mysql() {
    global $SIDU;
    return 'CREATE TABLE tab(
  id <i class="green">int</i> <u>unsigned</u> <i class="blue">NOT NULL</i> <i class="red">auto_increment</i> <b>PRIMARY KEY</b>,
  id2 <i class="green">smallint</i> NOT NULL DEFAULT 0,
  ccy <i class="green">char(3)</i> NOT NULL DEFAULT \'USD\',
  notes <i class="green">varchar(255)</i> <i class="blue">binary</i>,
  created <i class="green">date</i> NOT NULL DEFAULT \'0000-00-00\',
  updated <i class="green">timestamp</i> NOT NULL DEFAULT <i class="blue">now()</i>,
  price <i class="green">numeric(7,2)</i> NOT NULL DEFAULT 0.00,
  txt <i class="green">text</i>,
  UNIQUE uk (id2,ccy)
)

## Column types stats:

SELECT COLUMN_TYPE AS typ,count(*) AS num, group_concat(TABLE_NAME) AS tabs
  FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA=\'' . $SIDU[1] . '\' AND TABLE_NAME LIKE \'' . $SIDU[4] . '%\'
 GROUP BY 1 ORDER BY 1';
}
function db_view_mysql($SIDU) {
    $arr = sidu_rows("SELECT TABLE_NAME View,VIEW_DEFINITION as def,DEFINER Owner\nFROM information_schema.VIEWS WHERE TABLE_SCHEMA='$SIDU[1]'". ($SIDU[4] != '' ? " AND TABLE_NAME LIKE '$SIDU[4]%'" : '') .' ORDER BY 1');
    foreach ($arr as $i => $v) {
        $arr[$i]['Rows'] = sidu_val("SELECT COUNT(*) FROM `$SIDU[1]`.`$v[View]`");
        $arr[$i]['Definition'] = trim(str_replace('/* ALGORITHM=UNDEFINED */','',$v['def']));
    }
    array_unshift($arr, array('View'=>'View', 'Rows'=>'Rows', 'Owner'=>'Owner', 'Definition'=>'Definition'));
    return $arr;
}
function menu_mysql($SIDU, $dbs, $typ = '') {
    if ($typ) return sidu_enum("SHOW FULL TABLES FROM `$dbs` WHERE Table_type='". ($typ == 'v' ? 'VIEW' : ($dbs == 'information_schema' ? 'SYSTEM VIEW' : 'BASE TABLE')) ."'");
    foreach ($dbs['dbs'] as $db) {
        $res = sidu_enum('SHOW DATABASES'. ($db != '%' ? " LIKE '$db'" : ''));
        foreach ($res as $d) $arr[$d][0] = array('r'=>'', 'v'=>'');
    }
    return $arr;
}
function sidu_log_mysql() {
    return 'CREATE TABLE sidu_log(
  id int NOT NULL auto_increment PRIMARY KEY,
  ts timestamp NOT NULL DEFAULT now(),
  ms int NOT NULL DEFAULT 0,
  typ char(1) NOT NULL DEFAULT \'B\',
  txt text,
  info text
)';
}
function tab_init_mysql(&$SIDU, $fk) {
    $SIDU['pk'] = array();
    $cols = sidu_row("SELECT COLUMN_NAME AS col,COLUMN_TYPE AS typ,IS_NULLABLE AS is_null,COLUMN_DEFAULT AS defa,\nifnull(CHARACTER_MAXIMUM_LENGTH,NUMERIC_PRECISION) AS maxchar,EXTRA AS extra,COLUMN_COMMENT AS comm,\nCOLUMN_KEY AS pk,if(NUMERIC_PRECISION IS NULL,'','i') AS is_int,ORDINAL_POSITION AS pos\nFROM information_schema.COLUMNS\nWHERE TABLE_SCHEMA='$SIDU[1]' AND TABLE_NAME='$SIDU[4]'\nORDER BY ORDINAL_POSITION", '', 'col');
    foreach ($cols as $c => $r) {
        if ($r['pk'] == 'PRI') $SIDU['pk'][] = $c;
        $cols[$c]['fk'] = isset($fk[$c]) ? $fk[$c] : array();
    }
    return $cols;
}
function tab_desc_mysql($SIDU, &$desc, &$comm, &$idx, &$help) {
    $desc= sidu_row('SHOW CREATE TABLE '. sidu_keyw($SIDU[1]) .'.'. sidu_keyw($SIDU[4]), '', 'NUM');
    $arr = explode(NL, $desc[1]);
    $alt = my_drop_tab_charset($arr);
    foreach ($arr as $i => $line) $arr[$i] = my_clean_keyw($line);
    $desc = tab_desc_my_sl(implode(NL, $arr));
    $alt = $alt ? NL . NL . '<i class="green">Remove charset collate, please double check SQL</i>' . NL . $help['alt'] . NL . $alt . NL : '';
    $help = "
<b>$help[rn]</b> TABLE $SIDU[4] <b>TO</b> new_name
$help[alt] $help[addC] a INT(2),$help[addC] b INT(3),$help[delC] c
$help[alt] <b>CHANGE</b> a newname VARCHAR(10) NOT NULL DEFAULT '' <b>AFTER</b> c
$help[alt] <b>ADD $help[pk]</b> (b)
$help[alt] <b>DROP $help[pk]</b>
$help[alt] CONVERT TO CHARACTER SET utf8mb4

SELECT concat('ALTER TABLE ', TABLE_NAME, ' CONVERT TO CHARACTER SET utf8mb4;') AS Please_Run_In_SQL
FROM information_schema.TABLES WHERE TABLE_SCHEMA = '" . $SIDU[1] . "' AND TABLE_TYPE = 'BASE TABLE'

$help[alt] <b>ADD UNIQUE</b> uk (c)
$help[alt] <b>DROP KEY</b> uk
$help[alt] <b>ADD INDEX</b> idx (a,b)
$help[alt] <b>DROP KEY</b> idx
$help[alt] <b>DROP FOREIGN KEY</b> fk
$help[alt] <b>AUTO_INCREMENT</b> = x
$help[alt] <b>ADD CONSTRAINT</b> fk FOREIGN KEY (col) REFERENCES tab2 (col2) ON DELETE CASCADE ON UPDATE CASCADE";
    return $alt;
}
function my_drop_tab_charset($arr) {
    $tab = array_pop($arr);
    $tab_chr = my_cut_str($tab, ' DEFAULT CHARSET=');
    $tab_col = my_cut_str($tab, ' COLLATE=');
    foreach ($arr as $i => $txt) {
        $chr = my_cut_str($txt, ' CHARACTER SET ');
        $col = my_cut_str($txt, ' COLLATE ');
        if ($chr || $col) {
            $diff = ($chr && $tab_chr && $chr != $tab_chr) || ($tab_col && $col != $tab_col) ? 1 : 0;
            $arr2 = explode(' ', $txt, 2);
            $arr[$i] = 'CHANGE ' . $arr2[0] . ' <span' . ($diff ? ' class="red"' : '') . '>' . $txt . '</span>';
        } else {
            unset($arr[$i]);
        }
    }
    return implode(NL, $arr);
}
function my_cut_str(&$str, $cut) {
    $arr = explode($cut, trim($str), 2); // ... CHARTSET utf8 ...
    if (!isset($arr[1])) {
        return;
    }
    $arr2 = explode(' ', $arr[1], 2);
    $arr[1] = isset($arr2[1]) ? $arr2[1] : '';
    $str = implode(' ', $arr);
    return $arr2[0];
}
