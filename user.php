<?php
$data = ['tab', 'userHost', 'user', 'host', 'pass', 'pass2', 'acs', 'acs2', 'grant', 'db'];
include 'inc.page.php';
head($SIDU);
main($SIDU);
foot($SIDU);

function main($SIDU) {
    if ($SIDU['eng'] != 'mysql') return main2();
    $data = $SIDU['data'];
    $tab  = ceil($data['tab']);
    $tabs = array(lang(4701), lang(4702), lang(4703));
    $url  = 'user.php?id='. $SIDU[0];
    if (!$data['userHost'] && strlen($data['user'])) $data['userHost'] = $data['user'] .'@'. $data['host'];
    echo NL .'<input type="hidden" id="curTab" value="'. $tab .'">';
    foreach ($tabs as $k => $v) {
        echo ($k == $tab) ? '<div class="box fl b red mr">'. $v .'</div>' : '<div class="box fl mr"><a class="none grey" href="'. $url .'&#38;tab='. $k . ($data['userHost'] ? '&userHost='.$data['userHost'] : '') .'">'. $v .'</a></div>';
    }
    $ver = explode('.', $SIDU['dbL']->getAttribute(PDO::ATTR_SERVER_VERSION));
    $ver = $ver[0] . '.' . (isset($ver[1]) ? $ver[1] : 0);
    $colPass = $ver >= 5.7 ? 'authentication_string' : 'Password';
    $attr = array('list'=>main_user_init($colPass), 'id'=>'userHost', 'style'=>'width:350px', 'defa'=>'Please select a user');
    echo '<p class="clear"></p><form action="'. $url .'" method="post">';
    if (!sidu_val('SELECT 1 FROM mysql.user LIMIT 1')) echo '<p class="err">'. lang(4704) .'</p>';
    elseif ($tab == 1) main_all($data, $attr);
    elseif ($tab == 2) main_db($SIDU[0], $data, $attr);
    else main_user($data, $attr, $colPass);
    echo '</form>';
}
function main_all($data, $attr) {
    $opt = main_all_init($data['userHost'], $priv);
    if ($data['cmd'] == lang(4713)) {
        main_all_save($data, $opt);
        $opt = main_all_init($data['userHost'], $priv);
    }
    echo NL .'<p><b>', lang(4705) ,'</b>', cms_form('select', 'userHost', $data['userHost'], $attr) ,'</p>';
    if (!$opt) return;
    echo NL .'<div style="width:200px" class="fl mr">';
    echo NL .'  <p><b>'. lang(4706) .'</b></p><p>'. $opt['data'] .'</p>';
    echo NL .'  <p><b>'. lang(4707) .'</b> ('. lang(4708) .')</p><p>';
    $arr = array('max_questions', 'max_updates', 'max_connections', 'max_user_connections');
    $w50 = array('style'=>'width:50px');
    foreach ($arr as $v) echo cms_form('text', 'acs2['. $v .']', (isset($priv[$v]) ? $priv[$v] : ''), $w50) ,' ', substr($v, 4), '<br>';
    echo '</p>'. NL .'</div>';
    echo NL .'<div class="fl mr">';
    echo NL .'  <p><b>'. lang(4710) .'</b></p><p>'. $opt['stru'] .'</p>'. NL .'</div>';
    echo NL .'<div class="fl"><p><b>'. lang(4709) .'</b></p><p>'. $opt['adm'] .'</p></div>';
    echo NL .'<p class="clear"><br><label><input type="checkbox" id="checkAll"> '. lang(4714) .'</label>';
    echo '&nbsp; <label><input type="checkbox" name="grant" value="Y"', ($priv['Grant_priv'] == 'Y' ? ' checked' : '') ,'> ', lang(4711) ,'</label>';
    echo cms_form('hidden', 'tab', 1);
    echo '&nbsp; ', cms_form('submit', 'cmd', lang(4713)) ,'</p>';
}
function main_all_save($data, $opt) {
    $user = str_replace('@', "'@'", $data['userHost']);
    sidu_run("REVOKE ALL PRIVILEGES ON *.* FROM '$user'");
    $strGrant = ' GRANT OPTION';
    if (!$data['grant']) {
        sidu_run("REVOKE GRANT OPTION ON *.* FROM '$user'");
        $strGrant = '';
    }
    $priv = 'GRANT ';
    if (!$data['acs']) $priv .= 'USAGE';
    elseif (count($data['acs']) == $opt['ttl'] - 1) $priv .= 'ALL';//ALL PRIVILEGES -- 8.0 works with all only
    else {
        $tran = array('Show_db'=>'SHOW DATABASES', 'Repl_'=>'REPLICATION ', '_tmp_table'=>' TEMPORARY TABLES', '_'=>' ');
        $priv .= strtoupper(strtr(implode(', ', $data['acs']), $tran));
    }
    foreach ($data['acs2'] as $k => $v) $data['acs2'][$k] = abs(ceil($v));
    $priv .= " ON *.* TO '$user' WITH$strGrant MAX_QUERIES_PER_HOUR ". $data['acs2']['max_questions']
        .' MAX_CONNECTIONS_PER_HOUR '. $data['acs2']['max_connections']
        .' MAX_UPDATES_PER_HOUR '. $data['acs2']['max_updates']
        .' MAX_USER_CONNECTIONS '. $data['acs2']['max_user_connections'];
    sidu_run($priv);
}
function main_all_init($userHost, &$priv) {
    if (!$userHost) return;
    $acs_data = array('Select', 'Insert', 'Delete', 'Update', 'File');
    $acs_adm  = array('Super', 'Reload', 'Shutdown', 'Process', 'References', 'Show_db', 'Lock_tables', 'Repl_slave', 'Repl_client', 'Create_user');
    $opt  = array('ttl'=>0, 'data'=>'', 'adm'=>'', 'stru'=>'');
    $user = explode('@', $userHost);
    $priv = sidu_row("SELECT * FROM mysql.user WHERE user='$user[0]' AND host='$user[1]'");
    if (!$priv) return;
    foreach ($priv as $k => $v) { if (substr($k, -5) == '_priv') {
        $k = substr($k, 0, -5);
        $str = '<label><input type="checkbox" name="acs[]" value="'. $k .'"'. ($v == 'Y' ? ' checked' : '') .'> '. $k .'</label><br>';
        if (in_array($k, $acs_data)) $opt['data'] .= $str;
        elseif (in_array($k, $acs_adm)) $opt['adm'] .= $str;
        elseif($k != 'Grant') $opt['stru'] .= $str;
        $opt['ttl']++;
    }}
    return $opt;
}
function main_db($id, $data, $attr) {
    echo NL .'<p><b>', lang(4716) ,'</b>', cms_form('select', 'userHost', $data['userHost'], $attr) ,'</p>';
    if (!$data['userHost']) return;
    $rows= sidu_rows('SHOW FIELDS FROM mysql.db');
    $col = array();
    foreach ($rows as $r) {
        if (substr($r['Field'], -5) == '_priv') $col[] = substr($r['Field'], 0, -5);
    }
    $user = explode('@', $data['userHost'], 2);
    $db_priv = sidu_row("SELECT * FROM mysql.db WHERE host='$user[1]' AND user='$user[0]'", '', 'Db');
    $userHost = "'". str_replace('@', "'@'", $data['userHost']) ."'";
    if ($data['cmd'] == lang(4715)) $db_priv = main_db_save($data, $db_priv, count($col) - 1, $userHost);
    echo NL .'<p><b>', lang(4717, '<i class="red">'. $data['userHost'] .'</i>') ,'</b></p>';
    echo NL .'<p><i class="icon i-info"></i>', lang(4718) ,'</p>';
    echo NL .'<div style="overflow:auto">';
    echo NL .'  <table class="box">'. NL .'    <tr><td>', lang(4719) ,'</td><td>', implode('</td><td>', $col), '</td></tr>';
    $dbs = sidu_enum('show databases');
    foreach ($dbs as $db) { if ($db != 'information_schema' && $db != 'performance_schema') {
        echo NL .'    <tr><td><a href="user.php?id='. $id .'&#38;tab=2&#38;db='. $db .'&#38;userHost='. $data['userHost'] .'"'. ($db == $data['db'] ? ' class="red"' : '') .'>'. $db .'</a></td>';
        foreach ($col as $v) echo '<td class="ac"><input type="checkbox" name="acs['. $db .'][]" value="'. $v .'"'. (isset($db_priv[$db][$v .'_priv']) && $db_priv[$db][$v .'_priv'] == 'Y' ? ' checked' : '') ,'></td>';
        echo '</tr>';
    }}
    echo NL .'  </table>'. NL .'</div>';
    echo NL .'<p class="ac">', cms_form('submit', 'cmd', lang(4715)) ,'</p>';
    echo cms_form('hidden', 'tab', 2);
    if ($data['db']) main_db_tab($data, $user, $userHost);
}
function main_db_save($data, $old_priv, $ttl, $userHost) {
    $tran = array('_tmp_table'=>' TEMPORARY TABLES', '_'=>' ');
    $RES = array();
    if ($data['acs']) { foreach ($data['acs'] as $db => $new) {
        if (isset($old_priv[$db])) {
            sidu_run("REVOKE ALL PRIVILEGES ON $db.* FROM $userHost");
            if (!in_array('Grant', $new) && $old_priv[$db]['Grant_priv'] == 'Y') sidu_run("REVOKE GRANT OPTION ON $db.* FROM $userHost");
            unset($old_priv[$db]);
        }
        $arr2 = array();
        foreach ($new as $p) {
            $RES[$db][$p .'_priv'] = 'Y';
            if ($p != 'Grant') $arr2[] = strtr($p, $tran);
        }
        $priv = (count($arr2) == $ttl) ? 'ALL PRIVILEGES' : ($arr2 ? strtoupper(implode(', ',$arr2)) : 'USAGE');
        sidu_run("GRANT $priv ON $db.* TO $userHost". (in_array('Grant', $new) ? ' WITH GRANT OPTION' : ''));
    }}
    foreach ($old_priv as $db => $old) {
        sidu_run("REVOKE ALL PRIVILEGES ON $db.* FROM $userHost");
        if ($old['Grant_priv'] == 'Y') sidu_run("REVOKE GRANT OPTION ON $db.* FROM $userHost");
    }
    return $RES;
}
function main_db_tab($data, $user, $userHost) {
    $col = array('Select', 'Insert', 'Update', 'Delete', 'Create', 'Drop', 'Grant', 'References', 'Index', 'Alter', 'Create View', 'Show view'); // please note Show view is not Show [V]iew
    $acs2= sidu_list("SELECT table_name,table_priv FROM mysql.tables_priv WHERE host='$user[1]' AND user='$user[0]' AND db='$data[db]'");
    foreach ($acs2 as $tab => $v) $acs2[$tab] = explode(',', $v);
    if ($data['cmd'] == lang(4720)) $acs2 = main_db_tab_save($data, $acs2, $userHost);
    echo NL .'<p><b>', lang(4721, '<i class="red">'. $data['db'] .'</i>') ,'</b></p>';
    echo NL .'<p><i class="icon i-info"></i>', lang(4722) ,'</p>';
    echo NL .'<div style="overflow:auto;max-height:200px">';
    echo NL .'  <table class="box">'. NL .'    <tr><td>', lang(4723) ,'</td><td>'. str_replace(' ', '', implode('</td><td>', $col)) ,'</td></tr>';
    $tabs = sidu_enum('SHOW TABLES FROM '. $data['db']);
    foreach ($tabs as $tab) {
        echo NL .'    <tr><td>'. $tab .'</td>';
        foreach ($col as $v) echo '<td class="ac"><input type="checkbox" name="acs2['. $tab .'][]" value="'. $v .'"', (isset($acs2[$tab]) && in_array($v, $acs2[$tab]) ? ' checked' : '') ,'></td>';
        echo '</tr>';
    }
    echo NL .'  </table>'. NL .'</div>';
    echo cms_form('hidden', 'db', $data['db']);
    echo NL .'<p class="ac">', cms_form('submit', 'cmd', lang(4720)) ,'</p>';
    echo NL .'<p><i class="icon i-info"></i>', lang(4724) ,'</p>';
    echo NL .'<p><i>GRANT SELECT, INSERT, UPDATE(id,name), REFERENCES(id) ON '. $data['db'] .'.table_name TO '. $userHost .'</i></p>';
    echo NL .'<p>', lang(4725, '<b>mysql.columns_priv</b>') ,'</p>';
}
function main_db_tab_save($data, $old_acs2, $userHost) {
    $db = $data['db'];
    foreach ($data['acs2'] as $tab => $new) {
        if (isset($old_acs2[$tab])) {
            sidu_run("REVOKE ALL PRIVILEGES ON $db.$tab FROM $userHost");
            if (!in_array('Grant', $new) && in_array('Grant', $old_acs2[$tab])) sidu_run("REVOKE GRANT OPTION ON $db.$tab FROM $userHost");
            unset($old_acs2[$tab]);
        }
        $arr2 = array();
        foreach ($new as $v) {
            if ($v != 'Grant') $arr2[] = strtoupper($v);
        }
        sidu_run('GRANT '. ($arr2 ? implode(', ',$arr2) : 'USAGE') ." ON $db.$tab TO ". $userHost . (in_array('Grant', $new) ? ' WITH GRANT OPTION' : ''));
    }
    foreach ($old_acs2 as $tab => $old) {
        sidu_run("REVOKE ALL PRIVILEGES ON $data[db].$tab FROM $userHost");
        if (in_array('Grant', $old)) sidu_run("REVOKE GRANT OPTION ON $data[db].$tab FROM $userHost");
    }
    return $data['acs2'];
}
function main_user($data, $attr, $colPass) {
    $err = '';
    if ($data['cmd']) {
        $err = main_user_save($data, $colPass);
    } else {
        $arr = explode('@', $data['userHost']);
        $data['user'] = isset($arr[0]) ? $arr[0] : '';
        $data['host'] = isset($arr[1]) ? $arr[1] : '';
    }
    echo NL .'<p><b>', lang(4729) ,'</b></p>';
    echo NL .'<p><i class="icon i-info"></i>', lang(4730) ,'</p>';
    if ($err) echo NL .'<p class="err">', $err ,'</p>';
    echo NL .'<table style="width:500px">';
    $attr['defa'] = lang(4732);
    echo NL .'<tr><td style="width:150px">', lang(4731) ,':</td><td style="width:350px">', cms_form('select', 'userHost', $data['userHost'], $attr) ,'</td></tr>';
    echo NL .'<tr><td>', lang(4733) ,':</td><td>', cms_form('text', 'user', $data['user']) ,'</td></tr>';
    echo NL .'<tr><td>', lang(4734) ,':</td><td>', cms_form('text', 'host', $data['host']) ,'</td></tr>';
    echo NL .'<tr><td>', lang(4735) ,':</td><td>', cms_form('password', 'pass', $data['pass']) ,'</td></tr>';
    echo NL .'<tr><td>', lang(4736) ,':</td><td>', cms_form('password', 'pass2',$data['pass2']),'</td></tr>';
    echo NL .'<tr><td></td><td>';
    echo cms_form('submit', 'cmd', lang(4738));
    echo cms_form('submit', 'cmd', lang(4737));
    echo '</td></tr></table>';
    echo NL .'<p><i class="icon i-info"></i>', lang(4739) ,'</p>
<pre>
<b>MySQL SQL</b>

REVOKE ALL PRIVILEGES ON *.* FROM user;
REVOKE GRANT OPTION ON *.* FROM user;

GRANT USAGE ON *.* TO user;
GRANT ALL (PRIVILEGES) ON *.* TO user WITH GRANT OPTION;

GRANT SELECT,INSERT,DELETE,UPDATE,FILE,
SUPER,RELOAD,SHUTDOWN,PROCESS,REFERENCES,SHOW DATABASES,LOCK TABLES,
REPLICATION SLAVE,REPLICATION CLIENT,CREATE USER
ON *.* TO user WITH GRANT OPTION
MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0
MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0;

REVOKE ALL PRIVILEGES ON db.* FROM user;
REVOKE GRANT OPTION ON db.* FROM user;

GRANT USAGE USAGE ON db.* TO user;
GRANT ALL PRIVILEGES ON db.* TO user WITH GRANT OPTION;

REVOKE ALL PRIVILEGES ON db.tab FROM user;
REVOKE GRANT OPTION ON db.tab FROM user;

GRANT USAGE USAGE ON db.tab TO user;
GRANT ALL PRIVILEGES ON db.tab TO user WITH GRANT OPTION;
</pre>';
}
function main_user_save($data, $colPass) {
    $data = cms_clean_str($data, 1, 1, 1);
    $userHost = '<b>'. $data['user'] .'@'. $data['host'] .'</b>';
    if ($data['cmd'] == lang(4737)) { // drop
        sidu_run("DROP USER '$data[user]'@'$data[host]'");
        $err = sidu_err(1);
        if ($err) return $err;
        echo '<p class="green">', lang(4726, $userHost) ,'</p>';
        return;
    } // else edit
    $tr = array(' ', "'");
    $data['name'] = str_replace($tr, '', $data['user']);
    $data['host'] = str_replace($tr, '', $data['host']);
    $data['pass'] = str_replace($tr, '', $data['pass']);
    if ($data['pass'] != $data['pass2']) {
        return lang(4727);
    }
    $where = "WHERE user='$data[user]' AND host='$data[host]' LIMIT 1";
    $sql = "'" . $data['name'] . "'@'" . $data['host'] . "' IDENTIFIED WITH mysql_native_password BY '" . $data['pass'] . "'";
    if (sidu_val('SELECT 1 FROM mysql.user '. $where)) {
        sidu_run('ALTER USER ' . $sql);
    } else {
        sidu_run('CREATE USER ' . $sql);
    }
    $err = sidu_err(1);
    if ($err) return $err;
    sidu_run('FLUSH PRIVILEGES');
    echo '<p class="green">', lang(4728, $userHost) ,'</p>';
}
function main_user_init($colPass) {
    $rows= sidu_rows('SELECT Host,User,'. $colPass .' pass FROM mysql.user ORDER BY 2,1');
    $arr = array();
    foreach ($rows as $r) {
        $uh = $r['User'] .'@'. $r['Host'];
        $arr[$uh] = $uh . ($r['pass'] ? '' : ' -- No Pwd');
    }
    return $arr;
}
function main2() { //non mysql--user manager not ready yet
    echo '<p><b>Database User</b> -- not available yet</p>
<pre>
<b>Postgres</b>

CREATE ROLE ben LOGIN ENCRYPTED PASSWORD \'md5021fae7a1b5955\'
    SUPERUSER NOINHERIT CREATEDB CREATEROLE
    VALID UNTIL \'infinity\';
COMMENT ON ROLE benb IS \'comm\';

CREATE USER name SUPERUSER CREATEDB CREATEROLE CREATEUSER INHERIT LOGIN
ALTER Role postgres ENCRYPTED PASSWORD \'md5965fb1f623b2c\';

DB:Find Variables process Lock Admin Privileges Export Create Drop Alter
Sch:Find Priv Create Drop Alter
Tab:analyze vaccum empty drop create alter

</pre>

<table class="grid">
<tr class="th"><td>user</td><td>super</td><td>+db</td><td>+role</td><td>inherit</td><td>conn limit</td><td>expire</td></tr>
<tr><td>ben</td><td>Y</td><td>Y</td><td>Y</td><td>Y</td><td>no limit</td><td>never</td></tr>
</table>';
}
