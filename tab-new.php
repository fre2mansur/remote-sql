<?php
$data = array('sql');
include 'inc.page.php';
sidu_cook_copy($SIDU);
head($SIDU, $conn);
main($SIDU);
foot($SIDU);

function main($SIDU) {
    $err = save_data($SIDU);
    echo '<p class="b dot">', lang(4101) ,' <span class="red">', $SIDU[1] , ($SIDU['eng'] == 'pgsql' ? '.'.$SIDU[2] : '') ,'</span></p>';
    if ($err) echo '<p class="err">', $err ,'</p>';
    echo cms_form('form', 'myform', "tab-new.php?id=$SIDU[0],$SIDU[1],$SIDU[2],$SIDU[3],$SIDU[4],$SIDU[5],$SIDU[6]");
    echo NL .'<div style="width:50%;min-width:400px;margin-right:10px;float:left">';
    echo cms_form('textarea', 'sql', $SIDU['data']['sql'], array('spellcheck'=>'false', 'style'=>'width:100%;height:350px', 'class'=>'box', 'id'=>'txtArea'));
    echo cms_form('submit', 'cmd', lang(4102));
    echo NL .'</div><p id="txtRep">';

    $str = "9|0|smallint|smallint
0|1|32768|smallint unsigned NOT NULL DEFAULT 0
1|0|int|int
0|1|2,147,483,647|int unsigned NOT NULL DEFAULT 0
1|0|numeric|numeric(7,2)
0|1|(7,2)|numeric(7,2) unsigned NOT NULL DEFAULT 0.00
2|0|char|char(255)
0|1|255|char(255) NOT NULL DEFAULT ''
0|0|binary|char(255) binary NOT NULL DEFAULT ''
1|0|varchar|varchar(255)
0|1|255|varchar(255) NOT NULL DEFAULT ''
0|0|binary|varchar(255) binary NOT NULL DEFAULT ''
1|0|text|text
0|1|65535|text NOT NULL DEFAULT ''
2|0|date|date
0|1|YYYY-MM-DD|date NOT NULL DEFAULT '0000-00-00'
1|0|timestamp|timestamp
0|1|YmdHis|timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
0|0|now|timestamp NOT NULL DEFAULT now()
2|0|auto|auto_increment
0|1|!null|NOT NULL
0|0|PK|NOT NULL auto_increment PRIMARY KEY
1|0|PK|PRIMARY KEY
0|1|PK(a)|PRIMARY KEY (col1,col2)
0|0|UK|UNIQUE uk (col1,col2)
0|1|idx|INDEX idx (col1,col2)
2|0|MyISAM|ENGINE=MyISAM
1|0|InnoDB|ENGINE=InnoDB
1|0|utf8mb4|DEFAULT CHARSET=utf8mb4";
    if ($SIDU['eng'] == 'pgsql') $str = strtr($str, array(" DEFAULT \'0000-00-00\'"=>'', " DEFAULT \'0000-00-00 00:00:00\'"=>'', 'auto|auto_increment'=>'serial|serial', 'NOT NULL auto_increment'=>'serial NOT NULL', "0|0|binary|char(255) binary NOT NULL DEFAULT \'\'"=>'', "0|0|binary|varchar(255) binary NOT NULL DEFAULT \'\'"=>'', 'MyISAM|ENGINE = MyISAM'=>'With OID|WITH (OIDS=TRUE)', '0|1|InnoDB|ENGINE = InnoDB'=>'', ' unsigned'=>'', 'PRIMARY KEY ('=>'CONSTRAINT pk PRIMARY KEY (', 'UNIQUE uk ('=>'CONSTRAINT uk UNIQUE (', 'idx|INDEX idx (col1,col2)'=>"FK|CONSTRAINT fk FOREIGN KEY (col) REFERENCES tab(pk) MATCH SIMPLE\\n\\tON UPDATE NO ACTION ON DELETE NO ACTION"));
    elseif ($SIDU['eng'] == 'sqlite') $str = "9|0|int|int
0|1|PK|int PRIMARY KEY
0|1|text|text
0|1|real|real";
    elseif ($SIDU['eng'] == 'cubrid') $str = strtr($str, array('1|0|text|text'=>'', '0|1|65535|text NOT NULL DEFAULT \\\'\\\''=>'', " DEFAULT \'0000-00-00\'"=>'', " DEFAULT \'0000-00-00 00:00:00\'"=>'', "0|0|binary|char(255) binary NOT NULL DEFAULT \'\'"=>'', "0|0|binary|varchar(255) binary NOT NULL DEFAULT \'\'"=>'', 'MyISAM|ENGINE = MyISAM'=>'', '0|1|InnoDB|ENGINE = InnoDB'=>'', ' unsigned'=>'', 'PRIMARY KEY ('=>'CONSTRAINT pk PRIMARY KEY (','UNIQUE uk ('=>'CONSTRAINT uk UNIQUE (', 'idx|INDEX idx (col1,col2)'=>"FK|CONSTRAINT fk FOREIGN KEY (col) REFERENCES tab(pk)"));
    $arr = explode(NL, $str);
    foreach ($arr as $v) main_add_txt(trim($v));
    if ($SIDU['eng'] == 'mysql') main_add_txt("2|0|enum(Y,N)|enum('Y','N') NOT NULL DEFAULT 'Y',\n");
    echo '</p>', cms_form('end');
}
function main_add_txt($str = '') {
    if (!$str) return;
    $arr = explode('|', $str, 4); // 0=br br 1=red 2=txt 3=sql
    if ($arr[0] == '0') echo ' ';
    elseif ($arr[0] == '1') echo '<br>';
    elseif ($arr[0] == '2') echo '<br><br>';
    echo '<u'. ($arr[1] ? ' class="red"' : '') .' data-txt=" '. $arr[3] . ($arr[0] ? '' : ','. NL) .'">'. $arr[2] .'</u>';
}
function save_data(&$SIDU) {
    if (!$SIDU['data']['sql'] || !$SIDU['data']['cmd']) {
        $SIDU['data']['sql'] = $SIDU['data']['sql'] ?: 'CREATE TABLE '. (
            $SIDU['eng'] == 'mysql' ? sidu_keyw($SIDU[1]).'.' : ($SIDU['eng'] == 'pgsql' ? sidu_keyw($SIDU[2]).'.' : '')
            ) .'tabname('. NL .'id int'. ($SIDU['eng'] == 'sqlite' ? '' : ' NOT NULL DEFAULT 0') .' PRIMARY KEY,'. NL . NL .')';
        return;
    }
    $sql = trim($SIDU['data']['sql']);
    if (substr($sql, -1) == ')') {
        $sql = trim(substr($sql, 0, -1));
        if (substr($sql, -1) == ',') $sql = substr($sql, 0, -1);
        $sql .= ')';
    }
    $res = $SIDU['dbL']->query($sql);
    $err = sidu_err(1);
    if ($err) return $err;
    echo cms_html_js('', 'self.close()');
}
