<?php
namespace Topnew;

/**
 * Topnew Chart v 2019.11.11
 * The MIT License (MIT) Copyright (c) 1998-2018, Topnew Geo, topnew.net
 */

/**
 * ---------------------------------------------------------------------------
 * data fmt = '' || sxy, xsy, xss, sxx (all as array)
 * ---------------------------------------------------------------------------
 * fmt = '' or sxy (default) : col1 = serial, col2 = xAxis, col3 = yVal
 * max 3 cols, col4+ will be ignored
 * eg. select manager,month,count(*) qty from sales group by 1,2
 * Tom Jan 123
 * Sam May 234
 * data = cms_arr(sql,2) dirty or cms_arr(sql,2,1) clean
 *
 * fmt = xsy : col1 = xAxis, col2 = serial, col3 = yVal
 * max 3 cols, col4+ will be ignored
 * eg. select month,manager,count(*) qty from sales group by 1,2
 * Jan Tom 123
 * May Sam 234
 * data = cms_arr(sql,2) dirty or cms_arr(sql,2,1) clean
 * -- as a result this data same as xss
 *
 * fmt = xss : col1 = xAxis, col2...x = serial, yVal in the grid
 * eg. select month,count(*) qty,sum(amt) amt from sales group by 1
 * Jan 123 956 789 ...
 * Feb 222 933 444 ...
 * data = cms_arr(sql,1) -- only this need to convert to default sxy
 *
 * fmt = sxx : col1 = serial, col2...x = xAxis, yVal in the grid
 * very rarely when the col name is xAxis
 * eg. select manager,jan,feb,march from rep_sales
 * Tom 123 456 789 ...
 * Sam 222 333 444 ...
 * data = cms_arr(sql,1) -- data same as default sxy
 *
 * when there is only 2 cols, which means only 1 serial
 * no matter which fmt : col1 = xAxis, col2 = yVal
 * eg. select month,count(*) qty from sales group by 1
 * Jan 123
 * Feb 234
 * data = cms_row(sql,2) = cms_arr(sql,1,1) or dirty cms_arr(sql,1)
 * ---------------------------------------------------------------------------
 * as a result, if you can not remember which func to get data, remember this:
 * data = cms_arr(sql,2) : when fmt got 3 different chars eg xsy sxy
 * data = cms_arr(sql,1) : when fmt only has 2 different chars eg xss sxx
 *
 * ---------------------------------------------------------------------------
 * fmt = str || str_sxy, str_xsy, str_xss, str_sxx (all as string)
 * ---------------------------------------------------------------------------
 * default sepCol = , sepRow = ;
 *
 * str_sxy = 'Staff,month,Qty;Tom,Feb,12;Tom,Mar,34;Sam,Jan,112;...'
 * str_xsy = 'month,Staff,Qty;Feb,Tom,12;Mar,Tom,34;Jan,Sam,112;...'
 * str_xss = 'month,Qty,Vol;Feb,22,12;Mar,31,34;May,12,112;...'
 * str_sxx = 'Staff,Jan,Feb,Mar;Tom,1,2,3;Sam,2,3,4;...'
 *
 * ---------------------------------------------------------------------------
 * fmt = sql || sql_sxy, sql_xsy, sql_xss, sql_sxx (all as SQL)
 * ---------------------------------------------------------------------------
 * you need cms_arr() and cms_row() etc cms db function plugin to run this
 *
 * sql_sxy cms_arr(sql,2) select staff,year,count(*) from sales group by 1,2;
 * sql_xsy cms_arr(sql,2) select year,staff,count(*) from sales group by 1,2;
 * sql_xss cms_arr(sql,1) select year,count(*) qty,sum(amt) vol from sales group by 1;
 * sql_sxx cms_arr(sql,1) select staff,jan,feb,mar from rep_sales;
 *
 * if you only have 2 cols in sql, you have to set fmt = sql_sxx or sql_xss!!!
 * sql_*** cms_arr(sql,1) select a,b from tab -- only 2 cols
 */

class Chart
{
    private static $data = [];
    private static $init = [];

    public static function svg($data = null, $init = null) {
        if (!$data) {
            return;
        }
        self::initInit($init);
        self::initData($data);
        if (!self::$data) {
            return;
        }
        self::initColor();
        self::initChart();
        $init = self::$init;
        $svg = "\n" . '<svg viewBox="0 0 ' . $init['w'] . ' ' . $init['h'] . '" class="chart">'
            . "\n" . '  <rect width="' . $init['w'] .'" height="'. $init['h'] .'" class="chart-bg"></rect>'
            . "\n" . '  <rect x="' . $init['x1'] . '" y="' . $init['y1']
            . '" width="' . $init['x2'] . '" height="' . $init['y2'] . '" class="chart-box"></rect>';
        if ('pie' != $init['chart']) {
            $svg .= self::drawAxisX();
            $svg .= self::drawAxisY();
        }
        $svg .= self::drawTitle();
        if ($init['chart'] == 'pie') {
            self::$data = reset(self::$data);
            foreach (self::$data as $k => $v) {
                if ($v < 0 || !$v) {
                    unset(self::$data[$k]);
                }
            }
        }
        $svg .= self::drawLegend();
        $svg .= "\n  <!-- the chart -->\n"
            . '  <g transform="translate(' . $init['x1'] . ' ' . $init['y1'] . ')">';
        if ('line' == $init['chart']) {
            $svg .= self::drawLine();
        } elseif ('pie' == $init['chart']) {
            $svg .= self::drawPie();
        } else {
            $svg .= self::drawBar();
        }
        $svg .= "\n  </g>"
            . ($init['chart'] == 'pie' ? '' : self::$init['mouseInfo'])
            . "\n</svg>\n";
        return $svg . self::drawCss();
    }

    private static function drawAxisFormat($v, $format) {
        if (!$format) {
            return $v;
        }
        $format = explode('|', $format, 4); // eg substr|6 or format|0|.|, or data|M
        if ('format' == $format[0]) {
            return number_format(
                $v,
                isset($format[1]) ? ceil($format[1]) : 0,
                isset($format[2]) ? $format[2] : null,
                isset($format[3]) ? $format[3] : null
            );
        }
        if ('substr' == $format[0]) {
            if (isset($format[2])) {
                return substr($v, ceil($format[1]), ceil($format[2]));
            }
            return substr($v, isset($format[1]) ? ceil($format[1]) : 0);
        }
        if ('date' == $format[0]) {
            return date($format[1], strtotime($v));
        }
        return $v;
    }

    private static function drawAxisX() {
        $init = self::$init;
        $xNum = count(reset(self::$data));
        if ($init['is_bar']) {
            $xDiv = ($init['x2'] - $xNum - 1) / $xNum;
            $xVal[0] = round($xDiv / 2 + 1, 5);
            $xDiv++;
            $xLeft = 0;
        } else {
            $xDiv = $xNum > 1 ? $init['x2'] / ($xNum - 1) : $init['x2'];
            $xVal[0] = 0;
            $xLeft = $xDiv / 2;
        }
        $xLabel = array_keys(reset(self::$data));
        $angle1 = $angle2 = $angleCSS = '';
        if ($init['xAngle']) {
            $angle1 = '<g transform="translate(5) rotate(' . $init['xAngle'] . ')">';// best for 45
            $angle2 = '</g>';
            $angleCSS = ' xAngle';
        }
        $svg = "\n" . '  <g class="chart-tick axisX' . $angleCSS . '" transform="translate(' . $init['x1'] . ',' . ($init['y1'] + $init['y2']) . ')">';
        $cNum = count($init['color']);
        $j = 0;
        foreach (self::$data as $k => $arr) {
            $i = 0;
            foreach ($arr as $k2 => $v) {
                if ($i) {
                    $xVal[$i] = round($xVal[$i - 1] + $xDiv, 5);
                }
                if (!isset($labelTxt[$i])) {
                    $labelTxt[$i] = '<tspan x="' . $xVal[$i] . '" dy="15">' . $k2 .'</tspan>';
                }
                $labelTxt[$i] .= '<tspan x="' . $xVal[$i] . '" dy="15" fill="#'
                    . $init['color'][$j % $cNum] . '">'
                    . ($k ? $k . ' : ' : '')
                    . self::drawAxisFormat($v, $init['yFormat'])
                    . $init['yUnit'] . '</tspan>';
                $i++;
            }
            $j++;
        }
        $mouseInfo = "\n" .'  <g class="chartInfo" transform="translate(' . $init['x1'] . ',' . $init['y1'] . ')">';
        $mouseInfoH = (count(self::$data) + 1) * 15 + 7;
        for ($i = 0, $skip = 0; $i < $xNum; $i++) {
            $rect = '<rect x="' . round($i * $xDiv - $xLeft, 5) . '" width="' . round($xDiv, 5) . '" ';
            $mouseInfo .= "\n" .'    <g>' . $rect . 'height="' . $init['y2'] . '" fill="#000" opacity="0"/>'
                . $rect . 'height="' . $mouseInfoH . '"/><text>' . $labelTxt[$i] . '</text></g>';
            if (
                !$init['xSkip'] ||
                ($init['xSkip'] > 0 && $i == ($init['xSkip'] + 1) * $skip) ||
                ($init['xSkip'] < 0 && !(substr($xLabel[$i], -2) % $init['xSkip']))
            ) {
                $skip++;
                $svg .= "\n" . '    <g transform="translate(' . $xVal[$i] . ')">'
                    . '<line y2="-' . $init['y2'] .'"></line>'
                    . $angle1
                    . '<text y="3" dy=".71em">'
                    . self::drawAxisFormat($xLabel[$i], $init['xFormat'])
                    . '</text>' . $angle2 . '</g>';
            }
        }
        $mouseInfo .= "\n  </g>";
        self::$init['xVal'] = $xVal;
        self::$init['mouseInfo'] = $mouseInfo;
        return $svg . "\n" . '  </g>';
    }

    private static function drawAxisY() {
        $max_min = reset(self::$data);
        $max = $min = reset($max_min);
        $init = self::$init;
        if ($init['chart'] == 'barV') {
            foreach (self::$data as $arr) {
                foreach ($arr as $k => $v) {
                    $TTL[0][$k] = $v + (isset($TTL[0][$k]) ? $TTL[0][$k] : 0);
                }
            }
        } else {
            $TTL = self::$data;
        }
        foreach ($TTL as $arr) {
            $min = min(min($arr), $min);
            $max = max(max($arr), $max);
        }
        if ($max < 0 && $min < 0) {
            $ttl = $min;
        } elseif ($max < 0 || $min < 0) {
            $ttl = $max - $min;
        } else {
            $ttl = $max;
        }
        if ($ttl < 0) {
            $ttl = abs($ttl);
        }
        $zoom = pow(10, floor(log10($ttl))) ?: 1;
        $ttl /= $zoom;
        if ($ttl == 1) {
            $step = 0.2;
        } elseif ($ttl > 6) {
            $step = 2;
        } elseif ($ttl > 5) {
            $step = 1.2;
        } elseif ($ttl > 4) {
            $step = 1;
        } elseif ($ttl > 3) {
            $step = 0.8;
        } elseif ($ttl > 2) {
            $step = 0.6;
        } else {
            $step = 0.4;
        }
        if ($max <= 0 && $min < 0) {
            for ($i = 0; $i < 6; $i++) {
                $yVal[] = 0 - $i * $zoom * $step;
            }
        } else {
            for ($i = 5; $i >- 1; $i--) {
                $yVal[] = $i * $zoom * $step;
            }
            if ($max <= 0 || $min < 0) {
                self::drawAxisYAudit($max, $min, $step, $zoom, $yVal);
            }
        }
        if ($max > 0 && $yVal[1] >= $max) {
            array_shift($yVal);
        } elseif ($min < 0 && $yVal[4] <= $min) {
            array_pop($yVal);
        }
        $step = count($yVal);
        if ($min < $yVal[$step-1]) {
            $yVal[] = $yVal[$step-1] - abs($yVal[$step-2] - $yVal[$step-3]);
        }
        $step = $init['y2'] / (count($yVal) - 1);

        $svg = "\n" . '  <g class="chart-tick axisY" transform="translate(' . $init['x1'] . ' ' . $init['y1'] .')">';
        $yDiv = count($yVal);
        $last_not_zero = end($yVal);
        for ($i = 0; $i < $yDiv; $i++) {
            $svg .= "\n" . '    <g transform="translate(0,' . $i * $step . ')">'
                . '<line x2="' . $init['x2'] .'"></line>';
            if ($yVal[$i] || $last_not_zero) {
                $svg .= '<text x="-3" dy=".32em">'
                    . self::drawAxisFormat($yVal[$i], $init['yFormat'])
                    . $init['yUnit'] . '</text>';
            }
            $svg .= '</g>';
        }
        self::$init['yVal'] = $yVal;
        self::$init['zoom'] = $init['y2'] / abs($yVal[0] - end($yVal));
        return $svg . "\n" . '  </g>';
    }

    private static function drawAxisYAudit($max, $min, $step, $zoom, &$yVal, $count = 0) {
        if ($count <= 5 && $yVal[1] > $max && $yVal[5] > $min) {
            $yVal[] = $yVal[5] - $zoom * $step;
            array_shift($yVal);
            self::drawAxisYAudit($max, $min, $step, $zoom, $yVal, $count++);
        }
    }

    private static function drawBar() {
        $init = self::$init;
        $i = 0;
        $w1 = $xDiv = abs((isset($init['xVal'][1]) ? $init['xVal'][1] : 0) - $init['xVal'][0] - 1);
        $half = $xDiv / 2;
        if ($init['chart'] == 'bar') {
            $w1 /= count(self::$data); // not barV not barS
        }
        $w1 = round($w1, 5);
        $cNum = count($init['color']);
        $opacity = $init['chart'] == 'barS' ? ' opacity=".8"' : '';
        $valClass = '';
        if ($init['valShow']) {
            $xMove = -90 == $init['valAngle'] ? 4 : ($init['valAngle'] > 0 ? -3 : 0);
            $yMove =  45 == $init['valAngle'] ? 5 : 0;
            $valClass = ' class="valShow"';
        }
        $svg = '';
        $colorBar = $init['colorBar'] && count(self::$data) == 1;
        foreach (self::$data as $arr) {
            $svg .= $colorBar ? '' : "\n" . '    <g fill="#' . $init['color'][$i % $cNum] . '"' . $valClass . '>';
            $j = 0;
            foreach ($arr as $v) {
                $svg .= $colorBar ? "\n" . '    <g fill="#' . $init['color'][$j % $cNum] . '"' . $valClass . '>' : '';
                $hold[$i][$j] = $v;
                if ($init['chart'] == 'barV') {
                    // when more than 1 negative bar, barV does not work--fix later
                    if ($i) {
                        $Y[$j] = (isset($Y[$j]) ? $Y[$j] : 0) + ($hold[$i-1][$j] > 0 ? $hold[$i-1][$j] : 0);
                    }
                } else {
                    $Y[$j] = 0;
                }
                //if ($is_barV && $i) $Y[$j] += $hold[$i-1][$j];
                //else $Y[$j] = 0;
                $x = $init['xVal'][$j] - $half + ($init['chart'] != 'bar' ? 0 : $i * $w1);
                $y = $init['yVal'][0] - (isset($Y[$j]) ? $Y[$j] : 0);
                if ($v > 0) {
                    $y -= $v;
                }
                $x = round($x, 5);
                $y = round($y * $init['zoom'], 5);
                $svg .= "\n" . '      <rect' . $opacity . ' x="'. $x . '" y="' . $y
                    . '" width="' . $w1 . '" height="' . round(abs($v) * $init['zoom'], 5)
                    . '"/>';//remove inside rect: <title>'. $v .'</title>
                if ($init['valShow'] && $v) {
                    $svg .= self::drawVal($init['valAngle'], $x + $w1 /2, $y, $xMove, $yMove, self::drawAxisFormat($v, $init['yFormat']));
                }
                $j++;
                $svg .= $colorBar ? "\n" . '    </g>' : '';
            }
            $svg .= $colorBar ? '' : "\n" . '    </g>';
            $i++;
        }
        return $svg;
    }

    private static function drawCss() {
        if (!self::$init['css'] && !self::$init['style']) {
            return;
        }
        $svg = '<style>';
        if (self::$init['css']) {
            $svg .= "\n" . 'svg.chart{display:block}'
            . "\n" . '.chart-bg{fill:#eee;opacity:.5}'
            . "\n" . '.chart-box{fill:#fff;opacity:1}'
            . "\n" . '.chart-tick line{stroke:#ddd;stroke-width:1;stroke-dasharray:5,5}'
            . "\n" . '.chart text{font-family:Helvetica,Arial,Verdana,sans-serif;font-size:12px;fill:#666}'
            . "\n" . '.axisX text{text-anchor:middle}'
            . "\n" . '.xAngle text{text-anchor:start}'
            . "\n" . '.axisY text{text-anchor:end}'
            . "\n" . '.chart circle{stroke-width:2px;stroke:#eee}'
            . "\n" . '.chart .line{fill:none;stroke-width:3}'
            . "\n" . '.chart .fill{stroke-width:0}'
            . "\n" . '.valShow text,.pie text{fill:#fed;opacity:.8;font-size:10px}'
            . "\n" . '.pie text{font-size:12px;stroke-width:.5}'
            . "\n" . '.chartInfo g{opacity:0}'
            . "\n" . '.chartInfo g:hover{opacity:1}'
            . "\n" . '.chartInfo .textBg{fill:#000;opacity:.9}'
            . "\n" . '.chartInfo text{text-anchor:middle;fill:#887}'
            . "\n" . '.chartInfo g rect:nth-child(even){fill:#eee;opacity:.8;stroke:#eed;stroke-width:1}'
            . "\n" . 'circle.pieBg{fill:#eee;stroke:none}';
        }
        if (self::$init['style']) {
            $svg .= "\n" . self::$init['style'] . "\n";
        }
        return $svg . '</style>' . "\n";
    }

    private static function drawLegend() {
        $init = self::$init;
        if (!$init['legend'] || (count(self::$data) < 2 && $init['chart'] != 'pie')) {
            return;
        }
        $i = 0;
        if (in_array($init['legend'], ['T', 'B'])) {
            $x = $init['legendW'];
            $y = 0;
            if ('B' === $init['legend']) {
                $init['y1'] += $init['y2'] + ($init['chart'] == 'pie' ? 25 : 40) + ($init['xTitle'] ? 15 : 0);
            }
        } else {
            $x = 0;
            $y = 16;
            $init['y1'] += 15;
            if ('L' == $init['legend']) {
                $init['x1'] = 9;
            } else {
                $init['x1'] += $init['x2'] + 8;
            }
        }
        $svg = "\n" . '  <!-- legend -->';
        $cNum = count($init['color']);
        foreach (self::$data as $k => $arr) {
            $svg .= "\n" . '  <g fill="#' . $init['color'][$i % $cNum] . '">';
            $cx = $init['x1'] + 6 + $i * $x;
            $cy = $init['y1'] - 10 + $i * $y;
            $svg .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="6"/>'
                . '<text x="' . ($cx + 12) . '" y="' . ($cy + 4) . '">' . $k . '</text>'
                . '</g>';
            $i++;
        }
        return $svg;
    }

    private static function drawLine() {
        $init = self::$init;
        $i = 0;
        $cNum = count($init['color']);
        $svg = '';
        foreach (self::$data as $arr) {
            $j = 0;
            $line = $dot = '';
            foreach ($arr as $v) {
                $x = round($init['xVal'][$j++], 5);
                $y = round(($init['yVal'][0] - $v) * $init['zoom'], 5);
                $line .= ' '. $x .','. $y;
                $dot .= "\n" . '      <circle cx="' . $x . '" cy="' . $y . '" r="3"/>';//removed: <title>'. $v .'</title>
                if ($init['valShow']) {
                    $dot .= self::drawVal($init['valAngle'], $x, $y - 15, 0, 0, self::drawAxisFormat($v, $init['yFormat']));
                }
            }
            $svg .= "\n" . '    <g fill="#' . $init['color'][$i % $cNum] . '">'
                . "\n" . '      <path d="M' . substr($line, 1)
                . '" class="line" stroke="#' . $init['color'][$i++ % $cNum] . '"/>'
                . $dot . "\n" . '    </g>';
        }
        return $svg;
    }

    private static function drawPie() {
        $sum = array_sum(self::$data);
        $pct = [];
        foreach (self::$data as $k => $v) {
            $pct[$k] = round($v / $sum, 5);
        }
        $init = self::$init;
        $R = round($init['y2'] / 2, 5);
        $svg = "\n" . '    <g transform="translate('. $R . ',' . $R . ')" class="pie">'
            . "\n" . '      <circle r="' . $R . '" class="pieBg"/>';
        $i = 0;
        $cNum = count($init['color']);
        $Arc = $init['pieArc'];
        foreach ($pct as $k => $v) {
            $a = round($v * 360, 5);
            if (360 == $a) {
                $svg .= "\n" . '      <circle r="'. $R .'" fill="#' . $init['color'][$i % $cNum] . '"/>';
            } elseif ($a) {
                $pie_pct = round($v * 100) . '%';
                $pie_val = self::drawAxisFormat(self::$data[$k], $init['yFormat']);
                $svg .= "\n" . '      <g'
                    . ($Arc ? ' transform="rotate(' . $Arc . ')"' : '')
                    . ' fill="#' . $init['color'][$i % $cNum]
                    . '" stroke="#fff" stroke-width="1">'
                    . "<title>$k : $pie_val ($pie_pct)</title>";
                $r = deg2rad($a);
                $x = round(cos($r) * $R, 5);
                $y = round(sin($r) * $R, 5);
                $svg .= '<path d="M0,0 L'. "$R,0 A$R,$R 0 " . ($a > 180 ? 1 : 0) . ",1 $x,$y z\"/>";
                if ($init['valShow'] || $init['piePct']) {
                    $V = $init['valShow'] ? $pie_val : '';
                    if ($init['piePct']) {
                        $V .= ($V ? ' (' : '') . $pie_pct . ($V ? ')' : '');
                    }
                    $svg .= '<g transform="rotate(' . ($a / 2 - 400 / $R) . ')">' // why 400? looks odd
                        . self::drawVal(0, round($R * .6, 2), 0, 0, 0, $V) .'</g>';
                }
                $svg .= '</g>';
                $Arc += $a;
            }
            $i++;
        }
        if ($init['pieStripe']) {
            $stripeCSS = 'style="fill:none;stroke:#fff;stroke-width:' . round($R * 2 / 11, 5) . ';opacity:0.2"';
            $svg .= "\n      <!-- stripes -->\n      "
                . '<circle r="' . round($R * 8 / 11, 5) . '" ' . $stripeCSS . '/>'
                . '<circle r="' . round($R * 4 / 11, 5) . '" ' . $stripeCSS . '/>'
                . "\n      "
                . '<circle r="' . round($R / 11, 5) . '" style="fill:#fff;stroke:0;opacity:0.2"/>';
        }
        if ($init['pieDonut']) {
            $svg .= "\n" . '      <circle r="' . round($R * .45) . '" class="pieBg"/>';//donut
        }
        return $svg . "\n" . '    </g>';
    }

    private static function drawTitle() {
        $init = self::$init;
        $svg = '';
        if ($init['title']) {
            $svg .= "\n" . '  <text y="15" x="';
            if (1 == $init['titleAlign']) {
                $svg .= 5 . '"'; // left
            } elseif (3 == $init['titleAlign']) {
                $svg .= $init['w'] - 5 . '" text-anchor="end"'; // right
            } else {
                $svg .= $init['w'] / 2 . '" text-anchor="middle"'; // default center
            }
            $svg .= '>' . $init['title'] .'</text>';
        }
        $y = $init['y1'] + $init['y2'];
        if ($init['xTitle']) {
            $svg .= "\n" . '  <text y="' . ($y + ($init['chart'] == 'pie' ? 15 : 37)) .'" x="';
            if (1 == $init['xTitleAlign']) {
                $svg .= 5 . '"'; // left
            } elseif (3 == $init['xTitleAlign']) {
                $svg .=  $init['w'] - 5 . '" text-anchor="end"'; // right
            } else {
                $svg .= $init['w'] / 2 . '" text-anchor="middle"'; // default center
            }
            $svg .= '>' . $init['xTitle'] . '</text>';
        }
        if ($init['yTitle']) {
            $svg .= "\n" . '  <g transform="translate(15,' . $y . ')"><text x="';
            if (1 == $init['yTitleAlign']) {
                $svg .= 0 . '"'; // left
            } elseif (3 == $init['yTitleAlign']) {
                $svg .= $init['y2'] . '" text-anchor="end"'; // right
            } else {
                $svg .= $init['y2'] / 2 . '" text-anchor="middle"'; // default center
            }
            $svg .= ' transform="rotate(-90)">' . $init['yTitle'] . '</text></g>';
        }
        return $svg;
    }

    private static function drawVal($valAngle, $x, $y, $xMove, $yMove, $v) {
        if (!$valAngle) {
            return '<text x="' . $x . '" y="' . ($y + 10) . '" text-anchor="middle">' . $v . '</text>';
        }
        return "\n" . '      <g transform="translate('
            . ($x + $xMove) . ',' . ($y + $yMove)
            . ') rotate(' . $valAngle . ')"><text>' . $v . '</text></g>';
    }

    private static function initChart() {
        $num = count(self::$data);
        $xNum = count(reset(self::$data));
        $arr = self::$init;
        if (!$arr['xSkip'] && $arr['xSkipMax'] > 0) {
            $arr['xSkip'] = floor($xNum / $arr['xSkipMax']);
        }
        if (!$arr['chart'] || !in_array($arr['chart'], ['line','pie','barV','barS'])) {
            $arr['chart'] = 'bar';
        }
        $arr['is_bar'] = 'bar' === substr($arr['chart'], 0, 3);
        $arr['gapL'] += 9; // default box margin 9px each side
        $arr['gapT'] += 9;
        $arr['gapR'] += 9;
        $arr['gapB'] += 9;
        $arr['gapT'] += $arr['title']  ? 15 : 0;
        $arr['gapB'] += $arr['xTitle'] ? 15 : 0;
        $arr['gapL'] += $arr['yTitle'] ? 15 : 0;
        if (!strlen($arr['legend'])) {
            $arr['legend'] = ($num > 1 || 'pie' == $arr['chart']) ? 'R' : '0';
        }
        if ($arr['legendW'] < 1) {
            $arr['legendW'] = 80;
        }
        if ($arr['legend']) { // 0 T B R
            if ('T' === $arr['legend']) {
                $arr['gapT'] += 15;
            } elseif ('B' === $arr['legend']) {
                $arr['gapB'] += 15;
            } elseif ('L' === $arr['legend']) {
                $arr['gapL'] += $arr['legendW'];
            } else {
                $arr['gapR'] += $arr['legendW'];
            }
        }
        if ('pie' == $arr['chart']) {
            $arr['gapL'] += $arr['yTitle'] ? 3 : 0;
            if ('L' === $arr['legend']) {
                $arr['gapL'] += 51;
            } elseif ('R' === $arr['legend']) {
                $arr['gapR'] += 51;
            }
        } else {
            $arr['gapL'] += 51; // default yLabel
            $arr['gapB'] += 16; // default xLabel
        }
        $arr['x1'] = $arr['gapL'];
        $arr['y1'] = $arr['gapT'];
        if ($arr['x1'] < 0) {
            $arr['x1'] = 0;
        }
        if ($arr['y1'] < 0) {
            $arr['y1'] = 0;
        }

        if ('pie' != $arr['chart'] && $arr['wFix']) {
            $arr['x2'] = 10 * $num * $xNum + $xNum + 1;
            $arr['w'] = $arr['x1'] + $arr['x2'] + $arr['gapR'];
        } else {
            if ($arr['w'] < 1) {
                $arr['w'] = 480;
            }
            $arr['x2'] = $arr['w'] - $arr['x1'] - $arr['gapR'];
            if ($arr['x2'] > $arr['w'] || $arr['x2'] < $arr['x1']) {
                $arr['x2'] = $arr['w'];
            }
        }
        if ($arr['h'] < 1) {
            $arr['h'] = 250;
        }
        if ($arr['h'] > $arr['w']) {
            //$arr['h'] = $arr['w'];
        }
        if ('pie' == $arr['chart']) {
            $arr['y2'] = $arr['x2'];
            $arr['h'] = $arr['y1'] + $arr['y2'] + $arr['gapB']; // pie h auto calculated
        } else {
            $arr['y2'] = $arr['h'] - $arr['y1'] - $arr['gapB'];
            if ($arr['y2'] > $arr['h'] || $arr['y2'] < $arr['y1']) {
                $arr['y2'] = $arr['h'];
            }
        }
        self::$init = $arr;
    }

    private static function initColor() {
        // the following are default 11 colors
        $defa = ['d9534f', 'f0ad4e', '5bc0de', '5cb85c', '337ab7', 'f26522', '754c24', 'd9ce00', '0e2e42', 'ce1797','672d8b'];

        $color = [];
        // add colors at front
        if (self::$init['color'] && !is_array(self::$init['color'])) {
            $col = explode(',', self::$init['color']);
            if (is_array($col)) {
                foreach ($col as $c) {
                    $c = trim(substr(trim($c), 0, 6));
                    if (strlen($c) > 2) {
                        $color[] = $c;
                    }
                }
            }
        }
        // del colors
        if (self::$init['colorDel']) {
            $col = explode(',', self::$init['colorDel']);
            if (is_array($col)) {
                foreach ($col as $c) {
                    unset($defa[ceil($c)]);
                }
            }
        }
        foreach ($defa as $c) {
            $color[] = $c;
        }
        // add colors at end
        if (self::$init['colorAdd']) {
            $col = explode(',', self::$init['colorAdd']);
            if (is_array($col)) {
                foreach ($col as $c) {
                    $c = trim(substr(trim($c), 0, 6));
                    if (strlen($c) > 2) {
                        $color[] = $c;
                    }
                }
            }
        }
        self::$init['color'] = $color;
    }

    private static function initData($data) {
        $fmt = self::$init['fmt'];
        $fmt3 = substr($fmt, 0, 3);
        if ('sql' === $fmt3) {
            $fmt = substr($fmt, 4);
            $data = [];
            if (isset(self::$init['db']) && is_object(self::$init['db'])) {
                $data = ('sxx' === $fmt || 'xss' === $fmt)
                    ? self::$init['db']->arr($data)
                    : self::$init['db']->arr2($data);
            }
        } elseif ('str' === $fmt3) {
            $fmt = substr($fmt, 4);
            $data = self::initDataStr($data, $fmt);
        } elseif ('jso' === $fmt3) {
            $data = json_decode($data, 1);
        }
        $num = $data ? count($data) : 0;
        if (!$num) {
            return;
        }
        if (!is_array(reset($data))) {
            $data = [$data]; // only 2 cols
            $num = 1;
        } elseif ('xss' === $fmt || 'xsy' === $fmt) {
            $sxy = [];
            foreach ($data as $x => $arr) {
                foreach ($arr as $s => $y) {
                    $sxy[$s][$x] = $y;
                }
            }
            $data = $sxy;
        }

        $keys = []; // x labels
        foreach ($data as $s => $arr) {
            foreach ($arr as $x => $y) {
                if (!in_array($x, $keys)) {
                    $keys[] = $x;
                }
                if (is_array($y)) {
                    $data[$s][$x] = reset($y); // clean dirty data
                }
            }
        }
        if (in_array(self::$init['xKey'], ['year', 'month', 'week', 'day', 'hour'])) {
            $keys = self::initDataYmdH($keys);
        } elseif ('x' === self::$init['sort']) {
            sort($keys);
        }
        if ('y' === self::$init['sort'] && $num > 1) {
            ksort($data);
        }

        $res = [];
        foreach ($data as $k => $arr) {
            foreach ($keys as $k2) {
                $res[$k][$k2] = isset($arr[$k2]) ? $arr[$k2] + 0 : 0;
            }
        }
        if ('y' === self::$init['sort'] && 1 == $num) { // if pie make sure only 1 array
            $pie = reset($res);
            arsort($pie);
            $res = [$pie];
        }

        if (self::$init['ySum']) {
            foreach ($res as $k => $arr) {
                $j = $hold = 0;
                foreach ($arr as $k2 => $v) {
                    if ($j++) {
                        $res[$k][$k2] += $hold;
                    }
                    $hold += $v;
                }
            }
        }

        if ('barS' === self::$init['chart']) {
            // not sure what is doing here ? -- pls add wiki when testing
            foreach ($res as $k => $arr) {
                $sortS[$k] = array_sum($arr);
            }
            array_multisort($sortS, SORT_DESC, $res);
        }

        self::$data = $res;
    }

    private static function initDataStr($str, $fmt) {
        $sepRow = self::$init['sepRow'] ?: ';';
        $sepCol = self::$init['sepCol'] ?: ',';
        $rows = explode($sepRow, trim($str));
        $head = explode($sepCol, trim(array_shift($rows)));
        foreach ($head as $v) {
            $cols[] = trim($v);
        }
        unset($cols[0]);
        if (1 === count($cols)) {
            foreach ($rows as $i => $r) {
                $r = explode($sepCol, trim($r));
                $data[trim($r[0])] = trim($r[1]);
            }
            return $data;
        }
        foreach ($rows as $i => $r) {
            $r = explode($sepCol, trim($r));
            foreach ($r as $j => $v) {
                $r[$j] = trim($v);
            }
            if ('xss' === $fmt || 'sxx' === $fmt) {
                foreach ($r as $j => $v) {
                    if ($j) {
                        $data[$r[0]][$cols[$j]] = $v;
                    }
                }
            } else {
                $data[$r[0]][$r[1]] = isset($r[2]) ? $r[2] : 0;
            }
        }
        return $data;
    }

    private static function initDataYmdH($keys) {
        $xMin = self::$init['xMin'] ?: min($keys);
        $xMax = self::$init['xMax'] ?: max($keys);
        $y1 = substr($xMin, 0, 4);
        $y2 = substr($xMax, 0, 4);
        $xKey = self::$init['xKey'];
        if ('year' === $xKey) {
            return range($y1, $y2);
        }

        $keys = [];
        if ('week' === $xKey) {
            $xMin = substr($xMin, 0, 6);
            $xMax = substr($xMax, 0, 6);
            for ($Y = $y1; $Y <= $y2; $Y++) {
                for ($W = 1; $W < 54; $W++) {
                    $yw = $Y . 'W' . str_pad($W, 2, 0, STR_PAD_LEFT);
                    if ($yw >= $xMin && $yw <= $xMax) {
                        $keys[] = $yw;
                    }
                }
            } // year week
            return $keys;
        }

        $m1 = substr($xMin, 0, 7); $d1 = substr($xMin, 0, 10);
        $m2 = substr($xMax, 0, 7); $d2 = substr($xMax, 0, 10);
        for ($Y = $y1; $Y <= $y2; $Y++) {
            for ($M = 1; $M < 13; $M++) {
                $ym = $Y .'-'. str_pad($M, 2, 0, STR_PAD_LEFT);
                if ($ym >= $m1 && $ym <= $m2) {
                    if ('month' === $xKey) {
                        $keys[] = $ym;
                    } else {
                        for ($D = 1; $D < 32; $D++) {
                            $ymd = $ym .'-'. str_pad($D, 2, 0, STR_PAD_LEFT);
                            if (checkdate($M, $D, $Y) && $ymd >= $d1 && $ymd <= $d2) {
                                if ('day' === $xKey) {
                                    $keys[] = $ymd;
                                } else {
                                    for ($H = 0; $H < 24; $H++) {
                                        $ymdH = $ymd .' '. str_pad($H, 2, 0, STR_PAD_LEFT);
                                        if ($ymdH >= $xMin && $ymdH <= $xMax) {
                                            $keys[] = $ymdH;
                                        }
                                    }
                                } // hour
                            } // valid day
                        }
                    } // day
                } // valid m
            }
        } // y m
        return $keys;
    }

    private static function initInit($init) {
        self::$init = [];
        self::$init['wFix'] = isset($init['w']) && $init['w'] == 'fix';
        $ceil = [
            'w',
            'h',
            'gapT',
            'gapR',
            'gapB',
            'gapL',
            'legendW',
            'titleAlign',
            'xTitleAlign',
            'yTitleAlign',
            'xSkip',
            'xSkipMax',
            'xAngle',
            'ySum',
            'pieArc',
            'pieStripe',
            'piePct',
            'pieDonut',
            'css',
            'valAngle',
            'valShow',
            'colorBar',
        ];
        $trim = [
            'chart',
            'fmt',
            'title',
            'legend',
            'style',
            'sepCol',
            'sepRow',
            'xTitle',
            'yTitle',
            'yUnit',
            'xFormat',
            'yFormat',
            'xMin',
            'xMax',
            'xKey',
            'sort',
            'xSum',
            'colorDel',
            'colorAdd',
        ];
        foreach ($ceil as $k) {
            self::$init[$k] = isset($init[$k]) ? ceil($init[$k]) : 0;
        }
        foreach ($trim as $k) {
            self::$init[$k] = isset($init[$k]) ? trim($init[$k]) : '';
        }
        if (self::$init['yUnit']) {
            self::$init['yUnit'] = ' ' . self::$init['yUnit'];
        }
        $defa = [
            'color',
        ];
        foreach ($defa as $k) {
            self::$init[$k] = isset($init[$k]) ? $init[$k] : '';
        }
    }
}
