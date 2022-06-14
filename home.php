<?php
$data = array('close');
include 'inc.page.php';
main_close();
head($SIDU);
main($SIDU);
foot($SIDU);

function main_close() {
    global $SIDU;
    $id = ceil($SIDU['data']['close']);
    if (!$id) return;
    sidu_close($id);
}
function main($SIDU) {
    if (isset($_GET['phpinfo'])) {
        phpinfo();
        return;
    }
    echo NL .'<p><b>', lang(2103), ':</b></p>'. NL .'<p style="margin-left:40px">';
    foreach ($SIDU['conn'] as $conn) {
        echo NL .'  <a title="', lang(2104) ,'" '. ($SIDU[0] == $conn['id'] ? 'class="goto" href="conn.php?cmd=close&#38;id=' : 'href="home.php?id='.$SIDU[0].'&#38;close=') , $conn['id'] ,'"><i class="icon i-close"></i></a>';
        echo NL .'  <i class="icon i-eng'. $conn['eng'] .'"></i>';
        echo NL .' '. ($SIDU[0] == $conn['id'] ? '<b class="green">' : '<a href="./?id='. $conn['id'] .'" class="goto" title="'. lang(2105) .'">');
        echo ($conn['eng'] == 'sqlite') ? 'SQLite' : $conn['user'] .' @ '. $conn['host'];
        echo ($SIDU[0] == $conn['id']) ? '</b>' : '</a>';
        $port = $conn['port'];
        if ($conn['eng'] == 'mysql' && !$port) $port = '<i class="grey">(3306)</i>';
        elseif ($conn['eng'] == 'pgsql' && !$port) $port = '<i class="grey">(5432)</i>';
        elseif ($conn['eng'] == 'cubrid' && !$port) $port = '<i class="grey">(30000)</i>';
        elseif ($conn['eng'] != 'sqlite') $port = '('. $port .')';
        echo NL .'    '. $port;
        if ($conn['dbs'])  echo NL .'   {DB=<i class="green">', $conn['dbs'] ,'</i>}';
        if ($conn['char']) echo NL .'   {', lang(2106) ,'=<i class="blue">'. $conn['char'] .'</i>}';
        echo ' <b>PDO</b><br>';
    }
    $ip = SIDU_IP();
    echo NL .'<p>', ($ip ? '<b class="green">'.lang(2107).': '.$ip.'</b>' : '<b class="red">'.lang(2108).'</b>');
    echo NL .'<br>', lang(2109, array($_SERVER['REMOTE_ADDR'], 'inc.page.php')), '</p>';
    echo NL .'<pre>Server soft: ', $_SERVER['SERVER_SOFTWARE'];
    echo NL .'Server name: ', $_SERVER['SERVER_NAME'] ,' (', $_SERVER['SERVER_ADDR'] ,')';
    echo NL .'PHP version: ', phpversion();
    echo NL .'SIDU ver   : ', $SIDU['sidu_ver'];
    echo NL .'Database v : ', $SIDU['dbL']->getAttribute(PDO::ATTR_SERVER_VERSION);
    echo NL .'<a href="home.php?id='. $SIDU[0] .'&#38;phpinfo">More PHP Info ...</a>';
    echo NL . NL .'</pre>';
    echo NL .'<p class="show" data-src="#hotkey" title="', lang(2110) ,'"><b>', lang(2112) ,' (Fn):</b> FF|Chrome (Alt+Shift+', lang(2113) ,') IE (Alt+', lang(2114) ,') Opera (Shift+Esc) IOS Chrome (Ctrl+Alt+', lang(2113) ,')</p>';
    echo NL .'<pre id="hotkey" class="hide">» http://en.wikipedia.org/wiki/Access_key'. NL . NL . lang(2115) . NL;
    echo NL .'<b>The following is universal on any OS any browser: Shift or Ctrl or Alt</b>'. NL;
    echo NL .'+ Click on any links on Tool bar, or menu window will open links in new tab';
    echo NL .'+ Click on data window data row, will (de-)highlight multiple rows'. NL;
    echo NL .'</pre>';
    echo NL .'<p><b>Important changes since SIDU 5.5</b></p>';
    echo NL .'<p>If no SQL is highlighted in SQL window, Run / Multi will only run the SQL block of current cursor, stops at new lines before and after. Instead of all SQL in any previous versions.</p>';
    echo NL .'<p>', lang(2117) ,' <i class="green">http://topnew.net/sidu</i><br>', lang(2118) ,': <i class="green">topnew@hotmail.com</i> ? subject=<i class="green">sidu</i>';
    echo NL .'</div>';
}
