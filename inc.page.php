<?php
const NL = "\n";
session_start();

$data[] = 'ajax';
$data[] = 'id';
$data[] = 'cmd';
$data[] = 'oid';//common paras used by each page
$arr = [];
foreach ($data as $k) {
    $arr[$k] = isset($_POST[$k]) ? $_POST[$k] : (isset($_GET[$k]) ? $_GET[$k] : null);
}
$data = $arr;

include 'inc.func.php';
include 'eng.mysql.php';
include 'eng.pgsql.php';
include 'eng.sqlite.php';
include 'eng.cubrid.php';
include 'cms_enc.php';

cms_firewall(SIDU_IP(), 'Access from un-authorized IP (SIDU 防火墙阻止了 IP): '. cms_ip() .'<br><br>Please check SIDU firewall setting at <u>last line</u> of file <b>inc.page.php</b><br>OR visit <b>topnew.net/sidu</b> for solution');

// db =0id 1db 2sch 3typ 4tab 5sort1 6sort2 7sort &oid--sad oid not designed into the id-link
// tab=0id 1db 2sch 3typ 4tab 5sort1 6sort2 7sort 8fm 9to ; fm|to ; f[0..x|sql] ; g[size|show][0..x] &oid
// sql=id,sql,hide,ttl_sql,ttl_err,ttl_time,Rows
// cookie:--better put into session. but sf.net does not support session?
// conn[txt]--please read conn.php
// MODE=0lang.1gridMode.2pgSize.3tree.4sortObj.5sortData.6menuTextSQL.7menuText.8his.9hisErr.10hisSQL.11hisData.12dataEasy(pg).13oid(pg)
// CONN=0id.1eng.2host.3user.4enc(pass).5port.6dbs.7penc.8charset
// SQL =0id.1db.2sch.3typ.4tab@id.db.sch.typ.tab@...

sidu_init($SIDU, $data);
$SIDU['data'] = $data;
$conn = isset($SIDU['conn'][$SIDU[0]]) ? $SIDU['conn'][$SIDU[0]] : []; // shortcut as most page use it

include 'inc.lang.' . $SIDU['page']['lang'] . '.php';

$SIDU['sidu_ver'] = '6.3'; // released 2021-09-19

function SIDU_PK() {return '2021-SIDU-0909';} // SIDU Product Key - please update
function SIDU_IP() {return '';} // SIDU Global Firewall eg '/^127.0.0.1$/;/^192.168/'
