<?php

if ($_SERVER['QUERY_STRING'] == '') {
    echo '<script>top.location="conn.php"</script>';
}

include 'inc.page.php';
html_meta($SIDU);
html_tool($SIDU);

?>

<div id="page" class="flex">
  <div id="menu" class="icon H load" data-url="menu.php?id=<?php echo $SIDU[0];?>"></div><!-- menu -->
  <div id="colR" class="flex">
    <div id="menuDrag"></div>
    <div id="sqls"><textarea id="sqlsT">select now()</textarea></div>
    <div id="sqlsDrag"></div>
    <div id="main" class="flex">
<?php html_navi($SIDU); ?>
<div id="colB">
  <div class="solar-syst">
    <div style="width:100px;border:0;margin-left:-50px;margin-top:50px"><?php echo html_logo(1);?></div>
    <div class="mercury"></div>
    <div class="venus"></div>
    <div class="earth"></div>
    <div class="earth" style="height:190px;width:100px;margin-top:130px;margin-left:-50px"></div>
    <div class="mars"></div>
    <div class="mars" style="height:500px;width:300px;margin-top:-150px;margin-left:-150px"></div>
    <div class="neptune"></div>
  </div>
<style>
.solar-syst{margin:0 auto;width:100%;height:450px;position:relative}
.solar-syst div{border-radius:1000px;top:50%;left:50%;position:absolute;z-index:999;xborder:1px dashed rgba(102, 166, 229, 0.12)}
.solar-syst div:before{left:50%;border-radius:100px;content:"";position:absolute;box-shadow:inset 0 6px 0 -2px rgba(0, 0, 0, 0.25)}
.mercury{height:70px;width:70px;margin-top:-35px;margin-left:-35px;animation:orb 7s linear infinite}
.mercury:before{height:4px;width:4px;background:#9f5e26;margin-top:-2px;margin-left:-2px}
.venus{height:100px;width:100px;margin-top:-50px;margin-left:-50px;animation:orb 19s linear infinite}
.venus:before{height:8px;width:8px;background:#BEB768;margin-top:-4px;margin-left:-4px}
.earth{height:145px;width:145px;margin-top:-72.5px;margin-left:-72.5px;animation:orb 30s linear infinite}
.earth:before{height:6px;width:6px;background:#11abe9;margin-top:-3px;margin-left:-3px}
.earth:after{position:absolute;content:"";height:18px;width:18px;left:50%;top:0px;margin-left:-9px;margin-top:-9px;border-radius:100px;box-shadow:0 -10px 0 -8px grey;animation:orb 3s linear infinite}
.mars{height:190px;width:190px;margin-top:-95px;margin-left:-95px;animation: orb 56s linear infinite}
.mars:before{height:6px;width:6px;background:#cf3921;margin-top:-3px;margin-left:-3px}
.neptune{height:230px;width:230px;margin-top:-115px;margin-left:-115px;animation:orb 48s linear infinite}
.neptune:before{height:10px;width:10px;background:#175e9e;margin-top:-5px;margin-left:-5px;}
@-webkit-keyframes orb{
  from{-webkit-transform:rotate(0deg);   transform:rotate(0deg);}
  to  {-webkit-transform:rotate(-360deg);transform:rotate(-360deg);}
}
@keyframes orb{
  from{-webkit-transform:rotate(0deg);   transform:rotate(0deg);}
  to  {-webkit-transform:rotate(-360deg);transform:rotate(-360deg);}
}
</style>
</div><!-- colB -->
    </div><!-- main -->
  </div><!-- colR -->
</div><!-- page -->
</div></body>
</html>
