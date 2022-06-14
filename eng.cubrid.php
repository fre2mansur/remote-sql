<?php
function db_cubrid(&$SIDU, $dbs, $conn) {
    foreach ($dbs as $db) {
        $SIDU['dbL'] = sidu_conn($conn, $db);
        $rows = sidu_list("SELECT class_type,count(*) FROM db_class WHERE is_system_class='NO' GROUP BY 1");
        foreach ($rows as $typ => $num){
            if ($typ == 'CLASS') $typ = 'r';
            elseif ($typ == 'VCLASS') $typ = 'v';
            else $typ = 'other';
            $arr[$db]['sch'][0][$typ] = $num;
        }
    }
    return $arr;
}
function db_tab_cubrid($SIDU) {
    $arr[0] = array('Table'=>'class_name', 'Rows'=>'Rows', 'owner'=>'owner_name', 'PK'=>'PK', 'partition'=>'partitioned', 'reuse_oid'=>'is_reuse_oid_class');
    $pks = sidu_rows('SELECT class_name AS t,key_attr_name AS c FROM db_index_key ORDER BY key_order');
    $pk = array();
    foreach ($pks as $r) $PK[$r['t']][] = $r['c'];
    $rows = sidu_rows("SELECT * FROM db_class WHERE is_system_class='". ($SIDU[2] == 'sys' ? 'YES' : 'NO') ."' AND class_type='CLASS'". ($SIDU[4] != '' ? " AND class_name LIKE '$SIDU[4]%'" : '').' ORDER BY class_name');
    foreach ($rows as $r) {
        $t = $r['class_name'];
        $r['Rows'] = sidu_val("SELECT count(*) FROM \"$t\"");
        $r['PK'] = isset($pk[$t]) ? implode(',', $pk[$t]) : '';
        foreach ($arr[0] as $k => $v) $data[$k] = $r[$v];
        $arr[] = $data;
    }
    return $arr;
}
function db_tab_new_cubrid() {
    return 'CREATE TABLE tab(
  id <i class="green">int</i> <i class="blue">NOT NULL</i> <i class="red">auto_increment</i> <b>PRIMARY KEY</b>,
  id2 <i class="green">smallint</i> NOT NULL DEFAULT 0,
  ccy <i class="green">char(3)</i> NOT NULL DEFAULT \'USD\',
  notes <i class="green">varchar(255)</i> NOT NULL DEFAULT \'\',
  created <i class="green">date</i>,
  updated <i class="green">timestamp</i>,
  price <i class="green">numeric(7,2)</i> NOT NULL DEFAULT 0.00,
  CONSTRAINT tab_uk UNIQUE (id2),
  FOREIGN KEY (id) REFERENCES tabB(id)
)';
}
function db_view_cubrid($SIDU) {
    $arr[0] = array('View'=>'View', 'Rows'=>'Rows', 'owner'=>'owner', 'partition'=>'partition', 'reuse_oid'=>'reuser_oid');
    $rows = sidu_rows("SELECT * FROM db_class WHERE is_system_class='".($SIDU[2] == 'sys' ? 'YES' : 'NO')."' AND class_type='VCLASS'".($SIDU[4] != '' ? " AND class_name LIKE '$SIDU[4]%'" : '').' ORDER BY class_name');
    foreach ($rows as $r) {
        $num = sidu_val("SELECT count(*) FROM \"$r[class_name]\"");
        $arr[] = array('View'=>$row['class_name'], 'Rows'=>$num, 'owner'=>$row['owner_name'], 'partition'=>$row['partitioned'], 'reuse_oid'=>$row['is_reuse_oid_class']);
    }
    return $arr;
}
function menu_cubrid($SIDU, $conn) {
    foreach ($conn['dbs'] as $db) {
        $SIDU['dbL'] = sidu_conn($conn, $db);
        $rows = sidu_rows('SELECT class_name,class_type,is_system_class FROM db_class ORDER BY 3 desc,1');
        foreach ($rows as $r) {
            $sch = ($r['is_system_class'] == 'NO') ? 0 : 'sys';
            $typ = ($r['class_type'] == 'CLASS') ? 'r' : ($r['class_type'] == 'VCLASS' ? 'v' : 'other');
            sidu_menu_tree_init($arr[$db][$sch][$typ], $r[0], $SIDU['page']['tree']);
        }
    }
    return $arr;
}
function sidu_log_cubrid() {
    return 'CREATE TABLE sidu_log(
  id int NOT NULL auto_increment PRIMARY KEY,
  ts timestamp NOT NULL DEFAULT now(),
  ms int NOT NULL DEFAULT 0,
  typ char(1) NOT NULL DEFAULT \'B\',
  txt text,
  info text,
)';
}
function tab_init_cubrid(&$SIDU, $fk) {





    //0name 1type 2null 3defa 4maxchar 5extra 6comm 7pk 8align 9pos 10pg_type 11pg_defa 12fk
    $auto_inc = sidu_val("SELECT att_name FROM db_serial WHERE class_name='$SIDU[4]'");
    $rows = sidu_rows("SELECT attr_name,data_type,prec,scale,is_nullable,default_value,def_order FROM db_attribute WHERE class_name='$SIDU[4]' ORDER BY def_order");
    foreach ($rows as $r) {
        $row['fk'] = $fk[$row[0]];
        $row[3]=str_replace("''","'",$row['default_value']);
        if ($row['data_type']=='INTEGER') $row['data_type']='int';
        elseif ($row['data_type']=='STRING' || $row['data_type']=='CHAR'){
            $row[4]=$row['prec'];
            if (substr($row[3],0,1)=="'" && substr($row[3],-1)=="'") $row[3]=substr($row[3],1,-1);
        }
        if ($auto_inc==$row['attr_name']) $row[5]='auto_increment';
        $col[]=array($row['attr_name'],$row['data_type'],($row['is_nullable']=='YES' ? 'YES' : 'NO'),$row[3],$row[4],$row[5],9=>$row['def_order']+1,12=>$row[12]);
    }
    $rowsK=get_rows("SELECT is_unique,is_primary_key,is_foreign_key,c.def_order,key_order\nFROM db_index a,db_index_key b,db_attribute c\nWHERE a.class_name='$link[4]' AND a.index_name=b.index_name \nand b.key_attr_name=c.attr_name and a.class_name=c.class_name\nORDER BY a.index_name,b.key_order");
    foreach ($rowsK as $rowK){
        $id=$rowK['def_order'];
        unset($pufi);
        if ($rowK['is_primary_key']=='YES'){
            $pufi[]='p';
            $SIDU['pk'][]=$id;
        }elseif ($rowK['is_unique']=='YES') $pufi[]='u';
        if ($rowK['is_foreign_key']=='YES') $pufi[]='f';
        if ($rowK['is_primary_key']=='NO' && $rowK['is_unique']=='NO' && $rowK['is_foreign_key']=='NO') $pufi[]='i';
        $col[$id][7]=implode(',',$pufi);
    }
    return $cols;
}
function tab_desc_cubrid($SIDU, &$desc, &$comm, &$idx, &$help) {
    $auto_inc = sidu_val("SELECT att_name FROM db_serial WHERE class_name='$SIDU[4]'");
    $desc = '<i class="grey">--CUBRID desc table is experimental</i>'. NL .'CREATE TABLE '. sidu_keyw($SIDU[4]) .'(';
    foreach ($SIDU['col'] as $c => $v) {
        $is_str = ($v['typ'] == 'CHAR' || $v['typ'] == 'STRING');
        if ($v['typ'] == 'STRING' && $v['maxchar'] < 256) $v['typ'] = 'varchar';
        $desc .= NL .'  '. sidu_keyw($c) .' <i class="green">'. $v['typ'] . ($is_str && $v['maxchar'] < 256 ? '('.$v['maxchar'].')' : '') .'</i>';
        if ($v['is_null'] == 'NO') $desc .= ' NOT NULL';//funny as pg style
        if ($auto_inc == $c) $desc .= ' AUTO_INCREMENT';
        if ($v['defa'] != '') $desc .= ' <span class="blue">DEFAULT</span> '. ($is_str ? "'" : '') . cms_html8(str_replace("'", "''", $v['defa'])) . ($is_str ? "'" : '');
        $desc .=',';
    }
    $pufi = sidu_rows("SELECT * FROM db_index WHERE class_name='$SIDU[4]'");
    $rows = sidu_rows("SELECT a.index_name,a.class_name,a.key_attr_name FROM db_index_key a,db_index b\nWHERE a.index_name=b.index_name AND b.class_name='$SIDU[4]' and a.class_name=b.class_name ORDER BY a.key_order");
    foreach ($rows as $r) $pufi_col[$r['index_name']][] = $r['key_attr_name'];
    foreach ($pufi as $r) {
        $col_list = implode(',', $pufi_col[$r['index_name']]);
        if ($r['is_primary_key'] == 'YES') $desc .= NL .'CONSTRAINT <i class="green">'. $r['index_name'] .'</i> <b>PRIMARY KEY</b> ('. $col_list .'),';
        elseif ($r['is_foreign_key'] == 'NO') $desc .= NL .'CONSTRAINT <i class="green">'. $r['index_name'] .'</i> <b>'. ($r['is_unique'] == 'YES' ? 'UNIQUE' : 'KEY') .'</b> ('. $col_list .'),';
    }
    $cb_fk_act = array('CASCADE', 'RESTRICT', 'NO ACTION', 'SET NULL');
    $fks = cubrid_schema($SIDU['dbL'], CUBRID_SCH_IMPORTED_KEYS, $SIDU[4]);
    foreach ($fks as $v) $desc .= NL .'CONSTRAINT <i class="green">'. $v['FK_NAME'] .'</i> <b>FOREIGN KEY</b> ('. $v['FKCOLUMN_NAME'] .') <b>REFERENCES</b> '. sidu_keyw($v['PKTABLE_NAME']) .'('. $v['PKCOLUMN_NAME'] .') ON DELETE '. $cb_fk_act[$v['DELETE_RULE']] .' ON UPDATE '. $cb_fk_act[$v['UPDATE_RULE']] .',';
    $desc = substr($desc, 0, -1) . NL .');';
    $help = "<b>$help[rn]</b> TABLE $SIDU[4] <b>TO</b> new_name
$help[alt] $help[addC] a INT [FIRST|AFTER colB]
$help[alt] $help[altC] a <b>SET DEFAULT</b> 'value'
$help[alt] <b>$help[rn] COLUMN</b> a <b>TO</b> b
$help[alt] $help[delC] a,b

$help[alt] <b>ADD CONSTRAINT $help[pk]</b> (b)
$help[alt] <b>DROP $help[pk]</b>

$help[alt] <b>ADD CONSTRAINT UNIQUE</b> uk (c)
$help[alt] <b>DROP CONSTRAINT</b> uk

$help[alt] <b>ADD INDEX</b> idx (c)
$help[alt] <b>DROP INDEX</b> idx

$help[alt] <b>ADD CONSTRAINT</b> fk <b>FOREIGN KEY</b> (c) <b>REFERENCES</b> tab2(c)
$help[alt] <b>DROP FOREIGN KEY</b> fk";
}
