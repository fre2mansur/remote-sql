<?php include 'inc.page.php'; head($SIDU);?>

<p><b>SQL Easy Templates</b></p>
<p>MySQL:<br>EXPLAIN SELECT * FROM tab<br>KILL process-ID</p>
<p>PostgreSQL:<br>EXPLAIN ANALYZE SELECT * FROM tab<br>SELECT pg_cancel_backend(procpid or PID after pg9.2)</p>

<p>SET PASSWORD = password('new-pass')
<br>SET PASSWORD for user@localhost = password('new-pass')</p>
<p>[my]SELECT inet_aton('127.0.0.1') == [pg]inetmi('127.0.0.1', '0.0.0.0')
<br>[my]inet_ntoa() == [pg]'0.0.0.0'::inet+int
<br>SELECT substring_index('sidu@topnew.net', '@', 1)</p>
<p>It's a good idea to have primary key in each table
<br>It's a good idea to have int col ahead, and blob col at end
<br>SIDU will sort first col if no sort found by default</p>

<p>sudo apt-get install apache2 php5 libapache2-mod-php5
<br>sudo apt-get install mysql-server mysql-client php5-mysql
<br>sudo apt-get install php5-gd</p>

<p>sudo apt-get install postgresql
<br>sudo apt-get install php5-pgsql
<br>sudo -u postgres psql ## login via cmd
<br>\password postgres ## change password</p>

<p>sudo /etc/init.d/postgresql restart
<br>sudo /etc/init.d/apache2 restart</p>

<p>www.mysql.com<br>www.postgresql.org<br>www.sqlite.org<br>www.cubrid.org</p>

<pre>CREATE TABLE sidu_fk (
  tab varchar(80) NOT NULL,
  col varchar(80) NOT NULL,
  ref_tab varchar(80) NOT NULL,
  ref_cols varchar(255) NOT NULL,
  where_sort varchar(255),
  PRIMARY KEY (tab,col)
);//you need refresh window after sidu_fk table is created

CREATE TABLE sidu_log(
  id int NOT NULL auto_increment PRIMARY KEY,
  ts timestamp NOT NULL default now(),
  ms int NOT NULL default 0,
  typ enum('B', 'S', 'E', 'D'),
  txt text,
  info text
);//this is the table to save history log</pre>

<p><br>$ mysql -u root -p dbname < import.sql
<br>$ mysqldump -u root -p dbname --ignore-table=dbname.tab1 --ignore-table=dbname.tab2 > export.sql
<br>$ mysqldump -u root -p dbname tabname1 tabname2 > export-two-table.sql
<br>$ mysqlimport -uroot -p dbname --ignore-lines=1 --lines-terminated-by='\n' --fields-terminated-by=',' --fields-enclosed-by='"' --verbose --local tabname.csv</p>

<p><b>/*SIDU_SQL1*/</b><br>at first line of selected SQL, it will treat the whole selected text as one SQL regardless how many line breaks</p>
<p><b>/*SIDU_CSV*/</b><br>at first line of selected SQL, it will parse CSV into table data</p>
<p><b>/*SIDU_JSON*/</b><br>at first line of selected SQL, it will parse JSON into table data</p>

<?php
$sql = 'sql.php?id='. $SIDU[0] . '&#38;sql=';
if ($SIDU['eng'] == 'mysql' || $SIDU['eng'] == 'pgsql') {
    echo '<p><a href="'. $sql .'show process">PROCESSLIST</a> (check Temp howto kill)</p>';
}
if ($SIDU['eng'] == 'mysql') {
    echo NL .'<p>SHOW: ';
    $arr = array('STATUS', 'GRANTS', 'VARIABLES');
    foreach ($arr as $v) echo ' <a href="'. $sql .'SHOW '. $v .'">'. $v .'</a>;';
    echo NL .'<br>FLUSH: ';
    $arr = array('ALL', 'LOGS', 'HOSTS', 'PRIVILEGES', 'TABLES', 'STATUS', 'DES_KEY_FILE', 'QUERY CACHE', 'USER_RESOURCES', 'TABLES WITH READ LOCK');
    foreach ($arr as $v) echo ' <a href="'. $sql .'FLUSH '. $v .'">'. $v .'</a>;';
    echo '</p>';
}
echo NL .'<p class="box hand show" data-src="next" title="', lang(2110) ,'"><b>', lang(2112) ,' (Fn):</b> FF|Chrome (Alt+Shift+', lang(2113) ,') IE (Alt+', lang(2114) ,') Opera (Shift+Esc) IOS Chrome (Ctrl+Alt+', lang(2113) ,')</p>';
echo NL .'<pre class="hide">» http://en.wikipedia.org/wiki/Access_key'. NL . NL . lang(2115) . NL . NL .'</pre>';

foot($SIDU);
