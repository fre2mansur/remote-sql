<?php
function db_pgsql(&$SIDU, $dbs, $conn) {
    $owner = sidu_list('SELECT oid,rolname FROM pg_authid');
    if ($SIDU[1]) {
        $where = " AND a.datname='$SIDU[1]'";
    } else {
        $where = '';
        foreach ($dbs as $db) $where .= " OR a.datname LIKE '$db%'";
        if ($where) $where = ' WHERE '. substr($where, 4);
        if ($where) $where = NL .'AND ('. substr($where, 4) .')';
    }
    $rows_db = sidu_rows("SELECT a.oid,a.datname AS db,pg_encoding_to_char(a.encoding) AS enc,a.datdba,c.spcname,pg_database_size(a.oid) AS size\nFROM pg_database a,pg_tablespace c\nWHERE a.datistemplate='f' AND a.dattablespace=c.oid$where ORDER BY 2");
    foreach ($rows_db as $r) {
        $SIDU['dbL'] = sidu_conn($conn, $r['db']);
        $func = sidu_list("select pronamespace,count(*) from pg_proc group by 1");
        $rows_sch = sidu_rows("SELECT a.oid,a.nspname,a.nspowner,b.relkind AS typ,count(b.oid) AS num\nFROM pg_namespace a LEFT JOIN pg_class b ON a.oid=b.relnamespace\nWHERE a.nspname".($SIDU[2] ? "='$SIDU[2]'" : " NOT LIKE 'pg_toast%' AND a.nspname NOT LIKE 'pg_temp%'")."\nGROUP BY 1,2,3,4");
        unset($sch);
        foreach ($rows_sch as $r2) {
            $sch[$r2['nspname']]['oid'] = $r2['oid'];
            $sch[$r2['nspname']][$r2['typ']] = $r2['num'];
            $sch[$r2['nspname']]['f'] = isset($func[$r2['oid']]) ? $func[$r2['oid']] : 0;
        }
        $arr[$r['db']] = array('oid'=>$r['oid'], 'enc'=>$r['enc'], 'size'=>$r['size'], 'owner'=>$owner[$r['datdba']], 'spcname'=>$r['spcname'], 'sch'=>$sch);
    }
    return $arr;
}
function db_func_pgsql(&$SIDU, $conn) {
    $arr[0] = array('OID'=>'oid', 'Func'=>'proname', 'Owner'=>'towner', 'return'=>'prorettype', 'lang'=>'prolang', 'Definition'=>'prosrc', 'Comment'=>'comm');
    $SIDU['dbL'] = sidu_conn($conn, $SIDU[1]);
    $lang = sidu_list('SELECT oid,lanname FROM pg_language');
    $typ = sidu_list('SELECT oid,typname FROM pg_type');
    $rows = sidu_rows("SELECT b.proname,b.oid,pg_get_userbyid(b.proowner) AS towner,b.pronamespace,\nobj_description(b.oid,'pg_proc') AS comm,b.proargtypes,b.prorettype,b.prolang,b.prosrc\nFROM pg_namespace a,pg_proc b\nWHERE a.oid=b.pronamespace AND a.nspname='$SIDU[2]'". ($SIDU[4] != '' ? " AND b.proname LIKE '$SIDU[4]%'" : '') .' ORDER BY 1');
    foreach ($rows as $r) {
        $r['prorettype'] = $typ[$r['prorettype']];
        $r['prolang'] = $lang[$r['prolang']];
        $para = explode(' ', trim($r['proargtypes']));
        foreach ($para as $i => $v) {
            if ($v) $para[$i] = $typ[$v];
        }
        if ($para) $r['proname'] .= '('. implode(', ', $para) .')';
        foreach ($arr[0] as $k => $v) $data[$k] = $r[$v];
        $arr[] = $data;
    }
    return $arr;
}
function db_seq_pgsql(&$SIDU, $conn) {
    $arr[0] = array('OID'=>'oid', 'Seq'=>'relname', 'Owner'=>'towner', 'TS'=>'reltablespace', 'cur'=>'last_value', 'min'=>'min_value', 'max'=>'max_value', 'inc'=>'increment_by', 'cache'=>'cache_value', 'cycle'=>'is_cycled', 'called'=>'is_called', 'Comment'=>'comm');
    $SIDU['dbL'] = sidu_conn($conn, $SIDU[1]);
    $ts = sidu_list('SELECT oid,spcname FROM pg_tablespace');
    $rows = sidu_rows("SELECT b.relname,b.oid,pg_get_userbyid(b.relowner) AS towner,b.reltablespace,\nobj_description(b.oid,'pg_class') AS comm\nFROM pg_namespace a,pg_class b\nWHERE a.oid=b.relnamespace AND a.nspname='$SIDU[2]' AND b.relkind='$SIDU[3]'". ($SIDU[4] != '' ? " AND b.relname LIKE '$SIDU[4]%'" : '') .' ORDER BY 1');
    $all_seq = sidu_row('SELECT * FROM pg_sequence', '', 'seqrelid');//added for pg 13
    foreach ($rows as $r) {
        $r['reltablespace'] = isset($ts[$r['reltablespace']]) ? $ts[$r['reltablespace']] : '';
        $seq = sidu_row("SELECT * FROM $r[relname]");
        if ($seq['is_called'] == 'f') $seq['last_value'] -= $seq['increment_by'];
        foreach ($arr[0] as $k => $v) $data[$k] = isset($seq[$v]) ? $seq[$v] : $r[$v];
        if (isset($all_seq[$r['oid']])) { // added for pg 13 not tested for other pg
            $seq = $all_seq[$r['oid']];
            $data['inc'] = $seq['seqincrement'];
            $data['min'] = $seq['seqmin'];
            $data['max'] = $seq['seqmax'];
            $data['cache'] = $seq['seqcache'];
            $data['circle'] = $seq['seqcircle'];
        }
        $arr[] = $data;
    }
    return $arr;
}
function db_tab_pgsql(&$SIDU, $conn) {
    $arr[0] = array('OID'=>'oid', 'Table'=>'relname', 'Owner'=>'towner', 'TS'=>'reltablespace', 'Rows'=>'Rows', 'Avg'=>'Avg', 'Size'=>'size', 'Index'=>'ind', 'PK'=>'PK', 'Comment'=>'comm');
    $SIDU['dbL'] = sidu_conn($conn, $SIDU[1]);
    $ts = sidu_list('SELECT oid,spcname FROM pg_tablespace');
    $rows = sidu_rows("SELECT b.relname,b.oid,pg_get_userbyid(b.relowner) AS towner,b.reltablespace,\npg_relation_size(b.oid) AS size,pg_total_relation_size(b.oid) AS ind,\nobj_description(b.oid,'pg_class') AS comm,b.relnamespace\nFROM pg_namespace a,pg_class b\nWHERE a.oid=b.relnamespace AND a.nspname='$SIDU[2]' AND b.relkind='$SIDU[3]'". ($SIDU[4] != '' ? "\nAND b.relname LIKE '$SIDU[4]%'" : '') .' ORDER BY 1');
    foreach ($rows as $r) {
        $r['Rows'] = sidu_val('SELECT COUNT(*) FROM "'. $SIDU[2] .'"."'. $r['relname'] .'"');
        $r['PK'] = sidu_pg_pk($r['oid'], $r['relnamespace']);
        $r['reltablespace'] = isset($ts[$r['reltablespace']]) ? $ts[$r['reltablespace']] : '';
        $r['ind'] -= $r['size'];
        $r['Avg'] = $r['Rows'] ? ceil($r['size'] / $r['Rows']) : 0;
        foreach ($arr[0] as $k => $v) $data[$k] = $r[$v];
        $arr[] = $data;
    }
    return $arr;
}
function db_tab_new_pgsql() {
    return 'CREATE TABLE tab(
  id <i class="green">serial</i> <i class="blue">NOT NULL</i> <b>PRIMARY KEY</b>,
  id2 <i class="green">smallint</i> NOT NULL DEFAULT 0,
  id4 <i class="green">int</i> NOT NULL DEFAULT 0,
  ccy <i class="green">char(3)</i> NOT NULL DEFAULT \'USD\',
  notes <i class="green">varchar(255)</i>,
  created <i class="green">date</i>,
  updated <i class="green">timestamp</i> NOT NULL DEFAULT <i class="blue">now()</i>,
  price <i class="green">numeric(7,2)</i> NOT NULL DEFAULT 0.00,
  txt <i class="green">text</i>,
  CONSTRAINT uk UNIQUE (id2,ccy),
  CONSTRAINT fk FOREIGN KEY (id) REFERENCES tabB(idx)
)';
}
function db_view_pgsql(&$SIDU, $conn) {
    $arr[0] = array('OID'=>'oid', 'View'=>'relname', 'Owner'=>'towner', 'TS'=>'reltablespace', 'Rows'=>'Rows', 'Definition'=>'def', 'Comment'=>'comm');
    $SIDU['dbL'] = sidu_conn($conn, $SIDU[1]);
    $ts = sidu_list("SELECT oid,spcname FROM pg_tablespace");
    $rows = sidu_rows("SELECT b.relname,b.oid,pg_get_userbyid(b.relowner) AS towner,b.reltablespace,\nobj_description(b.oid,'pg_class') AS comm,pg_get_viewdef(b.oid) AS def\nFROM pg_namespace a,pg_class b WHERE a.oid=b.relnamespace\nAND a.nspname='$SIDU[2]' AND b.relkind='$SIDU[3]'".($SIDU[4] != '' ? " AND b.relname LIKE '$SIDU[4]%'" : '').' ORDER BY 1');
    foreach ($rows as $r) {
        $t = $r['relname'];
        $r['Rows'] = sidu_val("SELECT COUNT(*) FROM \"$SIDU[2]\".\"$t\"");
        $r['reltablespace'] = isset($ts[$t]) ? $ts[$t] : '';
        foreach ($arr[0] as $k => $v) $data[$k] = $r[$v];
        $arr[] = $data;
    }
    return $arr;
}
function exp_tab_desc_pgsql($SIDU, $tab, $sql_slash, $fp) {
    $SIDU[4] = $tab;
    $SIDU['cols'] = tab_init_pgsql($SIDU);
    $desc = $comm = $idx = $help = '';
    tab_desc_pgsql($SIDU, $desc, $comm, $idx, $help, 1);
    main_cout_str(NL . $desc, $fp);
    if ($comm) main_cout_str(NL . $comm, $fp);
    if ($idx)  main_cout_str(NL . $idx,  $fp);
}
function menu_pgsql(&$SIDU, $conn, $db = '', $typ = '') {
    if ($db) {
        if ($typ == 'S') return sidu_val("SELECT count(*) FROM pg_class WHERE relnamespace=$db AND relkind='S'");
        if ($typ == 'f') return sidu_list("SELECT substr(proname,1,1),count(*) FROM pg_proc\nWHERE pronamespace=$db GROUP BY 1 ORDER BY 1");
        return sidu_enum("SELECT relname FROM pg_class\nWHERE relnamespace=$db AND relkind='$typ' ORDER BY 1");
    }
    $where = '';
    if ($conn['dbs'][0] != '%') {
        foreach ($conn['dbs'] as $db) $where .= " OR datname LIKE '$db'";
        $where = NL .'AND ('. substr($where, 4) .')';
    }
    $dbs = sidu_enum('SELECT datname FROM pg_database WHERE datistemplate=false'. $where .' ORDER BY 1');
    if (!$dbs && $conn['dbs'][0] != '%') {
        foreach ($conn['dbs'] as $db) $dbs[]=substr($db, 0, -1);
    }
    foreach ($dbs as $db) {
        $SIDU['dbL'] = sidu_conn($conn, $db);
        $res = sidu_list("SELECT oid,nspname FROM pg_namespace\nWHERE nspname NOT LIKE 'pg_toast%' AND nspname NOT LIKE 'pg_temp%' ORDER BY 2");
        foreach ($res as $id => $d) $arr[$db][$d] = array('f'=>$id, 'S'=>$id, 'r'=>$id, 'v'=>$id);
    }
    return $arr;
    //operator temp parser dict domain conversion aggregate -- not available at the moment
}
function sidu_log_pgsql() {
    return 'CREATE TABLE sidu_log(
  id serial NOT NULL PRIMARY KEY,
  ts timestamp NOT NULL DEFAULT now(),
  ms int NOT NULL DEFAULT 0,
  typ char(1) NOT NULL DEFAULT \'B\',
  txt text,
  info text
)';
}
function tab_init_pgsql(&$SIDU, $fk = null) {
    //0name 1type 2null 3defa 4maxchar 5extra 6comm 7pk 8align 9pos 10pg_type 11pg_defa 12fk
    $tab = sidu_row("SELECT a.oid,a.relnamespace,obj_description(a.oid,'pg_class') AS comm\nFROM pg_class a,pg_namespace b WHERE a.relkind='$SIDU[3]' AND a.relnamespace=b.oid\nAND a.relname='$SIDU[4]' AND b.nspname='$SIDU[2]'");//a.relhasoids,
    //$defa= sidu_list('SELECT adnum,adsrc FROM pg_attrdef WHERE adrelid='. $tab['oid']);
    $defa= sidu_list('SELECT adnum,pg_get_expr(adbin, adrelid) FROM pg_attrdef WHERE adrelid='. $tab['oid']);
    foreach ($defa as $k => $v) {
        if (substr($v, 0, 9) != "nextval('") {
            $arr = explode('::', $v);
            if ($arr[0] == 'NULL') $arr[0] = null;
            elseif (substr($arr[0], 0, 1) == "'" && substr($arr[0], -1) == "'") $arr[0] = substr($arr[0], 1, -1);
            $defa[$k] = is_null($arr[0]) ? null : str_replace("''", "'", $arr[0]);
        }
    }
    //if ($SIDU['page']['oid'] &&  && $tab['relhasoids'] == 't') $SIDU['hasOid'] = 'oid,';
    $SIDU['hasOid'] = '';//no long has oid supported, not sure from which ver, v9 was supported
    $typ = sidu_list('SELECT oid,typname FROM pg_type');
    $cols= sidu_rows("SELECT attname AS col,atttypid AS typ,attnotnull AS is_null,atthasdef AS defa,\nCASE attlen WHEN -1 THEN atttypmod ELSE attlen END AS maxchar,\n'' AS extra,'' AS comm,'' AS pk,'' AS is_int,attnum AS pos,format_type(atttypid,atttypmod) AS pg_typ FROM pg_attribute\nWHERE attrelid=$tab[oid] AND attnum>0 AND attisdropped=FALSE ORDER BY attnum", '', 'col');
    foreach ($cols as $c => $r) {
        $cols[$c]['defa'] = ($r['defa'] == 't') ? $defa[$r['pos']] : null;
        $cols[$c]['is_null'] = ($r['is_null'] == 1 || $r['is_null'] == 't' ? 'NO' : 'YES');//pdo psql 1/ psql t/f
        $t = $typ[$r['typ']];
        if ($t == 'numeric') $t = $r['pg_typ'];
        elseif ($t == 'int2') $t = 'smallint';
        elseif ($t == 'int4') $t = 'int';
        elseif ($t == 'int8') $t = 'bigint';
        elseif ($t == 'bpchar') $t = 'char';
        if ($r['maxchar'] > 4 && ($t == 'varchar' || $t == 'char')) $t .= '('. ($r['maxchar'] - 4) .')';
        if (substr($r['defa'], 0, 9) == "nextval('") $t = ($t == 'int') ? 'serial' : 'bigserial';
        $cols[$c]['typ'] = $t;
        $cols[$c]['fk'] = isset($fk[$c]) ? $fk[$c] : array();
        $tab['col'][] = $c;
    }
    if ($SIDU['hasOid']) {
        $cols = array('oid'=>array('col'=>'oid','pk'=>'', 'typ'=>'oid', 'is_null'=>'NO', 'fk'=>'', 'extra'=>'', 'defa'=>'', 'maxchar'=>'', 'is_int'=>1)) + $cols;
    }
    $SIDU['tabinfo'] = $tab;
    $keys = sidu_rows("SELECT conkey,contype,pg_get_constraintdef(oid,TRUE) AS kstr FROM pg_constraint\nWHERE connamespace=$tab[relnamespace] AND conrelid=$tab[oid]");
    foreach ($keys as $r) { // pk uk fk
        $pucf = explode(',', substr($r['conkey'], 1, -1)); // { ... }
        $t = strtr($r['contype'], array('p'=>'PRI', 'u'=>'UK', 'c'=>'CK', 'f'=>'FK'));
        foreach ($pucf as $i => $v) {
            $c = $tab['col'][$v - 1];
            $cols[$c]['pk'] = $cols[$c]['pk'] ? $cols[$c]['pk'] .','. $t : $t;
            if ($t == 'FK' && !$cols[$c]['fk']) {
                $pks = explode(' REFERENCES ', substr($r['kstr'], 0, -1), 2); // FOREIGN KEY (a,b) REFERENCES tab(c,d)
                $pksT= explode('(', $pks[1], 2); // tab(c,d
                $pksC= explode(',', $pksT[1]); // c,d
                $cols[$c]['fk'] = array('col'=>$c, 'ref_tab'=>$pksT[0], 'ref_cols'=>trim($pksC[$i]), 'where_sort'=>'');
            }
            if ($t == 'PK') $SIDU['pk'][] = $c;
        }
    }
    return $cols;
}
function tab_desc_pgsql($SIDU, &$desc, &$comm, &$idx, &$help, $exp = 0) {
    $tran = array("'" => "''"); //pg9.0- :,"\\"=>"\\\\"
    $tab = $SIDU['tabinfo'];
    if ($tab['comm']) $comm = NL . ($exp ? '' : '<b class="blue">') .'COMMENT ON TABLE '. sidu_keyw($SIDU[4]) ." IS '". strtr($tab['comm'], $tran) ."';". ($exp ? '' : '</b>');
    $desc = ($exp ? '' : '<i class="grey">') .'--PG desc table is experimental--oid='. $tab['oid'] .($exp ? '' : '</i>') . NL .'CREATE TABLE '. sidu_keyw($SIDU[4]) .'(';
    foreach ($SIDU['cols'] as $c => $v) {
        $c = sidu_keyw($c);
        $desc .= NL .'  '. $c .' '. ($exp ? '' : '<i class="green">') . $v['typ'] . ($exp ? '' : '</i>');
        if ($v['is_null'] == 'NO') $desc .= ' NOT NULL';
        if (!is_null($v['defa'])) {
            if (!is_numeric($v['defa']) && substr($v['defa'], 0, 8) != 'nextval('
                && $v['defa'] != 'now()' && $v['defa'] != 'true' && $v['defa'] != 'false'
            ) $v['defa'] = "'". strtr($v['defa'], $tran) ."'";
            $desc .= ' '. ($exp ? '' : '<span class="blue">') .'DEFAULT' .($exp ? '' : '</span>') .' '. ($exp ? $v['defa'] : cms_html8($v['defa']));
        }
        $desc .= ',';
        if (isset($v['comm']) && $v['comm'] != '') $comm .= NL . ($exp ? '' : '<i class="blue">') .'COMMENT ON COLUMN '. sidu_keyw($SIDU[4]) .'.'. $c ." IS '". strtr($v['comm'], $tran) ."';". ($exp ? '' : '</i>');
    }
    $fkmatch = array('f'=>'FULL', 'p'=>'PARTIAL', 'u'=>'SIMPLE', 's'=>'SIMPLE');
    $fkact = array('a'=>'NO ACTION', 'r'=>'RESTRICT', 'c'=>'CASCADE', 'n'=>'SET NULL', 'd'=>'SET DEFAULT');
    $rows = sidu_rows("SELECT *,pg_get_constraintdef(oid,TRUE) AS kstr FROM pg_constraint\nWHERE conrelid=". $tab['oid'] .' AND connamespace='. $tab['relnamespace']);
    foreach ($rows as $r) {
        $desc .= NL .'CONSTRAINT '. ($exp ? '' : '<i class="green">') . sidu_keyw($r['conname']) .($exp ? ' ' : '</i> <b>') . $r['kstr'] .($exp ? '' : '</b>');
        $desc .= ($r['contype'] == 'f') ? ' MATCH '. $fkmatch[$r['confmatchtype']] . NL .'  ON UPDATE '. $fkact[$r['confupdtype']] .' ON DELETE '. $fkact[$r['confdeltype']] .',' : ',';
    }
    $desc = substr($desc, 0, -1) . NL .');';
    // WITH (OIDS='. ($tab['relhasoids'] == 't' ? 'TRUE' : 'FALSE') .');';
    $rows = sidu_enum("SELECT pg_get_indexdef(indexrelid) FROM pg_index\nWHERE indrelid=$tab[oid] AND indisprimary='f'");
    foreach ($rows as $r) $idx .= ($exp ? '' : '<i class="green">') . $r .';'. ($exp ? '' : '</i>') . NL;
    if (!$help) return;
    $help = "
$help[alt] <b>$help[rn] COLUMN</b> col TO new_col
$help[alt] <b>$help[rn] TO</b> new_name
$help[alt] SET SCHEMA new_schema

$help[alt] $help[addC] col type
$help[alt] $help[addC] col [ RESTRICT | CASCADE ]
$help[alt] $help[addC] col TYPE type
$help[alt] $help[addC] col <b>SET DEFAULT</b> expression
$help[alt] $help[addC] col <b>DROP DEFAULT</b>
$help[alt] $help[addC] col { SET | DROP } <b>NOT NULL</b>
$help[alt] <b>DROP CONSTRAINT</b> constraint_name [ RESTRICT | CASCADE ]
$help[alt] SET WITH OIDS
$help[alt] SET WITHOUT OIDS
$help[alt] OWNER TO new_owner
$help[alt] SET TABLESPACE new_tablespace

CREATE UNIQUE INDEX idx ON $SIDU[4] (col1,col2);
$help[addI] lower_title_idx ON $SIDU[4] ((lower(title)));
$help[addI] title_idx_nulls_low ON $SIDU[4] (title NULLS FIRST);
$help[addI] code_idx ON $SIDU[4] (code) TABLESPACE indexspace;
DROP INDEX idx;

$help[altI] $help[rn] TO suppliers;
$help[altI] SET TABLESPACE fasttablespace;

SELECT setval('$SIDU[4]_id_seq',(SELECT MAX(id) FROM $SIDU[4])+1);";
}
