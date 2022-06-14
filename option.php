<?php
$data = array('lang', 'pgSize', 'tree', 'sortObj', 'sortData', 'menuTextTool', 'menuTextData', 'his', 'hisErr', 'hisSQL', 'hisData', 'dataEasy');
include 'inc.page.php';
sidu_cook_copy($SIDU);
save_data($SIDU);
head($SIDU, $conn);
main($SIDU);
foot($SIDU);

function save_data(&$SIDU) {
    $data = $SIDU['data'];
    if (!$data['cmd']) return;
    $data['pgSize'] = ceil($data['pgSize']);
    $page = $SIDU['page'];
    foreach ($page as $k => $v) {
        if (isset($data[$k])) $page[$k] = $data[$k];
    }
    sidu_cook_set('PAGE', $page);
    $SIDU['page'] = $page;
}
function main($SIDU) {
    $page = $SIDU['page'];
    // $arr_lang = array('cn'=>'中文','de'=>'Deutsch','en'=>'English','es'=>'Espanol','fr'=>'Francais','it'=>'Italiano');
    $arr_lang = array('list'=>array('cn'=>'中文', 'en'=>'English'), 'defa'=>-1, 'style'=>'width:150px');
    $arr_yes = array('list'=>array(lang(2710),lang(2711)));
    $arr_sort = array('list'=>array(1=>lang(2706), lang(2707)));
    $w150 = array('style'=>'width:150px');
    echo NL .'<h3>', lang(2700), '</h3>';
    if ($SIDU['data']['cmd']) echo '<p class="green">Saved</p>';
    echo cms_form('form', 'myform', 'option.php?id='. $SIDU[0]);
    echo NL .'<table>
<tr class="bg1"><td>', lang(2701) ,':</td><td>', cms_form('select','lang',        $page['lang'], $arr_lang) ,'</td></tr>
<tr class="bg1"><td>', lang(2702) ,':</td><td>', cms_form('text',  'pgSize',      $page['pgSize'],$w150) ,' ', lang(2703) ,'</td></tr>
<tr class="bg1"><td>', lang(2704) ,':</td><td>', cms_form('text',  'tree',        $page['tree'],  $w150) ,' eg. _ 0...9</td></tr>
<tr class="bg1"><td>', lang(2705) ,':</td><td>', cms_form('radio', 'sortObj',     $page['sortObj'], $arr_sort) ,'</td></tr>
<tr class="bg1"><td>', lang(2708) ,':</td><td>', cms_form('radio', 'sortData',    $page['sortData'],$arr_sort) ,'</td></tr>
<tr><td class="grey">',lang(2709) ,':</td><td>', cms_form('radio', 'menuTextTool',$page['menuTextTool'],$arr_yes) ,'</td></tr>
<tr><td class="grey">',lang(2712) ,':</td><td>', cms_form('radio', 'menuTextData',$page['menuTextData'],$arr_yes) ,'</td></tr>
<tr class="bg1"><td>', lang(2713) ,':</td><td>', cms_form('radio', 'his',         $page['his'],         $arr_yes) ,'</td></tr>
<tr class="bg1"><td>', lang(2714) ,':</td><td>', cms_form('radio', 'hisErr',      $page['hisErr'],      $arr_yes) ,'</td></tr>
<tr class="bg1"><td>', lang(2715) ,':</td><td>', cms_form('radio', 'hisSQL',      $page['hisSQL'],      $arr_yes) ,'</td></tr>
<tr class="bg1"><td>', lang(2716) ,':</td><td>', cms_form('radio', 'hisData',     $page['hisData'],     $arr_yes) ,'</td></tr>
<tr><td><br>Postgres: ',lang(2717),':</td><td><br>',cms_form('radio','dataEasy',  $page['dataEasy'],    $arr_yes) ,'</td></tr>
<tr><td>Postgres: ',   lang(2718) ,':</td><td>', cms_form('radio', 'oid',         $page['oid'],         $arr_yes) ,'</td></tr>
<tr><td></td><td>', cms_form('submit', 'cmd', lang(2719), $w150), '</td></tr>
</table></form>';
}
