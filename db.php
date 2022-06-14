<?php
$data = array('objs', 'objcmd');
include 'inc.page.php';
$SIDU['navi'] = 'navi';
sidu_sort($SIDU[5], $SIDU[6], $SIDU[7], $SIDU['page']['sortObj']);
sidu_cook_set_db($SIDU);
head($SIDU, $conn);
main($SIDU, $conn);
foot($SIDU);

function navi($SIDU, $conn) {
    $obj= array('r'=>lang(1416), 'v'=>lang(1417), 'S'=>lang(1418), 'f'=>lang(1419));
    $id = "?id=$SIDU[0],$SIDU[1],$SIDU[2],$SIDU[3],$SIDU[4]";
    echo '<div class="tool icon">';
    if ($SIDU[3] != '') {
        if ($SIDU[3] == 'r' || $SIDU[3] == 'v') {
            echo NL .'<i data-url="exp.php'. $id .'"'. html_hkey('E', lang(1402)) .' class="a dbImpExp i-exp"></i>';
            if ($SIDU[3] == 'r') {
                echo NL .'<i data-url="imp.php'. $id .'"'. html_hkey('I', lang(1404)) .' class="a dbImpExp i-imp"></i>';
                echo NL .'<i title="'. lang(1406) .'" class="show i-xf" data-src="#objTool"></i>';
                echo NL .'<i'. html_hkey('-', lang(1410)) .' class="a confirm objTool i-flus" data-confirm="'. lang(1409) .'" data-cmd="EMPTY"></i>';
            }
        }
        echo NL .'<i'. html_hkey('X', lang(1413)) .' class="a confirm objTool i-drop" data-confirm="'. lang(1412, $obj[$SIDU[3]]) .'" data-cmd="DROP"></i>';
        $oidStr = sidu_pg_oidStr($SIDU);
        navi_seek($SIDU, $oidStr);
        html_navi_obj($SIDU, 1);
    } else {
        $engs = array('mysql'=>'MySQL', 'pgsql'=>'Postgres', 'sqlite'=>'SQLite', 'cubrid'=>'CUBRID');
        echo NL .'<i class="i-eng'. $SIDU['eng'] .'"></i><b>SIDU '. $SIDU['sidu_ver']. '</b> for <b>'. $engs[$SIDU['eng']] .'</b>';
        echo NL .'<i class="i-sep"></i>';
        echo NL .'<a href="db.php?id='. "$SIDU[0],,,,,$SIDU[5],$SIDU[6]" .'"><b>', ($SIDU['eng'] =='sqlite' ? 'SQLite' : $conn['user'].'@'.$conn['host']) ,'</b></a>';
        echo NL .'v '. $SIDU['dbL']->getAttribute(PDO::ATTR_SERVER_VERSION);
        echo NL .'<i class="i-sep"></i>';
        echo NL , date('Y-m-d H:i:s');
    }
    echo NL .'      </div><!-- tool -->'. NL;
}
function navi_seek($SIDU, $oidStr) {
    echo NL .'<a'. html_hkey('F', lang(1414)) .' href="tab.php?id='. $SIDU[0] .',';
    if ($SIDU['eng'] == 'mysql') {
        echo 'information_schema,0,r,'. ($SIDU[3] == 'r' ? 'TABLES&#38;where[TABLE_TYPE]=!=%27VIEW%27' : 'VIEWS') .'&#38;where[TABLE_SCHEMA]==%27' . $SIDU[1] .'%27';
        if ($SIDU[4] != '') echo '&#38;where[TABLE_NAME]=like %27'. $SIDU[4] .'%25%27';
    } elseif ($SIDU['eng'] == 'pgsql') {
        if ($oidStr) $oidStr .= '&#38;where['. ($SIDU[3] == 'f' ? 'pro' : 'rel') .'namespace]=='. $SIDU['data']['oid'];
        echo $SIDU[1] .',pg_catalog,r,'. ($SIDU[3] == 'f' ? 'pg_proc' : 'pg_class') . $oidStr;
        if ($SIDU[3] && $SIDU[3] != 'f') echo '&#38;where[relkind]==%27'. $SIDU[3] .'%27';
        if ($SIDU[4] != '') echo '&#38;where['. ($SIDU[3] == 'f' ? 'pro' : 'rel') .'name]=like+%27'. $SIDU[4] .'%25%27';
    } elseif ($SIDU['eng'] == 'sqlite') {
        echo $SIDU[1] .',0,r,sqlite_master&#38;where[type]==%27'. ($SIDU[3] == 'r' ? 'table' : 'view') .'%27'. ($SIDU[4] != '' ? '&#38;where[tbl_name]=like %27'. $SIDU[4] .'%25%27' : '');
    } elseif ($SIDU['eng'] == 'cubrid') {
        echo $SIDU[1] .',sys,v,db_class&#38;f[2]==%27' . ($SIDU[3] == 'r' ? 'CLASS' : 'VCLASS') .'%27&#38;f[3]==%27'. ($SIDU[2] == 'sys' ? 'YES' : 'NO') .'%27'. ($SIDU[4] != '' ? '&#38;f[0]=like %27'.$SIDU[4].'%25%27' : '');
    }
    echo '"><i class="i-find"></i></a>';
}
function main(&$SIDU, $conn) {
    if (!$SIDU[3]) return main_db($SIDU, $conn);
    html_tool_obj($SIDU, 1);
    if ($SIDU[3] == 'r') $func = 'db_tab';
    elseif ($SIDU[3] == 'v') $func = 'db_view';
    elseif ($SIDU[3] == 'S') $func = 'db_seq';
    elseif ($SIDU[3] == 'f') $func = 'db_func';
    else $func = '';
    if (!$func) return;
    $func .= '_'. $SIDU['eng'];
    if (!function_exists($func)) return;
    $arr = $func($SIDU, $conn);
    echo cms_form('form', 'dataTab', "db.php?id=$SIDU[0],$SIDU[1],$SIDU[2],$SIDU[3],$SIDU[4],$SIDU[5],$SIDU[6]");
    obj_cout($SIDU, $arr);
    echo cms_form('end');
    if ($SIDU[3] == 'v') echo "<pre>\n\n<b>CREATE VIEW</b> vvv <b>AS</b>\nSELECT * FROM tab WHERE col=5</pre>";
    elseif ($SIDU[3] == 'r') main_tab($SIDU);
}
function main_db(&$SIDU, $conn) {
    $dbs = $conn['dbs'] ? explode(';', $conn['dbs']) : array();
    $func= 'db_'. $SIDU['eng'];
    $arr = $func($SIDU, $dbs, $conn);
    echo NL .'<table class="grid">';
    echo NL .'<tr class="th"><td></td>';
    echo NL .'  <td>'. lang(1420) .'</td>';
    if ($SIDU['eng'] != 'sqlite') echo NL .'  <td>'. lang(1421) .'</td>';
    echo NL .'  <td>'. lang(1422) .'</td>';
    if ($SIDU['eng'] == 'pgsql') {
        echo NL .'  <td>'. lang(1423) .'</td>';
        echo NL .'  <td>'. lang(1424) .'</td>';
        echo NL .'  <td>'. lang(1425) .'</td>';
        echo NL .'  <td>'. lang(1423) .'</td>';
        echo NL .'  <td>'. lang(1426) .'</td>';
    } elseif ($SIDU['eng'] == 'sqlite') {
        echo NL .'  <td>'. lang(1423) .'</td>';
        echo NL .'  <td>'. lang(1427) .'</td>';
    }
    echo NL .'  <td>'. lang(1428) .'</td>';
    echo NL .'  <td>'. lang(1429) .'</td>';
    echo NL .'  <td>'. lang(1430) .'</td>';
    if ($SIDU['eng'] == 'sqlite') echo NL .'  <td>'. lang(1431) .'</td>';
    echo NL .'</tr>';
    $ttl = ['db' => 0, 'size' => 0, 'r' => 0, 'v' => 0, 'f' => 0, 'S' => 0];
    foreach ($arr as $db => $v) {
        $ttl['db']++;
        $i = 0;
        foreach ($v['sch'] as $sch => $v1) {
            echo '<tr><td></td>';
            if ($i) {
                echo NL .'  <td colspan="5"></td>';
            } else {
                echo NL .'  <td><i class="icon i-db"></i><a href="db.php?id='. "$SIDU[0],$db,,,,$SIDU[5],$SIDU[6]" . ($SIDU['eng'] == 'pgsql' ? '" title="oid='.$v['oid'].'"' : '') .'">'. $db .'</a></td>';
                if ($SIDU['eng'] != 'sqlite') echo NL .'  <td>'. $v['enc'] .'</td>';
                $ttl['size'] += $v['size'];
                echo NL .'  <td class="ar">', size2str($v['size']) ,'</td>';
                if ($SIDU['eng'] == 'pgsql') echo NL .'  <td>'. $v['owner'] .'</td>'. NL .'  <td>'. $v['spcname'] .'</td>';
            }
            if ($SIDU['eng'] == 'pgsql') {
                echo NL .'  <td><i class="icon i-sch"></i><a href="db.php?id=', "$SIDU[0],$db,$sch,,,$SIDU[5],$SIDU[6]" .'" title="oid='. $v1['oid'] .'">'. $sch .'</a></td>';
                echo NL .'  <td>'. $v['owner'] .'</td>';
                echo NL .'  <td class="ar"><a href="db.php?id='. "$SIDU[0],$db,$sch,S,,$SIDU[5],$SIDU[6]" .'">'. (isset($v1['S']) ? $v1['S'] : 0) ,'</a></td>';
            } elseif ($SIDU['eng'] == 'sqlite') {
                echo NL .'  <td>'. $v['owner']['name'] .'</td>';
                echo NL .'  <td>'. $v['group']['name'] .'</td>';
            }
            echo NL .'  <td class="ar"><a href="db.php?id=', "$SIDU[0],$db,$sch,r,,$SIDU[5],$SIDU[6]" ,'">', (isset($v1['r']) ? $v1['r'] : '<span class="grey">0</span>') ,'</a></td>';
            echo NL .'  <td class="ar"><a href="db.php?id=', "$SIDU[0],$db,$sch,v,,$SIDU[5],$SIDU[6]" ,'">', (isset($v1['v']) ? $v1['v'] : '<span class="grey">0</span>') ,'</a></td>';
            echo NL .'  <td class="ar"><a href="db.php?id=', "$SIDU[0],$db,$sch,f,,$SIDU[5],$SIDU[6]" ,'">', (isset($v1['f']) ? $v1['f'] : '<span class="grey">0</span>') ,'</a></td>';
            if ($SIDU['eng'] == 'sqlite') echo NL .'  <td>'. $v['ts'] .'</td>';
            echo NL .'</tr>';
            $i++;
            foreach (['r', 'v', 'f', 'S'] as $k) {
                $ttl[$k] += isset($v1[$k]) ? $v1[$k] : 0;
            }
        }
    }
    if ($ttl['db'] > 1) {
        echo '<tr><td></td><td>Total</td>';
        echo $SIDU['eng'] == 'sqlite' ? '' : '<td></td>';
        echo '<td class="ar">' . size2str($ttl['size']) . '</td>';
        echo $SIDU['eng'] == 'mysql' ? '' : '<td></td><td></td>';
        echo $SIDU['eng'] == 'pgsql' ? '<td></td><td></td><td class="ar">' . $ttl['S'] . '</td>' : '';
        echo '<td class="ar">' . $ttl['r'] . '</td>';
        echo '<td class="ar">' . $ttl['v'] . '</td>';
        echo '<td class="ar">' . $ttl['f'] . '</td>';
        echo $SIDU['eng'] == 'sqlite' ? '<td></td>' : '';
        echo NL .'</tr>';
    }
    echo NL .'</table>'. NL .'<pre>'. NL;
    if (!$SIDU[1]) {
        if ($SIDU['eng'] == 'pgsql') echo "<b>CREATE DATABASE name</b> WITH ENCODING='UTF8' OWNER=postgres TABLESPACE=pg_default;
COMMENT ON DATABASE name IS 'comm';
DROP DATABASE name;
ALTER DATABASE name RENAME TO newname;
ALTER DATABASE name OWNER TO new_owner;
ALTER DATABASE name SET TABLESPACE new_tablespace;

<b>CREATE SCHEMA name</b> AUTHORIZATION postgres;
COMMENT ON SCHEMA mysch IS 'comm';
DROP SCHEMA mysch;
ALTER SCHEMA name RENAME TO newname;
ALTER SCHEMA name OWNER TO newowner;";
        elseif ($SIDU['eng'] == 'mysql') echo '<b>CREATE DATABASE</b> name;<br><b>DROP DATABASE</b> name;';
    } elseif ($SIDU['eng'] == 'mysql') {
        $row = sidu_row("SHOW CREATE DATABASE `$SIDU[1]`", '', 'NUM');
        echo $row[1];
    } elseif ($SIDU['eng'] == 'pgsql') {
        $db = $arr[$SIDU[1]];
        $desc = sidu_val('SELECT description FROM pg_shdescription WHERE objoid='. $db['oid']);
        echo 'CREATE DATABASE "<b>'. $SIDU[1] .'</b>" WITH ENCODING=<b>'. $db['enc'] .'</b> OWNER=<b>'. $db['owner'] .'</b> TABLESPACE=<b>'. $db['spcname'] ."</b>;\n". 'COMMENT ON DATABASE "'. $SIDU[1] .'" IS \'<b>', addslashes($desc), "</b>';";
        if ($SIDU[2]) {
            foreach ($db['sch'] as $sch => $v);
            $desc = sidu_val("SELECT obj_description('$v[oid]','pg_namespace')");
            echo "\n\nCREATE SCHEMA \"<b>$SIDU[2]</b>\" AUTHORIZATION <b>$db[owner]</b>;\nCOMMENT ON SCHEMA \"$SIDU[2]\" IS '<b>", addslashes($desc), "</b>';";
        }
    }
    echo NL .'</pre>';
}

function main_tab($SIDU) {
    $dataMap='tinyint [unsigned]###tinyint <i class="grey">{0 ~ 255}</i>#-128 ~ 127#1B
<b>smallint</b>#smallint##smallint#±32,768#2B
<b>int</b>#int#int#int#±2,147,483,647#4B
<b>bigint</b>#bigint##bigint#±922..(19)..808#8B
<b>float</b>[(53,D)]#float [(53)]#float#float [(53)]#0 ~ 24 4B, 25 ~ 53 8B#4B / 8B
<b>char</b>[(255)]#char[(10485760)]##char(8000)#Ms nchar nvarchar for unicode#
<b>varchar</b>(255)#varchar[(10485760)]##varchar(8000 | max)#Ms max = 2G#
<b>text</b> <i class="grey">{max 65k}</i>#text <i class="grey">{max 1G}</i>#text#text <i class="grey">{max 2G}</i>#My mediumtext 16M longtext 4G#
enum(,,,)#<i class="grey">enum(,,,)</i>###max 65,535 values#1B / 2B
<b>date</b> <i class="grey">{1000 ~ 9999}</i>#date <i class="grey small">{-4713~5874... 4B}</i>##date <i class="grey">{0001 ~ 9999}</i>#<i class="small">My curdate() Pg current_date Ms getdate()</i>#3B
<b>time</b> <i class="grey">{±838:59:59}</i>#time <i class="grey">{0:0:0 ~ 24:0:0}</i>##time <i class="grey small">{0:0:0~23:59:59}</i>#My curtime() Pg current_time#3B / 8B / 5B
timestamp<i class="grey small">{1970~2038}</i>#timestamp <i class="grey small">{-4713~2942..}</i>###My now() Pg now()#4B / 8B
datetime <i class="grey small">{1000~9999}</i>###datetime <i class="grey small">{1753~9999}</i>#All CURRENT_TIMESTAMP#8B
blob#BYTEA#blob#varbinary#same as text#
auto_increment PK#serial PK#int PK#int identity(1,1) PK#PK = primary key#';
    echo NL .'<table class="grid">';
    echo NL .'<tr class="th"><td></td><td>MySQL</td><td>Postgres</td><td>SQLite</td><td>MsSQL</td><td>Range</td><td>Storage</td></tr>';
    $arr = explode("\n", $dataMap);
    foreach ($arr as $line) {
        $arr2 = explode('#', $line);
        echo NL .'<tr><td></td><td>', implode('</td><td>', $arr2), '</td></tr>';
    }
    echo NL .'</table>';
    $func = 'db_tab_new_'. $SIDU['eng'];
    echo NL .'<pre>'. NL . $func() . NL .'</pre>';
}
function obj_cout($SIDU, $arr) {
    $col = array_shift($arr);
    $arr = sidu_sort_arr($arr, $SIDU[5], $SIDU[6]);
    $right = array('Rows', 'Avg', 'Size', 'Auto', 'Index', 'cur', 'min');
    $url = "db.php?id=$SIDU[0],$SIDU[1],$SIDU[2],$SIDU[3],$SIDU[4],$SIDU[5],$SIDU[6]";
    // echo NL .'<div id="objTool" class="hide">obj tools</div>'; // what is use of this line ? can we delete ?
    echo NL .'<table class="grid">';
    echo NL .'<tr class="th">';
    echo NL .'  <td class="cbox"><input type="checkbox" id="checkAll"></td>';
    if ($SIDU['page']['lang'] != 'en') $colStr = lang(1432);
    foreach ($col as $k => $v) {
        $ar[$k] = in_array($k, $right) ? 'ar' : '';
        echo NL .'  <td><a href="'. $url .','. $k .'">'. get_sort_css($k, $SIDU[5], $SIDU[6]) . (isset($colStr[$k]) ? $colStr[$k] : $k) .'</a></td>';
    }
    echo NL .'</tr>';
    $obj = ($SIDU[3] == 'r') ? 'Table' : ($SIDU[3] == 'v' ? 'View' : ($SIDU[3] == 'S' ? 'Seq' : 'Func'));
    $oidStr = sidu_pg_oidStr($SIDU);
    $ttl = array();
    foreach ($arr as $i => $r) {
        echo '<tr>';
        echo NL .'  <td class="cbox">', cms_form('checkbox','objs[]', $SIDU['data']['objs'], array('list'=>array($r[$obj]=>''))) ,'</td>';
        foreach ($col as $k => $v) {
            $ttl[$k] = (isset($ttl[$k]) ? $ttl[$k] : 0) + ($ar[$k] ? $r[$k] : 1);
            $url = "tab.php?id=$SIDU[0],$SIDU[1],$SIDU[2],$SIDU[3],$r[$k]". $oidStr;
            if ($k == 'Table' || $k == 'View') $r[$k] = '<a href="'. $url .'&#38;desc=1" title="'. lang(1433) .'"><i class="icon i-x'. $SIDU[3] .'"></i></a> <a href="'. $url .'">'. $r[$k] .'</a>';
            elseif ($k == 'Size' || $k == 'Index' || $k == 'Avg') $r[$k] = size2str($r[$k]);
            elseif ($k == 'Definition') $r[$k] = cms_form('text', 'n', substr($r[$k], 0, 100), array('class'=>'bg1 Hpop')) . cms_form('textarea', 'n', $r[$k], array('class'=>'hide'));
            $class = $ar[$k] .' ';
            if ($k == 'Rows' || $k == 'PK') $class .= 'green';
            elseif ($k == 'Auto' && $r[$k] > 2000000000) $class .= 'red';
            echo NL .'  <td'. (trim($class) ? ' class="'.$class.'"' : '') .'>'. $r[$k] .'</td>';
        }
        echo NL .'</tr>';
    }
    if ($SIDU[3] == 'r') {
        echo '<tr>'. NL .'  <td></td>';
        foreach ($ttl as $k => $v) {
            echo NL .'  <td'. ($ar[$k] ? ' class="'.$ar[$k].'"' : '') .'>';
            if ($k == 'Table') echo 'Total '. $v .' Tables';
            elseif ($k == 'Rows') echo number_format($v);
            elseif ($k == 'Size' || $k == 'Index') echo size2str($v);
            echo '</td>';
        }
        echo NL .'</tr>';
    }
    echo NL .'</table>';
}
function size2str($i) {
    if ($i < 1024) $c = 'grey';
    elseif ($i < 1048576) $c = 'green'; // 1m
    elseif ($i < 10485760) $c = ''; // 10m
    elseif ($i < 104857600) $c = 'blue'; // 100m
    else $c = 'red';
    if ($i < 10000) $i = $i .'B';
    elseif ($i < 10238976) $i = round($i / 1024) .'K';
    elseif ($i < 10484711424) $i = round($i / 1048576) .'M';
    else $i = round($i / 1073741824, 1) .'G';
    return '<span class="'. $c .'">'. $i .'</span>';
}
