<?php
$data = ['eng', 'host', 'user', 'pass', 'dbs', 'penc', 'port', 'char', 'url', 'lang'];
include 'inc.page.php';
$SIDU['navi'] = -1;
auto_conn($SIDU, $data);
main($SIDU, $data);

function auto_conn($SIDU, $data) {
    if (!$SIDU[0] || !isset($SIDU['conn'][$SIDU[0]]) || !$data['id'] || $data['cmd'] == 'close') {
        return;
    }
    header('location:./'. $data['url'] .'?id='. $data['id']);
    exit;
}

function main_salt($cmd = 'set') { //set,get,del
    if ($cmd == 'get') {
        return explode('|', $_SESSION['sidu_conn_salt']);
    } elseif ($cmd == 'del') {
        unset($_SESSION['sidu_conn_salt']);
    } elseif ($cmd != 'set') {
        return;
    }
    $fm = parse_url($_SERVER['HTTP_REFERER']);
    $arr= parse_url('http://' . $_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME']);
    if ($fm['host'] != $arr['host'] || $fm['path'] != $arr['path']) {
        return;//prevent heck
    }
    $_SESSION['sidu_conn_salt'] = time() . '|' . hash(
        'sha256', rand() . time() . $_SERVER['SERVER_SIGNATURE'] . $_SERVER['HTTP_USER_AGENT']
        . session_id() . $_SERVER['REMOTE_ADDR']
    );
}

function main($SIDU, $data) {
    if ($data['cmd'] == 'salt') {
        main_salt();
        $arr = main_salt('get');
        return print($arr[1]);
    }
    $err = '';
    if ($data['cmd'] == 'quit') {
        sidu_cook_set('CONN', '', -1);
        sidu_cook_set('COOK', '', -1);
        $data['penc'] = 1;
    } elseif ($data['cmd'] == 'close') {
        $goto = sidu_close($data['id']);
        if ($goto) {
            return header('location:./?id=' . $goto);
        }
    } elseif ($data['cmd'] == 'connect') {
        $err = test_conn($data);
        if (!$err) {
            return main_conn($data);
        }
    }
    head($SIDU);
    main_form($SIDU, $data, $err);
    foot($SIDU);
}

function main_form($SIDU, $data, $err) {
    $data['host'] = $data['host'] ?: 'localhost';
    $arr_eng = ['mysql'=>'PDO MySQL', 'pgsql'=>'PDO PostgreSQL', 'sqlite'=>'PDO SQLite'];
    if (!isset($arr_eng[$data['eng']])) {
        $data['eng'] = 'mysql';
    }
    if (!$data['user']) {
        $data['user'] = $data['eng'] == 'mysql' ? 'root' : ($data['eng'] == 'pgsql' ? 'postgres' : 'dba');
    } elseif (!$data['cmd']) {
        if (($data['user'] == 'root' || $data['user'] == 'dba') && $data['eng'] == 'pgsql') {
            $data['user'] = 'postgres';
        } elseif (($data['user'] == 'postgres' || $data['user'] == 'dba') && $data['eng'] == 'mysql') {
            $data['user'] = 'root';
        } elseif (($data['user'] == 'postgres' || $data['user'] =='root') && $data['eng'] == 'cubrid') {
            $data['user'] = 'dba';
        }
    }
    if (!$data['cmd']) {
        if ($data['eng'] == 'cubrid' && !$data['dbs']) {
            $data['dbs'] = 'demodb';
        } elseif ($data['eng'] != 'cubrid' && $data['dbs'] == 'demodb') {
            $data['dbs'] = '';
        }
        $data['penc'] = 1;
    }
    echo cms_form('form', '', '', ['class'=>'box', 'style'=>'max-width:300px;margin:15px auto', 'id'=>'conn']);
    echo html_logo();
    echo $err ? '<p class="err">' . $err . '</p>' : '';
    echo '<p class="ac"><b>SIDU '. $SIDU['sidu_ver'] . '</b> Database Web GUI</p><p class="ac">';
    $lang = ['en' => 'English', 'cn' => '中文'];
    $url = ' - <a href="?' . ($SIDU[0] ? 'id=' . $SIDU[0] . '&' : '') . 'lang=';
    foreach ($lang as $k => $v) {
        echo $url . $k . '"' . ($k == $SIDU['page']['lang'] ? ' class="on"' : '') . '">' . $v . '</a>';
    }
    echo '</p>' . cms_form('select', 'eng',  $data['eng'],  ['list'=>$arr_eng, 'defa'=>-1]);
    if ($data['eng'] != 'sqlite') {
        echo cms_form('text', 'host', $data['host'], ['placeholder'=>lang(1109) .' *']);
        echo cms_form('text', 'user', $data['user'], ['placeholder'=>lang(1110) .' *']);
        echo cms_form('pass', 'pass', $data['pass'], ['placeholder'=>lang(1111), 'id'=>'pwd']);
        echo cms_form('text', 'port', $data['port'], ['placeholder'=>lang(1112), 'style'=>'width:60px']);
        echo cms_form('cbox', 'penc', $data['penc'], ['list'=>lang(1113), 'id'=>'enc']);
    }
    echo cms_form('text', 'dbs',  $data['dbs'],  ['placeholder'=>'database1;db2;db3' . ($data['eng'] == 'mysql' || $data['eng'] == 'pgsql' ? '' : ' *')]);
    if ($data['eng'] == 'mysql') {
        echo cms_form('select', 'char', $data['char'], ['list'=>get_conn_char(), 'defa'=>-1]);
    }
    echo cms_form('submit','x', lang(1104), ['id'=>'enc_pwd']);
    echo cms_form('hidden','cmd', 'connect', ['id'=>'cmd']);
    echo cms_form('hidden','url', $data['url']);
    echo cms_form('hidden','id',  $data['id']);
    echo cms_form('hidden','lang',$SIDU['page']['lang']);
    echo '<p class="ac"><br><b class="xwin hand green" data-url="http://topnew.net/sidu">topnew.net/sidu</b></p>';
    echo cms_form('end');
    echo NL . '<br><iframe src="http://topnew.net/sidu/news?cms=SIDU' . $SIDU['sidu_ver'] . '" style="border:0;width:100%;height:400px"></iframe>';
    echo NL . '<script>' . NL . '$("select").change(function(){';
    echo NL . '  $("#cmd").val(""); $(this).parent("form").submit();';
    echo NL . '});' . NL . '</script>';
}

function test_conn(&$data) {
    $data = cms_clean_str($data, 1, 0, 1);
    $data['port'] = ceil($data['port']);
    $data['port'] = $data['port'] < 1 ? '' : $data['port'];
    if ($data['penc']) {
        $salt = main_salt('get');
        //some server does not send salt at all, turn off salt exp check since 5.2
        //$now=time();
        //if ($salt[0]<$now-480 || $salt[0]>$now) return lang(1106);//salt exp in 8 minutes
        $data['pass'] = cms_dec($data['pass'], $salt[1]);
    }
    //if you keep play with % i do not care
    $data['dbs'] = strtr($data['dbs'], [' '=>'', ','=>';', '%%'=>'%', '%%%'=>'%', '%%%%'=>'%']);
    $dbs = explode(';', $data['dbs']);
    $db = [];
    foreach ($dbs as $v) {
        $v = trim($v);
        if ($v && $v != '%' && $v != '%%' && $v != '%%%' && $v != '%%%%') {
            $db[] = $v;
        }
    }
    $data['dbs'] = implode(';', $db);
    $err = '';
    $eng = $data['eng'];
    $host= $data['host'];
    $port= $data['port'];
    $user= $data['user'];
    $pass= $data['pass'];
    $pdo = $eng . ':host=' . $host;
    $pdo .= (isset($db[0]) && $db[0]) ? ';dbname=' . $db[0] : '';
    if (($eng == 'mysql' && $port == 3306) || ($eng == 'pgsql' && $port == 5432)) {
        $port = 0;
    } elseif ($eng == 'cubrid' && !$port) {
        $port = 30000;
    }
    $pdo .= $port ? ';port=' . $port : '';
    if ($eng=='sqlite') {
        $pdo = $eng .':'. $db[0];
    }
    try {
        $dbh = new PDO($pdo, $user, $pass);
        $err = 0;
    } catch (PDOException $e) {
        $err = 'Connection failed: '. $e->getMessage();
    }
    return $err;
}

function main_conn($data) {
    global $SIDU;//have to use global
    $arr = ['eng', 'host', 'user', 'dbs', 'port', 'char'];
    $c = [
        'pass' => cms_enc($data['pass'], 1),
        'penc' => 1,
        'cid'  => time() - strtotime('2016-05-05'),
        //more then 1 user login same time same cid? not good enough fix later
    ];
    foreach ($arr as $k) {
        $c[$k] = $data[$k];
    }
    $conn = sidu_cook_get('CONN');
    $SIDU[0] = $c['id'] = $id = $conn ? max(array_keys($conn)) + 1 : 1;
    $SIDU['conn'][$id] = $conn[$id] = $c;
    sidu_cook_set('CONN', $conn);
    $page = sidu_cook_get('PAGE');
    if (!isset($page['lang']) || $page['lang'] != $SIDU['page']['lang']) {
        sidu_cook_set('PAGE', $SIDU['page'], time() + 311040000);
    }
    $arr = explode(',', $data['id'], 2);
    $arr[0] = $id;
    sidu_log('B', "$c[eng]CONN$c[cid]: $c[user]@$c[host] - ".$_SERVER['REMOTE_ADDR'],0);
    header('Location:./' . $data['url'] . '?id=' . implode(',', $arr));
    exit;
}

function get_conn_char() {
    $arr = explode('|', 'utf8mb4:UTF-8 Unicode|armscii8:ARMSCII-8 Armenian|ascii:US ASCII|big5:Big5 繁体中文|binary:Binary pseudo charset|cp1250:Windows Central European|cp1251:Windows Cyrillic|cp1256:Windows Arabic|cp1257:Windows Baltic|cp850:DOS West European|cp852:DOS Central European|cp866:DOS Russian|cp932:SJIS for Windows Japanese|dec8:DEC West European|eucjpms:UJIS for Windows Japanese|euckr:EUC-KR Korean|gb2312:GB2312 简体中文|gbk:GBK 简体中文|geostd8:GEOSTD8 Georgian|greek:ISO 8859-7 Greek|hebrew:ISO 8859-8 Hebrew|hp8:HP West European|keybcs2:DOS Kamenicky Czech-Slovak|koi8r:KOI8-R Relcom Russian|koi8u:KOI8-U Ukrainian|latin1:cp1252 West European|latin2:ISO 8859-2 Central European|latin5:ISO 8859-9 Turkish|latin7:ISO 8859-13 Baltic|macce:Mac Central European|macroman:Mac West European|sjis:Shift-JIS Japanese|swe7:7bit Swedish|tis620:TIS620 Thai|ucs2:UCS-2 Unicode|ujis:EUC-JP Japanese');
    foreach ($arr as $v) {
        $arr2 = explode(':', $v, 2);
        $res[$arr2[0]] = $arr2[0] . ': ' . $arr2[1];
    }
    return $res;
}
