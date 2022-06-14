<?php
/**
 * cms_enc v5.5.1 - updated 2015-08-08 - run by itself no dependent on cms
 * --------------------------------------------------------------------------
 * 1. cms_enc(str, key) + cms_dec(str, key)
 * key=0: dynamic base64 +server+client+session+ip, one-time use only
 * key=1: dynamic base64
 * key=x: dynamic base88
 * --------------------------------------------------------------------------
 * 2. cms_kg(level, ...) + cms_kd(level, ...) : key generator and key decoder
 * level = 0-2 is same as cms_hash
 * --------------------------------------------------------------------------
 * 3. cms_hash() -- no decode
 */
function cms_enc($str = '', $key = '') {
    $init = cms_enc_init($key);
    return cms_kg(9, $init['code'], $init['name'], $init['hw'], $init['n1'], $init['n2'], $init['n3'], $init['n4'], $init['n5'], $str, $init['base']);
}
function cms_dec($str = '', $key = '') {
    $init = cms_enc_init($key);
    $res = cms_kd(9, cms_kg_code($init['code']), $init['name'], $init['hw'], $str, $init['base']);
    return $res['err'] ? '' : $res['txt'];
}
function cms_enc_init($key = '') {
    $init['base'] = (!$key || '1' == $key) ? 64 : 88;
    $init['n1'] = rand(0, 65535);
    $init['n2'] = rand(0, 65535);
    $init['n3'] = rand(0, 255);
    $init['n4'] = rand(0, 255);
    $init['n5'] = rand(0, 255);
    $code = md5('南無阿彌陀佛 - Namo Amitabha - 南无阿弥陀佛' . $key);
    if (!$key) {
        $code .= str_repeat(session_id(), substr(ord($code[0]), 0, 1) + 1);
        $code .= $_SERVER['HTTP_USER_AGENT'];
        $code .= md5($code);
        $code .= str_repeat($_SERVER['REMOTE_ADDR'], substr(ord($code[0]), -1) + 1);
        $code .= $_SERVER['SERVER_SIGNATURE'];
        $code .= md5($code);
    }
    $init['code'] = md5($code);
    $init['name'] = md5($init['code']);
    $init['hw'] = 'ABCD-1234';
    return $init;
}
/**
 * cms_kg() is a key generator to generate dynamic hash which can be decoded
 * level 0-9 the higher the better
 * L0: .6 xxxxxx .. - no decode == cms_hash()
 * L1: .8 4-4 ..... - no decode == cms_hash()
 * L2: 12 4-4-4 ... - no decode == cms_hash()
 * L3: 18 6-6-6 ... +n1(max256)
 * L4: 24 6-6-6-6 . +n1+n2(both max 65536)
 * 5-7:30 6-6-6-6-6 +n3+n4+n5(all max 255)
 * L8: 48Max 6-6-6-6-6-6(-6-6) +str(max 9char)
 * L9: unlimited string for encoding
 * hw max FFFF-FFFF
 * base32: 2-9A-Z .... good for licence (ex:01IO)
 * base64: 0-9A-Za-z_- good for cookie
 * base88: any char from keyboard except: < > space tab /'"\
 */
function cms_kg($level = 9, $code = '', $name = '', $hw = '', $n1 = 0, $n2 = 0, $n3 = 0,$n4 = 0, $n5 = 0, $str = '', $base = 32) {
    if ($base > 88) $base = 88;
    elseif ($base < 10) $base = 10;
    if ($level < 3) return cms_hash($level);
    $res = cms_kg_now() . cms_kg_hw($hw);
    if (3 == $level) return cms_kg_txt($res . cms_kg_b256($n1 % 256), $level, $code, $name, $base);
    //rand 2 date 2 HW 4 n1 2 n2 2 n3 1 n4 1 n5 1 str 9 = 24char max internal = 48char max output |L9 no limit
    $res .= str_pad(cms_kg_b256($n1 % 65536), 6, 0, STR_PAD_LEFT) . str_pad(cms_kg_b256($n2 % 65536), 6, 0, STR_PAD_LEFT);
    if ($level > 4 || $n3) $res .= cms_kg_b256($n3 % 256);
    if ($level > 4 || $n4) $res .= cms_kg_b256($n4 % 256);
    if ($level > 4 || $n5) $res .= cms_kg_b256($n5 % 256);
    if ($level > 7 || $str) $res .= cms_kg_str($str, $level);
    return cms_kg_txt($res, $level, $code, $name, $base);
}
//note cms_kd.code=cms_kg_code(cms_kg.code)
function cms_kd($level = 9, $code = '', $name = '', $hw = '', $key = '', $base = 32) {
    if ($base > 88) $base = 88;
    if ($base < 10) $base = 10;
    if ($level < 3) return $key;
    $arr = cms_kg_codeArr($code, $name);
    if ($base < 63) $key = str_replace('-', '', $key);
    $len = strlen($key);
    $base2 = $base * $base;
    $txt = array();
    for ($i = 0; $i < $len; $i++) {
        $x = 0;
        if ($base < 32) $x = cms_kg_base($key[$i++], 1) * $base2;
        $x += cms_kg_base($key[$i++], 1) * $base;
        if (isset($key[$i])) $x += cms_kg_base($key[$i], 1);
        $txt[] = str_pad($x, 3, 0, STR_PAD_LEFT);
    }
    if ($level > 8) $txt = cms_kd_add($txt, $arr, 1);
    if ($level > 7) $txt = cms_kd_add($txt, $arr,-3);
    if ($level > 6) $txt = cms_kd_add($txt, $arr, 3);
    if ($level > 5) $txt = cms_kd_add($txt, $arr,-2);
    if ($level > 4) $txt = cms_kd_add($txt, $arr, 2);
    if ($level > 3) $txt = cms_kd_add($txt, $arr,-1);
    $txt = cms_kd_add($txt, $arr, 1);
    $res = '';
    foreach ($txt as $v) $res .= str_pad($v, 3, 0, STR_PAD_LEFT);
    $md5 = cms_kg_md5txt($res, $code, $name);
    $md5err = (substr($res, 0, 3) <> $md5) ? 1 : 0;
    $dec['level']= $level;
    $dec['code'] = $code;
    $dec['name'] = $name;
    $dec['base'] = $base;
    $dec['key']  = $key;
    $dec['rand'] = substr($res, 0, 6);
    $dec['date'] = substr($res, 6, 3) * 256 + substr($res, 9, 3);
    $dec['date'] = date('Y-m-d', $dec['date'] * 3600 * 24 + mktime(0, 0, 0, 1, 1, 2000));
    $dec['hw'] = '';
    for ($i = 0; $i < 4; $i++) {
        $dec['hw'] .= strtoupper(str_pad(dechex(substr($res, 3 * $i + 12, 3)), 2, 0, STR_PAD_LEFT));
        if (1 == $i) $dec['hw'] .= '-';
    }
    if (3 == $level) {
        $dec['n1'] = substr($res, 24) % 256;
        $dec['n2'] = $dec['n3'] = $dec['n4'] = $dec['n5'] = 0;
        $dec['txt']= '';
    } else {
        $dec['n1'] = substr($res, 24, 3) * 256 + substr($res, 27, 3);
        $dec['n2'] = substr($res, 30, 3) * 256 + substr($res, 33, 3);
        $dec['n3'] = substr($res, 36, 3) + 0;
        $dec['n4'] = substr($res, 39, 3) + 0;
        $dec['n5'] = substr($res, 42, 3) + 0;
        $str = substr($res, 45);
        $len = ceil(strlen($str) / 3);
        $dec['txt'] = '';
        for ($i = 0; $i < $len; $i++) $dec['txt'] .= chr(str_replace('-', '', substr($str, $i * 3, 3)));
        $dec['txt'] = trim($dec['txt']);
    }
    $dec['err'] = 1;
    if ($md5err) return $dec;
    if ($dec['n1'] < 0 || $dec['n2'] < 0 || $dec['n3'] < 0 || $dec['n4'] < 0 || $dec['n5'] < 0 || $dec['n5'] > 255 || $dec['n4'] > 255 || $dec['n3'] > 255 || $dec['n2'] > 65535 || $dec['n1'] > 65535) return $dec;
    if ($level < 4 && $dec['n1'] > 65535) return $dec;
    if ($level < 4 && ($dec['n2'] > 0 || $dec['n3'] > 0 || $dec['n4'] > 0 || $dec['n5'] > 0)) return $dec;
    if ($level < 9 && strlen($dec['txt']) > 9) return $dec;
    if (!$hw) $hw = '0000-0000';
    if ($dec['hw'] <> strtoupper($hw)) return $dec;
    $dec['err'] = 0;
    return $dec;
}

//the following all private func
function cms_kd_add($txt, $arr, $x) {
    $y = abs($x);//1 2 3
    $add = ($x < 0) ? -1 : 1;
    $rand = str_split($txt[0] . $txt[1], $y);
    $numR = count($rand);
    $numT = count($txt) - 2;
    $numA = count($arr);
    for ($k = $numT + 1; $k > 1; $k--) {
        $pos = abs($k + $rand[$k % $numR] * $add) % $numT + 2;
        cms_kg_swap($txt[$k], $txt[$pos]);
    }
    foreach ($txt as $k => $v) {
        if ($numT) $pos = abs($k + $rand[$k % $numR] * $add) % $numT + 2;
        //255+7*106=997max::32*32=1024
        if ($k > 1) $txt[$k] -= $arr[$pos % $numA] % 107;
    }
    return $txt;
}
function cms_kg_base($x = 0, $dec = 0) {
    $b32 = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ'; //good for licence
    $b64 = $b32 . 'abcdefghijkmnpqrstuvwxyz01IOlo_-'; //good for cookie
    $b88 = $b64 . '~!@#$%^&*()+`={}|[]:;?,.'; //except '"<>/\ from 94keyboard
    if ($dec) return strpos($b88, $x) + 0;
    return substr($b88, $x, 1);
}
function cms_kg_now() {
    $now = floor((time() - mktime(0,0,0,1,1,2000)) / 24 / 3600); //max 179 year from year 2000
    return str_pad(cms_kg_b256(rand(0, 65535)), 6, 0, STR_PAD_LEFT) . str_pad(cms_kg_b256($now), 6, 0, STR_PAD_LEFT);
} //rand.now:123456.123456
function cms_kg_hw($hw = '') {
    $hw = str_replace('-', '', $hw);
    $res = '';
    for ($i = 0; $i < 4; $i++) $res .= str_pad(hexdec(substr($hw, $i*2, 2)), 3, 0, STR_PAD_LEFT);
    return $res;
} //max ffff-ffff
function cms_kg_b256($num = 0) {
    $num = base_convert($num, 10, 2);
    $len = ceil(strlen($num) / 8) * 8;
    $num = str_pad($num, $len, 0, STR_PAD_LEFT);
    $res = '';
    for ($i = 0; $i < $len; $i += 8) $res .= str_pad(base_convert(substr($num, $i, 8), 2, 10), 3, 0, STR_PAD_LEFT);
    return $res;
} //max 65536 * 65536 = 4294967295
function cms_kg_str($str = '', $level = 9) {
    $str = trim($str);
    if ('' === $str) $str = md5(time());
    if ($level < 9) $str = substr($str, 0, 9);
    $len = strlen($str);
    $res = '';
    for ($i = 0; $i < $len; $i++) $res .= str_pad(ord($str[$i]), 3, 0, STR_PAD_LEFT);
    return $res;
}
function cms_kg_txt($txt = '', $level = 9, $code = '', $name = '', $base = 32) {
    $code = cms_kg_code($code);
    $arr = cms_kg_codeArr($code, $name);
    $md5 = cms_kg_md5txt($txt, $code, $name);
    $txt = str_split($txt, 3);
    $txt[0] = $md5; //used for err checking
    $txt = cms_kg_add($txt, $arr, 1);
    if ($level > 3) $txt = cms_kg_add($txt, $arr,-1);
    if ($level > 4) $txt = cms_kg_add($txt, $arr, 2);
    if ($level > 5) $txt = cms_kg_add($txt, $arr,-2);
    if ($level > 6) $txt = cms_kg_add($txt, $arr, 3);
    if ($level > 7) $txt = cms_kg_add($txt, $arr,-3);
    if ($level > 8) $txt = cms_kg_add($txt, $arr, 1);
    return cms_kg_cout($txt, $base);
}
function cms_kg_code($code = '') {
    if (!$code) $code = time();
    return strtoupper(md5(md5($code) . $code));
}
function cms_kg_codeArr($code = '', $name = '') {
    $name = md5($name . md5($name));
    for ($i = 0; $i < 32; $i++) {
        $arr[] = ord($code[$i]) % 107;
        $arr[] = ord($name[$i]) % 107;
        $arr[] = ord($code[$i]) % 107;
        $arr[] = ord($name[$i]) % 107;
    } // 32 * 4 = 128
    return $arr;
}
function cms_kg_md5txt($txt = '', $code = '', $name = '') {
    $md5 = md5($code . md5(substr($txt, 3)) . md5($name));
    $res = 0;
    for ($i = 0; $i < 32; $i++) $res += hexdec($md5[$i]);
    return str_pad($res, 3, 0, STR_PAD_LEFT);
}
function cms_kg_add($txt = '', $arr = array(), $x = 0) {
    $y = abs($x); //1 2 3
    $add = ($x < 0) ? -1 : 1;
    $rand = str_split($txt[0] . $txt[1], $y);
    $numR = count($rand);
    $numT = count($txt) - 2;
    $numA = count($arr);
    foreach ($txt as $k => $v) {
        $pos = abs($k + $rand[$k % $numR] * $add) % $numT + 2;
        // 255 + 7 * 106 = 997 max :: 32 * 32 = 1024
        if ($k > 1) $txt[$k] += $arr[$pos % $numA];
    }
    foreach ($txt as $k => $v) {
        $pos = abs($k + $rand[$k % $numR] * $add) % $numT + 2;
        if ($k > 1) cms_kg_swap($txt[$k], $txt[$pos]);
    }
    return $txt;
}
function cms_kg_swap(&$a, &$b) {
    $t = $a;
    $a = $b;
    $b = $t;
}
function cms_kg_cout($txt = '', $base = 32) {
//base10-31: x*x*x=xxx
//base32-88: x*x  =xxx
    $num = count($txt);
    $base2 = $base * $base;
    $res = '';
    for ($i = 0; $i < $num; $i++) {
        $x = $txt[$i];
        if ($base < 32) {
            $x1 = floor($x / $base2);
            $x -= $x1 * $base2;
            $res .= cms_kg_base($x1);
        }
        $x2 = floor($x / $base);
        $x3 = $x - $x2 * $base;
        $res .= cms_kg_base($x2) . cms_kg_base($x3);
    }
    if ($base > 62) return $res;
    return implode('-', str_split($res, 6));
}
/**
 * L0: dynamic hash base16 (exl.01OI) FFFFFF
 * L1: dynamic hash base16 (exl.01OI) FFFF-FFFF
 * L2: dynamic hash base16 (exl.01OI) FFFF-FFFF-FFFF
 * L4: fixed.. hash base88 cms_hash(level,str,code) -- you can add own code='a|b|...'
 */
function cms_hash($level = 0, $str = '', $code = '') {
    if ($level > 3) return cms_hash4($str, $code);
    $str = md5(time() . rand() . $str);
    $str = str_replace(array('0','1','o','i'), '', $str);
    if (strlen($str) < 12) return cms_hash($level, $str);
    $str = strtoupper($str);
    if (!$level) return substr($str, 0, 6);
    if (1 == $level) return substr($str, 0, 4) . '-' . substr($str, 4, 4);
    return substr($str, 0, 4) . '-' . substr($str, 4, 4) . '-' . substr($str, 8, 4);
}
// 16 * 256 + 16 * 16 + 16 max = 4368 < base88 88 * 88 = 7744
function cms_hash4($str = '', $code = '') {
    $res = cms_hash_init($code);
    $res = md5(md5($res, $str) . $str);
    for ($i = 0; $i < 32; $i++) {
        $cur= 0;
        $cur+= hexdec($res[$i++]) * 256 + hexdec($res[$i++]) * 16 + hexdec($res[$i]);
        $r1 = floor($cur / 88);
        $r2 = $cur - $r1 * 88;
        $txt .= cms_kg_base($r1) . cms_kg_base($r2);
    }
    return $txt;
}
function cms_hash_init($code = '') {
    $init = 'नमः अमिताब बुद्ध|南無阿彌陀佛|南无阿弥陀佛|Namo Amitabha|namaḥ amitāba buddha|Noma Amitayus|南無阿弥陀仏(なんむあみだぶつ)(nanmuamidabutu)';
    if ($code) $init .= '|' . $code;
    $arr = explode('|', $init);
    foreach ($arr as $v) $res .= md5($v);
    return $res;
}
