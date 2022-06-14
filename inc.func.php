<?php
function cms_clean_str($x, $trim = 0, $tag = 0, $slash = 0) {
    if (!is_array($x)) {
        if ($tag) {
            $x = strip_tags($x, (1 == $tag ? '' : $tag));
        }
        if ('sql' === $slash) {
            $x = str_replace(['#', '--', ';', '/*', '\'', '"', '\\'], '', $x);//heck free+no slash
        } elseif ($slash) {
            $x = stripslashes($x);
        }
        if ('r' == $trim || 'R' == $trim) {
            $x = rtrim($x);
        }
        if ('l' == $trim || 'L' == $trim) {
            $x = ltrim($x);
        } elseif ($trim) {
            $x = trim($x);
        }
        return $x;
    }
    foreach ($x as $k => $v) {
        $x[$k] = cms_clean_str($v, $trim, $tag, $slash);
    }
    return $x;
}

function cms_firewall($ip_allowed = '', $err_msg = '') {
    if (!$ip_allowed) {
        return;//no firewall setup
    }
    $arr_ip = explode(';', $ip_allowed);
    if (!$arr_ip || !$arr_ip[0]) {
        return;//ok--no firewall
    }
    $ip = cms_ip();
    foreach ($arr_ip as $i) {
        if (preg_match($i, $ip)) {
            return;//ok
        }
    }
    exit($err_msg);
}

function cms_html_js($src = '', $js = '') {
    if ($src) {
        return NL . '<script src="' . $src . '"></script>';
    } elseif ($js) {
        return NL . '<script>' . $js . '</script>';
    }
}

// please note this function not same as cms/func.php::cms_html8()
function cms_html8($str = '') {
    return htmlspecialchars($str, ENT_QUOTES);
    //,'UTF-8': with this para invalid str becomes empty str;
    //fm sidu 3.5 this para been turned off, any bug found please fix this then!!!
}

function cms_ip($int = 0) {
    $ip = isset($_SERVER['HTTP_X_REMOTE_ADDR']) ? $_SERVER['HTTP_X_REMOTE_ADDR'] : (
        isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 0
    );
    return $int ? sprintf('%u', ip2long($ip)) : $ip;//eg 255.2.3.4
}

/**
 * arr is array, object, or string
 * mode = ''  : displayed as nice json format: key : value, no quotes and folded
 * mode = php : use php print_r()
 * mode = dump: use php var_dump()
 * mode = json: displayed as full json format: "key" : "value",
 * mode = arr : displayed as array definition: 'key' =>'value',
 * level is used for internal loop -- no direct call
 */
function cms_pr($arr = null, $mode = '', $level = 0) {
    if (!$level) echo '<pre>';
    $is_OBJ = is_object($arr);
    if ('php' === $mode || 'dump' === $mode || (!is_array($arr) && !$is_OBJ)) {
        'dump' === $mode ? var_dump($arr) : print_r($arr);
        echo '</pre>';
        return;
    }
    //the following: '' json arr
    $num = count($arr);
    if (!$level) {
        echo '<span class="show green" data-src="next"><span class="show" data-src="next">';
        echo $is_OBJ ? 'object' : 'array';
        echo 'arr' == $mode && !$is_OBJ ? '(' : '{';
        echo '</span><span>' . $num . '</span></span><span class="hide">';
    }
    $tab = str_repeat('    ', $level + 1);
    $tran= ["\t"=>'\t', "\n"=>'\n', '\\'=>'\\\\'];
    if ('arr' === $mode) {
        $tran["'"] = '\\\'';
    } else {
        $tran['"'] = '\"';
    }
    $i = 0;
    foreach ($arr as $k => $v) {
        $is_arr = is_array($v);
        $is_obj = is_object($v);
        if ($v && !$is_arr && !$is_obj) {//check if json
            $v = trim($v);
            $s1= substr($v, 0, 1);
            $s2= substr($v, -1);
            if (($s1 == '[' && $s2 == ']') || ($s1 == '{' && $s2 == '}')) {
                $json = json_decode($v, 1);
                if (is_array($json)) {
                    $is_arr = 1;
                    $v = $json;
                }
            }
        }
        echo "\n" . $tab;
        echo ($is_arr || $is_obj) ? '<span class="show green" data-src="next"><span class="show" data-src="next">' : '';
        if ('arr' === $mode) {
            echo (is_numeric($k) && ceil($k) == $k ? $k : "'$k'") .' => ';
        } else {
            echo ('json' === $mode) ? '"'. $k .'" : ' : '<b>'. $k .'</b> : ';
        }
        if ($is_arr || $is_obj) {
            echo 'arr' == $mode ? ($is_arr ? 'array(' : 'object{') : '{';
            echo '</span><b>' . count($v) . '</b></span><span class="hide">';
            cms_pr($v, $mode, $level + 1);
            echo "\n" . $tab .'</span><span class="green">';
            echo ('arr' == $mode && $is_arr ? ')' : '}') .'</span>';
        } elseif (is_null($v)) {
            echo 'NULL';
        } elseif (is_numeric($v)) {
            echo $v;
        } else {
            $len = strlen($v);
            echo 'arr' == $mode ? "'" : ('json' === $mode ? '"' : '');
            echo $len > 50 ? '<span class="show" data-src="next"><span class="show" data-src="next">' : '';
            echo cms_html8(strtr(substr($v, 0, 50), $tran));
            echo $len > 50 ? '</span><i class="green">...</i></span><span class="hide">' : '';
            echo cms_html8(strtr(substr($v, 50), $tran));
            echo $len > 50 ? '</span>' : '';
            echo 'arr' == $mode ? "'" : ('json' == $mode ? '"' : '');
        }
        if ($mode && ++$i < $num) {
            echo ',';
        }
    }
    if (!$level) {
        echo NL . '</span><span class="green">';
        echo 'arr' == $mode && !$is_OBJ ? ')' : '}';
        echo '</span></pre>';
    }
}

/**
 * cms_form version 2 -- you must include only one version of cms_form
 * type = '', form, select, radio, checkbox, text, password, hiddhen, ...
 * data = action for form
 * attr = [id, class, style, method, list, defa, class_cbox, inline, label, class_label, ...]
 * form, name, action, ...
 */
function cms_form($type = 'form', $name = '', $data = null, $attr = null) {
    if ($type == 'end') {
        return "\n</form>";
    }
    $type = $type ?: 'form';
    if ($type == 'sele') {
        $type = 'select';
    } elseif ($type == 'cbox') {
        $type = 'checkbox';
    } elseif ($type == 'pass') {
        $type = 'password';
    }
    $is_input = !in_array($type, ['form', 'select', 'textarea']);
    if (in_array($type, ['select', 'checkbox', 'radio'])) {
        if (!is_array($attr) || !isset($attr['list'])) {
            $attr = ['list' => $attr];
        }
        $list = $attr['list'];
        if (!is_array($list)) {
            $list = [1 => $list];
        }
        $defa = isset($attr['defa']) ? $attr['defa'] : '';
        if (!$defa && $type == 'select' && !in_array('multiple', $attr)) {
            $defa = (isset($attr['label']) && $attr['label'] != -1)
                ? $attr['label']
                : ucwords(str_replace('_', ' ', $name));
        }
        if ($defa && $defa != -1) {
            if (!is_array($defa)) {
                $defa = ['' => $defa];
            }
            $list = $defa + $list;//either merge or + both got bug -- fix later
        }
    }
    if ($is_input) {
        $attr['type'] = $type;
    }
    if (!isset($attr['name']) && $type != 'form') {
        $attr['name'] = $name;
        if (($type == 'select ' && in_array('multiple', $attr)) || (
            $type == 'checkbox' && substr($attr['name'], -1) <> ']' &&
            is_array($attr['list']) && count($attr['list']) > 1
        )) {
            $attr['name'] .= '[]';
        }
    }
    if ($type == 'form') {
        if (!isset($attr['action'])) {
            if (isset($attr['url'])) {
                $attr['action'] = $attr['url'];
                unset($attr['url']);
            } elseif ($data) {
                $attr['action'] = $data;
            } else {
                $arr = explode('/', $_SERVER['SCRIPT_NAME']);
                $attr['action'] = array_pop($arr);
            }
        }
        if (!isset($attr['method']) || !in_array(strtolower($attr['method']), ['post', 'get'])) {
            $attr['method'] = 'post';
        }
    } elseif ($type == 'select' && !isset($attr['size'])) {
        $attr['size'] = 1;
    }
    $str = "\n<". ($is_input ? 'input' : $type) . cms_form_attr($attr);
    if ($type == 'form') {
        return $str .'>';
    } elseif ($type == 'textarea') {
        return $str .'>'. str_replace('</textarea>', '&lt;/textarea>', ($data ?: '')) .'</textarea>';
    } elseif ($type == 'select') {
        $str .= '>';
        foreach ($list as $k => $v) {
            if (is_array($v)) {
                $str .= "\n" .'<optgroup label="'. cms_html8($k) .'"'
                    . (isset($attr['class_'.$k]) ? ' class="'. $attr['class_'.$k] .'"' : '') .'>';
                foreach ($v as $k2 => $v2) {
                    $str .= cms_form_select_option($data, $attr, $k2, $v2);
                }
                $str .= "\n</optgroup>";
            } else {
                $str .= cms_form_select_option($data, $attr, $k, $v);
            }
        }
        return $str ."\n</select>";
    } elseif ($type == 'radio' || $type == 'checkbox') {
        if (!isset($attr['class_cbox'])) {
            $attr['class_cbox'] = (count($list) > 1) ? ' ' : '';
        }
        $res = in_array('class_no_hidden', $attr) ? '' : "\n" .'<input type="hidden" name="'. $name .'">';
        $str = trim($str);
        foreach ($list as $k => $v) {
            $res .= "\n<label>". $str .' value="'. cms_html8($k) .'"'
                . cms_form_selected($k, $data, ' checked') .'>'. (strlen($v) ? ' ' : '')
                . (isset($attr['class_'.$k]) ? '<span class="'. $attr['class_'.$k] .'">'. $v .'</span>' : $v)
                . '</label>'. $attr['class_cbox'];
        }
        return $res;
    }
    return $str .' value="'. cms_html8($data) .'">';
}

function cms_form_attr($attr = null) {
    if (!$attr) {
        return;
    } elseif (!is_array($attr)) {
        return ' '. $attr;
    }
    $style = '';
    $skip = ['label', 'defa', 'list', 'inline'];
    foreach ($attr as $k => $v) {
        $is_int = is_numeric($k);
        if (!is_array($v) && $v != -1 && substr($k, 0, 6) != 'class_' && ($is_int || !in_array($k, $skip))) {
            $style .= ' '. ($is_int ? cms_html8($v) : $k . (strlen($v) ? '="'. cms_html8($v) .'"' : ''));
        }
    }
    return $style;
}

function cms_form_select_option($data, $attr, $k, $v) {
    return "\n" .'  <option value="'. cms_html8($k) . '"' . cms_form_selected($k, $data)
        . (isset($attr['class_'.$k]) ? ' class="'. $attr['class_'.$k] .'"' : '')
        . '>' . $v . '</option>';
}

function cms_form_selected($k, $val, $str = ' selected') {
    if ((is_array($val) && in_array((string)$k, $val)) || ($k == $val && strlen($k) && strlen($val))) {
        return $str;
    }
}

function sidu_val($sql = '', $para = '') {
    $row = sidu_row($sql, $para);
    return is_array($row) ? array_shift($row) : $row;
}

function sidu_enum($sql = '', $para = '') {
    $rows = sidu_row($sql, $para, 'ALL NUM');
    $arr = array();
    foreach ($rows as $r) {
        $arr[] = isset($r[0]) ? $r[0] : null;
    }
    return $arr;
}

function sidu_list($sql = '', $para = '') {
    $rows = sidu_row($sql, $para, 'ALL NUM');
    $arr = [];
    foreach ($rows as $r) {
        if (isset($r[0])) {
            $arr[$r[0]] = isset($r[1]) ? $r[1] : null;
        }
    }
    return $arr;
}

function sidu_row($sql = '', $para = '', $mode = '') {
    if (!$sql) return array();
    $sth = sidu_run($sql, $para);
    if (!$sth) return array();
    $MODE = strtoupper($mode);
    if (!$MODE || $MODE == 'ASSOC') return $sth->fetch(PDO::FETCH_ASSOC);
    if ($MODE == 'NUM') return $sth->fetch(PDO::FETCH_NUM);
    if ($MODE == 'BOTH') return $sth->fetch();
    if ($MODE == 'ALL NUM') return $sth->fetchAll(PDO::FETCH_NUM);
    if ($MODE == 'ALL BOTH') return $sth->fetchAll();
    $rows = $sth->fetchAll(PDO::FETCH_ASSOC);
    if ($MODE == 'ALL ASSOC') return $rows;
    if (!count($rows) || !in_array($mode, array_keys($rows[0]))) return array();
    foreach ($rows as $r) $arr[$r[$mode]] = $r;//pk
    return $arr;
}
function sidu_rows($sql = '', $para = '', $mode = '') {
    $MODE = strtoupper($mode);
    if (!$MODE) $MODE = 'ASSOC';
    if ($MODE == 'NUM' || $MODE == 'BOTH' || $MODE == 'ASSOC') return sidu_row($sql, $para, 'ALL '. $MODE);
    return sidu_row($sql, $para, $mode);
}
function sidu_conn($conn = array(), $db = '') {
    if (!$db) {
        $dbs = explode(';', $conn['dbs'], 2);
        $db = $dbs[0];
    }
    if ($conn['eng'] == 'sqlite') return new PDO($conn['eng'] .':'. $db);
    $conn['pass'] = cms_dec($conn['pass'], 1);
    if (($conn['eng'] == 'mysql' && $conn['port'] == 3306)
     || ($conn['eng'] == 'pgsql' && $conn['port'] == 5432)
    ) $conn['port'] = 0;
    if ($conn['eng'] == 'cubrid' && !$conn['port']) $conn['port'] = 30000;
    if ($conn['eng'] == 'mysql' && $conn['char'] == 'latin1') $conn['char'] = '';
    $pdo = $conn['eng'] .':host='. $conn['host'] . ($db ? ';dbname='. $db : '') . ($conn['port'] ? ';port='. $conn['port'] : '');
    $options = $conn['char'] ? array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES '.$conn['char']) : null;//mysql only
    try {
        return new PDO($pdo, $conn['user'], $conn['pass'], $options);
    } catch (Exception $e) {
        echo 'Connection Error';
    }
}
function sidu_err($errStr = 0) {
    global $SIDU;
    $err = $SIDU['dbL']->errorCode();
    if (!$err || $err == '00000') return;
    $err = $SIDU['dbL']->errorInfo();
    if (!$errStr) return str_replace('<', '&lt;', $err[1]);
    return 'Err '. str_replace('<', '&lt;', $err[1] .': '. $err[2]);
}
function sidu_run($sql = '', $para = '') {
    global $SIDU;
    $time_start = microtime(true);
    $res = $SIDU['dbL']->query($sql);
    if (!$SIDU['page']['his']) return $res;
    $time_end = microtime(true);
    $time = round(($time_end - $time_start) * 1000);
    $err = $SIDU['page']['hisErr'] ? sidu_err(1) : 0;
    sidu_log('B', $sql, $time, $err);
    return $res;
}
function sidu_use_db($db = '', $sch = '') {
    global $SIDU;
    $db = trim($db);
    if (!$db) return;
    if ($SIDU['eng'] == 'mysql') {
        sidu_run('USE '. sidu_keyw($db));
    } else {
        $SIDU['dbL'] = sidu_conn($SIDU['conn'][$SIDU[0]], $db);
        if ($SIDU['eng'] == 'pgsql' && $sch) sidu_run('SET search_path TO '. sidu_keyw($sch));
    }
}
function sidu_slash($eng = '') {
    return ($eng == 'mysql' || ($eng == 'pgsql' && sidu_val('SHOW standard_conforming_strings') != 'on'));
}

function sidu_close($id) {
    global $SIDU;
    unset($SIDU['conn'][$id]);
    if (!$SIDU['conn']) {
        sidu_cook_set('CONN', '', -1);
        sidu_cook_set('COOK', '', -1);
        return;
    }
    if (isset($SIDU['cook'][$id])) unset($SIDU['cook'][$id]);
    sidu_cook_set('CONN', $SIDU['conn']);
    sidu_cook_set('COOK', $SIDU['cook']);
    $ids = array_keys($SIDU['conn']);
    return $ids[0];
}
function sidu_cook_copy(&$SIDU) {
    if (!isset($SIDU['cook'][$SIDU[0]])) return;
    $cook = $SIDU['cook'][$SIDU[0]];
    for ($i = 1; $i < 5; $i++) {
        if (isset($cook[$i])) $SIDU[$i] = $cook[$i];
    }
}
function sidu_cook_get($cook = 'CONN') {
    $cook = 'SIDU'. ($cook ?: 'CONN');
    if (!isset($_COOKIE[$cook])) {
        return array();
    }
    $conn = json_decode(cms_dec($_COOKIE[$cook], 1), 1);
    return is_array($conn) ? $conn : array();
}
function sidu_cook_set($name = '', $cook = '', $expire = 0) {
    setcookie('SIDU'.$name, cms_enc(json_encode($cook), 1), $expire);
}
function sidu_cook_set_db(&$SIDU) {
    if (!$SIDU[1]) return;
    $cook = isset($SIDU['cook'][$SIDU[0]]) ? $SIDU['cook'][$SIDU[0]] : array(0, '', '', '', '');
    if ($SIDU[1] != $cook[1] || $SIDU[2] != $cook[2] || $SIDU[3] != $cook[3] || $SIDU[4] != $cook[4]) {
        $SIDU['cook'][$SIDU[0]] = array($SIDU[0], $SIDU[1], $SIDU[2], $SIDU[3], $SIDU[4]);
        sidu_cook_set('COOK', $SIDU['cook']);
    }
}
function sidu_cook_set_tab(&$SIDU) {
    sidu_cook_set_db($SIDU);
    $page = $SIDU['page'];
    if ($SIDU['data']['cmd'] == 'grid') $page['gridMode']++;
    if ($page['gridMode'] < 0 || $page['gridMode'] > 2) $page['gridMode'] = 0;
    $pgSize = ceil($SIDU['data']['pgSize']);
    if ($pgSize) $page['pgSize'] = $pgSize;
    if ($page['pgSize'] < -1 || !$page['pgSize']) $page['pgSize'] = 10;
    if ($page != $SIDU['page']) {
        sidu_cook_set('PAGE', $page);
        $SIDU['page'] = $page;
    }
}
function sidu_init(&$SIDU, $data) {
    $SIDU = explode(',', $data['id']);
    $SIDU += array_fill(0, 8, '');
    $SIDU['ajax'] = $data['ajax'];
    //0lang.1gridMode.2pgSize.3tree.4sortObj.5sortData.6menuTextSQL.7menuText.8his.9hisErr.10hisSQL.11hisData.12dataEasy(pg).13oid(pg).14slconn
    $page = sidu_cook_get('PAGE');
    if (isset($data['lang'])) $page['lang'] = $data['lang'];
    $defa = array(
        'lang' => 'en',
        'gridMode' => 0, // 0 text 1 form 2 json
        'pgSize' => 10,
        'tree' => '_', // menu tree group
        'sortObj' => 1,
        'sortData' => 2,
        'menuTextTool' => 1,
        'menuTextData' => 0,
        'his' => 1,
        'hisErr' => 0,
        'hisSQL' => 0,
        'hisData' => 1, // log original 5 rows of data before upd|del
        'dataEasy' => 1,// auto clean pgsql int char varchar
        'oid' => 1, // pgsql show oid
        'nav' => 'defa',
    );
    $SIDU['page'] = $page + $defa;//if page[k] exist ignore defa
    if ($SIDU['page']['lang'] != 'cn') $SIDU['page']['lang'] = 'en';
    $SIDU['conn'] = sidu_cook_get('CONN');
    if ((!$SIDU[0] || !isset($SIDU['conn'][$SIDU[0]])) && substr($_SERVER['SCRIPT_NAME'], -8) != 'conn.php') {
        echo cms_html_js('', 'top.location="./conn.php"');
        exit;//no connection
    }
    $SIDU['cook'] = sidu_cook_get('COOK');
    $conn = isset($SIDU['conn'][$SIDU[0]]) ? $SIDU['conn'][$SIDU[0]] : array();
    if (isset($conn['eng'])) {
        $SIDU['eng'] = $conn['eng'];
        $SIDU['dbL'] = sidu_conn($conn, $SIDU[1]);
    }
    $sql = 'ALL,ANALYZE,AND,AS,ASC,BINARY,BOTH,CASE,CHECK,COLLATE,COLUMN,CONSTRAINT,CREATE,CROSS,CURRENT_DATE,CURRENT_TIME,CURRENT_TIMESTAMP,CURRENT_USER,DEFAULT,DESC,DISTINCT,ELSE,FALSE,FETCH,FOR,FOREIGN,FROM,GRANT,GROUP,HAVING,IN,INNER,INTO,IS,JOIN,LEADING,LEFT,LIKE,LIMIT,LOCALTIME,LOCALTIMESTAMP,NATURAL,NOT,ON,OR,ORDER,OUTER,PRIMARY,REFERENCES,RIGHT,SELECT,TABLE,THEN,TO,TRAILING,TRUE,UNION,UNIQUE,USING,WHEN,WHERE,WITH';
    $pg  = 'ANALYSE,ANY,ARRAY,ASYMMETRIC,AUTHORIZATION,CAST,COLLATION,CONCURRENTLY,CURRENT_CATALOG,CURRENT_ROLE,CURRENT_SCHEMA,DEFERRABLE,DO,END,EXCEPT,FREEZE,FULL,ILIKE,INITIALLY,INTERSECT,ISNULL,LATERAL,NOTNULL,OFFSET,ONLY,OVERLAPS,PLACING,RETURNING,SESSION_USER,SIMILAR,SOME,SYMMETRIC,USER,VARIADIC,VERBOSE,WINDOW';
    $my  = 'ACCESSIBLE,ADD,ALTER,ASENSITIVE,BEFORE,BETWEEN,BIGINT,BLOB,BY,CALL,CASCADE,CHANGE,CHAR,CHARACTER,CONDITION,CONTINUE,CONVERT,CURSOR,DATABASE,DATABASES,DAY_HOUR,DAY_MICROSECOND,DAY_MINUTE,DAY_SECOND,DEC,DECIMAL,DECLARE,DELAYED,DELETE,DESCRIBE,DETERMINISTIC,DISTINCTROW,DIV,DOUBLE,DROP,DUAL,EACH,ELSEIF,ENCLOSED,ESCAPED,EXISTS,EXIT,EXPLAIN,FLOAT,FLOAT4,FLOAT8,FORCE,FULLTEXT,GET,HIGH_PRIORITY,HOUR_MICROSECOND,HOUR_MINUTE,HOUR_SECOND,IF,IGNORE,INDEX,INFILE,INOUT,INSENSITIVE,INSERT,INT,INT1,INT2,INT3,INT4,INT8,INTEGER,INTERVAL,IO_AFTER_GTIDS,IO_BEFORE_GTIDS,ITERATE,KEY,KEYS,KILL,LEAVE,LINEAR,LINES,LOAD,LOCK,LONG,LONGBLOB,LONGTEXT,LOOP,LOW_PRIORITY,MASTER_BIND,MASTER_SSL_VERIFY_SERVER_CERT,MATCH,MAXVALUE,MEDIUMBLOB,MEDIUMINT,MEDIUMTEXT,MIDDLEINT,MINUTE_MICROSECOND,MINUTE_SECOND,MOD,MODIFIES,NO_WRITE_TO_BINLOG,NONBLOCKING,NUMERIC,OPTIMIZE,OPTIMIZER_COSTS,OPTION,OPTIONALLY,OUT,OUTFILE,PARTITION,PRECISION,PROCEDURE,PURGE,RANGE,READ,READS,READ_WRITE,REAL,REGEXP,RELEASE,RENAME,REPEAT,REPLACE,REQUIRE,RESIGNAL,RESTRICT,RETURN,REVOKE,RLIKE,SCHEMA,SCHEMAS,SECOND_MICROSECOND,SENSITIVE,SEPARATOR,SET,SHOW,SIGNAL,SMALLINT,SPATIAL,SPECIFIC,SQL,SQLEXCEPTION,SQLSTATE,SQLWARNING,SQL_BIG_RESULT,SQL_CALC_FOUND_ROWS,SQL_SMALL_RESULT,SSL,STARTING,STRAIGHT_JOIN,TERMINATED,TINYBLOB,TINYINT,TINYTEXT,TRIGGER,UNDO,UNLOCK,UNSIGNED,UPDATE,USAGE,USE,UTC_DATE,UTC_TIME,UTC_TIMESTAMP,VALUES,VARBINARY,VARCHAR,VARCHARACTER,VARYING,WHILE,WRITE,XOR,YEAR_MONTH,ZEROFILL';
    $sql.= ','. (isset($SIDU['eng']) && $SIDU['eng'] == 'mysql' ? $my : $pg);
    $SIDU['sql_keyw'] = explode(',', $sql);
}
function sidu_is_blob($eng, $col) {
    if ($col['maxchar'] > 300) return 1;
    if ($eng !== 'mysql' && $eng != 'pgsql') return 0;
    return in_array($col['typ'], array('text','mediumtext','longtext','blob','mediumblob','longblob'));
}
function sidu_keyw($keyw = '') {
    global $SIDU;
    if (!strlen($keyw)) return '';
    $is_int = is_numeric($keyw[0]);
    $has_hyphen = (strpos($keyw, '-') !== false);
    $has_dot = (strpos($keyw, '.') !== false);
    if (!$is_int && !in_array(strtoupper($keyw), $SIDU['sql_keyw']) && !$has_hyphen && !$has_dot) {
        return $keyw;
    } elseif ($SIDU['eng'] != 'mysql') {
        return '"'. $keyw .'"';
    }
    //if (!$is_int && !$has_hyphen) return $keyw;
    return "`$keyw`";
}
function sidu_log($typ, $log = '', $time = 0, $err = '') { // [id]ts Back|Sql|Err|Data logs
    global $SIDU;
    $cid = $SIDU['conn'][$SIDU[0]]['cid'];
    $his = &$_SESSION['siduhis'][$cid];
    $ts  = date('Y-m-d H:i:s');
    if ($typ == 'D' && $SIDU['page']['hisData']) {
        $his[] = $ts .' D 0 ['. $err .']'. (is_array($log) ? implode('»', $log) : $log);
    } else{
        if (($typ == 'B' && $SIDU['page']['his']) || ($typ == 'S' && $SIDU['page']['hisSQL'])) $his[] = $ts .' '. $typ .' '. $time .' '. $log;
        if ($err && $SIDU['page']['hisErr']) $his[] = $ts .' E 0 '. $err;
    }
}
function sidu_menu_tree_init(&$arr, $str, $tree) {
    $tab = sidu_menu_tree_tab($str, $tree);
    if ($tab == '') $tab = $str;
    $arr[$tab][] = $str;
}
function sidu_menu_tree_tab($str = '', $tree = '_') { // return tab from tab_table_name
    if ($tree == '_') {
        $arr = explode('_', $str, 3);
        $tab = $arr[0];
        if (isset($arr[2]) && $arr[2] != '' && (
            $str[0] == '_' || in_array($arr[0], array('pg', 'log', 'wp', 'drupal'))
        )) $tab = "$arr[0]_$arr[1]";
    } elseif ($tree) {
        $tab = substr($str, 0, $tree);
    }
    if ($tab == $str) {
        if (strlen($tab) > 2 && substr($tab, -1) == 's') {
            $tab = substr($tab, 0, -1);
        } else {
            $tab = '';
        }
    }
    return $tab;
}
function sidu_menu_tree_tab_del(&$arr) { // change log_2016 to log if only has one child etc
    if (!is_array($arr)) return;
    foreach ($arr as $k => $v) {
        if (substr($k, 0, 4) == 'log_' && count($v) == 1) {
            $arr['log'][] = $v[0];
            unset($arr[$k]);
        }
        if (substr($k, 0, 3) == 'pg_' && count($v) == 1) {
            $arr['pg'][] = $v[0];
            unset($arr[$k]);
        }
    }
    if (isset($arr['log'])) sort($arr['log']);
    if (isset($arr['pg']))  sort($arr['pg']);
}
function sidu_pg_oidStr($SIDU) {
    if (!$SIDU['data']['oid']) return '';
    return '&#38;oid='. $SIDU['data']['oid'];
}
function sidu_pg_pk($tab, $nsp) {
    $pk = sidu_val("SELECT pg_get_constraintdef(oid,TRUE) FROM pg_constraint\nWHERE contype='p' AND conrelid=$tab AND connamespace=$nsp");
    if (!$pk) return;
    return substr($pk, 13, -1); // PRIMARY KEY(col)
}
function sidu_sort(&$s1, &$s2, &$sort, $mode) {
    if (!$sort) return;
    $sort = sidu_keyw($sort);
    if ($mode == 1) { // 1 sort | 2 sort
        $s1 = $sort . ($s1 == $sort .' desc' ? '' : ' desc');
        $s2 = $sort = '';
        return;
    }
    if (!$s1) $s1 = $sort .' desc';
    elseif ($s1 == $sort .' desc') $s1 = $sort;
    elseif ($s1 == $sort) { $s1 = $s2; $s2 = ''; }
    elseif (!$s2) $s2 = $sort .' desc';
    elseif ($s2 == $sort .' desc') $s2 = $sort;
    elseif ($s2 == $sort) $s2 = '';
    else { $s1 = $s2; $s2 = $sort .' desc'; }
    $sort = '';

    return; // the following sort is by defaut:asc
    if (!$sort) return;
    if ($mode == 1) { // 1 sort | 2 sort
        $s1 = $sort . ($s1 == $sort ? ' desc' : '');
        $s2 = $sort = '';
        return;
    }
    if (!$s1) $s1 = $sort;
    elseif ($s1 == $sort) $s1 .= ' desc';
    elseif ($s1 == $sort .' desc') { $s1 = $s2; $s2 = ''; }
    elseif (!$s2) $s2 = $sort;
    elseif ($s2 == $sort) $s2 .= ' desc';
    elseif ($s2 == $sort .' desc') $s2 = '';
    else{ $s1 = $s2; $s2 = $sort; }
    $sort = '';
}
function sidu_sort_arr($arr, $s1 = '', $s2 = '') {
    if (!$s1 || !$arr || !is_array($arr)) return $arr;
    $desc1 = $desc2 = 0;
    if (substr($s1, -5) == ' desc') {
        $s1 = substr($s1, 0, -5); $desc1 = 1;
    }
    if ($s2 && substr($s2, -5) == ' desc') {
        $s2 = substr($s2, 0, -5); $desc2 = 1;
    }
    $s1 = trim($s1, '`"\'[]');
    $s2 = trim($s2, '`"\'[]');
    foreach ($arr as $k => $v) {
        $a1[$k] = $v[$s1];
        if ($s2) $a2[$k] = $v[$s2];
    }
    if ($s2) {
        array_multisort($a1, ($desc1 ? SORT_DESC : SORT_ASC), $a2, ($desc2 ? SORT_DESC : SORT_ASC), $arr);
    } else {
        array_multisort($a1, ($desc1 ? SORT_DESC : SORT_ASC), $arr);
    }
    return $arr;
}
function sidu_sl_pk($tab) {
    $rows = sidu_rows("pragma table_info($tab)");
    $pk = array();
    foreach ($rows as $r){
        if ($r['pk']) $pk[] = $r['name'];
    }
    return implode(',', $pk);
}
function sidu_grid_align($data, &$col) { // this method sucks and not good
    $ints = array('int', 'bigint', 'smallint', 'mediumint', 'tinyint', 'serial', 'bigserial', 'oid', 'int2', 'int4', 'int8');
    foreach ($data as $r) {
        foreach ($r as $c => $v) {
            if (in_array($col[$c]['typ'], $ints)) {
                $col[$c]['is_int'] = 1;
            } else {
                if (!isset($col[$c]['is_int']) || !strlen($col[$c]['is_int'])) $col[$c]['is_int'] = 1;
                if ($col[$c]['is_int'] && !is_null($v) && !is_numeric($v)) $col[$c]['is_int'] = 0;
            }
        }
    }
}
function sidu_grid_cout($SIDU, $sql = '') {
    if (!$sql) echo cms_form('form', 'dataTab', "tab.php?id=$SIDU[0],$SIDU[1],$SIDU[2],$SIDU[3],$SIDU[4],$SIDU[5],$SIDU[6]" . sidu_pg_oidStr($SIDU), array('id'=>'dataTab'));
    $gridMode = $SIDU['page']['gridMode'];
    if ($gridMode == 2) {
        foreach ($SIDU['rows'] as $r) cms_pr($r);
        return;
    }
    $colHide = $colGrid = $colShow = $colHead = $colWhere = $colNew = '';
    $w = 30; // first cbox width
    $PK = $FK = array();
    foreach ($SIDU['cols'] as $c => $v) {
        $w += $v['grid'];
        $title = ($v['pk'] == 'PRI' ? 'PK ' : '') . $v['col'] .' '. str_replace("'", '', $v['typ']);
        $color = ($v['extra'] == 'auto_increment' || $v['typ'] == 'serial' || $v['typ'] == 'bigserial') ? 'red' : ($v['pk'] == 'PRI' ? 'blue' : '');
        $class[$c]= ($v['grid'] > 0 && !$v['is_int']) ? '' : ' class="'. trim(($v['grid'] > 0 ? '' : 'hide') .' '. ($v['is_int'] ? 'ar' : '')) .'"';
        $colShow .= NL .'  <td'. $class[$c] .'><span class="xsort" title="'. lang(124) .'"><i class="i-sort1"></i><i class="i-sort2d"></i></span><i class="i-hide hideCol" title="'. lang(105) .'"></i></td>'; // <i class="i-arrFirst gridDec"></i><i class="i-arrLast gridInc"></i>
        $colHead .= NL. '  <td style="width:'. abs($v['grid']) .'px"'. $class[$c] .' title="'. $title .'"><b></b><div>'. get_sort_css($c, $SIDU[5], $SIDU[6]) . ($color ? '<span class="'.$color.'">'.$v['col'].'</span>' : $v['col']) .'</div></td>';
        $is_blob[$c] = sidu_is_blob($SIDU['eng'], $v);
        if (!$sql) {
            $colHide .= NL .'  <b'. ($v['grid'] > 0 ? ' class="hide"' : '') .'>'. $c .'</b> ';
            $colGrid .= cms_form('text', 'grid['.$c.']', $v['grid']);
            $colWhere.= NL .'  <td'. $class[$c] .'>'. cms_form('text', 'where['.$c.']', $SIDU['data']['where'][$c], array('class'=>'where')) .'</td>';
            if ($v['defa'] == 'CURRENT_TIMESTAMP' || $v['defa'] == 'now()') $defa = date('Y-m-d H:i:s');
            elseif (substr($v['defa'], 0, 9) == "nextval('") $defa = '';
            else $defa = $v['defa'] ?: ($v['is_null'] == 'NO' ? '' : 'NULL');
            $FK[$c] = sidu_grid_fk($v['typ'], $v['fk'], $v['is_null']);
            $colNew  .= NL .'  <td'. $class[$c] .'>'. sidu_grid_cout_col($c, $defa, $is_blob, $FK, $v['typ']) .'</td>';
            if ($v['pk'] == 'PRI') $PK[] = $c;
        }
    }
    if (!$sql) {
        $attr = array('placeholder'=>'where ... eg. id=123 AND ...', 'style'=>'max-width:500px;width:90%', 'id'=>'sidu_where', 'class'=>'where');
        if (!$SIDU['data']['where']['_SIDU_TAB_WHERE_']) $attr['class'] .= ' hide';
        echo cms_form('text', 'where[_SIDU_TAB_WHERE_]', $SIDU['data']['where']['_SIDU_TAB_WHERE_'], $attr);
        echo NL .'<div id="colHide">'. NL .'  <i class="hide"></i>', $colHide . NL .'</div>';
        echo NL .'<div id="colGrid" class="hide">'. NL .'<input>', $colGrid . NL .'</div>';
    }
    $sqlShowAll = ($sql && count($SIDU['rows']) > 6) ? NL .'<b class="show" data-src=".sqlShowAll">Show All</b>' : '';
    echo $sqlShowAll . NL .'<table class="grid'. ($sql ? '' : ' data') .'" style="width:'. $w .'px;margin-bottom:0">';
    if (!$sql) echo NL .'<tr class="off hide hand grey icon" id="colShow">'. NL .'  <td><i class="i-hide hideCol allCol" title="Toggle All Field"></i></td>', $colShow . NL .'</tr>';
    echo NL .'<tr class="th hand">'. NL .'  <td><input type="checkbox" id="checkAll"></td>', $colHead . NL .'</tr>';
    if (!$sql) {
        echo NL .'<tr class="off">'. NL .'  <td><i class="icon i-find show" data-src="#sidu_where" title="Show Raw Where"></i></td>', $colWhere . NL .'</tr>';
        echo NL .'<tr id="newR" class="hide">'. NL .'  <td><input type="checkbox"></td>', $colNew . NL .'</tr>';
    }
    $ttl = array();
    $is_my_info_tab = $SIDU['eng'] == 'mysql' && $SIDU[1] == 'information_schema' && $SIDU[4] == 'TABLES' ? 1 : 0;
    foreach ($SIDU['rows'] as $i => $r) {
        $sqlCSS = ($sqlShowAll && $i > 5) ? ' class="hide sqlShowAll"' : '';
        echo '<tr'. $sqlCSS .'>'. NL .'  <td><input type="checkbox"></td>';
        foreach ($r as $c => $v) {
            $typ = $SIDU['cols'][$c]['typ'];
            echo NL .'  <td'. (is_null($v) && $sql ? ($class[$c] ? substr($class[$c], 0, -1).' null"' : ' class="null"') : $class[$c]) .'>';
            if (is_null($v)) $v = 'NULL';
            if (($is_blob[$c] || strlen($v) > 200 || strpos($v, NL)) && (!$gridMode || substr($typ, 0, 4) != 'set(')) {
                echo '<input type="text" readonly class="bg1 Hpop'. ($v === 'NULL' ? ' null' : '') .'" value="'. cms_html8(substr($v, 0, 200)) .'">'. cms_form('textarea', $c, str_replace('&', '&#38;', $v), array('class' => 'hide'));
            } elseif ($gridMode) {
                echo sidu_grid_cout_col($c, $v, $is_blob, $FK, $typ);
            } else {
                if (!$sql) echo '<div data-name="'. $c .'"'. ($v === 'NULL' ? ' class="null"' : '') . (isset($FK[$c][$v]) ? ' title="'.cms_html8($FK[$c][$v]).'"' : '') .'>';
                echo $is_my_info_tab && $c == 'TABLE_NAME'
                    ? "<a href=\"tab.php?id=$SIDU[0],$r[TABLE_SCHEMA],$SIDU[2]," . ($r['TABLE_TYPE'] == 'BASE TABLE' ? 'r' : 'v') . ",$v\">$v</a>"
                    : nl2br(cms_html8($v));
                if (!$sql) {
                    echo '</div>';
                    if (isset($SIDU['fks'][$c]) && $v && $v != 'NULL') {
                        $fk = $SIDU['fks'][$c];
                        echo '<a href="tab.php?id='. $SIDU[0] .','. $fk['db'] .',0,r,'. $fk['tab'] .'&where['. $fk['col'] .']=='. $v .'" class="fk">..</a>';
                    }
                }
            }
            if (!$sql && (in_array($c, $PK) || !$PK)) echo cms_form('hidden', 'KEY.'.$c, ($is_blob[$c] && strlen($v) > 50 ? '::md5BLOB::'.md5($v) : $v));
            echo '</td>';
            $ttl[$c][] = is_numeric($v) ? $v : 0; // avoid php 7.1 warning
        }
        echo NL .'</tr>';
    }
    if (count($SIDU['rows']) > 1) {
        echo NL .'<tr id="colTtl" title="'. lang(125) .'">'. NL .'  <td class="hideP hand" title="'. lang(126) .'"></td>';
        foreach ($ttl as $c => $v) {
            $sum = array_sum($v);
            echo NL .'  <td'. $class[$c] .' title="TTL: '. number_format($sum) .'; Max: '. number_format(max($v)) .'; Min: '. number_format(min($v)) .'; Avg: '. number_format($sum / count($v), 1) .'">', ($sum ? number_format($sum) : '') ,'</td>';
        }
        echo NL .'</tr>';
    }
    echo NL .'</table>';
    if (!$sql) echo cms_form('end');
}
function sidu_grid_cout_col($c, $v, $is_blob, $FK, $typ = '') {
    $attr = array();
    if (is_null($v) || $v === 'NULL') {
        $v = 'NULL';
        $attr = array('class' => 'null');
    }
    if (substr($typ, 0, 4) == 'set(') {
        $attr[] = 'multiple';
        $attr['class'] = isset($attr['class']) ? $attr['class'] .' mult-sel' : 'mult-sel';
        $v = explode(',', $v);
    }
    if (!is_array($v) && ($is_blob[$c] || strlen($v) > 200)) return '<input type="text" readonly class="bg1 Hpop'. ($v == 'NULL' ? ' null' : '') .'" value="'. cms_html8(substr($v, 0, 200)) .'">'. cms_form('textarea', $c, $v, array('class' => 'hide'));
    if (isset($FK[$c])) {
        $attr['list'] = $FK[$c];
        return cms_form('select', $c, $v, $attr);
    }
    /*if ($typ != 'date' && $typ != 'time') {
        if ($typ == 'datetime' || $typ == 'timestamp') $typ = 'datetime-local';
        else $typ = 'text';
    }*/
    if ($typ != 'date') $typ = 'text';
    return cms_form($typ, $c, $v, $attr);
}
function sidu_grid_fk($colTyp, $fk, $is_null) {
    if ($is_null != 'NO') $arr['NULL'] = 'NULL';
    $is_set = substr($colTyp, 0, 4) == 'set(';
    if (substr($colTyp, 0, 5) == 'enum(' || $is_set) {
        $arr2 = explode("','", substr($colTyp, ($is_set ? 5 : 6), -2));
        foreach ($arr2 as $k) $arr[$k] = $k;
        return $arr;
    }
    if (!$fk) return; // ref_tab, ref_col;name, whereSort max200
    $cols = explode(';', $fk['ref_cols'], 2);//exp default colSep=, so make it ; here
    $col0 = sidu_keyw($cols[0]);
    $col1 = isset($cols[1]) ? sidu_keyw($cols[1]) : $col0;
    $arr2 = sidu_list("SELECT DISTINCT $col0,$col1 FROM ". sidu_keyw($fk['ref_tab']) ." $fk[where_sort] LIMIT 201");
    if (count($arr2) > 200) return;
    $arr = array();
    foreach ($arr2 as $k => $v) $arr[$k] = $v;
    return $arr;
}
function sidu_grid_init($data, $col, &$px) {
    foreach ($data as $r) {
        $len = strlen($r[$col]);
        $grid = $len * ($len > 15 ? 7.5 : 8.5);
        if ($grid > $px) $px = $grid;
        if ($px > 300) {
            $px = 300;
            return;
        }
    }
}
function sidu_grid_width(&$SIDU) {
    foreach ($SIDU['cols'] as $c => $v) { // init grid size
        $px = isset($SIDU['data']['grid'][$c]) ? ceil($SIDU['data']['grid'][$c]) : 0;
        if (!$px) {
            sidu_grid_init($SIDU['rows'], $c, $px);
            if ($px < 60) $px = 60;
            if ($px == 60) {
                $len = strlen($c) * 8;
                if ($len > $px) $px = $len;
                if ($px  > 110) $px = 110;
            }
        }
        $SIDU['cols'][$c]['grid'] = $px;
        //$SIDU['cols'][$c]['hide'] = isset($SIDU['data']['xCol'][$c]) && $SIDU['data']['xCol'][$c] ? 1 : 0;
    }
}

function lang($id = 0, $arr = null) {
    global $LANG;
    $id = isset($LANG[$id]) ? $LANG[$id] : $id;
    if (!isset($arr)) return $id;
    if (!is_array($arr)) return str_replace('%0%', $arr, $id);
    foreach ($arr as $k => $v) $tr[] = '%'. $k .'%';
    return str_replace($tr, $arr, $id);
}
function get_sort_css($name = '', $sort1 = '', $sort2 = '') {
    if ($sort1 === '' && $sort2 === '') return '';
    $class = '';
    $name = sidu_keyw($name);
    if ($name == $sort1) $class = 'sort1';
    elseif ($name .' desc' == $sort1) $class = 'sort1d';
    elseif ($name == $sort2) $class = 'sort2';
    elseif ($name .' desc' == $sort2) $class = 'sort2d';
    if (!$class) return '';
    return '<i class="icon i-'. $class .'"></i>';
}

function obj_save($SIDU, $objs, $cmd) {
    $typ = array('r'=>'TABLE', 'v'=>'VIEW', 'S'=>'SEQUENCE', 'f'=>'FUNCTION');
    $typ = $typ[$SIDU[3]];
    $cmd2='';
    if (($cmd == 'ANALYZE' && $SIDU['eng'] == 'pgsql') || $cmd == 'VACUUM') ; // doing nothing
    elseif (in_array($cmd, array('DROP', 'ANALYZE', 'CHECK', 'OPTIMIZE', 'REPAIR', 'REINDEX'))) $cmd .= ' '. $typ;
    elseif ($cmd == 'EMPTY') $cmd = ($SIDU['eng'] =='sqlite') ? 'DELETE FROM' : 'TRUNCATE TABLE';
    elseif ($cmd == 'DROP CASCADE' || $cmd == 'TRUNCATE CASCADE') {
        $arr = explode(' ', $cmd);
        $cmd = $arr[0] .' '. $typ;
        $cmd2= ' '. $arr[1];
    }
    $objs = explode(',', $objs);
    if (substr($cmd, 0, 17) == 'CHANGE ENGINE TO ') {
        $engTo = substr($SIDU['data']['objcmd'], 17);
        $rows = sidu_rows("SHOW TABLE STATUS FROM `$SIDU[1]`");
        foreach ($rows as $r) {
            if ($r['Engine'] != $engTo && in_array($r['Name'], $objs)) obj_save_run("ALTER TABLE `$SIDU[1]`.`$r[Name]` ENGINE = $engTo");
        }
    } else {
        foreach ($objs as $v) obj_save_run($cmd .' '. sidu_keyw($v) . $cmd2);
    }
}
function obj_save_run($sql = '') {
    sidu_run($sql);
    $err = sidu_err(1);
    echo NL .'<br>'. $sql . NL .'<br><span class="'. ($err ? 'red' : 'green') .'">'. ($err ?: 'OK') .'</span>';
}

function html_hkey($key = '', $title = '') {
    if ($key == ',') $str = '&lt;';
    elseif ($key == '.') $str = '&gt;';
    elseif ($key == '=') $str = '+';
    else $str = $key;
    return ' accesskey="'. $key .'" title="'. $title .' - Fn+'. $str .'"';
}
function html_meta($SIDU) {
    $url = explode('/', $_SERVER['SCRIPT_NAME']);
    $url = array_pop($url);
    if ('db.php' == $url || 'tab.php' == $url) {
        $title = ($SIDU[4] != '' ? $SIDU[4] .' « ': '') . ($SIDU[2] ? $SIDU[2] .' « ' : '') . $SIDU[1];
    } else {
        $title = 'SIDU '. $SIDU['sidu_ver'] .' Database Web GUI: MySQL + PostgreSQL + SQLite - topnew.net/sidu';
    }
    echo '<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta http-equiv="x-ua-compatible" content="ie=edge">
<title>', $title, '</title>
<meta name="description" content="MySQL SIDU, PostgreSQL SIDU, SQLite SIDU: Free SQL client front-end GUI - Select Insert Delete Update">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="keywords" content="MySQL,SIDU,SQL client,font-end GUI,PostgreSQL,SQLite">
<meta name="author" content="Topnew Geo">
<link rel="shortcut icon" href="sidu.png">
<link rel="apple-touch-icon" href="sidu.png">
<link rel="stylesheet" href="sidu.css">
<script src="jquery-1.11.3.min.js"></script>
<script src="login-enc.js"></script>
<script src="login-md5.js"></script>';
    if ('index.php' == $url) {
        echo '
<link rel="stylesheet" href="codemirror.css">
<script src="codemirror.js"></script>
<script src="codemirror.sql.js"></script>
<script>
window.onload=function(){
  window.editor=CodeMirror.fromTextArea(document.getElementById("sqlsT"),{
    mode:"text/x-sql",
    smartIndent:true,
    lineNumbers:true,
    lineWrapping:true,
    styleActiveLine:true
  });
};
</script>';
    }
    echo NL .'<script src="sidu.js"></script>', NL, '</head>', NL, '<body><div id="sqlwait"></div><div'. ('index.php' == $url ? '' : ' id="main"').' class="flex fullH">', NL;
}
function html_tool($SIDU) {
    $id = '?id='. $SIDU[0];
    echo '<div class="tool icon">
  <input type="hidden" id="sidu0" value="'. $SIDU[0] .'">
  <a class="ajax" href="home.php'. $id .'"', html_hkey('B', lang(3405)), '><i></i></a>
  <i class="i-menu a resize" data-id="menu"', html_hkey('/', lang(3420)), '></i>
  <i class="i-main a resize" data-id="sqls"', html_hkey('\\',lang(3421)), '></i>
  <a class="ajax" href="conn.php"', html_hkey('N', lang(3406)) ,'><i class="i-conn"></i></a>
  <a class="a xwin" data-url="tab-new.php'. $id .'" title="', lang(1401) ,'"><i class="i-plus"></i></a>
  <a class="a" id="runA"', html_hkey('A', lang(3410)) ,'><i class="i-runA"></i>', lang(3411) ,'</a>
  <a class="a" id="runR"', html_hkey('R', lang(3412)) ,'><i class="i-run"></i>', lang(3413) ,'</a>
  <a class="a" id="runM"', html_hkey('M', lang(3414)) ,'><i class="i-mult"></i>', lang(3415) ,'</a>
  <a class="a" id="sqlLoad"><i class="i-folderOpen"></i>SQL</a>
  <input type="file" id="sqlLoadFile" class="hide">
  <a class="a" id="sqlSave"><i class="i-save"></i>SQL</a>
  <a class="ajax" href="his.php'. $id .'"', html_hkey('H', lang(3416)) ,'><i class="i-time"></i>', lang(3417) ,'</a>';
    if (!isset($SIDU['eng'])) {
        $SIDU['eng'] = 'mysql';
    }
    if ($SIDU['eng'] == 'mysql' || $SIDU['eng'] == 'pgsql') {
        $sql = 'sql.php'. $id .'&#38;sql=';
        echo NL, '  <a class="ajax" href="'. $sql .'show+vars"', html_hkey('V', lang(3422)) ,'><i class="i-xf"></i>', lang(3423) ,'</a>';
        if ($SIDU['eng'] == 'mysql') echo NL, '  <a class="ajax" href="'. $sql .'FLUSH+ALL" title="', lang(3424) ,'"><i class="i-flus"></i>', lang(3425) ,'</a>';
        echo NL, '  <a class="a xwin" data-url="user.php'. $id .'"', html_hkey('U', lang(3430)) ,'><i class="i-user"></i>', lang(3431) ,'</a>';
    }
    echo NL, '  <a class="ajax" href="temp.php'. $id .'"', html_hkey('T', lang(3426)) ,'><i class="i-temp"></i>', lang(3427) ,'</a>';
    echo NL, '  <a class="a xwin" data-url="option.php'. $id .'"', html_hkey('O', lang(3428)) ,'><i class="i-opts"></i>', lang(3429) ,'</a>';
    echo NL, '  <a href="conn.php?cmd=quit" class="fr"', html_hkey('Q', lang(3404)) ,'><i class="i-exit"></i></a>';
    echo NL, '</div><!-- tool -->', NL;
    $sql = 'sql.php'. $id . 'sql=';
    if ($SIDU['eng'] == 'mysql' || $SIDU['eng'] == 'pgsql') {
        echo '<a class="ajax hide"'. html_hkey('P') .'href="sql.php?id='. $SIDU[0] .'&#38;sql=show process"></a>';
    }
}
function html_tool_obj($SIDU, $is_db = 0) {
    $typ = array('r'=>lang(111), 'v'=>lang(112), 'S'=>lang(113), 'f'=>lang(114));
    if (!$typ[$SIDU[3]]) return;
    $cmd = $SIDU['data']['objcmd'];
    echo NL .'<div id="objTool"'. ($cmd ? '' : ' class="hide"') .' data-url="'. ($is_db ? 'db' : 'tab');
    echo '.php?id='. "$SIDU[0],$SIDU[1],$SIDU[2],$SIDU[3],$SIDU[4],$SIDU[5],$SIDU[6]" . sidu_pg_oidStr($SIDU) .'">';
    if ($SIDU[3] == 'r') html_tool_tab($SIDU, $is_db, $typ);
    $objs = $is_db ? $SIDU['data']['objs'] : $SIDU[4];
    if ($objs && $cmd) obj_save($SIDU, $objs, $cmd);
    echo NL .'</div>';
}
function html_tool_tab($SIDU, $is_db, $typ) {
    $obj = $typ[$SIDU[3]] . ($is_db ? '' : ': '.$SIDU[4]);
    $arr = array(
        array('DROP', 'Drop', lang(117, $obj)),
        array('EMPTY','Empty',lang(118, $obj))
    );
    if ($SIDU['eng'] == 'mysql') {
        $arr[] = array('Analyze');
        $arr[] = array('Check');
        $arr[] = array('Optimize');
        $arr[] = array('Repair');
        $arr[] = array('Change Engine to MyISAM', '', lang(116, array('MyISAM', $obj)));
        $arr[] = array('Change Engine to InnoDB', '', lang(116, array('InnoDB', $obj)));
    } elseif ($SIDU['eng'] == 'pgsql') {
        $arr[] = array('Analyze');
        $arr[] = array('Vacuum');
        $arr[] = array('REINDEX', 'Re-index');
        $arr[] = array('Truncate Cascade', '', lang(118, $obj));
        $arr[] = array('Drop Cascade', '', lang(120, $obj));
    } elseif ($SIDU['eng'] == 'cubrid') {
        $arr[] = array('Vacuum');
    }
    foreach ($arr as $k => $v) {
        echo NL .'  <b'. (isset($v[2]) ? ' class="confirm" data-confirm="'.$v[2].'"' : '') .' data-cmd="'. strtoupper($v[0]) .'">'. (isset($v[1]) && $v[1] ? $v[1] : $v[0]) .'</b>';
    }
    echo NL .'  <i class="xwin green box hand" data-url="db-cmp.php?id=', "$SIDU[0]&fm[host]=$SIDU[0]&fm[db]=$SIDU[1]";
    if (!$is_db) echo '&fm[tab]='. $SIDU[4];
    echo '" style="padding:5px">DB Compare</i>';
}
function html_navi($SIDU) {
    echo NL .'<div class="tool icon">';
    echo NL .'  <i></i><b>SIDU '. $SIDU['sidu_ver'] .'</b> Database Web GUI <i class="i-web"></i><b>topnew.net/sidu</b>';
    html_navi_obj($SIDU);
    echo NL .'</div><!-- navi -->'. NL;
}
function html_navi_obj($SIDU, $is_db = 0) {
    if (!$SIDU[1]) return;
    $oidStr = sidu_pg_oidStr($SIDU);
    $id1 = "?id=$SIDU[0],$SIDU[1],";
    $id3 = $id1 ."$SIDU[2],$SIDU[3],";
    echo NL .'<i class="i-sep"></i>';
    if ($SIDU[4] != '' && !$is_db) {
        echo NL .'<a href="tab.php'. $id3 . $SIDU[4] . $oidStr .'&#38;desc=1" title="'. lang(122) .'"><i class="i-x'. $SIDU[3] .'"></i></a>';
    } else {
        echo NL .'<i class="i-x'. $SIDU[3] .'"></i>';
    }
    echo NL .'<a href="db.php'. $id1 .",,,$SIDU[5],$SIDU[6]" .'">'. $SIDU[1] . '</a>';
    if ($SIDU[2]) echo ' » <a href="db.php'. $id3 . ",$SIDU[5],$SIDU[6]$oidStr" .'">'. $SIDU[2] .'</a>';
    if ($SIDU[4] == '') return;
    if ($is_db) return print(' » '. $SIDU[4]);
    $tab = sidu_menu_tree_tab($SIDU[4], $SIDU['page']['tree']);
    if ($tab != '') echo ' » <a href="db.php'. $id3 . $tab . $oidStr .'">'. $tab .'</a>';
    echo ' » <a href="tab.php'. $id3 . $SIDU[4] . $oidStr .'" title="'. lang(123) .'">'. $SIDU[4] .'</a>';
}
function html_logo($div = 0) {
    $str1 = $str2 = '';
    if (!$div) {
        $str1 = '<div style="width:50px;height:50px;margin:10px auto">';
        $str2 = '</div>';
    }
    return $str1 .'<svg viewBox="-24.5 0 51 51" class="logo">
  <defs>
    <linearGradient id="logoR" x2="0" y2="1">
      <stop stop-color="#ff0"/>
      <stop offset=".6" stop-color="#f00"/>
    </linearGradient>
    <linearGradient id="logoG" y2="1">
      <stop stop-color="#0f0"/>
      <stop offset="1" stop-color="#474"/>
    </linearGradient>
    <linearGradient id="logoB" x2="0" y2="1">
      <stop stop-color="#38f"/>
      <stop offset="1" stop-color="#008"/>
    </linearGradient>
  </defs>
  <use xlink:href="#logoGB" transform="scale(-1,1)" fill="url(#logoB)"/>
  <circle cx="1" cy="32" r="15.5" fill="none" stroke="#222" stroke-width="7"/>
  <circle cy="31" r="15.5" fill="none" stroke="url(#logoR)" stroke-width="7"/>
  <g fill="url(#logoG)"><path id="logoGB" d="M0,19 A19,19 0 0,1 19,0 A19,19 0 0,1 0,19z"/></g>
  <g transform="translate(0,31)">
    <g id="lotus8">
      <g id="lotus4">
        <g id="lotus2">
          <path id="lotus1" d="M0,0 A5,5 0 0,1 0,-12 A5,5 0 0,1 0,0z" fill="#fff" opacity=".016"/>
          <use xlink:href="#lotus1" transform="rotate(45)"/>
        </g><use xlink:href="#lotus2" transform="rotate(90)"/>
      </g><use xlink:href="#lotus4" transform="rotate(180)"/>
    </g><use xlink:href="#lotus8" transform="rotate(22.5)"/>
  </g>
</svg>'. $str2;
}
function head($SIDU, $conn = null) {
    if (!$SIDU['ajax']) html_meta($SIDU);
    echo '      ';
    if (isset($SIDU['navi'])) {
        if ($SIDU['navi'] != -1 && function_exists($SIDU['navi'])) $SIDU['navi']($SIDU, $conn);
    } else {
        html_navi($SIDU);//default
    }
    echo '      <div id="colB">', "\n";
    echo NL .'<input type="hidden" id="sidu0" value="'. $SIDU[0] .'">';
}
function foot($SIDU) {
    echo NL .'      </div><!-- colB -->';
    if (!$SIDU['ajax']) echo NL .'</div></body>'. NL .'</html>';
}
