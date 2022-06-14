<?php
$data = array('lid');
include 'inc.page.php';
$SIDU['navi'] = 'navi';
sidu_cook_copy($SIDU);
head($SIDU, $conn);
main($SIDU);
foot($SIDU);

function navi($SIDU) {
    echo '<div class="tool icon">';
    echo NL . '<i class="a delHis i-arrHead" title="'. lang(1701) .'" data-cmd="delRest"></i>';
    echo NL . '<i class="a delHis i-arrFoot" title="'. lang(1702) .'" data-cmd="delBack"></i>';
    echo NL . '<i class="a delHis i-drop" title="'. lang(1703) .'" data-cmd="del"></i>';
    echo NL . '<i class="a delHis i-flus" title="'. lang(1704) .'" data-cmd="delAll"></i>';
    echo NL . '<i class="a delHis i-save" title="Save SQL to log table" data-cmd="save"></i>';
    html_navi_obj($SIDU);
    echo NL . '<i class="i-sep"></i>';
    echo NL . date('Y-m-d H:i:s');
    echo NL . '</div><!-- navi -->';
}
function main($SIDU) {
    $cid = $SIDU['conn'][$SIDU[0]]['cid'];
    $log = &$_SESSION['siduhis'][$cid];
    $cmd = $SIDU['data']['cmd'];
    $lid = explode(',', $SIDU['data']['lid']);
    if ($cmd == 'delAll') {
        unset($_SESSION['siduhis'][$cid]);
        $log = '';
    } elseif ($cmd == 'del') {
        foreach ($lid as $x) unset($log[$x]);
    } elseif (($cmd == 'delBack' || $cmd == 'delRest') && $lid[0] != '') {
        foreach ($log as $i => $v) {
            if (($cmd == 'delBack' && $i < $lid[0]) || ($cmd == 'delRest' && $i > $lid[0])) unset($log[$i]);
        }
    } elseif ($cmd == 'save') {
        if ($SIDU[4] != 'sidu_log') {
            echo NL .'<p>History Log can only be saved to table = sidu_log. Please goto <a href="tab.php?id='. "$SIDU[0],$SIDU[1],$SIDU[2]" .',r,sidu_log">sidu_log table and click History to save</a></p>';
            echo NL .'<p>If you have not created sidu_log table yet, here is the SQL:</p>';
            $func = 'sidu_log_'. $SIDU['eng'];
            echo NL .'<pre>'. $func() . NL . NL .'</pre>';
        } else {
            sidu_use_db($SIDU[1], $SIDU[2]);
            foreach ($lid as $x) { if ($x) {
                $arr = explode(' ', $log[$x], 5);
                $sql = 'INSERT INTO '. sidu_keyw($SIDU[4]) ."(ts,ms,typ,txt) VALUES('$arr[0] $arr[1]',$arr[3],'$arr[2]','". str_replace("'", "''", $arr[4]) ."')";
                $res = $SIDU['dbL']->query($sql);
                $err = sidu_err(1);
                if ($err) echo '<pre>'. cms_html8($sql) .'</pre><p class="red">'. $err .'</p><p>Please check <b class="hand xwin red" data-url="option.php?id='. $SIDU[0] .'">Option page</b> if sidu_log table is correct</p>';
                else unset($log[$x]);
            }}
            echo '<p class="green">Saved to sidu_log and deleted from below.</p>';
        }
    }
    if (!$log) return;
    $his = $log;
    krsort($his);
    $typ = ['B'=>lang(1705), 'S'=>'SQL', 'E'=>lang(1706), 'D'=>lang(1707)];
    $css = ['B'=>'grey', 'S'=>'', 'E'=>'red', 'D'=>'green'];
    echo NL .'<form><table class="grid" id="hisAll">';
    echo NL .'<tr class="th"><td class="cbox"><input type="checkbox" id="checkAll"></td><td>Time</td><td>ms</td><td>Typ</td><td>SQL (click SQL to run, click Typ to copy)</td></tr>';
    foreach ($his as $i => $l) {
        $arr = explode(' ', $l, 5);
        echo NL .'<tr class="'. $css[$arr[2]] .'">'. NL .'  <td><input type="checkbox" name="objs[]" value="'. $i .'"></td>';
        echo NL .'  <td>'. $arr[0] .' '. $arr[1] .'</td>';
        echo NL .'  <td class="ar">'. $arr[3] .'</td>';
        echo NL .'  <td>'. $typ[$arr[2]] .'</td>';
        echo NL .'  <td><a href="sql.php?id='. $SIDU[0] .'&sql=SIDUhis:'. $i .'"><span class="small '. $css[$arr[2]] .'">'. nl2br(cms_html8($arr[4]), 0) .'</span></a></td>';
        echo NL .'</tr>';
    }
    echo NL .'</table></form>';
    echo '<script src="jquery-1.11.3.min.js"></script>';
    echo '<script>$(function(){$("#hisAll tr td:nth-child(4)").on("click", function(){
        var $temp = $("<input>");
        $("body").append($temp);
        $temp.val($(this).next().text()).select();
        document.execCommand("copy");
        $temp.remove();
    })})</script>';
}
