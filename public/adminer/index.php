<?php

/** Adminer - Compact database management
* @link https://www.adminer.org/
* @author Jakub Vrana, https://www.vrana.cz/
* @copyright 2007 Jakub Vrana
* @license https://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
* @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2 (one or other)
* @version 6.0.0
*/namespace
Adminer;const
VERSION="6.0.0";error_reporting(24575);set_error_handler(function($Ac,$Cc){return!!preg_match('~^Undefined (array key|offset|index)~',$Cc);},E_WARNING|E_NOTICE);$ad=!preg_match('~^(unsafe_raw)?$~',ini_get("filter.default"));if($ad||ini_get("filter.default_flags")){foreach(array('_GET','_POST','_COOKIE','_SERVER')as$X){$hj=filter_input_array(constant("INPUT$X"),FILTER_UNSAFE_RAW);if($hj)$$X=$hj;}}if(function_exists("mb_internal_encoding"))mb_internal_encoding("8bit");function
connection($f=null){return($f?:Db::$instance);}function
adminer(){return
Adminer::$instance;}function
driver(){return
Driver::$instance;}function
connect(){$xb=adminer()->credentials();$K=Driver::connect($xb[0],$xb[1],$xb[2]);return(is_object($K)?$K:null);}function
idf_unescape($u){if(!preg_match('~^[`\'"[]~',$u))return$u;$Ee=substr($u,-1);return
str_replace($Ee.$Ee,$Ee,substr($u,1,-1));}function
q($Q){return
connection()->quote($Q);}function
idx($ua,$y,$i=null){return($ua&&array_key_exists($y,$ua)?$ua[$y]:$i);}function
number($X){return
preg_replace('~[^0-9]+~','',$X);}function
int_type(){return'(tiny|small|medium|big)?int(eger|\d)?';}function
number_type(){return'(^('.int_type().'|decimal|numeric|real|(binary_|half_|scaled_)?float\d?|(binary_)?double( precision)?|(small)?money)$)';}function
remove_slashes(array$yj,$ad=false){$K=array();foreach($yj
as$y=>$X)$K[stripslashes($y)]=(is_array($X)?remove_slashes($X,$ad):($ad?$X:stripslashes($X)));return$K;}function
bracket_escape($u,$Ba=false){static$Ri=array(':'=>':1',']'=>':2','['=>':3','"'=>':4','='=>':5');return
strtr($u,($Ba?array_flip($Ri):$Ri));}function
url_escape($Q){static$Ri=array();if(!$Ri){$Ri=array(' '=>'+');foreach(str_split("\"'<>#%&+=?".ini_get("arg_separator.input"))as$Oa)$Ri[$Oa]=sprintf('%%%02X',ord($Oa));for($s=0;$s<256;$s++){if($s<32||$s>126)$Ri[chr($s)]=sprintf('%%%02X',$s);}}return
strtr((string)$Q,$Ri);}function
min_version($Aj,$Xe="",$f=null){$f=connection($f);$Kh=$f->server_info;if($Xe&&preg_match('~([\d.]+)-MariaDB~',$Kh,$B)){$Kh=$B[1];$Aj=$Xe;}return$Aj&&version_compare($Kh,$Aj)>=0;}function
charset(Db$lb){return(min_version("5.5.3",0,$lb)?"utf8mb4":"utf8");}function
ini_set($bg,$Y){return(function_exists('ini_set')?\ini_set($bg,$Y):false);}function
ini_bool($ge){$X=ini_get($ge);return(preg_match('~^(on|true|yes)$~i',$X)||(int)$X);}function
ini_bytes($ge){$X=ini_get($ge);switch(strtolower(substr($X,-1))){case'g':$X=(int)$X*1024;case'm':$X=(int)$X*1024;case'k':$X=(int)$X*1024;}return$X;}function
max_input_vars($L,$pg){$af=(int)ini_get("max_input_vars");return($af?(int)floor(($af-$pg)/$L):0);}function
max_input_vars_error(){$ge="max_input_vars";return
lang(0,"<b>$ge = ".ini_get($ge)."</b>");}function
sid(){static$K;if($K===null)$K=(SID&&!($_COOKIE&&ini_bool("session.use_cookies")));return$K;}function
set_password($_j,$O,$V,$Eg){$_SESSION["pwds"][$_j][$O][$V]=($_COOKIE["adminer_key"]&&is_string($Eg)?array(encrypt_string($Eg,$_COOKIE["adminer_key"])):$Eg);}function
get_password(){$K=get_session("pwds");if(is_array($K))$K=($_COOKIE["adminer_key"]?decrypt_string($K[0],$_COOKIE["adminer_key"]):false);return$K;}function
get_val($I,$k=0,$kb=null){$kb=connection($kb);$J=$kb->query($I);if(!is_object($J))return
false;$L=$J->fetch_row();return($L?$L[$k]:false);}function
get_vals($I,$d=0){$K=array();$J=connection()->query($I);if(is_object($J)){while($L=$J->fetch_row())$K[]=$L[$d];}return$K;}function
get_key_vals($I,$f=null,$Nh=true){$f=connection($f);$K=array();$J=$f->query($I);if(is_object($J)){while($L=$J->fetch_row()){if($Nh)$K[$L[0]]=$L[1];else$K[]=$L[0];}}return$K;}function
get_rows($I,$f=null,$j="<p class='error'>"){$kb=connection($f);$K=array();$J=$kb->query($I);if(is_object($J)){while($L=$J->fetch_assoc())$K[]=$L;}elseif(!$J&&!$f&&$j&&(defined('Adminer\PAGE_HEADER')||$j=="-- "))echo$j.error()."\n";return$K;}function
unique_array($L,array$w){foreach($w
as$v){if(preg_match("~^(PRIMARY|UNIQUE)$~",$v["type"])&&!$v["partial"]){$K=array();foreach($v["columns"]as$y){if(!isset($L[$y]))continue
2;$K[$y]=$L[$y];}return$K;}}}function
escape_key($y){if(preg_match('(^([\w(]+)('.str_replace("_",".*",preg_quote(idf_escape("_"))).')([ \w)]+)$)',$y,$B))return$B[1].idf_escape(idf_unescape($B[2])).$B[3];return
idf_escape($y);}function
where(array$Z,array$l=array()){$K=array();foreach((array)$Z["where"]as$y=>$X){$y=bracket_escape($y,true);$d=escape_key($y);$k=idx($l,$y,array());$Vc=$k["type"];$pe=$k&&(is_blob($k)||preg_match('~binary~',$Vc));$K[]=$d.($pe&&!is_utf8($X)?" = ".driver()->quoteBinary($X):(JUSH=="sql"&&$Vc=="json"?" = CAST(".q($X)." AS JSON)":(JUSH=="pgsql"&&preg_match('~^jsonb?$~',$k["full_type"])?"::jsonb = ".q($X)."::jsonb":(JUSH=="sql"&&is_numeric($X)&&preg_match('~\.~',$X)?" LIKE ".q($X):(JUSH=="mssql"&&strpos($Vc,"datetime")===false?" LIKE ".q(preg_replace('~[_%[]~','[\0]',$X)):" = ".unconvert_field($k,q($X)))))));if(JUSH=="sql"&&preg_match('~char|text~',$Vc)&&preg_match("~[^ -@]~",$X))$K[]="$d = ".q($X)." COLLATE ".charset(connection())."_bin";}foreach((array)$Z["null"]as$y)$K[]=escape_key($y)." IS NULL";return
implode(" AND ",$K);}function
where_columns(array$l){$K=array();foreach((array)$_GET["null"]as$y)$K[$y]=true;foreach((array)$_GET["where"]as$y=>$X){$y=bracket_escape($y,true);foreach($l
as$D=>$k){if($y==$D||strpos($y,idf_escape($D))!==false)$K[$D]=true;}}return$K;}function
where_check($X,array$l=array()){parse_str($X,$Pa);remove_slashes(array(&$Pa));return
where($Pa,$l);}function
where_link($s,$d,$Y,$Yf="="){$Vf=($Y!==null?$Yf:"IS NULL");return"&where[$s][col]=".url_escape($d).($Vf!=first(adminer()->operators())?"&where[$s][op]=".url_escape($Vf):"")."&where[$s][val]=".url_escape($Y);}function
convert_fields(array$e,array$l,array$N=array()){$K="";foreach($e
as$y=>$X){if($N&&!in_array(idf_escape($y),$N))continue;$va=convert_field($l[$y]);if($va)$K
.=", $va AS ".idf_escape($y);}return$K;}function
cookie_path(){return
strtr(preg_replace('~\?.*~','',$_SERVER["REQUEST_URI"]),array(";"=>"%3B",","=>"%2C"));}function
cookie($D,$Y,$Pe=2592000){header("Set-Cookie: $D=".rawurlencode($Y).($Pe?"; expires=".gmdate("D, d M Y H:i:s",time()+$Pe)." GMT":"")."; path=".cookie_path().(HTTPS?"; secure":"").($D=="adminer_import"?"":"; HttpOnly")."; SameSite=lax",false);}function
get_url($oj,$pb){$http_response_header=null;$Bc=array();set_error_handler(function($Ac,$j)use(&$Bc){$Bc[]=preg_replace('~^file_get_contents\([^)]*\):\s*~','',$j);return
true;});$K=file_get_contents($oj,false,$pb);restore_error_handler();$Fd=(function_exists('http_get_last_response_headers')?http_get_last_response_headers():$http_response_header);return
array($K,(preg_match('~^HTTP/[\d.]+ (\d+)~',idx($Fd,0,''),$B)?$B[1]:''),(array)$Fd,($K===false?implode("\n",$Bc):''),);}function
get_settings($sb){parse_str($_COOKIE[$sb],$Oh);return$Oh;}function
get_setting($y,$sb="adminer_settings",$i=null){return
idx(get_settings($sb),$y,$i);}function
save_settings(array$Oh,$sb="adminer_settings"){$Y=http_build_query($Oh+get_settings($sb));cookie($sb,$Y);$_COOKIE[$sb]=$Y;}function
restart_session(){if(!ini_bool("session.use_cookies")&&(!function_exists('session_status')||session_status()==PHP_SESSION_NONE))session_start();}function
stop_session($gd=false){$rj=ini_bool("session.use_cookies");if(!$rj||$gd){session_write_close();if($rj&&ini_set("session.use_cookies",'0')===false)session_start();}}function&get_session($y){return$_SESSION[$y][DRIVER][SERVER][$_GET["username"]];}function
set_session($y,$X){$_SESSION[$y][DRIVER][SERVER][$_GET["username"]]=$X;}function
auth_url($_j,$O,$V,$h=null){$nj=remove_from_uri(implode("|",array_keys(SqlDriver::$drivers))."|username|ext|".($h!==null?"db|":"").($_j=='mssql'||$_j=='pgsql'?"":"ns|").session_name());preg_match('~([^?]*)\??(.*)~',$nj,$B);return"$B[1]?".(sid()?SID."&":"").($_GET["ext"]?"ext=".url_escape($_GET["ext"])."&":"").($_j!="server"||$O!=""?url_escape($_j)."=".url_escape($O)."&":"")."username=".url_escape($V).($h!=""?"&db=".url_escape($h):"").($B[2]?"&$B[2]":"");}function
is_ajax(){return($_SERVER["HTTP_X_REQUESTED_WITH"]=="XMLHttpRequest");}function
redirect($A,$C=null){if($C!==null){restart_session();$_SESSION["messages"][preg_replace('~^[^?]*~','',($A!==null?$A:$_SERVER["REQUEST_URI"]))][]=$C;}if($A!==null){if($A=="")$A=".";header("Location: $A");exit;}}function
query_redirect($I,$A,$C,$jh=true,$Hc=true,$Qc=false,$Ei=""){if($Hc){$di=microtime(true);$Qc=!connection()->query($I);$Ei=format_time($di);}$Yh=($I?adminer()->messageQuery($I,$Ei,$Qc):"");if($Qc){adminer()->error
.=error().$Yh.script("messagesPrint();")."<br>";return
false;}if($jh)redirect($A,$C.$Yh);return
true;}class
Queries{static$queries=array();static$start=0;}function
queries($I){if(!Queries::$start)Queries::$start=microtime(true);Queries::$queries[]=(driver()->delimiter!=';'?$I:(preg_match('~;$~',$I)?"DELIMITER ;;\n$I;\nDELIMITER ":$I).";");return
connection()->query($I);}function
apply_queries($I,array$T,$Dc='Adminer\table'){foreach($T
as$R){if(!queries("$I ".$Dc($R)))return
false;}return
true;}function
queries_redirect($A,$C,$jh){$eh=implode("\n",Queries::$queries);$Ei=format_time(Queries::$start);return
query_redirect($eh,$A,$C,$jh,false,!$jh,$Ei);}function
format_time($di){return
lang(1,max(0,microtime(true)-$di));}function
relative_uri(){return
preg_replace_callback('~^[^?]*~',function($B){return
str_replace(":","%3A",$B[0]);},preg_replace('~^[^?]*/([^?]*)~','\1',$_SERVER["REQUEST_URI"]));}function
remove_from_uri($vg=""){return
substr(preg_replace("~(?<=[?&])($vg".(SID?"":"|".session_name()).")=[^&]*&~",'',relative_uri()."&"),0,-1);}function
get_files($y,$Kb=false){$m=$_FILES[$y];if(!$m)return
null;foreach($m
as$y=>$X)$m[$y]=(array)$X;$K=array();foreach($m["error"]as$y=>$j){if($j)return$j;$D=$m["name"][$y];$Mi=$m["tmp_name"][$y];$nb=file_get_contents($Kb&&preg_match('~\.gz$~',$D)?"compress.zlib://$Mi":$Mi);if($Kb){$di=substr($nb,0,3);if(function_exists("iconv")&&preg_match("~^\xFE\xFF|^\xFF\xFE~",$di))$nb=iconv("utf-16","utf-8",$nb);elseif($di=="\xEF\xBB\xBF")$nb=substr($nb,3);}$K[]=array($D,$nb);}return$K;}function
get_file($y,$Kb=false,$Pb=""){$Zc=get_files($y,$Kb);if(!is_array($Zc))return$Zc;$K='';foreach($Zc
as$m){$nb=$m[1];$K
.=$nb;if($Pb)$K
.=(preg_match("($Pb\\s*\$)",$nb)?"":$Pb)."\n\n";}return$K;}function
upload_error($j){$if=($j==UPLOAD_ERR_INI_SIZE?ini_get("upload_max_filesize"):0);return($j?lang(2).($if?" ".lang(3,$if):""):lang(4));}function
repeat_pattern($Gg,$Le){return
str_repeat("$Gg{0,65535}",$Le/65535)."$Gg{0,".($Le%65535)."}";}function
is_utf8($X){return(preg_match('~~u',$X)&&!preg_match('~[\0-\x8\xB\xC\xE-\x1F]~',$X));}function
format_number($X){return
strtr(number_format($X,0,".",lang(5)),preg_split('~~u',lang(6),-1,PREG_SPLIT_NO_EMPTY));}function
format_status(array$S,$y){$X=idx($S,$y,'?');if(!is_numeric($X))return
h($X);if($X<0)return'?';$ra=($y=="Rows"&&(JUSH=="sqlite"||$S["Engine"]==(JUSH=="pgsql"?"table":"InnoDB")));return($ra?"~ ":"").format_number($X);}function
friendly_url($X){return
preg_replace('~\W~i','-',$X);}function
table_status1($R,$Rc=false){$K=table_status($R,$Rc);return($K?reset($K):array("Name"=>$R));}function
column_foreign_keys($R){$K=array();foreach(adminer()->foreignKeys($R)as$o){foreach($o["source"]as$X)$K[$X][]=$o;}return$K;}function
fields_from_edit(){$K=array();foreach((array)$_POST["field_keys"]as$y=>$X){if($X!=""){$X=bracket_escape($X);$_POST["function"][$X]=$_POST["field_funs"][$y];$_POST["fields"][$X]=$_POST["field_vals"][$y];}}foreach((array)$_POST["fields"]as$y=>$X){$D=bracket_escape($y,true);$K[$D]=array("field"=>$D,"full_type"=>"","type"=>"","privileges"=>array("insert"=>1,"update"=>1,"where"=>1,"order"=>1),"null"=>true,"auto_increment"=>($D==driver()->primary),);}return$K;}function
dump_headers($Qd,$Af=false){$K=adminer()->dumpHeaders($Qd,$Af);$rg=$_POST["output"];if($rg!="text"||$K=="tar"){$hb=($rg!="text"&&$rg!="file"&&preg_match('~^[0-9a-z]+$~',$rg)?".$rg":"");header("Content-Disposition: attachment; filename=".adminer()->dumpFilename($Qd).".$K$hb");}session_write_close();if(!ob_get_level())ob_start(null,4096);ob_flush();flush();return$K;}function
dump_csv(array$L){$aj=$_POST["format"]=="tsv";foreach($L
as$y=>$X){if(preg_match('~["\n]|^0[^.]|\.\d*0$|'.($aj?'\t':'[,;]|^$').'~',$X))$L[$y]='"'.str_replace('"','""',$X).'"';}echo
implode(($_POST["format"]=="csv"?",":($aj?"\t":";")),$L)."\r\n";}function
parse_csv($_b,$Jh){$K=array();preg_match_all('~(?>"[^"]*"|[^"\r\n]+)+~',$_b,$Ye);foreach($Ye[0]as$L){preg_match_all("~((?>\"[^\"]*\")+|[^$Jh]*)$Jh~",$L.$Jh,$Ze);$K[]=$Ze[1];}return$K;}function
csv_value($X){return(preg_match('~^".*"$~s',$X)?str_replace('""','"',substr($X,1,-1)):$X);}function
apply_sql_function($q,$d){return($q?($q=="unixepoch"?"DATETIME($d, '$q')":($q=="count distinct"?"COUNT(DISTINCT ":strtoupper("$q("))."$d)"):$d);}function
get_temp_dir(){return
ini_get("upload_tmp_dir")?:sys_get_temp_dir();}function
file_open_lock($n){if(is_link($n))return;$p=@fopen($n,"c+");if(!$p)return;@chmod($n,0660);if(!flock($p,LOCK_EX)){fclose($p);return;}return$p;}function
file_write_unlock($p,$Db){rewind($p);fwrite($p,$Db);ftruncate($p,strlen($Db));file_unlock($p);}function
file_unlock($p){flock($p,LOCK_UN);fclose($p);}function
first(array$ua){return
reset($ua);}function
password_file($vb){$n=get_temp_dir()."/adminer.key";if(!$vb&&!file_exists($n))return'';$p=file_open_lock($n);if(!$p)return'';$K=stream_get_contents($p);if(!$K){$K=rand_string();file_write_unlock($p,$K);}else
file_unlock($p);return$K;}function
rand_string(){return(function_exists('random_bytes')?bin2hex(random_bytes(16)):md5(uniqid(strval(mt_rand()),true)));}function
select_value($X,$_,array$k,$Di){if(is_array($X)){$K="";if(array_filter($X,'is_array')==array_values($X)){$ze=array();foreach($X
as$W)$ze+=array_fill_keys(array_keys($W),null);foreach(array_keys($ze)as$xe)$K
.="<th>".h($xe);foreach($X
as$W){$K
.="<tr>";foreach(array_merge($ze,$W)as$vj)$K
.="<td>".select_value($vj,$_,$k,$Di);}}else{foreach($X
as$xe=>$W)$K
.="<tr>".($X!=array_values($X)?"<th>".h($xe):"")."<td>".select_value($W,$_,$k,$Di);}return"<table>$K</table>";}if(!$_)$_=adminer()->selectLink($X,$k);if($_===null){if(is_mail($X))$_="mailto:$X";if(is_url($X))$_=$X;}$X=driver()->value($X,$k);$K=adminer()->editVal($X,$k);if($K!==null){if(!is_utf8($K))$K="\0";elseif($Di!=""&&is_shortable($k))$K=shorten_utf8($K,max(0,+$Di));else$K=h($K);}return
adminer()->selectVal($K,$_,$k,$X);}function
is_blob(array$k){return
preg_match('~blob|bytea|raw|file'.(JUSH=="mssql"?'|binary|image':'').'~',$k["type"])&&!in_array($k["type"],idx(driver()->structuredTypes(),lang(7),array()));}function
is_mail($sc){$xa='[-a-z0-9!#$%&\'*+/=?^_`{|}~]';$fc='[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])';$Gg="$xa+(\\.$xa+)*@($fc?\\.)+$fc";return
is_string($sc)&&preg_match("(^$Gg(,\\s*$Gg)*\$)i",$sc);}function
is_url($Q){$fc='[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])';return
preg_match("~^((https?):)?//($fc?\\.)+$fc(:\\d+)?(/.*)?(\\?.*)?(#.*)?\$~i",$Q);}function
is_shortable(array$k){return!preg_match('~'.number_type().'|date|time|year~',$k["type"]);}function
host_port($O){return(preg_match('~^(:([^:].*)|(\[(.+)\]|(([^:]+://)?[^:]+))(:(\d+))?)$~',$O,$B)?array($B[4].$B[5],$B[2].$B[8]):array($O,''));}function
count_rows($R,array$Z,$qe,array$r){$I=" FROM ".table($R).($Z?" WHERE ".implode(" AND ",$Z):"");return($qe&&(JUSH=="sql"||count($r)==1)?"SELECT COUNT(DISTINCT ".implode(", ",$r).")$I":"SELECT COUNT(*)".($qe?" FROM (SELECT 1$I GROUP BY ".implode(", ",$r).") x":$I));}function
slow_query($I){$h=adminer()->database();$Fi=adminer()->queryTimeout();$Sh=driver()->slowQuery($I,$Fi);$f=null;if(!$Sh&&support("kill")){$f=connect();if($f&&($h==""||$f->select_db($h))){$_e=get_val(connection_id(),0,$f);echo
script("const timeout = setTimeout(() => { ajax('".js_escape(ME)."script=kill', function () {}, 'kill=$_e&token=".get_token()."'); }, 1000 * $Fi);");}}ob_flush();flush();$K=@get_key_vals(($Sh?:$I),$f,false);if($f){echo
script("clearTimeout(timeout);");ob_flush();flush();}return$K;}function
get_token(){$hh=rand(1,1e6);return($hh^$_SESSION["token"]).":$hh";}function
verify_token(){list($Ni,$hh)=explode(":",$_POST["token"]);return($hh^$_SESSION["token"])==$Ni&&in_array($_SERVER["HTTP_SEC_FETCH_SITE"],array("","same-origin"));}function
compress_alphabet(){return
strtr(implode(range('"','~')),"'\\","!\n");}function
decompress_string($Q,$Vb=""){$oa=array_flip(str_split(compress_alphabet()));$Le=strlen($Q);$xj=($Le?13*($Le-1)/2-$oa[$Q[0]]:0);$c="";$uh=0;$vh=0;for($s=1;$s<$Le;$s+=2){$uh=($uh<<13)+$oa[$Q[$s]]*93+$oa[$Q[$s+1]];$vh+=13;while($vh>=8&&$xj>=8){$vh-=8;$xj-=8;$c
.=chr($uh>>$vh);$uh&=(1<<$vh)-1;}}if($c=="")return"";if($Vb!=""&&function_exists('inflate_init'))return
inflate_add(inflate_init(ZLIB_ENCODING_RAW,array('dictionary'=>$Vb)),$c,ZLIB_FINISH);return($Vb==""&&function_exists('gzinflate')?gzinflate($c):inflate($c,$Vb));}function
inflate($c,$Vb=""){$Me=array(3,4,5,6,7,8,9,10,11,13,15,17,19,23,27,31,35,43,51,59,67,83,99,115,131,163,195,227,258);$Ne=array(0,0,0,0,0,0,0,0,1,1,1,1,2,2,2,2,3,3,3,3,4,4,4,4,5,5,5,5,0);$Zb=array(1,2,3,4,5,7,9,13,17,25,33,49,65,97,129,193,257,385,513,769,1025,1537,2049,3073,4097,6145,8193,12289,16385,24577);$bc=array(0,0,0,0,1,1,2,2,3,3,4,4,5,5,6,6,7,7,8,8,9,9,10,10,11,11,12,12,13,13);$K=$Vb;$H=0;do{$bd=inflate_bits($c,$H,1);$U=inflate_bits($c,$H,2);if(!$U){$H=($H+7)&~7;$Le=inflate_bits($c,$H,16);$H+=16;$K
.=substr($c,$H>>3,$Le);$H+=$Le<<3;}else{if($U==1){$Te=array_merge(array_fill(0,144,8),array_fill(0,112,9),array_fill(0,24,7),array_fill(0,8,8));$cc=array_fill(0,30,5);}else{$Se=inflate_bits($c,$H,5)+257;$ac=inflate_bits($c,$H,5)+1;$F=array(16,17,18,0,8,7,9,6,10,5,11,4,12,3,13,2,14,1,15);$uf=array_fill(0,19,0);$tf=inflate_bits($c,$H,4)+4;for($s=0;$s<$tf;$s++)$uf[$F[$s]]=inflate_bits($c,$H,3);$vf=inflate_table($uf);$Oe=array();while(count($Oe)<$Se+$ac){$oi=inflate_symbol($c,$H,$vf);if($oi==16)$Oe=array_merge($Oe,array_fill(0,inflate_bits($c,$H,2)+3,end($Oe)));elseif($oi==17)$Oe=array_merge($Oe,array_fill(0,inflate_bits($c,$H,3)+3,0));elseif($oi==18)$Oe=array_merge($Oe,array_fill(0,inflate_bits($c,$H,7)+11,0));else$Oe[]=$oi;}$Te=array_slice($Oe,0,$Se);$cc=array_slice($Oe,$Se);}$Ue=inflate_table($Te);$ec=inflate_table($cc);while(($oi=inflate_symbol($c,$H,$Ue))!=256){if($oi<256)$K
.=chr($oi);else{$Le=$Me[$oi-257]+inflate_bits($c,$H,$Ne[$oi-257]);$dc=inflate_symbol($c,$H,$ec);$Qf=strlen($K)-$Zb[$dc]-inflate_bits($c,$H,$bc[$dc]);for($s=0;$s<$Le;$s++)$K
.=$K[$Qf+$s];}}}}while(!$bd);return($Vb==""?$K:substr($K,strlen($Vb)));}function
inflate_bits($c,&$H,$ub){$K=0;for($s=0;$s<$ub;$s++){$K+=((ord($c[$H>>3])>>($H&7))&1)<<$s;$H++;}return$K;}function
inflate_table(array$Oe){$R=array();$Xa=0;for($Ha=1;$Ha<=max($Oe);$Ha++){foreach($Oe
as$oi=>$Le){if($Le==$Ha){$R[$Ha][$Xa]=$oi;$Xa++;}}$Xa<<=1;}return$R;}function
inflate_symbol($c,&$H,array$R){$Xa=0;$Ha=0;do{$Xa=($Xa<<1)+inflate_bits($c,$H,1);$Ha++;}while(!isset($R[$Ha][$Xa]));return$R[$Ha][$Xa];}function
script($Wh,$Qi="\n"){return"<script".nonce().">$Wh</script>$Qi";}function
script_src($oj,$Nb=false){return"<script src='".h($oj)."'".nonce().($Nb?" defer":"")."></script>\n";}function
nonce(){return' nonce="'.get_nonce().'"';}function
on($Ec,$zd,$sa=null){$ta=array();foreach(array_slice(func_get_args(),2)as$X)$ta[]=json_encode($X,256);return" data-on$Ec='".str_replace(array('&','<',"'"),array('&amp;','&lt;','&#039;'),"$zd(".implode(", ",$ta).")")."'";}function
input_hidden($D,$Y=""){return"<input type='hidden' name='".h($D)."' value='".h($Y)."'>\n";}function
input_token(){return
input_hidden("token",get_token());}function
target_blank(){return' target="_blank" rel="noreferrer noopener"';}function
h($Q){return
str_replace(array('&','<','"',"'","\0"),array('&amp;','&lt;','&quot;','&#039;','&#0;'),$Q);}function
nl_br($Q){return
str_replace("\n","<br>",$Q);}function
checkbox($D,$Y,$Ra,$Ae="",$b="",$Wa="",$Ce=""){$K="<input type='checkbox' name='$D' value='".h($Y)."'".($Ra?" checked":"").($Ae==""&&$Wa?" class='$Wa'":"").($Ce?" aria-labelledby='$Ce'":"").$b.">";return($Ae!=""?"<label".($Wa?" class='$Wa'":"").">$K".h($Ae)."</label>":$K);}function
optionlist($cg,$Gh=null,$sj=false){$K="";foreach($cg
as$xe=>$W){$dg=array($xe=>$W);if(is_array($W)){$K
.='<optgroup label="'.h($xe).'">';$dg=$W;}foreach($dg
as$y=>$X)$K
.='<option'.($sj||is_string($y)?' value="'.h($y).'"':'').($Gh!==null&&($sj||is_string($y)?(string)$y:$X)===$Gh?' selected':'').'>'.h($X);if(is_array($W))$K
.='</optgroup>';}return$K;}function
html_select($D,array$cg,$Y="",$b="",$Ce=""){static$Ae=0;$Be="";if(!$Ce&&substr($cg[""],0,1)=="("){$Ae++;$Ce="label-$Ae";$Be="<option value='' id='$Ce'>".h($cg[""]);unset($cg[""]);}return"<select name='".h($D)."'".($Ce?" aria-labelledby='$Ce'":"")."$b>".$Be.optionlist($cg,$Y)."</select>";}function
html_radios($D,array$cg,$Y="",$Jh=""){$K="";foreach($cg
as$y=>$X)$K
.="<label><input type='radio' name='".h($D)."' value='".h($y)."'".($y==$Y?" checked":"").">".h($X)."</label>$Jh";return$K;}function
confirm($C=""){return
on('click','confirmClick',$C?:lang(8));}function
print_fieldset($t,$Ke,$Dj=false){echo"<fieldset><legend>","<a href='#fieldset-$t' class='toggle'>$Ke</a>","</legend>","<div id='fieldset-$t'".($Dj?"":" class='hidden'").">\n";}function
bold($Ja,$Wa=""){return($Ja?" class='active $Wa'":($Wa?" class='$Wa'":""));}function
js_escape($Q){return
str_replace("<","\\x3C",addcslashes($Q,"\r\n'\\"));}function
js_escape_re($Q){return
addcslashes(preg_quote($Q,"/"),"\r\n");}function
pagination_href($G){return
remove_from_uri("page|next").($G?"&page=$G".($_GET["next"]!=""?"&next=".url_escape($_GET["next"]):""):"");}function
pagination($G,$Ab){return" ".($G==$Ab?($G?"<b>".($G+1)."</b>":$G+1):'<a href="'.h(pagination_href($G)).'">'.($G+1)."</a>");}function
hidden_fields(array$bh,array$Td=array(),$Ug=''){$K=false;foreach($bh
as$y=>$X){if(!in_array($y,$Td)){if(is_array($X))hidden_fields($X,array(),$y);else{$K=true;echo
input_hidden(($Ug?$Ug."[$y]":$y),$X);}}}return$K;}function
hidden_fields_get(){echo(sid()?input_hidden(session_name(),session_id()):''),($_GET["ext"]?input_hidden("ext",$_GET["ext"]):""),(isset($_GET[DRIVER])?input_hidden(DRIVER,SERVER):""),input_hidden("username",$_GET["username"]);}function
file_input($b,$uh=""){$cf="max_file_uploads";$df=ini_get($cf);$if="upload_max_filesize";$jf=ini_bytes($if);$Rg=ini_bytes("post_max_size");if($Rg&&$Rg<$jf){$if="post_max_size";$jf=$Rg;}$kf=ini_get($if);return(ini_bool("file_uploads")?"<input type='file'$b".on('change','fileChange',(int)$df,lang(9,"$cf = $df"),$jf,lang(9,"$if = $kf")).">$uh":lang(10));}function
enum_input($U,$b,array$k,$Y,$vc=""){preg_match_all("~'((?:[^']|'')*)'~",$k["length"],$Ye);$Ug=($k["type"]=="enum"?"val-":"");$Ra=(is_array($Y)?in_array("null",$Y):$Y===null);$K=($k["null"]&&$Ug?"<label><input type='$U'$b value='null'".($Ra?" checked":"")."><i>$vc</i></label>":"");foreach($Ye[1]as$X){$X=stripcslashes(str_replace("''","'",$X));$Ra=(is_array($Y)?in_array($Ug.$X,$Y):$Y===$X);$K
.=" <label><input type='$U'$b value='".h($Ug.$X)."'".($Ra?' checked':'').'>'.h(adminer()->editVal($X,$k)).'</label>';}return$K;}function
input(array$k,$Y,$q,$_a=false,$lj=false){$D=h(bracket_escape($k["field"]));echo"<td class='function'>";if(is_array($Y)&&!$q)$q="json";$ue=($q=="json"||preg_match('~^jsonb?$~',$k["full_type"]));if($ue&&$Y!=''&&(JUSH!="pgsql"||$k["type"]!="json"))$Y=json_encode(is_array($Y)?$Y:json_decode($Y),128|64|256);$th=(JUSH=="mssql"&&$lj&&$k["auto_increment"]);if($th&&!$_POST["save"])$q=null;$rd=(isset($_GET["select"])||$th?array("orig"=>lang(11)):array())+adminer()->editFunctions($k);$_c=driver()->enumLength($k);if($_c){$k["type"]="enum";$k["length"]=$_c;}$b=" name='fields[$D]".($k["type"]=="enum"||$k["type"]=="set"?"[]":"")."'".($_a?" autofocus":"");echo
driver()->unconvertFunction($k)." ";$R=$_GET["edit"]?:$_GET["select"];if($k["type"]=="enum")echo
h($rd[""])."<td>".adminer()->editInput($R,$k,$b,$Y);else{$Ad=(in_array($q,$rd)||isset($rd[$q]));$cd=0;foreach($rd
as$y=>$X){if($y===""||!$X)break;$cd++;}echo(count($rd)>1?"<select name='function[$D]'".on('change','functionChange').on_help_value('^SQL$').">".optionlist($rd,$q===null||$Ad?$q:"")."</select>":h(reset($rd)))."<td".($cd&&count($rd)>1?on('input','skipOriginal',$cd):"").">";$ie=adminer()->editInput($R,$k,$b,$Y);if($ie!="")echo$ie;elseif(preg_match('~bool~',$k["type"]))echo"<input type='hidden'$b value='0'>"."<input type='checkbox'".(preg_match('~^(1|t|true|y|yes|on)$~i',$Y)?" checked":"")."$b value='1'>";elseif($k["type"]=="set")echo
enum_input("checkbox",$b,$k,(is_string($Y)?explode(",",$Y):$Y));elseif(is_blob($k)&&ini_bool("file_uploads"))echo"<input type='file' name='fields-$D'>";elseif($ue)echo"<textarea$b cols='50' rows='12' class='jush-json'>".h($Y).'</textarea>';elseif(($Ci=preg_match('~text|lob|memo~i',$k["type"]))||preg_match("~\n~",$Y)){if($Ci&&JUSH!="sqlite")$b
.=" cols='50' rows='12'";else{$M=min(12,substr_count($Y,"\n")+1);$b
.=" cols='30' rows='$M'";}echo"<textarea$b>".h($Y).'</textarea>';}else{$cj=driver()->types();$lf=(!preg_match('~int~',$k["type"])&&preg_match('~^(\d+)(,(\d+))?$~',$k["length"],$B)?((preg_match("~binary~",$k["type"])?2:1)*$B[1]+($B[3]?1:0)+($B[2]&&!$k["unsigned"]?1:0)):($cj[$k["type"]]?$cj[$k["type"]]+($k["unsigned"]?0:1):0));if(JUSH=='sql'&&min_version(5.6)&&preg_match('~time~',$k["type"]))$lf+=7;echo"<input".((!$Ad||$q==="")&&preg_match('~^'.int_type().'$~',$k["type"])&&!preg_match('~\[]~',$k["full_type"])?" type='number'":"")." value='".h($Y)."'".($lf?" data-maxlength='$lf'":"").(preg_match('~char|binary~',$k["type"])&&$lf>20?" size='".($lf>99?60:40)."'":"")."$b>";}echo
adminer()->editHint($R,$k,$Y),(count($rd)>1?script("fire(qs('select', qsl('td').previousSibling), 'change');",""):"");}}function
process_input(array$k){$u=bracket_escape($k["field"]);$q=idx($_POST["function"],$u);if($q=="orig")return(preg_match('~^CURRENT_TIMESTAMP~i',$k["on_update"])?idf_escape($k["field"]):false);if($q=="NULL")return"NULL";if(is_blob($k)&&ini_bool("file_uploads")){$m=get_file("fields-$u");if(!is_string($m))return
false;return
driver()->quoteBinary($m);}$Y=idx($_POST["fields"],$u);if($Y===null)return
false;if($k["type"]=="enum"||driver()->enumLength($k)){$Y=idx($Y,0);if($Y=="orig"||!$Y)return
false;if($Y=="null")return"NULL";$Y=substr($Y,4);}if($k["auto_increment"]&&$Y=="")return
null;if($k["type"]=="set")$Y=implode(",",(array)$Y);if($q=="json"){$Y=json_decode($Y,true);if(!is_array($Y))return
false;return$Y;}return
adminer()->processInput($k,$Y,$q);}function
search_tables(){$_GET["where"][0]["val"]=$_POST["query"];$Ih="<ul>\n";foreach(table_status('',true)as$R=>$S){$D=adminer()->tableName($S);if(isset($S["Engine"])&&$D!=""&&(!$_POST["tables"]||in_array($R,$_POST["tables"]))){$J=connection()->query("SELECT".limit("1 FROM ".table($R)," WHERE ".implode(" AND ",adminer()->selectSearchProcess(fields($R),array())),1));if(!$J||$J->fetch_row()){$Yg="<a href='".h(ME."select=".url_escape($R)."&where[0][op]=".url_escape($_GET["where"][0]["op"])."&where[0][val]=".url_escape($_GET["where"][0]["val"]))."'>$D</a>";echo"$Ih<li>".($J?$Yg:"<p class='error'>$Yg: ".error())."\n";$Ih="";}}}echo($Ih?"<p class='message'>".lang(12):"</ul>")."\n";}function
on_help($Ci,$Qh=0){return
on('mouseover','helpMouseover',$Ci,$Qh).on('mouseout','helpMouseout');}function
on_help_value($qh="",$sh=""){return
on('mouseover','helpValueMouseover',$qh,$sh).on('mouseout','helpMouseout');}function
edit_form($R,array$l,$L,$lj,$j=''){$ri=adminer()->tableName(table_status1($R,true));page_header(($lj?lang(13):lang(14)),$j,array("select"=>array($R,$ri)),$ri);adminer()->editRowPrint($R,$l,$L,$lj);if($L===false){echo"<p class='error'>".lang(15)."\n";return;}echo"<form action='' method='post' enctype='multipart/form-data' id='form'>\n";$qc=false;$Jj=($lj&&!isset($_GET["select"])?where_columns($l):array());$qb=(count($Jj)!=count($l));if(!$qb)$Jj=array();if(!$l)echo"<p class='error'>".lang(16)."\n";else{echo"<table class='layout nowrap'".on('keydown','editingKeydown').">\n";$_a=!$_POST;foreach($l
as$D=>$k){echo"<tr".($Jj[$D]?on('change','whereChange'):"")."><th>".adminer()->fieldName($k);$i=idx($_GET["set"],bracket_escape($D));if($i===null){$i=$k["default"];if($k["type"]=="bit"&&preg_match("~^b'([01]*)'\$~",$i,$rh))$i=$rh[1];if(JUSH=="sql"&&preg_match('~binary~',$k["type"]))$i=bin2hex($i);}$Y=($L!==null?($L[$D]!=""&&JUSH=="sql"&&preg_match("~enum|set~",$k["type"])&&is_array($L[$D])?implode(",",$L[$D]):(is_bool($L[$D])?+$L[$D]:$L[$D])):(!$lj&&$k["auto_increment"]?"":(isset($_GET["select"])?false:$i)));if(!$_POST["save"]&&is_string($Y))$Y=adminer()->editVal($Y,$k);if(($lj&&!isset($k["privileges"]["update"]))||$k["generated"])echo"<td class='function'><td>".select_value($Y,'',$k,null);else{$qc=true;$q=($_POST["save"]?idx($_POST["function"],bracket_escape($D),""):($lj&&preg_match('~^CURRENT_TIMESTAMP~i',$k["on_update"])?"now":($Y===false?null:($Y!==null?'':'NULL'))));if(!$_POST&&!$lj&&$Y==$k["default"]&&preg_match('~^[\w.]+\(~',$Y))$q="SQL";if(preg_match("~time~",$k["type"])&&preg_match('~^CURRENT_TIMESTAMP~i',$Y)){$Y="";$q="now";}if($k["type"]=="uuid"&&$Y=="uuid()"){$Y="";$q="uuid";}if($_a!==false)$_a=($k["auto_increment"]||$q=="now"||$q=="uuid"?null:true);input($k,$Y,$q,$_a,$lj);if($_a)$_a=false;}}if(!fields($R)&&driver()->primary!="")echo"<tr>"."<th><input name='field_keys[]'".on('input','fieldChange').">"."<td class='function'>".html_select("field_funs[]",adminer()->editFunctions(array("null"=>isset($_GET["select"]))))."<td><input name='field_vals[]'>";echo"</table>\n";}echo"<p>\n";if($qc){echo"<input type='submit' value='".lang(17)."'>\n";if(!isset($_GET["select"])&&$qb){$Wb=($Jj&&($j!=""||adminer()->error!="")?" disabled":"");echo"<input type='submit' name='insert' value='".($lj?lang(18):lang(19))."' title='Ctrl+Shift+Enter'$Wb".($lj?on('click','ajaxForm',lang(20)):"").">\n";}}echo($lj?"<input type='submit' name='delete' value='".lang(21)."'".confirm().">\n":"");if(isset($_GET["select"]))hidden_fields(array("check"=>(array)$_POST["check"],"clone"=>$_POST["clone"],"all"=>$_POST["all"]));echo
input_hidden("referer",(isset($_POST["referer"])?$_POST["referer"]:$_SERVER["HTTP_REFERER"])),input_hidden("save",1),input_token(),"</form>\n";}function
shorten_utf8($Q,$Le=80,$ki=""){if(!preg_match("(^(".repeat_pattern("[\t\r\n -\x{10FFFF}]",$Le).")($)?)u",$Q,$B))preg_match("(^(".repeat_pattern("[\t\r\n -~]",$Le).")($)?)",$Q,$B);return
h($B[1]).$ki.(isset($B[2])?"":"<i>…</i>");}function
icon($Pd,$D,$Od,$Hi,$b=""){return"<button ".($D?"type='submit' name='$D'":"draggable='true' tabindex='-1'")." title='".h($Hi)."' class='icon icon-$Pd".($D?"":" jsonly")."'$b><span>$Od</span></button>";}function
copy_icon(){$tb=lang(22);return"<a href='' class='jsonly icon-copy' title='$tb'><span>$tb</span></a>";}if(isset($_GET["file"])){if(substr(VERSION,-4)!='-dev'){if($_SERVER["HTTP_IF_MODIFIED_SINCE"]){header("HTTP/1.1 304 Not Modified");exit;}header("Expires: ".gmdate("D, d M Y H:i:s",time()+365*24*60*60)." GMT");header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");header("Cache-Control: immutable");}ini_set("zlib.output_compression",'1');if($_GET["file"]=="default.css"){header("Content-Type: text/css; charset=utf-8");echo
decompress_string('!c0=@iDZ*tV?H*{U)[Q;B/1SR=Dh9&hJv;rrHHN,.V&KGmzhDwb9E:tfItN#CwUSwX?Xyeqi5d/N>]A"1lTaK
Tx^G#)>.UM~&(MUO{shFwKG+g4,>C*S:
f1hRcL)KhkmZFtH^qWCMBf7tZ{.#f{8V6<
#Nk9.jSA&0km
lxTc6$tVXF.+.*cJeW<wG~51NPIP4xT,`5Fw(3!{(~-,9<s}YqWT+L%^[i[s<&8ErH[O8<a)
ljb
$LurL4t]W%a>H/b
X/{EMCz:LXX((.yD>6A0+]t%ACU_
:"Bp%c=`r4T.#6G1(p
xo=TMNIiX,W0G-OEkD}^/L"3iRuM0)KZQ^aWB9dsO%0WmcO<LgliJIDSwKw0uo4(Piokl7g)}Qq_R"C>
^,?D183n.@41e}1M3L@&rCG$;yG3^fAu1qCeb_`V5R)ywQ+^^}Y?,S-#YZFZG
*@I%I_vxm,Tu:<aGT4wdZr#t8h]Nq~_-mA_aP)C2W
3#$o
g`gA/T"apmp;"31><i"",jWq9Wx4|Kj$:Svf`fH`|l`L/=wn!GzOm+(2zYb@S?I6~Dgg51]s$GQ<f%*sZ)4os*u%H<]daIUU7+nOS>!R,?jI-ZyOTT8YA+<ro/FX
5%v%]D1&UG`Rk{"Wc.*PH+X"!Vb@SA=T#6)+N_
VgZ:[vm-?:-d-#LVMbB`M*
o3=!8PG}PV45(W`#.!4Aj#=
`|=e];={gdf>3&l{-kM.$C*+s{3":?S*Zv4|Rl!*UYvBXH@}(A,#om09^h1i;#LHmj2,KUT]s;#mi|*91KjF]nE
u?>^sG`oFK
)Wofomi0<!n"hdYaSs6[44(o8rHBG_@1V2u@D_*jz/#ZgKg<,ob6)a>B~0
Nc9PJ]bx=7K{0`!<w~"{8gg&A2+L#$C,xw#&#5qLhH:Y
6oD1wS)Hu:z&]%$L:*RH&&hm9*p.)J&x-8E0z+soB4Y.o:5!`DtOyw7783CWgj
WZ%`ELhCb!<9!`!t;k@5]}^L$~J|@.agt}?B1>
;"ZGyF-kRn"BIwMi;iFn0;f?!>s@V%wLZZ[kKwyDKfGko5=+|UjHeZjXy;0;#G@L"d`Um3u4Z@)WU.Kf:>6w?u|8l*.uRy`amgR$8Nv?MAbetW1fZC=.a/i!<lm+CgiuJdI)Ig2l@6xS*[@!@B.hXtesj)KZ`"D(QZyUo#,afykRAvt+#nz?,6c9u&`9kdt)X35?[Y<n!"C4r!0$AJl3>+#H(mk1pQn,Z3ZZ]8D)q@wst)_4|I5f}dsW#hqo*!.
4#"_/$:mCAq.5UCVL;oIlfE&U`w!f9m@e?)4t)~-8Kr.@Bm$9-|,R
ult.W=H4(dSM?+2D
gapxO[e_/=:kYP05Q|i+[N_N-YHIAe)0I*>{8]&Z?!aMCh.,oL@6p&lY$/
U=Fds9>*<FAc!>,5<A*;C+!_3@O6|?
//8+>*;@Um0hT[y8<Yt,@dvwiU()maH>967;d_]`={>5EWy&s32*#uINV)k5YG"ekF2}hI1O:Mj?8&AG/j[.-n!P5/("uWRm`3"j5%iI+qc5SJ+:9eOv83%i]U%[V*dHY/2lm8EP@h:*ITM4#//"X9KhV|EJ;q*De_$X_uTkg^D"0(-oU$AjZ^;N#1fw]3U1a`)mkv^ymmdQDDS;q71|/~(_`/BNq++E<jkdNVV@mh?.W_4M<w=_(ybU80Bn/@V^N!54"@
H!U(`":dm[rVdms6(S],j-batnN&O(^ru_<To+HJ~-NHu&
@=6dl$NMZ6.-yJ1jkQMe$lOAr{RGpt_56jj]YcYfGIIoQ"Gp(LKTcE%
(#A-Ss>OBN92I3S^/SZ[Y{fR5U]f3~"At`%-@82:PR%ue
?eN{]g1_DyuG)bqfX_Qs^{78KXKDK$X3a[Ecg5g;AJ4X-!D6[i7{=;"[e<PUxWs#8`
A
"RPK}9NBKH69/U1HuFO6us<2>Oj!}^0)84[gIeYd+TUi:!R?Fdgb1)D%@K@H-J^5m+l!YgY?3xK#mU+#Qm]0g"V)XSM0|uEUQFXQBk%K.*BURB
10iC:gT=p:fn<{1$Zp5Ovs@e;!HiB|gIW:-0v}QHRYkl1<T*o2TX81n2`o1uT)@|7jq%"/7@G)frLDr8H3rf_Na<86aPUN*MUbTee4EdGd"1t=NaEF
;iySU?Y;v.4>
RP2b=1]Ad#4rICDk=x"Z`z?~X!`9<#2>$)ds-gMK0Ux00NP!8ZqDXXp3GXgn;Q/Yh-#qdOQ)S}u]q?W-CFEV(7eTC:uKZPMYS5$i,I
2[0ov7,YH0pH6R`n,nr(8U[C-nTMc4~8NY~#n])3`!4/vklG
N%[#+;)i29O[fww}VbQ]rKi%y-ZW>Gfjs~p(*;U,"!Nb?-U]j|.+n]tcTM&fIT9Txd(Xod^%"{+[N9i2wzya_4MaMv!mcFuYxzK2uWypf-Yk^aYCxviP2qUT6}5(x~cMiq^HyEAUC"wnB}[#@KK)25n!nubey@KY7vpFug_hT>MRR-PyeDx`nbnx+Esrx8K7s3mrcwqM21p|blg?r41.s2Fe>OEZ`k/JpHne&Us&nDhatAWZv[y&$@`;Qn@WmZnmpc6]Y4TVtMp;4DQvyF0k8pZD=5bWwf
&@wXiEVG3L~r9x>f
DJv/ymXPyEB_ctnuw=s~Cw_nhh`{Ej14p4<A."no_E=r?V>
X/mcPhtkK
SHlA[=3)jxBjTN?Fe=K3mXc[JfhI)O+H?s4z>nCQ1zsK7lTpOp/EyNykw~PCe>j:YBo!af6g2s.elsKdH90yhlW|`Ib;pXh[h8+thI)*3^^VMelUX,D7=E%gNYB|!Ui(a%<e,qo(V~,OVWBQiQa{E~JjL.l)Z)s~SM<;1@H4S=U(RF;7uOW[""8dG;qK-cOo"FZYYl"dR%j%1)P33!1_jb2My9WJ$HcX;,>3z#p{Vl.-7WAo(snORM+[dimjqqHs6f=}g$mcji$bJZ[:XSHOb>Xmtx/<NJHc]$bOxnwttNq~T+A~i&.dMyF@w]c8_/6O8XCKwJyFbyBn[4e~Mb[,2oL?
nw.DpU|W%F+RddY/Zd.3$0W!sX9Lx^%b7@mO2x}SPPcu5gdBS;rHC:7^tfOQRKVQD&s)9oM`LZ_q;f]]#43gQkr=Z_L9p.?`P=j.yac@GbLQ^f<GGZ~]6kya;<8:F@hcNG,P!41@F)/:1Qq@&!L?]UN!3MG]s[~OAU`#nEbRUKQ*1(wpi7z-+cg_!Rbg$@^7a,TC#U_Is_vM"7L=&1(@d.AN*2H2hG(a<4JcT4QrvvmyR9>VCK0L;+;0K/BTI7@[lNzAgjG:)Y5`IIGxr!$lWV1aWii,3F!/{`s13+P
WmZ/+Rx>/5kfH^HZw96+`+Kg
d{skqlg_Z&_>xhPZN4XZ]LMvK_DQt{mZ>{EvrNTg9Ym"-?2=4,a]XVx$<gc$#&rc`0c@w"b
iJmT:GD~_
_*u!sl`,twN*82)AXsWx_7Chyn=14#77aNK<%RuEZMtV5bYfhw2RC,-2"(L,y<0T4qAY:ww{Z~<,Y>D}(Bk4n+&c"gcS0j9VkYQ=uJ<^:uQ
et&&B>f3yfuUqMh2DNQD;mk?ce`(?3S-WN4Q1mQBHMO(Gt2+nYFK`tncD)YIlKodjnrl]=O|6G5>V:Ex=5w]75MD501&2,gCqF
k]B*OK&O4=sBXd&q8BwW5hdkg4S1j#0X,E7vh8)NNmp6R]1curHvrKxu59J$S:1L6A;Mx/840/;]@BF)AkbJ~D"3>/"SoFH+&mO[y"EbOnY$evn)/<+-->rA,2QHBqRdJk;W4j#^S7AHal}2Hr|mYOBF`,Xw!J(7+RrSuO[aRC^yfg`Y^qE:o;0d/Y9/CaT?ZW{B^F/S1p:<?qS(|,a_M$6L5UzkJJXb=!>?qKg`yn*Wr@Nf1l/UF^3^*u?/3yJi?`lUmesKe.$4kv5*Ca(`?m[EX0/aO>(xbesGcRlE_Tzf7d<XX`)9G1`d>6VfK@Id
n;HULv1;x]a+n|H5$GpIYWE/2@2bH*b~Euozf/VRryH=4fpDuUa&LMWGpnMtJfxRu!YDf^_3r-=3nMt_v[3Z9s%iTc/<AA
"jF:YJ4c},/bNbT^[Ekpl"UA;J%)h%kQ4b*56aD9FCwnmmTb59/A%AR`60iM=y%Qxs_XZ)XQQ8O8pc7Z4-$?i5EYH$c#?7_-Yslj.FL$Er7
c$p(GPo*@RfuCsbme7[v:$ZGKTl"_M_)Ym0h+QDWXq?4Eoh-R7bn~ga%7@ZBwnyMh7a');}elseif($_GET["file"]=="dark.css"){header("Content-Type: text/css; charset=utf-8");echo
decompress_string('%OsbOb3V?!K0U*,j#-4V$+4lSl,oCh*02mX@fy~Y!-lFD?AZS5iE
nM`YKnnN5@7$,h]yHv0]"r/{_.;5=S+SNKE}<JYs`q%O%%)irj"Ua|G&>l)NqxPHIui")?!f$TF|nwt-nQCaG&Tzq)X$0a:"l<uhiWpN+Q>JUl.I??
0[m@%2{lZZ-SVaY0c(Abuipc;HrUB.?"L
&fe39+`O>CaP%DBGl_a;sKU:Vn{vUd)#z;(-4/lH:f/yqJRLo1D)]&Q)#F_Ex@I.Aoq!%P+x`#:u7a*NRit]e+S_#3_W;B1:p*qj1n&6tLeURFTa*Z%=PigZV?!E,M#fGWI7
Vby;v}uyiyNSk%!K32:q%~)Z7R]f7*[T1VD8GAHNE,gNAjPt3bJTq!),5tH82n<xEH5{06?o3=vyf/"d[Dx=^/`OW(R/VJpy<uN~pK
XY0h>3?PG;:6W2&H^g`XJac/.2vy_[sa[I@2XZ6h^)(qYAo-$5uc0Ep%,GX=n?^Dh<AHDPP6:^cBoLiHv;/&f"x+
Fxs2:m>cC)c>Lo
0T]2{suTY+[`^=g^8K@M"IJhD,eB]&O05-RUzKB;q=jP@t>t?wQJam-T
Ct4iGwsJeBb--L[GY@5KjZDe)KI2"iJ+I
sFktJV_tO_ae<,6L%wV]]G$83G65)NlCxcni0jK(!Hn+6;A/K;bfn9xSp=TVCsf``qH7Mimc,xAY3>O[u4w?fz&Fj
9f,[];aKusLC!8-;hiECD`(]x7[,6WvZQwb}-<xBhai*6z.x#y/,/,PfbzjJZY5<k)c%nD&#@k/fnmY$Bx2daWEELXWrfOnaM:!Fa_[qjXgtfwLcv6,3f~T:>3n3wR;MUKGkB;/1<=rsb.a0udo%x4L7HAUd8(4Q+6[S3/m5?BQNG}h9#D7rZ(C[A#`5XL+tAmR_k4;*wtK-0+ixONPclR9
9Q2)1cdU5,ODCdgYd!N6');}elseif($_GET["file"]=="functions.js"){header("Content-Type: text/javascript; charset=utf-8");echo
decompress_string('$c4]`nsWl1ptWOv_h:.%y>(B8Jhsf@ooc^S6O.cGC9BHMu=?3)
3[.X?=Wv,ZyTxSc%"*Tj_GrEE9;FU$:J.f=X0d>JYBIVZj]D<aA3aJq*XnQqxcw)y-@0VgkGu?I^TmUVgb:3EfFdr)MNb!A,_(k]_9iV_"_(G,09n1q_nV?v_Ya}yH922HAvE)Q-Dca{7S&0T[#Dtk/#Bl_W&wjz.9y.XC]Yq%Jhb/AiQuuuIe`
H[3+1Q4&&~EB[RStfa@oiB?KrvDpHs&j#r_LJL`Fqt*g"xA[A8
[y~qK:8it:6_]`i&KGku1;72%Z|S"<"lD2ac}UGi{G0+g.v*]W)w>x/UcKbq9g^FuU`Cx2;l0o]3G78`XOn?+m4h40J%^y[,j8/jf`[Aa
ekiT
]!]LVm5+_"kYM9f,H8q4W[Pz%8**gF[*;oMUB}w6uH@/HF4QT,aW)t1GER
6x,RTi^tAv3wj1,rPT[<m[V[
v7w0=&nC3$Jan|l=tdXcXut7wuK[MZEdJ8uZSC^U)zk-h]L>2"Klj]:[sKC>q*P}c9tr,ySzuv&+rPNW!Ml13$YQx$tt6nbASO!]:H[_(b&6Q[7Z6!1nLF,X<wye,d3tfh"JO4b_,akkbL-gTUfs!onu3q,>=ZgGf}1)_HCzB[dxj}I&?d-:=[TFTqbm
jTGDH<FQsW):/7(`sMu5uZF%cu6-t%h-V@
S`nB8s;7eS9Y;P7uI*f%<Sj0e|;fCM1ZmLc-<V^"Aum=T-Xb2$xhvH+nfh%401Vm*95qb&;?hropn>:4HL6I`dBm#J4P+uZc-`3:8IP~y!z"&FTsPyc)eTPqO=MXC+&9GQY90mSIR/pOr,:dR/5J4sBQ:>.K8KeH)L2LK#+4%~avx><sm6t%D,?CM[__KlhK1M^6OLZafEXTfCH!s2s8;6E[yix:0<]pjSEtL^,m=iG%f?UkP|7Z?U:wqHDPQ|&E@>0Xlxb2W@Uz#x*yPK(-S)eaNJ&Bfs,vb8K*yQQ|X}s<m-Q1=FGg1p<:F&ULI@>Q[e`8]J$im:dY#D,Oty]u%`GZ<wZD7Xjt0(^oqGR6X7Z0=W+;g[FPTX7mbv@m_r1ox}ydcD&K2!q8$f3E8nwpiXvn,vJByWtZ*SBob5n7z#
Soub+!!"]Tv[d!h"a``]uxL);"fsAF"W=cgDso{(r,~(hkY9Rh5@}3F25%HS?"~>G!7C
%"uliWhZ[zfhK`&ZgH<Be(t5m]MN&m;9WMsZgD#j2/_(6tud34LhZa+*Y~HlkEP}LYr?I@k?[n=~Id2|"f]`<2x7^AP:DI%9+IOd!WwG+"""F_;a@hkicZ:FW0u@]upbdFl1*38IBbq
?l(~:S0P=gb<GY^t<5Ask!qJ+|d&0FR)XOV5eBb_yI?dmXVklmow;eIhOUyCO.K:RPg9G$j
`7se]e9iOE?w/B(R!V[CX4"("i2i@Cx>R9m+&8+Np3Y?k#3)JB_BEO*X1q?83pC
=aF2cPlq-e&L,
:~g1O(5WtRKTi$P:D:6-@!!7=oO/moc.$>c+]/hvY8Y*6wf_,+.k]84!J"
BcX&_j6iUX8-L;i(_oc
":03Pk]FuUv/1o9Cq+WNKqm74;VqvrBBoxm)D2@G_YjGVRky>9ao0pN;ZyR,*e/,.mchZXRZiND5i
E"R3/XkYDa!("eoNed99?<?rk_#
iVH8:NO({5N"18#lhc)O}F~;jr2kI!-0>Cr+lgIabnSY*oipd7oQ.o"#uh)+>"uIeuQV)jAuy3s5?twVBbXCdsrOQ&Qk(ayr<H3if)vVX.5ITL*^F3nbnPBBZ7ajgZ0$vMz?9s:@+JJbXAVS/fu7V>rRAKR)k_9`2oW"nT[fFAdFb
nfog:`79ePeSYtN0|(@-./s.v%_RmkF/&XLvK0VP1&b"i_<ph?o_-5h&:wB`lcI%,$7Oy?E;gs{en6s7o4mRS_=bi7KSrpZT=0c%ogvtFm#tC9kQ}3NamW]apPHV/#?biJP&JSw5O+a+`a[":"&[oMC2^D^IBZ&eua?IiB`H*N80:Wyg916?Gs"X0T,FX!]EJT(lIu{M0GjVP;/1F^J-Gb:WllMX3/_i/>RR:_*slBC6TlZCpr0k^yrGk6F#zwT
5NyFB@&AFGK&}sIdH)Z.I5E^tgYyVXe]%aac:aY#$Z/NGN21r0Z/vP;@%t/miyd_J^?.,ekJFXQ9V!f(|uaIdBA[cqr"eQ|<kX"OSE?C8AR"{Ory90uHYdR8(eh;_%U!2j.$6*}AVGI(7-d%5NFhUfL!(!^@CVR_h;znlt3qIg7pj0+8u"?S4rxvTN-o*;yT&9$PLZXT^T4=wI#rfRv+K&oTL:%P#_65sg#B&->[aYmIFSO),8W_qxe)qFk,)_M8ktxk"(|Oe/VC"DeCeu:yn!F0n+*&%&?;][YF?*sd<l)vWe*b5W$VZ6eC>Gx4"8+ah4^-J
n25m~hPP:vHJEy^l%c{?+rP&{c@DTcfm}au@$*6T@Kji)pM^*?ChLp6^q4wa1`vW&12T}HDNc_xr$5ngo_&S^7&#~nx<G;(BBeLmq/3>MY70.fj#ABt3.;*7!f&gM8+Ye!_1vc6pO9~pj(0Ek.xM[8*[Q.Z8&J9W|#4j$TWo(^V1Vo+Ktb3i1hP1hLUqcWCr2<r:6d+EK5PJ+f=U^e;D]lt,%Bq8zrf33%E!<_P+lQKg/SD%/wS_BS{x#?c`<*:kL*/5Rr^2}7ePX;w&T5LTYE<XHi!?nN^Xf,:*@iWD$+;0Lkk@IGn)3SUiqQL)b*(?{)q.DY/+j>jQUc>:E?`qx5OsjF,5I!P_W#i8.v~;0-gYJBFn(rg+V9o]mcEU<X#UF%_$fwL4aU5`Oe~bk$gqe)MU9V
$%FQc!`&W{
lr%*{wY@2ExA,V#x"@?XGZ@bf<lgg!/:&)Z+dh.*P5_kufS_7
<9;44lAyM+A,<!ct#Gnv~Vf?_yj]9LtEu[*)^JNY[o`QNRc6>UQldV;L;`;.
v`g$k
$f
$/-Q}N:3K[>uE#5O|Oinj2+0BP!cJ;^7/H,ssnkBd/[R}H&JsK%@n=bD8L@x`W)w."?,340*%8;#)uAq1!;))p_!n<%ni3Uo:7a5E>FT~.NZjZ;!f8=uZW9mU1zDyls0+9/"c0[CJeb#L:5C+Z5+O9J$+t)A48NE0D`lr^T,6^i^}wm98fNkUdg6bFdC,$LmQWoj`J;*XocC=w8*Wi^VupvA,BN8UQX&b*F-:xZ5j])T#PVpG(1.~Q;=S!&NB__=k#`C:2Rg)4;,$"d]-JasM[qu)**cA$9!NdKn7@N3@E-()VwmNiP8bNvNLV+abS)[[s:BWw8de,ZUpftEbm=)x?jk?+PG)^qO!M:b*UC$/_/Z__[=qT!6iq;
|!YW4[vC4
#?nRJG.t3k0n.YWkX*)1zGd+ol<dj:!D<.l,9)E>RxG",FBGUi)OoV(mwG(,?`$0
?g6:2wPTUSR+0aAEUlOaO"&F_yZL<65=WXPI5G*"a(,8PCYXvGiGyyIZH_xCI)5JNg_VeEM0+&n,y}:`.TL<W4(bX%5mb(]ZQ{a2x%E6^K-NJ*p0A@rPuXDt/U167_j8y1[R8I^;db2A%)^W+0:~Eu2fW$olA|c+1R:3KeO/^9`>VH>}sx48fzjE=ya:7)DP91</
IUA+.,S80mb-7^{YS%JPsgCd<s{B81Zf9F"K-C>l,.?C7jWq`,<K$myE[ON5u1<?$y7.P,->,4ma7_B
YOIdlna:sw
MDuC8y64K%VC6y.`a}yDe.KL8{C9oU]KO-U.aC#mBp+NaF*!.hAq&Cm&XB1XbD=;
eL5)C5t!}b72K_VP"l2F/hLXXffHHkGPaQeFFWZOJm#WaGLF;>:WXn2j`RM(GV^A36%YxbYsptKiBM{c4y?xq$E5qqxNb;gor*gE]0+T^IvNc96^0sq1{<:1#x%NR>bO*d{Ao5i<^"gE_ouy-3r8<SbS!/B+Eu=OK^{yJ3>f&lBk>pLM#(="v>WddPU&T4m@oZ{d0_WR"-8_(F+7ITpj33^n"9,]}7(>nUnbLyWdNL$3QPO_/thxF(|!{R2TVoMC!1;Ea*O<X?d0/*E0Yf@^YxfYV%xG91(!qP?>9i*As
3K+8ebKu!%c?>rne@)bSu,08[pl#SIGZ[np(=yp#9)I(J6D9iro(^(@Gv;*9SO}>PDZ^/L:6W*]SLwEAvB`K]K{y6*Uj]e%^V0g]i&zFUL=u7n+g]W7m|8D){jO+vCwTe2{69)+ySGm<n
{0c;sQ+q,#(>i^IL`dD),TtA@9"Kv`/?El$,0k&<;v1?v:BDZy(uJOH.#
G&k^>5
]mtfLphZ7Y"I(0eU5*b1]7$2f[Xd<cu&q"7HrfD:$90lgK`@,/yEH{TiuH[,Oj2V`UCrTF(XvwN|y6OI1
-(&Q[W)N!T#;nhXW$jTVv/8v+VR9r3D[_&$]Ua&>_2_<7.&1E$<gGG,l!gWEqX(Jy]&V
7%p_T+*F3(`C{`dx9#q%
16`Ay<6$m)9wEu5>O;a
H`lJJ>nexaBG%_B/JVg#mKbSedq
im_i$?)Ml4D(U)8rB3]?#XWDmtQ%_RggxOQ[^Z@`qnUE_UKLjj`9oX5puQY"IlbMa0HxdS9+jKe
RHD+JgJ&Q+RtuR"I3bb&
/LQY|b-b]uvEwg1!ELohh2,gBrW#ArHmxXs1>3/A$AJXHobi,yMYl,UgyK.gP5`Nv/E_jFlF|b[Yr&IO_IR3EqN0;jvaX-uA>^F<&t<&lDuH!k>^3ZkmI_w(<42hLdC22Rp/`_-YSZuZ9+$NQ(jH<U~5Xm9l+F3j1)sYm.=-}%5.IpWkc
zmMgh^o_
f>Tl_OLR?AU3N0V+#CrV3jt
@B[/+11Q4X+eM&HMss`JL5`K5X/8!2N*v5u)]iDaY:M(c*M[vO/7a(O0j>vGXvGd
P?dn(.//Zqu0,_xsjDA*bt[)o[&2X:8:NYy=!7KGpMaMs%}yeyy!J"CVW.6pQLRHX2Q[eqUQH;U)C;kj6".-Q)<(Eg*%uDVH4xCBm"X@|#e3*L8Q"c%N+c|iD)3FK0zXfg;m*T*5Wq_ivEi4j!eicSpqoM1FT/oha+<#dg6ZRj,hi(}J"_C[GxE8LNo-9^OE=%h73h}P)Y./k
3_nR~_b>,&f>BK(V$9.X1h4Re^:UGPRH~GCka,is-mu<,X~hV&+(UX,&Q_D<@H3K"2sZeubF%7!dKDu`J6Sb6/qCgD{+Th<2:uB;3UuVV*F>3sp<3-$NBSxIgHg)PbItNIMk~J{MscMynGhnV*rj%I00!PSWyrrS
>aauDl@R6$i-h;-FE-$ysHS)T-k*]UUMH%qLpxb(]7^]MtvFc/=b-sLDv+VD!)eO:@kj;Nw/PqX
yl&O<P^&@6<jYs:Blva5?(5PX<aZaiP,)du!37%e_OKZvi,>WFWxnfg>s5mj`n7$vtE0W
PaXkC]CxUEO^a_R/Pn*l[~eAZPX==.cUE]&
]T;Z`3NC732o1%?UP/[G?GAE5$#Q=4(Xa",cEaZ8Jqdy2!:H4c)"E|T^1yEDBSrHCyF.Z?V0dxE_[-sk1B
clDski2xuH%B2U/[:#B0(6$<G_rK0ojYNp&H;NDG]u}"ap/]CU=5A2IB|UeJxpD2)(G"E8o8Dh3E<Wrda%
N#"Cu`7Yk,geL=f(e[-
G@?t2RTim#,bFCp$qc3E0F^m.X$LoQ5,ygMfc=!0SXf,7(eCLoM6%8HAn&(;-Vk%B5]-AUg4jTdOdLfU+rSvG5jE:|DJu=nLjsw;iI;i:RS5B2O#:%-riqBvao@49WlwtY)weIrtFwE"V@M9+Cd>GcP21ZT,x8bn.WFJ2Y(Bt"3(cbAF6PG?*CKp%w3[GGjTc>-f6;.nB/>@!^7mp!kX*ApVfROJ[F+=-hkE.:m<S7wh)$UN5~ByYQSGL=q7oU&uQGSrRuPB8O-<mPFq2}v$":(i8fPh1&E#w
+lfx]d^E?8ppBl-"kq[>QFHpDcdG&8JjfR.LkOE^/R5b.?
i"U>45/hh7NID!5eAV+#Yf7b?Q|*/$-oNXqjB":O.X*-icZ8186)6*FsC:KX]CRlXRB+>mem:q=3#yyWjuhVl-<l9sK&@
0Z{_faJk&L&v-4`x?SmR;#f8P;&DAG7>MN+NRN.l5ohhtSe7_e5sD9Xs@O@94P#d4[zC|#VWSPm6E=wm1]u8enX29<9i$5>k6kZS8bu^
R(jteK??Wh35N8/l`@Q2MsDp&K*r%=7RPx=D
04{ag)pG;V(w*^d7
0G[<LvZqd%UTp7KFdP8"LikLbc1^c.d$emh;&2#zRA`~@dL6gQ+C.V(%e}>K1bj/v@(u6O]}5OG?,%iw:P]b=:*z[.^~Q}%1Z}wb!},;R/Y<r:g4_<=}b<hneVm2t6w9wx[A0jetpx2.[t>jHKB5Mj]T9SMi.BV8ds6WuhP3k&$4(QT7y0ZUQAht=gj}ZOnH*SEk-Z2KJp!_Y7]wD1J1"|TdGMf_@`w(=*/D(E<AULJ-MgX.PR<Gc#YXv1#[VM[1&7i+Ziu2)By%M(x<khZCS#lVdC8cBz
kG12VDkau*)1"#4ES*mJ0Wf7^[elz9q".THy*uhgEZW*YZu0%^=Cs:jAkDO4r6GSl@fnxprU6!V#a+mH%UIjnIJ&5V,f75#Abt"*ual9.A[[98/F"Dw
NVYho^293i!lJa/.O9o$,.Ks_5p
zPasn]a9l/kWd
{u[8FVbG0eBwFHCmv[z]2009X^;Puv
6~*/v*S/pM@"YC47)wX&J)SMYj-kr30Xk$-IQC"BsW]eoP@;o7<B;2^T%+E=Q>8`S7oBGFMssFj7eKI2SM2=v,6ai#?m%^Yv?eqR)+?u5(Bcrhnv&J->o]"iD78q#pO_q?L"u*U"xB>k9YGy
x#}ff0wj7?HfgAn*q=[53MAk4-T6$mYDwABaJ-UZTds#s<W&pIU`9WkqOI49BJe07Vb3AEZ0JCp<S3s
L=mA+f]itDR2f,menTLR@4%@x(8D<vp=x2w,=SLBQ;0U+4X
$21a$3=s?GBuNR^"N)~$y5O"q1w$~F*0lv_#|YF-#F:+>?3
aOD!Z*0/**enZI8d&Ik:t"]H@20B^o=LZ"l/TtunLCOYHu?XXkfqskKZRkg5f4NOxD6?d4D(<"blmb2+x"f?46
$%mNQs<)AqNbX4<!;Xkt%$&yJ^`v5H15*yi=?YrZo!A(Z|tKIz.`yS-<.GUa^^VbNz/huHRR,^8o>m<`Kqh`,nnItHb;)B:knHf9+iVo#hGOkjRdq^ZzCS^{*j`g,Bxdcs@d)h7[_<D<l._HhugO:=p9W!Ur5z+Slmvfkvu#3#uy`C".b2D!-/bMoBeoD>#hN6+r)QETrGdoVk/Mm.qoD4@=&?wO)(D,5i]_,{,RB0=X?,tPaTF*S]UtU4AvQ*50sNK,?VY!?0pP(ZJ8c$`_gww=IaTZ:F9#^>"sGwu2@/gF7Owl2,/VT%<<KjF&IU$nfKV[fAKvAZS0s~mWQ`[-L7azaMGRmP5Vi/Afn*E3945&3SJ=Zx:fG0?A)@15*/l!Ru7Z+`B#7D_ToMQY,SC~+l:^t^24r4yUA/i^x2TZ2"Uw1SDHt>;aCD?$-~Rm+o2gv}FxhnXTk<b@
Zo!L#_}H~E:*me>iT!0:is,ewa
gGTj0SDNW&ulC!?8?8,!ljnoW6=XK@%^>Z.;vVFsKJ(K8{f~/,<qFKpZ&:.0v;AvMRw>pGy}y%yeFYL|+Sm@$]:v)6]-".R|3^E4vF**y^o.oFt18,I7>0>GncxKf3xD(z"/@$dx,7)&BQUrCMWcN$6^#="#H~n;Z/,k(SX_Owys8&mE@)gdVpQ|m"xrEefIk.8EpL<CNq!d9{(TinvL[vaiaK5_.#"G)]T.FT/0PT[mvRg4;n2j.;mf3]D%a]sit|
QC*;8`wmyk/ZCv/Y56Q-ckcy:&s+5pvNR(gyWP{OPt/ZHgXAU^|H,Pg6q-tS4]?7w3ld[8tn7B:Xi"&jI?)+H:Ex$N^"8DSEjy8RDt{lP$Jr,w$&FcJMA
7in9R,aV
ip+6U:w,D(LQ4]iUP(`/]_WB.9M+
C`s05tcx3N|[
clJr2p8V)$&3Z|J{dO*-<HX/]+l8C4hIJG_8o[Hjs;wuD;8T!rG:<,O9ISS
E[%B"z>/WUv?%BCN>3D:NsN)/|<I1;#kET,HPxUl-6f)`1f!CCGR6u)sePOzOztxA@g>qbXs"VCR--@R,:"msy"0dlS)^qEneOwf)u-,6u-u[mHARm+Qy:]Cx""2?#/sge?sA/u1"rZSUO;;smjw&k:4n2y;gB4{LD`eHGbi_49<":y9K)@N^{ra`<F%10#~2l8NxR[|_,9;qD65X8EwvwtB;tF(
m!"VMXJ
1_hyoZg*U9Qv~d)Yi0qt+LlEn2@G%0"46/FV+Qr^PYZegs/7G$F-
caT#h41z%iQ5Ed5gi{Qz5TeK?$Q[XD7Q9`;NpOv=g-OEIR2P&OS{4DkH8lfV*:k(a?b"4;lip^9#dmZs%?$"/OmFmvZ#l[B/A)j0]"[Wt*8,*fdZ8dGW]:22;}U+!/iyGW4rFi#0FWO@:xI}TGu^Z?`Wj?wB4JeTLwAH:bf{"0J]N%;MZ0NFg$*}Spf?DQMaqD+P:^Ws53AK*9w*yxyo3>O-Y3XZ._xgZq0PLg^9[-9}mrD
oH!**=ypejPnT{c%`74|dL;I`{gn&;<")$3NtM,j5b1]i.f.7H8iw$xxntul`#--NWiY7$IOD^u#^~$f;Zi-_WP6Q
,7R)L{8-F,XL/I
DVe"]U~<YZ`("4PXYl<-lv8rd35cxSqIsU9$_]E
5W%:n`ITymytNMQY/*/x15T4f/_AlmQ4@!ExMtSR/Xe`fpJo~hbJvynsB)H$IG"kK=bY$;n`J_>XpHWdcwuQq$CIdZV.#Q~@iB+%/)T$V99@4_>K0g"^(CYJ*Uj<7[EsWQCQe*&y-E[EpC(sN&GnfFuw|yX)Y7cTqTH;|#A?_O=?jb<G-_=57aCY[!ZjhCV"NEP>1d"IU78_s8w$uhG
>aq+<N.%!FnNzQ+#!vTWib>;m1om{&FES?0d{a)[gQb&XW3:}m-$<EKf!Q$YDi1J#*8viaU+ZE,KxQPnN_^ka9Kr!E?;e[dG{(a_DZ(^&hFeNi#rVF#8).b/>Y$XRX{cC9Ny#gBW=B0#GX>CKVH7iLG@/Rx@|5}dOFSrCf@X+npef/aYnmuYd`CQf/Dg[3[1sY6AG2(_wu$wp:@QUgL+-$YwWd}7SDU8H5</sC?3%
/SDgPe8!I>dfplqV,mw)Tg
Aabewj2E1I/>OCJaa
W
:US6MJry1=+HDV]|nldr/v2%hEqb8(e0o#OV
2BU2X<Urbr?>0,IAc3wJ3IDIG_iv7YkeHaU7+kZ-*OL%_QjCR1~DK(:G9_-.r(-yX
l+wLb5+YX^xg$V|nS=nxVUjxxh.,ZqdW=_dmoCDD"=5:P9ZHIFP,#&"7t8[>h5lJ_^Y.wckNV61CRF{kFXKjIK{nTvl(EBR!>
>[q4L.FbIFz^E=kXP=v8@c(Ql$wQj(@Fx<Cam&wU)mdVUf?<]LdwH&v)HBDOgftwN4zPO+YGL?J3F:oRNiex<j_A*68>AK"d_r?:*PFI7E4r=a.Q&-iv#]{>0W(`lb>3L#;,^F9nmY*gT118mlqb|3iV{c=Q<pHA:D>x-RsqJ])"9Tuc`8C^,vLJi!>%>uPD%^/2_K#,V`q+"rdhr4Q(*=WMh+!cS/zW=ELI[Q8
3_94OA,;XQbAKSJX=K4j
_!l6></x3{4X%yFu$,UE@FL"6x8V54swO1M/%njfP*RW0nQ}P~db17`Vmkt2
_Yq;n?i9sAV+mk=`~rW:&o;myY[o4=nPLDExG:hnIsaLtpBRe+^ljmUQZjl6pPgL8H_sVBH/y$G"M0V2vwx9J%=80Z_Y!AvIDQ;^fBoeTeOSu9W9kJuV#TR^VG9Q-Fnz#qkne1(Tiy"Vsn9_u7<S_58nPj:u(8ht9lY(EVN:*o1$5"Rd8]xW<v/QmR+_t1~wcf|]khLb:e/g@9-lIJXOdL.w{.WcIAIm9R{ZDJk,YXGr50o>3J2PPd&9zG_M!(+f:E)cQMMc5OJc<)WyEtm3]tdgKF@"f%U*(L[;_CgKMhx*Y6cB%kd+jRa)&kdb;=A,<Q.
>]DM$75oX=CF4h]s*9,"JP-n2tDs~Yy-wHAdv6U,o2>.B=F>/=pV2V&]hV&T~&)F|p;5<*Wj
3J"KxuDq<:t2Y##sH*"w+XoG
Mm+/pnn5@U$?b63CynIo,
is*Pjplt3+_6LDn^)PKH]i,],Z%]6fiDzT(FdXdup-U_>9kb_V.+$@;xs<yG1K$/9,5b)Z8[hz#maLV]~w$w<`rH3GK-56#%l$EKHMybq"<LHaG=M,tm,"`"Dh}I77nOD!74,qu&]vSwIIMx:s=B1g"L-HRt):E?$-XDoQsH~R39w?^)7TR<A]!jh]1HZ#uODr<7-B`iE_o:gH%_~D_LIuXhI3,yZ$kfB^uvs^sut61M"!gnUs?M5,70ZmSJo5:bR3BbX[0kWw$.=HN3"!:nhz(aj^=:ydP3tO!s0b@gwpJr_]up&yVpBs+TGK>A@4>pIP=aX4T`_f?aTF6-g%q6Fh+FY+9;#``cjy(FoT3CaMNivhrLNvyj".7!wx=mjILJ5Pz0}ZY9)L]IoEIA%S5.[;C]^_NNPq0?]9/EWJb=K9iS]xXB%ie7?./wK3k"vo|OeQe_Wc*+cgHyF!)a*A:AXP&L/0:iJo"nhmNZ5$[AK,0>-LKV;c`QL7hKFDW"EI+5jY&WK$rCC"Y]l2Q73;!LYBy:#g}]C$RAQlN//S{r&CwC9TuGiV;kzA;"iGU2l74t$9GG@&H!<"BKb_S0+j(rOx.kiNH*l(KkjR!5w"NO8yObm7O60dp={uRa4gVV@.ekG$T_lcVHZ
H>4Y;UlW@I@0w5Tl5<Nc%JOd%H3T)yRm?T0J~yQ0guNk_s5&.U>jnZ!.1q,c$
+E"P>[%k%A4on/Xym9
7Rs"Fz7tk!mvB@-5Li0ci58GvA`Xr%RekZ0r,GaPAr-i^>d_2d33mmWkU;(Ss=q4r,EZTb$gwNJD^CAMbf&.xH9~OAHU_G?OK5Rd4(b/q.&cTlSbmrN.AL/Jw2_RG-3+=AxT0:EK]+?@r4H^)C;/>M=-gpd6K`*M`aI5
`g:2}lYf/q>GihaE<BqiNkBA7MIxk;?ng_y+rk[5?[M)}Mc"V2$L-DvEy^dMJ$BA;K_
4s">2Y~E,MpS"*eIP?<[EpL*1BkpjLcTBFMcnqgz)+"6^U4JFhnf]sr/1-D)&=l/BPhJAeAX}m~0=)r$lJ([nuqeIByYz>.UziwUWx`2DCdG+9ApHE^O&;CWD%usIZP^0U~6"*a%h6vQ;7(;;G
.BZu9jj{l~62>%){Thxc(eI]6>&7%mJZl#g}-!%_C:7Fw|fWJy
=Jqw>sGPn53`"w=ZKaNUj2inFSqv$`$_/*"J9g3=ViF;TlJTZMSNcVsvcE
"CR#Y|GfBiP+kDcDhkMV
Driv?<.l$)Pcj:"I(TXpXe
Yu"cZ&ypNL%muRtiD/^L,SQlB5o1u8&q_RV;CFpD_L5Yn^yA@&>Q-snu,_4syTV@sGgyGC,td$r5w&[~U62Qi>`Sn1$kPr#?@|=7_HyE+MyMo*[}Q}<
"V26WMN;*/gn#}W3QrwNRARzG
w-7yl<FkH%XRJwt_Y>YA,E8h94x/Df2H4LgA`3`n?;P!5Os#
>dG_an-rRJwbnREc+ub/;pw7/ns"/3{LJrnD.a3R=eK[kFmp&xOESP!^%AzaLR:pZ!,l2eF:XK"s|PW(HV,-zHVATDH300yBz`=gf^2w-bl9KLL_*].Ae5/@j..Es2x`swqv{6],~n~,~I-gts1hZEUEA4on=B{p5;z$kM/>sbaozx2>.8QbU_8PRrllo(c32tXl_DVAB+ctRFa93Wn`Osp-c[0$g;.2MU[1|
Oh0(*xE*O0X$0@d;}?2%7"WtiEK@/Oy+~7~Cclt5r&!eOX`m6q)5I/u=ItEtt`ovjNr"~*G8)?:M#hyk^2Bix_?aXkE`[=kn:2zOV%{G6LOe=FUBb$b4$kw4*$0sAY5uzWa#6/Mx/"kV!(g9-rydb%~:"^E
~U/Z-y`-t9>,;JI*E"y
DY">-Q0"/ai&afGGO+/l|x|pfT{s|"{0Lm|d]`<g4meAZ"3#h_*4sWU2;1Qk00
O1;=Pi>#S@H@$Z1l5uw+Vux/L|)lxF4#UW,(%9-QymPL2<I)5louCT;ntF+g[dj|$h17S@oVjyVa9|NppHE,dk>&"50-k,N*)"]Z76I$.r+-!ubTOpIb,;T-<~<u,]h:@DNDr~d~ZC.A9YE$5D6%*x>SJ^?kda.y[EA`W&A=g#Y
NZ-gkDT54&),h#lQGsnIHWbO(CKB]9%1Pw5>[3gEC{Bq&+,R5WW=_pf+g7OK$C"$6,5GLk%7g7xD$ZH9bzW)6CxMI+%TQs2]x<RMAz^[WVhOkB&3x%^v^VXof&d4+br-C`<
Ix[JYCS](<kU,{WJiRQ$u~fD2W[mCuaHR./Fnnn!ndWBD_Srl;T$jbD(G=6-QXc8lNS<QaS&f5C)gzZ?GJj_RN4~_7MOUYXGxh[OHKThbhu<[;7>k4Z`VGOest@1o{0/0I&m%)LmY?fq!32;*xQ.H8EWC)!EdQnS,Su0DJx^DF
03UkJ*7>vueTS/?n:-=1$(%l9XIsUHSgD68Y^G)lU%.;6qAeUJ,6W^W!g%pjHk>+$d~d^L*a7MPkK.D#Xd.oBunB%kQ9OK|mYh3jZU{tZ::xX/lJHJm/>(/S"FV3S>Zl.[(@stGg0$SrSbpwH[OTC
*MbI`b@#9hox#94%qYYNsxz_]YgRQoE');}elseif($_GET["file"]=="jush.js"){header("Content-Type: text/javascript; charset=utf-8");echo
decompress_string('*hc]`nsDSGrtW"s7HD1-)!Ghw7W7-3V0B;<LJedR9K|M#-k";Ty!w@^4cKTyVc_Q4UKRt;vc[Jg@}0VvYJwbM7>DMw|j+Kw
La2pf:_TFsFt8R-Q2Q9hll8Qus%T~Y_5B$bac
7CM>NxzUWsZP]xyugj{tv`5bA1rd~wr@=aig=HwQKRi<0h{)RdUWv3cCDf#&mv%*Ok,.Z31+59s&e.cktR
dU++Oiw}u|yby{vktr?~Y.Z*RO14UC4U+skiFQPzJvy_^SobDaGq.+lXb
J>skrXv8=%>*xGNWr93
b{-{%T&5xW6u>2tdL";FJ@2d(B"-eZoPqnPZcTEtSlh^WyQ:51,HV]%~^}ue!Xd):_rdfHaJf,`GJW+TE2kNRU?BO>tOV@JEI4@,(B2$QO.WjG,o`ygl#WKQ105lxVtMCNl5,=<P<35J2X<`[i6pkU8V<=/_+kt$*ZjRF9%<ueT5LL*zf,L=4^(cMQU-)9If4w[HAi8]n9u/ub%Zm{S]70nuC^_bGPP9D5fY<Q`EwYkpWx:pjdK$Xkq?:-FckJhkWPMEykO3,q9-]wLiJLVpvzIbcgDZPLiDVGZ}R3,s,LQjRB>xp3+Ur2PZItOrXs
r>{!<F4k)o)&Z?gcB&C.4NF0;L,AuEe-&-=_v?_YE#u
Ut],sD
.G]s`^acN@1iu~$q-KXFdbapS2LCsyigtmhQ5Gbn&DL4I9g$Mi#vsnQBeGH4ICSViRa#wLB-JD>sq5095*/.m+7ok5hyWRq.]swa&z!Zu-@[:T."D2
okocYg2-^Ff)abttvH_6UTS%H^K5LWns5H<S:eBK,CKS[H{4FJWxQ&sFB;3NKjmCoN,pXTm*doSAzkh7P"1#OG_=<MD`q?2B8i*CA@U(0b~`oj
hS@}hh"Ff0T;%.uP-:y%rrUtTe^+piS+
Dw2^*xZtG+}4|&&wF*Pb4#-4R:;o?,9%oCs[^G=9fEEbR1iv
yc$[X73zK5e8CK"2LCB3t@6Z,*qi]{k21A)fFWh*#<BnR
j%`V-`]kqb9s)^E{RL>Fm
R|^])0[U>~FjI,+XHG@o`{UPq)R]WJ4!;sw2sur:O=:fY;i(rls_l3Q_,)H5O8pa&.nG`Mp@!6$?RHXrVV[cN4G%`?E~F/Z1Ni%)/!O4.~**XbWKnVFv#j&~5P.KN}<5TaWzmQnG@B2{e{0O/o/.HOMq&cD{*#89x`merC1N@qw9(fC%k[tpl)(gN/e[Y;a)?z5QWOMl`^V-<PC575Dm$$-A#QN#=EqGDo<XRgtLQ)e|=SsKV,6&

iXMzYD)aUBF@N7[M`X3%:r5`<jggTQBVtgQ_?sJac{t/]3F}G%%Ov(_WDPcNvkNtv$m<=6<c4"K_[])lWqD?qpE.c";AS{l@4h-TM[5je&]S6*J$sJG(&O8UUtk7YIL{NVA?W&*25(bb+WW<IUGI#?[vYbDH4LWu
ifvNx?5.,82IYjrY1Y[^FCw72F[Z:sXCw8<r[m>KL,e]A,6Jl`
))"4,d@Hc@rw]!C+F+g=^"-g%@5[tZY^gWI5pNgF4$Dx8,;W-a+M"=fG$@6*meydfM:,FqrOv5&?wq]y)q1c"pvYK{fR^?8eXvF_8!;[[$>8KH;(XPAp&d2l-RrPQgs1JNM{0S
rO5YF^}e;$:7Fm"D#gZk-9AbBU{ZkfHm]Cnvs+b:CJ|Xk38KN1DDaw~<t:HsRFd7A?"W{VGA=/grjhKp7LI#{3f6w8NvqYl?C;s,K3a0DihZZ
[f$7~n^3=L28vp+mKfd"FPo33[dw"ir^mN2K1,:E<<YXQOj_8XgX?`LqKv?[``oooim>:+
wz%>,RqBA4O31HUXsB$<"?Yx`r(`?.:}MMv"tA2oRr4vj/3;dgwBl[68*=&jg#<Lk*+MDv,urm[ch[K.d]=DTh3Hb(OO^/Ka><h}Jp.0gCdH0,P-c2pp>`k3RZ`"1$Nii9k()7F_(c&rC58@gYWR
t1C$L(p9[u(KHwD[.7tmI@*eOB(#zGw"RRfS:A%>O-xS)-m@n19Cz<`b6Y|iU3RA;-*u$h75!,pIFO&pqhFf~H(m.*|e)fj?K/l8~L%u/IQgP"gKZr,VeJcakFY/Sg]KSEz7GEUC#N[&FvMU:YE]AV;lQiUDeg#f#+_o>_~JIe*_>]6
bn2SsU/xv$I6?9:,M<-5"vs950aj;/J&c!P">H)Y7gp?ieR*eLM$OYJa&42RS8Zy[%)825o(FW?Y,kD
}EB"DI`av2S]z%Wc`%GUI4KwUb5[:5|+}pW7!s"!s_VQ)ly:o:-1H(<;&[Am7]y&4<$&jkjyumkBcdGN7P)?B;YZr20:g<7_,
QP@T{l[b1"=!o4HqNK6FE%m*:n+p
"_%zV
%MC`JF(}I3%GQQTPl/xxS0
0*SZ38va@ei+Eg5.5k}0_?~_@BH#.,G7_MtCwx8)q8LVD?S?n-J$WP0>wFDh,5h&;q1dEbt.L7w<f<`m;djSo/eoh$GjDMl-{%@&HLGGzA{N5Y/^$[Wj>()0<Xb5fi*T-t)TYY7>(Q0d|ht^%??5"e9tF+UgRSIV:<Q^q-;-}J#?JII=sW|v5t^T(&SGD^#SU9z1%O`4jeQZ8/-=)`BO]]RMoym5M-X^Qq#A63+Oe&}e<DgZpvz+HO~,`hd:+g9_5_z5$fs+YBW0hp?O6Obh]0"N]0MV-85EH8X
P2z?<IQP7ljrL6C<AqIFgAAw!!5P6fwX1YP^.Y`DbPssx+FFQ)Kum_+%7!LykB16;:^^|NEO:rR`,R9>|RN-KapiN#cG)*b^LIy^9k@[gNqbteze=+J/w;+J/
_c)CbWG#Qla7zAzM5--]bSLpl@a!MiDW^2Uj>7>ER/~-XS*yd.GT5=>)pR2=D_#%W;-dw97
4N[.r1Xne*ra%V$+pGb!RVTu%"fd:8uJFF5i)AFjLXjw&5]Z}"44P2*M,]k>C&EX*Gr%[I$[9IwF~8b(rwAYmCY`pi7JAsuM$-^PiP/e_G?;WJXZPEBh)E1abiq3,4ZN3`)8kUQ`V(LGdKTmX;6#Zq:uKr4y!Oe9hVLr&WmH[/CvB99)*4bUe.YbQ:wDk"-"f@`TQY1aaq%Whb|3vUxTzSx8[KKYL;p]b1ec,_Ry$Z>jF"Mf|S-KU[pQH*AfA[e`"uYy)hfl$Rd*>]jr~Xab:v8G
g1rZ/NXJ&xE)S
CNK^E])+D$Y_.MQ8A`"~%I]{43;V#.<&y+HPC
U/A
FN-FQ$FzGPY^FcK%0[V&@k%?`fs`3y&>
eT[R>dPbZ.!_g8AF?4MByP4:*#

GP^6QaJ4)<UM)+4P}b/8E[cYvi78NYmP7=]JW^AdZ;".dI:t-u[[MF+ni/1r$]S+BkQ;FKY[DhdeNx8)A05)x^JGM(8y[%}0MD8a,8K09x#66^,/Y+kn=]m@%E>u<CBf#qksd&tFw&F];fg>HiZF#eYM@5rPS;.6$%s*qe}gVYA+?`]nZ-;te&D#F2{dN;<nv5|ri>n
SC{9QjOm0/<ja8!`)?n.q%
.0e#[J?[u#F;Z{"Gn!DHjwY
Y2fU=U(}3yK$SEuf5.L23S26.E"[a{Xu6L2PDIM&ONR-LAdZkCQaAFsdMJ90@_A7BpeWeSX$;MlqqK!QPraua[p=5JasPnP^T#"7yf*w0eIWP42n+2"pWJ$naW/qI,z%NQOdR"D2eI
Eq7HqMBxkM=2v_jVCw!=jVEo^DThMW~%^@NgsyU"O,.u!1%Z}&>?X>CI@OfvC7M/Jv6]FW
-Yl/z)$<t.2Q&79J`[QyMbfiPOTb7<C!Uj4JqzE`2GoBq:@gNnBBGOB@)6-UG49y7!;Ys}`Q7w3ogIgn,{3OEcV*WlCJll,2#7F/+~CU2:Y]l.ybW9:)1Ptde
,g_=!wFNykrY?acAhabik[%rF=Kn;1P"wqKXq0Quw<7dH^Y{`Wu5sWhgf1Xk+|Ar7#h$X@pqhsA^H=tws[uZ^3(M7#K3y)]pCHf1AAxH
z]=,J+;#ehFxeW"75na]-;#r{G..:o&-Xevy&v1;y1$)h
essr{dD0R.fJ9H_&Zp]Vs`s4Bt@.7Fhr~FFv1B>[bV0T&JP0|Z]5UaYA]4<sqyrNU+y9skkqF0p1S.Y`V"J/6xZ3@_8E|v2UJR`DM9d7f3ZfC_B-<]yDW+L
.Xp_{,X6V8B[k+h!fN@v1CDx2kux4G0)H$Yq8p[hwC0jL9_xn%)6"j=GLFeJ4jG@OLo^r.=TZ<FT.[^;(%c3,y"AEAJD
(~CV3[>5IFs24)r-qr-pu0;qa"ac${swTAisI/nMlI/%Hyj]sd0.a@NJTPl4+UvGjI`Vj
wic>ED=/fF:{ISh#a3#gP(,,q78Bnae_1]nzt0b$1PV7+Hd;33ZR]aay#kuh.$7,TY(rG"cqPj=8wQuHu&![qKUwWRFL4a9s&0kuXYlkDe`sLaS?kR]tZgVaIV+09M9Sk@NCFxSN9)4SY$6zu"3FXoC}#FEsUM27hRjMB}YBrFvZ30J0o~DZ
$pF_eJNP&VKIY%mVl"JIY]63PEDhVms%(<bal35kV^+Vc6KwsnLZE+@P=1L]Yq9a2dM>1f;-$H66a,0FZro,L3-VwC(
aASa33j)yr~g/g5%}X;rB4cCZm=mn_~fa-At,q0>OfNAOUHI{4td;U)`0i@]HJ.)Dq
Xqu"g=h*%^5/hT=U0t_zQ:_+_W"rA8"Ypb!7B5-S${tmZ*men:ns^yBXo-B3aoK8?|UyrRHIt/+n1`iPdq6tnI>GBM=#
u"I;YW|<Y1OgBm5[s9k/4_~y4KI7eEb8tf)B-kHK{cZR,m-i)m}Zy`teJjzDuFfA,(Qy9=fjEU1`O+,A|]nm;UIx$3+^)vSP}V79h0$%wf/uGCXvx#Xa[f$B)Ye5ge=y+TU/<hCJg2hJ+*?@WH<a-y<F9DOV0P{"/%wLl;<muLc65+LHSl%v*,}oCTjyBZ_!LK6yR2TYH_%=Cwy)J!7;rTF[0s_l#_)S!uKvJ!"gy,
ViyJ9B7IA"be%q4`)HXa?$eDA*m,TPD7*jEZYu&r/s+i>6r0J
iu4p"@0zwq%PS=x;!P9
`#nHhxE39+W$@GnoCNmTC-8L8s!,A$0+aF".4x7i5Dq!#?U;v0c=MO%[V_*ZE
U+U`sY7eF4-"j1^iD&ShB@T|JvsBZJ`qj&-2SXK-9Aa0FU*CS=_}0W$-oHk4&n?YEqju;hy!"7k{Q:Eg9d66.X@LJ/];#M3#/a$L$SNm22ZNKx",s[ZPE[b2$X3;BteN:=?Hfc0_DY@xJF=%Mb?wSB@!8"S3z!xtM/[KpD.Yljr2*hYn(2WC9xx|:}T0HyOw[-3)fe!&5@-"aqT?F@;G.9#sV!>hWc_B$lR-QadfcCx2lhQ!4Kenntxg=;^8fq
JTy[ViNh}@{^iu0H%qrL>J_qUX#=Ya{c
X1hsY%@cH_>
pG7nBBs06.BOM5uptGiW
;neWZ;M_nz(XJQMckSn@-
{t6iW&8meefXSnQ]0l]77tPrh$(I$09o$9%mq`T$]r`A~_lI3>hsab}Xka"^z->IDKxOA2C(or_bs&=-9FoN%`@ROFfK]"tCxd!k_]96ZjuRWM0knmJs)^;ik55y%ByO!=+^l"r2*t58l[/.#P
C+6zets;b-_wB)K:TI37=T]my~HI:o(;#~_N%V]I<<;:H{nukVa]Jun~QN`KPtW8myFJBkR0CdX"Y/PxT8jJ6ZMR-HkU*B&8R+l*M@E]Ol[Dm#4{s0PTO|buuT%B$$8b#6H?K>u~c:?Hsj$[AjC/7sHY];(vw8TACc?h7{)*BEv3aKuGn!tiup[6I"SZ^+FYnSn;&lAMc=;(g7_I<Z!A!&OSRvQxY8`+d>N"*h@BDJG@hV=SbDu@b2pm@5EDo&xb5|!oP1f?hnq5yqiZdp*a5G6$hr;l^M2I86E1nzWC[KeQh/6{uZKg4!(K-L?D#956MTjP?g,**aZ`]J=~JnklaM+l2Jj~adYro1%ynkSQfPxWotPI0,G>Yj4zG
hu4|`nxasSjwNl#6F5mxtMa[T:6OJ%,d:
sZ&nAyr`X(yn$kvt3g(}=xoEU|^19>
aTym,#WPt!&dE*s0tp)4Bmh1yX4#I$DW&9+!+Bk!a][6VUP<,k!X*jBk&X)0Xq$[-]|Wh^N-?fkALFp[d11m)93FNkC>PDXwQ.`.oYipMp7ffQ7(t.hv2mXHEo,RV[n,meTW6GLP#D$7kCPW(08gjd:H&;0@G2o&[dN
d_<<1-N;P(WEB4I]sn=BzjPJx6~LHADd

gJ1y[8.jX]3PlILbuDDSG
4bGq5
0]P=8TEa!>cE-Z@R[Ehl#>lw$&G-Pc~%PQLIACR7q
D@W
>C-M*Uqle8VB|]]l)<
liJ~7r]S
jdyCMG*CnT|0&N"kWU/?3,E9Rl(L*^X>XW35lC&
XJ]OI0HH$!_j,A!^HJL[HFVL$e9Y#eS(t@943%_%xPC>hV:/`7[=1V%DnJ6^J!0HAol0[JYngyGQf8951d&J@!V]~`]6m@(g_i$*d7)`FTt9/wv],V>"xkS"__x>i:1fBU]og-[<?
(i6x$F~
i0d=z);ACPC6;FYZ].]i,whFdF+^;b(gtMEanVwJA4cjybHF+sj:yjdd0
2,eaujR.h;loVgZmHV?[>t9X4I8AsQlmd6We]qV?dFp(RJIS>cVN4BZOM;BnHY1C/:^r1&NyebeA$G%j
8Z,,/6KPX1hNjPd%/?fC"^9E90f**Zc&Y*)20OsFvG<&otEi%)-Lh=t?wM-YjJSE"Q$NdfXIKF3l
;UZRrm>?!PhmYaK1)iHvs2=;*Y9C(*[FiG<K]L2<,FX_bM9;=<iqxA+.c0JQ.)ST}h-r]n3wxEPVi7,c(>_!Y5XAeRKL5Xv.3<LC[oY7HKiWouX#b8gxL**VHA>;wuH%l>aAXxl=824g_?KD.?tst^TYLoxyx]md_
UoskVB~P1.qCOJRu0?4^XaIi4W|00&W$E_8WqXtajIIaQ=Yp$A|Gl[C]vZMa0s_wxpgrEFu:wJL0q6%
+rq-(ieQJSrmLN3:J/_L8l5z"f4K@$i48Ji^CVQEq#Z1
&=&0N=*>3C(a@
c.rBpg;q*PAhb!Dp8e/U#9!9*<B,U?y<h9.f^9P)e
Xs5R@rb9+rO%7h@aGUnAXX)VCfHzLfT1"VI}jnn?.n%}
m0t)L?3qPc%o!HFXV_JFK@4J"h5D#BC5I^B71CKji_DWN5h]GxIEMcl>xdh-`@VG!T:P
h~.(oZ]iU)FmGK<i+>iQYD.;2J@|s/r`&]kZ#YX(@k+vB.I<4F^`OQxq-IB
y9]]!ukLM.LE89qF>B2d$uj)B_(p?UOc1`8+1J[BRX6l2:E)u~=C*`DCfrqAG>#|5NRD2u7qMn
c=Oh`e$cu:C1"Ll;:3^j}-]xDwJSb+3AGs!hsk|o<C/19>h+KsE2zrX#RA~pniye"Rm_!kpu6Ro]9LOIp`So?Y&oGt_SuI_hW2<%LQNt_(xSOxy3w7lX06g:r0;3A[`(orMWD<,[*K^Ih?3(z)^Vg#"Qr:x`)iZEFP/A3dDH_9r%~]2efV4V*0jn<y?<$1=KCHE@&Nm?o:-*IKN$*B1`uWFJzfj&^RnS[yBjGn
HcX+z(B5ogfj9y,2&~m5fJTtg<yA#TAj<_F;pNP3WU
1_ffd>tqOL_Z2BQ]4GWCWSB&-;3wPYSEyNRPxT5jX!/``x<<Nx;>:F3(]J/r{3@N$*AEQFjkRE!579#I5LKSo@W4-b:Wt(_D$woC2>f+wsnJ#UMqSgZ[a#Zlhk{O*7x
CLGacQG3!W7&itUlOe)&wO
EOyl
@jXIixpX"AmvwM4vyjriKxr%Ad)mr!<2x7GLaEUJu`09mv="Dg)^D
ro,D!kb0h$D0EZMmZ/9F[Y5r>)<+|#c(zWMc`71RjtSr($AnIy?.l5939Fq`Mujg;LUI+TVn,hHww*U>k0[)ED=F}$fSASe,$R`9[t9NzqOX8h-q/AA4a<y`atqOt"Q3l6;`PYS%>x7(=pA[8s=U_0ZTFur8@%/H8jc[:<Ath8IWX1nYIhegr>d0UwrAC,eW"Yg7ET7@/"`6pWSKa&]>qkD(StU_PU&9ANo%IjN;n$z/w%^D5Rh@/-,0]W
(X^PYKou!<t~d?t#aHc3rXMH=/5G=aF"rH;:]l;,:}_I3?LjYHxm[142L|E)c;yv36xGUis);RMz+|x+%aS;^h/?q6ihNNcQIiw*Jd.#P>[r
:Pdw!^t&lNP@685V^VCM7BwPUbw*R>>
kSawRcYJ}5W>#SBP{4_CBnxy:_(emHG.-*NXGPHt@GPqS%_5~S!$17/DZ
O!.Zb@l*O,l?hUMc}o,;c8"SCQea0a>i[/sfDoo=h@fAUWsIbV+Sd7bJ&/.SfnNlS+d1~M9oZR@-`$$@;C<S4/%2e,cLR#|x/@M!mR{px^hIgUy7uw
.$lr({?
H%*./)Aq-UpWY3PDuxMK?Qwee;DUl-V23@dc)beYWYrW*uLG>B/aJ.ZtTFl0A@WUTCX%M52JtJuF0x,,+2BxT9&WF5fYZ).y"9?ZO1[DhoSBRN<E:)6AZos.T_DZ,7+0HHQgyv8ssR]K-{1HP+p5Ud<Q6Rsg;{>v`1FF0f1o&E;$:{*kGSdp^qqoLj1iB{k/7I9)Zy*)A@D(:FyDi#1{J>t8v?E^hWjQ0LSVR_/Z2PZ!"R6]a[_G0Ev-0YA1"w^?T"jWhZ<x(@Y2mw
7iA:&*:MwXm*wMH&mpMkQhSvCv|iHKck8:{hkS~fw_?68:U4Y?v19AQt^x>a:5b/]C>5u8b&_Hoyb?>ozQ9"Bz%?zi337QqbnV,OA.[I=Lf%d^k.l,AIzet*s=mK&/E6&m=Zuk~IZwQYvZ$,
Osr"[kq#L;eH@CJbC-;d!iT`KX4<S~^H.a7#
BT<2sT&%W%32x[#rt(*yT:2#l:w`U"rHL0JgfxlbAJ50vuV5nv{U*1J<a.|Fh2I,Sc)fH4VY(Bnw`>lU1]8GD56FS.GGcLj!|%(IKtpV1tr1qNV]IdN[s>+

SC^$CXM{sZ(M>)a!au)qO!A>Y@1@IC:WxS[HhgTybsP#v|f2er<w@]=V68CiC?mfX+=*Lwb7>B5k+Nx6!Z3SA.@M6A92u[]$&b+2uE6%H_VJkdO@BsY>7ix8G.HHuSo7mfn?n`^)twRZ(Fgrz)8e$7ht]|1E]]O`mUG^
bS(mhcUmowbKWsoV5x%g!%zB,+:1M>;jno<eDI0&&:!<rR53Z$&>x5pI/<rFf.d;=,Di"VvB
m}.$N^,O
$h9RXU,@9O7Q.2=v@kf)gO*s$6o6yrkFQftfxv4_{Ev7w&(":D(-)9$QZ?Qw7o
wd^-tK4[Z$xec4N/eW,!?O>{&O;Xgbtz"sSRcX)kub++q3ahgFcL0xGPOCD_DMg{bjQHg8r~1VBLEY/|gVK,>RN9@1[{UcX@8%UwL"/<3Ys{__d(CyM,qSP%UI9oxz2?->3uXhi]j:Xc8Dh~N[J2bfZq%Q(gLJ*MVT&nD)`@qax5>N$082%D7<NwE^>eqUb8U{BqpN!k;kv&[1*cwAb>/apmB1$i`d7n2S
P$
rc1mKkfxSX>?FkCll]K~tHbY>Ue|D#XZ]=5pak>CI>&G1#Dw]`Gs3EEQM$3"wt`C*Wp&R;V^k7`!=XC=K0jlv0U^VNCt`?AF+Mww!^3>vmfHDnK~^N].Y:*{nms?;;iQhQ5j0%DZxM;"FsSXvZ
}a2/
dg_"b`KFqsf6"4v`3qIdRJ<E6CJW+awz;ovx$S1M!+9{:QmlLQ]b5V%1Nd?_Hv5Wf~C]qkZ/6v%O<e!A8)U]8IodHMU2.YHoM_Y_.][Hu`&nyFi|X#[+?c(UAA:-e}2xy#E]$38
Iys%LBqoG7GK<aqn1Vx9lX,#mjVjQ@gnKr+y+!cJA/%hkiu|n.!dG{OvSpG
!E@"_4C.2"x
Y_p"ods&RIr
-gQ8vK`h7RWNJ[?aL?g(-%l2T,
/.VxgPxTu8ox*oh1yQ.6x`J>/=sOnxBOsM>l+x;pscSh"&be"uXS8:
`!MSl_xV]51Xb4tv17(JdXp4w5O@"^A<^BO`s81BcD7
j(21?pX.]q^Yhm=UlWobqd?+
QC">(5;t]y@Y{B/&-tqfA>N$?#d?v9Zr0wt0^Lf"VPn`.9J=]AXv[AJ<!r1"K]_gQ-~*
(;l}Y9`Lh`gEggA(,|.!!";ycr/EWV.3jx=s=y%vSmO]tjF%vG<~qf]0y>ASZR]/<XbE7VxUX{KK>Y7(m.&R=bqGsltW9"KEyg^rOGKM_G$2b2%fB}V")8n(
MNOK:@Py[*ud
Z^Zsf60QjN<
+Z+&n<HxG+y&o"L+Fz/;^Gt,k$JcF%8CY4[X$WktM2f0c}RChnmFOd"$AIu@*dP6wT-Q$5iH`?#9=B#9mnO@`+;5wKavtO9<b(!rL]vr?(EvvvcDy)q`t6xRU:f+65+LO&t?"clls)]zq~l9pw6{LuC;kH9ey~ux[bIAyU6UoTpXZ&OPWJvZbQ^/gPsF1m+Mb%Zjy}OLpt@?t
;ka.^=J9Jsk[bDV<kD4~pK(Kuvl~d7!1>,<;yM6|k**rsCv"UUsshoEvOtWIV~R=^BSnuGl}GhStH{@/su6?6T]xf)M^[Te(t(M=qwnZ9U1DWZMRyEr=#z)a-G7etjPK_3,gdSqMJEG(MGD)q)(<mv4K5*8jYE#Y?=uVXpL`8I5I-?/T`.t_4zjvW8:M`LwTloVfM#8ft`0WQ](~av71+!w>L?dAl3LgBKMNAlw4lm2
*mS|nhl>hsBsB)M.%p/Jh:RCkFgFl{rpxdMzkut6lz9bi_xSMC@vpjBAZuL.T+v|l?&j98YN[U?*VLg$3#aADVN*w;-@V6^)$;*:1+EOn&69[8]O[n3?oOOAVMcYAp#m$j<o47tgd8
F$,p2c2D"wP<OWmpv_)[O)V[Y>-r3*rcBS&;1c>l9tIvzB*``-2_}avXk8$@r-Z/
Gca_1983FAYAPXC3Ey_~-Y-z_.xkC|`itEBt;`6"7O.v,.tYYG
QTB9l`k6:BTXkJ6xed-hlxti<s<a_&R!n(5K@R!M(
Icb2(e(7Ie]&Cr%^U-6l!`vd.FTW_mZg,V6@#l!Z:l}7
Dv[obz=jQ]S?+5)ysF:<!E=V2$afY2I/sdX7P]txEL1"hOvW7`v2SDA;uZdad?jsK8l7b&bT%RN;E*Md]dN"tKw3tc6FXoaBeg1}d"7;l]Mj^NW]CNjkz&EnToSIG@U<C",CtaG1Ulw!i9PwgfA<Z{Xm!E4U9[t&78x~i2,wK9/.mQ#K!-7K)j$X%u)c3lwLo4`usP^/h
@:4WlIT(+yU,<?q(*$nESo(wm^U4n+"TI|$iu5WBFP:b-XCY7(?1d,NEWAw@N&-yN!taMO0v"Ijm7@S0NG#M&i5=_p2;qRAWc"+c!qm"iW7L3,N(fyeT#-S(rR5:9vu}?OtSYeBF4tNm?gU-bK2$*.ht/*8G47q,tAQ*I%nqmXIh-k/nQ+uH-Iq-1Ax1U!O6o=@i;<>FbvwW()kex_p%AEMJ"}uGN*,vplSM=ube$
l~8dW
+TKcup#A/c"lD>-!GFw!K^.>.CI+C@eT"2bt%<%Poj5S%a(<"2
A
1*B-9P}u#d+af3h!23U8d6RCuUWEmgCHv,tMpCE)W4HD}X#b#KKyI6fp)HUqyW9go$+39*Dan*N&Q!Nu2!IDny&#hw*<@$H9nu%FX8&:^2C8L$"#V$^2T#u$X#D5[5CdqF!K]1x,8*)Y>?;`oHX.CS().T_8T?K#JQ!*QKS$k)c"
`0RtN!=#Lwfvu;wKR0$c2G=eBU
hZH="LvTZQ$1SO!!#RVb2DT.Op=o{T|T7prM[Or"S@,0)yPPusf-*g)v~0^+.F~yWMf&toRf:Npk5[0#^[KVz;1C*y`Si<A_u"AKFTj"a:hPXAhs47S!EH}4lD%@tPwVBCbL
iv"J,ts[+
P
yht*%n"`V1gs+Xxeu
*a0T6
QG+WZ#xsIG.<$w[qS~/)[NS5hJ&NlTv^-"Wn>gKyY6Siw{PlKzek^ThK[[ShL^%hP>H8E)3hGg8WUSq[0L*2*qC?v*.{ejXeXm8V-&jQ,)lI25)t9Ptd/8#NqNlBVqyHufl@)hj.3/(g#F?%N|1Ec.;|8
>6Zq*t(rZ#)GW&2(jFbZ!-e<1b3VrP$NRD/GZfZHv5WxZ]Mel9%?:ndAQ5cc&hM?_w%O&`8ioNdpcE6J1ySU/2dbq6y<J"jiS$uo30i(]itpmr,Mj+XNP%TESVRpU)79_*b-$.4+BtTMG6
![9*1>eQ{uU9`]fQ3(>1`QG%{9,S1#]b)>aKB/+gi5g#=^FRh*WO1EvoAFxUjM@/iG4Z?<MdF.CR9Xy0so-aHD_cEx.%(%-CJ1s@FH{0x!>u*tdLrR7+):[0[e>.eV.L,k)N_q+7lXX0hM9<["SN3V.@;Pd9s:2X[vs+w-nGa=MAo`+y)6T-Pn~3(%-]v]BNb3EF$0Tt!Jco|k>Kx^*EO"K#T3+"%oWQ1EPLV4
*H7HE=jaBq.!c%Z9xu]tZQ`
Z&OiBToUTpooMje5]/Mt]NeMy_"M
9kx-
([Smt=oDY-&^w)PV/LX2.]m+e,u^Svb
(v:*?1tu1Ra
.yU*w^haf=!~[R*A/ktdva=[YU7h@6F(<R
ACSk"tj9ViM93OfN-wm#%Me3p$gNjr*2OG
T^O=I4w9snT`uBS(o,"6I,4Ht&!oU>u3K]Bl<@8.EvuB,MU?<(_))+:T>M7DTJI$9%E?Fh<_3A
(iQQY2$UE[p6Ubo8t*k[$kUtjOQd/$Z5{9o*?o,V@`ck!SQpT9+
38OJ
",U<*_xbqXPzx/$L";#PS$&;^Zo%MQZB,Hg6xWH{U#yP#TY3H_e>KOy.gT84fZfFK_NAON7A>>5.d}yY=r(IWsNDq61dV/^D=/fO&(,%EN#k#c3WOqQXYfi/sp-oqW(YQ<TJL=I4@Tcq?4.*;[(It$4UZHTQD]V2D`PXTQ.:_Va$6&fDHBb.gFQkaG`OM{*:');}elseif($_GET["file"]=="logo.png"){header("Content-Type: image/png");echo
base64_decode('iVBORw0KGgoAAAANSUhEUgAAADkAAAA5BAMAAAB+Np62AAAAMFBMVEUAAACDl60rTnZZdJNziaOerr60vszI0tr8jZH8c3X8SUr309T8Ly78Bgf8r7H6/PpDBKXXAAAAAXRSTlMAQObYZgAAAAlwSFlzAAALEwAACxMBAJqcGAAAAbRJREFUOI3VlM1OwkAQx/sGG0Xh7GwTz7b1AaRwNhqIRy4kPRKjpcc+geEJDHc1chYPfYJ6N7I+gJFQE+UjJIyzS6FqqzeN/A/dtr/Mzsx/PzRtlYSI0fd0Ju5+wDMhHjCTMIqaXoS9QWYw3iLlvRHtLMrwKqDnNLyM4m+lReizCOjXWCgqWdPzvLgJNgnvUGNPV6IVyc7cim2SrHKDMMN+L6DhTKgBDVhqCyPWFW3KwfpqwEOAXUembeYAtn0W3ssErN+RdbxBOcBYowrU2Di8VrEdWcQrx0QjqGlx3m5LUThK4DFRNhGy5lkwp2CVHZ9Qs2ICUY1cGmiUfj7zOnBTyYAdo6a8otjzR0X1UT3uSc97kiqfFzPrMqM39woVZcoUTOhCin7QL1IoJLAOKcrniyCXwUhRboBplTYPSrYJPJ3XLS6Wd8fJqmrqVm2r6vxtvz9T3kigm3bDzPvxxqmn3QDg1l7VcasbtgEpqg+X2133ixlVuTky0Sw7/8eNF+4ncPi1oyFYy4Pk2tz/TPFELrt0w6aX/S93FMPT5OwXUvcbnQl3rWTT1nIy78akqjRbPb0DRTX3Uyvxl2MAAAAASUVORK5CYII=');}exit;}if(preg_match('~^/[-\w.]~',$_SERVER["HTTP_X_FORWARDED_PREFIX"]))$_SERVER["REQUEST_URI"]=$_SERVER["HTTP_X_FORWARDED_PREFIX"].$_SERVER["REQUEST_URI"];define('Adminer\HTTPS',($_SERVER["HTTPS"]&&strcasecmp($_SERVER["HTTPS"],"off"))||ini_bool("session.cookie_secure"));ini_set("session.use_trans_sid",'0');ini_set("arg_separator.output","&");if(!defined("SID")){session_cache_limiter("");session_name("adminer_sid");session_set_cookie_params(0,cookie_path(),"",HTTPS,true);session_start();}if(function_exists("get_magic_quotes_gpc")&&get_magic_quotes_gpc()){$_GET=remove_slashes($_GET,$ad);$_POST=remove_slashes($_POST,$ad);$_COOKIE=remove_slashes($_COOKIE,$ad);}if(function_exists("get_magic_quotes_runtime")&&get_magic_quotes_runtime())set_magic_quotes_runtime(false);if(function_exists('set_time_limit'))set_time_limit(0);ini_set("precision",'16');function
lang($u,$E=null){$ta=func_get_args();$ta[0]=Lang::$translations[$u]?:$u;return
call_user_func_array('Adminer\lang_format',$ta);}function
lang_format($Si,$E=null){if(is_array($Si)){$H=($E==1?0:(LANG=='cs'||LANG=='sk'?($E&&$E<5?1:2):(LANG=='fr'?(!$E?0:1):(LANG=='pl'?($E%10>1&&$E%10<5&&$E/10%10!=1?1:2):(LANG=='sl'?($E%100==1?0:($E%100==2?1:($E%100==3||$E%100==4?2:3))):(LANG=='lt'?($E%10==1&&$E%100!=11?0:($E%10>1&&$E/10%10!=1?1:2)):(LANG=='lv'?($E%10==1&&$E%100!=11?0:($E?1:2)):(in_array(LANG,array('bs','hr','ru','sr','uk'))?($E%10==1&&$E%100!=11?0:($E%10>1&&$E%10<5&&$E/10%10!=1?1:2)):1))))))));$Si=$Si[$H];}$Si=str_replace("'",'’',$Si);$ta=func_get_args();array_shift($ta);$kd=str_replace("%d","%s",$Si);if($kd!=$Si)$ta[0]=format_number($E);return
vsprintf($kd,$ta);}function
langs(){return
array('en'=>'English','id'=>'Bahasa Indonesia','ms'=>'Bahasa Melayu','bs'=>'Bosanski','ca'=>'Català','cs'=>'Čeština','da'=>'Dansk','de'=>'Deutsch','et'=>'Eesti','es'=>'Español','fr'=>'Français','gl'=>'Galego','hr'=>'Hrvatski','it'=>'Italiano','lv'=>'Latviešu','lt'=>'Lietuvių','ro'=>'Limba Română','hu'=>'Magyar','nl'=>'Nederlands','no'=>'Norsk','uz'=>'Oʻzbekcha','pl'=>'Polski','pt'=>'Português','pt-br'=>'Português (Brazil)','sk'=>'Slovenčina','sl'=>'Slovenski','fi'=>'Suomi','sv'=>'Svenska','vi'=>'Tiếng Việt','tr'=>'Türkçe','bg'=>'Български','el'=>'Ελληνικά','ru'=>'Русский','sr'=>'Српски','uk'=>'Українська','he'=>'עברית','ar'=>'العربية','fa'=>'فارسی','hi'=>'हिन्दी','bn'=>'বাংলা','ta'=>'த‌மிழ்','th'=>'ภาษาไทย','ka'=>'ქართული','ja'=>'日本語','zh'=>'简体中文','zh-tw'=>'繁體中文','ko'=>'한국어',);}function
switch_lang(){echo"<form action='' method='post'>\n<div id='lang'>","<label>".lang(23).": ".html_select("lang",langs(),LANG,on('change','formSubmit'))."</label>"," <input type='submit' value='".lang(24)."' class='hidden'>\n",input_token(),"</div>\n</form>\n";}if(isset($_POST["lang"])&&verify_token()){cookie("adminer_lang",$_POST["lang"]);$_SESSION["lang"]=$_POST["lang"];redirect(remove_from_uri());}$aa="en";if(idx(langs(),$_COOKIE["adminer_lang"])){cookie("adminer_lang",$_COOKIE["adminer_lang"]);$aa=$_COOKIE["adminer_lang"];}elseif(idx(langs(),$_SESSION["lang"]))$aa=$_SESSION["lang"];else{$ea=array();preg_match_all('~([-a-z]+)(;q=([0-9.]+))?~',str_replace("_","-",strtolower($_SERVER["HTTP_ACCEPT_LANGUAGE"])),$Ye,PREG_SET_ORDER);foreach($Ye
as$B)$ea[$B[1]]=(isset($B[3])?$B[3]:1);arsort($ea);foreach($ea
as$y=>$dh){if(idx(langs(),$y)){$aa=$y;break;}$y=preg_replace('~-.*~','',$y);if(!isset($ea[$y])&&idx(langs(),$y)){$aa=$y;break;}}}define('Adminer\LANG',$aa);class
Lang{static$translations;}Lang::$translations=(array)$_SESSION["translations"];if($_SESSION["translations_version"]!=LANG.
3168626050){Lang::$translations=array();$_SESSION["translations_version"]=LANG.
3168626050;}if(!Lang::$translations){Lang::$translations=get_translations(LANG);$_SESSION["translations"]=Lang::$translations;}function
get_compressed($De){switch($De){case"en":return'+X/+JaQAP*41^o;dh84@^`.AE6lo^ZM]4Ov%jS#sJnO.@arYZrVmOT%j9*aMLPh_(gF.QsN@t[0tp<^Fi^{k:5Ex
l4GhIy;HIxxM2-gk[Hb5UVTcJ%C;[<[!jPb!Au
z)MP>m]MJ(()3?lq-UC5-n{=p>ZJ(M(FaUEPOLU-NhGedq}d>hMoi<@Hc_~as([1#Sog(I*-Eh=3s6xz(xC.#N%JtbAyRa/5c)uw56OCKbTt8e/,Wnw6LU.)YM=7*2gvh#O?A
RKwD4]`KRGDT4k`v{*[!U82`1ZA6GX|2#&mA(9)bGs.O"#/0d]L@PCQ-1j&Yi&[_k,u1aU@_k3X$M[A/8>VnD8OBy3>DEN%x]!H+!`cG^e?y2mSZwPf]_fwcle3n?g<`zwyAOZx-{DdxfHnw|PyQe-@_o,8+oY.yUF_<1^f.%7)GW*t+IX"f$*oc]O>f[ks
y_"?cWVorlGMt3Lm1lmZW_ucJ*$R:ZjwiZ3A)+]Gb!=kR,K6@D(kN5X!=Xc0g24w?pF_FRAXgW_c`M70VojGN^Ovrv-b[s3J]G*nl%90x*`(.iHt/2>2SI"p/pa@BsmX%x(3("dAmQUF-&Ain7x0mQ%tDWgRQk?u=i{*r]oVFy|;Ld
-F,79sl`&IpC,=N~A`YSuLffDvvHYXcN/Cddq$!wtd`@+5%rB1G+dv1T<(n!sDm@Z>A7FLQ8=p;zP+KSK1N/2mOR)rH?lQ"P@=5Qlw01[wY7v:D>e5Sf%5oXGb!asG<fS,wt%4+XZodl<xPb5N#SBdk=]^eRtp,L$MMf:;)]-z@clC5L`?aPB1Y&.^02=>iKc&*ie4SpNfQP9{*,+ZUm%*
Lm7yiePIV5r/An7Y.2V9739Rx@+q*oc"MCR")gTM!L_:<ad/vXVICg^4wJu/jWZ:%8)rOOK%tP0s8Nue4^>p8*=(;H$[J"<i#aPTM1*Q&n1Q8N]9RTkvm09(3ThXL3p@Y]G+*4#EPW&9<!D8*l
:{c5kup7cDLXBn`oUY`>(a+Ip>"$tQ&M0_)eQ*i`/)@_MLCN>]*jc#c4>jvU,b9c87d(V5eX#bTGpd+yX%9b2Y8>Ie+NIu(^b1bzM!1=A8e?fi!)F`[~%KtReWd5.);.WL558N&/6k#2qm"RINWeh4BT#bEL5m".
UGX-A5lDK<9-e[nk&<EhvB*Uq-4`CvD0l/BvN"d@#p|AIM#M*oZu,!Kj>gHUK0t0)VG8<*kNDrr1e0;:/9i`@T<P1=@!_WzsQS8sWlPqY%eU<V2
TN[TZflVE6]aLFADHgH<4yofS0N-V)(MZtEj}+.mK?R$FXDKvG2X_`gJPVILS5~:yH2lP/aN#G!n6RLIR@rf)SzG;4t0s,@M)UpMrk`+wV_nc&ZYp.9>6#?FQGzEu<IKm>J2RXWXbUD%;BeVe8Z=w+9bYO-URWC`EaE;A:/1;_[#RgQkC`z;TcFT}n<<B^Iwln{vVaZG0:TrqB>G`9f:4B"f_t
-fx
T%o!ubILU@BdGtclm1K;P:rNTF0~elv@5wEr)JghK/X1*(_&ZgQ=Z4cc(3&B1L?loynT)SQz0(>8-Ze^x$WNwby_wWrc4hol(uKMbdV?4p6*a*iq+sE2sgg;+#)@iw0QRHFDN)8.Q49R";>f@^-1"eL|&.w<8yZ!W6
f9FY.f&95"8dDCx3{)Bipm9f!4k$]H1o2I+!0Fq+8+8`bIX(fu/aNodOnZ
+CP1kK*;>=r9R*]Gfxy}1Gfh1,Sq.BOJ7Q5I!.hg)4Fb/Y-k7;4?P:6%;5A)rd!9u`*wLM9*5My[A5t|Xx*2T*mdwoPV<1x3uv-}"e]jE<p(Y&b&wsGu9~5di4hKI%rIjx7hR$E
1t*8J=CCDMk(Zf3}`!+T5h!tyX&+[#OSS[;xwCHIjVb}iqR2q
w.3f!Xo+H[PWU$l|K!5NWR%yj%N-hG3.e?c}sA/Wl(6mZ5W#rTI^TTx]VI+/L}YV%":!2]f6qB6rq7fzx+B3LaCVl,yXdpxq3W!VY5"fAXUy9KK"mhs~tA"sL)sy$!Ex@4ser8@P)_Glg};ORW+?g+b0#Ed36D%uuqt2[kiQtJW?S>f"wm0ZV"seAyL|5#B-d!n^r,>IUAr+!u5q@m4<TQuuF3V{C3w8EQNBjH.yrw(aCe>*x](E.iWQ-Jin.^03.E2xE!IkTL(Q6nx_%5j&6u=zA(-iLD2%!5pkxrEOuGiRVQ>Z(CG6Z2yV<VZI01W:I?"*cwZI+r5cI0BI(<v!J&C&4W"3!,2ru!qX1Te;TXYm1IGId2&Kt>qMMA:*FjrOL1;?Lem1xI`_Z/k-b7[$w|ijjtDO%KD(uiT1$11h-KL566)wl7)l]mC@@>5.rSXa[]Km*|]&iPJCjx;<iFh8ac=v.-(wp8+w[qs7:"RIl6=?]_8=G{le5
A_*Fca0Hd:h``Tszd_)hFgC:D^s/R,trHG[*Kk6d87h&^r61(,1*A3L0P.96ecWS=lrxFO`Thcr8fK$,]b/j2MTx[M%B1=X^=*B$FV3@U36+e5s,*;O32GR]n%
EfbgQ1RC]w+Aog4hGyuO/7*ljr6V)7!r*;?SB7qgBKzaBIUMFtX';case"id":return'.]^@AbP+>&)kgn,<]lKpngKPn4$.NxAZ1
Z=0["A7-tP",st7WD1Yp{+q&b6vTU((.Vq<ct,FKk`9iZM`KJP(JWsyd:<d,:-tZclayo/Xuie{Y/v}OHj/"_!H#{k3jwup"SD4JG%.Nf+[iDCZn(O<LM6K)1sruN:WA7t}*-scY^EA2p#km5OTBUa%!:OXQ?"m*e8A^6Pgx(g(4O[%1/"__bFs3.dqQ_b]H5KU=qE[qJg
U!n`OG:~yxB`0&&9#`3Z6)bLehy4o7,ed]YG5SyB)gX=r9*b@3&Ipf[E6MCXpq0`YVpN[]l)ngap/X6Ct$cFF!LcrPZY4xsp5yMu$*MFL&tc_pYZ/wj
[g0`MsQYtP#<bU2Eb=A9S0a
Ry]cFn
#m[x&V>2L+v8ZWR>3o@Z4
rP3sR>qSe[g10kvRw%.qSVz$<]Vf]P<t%)UE2PS-:uWm>/}_6nWPt7q]
;cs%m*ZWM{>vhjP,`KY
j5B9Mt,O>$91(l8BQWVCax
enI<ac@p=#^mG6QOef}Yw&1-u03SCKL(4`BCa]:>Ws=cMeqcrruc&n.6vg&uI[eh>^uC00&L.7bKH-M)o7`M-u?3@Xv+E?NZis&JE,B62fAhz2dd*9?*Pd,xem"3J&JKFN7Ek3&Q@D:(F0x*UJS$4m$a*$<[I)=[6T{R+vuxG+=&d%<jA1}yxD!3jYY.@:>YY(--H3z)A8zh)-[SM;Z`@&%mP>II|AxleTHt|HBlD)"&s-rF.a7I>&ZUQO39$a.E{``uK?4g$ij-s
QD$UA@_V*A6?J7J
]kA@Zx+j~>eM-VM"rbXR/NUO;JziM:r%khkK,L9;H49f4l;W:,X=WP0IvyRo9dz&U1fs}*tqBP=C/>`pOxmmZ&8&=Ui^)GB9)+o"4kdk;S7_bwj&&7gt]yp)#(2#-MBN9iADoBV+xns*uAcU37LO7Bmjk>dY$.OUOtO_,FH_u%ArN1s8/o?i[1"ZK6mO&W`s+D[%aXyZ&Iy<3^@5onsB=
/Gu<r(X[>9$K#&/8b.Cfo)x>Ip~7U)l`LE-9GZy

./DB-s@
+U*L)SLG?>A&TY;3+S^-L5s5Ynj3b"9AjKcC,G-((PQ?]{,_%9q.iz>x?EI18$R/&(X[KUj~42Zo=V%?"OfKCC6f?N6}XfEP=J%"QKomy1Y4#ELj1M#I/~qwTX+}okHmi$FFT|N>b!hjATHc44C9NDC0sHk-4P8Sot3z]?QMwUS+oI82<,@v#3tyNi,{y=+f(#
S*h]I=B"*HN-xVnv^+HUPZ@AQ)@q`$$/:=I9[&(g@Ug9"*SQw7@e?gD^k*T^;R:%p#A.-]_)ukI[PMtJ+6"AYO^yP4-@AO)F|2QY&<YT=[/@G*
*h+9jSHFV_O!F"O"f|sITWf#F^:*VmM?EwPX>H.cPvJS>@DF!8Uj]"Ej#>CwC/slvs&pIXQ7b:hdF(S-i:-dt-gdnQ)Aefoo7Q!FZv#a4%%ov5/^L$tV<2xr%<AA
Yx&I)(0"+qyRBOSoD,q"2M~7[,NMn6KkkR}+g+f8bc<MEA
i;KhdUW>P)]TdJNr%z?<[#/t%^M#NmE0Cs#
1~>[8JM6-bHf*;U[9K7-)q!$C/ClAxFeKJy{hj)M1p[I*iFxv0G%[+lMW`2@C@F;;C7Aw#lIL<dx*Zo"O8Dsi57sDiln?ReUwuZ
0-y68[K[u2Kj#/0W$L+SFN4{@Bsyy@cT3N`zP&@=hP,^EoURD%b{o^hk_h*@T.6"j%NM3~8jG(<K9fQ#Zku}OP57yo!dL8Gm`j50K}xGNSn/D`w{Z<`+OMvi$vp5#siW!6-PV]e#nnNN2aH}oP)C7uV8Yv
AB18*Lu
Ku^vNju
^lgES6ZP]RV8>HfnCBe?bX<i1i1ZxmBm]y)C9;%UHv9jP>:(%%5hp$6%0#-RJNoi]vI8pgV?mB1mU#YM:9ChxV%e3d34C?E7v>)c{48i+@(u]p3ARbe;p3Tc,qJ3k7Q79@v8<#UFbU7c}EH&;b-x*;NS
y$Q;_a)0pvQZ7Eld"TK4K"S^6yD&`3v6RBd:/l8Qa]b$YuezXpwiIy(&aREqg@OUkk`CvjZIq61U<d[E7*fLq
ME4c[PomcyY+G8+/hn

N,fB39p&cXgJg:Ccm(E&bR4?dm(r*0?}&a3K3+>NZZyG<D),ark_VXGM.mAAgFT6X5W6u3<Iahw6;s[stqhU$#JZ5t!vCltCA.1F6<2(DuM(R:>H5q.@cR#6fzqu*6Hp0vTd8}d)z"R8fduDxO;"dq-,D9fus1L@W4hCN&';case"ms":return'(h_@B6KWB&)kgn,;:v>yWA:Z%D4.L+kj5c/<bFWFo#O@iV<y~E3f8V8T^WAA=Yiae0::LI3x#QGG/,KaCP,#LegSW.G0P=C6]u>&(?@u{MaK=(#nf:|,E00tysn"CV$z$SpSU]#-bS`Q>i;(15%SXu*s]&dVC/T@mFK.q,B
nXIT(?RJ.SR3g
Rq:A1K.R,pH<3^707eb9,tyI|]c[+9f,Vk}YU4{g74*q*0b.QMj)w=*b$Tx3/i3"zol9,MY`:_YNa_Hht.
"pd`4$R2K%G9X<@c>Yu.`yoaH%xXRRU0&?7eTh46,bB
M_4EGn3u>8yg<a;o/txfI*O,4v&
)tg"wL4PKcsfBak}$dP,pn+~&[YceYpE41Vw>]C9q0nW8~+2JdxAbntHnj3kvwhz9+EW#Q8)i-s`TsN@$GST>Ni-z"j[1#Ae-C`ddz22-!V&P7mv2A?,0uPm^(?]`h_~>RCx6b:0.|E/17#Z_U9Turv1S$
RYz,GPqyI_"aOhKy!N!CO=N5Rw#:`0Jf(NxD`N!VRJnJ;DYZ1<
+d+!t]n*@J2@1bSmA-0U_Sy|Q"(.nh2{$m@$T-2?v<i@:u8WV=4NF.M~yLdEb;[5#/&9#exEX1
.J[c]v{Z
HhYfs0o_[Pi-gVA}&-0>;:5^"?l"9,6Z#CdLizGZ!l/lPI3mh{Z.D<"mgXYsA8WF4`)}Nthq1SCs/z5W)#cI`NlNIku%f]BJo}h5VX9FPf#nHI5O6^Pm,8#&P_Fw1Z#`v@;=P_Qv$O5_9&yq$+!/%iwb/A5pG!3v/F/"Y`7KM(W=#K*fjL-,<9b]s=vth/n9PObqJ.
^hV.(NkH<yf^eoQ3=UD1^Ng9/5Y)wc{)QvyJdDsfnUA#;Q$dzQa398r?x.h+C[*ppA_hUov(dQx=l=)<HMaiTBV#EM{^gv{*tbr<7m/RkWsgE]Y=["Go7SMiqJC.-cAV@
|83`-<[YT[(m1,(F]Eb-9e9X+vh"#.LP2`10psbi-3`+RB%&:<oBjx.a%a6X
vU(:cz1K[NX[N=0y^^"e[r=+qBOf8DQJxy5d!cf.R$lXFD]`]cwUH6"~O>Bi;eclk#V"f>kA9R$M`~Pr!W3
W<;-,84|RSiD;g/5v^%"tXd(F3q[`zy@3Mvg`u>s:a[^-k_zf+NT!h]VGJl%%|"2"cA#DTkz[$cz/Z$Ho#^gANYxXDl<
GNv
pKQ@D^]H%vm><Y@!!oo%k
5TPx?jAVXIEe;!<rYP!1Zy1qQ&zYC><qQ7yjgnMrEj#g%>k2_L/xc15Bu-cVe=Xto_k59U~gtRmLZ:WB!V<+JMI5MrDQ3j:!Vm9Kqg6/=
rkvWH+styDR^G%ClQ!tb$t"I_1Fc!>wa2@|g$Y)Y{4k/y_VG!LNdn)62r
#@:=Gw,IDQ4?07S)=G25`@Vz!U#Q7pBO},4L
a?N@+=eP<
H9X3xF;On$%0E`nD3L#x`cXc9@;NVh<xhU^)Ah+SC)63Cz]fqE(=qSdic{R8SJUsaq6,16>ZQvRRt9QaPr#}!U8{k1_qI.4tExwR7P
7!+DR&yw&.Xg*2+_@s~.%V%wL*Z<u:up!PK:<mO&6-wqkKY&&IHn}W#[ZpL!@Rf]njYMLuW=@:K.%yw=rLITUF&?pAZxJtl+?THf8m[F5*D#65
gD;%J$jh[z+S3$d/`GvHwc>zq=1;<aUc4L&*?2HYA!%)1{vu1f3<3Rx*J!sc`wiNpS+#%"RI:CE&FnlUTW]i3=B",o8.y6*ND6W8]x#_p5FoP4rQ$~E[NVpS0ykIQoXiv7jI6I9~!Bi,oL^N6N0isZe2XMsYPy)4N1d^<NUlTzV1btsuE@1g/.>&ANX6>w%MgOxw/9"{)kR3+(9Zg1?2j~k#WzqdD~>Q;R`i?$/6I(n2?O"?OVIh
tr
3q7c8M-nG
gh.y6!>{+xi?k>b$Q(47ef9#0B8xy|>Sma2vV,0B`NT`nx/X0NSlK^irt!@XhV^vM-U1T}@VXr2,FXZl6qwyV_@TJ}mq;_@gf(gqPR>|9H59$(';case"bs":return')]^;:h&+>E$*#wG2cXE*)bR5!B274j:?djF=+Y/Sm4`-f.]#G*7%_cC"`_=`FLQ]Lt8-4E6(%i4_V)?%/7^uGc4kKy(EQx|@BFgGJRl(q5(An#:u)1&wsx:`zK9%VAZ@lk|yjA-po&eH!rFuqp&iL^31W11XIV(BiFk9}vTXT]">oEXoO8|4sbXl~`4y0sj>lB?HT$3ouoz;:H.P!MKASCBnSxTph^Bw^BJAq@MhkZ/hg[x54n/1ks=F<MdsgB6uvRiDVGwNuG3A|&FSYvK:NpY=jN!nw8c6^*Il[HZq2M6FmwM
!>iBO]?Gs%"*5syT`CRuz&dJI?0jN&T7JhIg$^UxMbl51uI#b^zrL0#QmqtjAO;+DJF8ox@&nK+y?JcjyMQaUy}Hu
{X=+bqM25bqddlNI%cACC$iLYpm1*?qH:?^SvB:-AC1<y28qX.(AT<X4bXJdf;+E$L1%&5zn-Cl679>e^T71lD6v^u~8M3)=
K!9O<-xw0v[[x{
L=P5iQ;1zxh-^v*X_ci3h3MHNayL8AsY!MNWgf~K*6?i3#*qZ9o<ZVV9%1$j3:BKz-uqG!BJz%z)g`/&gq/D8"xJpYl3{SlT"MVMW)>Qwj"__HkYX-IN,8GvT]psL7XrD-2M|]#$]Pdd;,)OC6Cgcmf;s!iFfGJYJ!}vy;M.1D9K2q-KQPS`(4=Lqvs
C-r(of#=?i96:(mC4=4n8!m![X`5"!A3ip16?3#OoemcQ-e"VOlx~oH-/3qIzIF"USl_t,py
5[X<y"Y#b
8NcqMO
{"CiYS)5W@V`e5zshoQ_9"{^L"1y%l_j{0.bWeIin&PM8)Np,?*TcDr/eOJH/-HejC}KtRX?JYU;E6VSO&p#^,m?`<xDM5SkP>2DB9`Tr<k?b;TGvaZF9$v@)!RlK.KT>QPA8[v7I6tGqG{K5rac"X+aCXx`*)zs~s3lmKXyxJ}nFKe&i+)"Qn$%NwA7G!q&XB2jw@+&^gQ2f-dSTJ~Pe(M0Kus.VtdW!mu_O"jFi<)FE/ZmorM=rR/r~f)OzxSW@ZhhhPr9#?H*FJ?*,g)a$.v65;]j>tfRQ%$N51us!]@=Wn)p5"qZYB"ifku?Q(X<T1s[8J3$bi:"@v:#J.rxQoVs~>$#~&Ln$8$-
<"?B:`H/
x"3RU.h=}F063uMIDKy8_=#_@*m"IF~F{!d&,AhV~Q!@3AEd(_$j=wnUI(U-.MY;MH7T9sLZ@9UUPx`aaSZT>$/43RVDW!",i_W<R3P#`n?5NsjnMw"H2%tVlfUQ;%P2yL0e2<t2M3ukVeaEkcxr|WMDK/L*N!gZ+"Bn,NE@AoK]_dUY6Pl8,N,3U.*Z5>%)CI7ib:GBD=qy}VV<G$Kd2-|u40R;_)d`</n[0l%3}*M#j4X$G$&rQT*H4D6@{Mz,kBJ3adWxiTK1Q^8-.lyL[4hY/=C!|Q^@<
7)5sKPDMD[JQ2cw[gI-g[];d!xt#}ao=#UE1Lc-D&J>)~Z7x^)9tvk"86.
LNo[2<hD99`@a#;2qc/lQ/l..SfMgM
2>WT|uW;&:1"Qc%h*1MU_2g0Nv!v|n4yit
orZ+mDD]_)P!_!6cg5Y/S<_Djp
LO|9w6l
(0-t&QE:+Mvo~bE_Zo4vpoH(]
)E.
42orXj65.xdiZPxeirg;.e)NU.N1SGO3W-h_Me^[O?2+DayC`DM6dpXh+JuIxbxTAcXsJ?{<8UR@(=b^+`jTeLDJu^j4OW.N#`3[<D<.(`z3Japlm+X,21}oVLk&|NnRz.:d)#RuCjFZAL*u%?z&<PwZ$k%u>$-:lqh23:QXeZ4LI
uBvff;0n`"eZxk
5S06x5?!
5Gic!Bw!;tWOOefnp1+[+waOdRqS_hm?qH|^Swb5Og:[iwvA!5t2:t=WalwXHxMH7VDB}]xs[D<2Y`G_+WNx
,qL(TKHlo=jAw_?~5)bd5k>N-:/mH%Qb
y%m?|o24H[3yN?Fr&-Jo{l
QG(4Fial8}66xPJs23(P,r]FF[ki(f;QsF
xVqDd=xa/I_Tq5RvZC0Wt6CQDe."v0y7NW,C%i9Wun_-{R1X,]Y.Z)(ciC^5<4wcKog](4GNqPTAHN=eSW9Zh0be5rC"~:$*Pga3_l~)s$IdRgFp+5(dzB~CZeC`),}B%tAsH!0@jc#2X=IiEw^gfqgi#WTdxwQ?nK9NfS+ljSg*#rt%o=DJ3X8#_^!p2ouF(EJXn:VhB[!)K^ki~0LXgCZO1+,F3g3i*H5O.Xs8b?(cXJ;>R!U?q/^(x1>nxkxL%TK,KFBd9KWd/))P)Jw!NtHf|v,;_]
kMq*Oh7w/d8[Xe53M0/BfC/
-3!OhiweM%4MT^tZGDJZtJm*WebBb:A0lxeR
iS]RX#&isNP8P+_6><R#D,fHj[|e!?`dT5h.IXmqP9o@I
oKh`RrC]~NX,TK(-e%vN/N7:7WBPQ!~$m>;s&vimVh$m^3V%2Z[yq?,o],Xs2$maa3:mcelIn3=M^X<XOpM@$A[/x[|om-4*M_wZ{y<ObPT#6D7(xPG<PQUhK#(L8A"j0hJ*LKZ<
B<PfYU(Bg(CojqTe82%St105$v.YV/d&r!M|M^b.<O`V=gW[&=rf`&jjZ>9:Yr(8j|b+)o&!gX%KL#c`(5G@?:Y+PDude-Y2bMIL2+0
Z0G1v6ZXT1^_yz
2GDEl7y+]';case"ca":return',]^:Wf{Z;;&(HmH(6.$-KaD=}fo1D=}l9Hbr66<o3/5t24kcu,3(X5g@%-!=5^6b5w0MPG15d?q;ZR;WyiVMV^Aq~XiRR":G.$_vzJ_MB
5d4SCqJ4Q8pZ=7$_2iRa3ZD)ZG1ihf1]Za]_ZjhICa
sNE.3v+8>
f;:lL,5~0^x=>_SM6|-eA<ZWH388XoHBwRadn9ADW

Z7P1uyiIz)5+]SewfwxKS.%umkSB7tx.|yaf>tqv^e"N1VA%kSC^u."wx?_d1=>lK4w@vY)3j=ZOhKoA<LQR
r=h4Wt<-FZZ+Tt#eqQ8DV]M3&P6Y#g:GyI)SPj?cvT5*TGg)k!&IGq
femB(=;_pgC7m/5fxqbk9b^n;BkeEmmm)vz,8xj
xoF:m=g?kG<>5Q
%_gBM&FT]#C`?B,3K~O~wc^2uV`!(vp/B|w:^aLtn7Eoq`
c!W@jbh^j29xtM`q4^{?"R}Luh?*6b9N[]e5:jN4MS,X;n/6{y[fIo=Ud:+3:QbK^cz:<x]ZH,)wL*+_XeYg)
x1A]khH/2?q8$VlyoI5#%IUTA1%p,)3]o
5n^C|eTOIGhSE_><PX0FUv@wMq?2>;`(4dZpxsJu!>3k*``dn!8RJ42?zFKevf|/o4iR>Mq@Q
}BOdx$6S2a-y|Aoc?rAMQX&oO
^@-LLx0<bmn-_"07S7sKib+n/D|F[0EHU7Jk3eMo2Uz%D^Yd,+$JT-,[IJgj0E
elX4/c9c-^aV!{M*xCyJ-aJXy,DHRo6sHC:<vO6ybg6`IIu5-ni<Upp(fVjEx_-S
-!(b#^$H)E~jmk`Yjj90SCSKR-X0d)i6
@9
L,h[cm8X4$Zd.VNL?K|Z~_-j=n<8UGz0u&mas?-oINF-NdFIPq9V:Fg):[rE;QCYS(Bq.vCsnr7Eus4PBq@1?F]AhU
gxK6r5<oo_m/!DeiV3!@,8NlE]c8DCYV#dr"`7V7139~rZXb4EB(5mP/O_N7?Q=cqwfB!V4R>BE&T%4}w=^:#gFa
;N*-yV6U*SSUkO.&4R=QM72V9@fsU7iy2"Z1P1EaU>kGc0p;vF]99TU(bLd`J7!T@ICGX?U&/ND4ZH9COACt-td^;YgtTY|)jx.?A*C"y=Uyq/Kc0Wwqx#Kl0/zVxbEvSYZf;oM]#1!SxZ2sky?n17(!c`L8_
D7O
_>w#MpugZ)5o4dZ8RgD*!VB.uuXWf*(8ncs5).x"2Q:9]dR729xf,u5L^4@vTrrx
gvF0f27tY{#ToB>Z:e+SfhaU-0LO4f$/=i/jR9.sU!K0Wdd:c)esOj%{z)N
@JKFRPT0#?+]?@vQJA$^9>osJV:c9Y9=W(2_@chi_w$QYpuDvX`2%i#n<B$$.G>B&SZT
:N.J!ok"QA`OJ`T"XYPK]<BXnZk_m3#%&Bwx<Bc=UwI:Z!K7m3}hyAjo*?,C3rDaGha66`HVEL.Pc-1Wfwk=65lquSUC;,IAd?VnbbM".$iyZ,tmdC>li.>SANGT%b9NsP0
u5]E6[cR7Kq
8UNXkeX-wp,m+6k$n@Z/zAxHr(qgd&3j$WsGD-m.uuV.?4`VuJ2LmKg>W(r[jkVUCAs7Ch2t]id(Z*sS2,dU]P;@Tj=7dp`qfp04dV7gIPq<X&ZnKL/@dsi=_1w<3IJQutXTD`b2Fx_DlQmA[Pn
/9(Hr5(A>fK.m4Kw~=c0o9jr$%}*"mzZf5,iy<`&k;nDp<soQ>+jQ3|IlRBS_!JG%Y[>0FBEsaj`,TA2Te7ix)oT
/>sGE-KCR8o$!gIx#F9TI-OjjdCHr1(cI+`L23>e/|*g0`s;kX<YG
;w6=urZOwYiZ6;*cUiE{4f(}BlM~rlrz4`R"T#)4C/d}NJ(=oB4VwiO43{!x)!
83$)LOBJboe
[xO2.59pf1xe_sW20TT03?G2_vASIdMR^0kZ&rBJ"mgMYSoTU3w]~v`weyMI<q18~-BbIk7[#We!,c{;z`*2eqmeh!k)?D./sP&tO;!X84[1N4xM>vrx3Ik=5xWA1<%?{=uNrL%pg>e,JeQOc?M&9I]cQ$wZ<HP39Um0l8TR.Q)hylK]S
M80]BCRrWv#d<78!xXj?
<7X^#j,U>6_2&|oStB+pIjDFEs283v97qixCi!rOWK85:?wNn"bvy#Wt;H8vaTsZ^!4<wCb`?J`oiPiwi3yJB^ShOTuuD#mMSAYu1fTA&y05B9t7+5jDk!c5L|p^N)XM"w@d]5a%M>F0,>C/<zu!UKdto>n=k|,0]KM}.EtQsG+DImGG>A_lM}YmvwxRG6_rBHR+qGBT9~LZ+465J@8?!u]VnBVkhMfHEKyn9`]{iy3c>B:@?
!RfRk`"RZro;Dv#)%*?eDvT[(jk2dN0]Kk0l(u",tILJF`O$?i?AtcDPN|g[coN/
phjNWA"FFoK4kuPaimUEy9F$uJ>#pCuUy_Po+-8a[(~v=YH]N#G#Id+jN3<-MYkX_$}
L3~-*tHCmf)X-kC!!%UF>
ZY
p2F`$Q:]OIOhZn/(!Z]TSk`e=Y#C4|7S^P,~X{v93Tk=P90]`Gn3xRfLW5&w<s"g4B4lL,^g;hay-T-f`gLg@]qB^y@xqgY5!b1LAvDnyhN&';case"cs":return')]^@)bSZKF)4otc"
3UX7xD2]Z@2e*UFqc"a@Dlu?"J>;:stnpRshXyvrQQ#uH_2_lA5Ryt5qpF^n^.5X<<Ue1)n-BaIpfegn@4WxouI^a:xw-=U#*{lUa[8<WPI)5*OU/WS^:_^yO$elselO`>RU?QGYC*^3?p>:Rcrw#XYk.wngdG9IH&dy]2KY^.!E1X)bJV[f^G/?Z3BPnN6}),RZm[C$ONooV;Sk$-?Btt%,eA^s1-kAwh1yHFIaq956Af$"E=7E/RO6c&*$$Lx>X8#yF]e;32,/6#4`>&SPQi#N,vOaufl:CtVN!0mgqQCP0F@j`CHSP^:,F(6ORZ
"3`HcEa)fD!FUq^MXwwHGH9*EAt$!`Mt}
S"aD8juh5JF*&
B)4vx[paVbrr`ER`8/(;DkKSBl.V7(_DvP`4]$H30JBGhe75Y@4O5&oC0^8JE8tfPkxhbZWUf]68qW.Tcoplxk-?=l!D2^BtAGv(T4{vu9"Z5qRO#1F=hXZ@[3If18L5B+5!sUQS5x
.Qqs0ge/wM/^gX)s#E6y2BpWfH`[-&4N
uqAgLWkh,j!P>JAvUnA1T]?n`NVnOq%%L5f]fHH;|JQaZ>OT5:AQ"43+I)9e%3W
,*WgsM7>-XHg{D5rXydbU!MF[j%v(D`^;4E)&W+8&_qWbM0n!x"
jw/)Gk+%_$-H&mlFUa}!}-XbmS4HbB)-lW>DQ]s^d6$9y":0l:))6@!,`hB9HsO#2OXcx%?`}(.@6OFB6VN:jSpAwE$rvG!WwiEb3CXi9hwnOm(A5G(":n4AP]aLMJ~hsMMVfDC!g;"LI/w"6ys3.q:u]n8V{"jR20cKvVcG|:{K"#DM%kp*e-[VH,PZE:a2dC.C1a61]ALk8aFr`U(5R]RkL(>8m,R1}hjZ<Kr
.=7NJM`G[ciK
J,+>-S0|r`:09>WX[GTYO10L(JK?p1B=9oZ|^&tFT"-M^LfD1Jd$Tqb9hTL@#]CocIUgv]R7q4?P"mq.NKeiuO"(P3V-7laa(+z)CL@WZ-1Epq*llc9mT[[u&0Pa#,UjSS3`Y[c{_%l_O9qo7s-=cjwr@+wO!XP^nC*vUzoRi@_=s;1IOX&3J@_~.eOP$RcCD6wE@SmSqo(MjZT$MpH+I?Pgq}n:kf-/Maex@a)#Im!}&2T>M3viFpJ{Q2Wxu&]xG|f6to#%,Fg5II(M[zUlYEc_D@QOJuVyu;9CGcH,"xnR*fE[N,N;GSDg(bYMAHQ4i.c;D+Y*T/f#hF1R#Wjg8Svt:bo%fVPXQ^_|NTf?A*/Wpy01>DAK>~[gZ40jpMOeduAzFRDkTvmR:z(c
,,xIiQY`gQhiX+Lf"cIWkxyh@xt[8BcMTCiG)g57fYnpo<i9y.+(,2N>bU)$M8$Q*+H=$:gfF<ORjO(b.^iR.dD
BxJ:A%M:JZ7G@e,]a=`j"*5%ga{CkN4
:"TeqWBA&"cA0-&xe,
Z
bBeGQ>)pj^jCYts5"fSw`~c;$a]H^i<GX">]Sxo]sNAIW0&D+.GWUSYsjTe~%F$xqil;e06]Xj51#3<|(f9uMopnHBJDdZ%e>Tu%!Tr_nl
]+j0pg[SXM8Y
Ci[;nja|&&B,*-#1Ykq](/
bZ?xsC>?JU0/2Y+A^6.=
>l?Gr>>i#0euW5`2GA]Xcj-`4N6H$.kwgRd[tM_2*?h6kxIr_Cj>c)[7c4a^bQ05U3E,KMHdSnugdOXL_@f1HsvnZHHG2+izZ2y`+=w4"aZimF"hWqmp/5;i:l8`:"$ru%XjN
RW%?!u7fR]"IJ/Wxe^5+9BTf
d$%OIh:P^OVGeB_e1J&,h$qUs1M"+ko^Qn4Pqy>&O]aGrytIKw(J}F%6vgH:I8,V`md+g/dW*/8L[+(:2u{7Q
cyA31GM+1OT!#T}"3g/o<M]gC
c_C3]z"feWOL<RgHSI>4I3%]*8wi.xym}LcG,Qxa`w^l?2WJ2@kab9=OG]tiu.k"XDG_mN@Jx!EospU>@7tG/>Eyr$_;KxYc%*ur9b}uH+ltc`+F;-`c9mmbFl(d`Hx][bjTMximhF33TLX<&(K4biT-#/7-lu<Le(N%$BB=T@6?$:V^L_g<ciJf=6N#qAR;P`~ny=/Dv*b`O)k]}%MF*A}s?noV1:!UbP>]YKMIlq6nB@.v8aQ)=_P,$pJwFB2JB:e6{U}mS!U($!PA}dRSuf7"MeET<&~sPjsG_<n`DI-=zyV3wZ{dOyj?4yH90N*(b/Ied4b@HT
6QS.Y{J+tjWxrHCL1K$&f*3^O{7aB&*nq
Pc]:)j=)):$?2x%E]Z)BX1mc^DchS`#52#1a.$<>OPWIUN#FZ$Y5n|-hNousYb^2+/.SYuE6&G:wL+jAq8hh)H-y$H"5*A^6g_
@&JwZKY)ZpUdEwuqrvQ]G:W_V1D1[0@Q[<B$8*]9xY.4k1rIj%sd%BCHW0cH_3QK-O$cLW%u<c@&)J8Rt?w(~I}HY46urriT}rZO.4sU!9FOFXB!tAio[sdg*fih{/RGk$"s,+YwLxmD?`$
!_?k,I1"hy3,NV8m"EnnENy6OVc`aw+Do])bzjFfx&f,QjtNO?!PCG,
&4bFqr_X8M=!
9o!a[dRoY(0IoO6V._a0A.srSQxpYQdm[&U<[+w&6B"#97ms:RU:6~:U%|o~
#c$7]nn^=o0Sx2<c)?5ksD-yzq{I|mtB!Vzg].E
-<B[PHYy;ZPHjAQxZv!lKRdq>Z!T1VfLUD[r@%:3I6/qSZN<)U{2m&c&~o!+Ao)WD0/ykA_P`#"j&kAI+_*l[y31^4EF6S;qu*x1JFWg#Cp`U3$:FuJ+k!{_kft=xZ6+;e~I!]ZJ;#_=%Ti$%JVd$#E';case"da":return'"Z};BbP.!$s^OQ
f^j>DIDzR&9!ft9`miz(d{CF
,nH!j#=%(-:2wiZJqWDKRkKr_RvN3eK&_FEW`
{jOvW.B
/UBXYD3Wx7;iaovhr(Y*w_VQPcjy"#G^KB=EnW,Y-a5PqV7_Wfj,&Cr]De}CEJ4[b!]kd2Yog&#4.DM<FYl$Ph@H|-+9:Y~Ud4QiuSC$U/xCGWMyR(+^qMWl5gA.,q3<c6{6yt;b-JOY2B>O{5bHpJky{Bfb
aQY6IoJ[ngyS&1.*O:Ec+>n
[2d==Zff8lGF?N&J,_>dAVmzu1.cHmn=qJnQ8L
@6mQDhLIhPC]
j]lyohC@1nJw86np%A=`RXsY({$?+82IeQd|VWmQ/BI}0xNFfId0C
#l@+:y_{LW(kwyg"tj4T(B6>?M3vh@m%FNh-p;@|(PS6?qT&]rg(TsET0_H~)f/Io&2BgzS;T-:MyhXIgxQ)yfq?v[8@M3sO:%x{"[)U:gg>%1yaiE1N,b+~sTKVo=h
C]sIwT^S_~Qe?UUp@.8wf^I(@18cx;92
DcfS)]hinEPafh-Z,[dJD8:;jv^IHM//5t~*9++dv)Uid
mk7l2WCBQoYK#kq,$;&b1iLf$4Ek"<hR.H+ej*}34tKUXVRB6k1e.l_6xMmy>^cMoqY_0o<M]-WwFMO7X6?hz#:QXAIy3fn#(w*:f1/r%!?n=qfN7@Cj2LH@z$!KKn/doC@!|"dCq1=UfQ<WA8P!9cS@tpP*3#e^eWf=zw~#5Lq8@p!NI&$sBmeVXr+lp[o/Hh7<STvWz>P;M4:([AKkVz&F")=-P_8<-M&Q}C>v]6qjv>agOe8I{_5k?C"JD[?T<VFd<h?j.OdZQFo"SF]<U6L
B%jS6hL`?W*n@)|xAhK2f;QnO(Ve5c4ba,A2#NWF3F/eWLZS%]dQ6:cwVC$P%5n"n7vmS
$ZdW=wBy`C]H`]OtXx53k.Jmt9X@C#O,Hi=`WRrQz:21J[A:CNI<e.btFh4-antH(S$i*HQ_)[7tK)faE=Eh^.YfkJY1Q;*$
]OFBRa].PZHhE*4Fp@%oQ<vWG+Yh)E%?/.VCZ#3U?_WCQG&R8RA,]]nxA]b|MCq1*ylANMN|!3G%&e@sJMD`FvsDd|C48ei5wND,ro_qO;4j4`+:WsK2RQY8Xe(+a3G
$X2};tUU+=`P)
?CebRHV((vO{m!teL3XC2IJ$jA3`gAsN^!->Sg(`.2u&o~;kP:RBwQ)+sQYE`$vvt(@5fLqpFM8*q:BK0_xU`]wJ
p4kv[YxT8Quuj&rIyZL0i<+hM35hY.D^Jqk3d)&#aN9MuJ.tn)a1wl~E}Uq_j=~^mR#94i}ji5fM@@M)`E/io&`S=#l.v
Z/v)UNqt{LnHzwGV;#472uPo<KgI|#=%Jg^1O<1bLdADgg{wd?1-^rCf502l5^sU#[dBItw-6RpE./ggg<"A_i%o*[8eOq1?<wjo,.o/YXX5p%=RkxRmTmr56i"mIfd;rKPK7^qOYv[<^bhl[Q!cv?mShTeuo%gqd.>
cpPoTcT$}g^e"5#=9JU90u{Yp+I5Yo|=8dtsYb7x-7m(;-B[
DH9cbgGpY$k.j*jQ^Dp7@JP;)
]KS{jGTd[wl5ttpamRN8tNlEDF<=KgDft"E`P%H{Fn8cTXEB2}VQMSu:Hy&lOZl~/k<_vVbAq!ST/gZXJATA"r>N6QO#Rz80+Y93^cH.
rd4x(xarmLvR]9iHn!GS`H~nMV<!rwJ.QeTjyP#s:l<One2+AYDyN<dE9]}jBJjo?xqcCQt$,AwF]mx9:fix&.2Yg<~Tp4}!]Kefqg6ZyZgOcpFZegxF{yT/_1Z5@^2UevC/fw;dbykKu_Am-u~m^_/tQYGStFo&KL4w3`c5-nMlS9r:V3MlX+QtO#n(>9H`_H-OIZ/Vb0`rEr(#qvNMmQ{lxWz^CYC,
T~ie-n<VB6kx_I)5W??rE`9Uot+3[l4R5la8bICbXhY$LN-2UpbF:5?mv+Vd0zJ?$F^_l-04Ffjc<zkm1tkIUHy=i}-.yg)E
Df2vc/~Y6*F]ft[B?PA&zR3V%NqpaqCcM[g<$&]E/!a43x_lA&Dh~66!wvD8LIs)kIafnc7QZq$N$XE@b"v!IjNbr+5Vlf;K2_V?utDEcJ1i>bDm`/!v0G[t=#_azg#@tA9c@ZM:&yAoT31KlDr@1ff<;Qi/XnVbCV^ex
Ag@!dbl$lT~8lkC;_pHb%0tvzE_Rk<$)Of4h@D66$I*QHGo-wm5NITq.8>T@i9k5%>?=~f(Ol^eUiJb.kKzMlpyj>)zp9gf*+<=c-`#r)';case"de":return'-]^;BboWR#?i6u#_AN&k]3OD9f#T"/F.<(JrJJ*-sxZ18AL]URQ]s"py:2ggIa>#XHSK
s$E4#[TJE=+,_P%|g-<wXkhf=M[u9X;J)]oN>$xFGUqiGg4IiR/|x[3|o->rKB$l^-<-Qq:>=J%20>hbA@XVPK0:@/DvV$`~:Qa6,NoIrqUTAYn(W]^d+U>Mb/3!Gk-VngZ<Mz(o05KH?c`$8dpn4]pa7Bj;TsGfJ3a~6rqTZi)ydc]u&yQh>_N6b{,8QZ9q!G@!@CBd.;,-<J
Y^hWG%yH$:+mVa]#8P
t[daeL+gQIms*w5PXuu7o6/G.[LgLS#]!XmnU/`3HFt^tu<0D^K}<LH?pWq>b9km:DkN%HmWp$t0w@8d>07D6a;~Tfd!LP7<s)bK<x%IR^2jE0n-c]KbdB;vX)T4rH$[sP:HMF,:A{!A%Ur<tojI#%$s>cUN`Q!XfZng@wCY6Lm
6Cg:pj#0*}qeb-/z1lEKZUvaJq/:yCD!!FS6n.SgpDE?*Jl1
^$q3ey!*{^R1EGIrS+PM:sW
LnPw5ujV.e7a:6g^D.x/E@)&vi2jvWSgutPo^5gvHIm8Zm*1zd3P$v^i9hVO,N}D8f[OmS|g#P}cLyav:rELkGvFWK4xuZ/xU1Tijj7:#bnC2=!o
;#]4Q9?KH2L"^}l+V?mYwI*y7ipg-6t1
4k`e_!Cno0F=G6IL_%sX|5V0
NZA<VL){Z7/Vw~d_hBxSC9CJoC$L4&99lGkXfDWb_Ei!tT":W]nfCVmC;!6Ec[w%hG2PW&PU1PT`Q:dpWCtNcK8(USp/J5ltXdFYXjv3bG@}E)[m=5UH3.0Gxk%t>Nykq3"MyZ7d(n<57mPUa[H+]#Zs%5D`>]l=@fq?O+U5$sx|au#5R!!gVsXnY%pvdxVS,|[o)6HX+1!4OC$x+_n4Q,qltG@xxt.?[HixdxRE>hOqMI$is_5`0.Z|qJ`Pndv!BRw{9)Q0C%]vqnraQ~>m?rXKaBO"Az5@5W,o,vfgI=E)5`nMsg4Y;Jk)Qh?Y<93o+:#s%|N~?%5w?_MLOQ_g2om6Ni$t5&qyg"
bA5oY
!Nnjq&+KRLbPTRj)#pn+0X/;6]r32)Z^"U)gie0]KDh7:ukyl[%9l"onwE
L.`rck0T
HQA@S
-Ba[XRQ=wS2&fjh,pUT(?epC#K!Q*DX
|h8Li.i$?$Y6%&[9h#kE.e4F>1xBpUIO@U6%lH^&L%=&K0{!&SOo+B(7LNsspmc#~23?G7^1C,/%;qaBY-Xa]W!R&fnV;`)%l4.v}X]BT5;TeBAas!wy.K*[51;jcdZq|!?
ndbAr?l*sSZ.]fuQ{0gN~#liY/"brjs;:OiJ0]uQs[MjmWt`wozU>]?DjFA?Ool7]">+mQpaqk&6/0Zydr{KbbmUY+:gx;5pY*$wskEX+5(aL5t)sZMx;<S>ISt.Zd1`]ovKuo{i33{XeK=G~VaS~j1.gD%9zG81=/,w%!<[bh+=;ozXOZlvFv^291b;De3rg2-!v&QQHu3_/bil#N"_/.,DDf[+!-UR}7*Wv@M3s=h<CjOZ;l*9V5_4tqlaUwG<yEtk^UK-6^#aY``luC2U*qfR:e;M++I3&LJ
{?+vR(nx/[6u+6o`UaykL?E"7CDU(&D+1w-;.2=*R;2&#0soT@Y$9/Vyg87a>8v^(cG;EaIfG"U%tU3gfI&;bPi2)1)]&P#;"rE9%R|/iGbAh.x0=)>r//?Uzevt]YZb^sf@&?TsajNgFg)%vKX<ObY.aX>Q1f>/WWFALdr`E3zE^S/Tz@!_kEtQzdtms<Tj;
N;kZA+t&]n1[
gSI3)[
t;#pJ+EN9<>O+Mc9_pX0;"*ZK[,h=h[=2Fv"{8$m,U&:S$l09:?=<F/&
ly/>]P>cPx-(<`xvO#1hcKy-kf2uwQcnE1>dE,?]QzojAfn4"^[ma]M2/1_E/2IOWauuBS4^/-_},4OgP0nUu.Tk/RgDeR2#!]4B#q>[Sp9MZ[t)OWbL=>IXMJDQyJ>D2)XV9wK$ja+c=[COu:1rLe;+E6<SK,8yg<:kZ`
D7.mr4)"o,d.AigRtcScb31Rcm5CE!.u7V"vim(D*n$#oP`ryOcVDjGibV4O%=YYS-pOuVQnE,Hy)ku.7hM(.<#3[CywabkxG`>_4Smnj#7=}j8HvP2D+w=f"bv?d^_F[ctXCOhu"Sat5uw4!<Qy+t~eHhO+
cnqnw8#37>Kz7Er
bf0vmW8{uVYTBsLKCfs=XPaI2bQhX&$9#KvwKc0r01>jO~fd>XplAAuUj
5fKx&|I6`#pL_4`>gM#tW5]E:;vt7gwa+|.ZJ&XmoGN`3l2q<355L%TCi3U}QVD3sp-bEq<&f8J-Goiio_R:z&".5(Q,SNZmNc9u?JIEdNV^k!^@@QVhuhDiAfJZ`ipA#lN9AiSMe1:w=d=
@H0".uk1j&,P`>7KqHBVH$c*Em-eQC&K;tDV4Iwq8Ra7
G,$rh$ko(@.&s@{["Qn>$++[u!jH.PX;J@<6K^~gBG?QfBvVs6-t-Tl[y>EFd/9d1H
xdDPuXwNg_
_SMqQn!EnEE.IV8pTw;e;xhF!59UJMb:K#g`Inu354U>M3
L{eRcSV|2#HR2h)M[yBt(IC0=<+B3Zj*]d={c,#sNkY~gRB"ne4b]eQH0g.oX6J_B2OZ;H%j;jXt`QYsX;%?>,!*?XV)IQ!&Xh7tf1uz0htW@sEsa(yGN&';case"et":return'.h_;:h%WB:",StbG[ZO:V,LBU-b_
_WS}G&!r-+),(>#sTCJz0{)3J1h"o7-,&ZZpE?9X=ERZ^~!q6Ws)w.K{S:)R4C?90my]^NYA:m)^v7kaq/<2Q_e$;p6>6yjESq`WS/hp9C.&Dw%[W+N&9?;[$S54R"C3?v1%S|yk#8agmE;Ym!*@nt)}x8W6@gEE
+DE
Yr@=KZ2^(EZ"%(QWxF|HZ52%x9+_?3WK2au)-!]uk=W0@t-,sak=}!K/4gnr=VR"pZDwyZC@7HSkEqlA{/wrr[lkqq/]@W<
k,9mg:Ze<@j7=Hq4X]3N2DY:B!fDbE
;Q/iyy9KUOqM:Z%NIaOk&43&@(E~<vWnAT;]Rsmkvi2B2lskkFJ091@
8{R}6FO7rb4`_f-903;R;)h0f=DwElY!t0M7iSL;`f$Zb!B6F@LUG)4/J3R|1d+8p,"9hu"X,%[f5:&6K41H@aj],s*EHb+H^5:t@ZxinjguNs?O<MP#Q,6@Zo0-)t4+N~17+S,)jrq<<feo0U;rUxYZpme8wYD,].wdUy;^]i0:K^l];Ua[NOJqKXef7d&s9+S)
z=J/Bx(nl
,4!wHWga-Xn3+jkbEo~U[v}6a8]x3>LS#ay(x?&QOdy]l_a#1LvWkF6]AqukDy(ATb%Q_[)5KDWOb1N?If6EUP<6LWzX,<?ISPTF$>Zw!_o7`RK;Ai<U:wCkk?@f}jl5gH.i3aaCen)viU>FmNr1eil`
`<P#^1t(p^s*C$1c"UkA55Dvj3b/N/3c$SWnY~NuSFcX<lu/mm]3$1ElE++9#kvV_6/BZV?)BLY49jMg?}?r+[sB29Ey$Iqs[Ncc7Gm_U@h-KJ#8,HiUe$RePKEGNp0lX/&k4}LR?=mM<4K1nUX-c6ns*#K"w-+{`}Bo$9#m4Xw$+GY5=/EUYu-#>:gOb37^^Tye?,%Hh5!!vAMxmC4ECpDrRY):9eBoK[O4h&ZL(Pm9$"1Sj.Sf]zv>_MCJnP3_9gbCQ1sH#G+"u|mVP^sIr#5bJLLdqH+P5oILs?SyA~_oj3)s`jqa-NYg[IU{eg/{]CL"T)s0M
KVn>b&;7L080hFbk<4i@4?$SwkJ1fTSX+NSY<Z#1oYspfFSIB>pBU[i>LcK]RH@?hW)yT7YC/2o.`b/DqKWM,.G5=%2nWY3A6`1ju@t|H+Z^+&s|u82IgG,9]/x"xg$Z3
vin{
6Aj^Zm%6Qc;k;_`4VM
KoHr^rc],yDt1.eO2r]MV[Z#xg(U<ID=nzmJskCGp^5GIP#"W._!bT2FjY<$w=6#CU$iU*f8xFtS7/&CY)Ip5R7!hrO^9f)hLK0z)<?C/3.:<N?Ipforpyy,EyQ8`@.@O(OIU30k[084[XK$Tdiv6C*z3I%$al2}`j0cm~78qYT!yQ=*6k1_+{B-2<B)iYtK#8J|%s220R!8%~kDlld8fXB<f&>%NTp*:+)
NHhMo^#lf)g(A<wj3SJ&oKWDvV(nJ|.)C`&WJLBf^MaINT=FSSQzh:)o[+"V62/h2PK_)ebh4D740x({AfK$5xhyLNCjA|gX#0B^KC$tGnC+Y[L3mZTzSXYKB*N-=.6#cWK1B)"eezq`9lG=kWw^-M[x7XYZQBUNViqjDQLE
pH4dqC;H"$byukQ>cZ|_)IRmroI.w-P@o-c2WKSn5%_)wf?qdLdec^6jrtIcnC#D`Xa1L`lXEv$MQU>O90kgItAv8SM4-BZ9K_5$=^4Gg]WnSPjUe.*r)0S-d#g%KZg>U/IA7xfyx-/l(Sk`nBR@P&UaH%PAC>fPfRLb.f~w6A;wHwk8J0W&WSVO#`&1WJo0t$:uF18D;R8PA<52&:vpn
WL$v*_p+b:0y/oBvRk5+^vu&/Oks5K;K%_Qo%BeAu3Wk~VrDr3+Cq%O,h(@(I?<wZ(&fBr3pDLL"/8!k#j:urrX@aW*6|#$V.O[DSAkmxj`EMvJ
nt19(%[U2Tr%TqWvK?O!gg+CxHaJ0sT-Ia+yF7Hw2u:]fl[N&';case"es":return'-]^5i6LDI(m)ntf,n<b&5yG*S-XJ
p-0m6x@Bmp@^<[4KA4PDD9d!#TVRI2sa3wF%#Aj6p]*xbe-d2m@[Zg.~ATg)xuT22j6~8g&"y#llT"7yJAHR9.$Q=ff0+V.s$b$J3)ejv3o8#{t:z(sN<iFIOz@t!#CfC
!Q/w0;cXiFC<GuG]bOx^1ey*GcEO4vu^b!s
?)r-.B>JQ&w@>Sl0.bHRsh!7dC$MfA5n[q.wx^w?75^,r5*2(oRAn>cRC[]xlaGDx}V{l)GnPZ@D?D+X]+;vYaSCbN
PGiCspR%nF2s9"1QqR,CC8_I($?tE<%8mo[aEQ}k6k/->0N6pqn;Ko7aW^{,wa6loG=um)-.-10qcf/cIf>1OW
=b2s
wr^$vT^]Qo[[-Xww[9!`yr`.JqzO]B>99+i%BSqy7Oj
YH-,:^}r$ex)Q]{YE%q,ZKOua"
UH=@@!1WM@Io_zyL?Gm
cH0FSsNf4H0[`gs,n@+Q6)H37n&GV1J]WA!|f04Ksrx9%XW38.Bv*"w1l![5E7!X[p5/1D7:-lqR(>r"IK50@6q<Ao"Qtg,MJXHOjjw!6,>8$nw)V2hlMDLjI8gZ$Nete)s?t(omBOj@.aD2*^6~F%7w()L,rm62hy.HItEO(&j#%=FAZm7A8_p7XahT&&oVE@;DNLJ-?A[%aRg.FJYxht#etKy!?5to%Zo0b(&Pl(jAE`Ue*0t$i
w_lxgahjI5#bJ{5,LzBY^18ctPDw@2Y;k)i&){_{T=w]<jA*Q@ryT)<WmC,nZ2z%r0?TU8X69_pg*"/!aXYpr
MbmW>UT!]%;WiQuPcqqqLvM&kN-md"hYck
k[Di_f.!v[u$P9G6Nx)NnP-QCJ&r".UNp6|9>kB8Kd1#pmmyI8(QH,5[>*}r~!K.bN?=10]BTV9qar1vp8He3$Ka|9Eyt#]M@z)S&6K=^/Par+D2;oWmJc7./OX$x6H-=-t;}W~MLDI*sCP1@XccddHoebV7Fj5
XxK-kTWP}8"y1QLx[N0`oNwC|oK0Egm*RiVA{LE?^U6/;(Ae<FCYy:!&C^fSH23y~9OZ$/>+)78we4w;&`b.IdzRNc&;cc7194wd@u+]|3.b8+oL`tYec"d0
t-Yh`%2E.5&LV|r"U98ONMP$%n/9<P656>d:Z#$8w]-zqqO]BqMqI?169&C&]q=`?<fzvM/q9XB_$hD1)3pOyKRIEK6)X?mlr]f69qO^DJk4HaewSX7%Sn(N;&%~1w;#
:V3K4u*?OQ~=Uw`[wv%47W&9dD=U7Eo*Ru,BQyM$JOB8Ammdmlh,hE
;(yIb/)h-of$pJhs5zB&?lP@]p:Q@@0q4%lS0wV:t|Xe`d1/$QmGwU+gC9KX9Ky8ApXd"_KPS(HKZQiyx:1%0x;kM~HU?hCyeJXgP(<
t*e3-Xj=kBIP-VmH,;rqaQ6YFr!{eS0`n@#1S-1sf3<MrF6Odn]VDDGDL"$&SU*|*?J"W8;jn>sJutJyXoSNjeq)b^JxWx?cniihS;u*Bc`-W;:UfON+ao-MHyRRSLAnR$)dV!?RlB`=HTXULIDWbyeOe]?;1^xL^i<QKu!B-/xi?KL1
`--fGG|x!M-Kq^c(x[Ef)]kMLQlgEUwTw.Rrf1L7pizZYYx&&>!/"/*1^$!bl7}<`?n3{*|F3fq8M$W)*QcX=Df&Kf~6p&&)WY-:eg"?j9QD))zb-JwSBW$pqA^lFgB1l]2x
^oK~?PalEl.voDL5gl>lA,P-PMCwjF6{+<a[[y-E_^x;,>!,;2Crw_>VY&4%%ME)sT0zER<YSVOJm?#0P8Aopw"|s>"w6&qYTp
pi
d`Y?5|;a>AhZT"T)g04Kr[D%H~o~>Cqrb6,K$<N$eDQaVG7a+J`un<YqiKQ:TQe`0.tuHi^:GQcyItNFGV!6[8;L4?_
G?`knbq-oUp^j$:(Eo]IozisT"H95=x=W{34nEwD=lj)m~D;pf_+(,,K_An0q;m+60E-fyfEVltEjn;^qs(bld+nYZaesYaC$H3Sf>9=9=>%c?@%euI"lDP3QPLv!~;
u&HnYHjBn_?~wnVSn,,{(OGh0q)ECFsCrPLZUNw*0*DGAUsFQE^H?R^wL`G|gVXDKnw&4<H-<6^KmCif
g4ZOaOumcM,R_7Y6i,w6QBQ3B4rSKee?vC>
"FEUfz)oDJYSkU}@eryWqG2.
X>Rs%:r6U$L(oPoPFT#5@OhWG?(4FB#):=B$bvryo>#zp/#jOoL"^@X!J_DRj1CdEKn_Cu3~
>/|ss"<"T(PZ+[)HM_}RxI>>9`m![b}AgA[l`Kj+]PgT-foc~IEbRb-`8D~
Jj-X>kF*no8e?uCD!ZF9mqjSXB6)cEYoG;OSl(ax*7bvx=;3"*-7n#ItqRg]!PH:7_Xo>?;@IB!%16o3-iOmI&Ue%)[r
cWx?,qs28uI6$y7JFsZ/*;0sdvU^BFhW8%
%Zp
,Oe]Q08[Z1&jTmNmPnrvtN;V::ts;PvFM?APrb$<.eHkuYKZ`NHGXkepT3NR=P5eQi!(X($#:b{G:?#Yj8X&cE/@ii|138G]U<fNX2UE3/%+TF
djfOi~f?KB8o"7Ab`6:
hT
TD,2Yp;r`N]2/?KdM"P0%7AWpEU[.YSn#CG
Ko
gJ+@U$#kBNCz-G(yP(QBBS#:P}4.h:.yLnyGd(';case"fr":return'&Zu;BcrZ+9@5*o9/{/i0T$^VWLe(HsY"})-n2L%!2P@;Yr<9,g3YryHxIMkj3IznUxpIlq&b]VffwjbL%1Tl$M:tTrI3h="B{x*cL!~[m^{(L^@&{9Vf(*Hc$;n54v
5,W
K.d=el?1;yGf9LYPIY"ghPv0*.D:jSnPWsHGo3wELZ(t21e]SGsDqYRhrY^RXK?SHc)M
dU3VIkQQ=PT">Dj?"hO&=qxq.mydcH`m42;xFBNjyTkcnEZpvhSx+!>GO&~r!mm/v`^hxUzE?UPf5$Sg;;h$~$qo?Ml!>bZVO1-N=V9VPhUP2yYR^Vp
"!TUZ&d(pghZH:yN"Rqp%ouy:wk_5ik(!D?SL6`rCB{q=G8Wfu1Nzwj5
LO^c))!(&^Mo]/YoQsr6yNKn?"X]i9:X5l)u.T"bW*3
,3:sHnRX`7$@*;]VsFF;Fi]@#Iy+v|5wI.n$uJ*Po@8|9dwnn2jRS&l-`}Gtc#T5y;78R2GIb
IkBwk>/1Ql0zxUwZkS=vcsC)4fQ%9:awv[h&bKTMl,]@1EQHRKsxY7%NYf,_/`LTfr5`Z>gFAj<A*XpQ]lD/:@.gy/Q[6aNy<AY}[0=0[TNH2H+ykyFv"*1h;NxmMd#l,
Xk*$"R3&D+sUcJZwqP
(4?.?<b
EQK1#2{vYeZ%$2UU
xli47_OJK<E#2]oxxH<H!wtfy},SPxn([OJ(^{jQGzj(?=ev!}B=q8sv"9U.lnPmG4gBu%x$S6pKd*.iOO8/+cYK:O-ZBA5]KXeKlW&b%E5Le.TqMwyfXQC8J;iut,b5S.#bh{p},=e5xotOb#qv
%8`7lenJ1qP-qoiLd8r-i)&:>peaIJsQMt^=@0SoJO=[|I8(OSA!}-/CAb3=y[A>zCV:)2Td(X9OdA0:PNYG4^5x2T~IK&SZYN*Y$wkH?*MsT"N*)Q-tX+>^"?ABhqAw!5:l`Il+_iLca;rcD]VQN7do~Z=RX
m+A_7Bb`6_Oe.^q%i.J"fHxRw:xt4WNE):$f-1TD;XH5h*Ca?5NZ@z$2gIpE2,1Qi5D6,.Ho%_hO0;7),i|.)>!+a8V/F!fQAnERky[U%"ITv=MvT4%*NSJ;n@u%sBlFPm=UVIKN[Q=)3[XJ%Vs*N7.rnQU<rSB`Y?
FlG6OOq<`2r?k#9<l
.|b5F!cJNu=a>>iay3,`S[!~=6L>Sy,H?yX).lE1Cqwi6/_f>s2(@o,G5@7&[#;EZTNck5Fk-,sJ:j/!.(e6Qc?l
R10oR>
5eR|JypGCg4{/=mup*b
xU4NfCy<UE!zGA6*30>u+aGOF-WknZmMI/tz5~PUWOn}XYj~m?h
VOFc[@/rO{/Ef>5
>-YDVG^d-g.@$9(Y.Fr}bnss
tn!EW({(vw(b~[rj2jA4/o/
I77>6
!rNnOlQlkO~F/.f2eu0[(lF
L5Vrg+C,SI4GeK_qX?s:+9vbv4IaYZzRu`KD6.)qE^HMX$aN&USqNmkkMR4]yS:UD//osc!*&VRcnS2xEA(rC?Uu3=!%k7<s@vOwRXC[z]&c6=E6v&jW,H5K-bQ^`>i)>od.Lu}>$"=Ke&^?L@{X-g@rz]
I"?9shXw5v/ea^^1EH]pd03fFmO!1_ZxG(7$?RH#ph>MT(<e:t8&B)bU`Iu)SkeFxE+VKSXmLDB[/rURZ}
yFlX8C[gOt+s?>T6ZM]:vAxDJrsImJeCm7A,YTL-t4SJ
P0-o<;eDUz5bt5tW%mA65^%U0sBS]tnu
52^ge8IWeUg
6?GluK>[QdmMV?v-5l,#-nj<c)Y^jp
34:R9z:p]i=x+h/&O^Q<hwD0)ToevZOdg!0<]t[`$h(9#9,LN:oV"<o5/?1q.OwMLNZWsY9WF}6b)@=Tp-Kgy}&s/pXijQn5+Dn-Nv&%U#^wq>>{MUFm0/,uDD9h*Un4X[=d!CnUG!Z!ti8
U3R(Dy8n=1i]W^
x5d8wYT7z^Ri.DDizI^[gh};X3o>O=ap/C+_NZN<S$
p*67;cioAlDOpL[XS3L{/Pwm%LF`X"r[Ndm&Pxy;T>ngmMl-2.jY+>U7O;VL!$5Ztw`f)l.+9R.]=!=+fqkBmT>Ve*3sF|fKi:`Q6^u6n6SsOla/$sIsD8U-;UOG7?m.`7-:*$^]S]v|/Zkm$tBxe7!t3L$V
t$[Gs7::`u>^~w:z$A0R7!([DJ[7MeBMecBy|QB_-<I2,QGZ}i4^(Rh#T=aU-y#"XZ%M0/1PLtyRmgCc:;a&n`,bDsejaJ8+w!#2-]0#br`nrr62%)0@Mv.JHc5ul7Itx6YlK_SQ[=HC-Rn<
NJ-*_XjxP>Ud5~<s&:%t&<6Gg>/!sLAWh3Cqq,/Re=+.GXq04|Sl
dCji>U;RMa{YcK|<y_NYSBU7VW2EpS]6^1SX85w
T.F<zI4i$#s2B/XE>wF2SqFeM.9A>=/sCDn#-]M2
G
#6+{mIGB!^,8r$MKR{(w2;g$Pn(s1#R_<r15VtuBRP0_x.%sc(d]k(t,BxgmAu+1e;@V,E*d6UCHwUm3mD:)d@#_v3De;V0oc%5BW7YvYDP#7TAt+=DiL2h[SuM^Z9,(kVZ/p~>"-"".nL:]82/g)Zv8j4O(jg=p70wrCB#*rrT.)6UcOHYIqYQ5G>(0V)Hs-Le|IIKhtmxu=jhCE+9WSIN{OjL<;U#
pq#H]:6ze_<)3m0$B`$b%-s~K>Toj}yR5R_@.hY"9I-Dq"(gJt^2ur_PqhWZMWJStGmA=2=ROy';case"gl":return'#]^@iaLWR:$iFVO^W(8%KyWfojh>n4L4{#M^5>*4Y5f&_T{SZyoEj%pUfd9Dzn9vJqjSB/P.JsUGh=s`eo[,]feAsIj_99vo;k3M=*QvYEyfOYcrq$bOddMNkKfh4hyo5H~jv&p@9dcy$?v_%(cYVJ:X[d1%nqcRZYd2;_8"r]pv-_^>)VKy=*agBL+jvQfJMG;cqr!wzmKga&$.(7+l<a][h[FBa31,b&CHWaw#|YX?=sug/SWbe:ZpBZFJH1fOwu3L}a}$noSn~*wyy+St*QVJo8_$Essq|if!2C!>g
Ckr;9,[5BtKZ`"?e0Vo&%9N>iJihR!XO2a,%v;/GAP&QEL|H>Uv0TwvDi3hK8g:o:Uo2@iA?.IJ#@n&1^Zks93.L"XlL]Rt30oEL5<Q-
r7.&03R@<3/gkcumXQF0W%XT-TJQD#;2[{nz^4=Mi,8l&XF:=}U`e`mj--
DGm`jLE[dxo,L9<&
6Cql<Su8EK&6o>%(@Ij%a
NfFTch-)RWEsG>X~J)4?
F>".Hy}BlUvPLp>;sTi.)[#$/,)r/y.dc9uU;0C[O+yGTr"l
6}?Uab9dlAX.O5!,C/Xf]0IYB9^{@AQh3&Gm#ymcTijkhh"~&9f|)0*@%{Ct
b99o~h
b!&{0XJPT(pw##&=3k]zB19~_59OI@Tk
J6xAYY!H0qNGI#m!2cAJ~H@P|VM]`#rTv%{j!-Hl
kCE9`i>LH,2dqZ2TuH3fS]h#.C*+/$uXaQyCY3<wThl]^W`Yxekn6r2lXRpgQQjH.(;jc97oHPqdrhZsoZ+LI|T=iyfC^Bt@=}v~xMyWufA6h{"4D[Wki12/.KS0j#%*8Ho56Qc9s>+&!8*]Kq4h8f;IHrqF%XiAg#%z/h#=L?TL45YDk9evQR_Zv{PXo%7s9EblOKm?b%"`"g8*N,X3L.srQqA8H!GI,u^UUziFgWbj"31F!?IcwND590[CK.glh_k$v,2w3W,=+U-MOX3,KRL&5:/"PT9fkMDWmS))qf626m7B@PIy-!s|J%"Y?@)7`omSW3g5k$Kr>B2@a]hG^6Fl+neu/{J.Mpnp=4haWj]s=gT.19/w;t_*)N<i=XkBx_0~yoj;9kdA.b[UUl^h[wuuWmMfTrxV2hrbMXngCa
])]`pEh?[oLui*Bk3A7U[k+J3Mq(
8_c~"(kpS/;xWLMC.3upX+CGH172*Y(RDP.ENQV_A_,wY|dC,C.ftZL4?(TseCdkk5a2i-,l*y,[IgxJVHOj_[r=/$p(27e}XAlxtBF(N?e8NSSpAC0.,N0;";!,oq9}U[UHi@H:/g0SU7)nl!]uookA1lJvO94T=wTT?<_l%Ket/]#=Jkj&SFA?_W!vTQv3:qVDB4CLJ($<DvE?HVcMknLySo6t0b3?=LW[)L_35+D.hadXs![+^R]FjyM"!Jz!x"yl*8T&amDdn)_jCYJCUz-qOYJuMpD{tE^Mb_Hj,;Ott%O~CB%cQ/xQ,s*T#w;FFfdP(CL7)<EU5Mk_hAVee3"[k^Y}0kObkfnm&="{.JEr=,1.o3sLk))(-stc^$Psrey/O1Y,*0v{hoppO1ppYrZ+.CX[+6;"6ONw:38Ax`mbjW<C!|_Fo5ibUo3%x.v`$Z>]5eUi?UU)$;b-NjO&fn,&f$xH%hJMPm=F8<c:MnGl8Oh_o^sp[if#dj(dr*GNN
%Z4]?(gS@H0%`n%V3!/IGiKGEDvzV/CCZ4N("T#Ja,/iETQAi_x
EvB+-!;oct/y$U<
8VFVj%3!t`mHR1^b.OuFrjx+-32hXxF5r?s(Ki
UFeM__tE>mh7MW^^|%O<L]F`sCr4usp$EAABNj{wBVDg:IML-R9L*.tFOkwCOPb_|UZbgB:`5`1E`0YH-?T=o<PDwv+HflU!J26(0h
l1qfG3^i^2`cwfC=ORd9R|.0]g<gfj4eu&Q&MtacjRy@)Jf>[8x%$S_1UpdrCbvj>,#C7%9JZHWBws`{_"h`!3Ax9l&e5-8!3yJM[Ed+[|vpIKBC`H(WbuH9-m2$4TR9Te6ZVAF(f8AZ
SjR"(wZ#imlXl<]F2F5M1eH<#Q)ZwRGH>()B]juC{N/m8Kcm;P,<m,p3ys+5J<iCya5/=
/IC=.qH$,yWHB=Eb9^}.{XWX=*`4<A2-x9,#Jajxo$|/qR4QJsZ]C,S4E
|m%rKrBn,RM;[x-s_Z#bl/pKr+9+m>v+$J&Iwu9^`QokDdzarWJ$EGDmj_O19;&g0cD>_3/#@Cb,#T
r6uwrrJnVRAklWKdrA#_w2pk?@anmG%Wkf8l-L
2M{*ZXg;Ae8>Y[~jFf7.8`<m^T"R><s_iXL/Eg%#,^ohCrKy[Pq)_mWP}p)GZe<[=f{fep(
g
C#z)%0z-q%c!@?d
LYsIc*d+>59Q2y~`Y9bA(UMA:&9rnEr1:O[9].v?#Cvconv7wM&@g65Q{
v.5B{0Q"&RQ:`Q;bwU+vzq](VQ{0H^utn[^Rkvs.mt)a"A
/M*K;V)RFq;$dTrDdKXJs-0(6|.a!K5H(#fWdyPG9WWTpr<~/Y>pX$*#
v*N)FM5X,]qor^8?rNicB/{^eRgqv]_vCl9=CsEV4$O9^#>32hH3
t^';case"hr":return'&]^0:6L.!/#H!tfEkD@)?&b3ja?RzjTs%bX`_Ok
s)9U?_"DX<1l@1kIO@YY,v+@7BQf*QX[6Gf6~>.m,UD.^SFx"1:[4ROm
L8$q`8`
i3lv_m]:n`sLV1pgLKmZ"`P^`
hAw6]jH`cvsmxc3s`~:EVA<vdwu}-bA(P$J%*D4S5ttXYv58KD-&sp]Bc
@z`3FcIBAt^)"c0J$mjcsclTb-nSrRu>$VujAf/QD:erwH+5k0Xi8>mSqi?sm>^!P?tgQ=yaX|La_F"5"^+L.a,9YO26"5&v]O,S,?PD43L`5Jw#JiMMlrW|Zsv#k{I~SCh,$@`
2PJx/(;]u3oo3l-liVu/Ifp-B+Xt>rY!y1mg&gd0rwvuuo2/6ePOs6aYA1pHJk^?TJ85Iut+2TWPXghWW<O2E%KqgPhhreqhOlCUe<:c";A0e&M#Z[_.)9iA/~1+Tah%%,s/s8I9ng8/QtoO@[p[)ck}8D!x-|
F532P1ME|+EE!ijhtZQ_`H*5~X8J@mZpRF4aLx)3|LkQ,yTpyh43r;kF5&`pOKD;T7N*Y8!LohP7yh50"jyFqN5Y<4ZC|o<B^-%-.0c+?++:-W`)t/ho64Y/);&Dbgqc?R="/.Vb]8OHkJP_u4I";I^"llgOaGEa"AO#1$[.;XDi/Giuu*EOFMxpa4A?H;C8TD-uwoXGzg2:lD{24c&1j2r-y4Zw=Ep91v%j)1<P5!{V|R/A8O
>vr_oaoNN=O5niPu-egiC|p|6*)k22!<"97FBPjjm_d]f-I,0$*$[MgovWVfd#0[q=4&[UfO5zp6X~DO.z,psLyN8C;R@4n[-7ly;aFJsP3tc:4f@sWjHv+_&`y(i>bb1U.lsiY56.O6E352=%2R-k*x+hUgo|N9c}q4avODG1..xSA#=M9?OrIEe$C1_.r<oomh[Mr
WJ-;x2;K=b5a=I%0r?sBl;2rxH_3g@Yg#=,a[/"2n,`KwCg7)+=4sC5e9wGa4a4fO;hsp@J"Fl2~6"4S.EC.Tg@z:H$?ykyfVQ9>Bdb^Be?@2B&Qytz$86hv,Mc!^)3BT)&f`-#MB"fG8!S@KaT~A6lb/S#B:6L`_Ojdf6lZ4ikNqK04_rQMk&sl=:]W"o"epn0vMT_~6rUu,+!{@0P{kXveo=.4Zq,]5;f$dlP:GE2
<NjL
R-D(HX`6WpZI7"f)s_..x)xD0WI:g+"5K_+"YA$2>Nb4kt)"?oay9_`-xcW*i8Mkzc;Qku!4T%!B/<:;-k,5HxsRsf
DWXWK+M;F<#Dv:jI"*Up$C:{!>I`"&ar"cqR4&;~iI!-"@qF_5VKEX#[Cyb%Fy:RyyV]/CG1QY)D"mCdI3:-^X*&%A"4:{2<pzxxJvd9[Fkt;.V`.3G
McFX0](K$`j$_eExCMHruu+`r#-iRtWa*(gGCisC"Wv>-1k8+YC^%_Vcw&3[N"_?%TK)=3E4PZQ*5wP:x=#]yQMG/UFIO^rj.oLT$Z&eBB2u2d
FH%eNp(?,7:3)3o1X[2@:%)w9(xN%68qAnb4{$OID)4/c]UQ#p5GYmw8,!
AaAp",IXS@LK7
HrmfX%T>#RP~`p3!&i)|RZYk^@jwEvqRPwSy3AjPxoCt/V3I/4=Bw,=.xncD2FQz3}x^uz!<Zp)jLeFmr>K3KIhEv3ZT-*Kx%xdYd.Oe^"W+9`R{&:v[y{WZPlv;mr+}Bgbt/j1$xZLDoV@I63w:iK7WUygQ7IjJp4c
ro`iw)8i&fG
tk^-)nP$^y]7%IQY`h6hkp$j+o59tA9OWj6Z3@_I9M^Z8|"Pk04H"_it3bTW5g_0DDGj:oDYsAi>BJ-YM#?9]&K_X`Bt=Do"^Ku-G-p~cQz("
5!cQJ)2g]d3E
%p/.S:{h97hQaVba7_D7|[LG2E,wHX3q"soP:/Sq;MJfBB=1_?L&1"E@eysCbC"c2ty/n];(D&uFj8=3Tp=J}I!j/HgT$`_YKC}>QlDNg"vl1g]>J;Z_jl^N1VrVN`+_zBoofv0`i2<a^>zhiA0"`y^8k3h=}1e9]w~wcL|NW-St&WK,H&7A/xDNk9(K[V@d4/p1+M:l=!f
}(P36Qe"jT9FnTV
yi"bb?V@D`J?ZCAGU2TR>o*E0g?n1^swmH#vx&.dA=xTuMrH+41>
Gnb//XsOiDv2BQGIdgaIB:P7gUME0r25diKgeGQKN.(fYI?rK8x9pIBIp]Gn9{x+FXA[Ad-I%)/%F^+U#WYb9|
sOcmFu5B!<aMrWvv8Ww`T^]18#6Gq"Q/SNSuUgUuL[C1$U"lKn
m|`!]/bGfy>mP1j|pc+DbCT.(Q2dUboU3?Pc=tTD5:G0G%e@e8b[e7qX.)@&5pa=(Fi#^/(8M%40qpJ*2/J3n?YAsogm%fr)Kb=%;mpIBXtoUnt=S}G[.yA5GcZV0/KSG"bk86^].]04-u>IMXVlJ>%}Ck
pZo+53Ybcefxh=x5/mTd0W|8k0dDP#`ta<zRaIwt
/CilJ=#:]>AP*R.Tg*jrwYubF<n]-I:sodsmih9ljThk%umpb?x;RVsl+4,,bi?)
QWTPBY..jWfa|.KO
H2`;bd[?Sqy|bBu/MM*"5IpOXER]Cg5PGKC_2q({@BgP>fA=J,]zBBGwQtk!io@:bf>M3wN^rVH+a@Y#tV,~wj/P[XhE,<+|Uaf_SE';case"it":return'&]^5(aM.7%$INsA$l/a8)m7Cg"+U!$lN*01/-HZK9G@&kX4K8a[*5+DJ_w"U"m}^Mc#j4j,oYWt6EEU5z
eI#M7P
*]az@j^4(5AQ#d613+.{c_?XK}vh)kbc)HJE%Ba/rv,Not))#E5M64I_)c98Y=X9ICN%ARUqSOa
wC%-+I]`EB
aHiT/y)c*8%amL=u<gNg8%=,PyJw:`.JBqdCcWVkGYr6>#a4:GP.ga;[Q>e&5=DW^;{!XVK3^[dr:1B
t+DZzW7uJpU^hEt=+;P!9F1sPwWkk
bt5dc5z@Om=^nT.NqrFT$oLluF0@n
efW)9J|V5(4il!;ObS,+6j~qwM[C1feO+Ue:T;vM#
~P%xQKqCXbh
4C1@V]r/muc5)?l^M"-xgJy`ehC[*r@k?/,I?XgCP@I0MtcC6CsEg%a^()nJAQng<f6`Nl;jsxJ@]L~9|SLxzI+i3xY2?.D[-:C5/>a29.>Ab#>REhfbUa?&1F=S6Za(qhgnD[N.LEef3SvF:@A!iZd.v8/r)y}G=$>
QmD4Q[A:XVI.T5ll}IgJ"i%3Vsw]J.*lF$,94

G^Y9@[TctNj8M}o0+PGP(]nY@}As6P4;Ni8/G3e9,d4|C@`7-3CUKqTC3^HQC

#?gDXZ
r(qeJ$ewU03TN^m0s)`jU9[7h7<6v+$R%".|"mlyZH+3JV-6J~=?vdF$-FrdgjQs&Q^.Lyb.YP1Jlo#V[BT|owU?JHg"_=[yX4S*$T1@[G"s*y4<6s45Q1nFOLO1*"7("wX]U|Dt2QvXU=wz`b5FyUs_OaBatE
r02@nO_%i8#B2`>a.!Q6taQ0zx,Z:,*d2]sKh@GA,D1L]@5CM&orSYBDSq1-Dr)o*S(x%OsL#J~5E[Nn?TEsvix;/i#!0

>K[REa-6iEl6=1`67{>T^-oRCSPz?RZ#v1juQ=-2GQ#EefS=Ao^No/g!jc-4K}&
mF!/Z[6qmGbG@R2Bv)MK33!0H(2FwkY_%H810zQDKQ9}.N!TW<-.ZL1o<I>0Dz?r*x%W.jl@hT^B9;RqfeCDvQgQAZAx`{APD4n(E9kD<-/z<&/b2Y0B$$[QV:J3UiE^eV<eVJ3bhZ)2>nCM7WOt/Q"Z,;!+L`MW54^xCIL<pnYed9):m.^-AZEO`;uelXgp7b8PinjS6G5wh`(jW#t%"<!/(]iInvIW:;q8cvBd&HN^)A!S)Yp#FbI})V_l"=XUBRS~%E[VAU(bG!<L9nYS#FFxkz<=3%O36>))w+_u[bjW)>]/(RbU8&g;3:?hYw+{irY3dT3Hm9&ax/ktRNt,_(y2#3rUt9-:)M+.IDNn5`(&iLO;n_-
D
>NA<f7ODk@4@ULIqgq[+uib8d1/#et:[-a
aQ0jM@.,]`4:y^/&u`vnkw_%tI
=lwc^<b3ar3/Xg6xSMYFhps])yc"LA#E.BhAO,.2Ut@dq3mtC7x{jG*z55Gsnx/_9ND(mtd^*)RTs7C5U-R!Qri5frQ%vqCD9{#]A(?<9~!pg-^W]wBW]BS9$>giCAEzIeANxd&YlSD?dsj.m}O
&Np6,A8moL-x8hS,+8NX%bQb<,

`$6-]7xstmy*B{p!q<Va_Oy-+9DM/yA1w=DGTMudeFN:O0i@JVq[DRqv9y>W"}Q$v{WhndX~a;[{:fd|c?GXmcvUIO)9]]&T.7`eYi(9di={)]?b)/GT&46mvV%#j!3R;q?<^&?=`@p06je420Zmm3E}:s`H>DW{7/RO]=x^R:
U`"xOqUH;E.f*Dw(>9u1tjl.tdm:JW&<?o.I5;epEs[U!>d>/N)e^E6)|Tsa`><&+jfb%QT(V&c(~PQT!GCIOZ=v#8j1M!rdCk8L<!M`"+0sJJODOG"L;S9k>t|5cvP5gEn7WO):{
JY)QA-SBR!Z+J^V>YU1vcJs%{40KWBLII11y"saB,/*(hjfV%H_?C)"P^(;
^shiWM_pv9<.j<=kVRlU3fnuC9QAYjRUa1k:V3`0vx*TjMMqVib)SA"JEB!_/IK;ZH4e
uS/SFn+DZZ!kO-fjf=!<]C]$JXC7jL?G,dVGV`p)es=*<Pg5R28hPq$Vp28MN:b23Ysc_dWi@,X^m/tz/=J#OwZ,>|UO@duO)Q&hYK<@Kg+FY`.P#_=T$P?Y%14yS:`wcr=py<h`#4ECWa1EP8?PV
Z_b*:*h+34^-WWo~cO:Q9_j41o/;q#Uyt~!n.(=JxCaxancY*9x}k"@-IF$zCRy*=$-5TrRa;pmXikk
f#YxOnjux&e=VX-gUh^9V>s=rWX:U@<>";RvcU+@Jmt?&OJ%$!($IOAtHjQs_21Cu}_Am&Cc&.y{"#ZYVVN$flL)G4t$rYM^3*V=p@V.';case"lv":return',c0;Bg~WBP!!:wB4+9iLW$4qV8
K(QMIo!D480TQ2=
8e?WRX*($!O=
0MS<TevZ[XXXAu{.JQ{EOhkF4Yo"ByveIx_!Jw":%4g8(k^<hD2@+m;Z|n+%itAFz[NSCW@7{tl])r
i0B1Y4:<7E"&P5czZsb%Z`tT+Ul1b_@;!9-JvUpwJ"J/<sj6T+QLVA%fyfl~?V7`ZDtnxL7~
V/dMrsa
.DU]|@oIrcpDse>V4F)(G4dmp8O%BqWgn
Dq!n>t.KmxBtG=<P,i2Wle;WW%DWN$(m*8O1r$f!PkE:95R6
5:!>e8h+V-+Cfwtlx0+HR`a59*e5mQfoPJ3N8mK.UYBGC>eQDO:`jkN)Jo"3QrDCe#V-`Jm
!:9e40FT+fTjc!>k
ka;LO%L]EQpX0);
rmpyhP%aTQ_2^*]:Mr7;hC#ww)R>,Gs"J"N+YL;?BeY]hRR0H6s]zsi;Ql)+-=2PfkQr`*<$@F&4x/aj_T.DuKWwl`@YRix1eS(U,(lPHJxypRw6.u<`O7uiUCudqP
EY(V`;^tVJ`~0GONcnT76TKn5koB`m8K.DmbBjU2>o&B%@u_XAtPq]^SC`l$DS42n&)qSPqzlEbZ[Z&0N]5w53bQ6UkV;io}v$Z@:)P-RSwQT-Ec.~G^]~:R$D-6HDkm*Y#rY`WKs-gqa(J~_THNchvLS!rP
fh0j"hQ4n+,Qml-+`+oR<[`mA_i#QaBcJ#yn1HL?}-puV-4q5Qa%fT(=fY_li1+nQU5v7E@lFJ%@Q.4wd
GSYQ=hhq"M
gYZtV,Z#l^L"U[
_"s2arGqOmseM[eZ>pYR[ppM^.ps<"U@`#~DJg`WTs{l+LcAMm6m63t*@bcaLIcKE+CjNsh*"MrQqF-gPx2sr6[QT#V#UFe@^ZvY`KMdUWE^$33m=asAO&,NX-eaCM#<`B;0}m<ajo(7b,@4@+]d%awIo+Kf,uc00bts|x!lZOK%ZW<mUbZaE4(FWC..V)]G1OnSY7VCU:<
z8iG,N1hGZk+>NvW18/,5Y$B9P~QeS|+V
&<
"4_OW"n2npSU_zb8)?o"_a4|2
PyH&tJAbo<","DV?5IoXk4kiK;Xc$"#sN@Y~$L2Q4zSj;6f,(q<;PpJ5EZC}v~?DTaP)0n6NoB9Ou!:k=Mq;:B-(%vq2xQJ"F7XkHTISt?:NxPkax=PR0.#*ePhNL-$:I&eK,m;.%H[2$,ShZrF"ut(.viWN$x+T;h*:/h5_L5t?p{BCWKu<YC*Bl(8U
x(]>pb;5`pQ+6ZQR%Nf&}O;eL-:5|D.uCKY:I+a>#9!lBCuDMqk8O,.Y>)TFS2<dREPpc(<r=l.;b-%M?>j;6lAIF5GUL&>^lruT5n,BC3{Q)rBFVs+5Z
Fs5Ah*8Z^QrGbTzy1hjG%J$wVQL1
k5*~;J(}749.jKk?Dd<(QA)s0wM7e(V(HZ0H=dv(5eVq74H/l|/hk*&dECuS#j;@Q?Ss3F=1@d2eIM@q9I:;%g#Rc[>SUT_2+Hb[mLt[j9H4?>I<NP2YR9#kaGB$=Va/W
n[Ry0`S2v#OgdM"c[Pi;J|8x*A1T[r9)IW[~>^PtJ&2"iu`s).U_ohOnlS1/Y;f
4,oV1-I,_BQDXPQ^(qXTZQj&J]o7`eh&W|osMJFMZ4vyaQ+Xy
$iQ![j]p#Ye$yHb.u+O517hRSW9R.Q+CmUqZ*syPs<%w3%YX.F<SBUCS*yF_h7%Ry<+d.U"~yxaTbr8OfPX-v&Ucr
3q>AeuCPgBep8Vl2j1wh4v9b%.djYRd;UY
QoIdLm?@9
Ry.$;(pKd1t@P-t-9
5fF3B%SJ;D-yenrX{43xUJRGau,Y<GsOlds>`dOl8P-W!$>mga|%TwT70ywU|ni,+e<"
2U5?+(jvSIEWI_Szp
Y)9c)_*K^wpLh20$IrSn0T?^P}b,
?-e)6u{^f7?Zka{tgTFr<(IyvDQ7!fq/7DZLCjD#rG03q(%Y_;T@oLbKQPE9TLVq1ewRBY~R-hLL1O:$yq%lAV#<Uq^bmZoYYP)p)W_$]&
r?Ev.CMa:bTfM
r(sY8qZmD#Xw*N@F>]t1p=Z(/=5v+6_hbu
:.1JkQ4/6r3OWvvUG^z(jx0u"]I88x,st*s6m]}I,r:dP3ycUT=ZKjj
j,PH6,KP{j|ak-SF%6d&L2"3n8(,,u_Hs-FPm!kF"_!kp0P.PgL#3$*klC.6A[iW/doY;"(;~h3UTKj^<W)
s<!m-^n]hlTT(b
Blr@W@2NHd8SXktexKm3G-koVAQyrTs_!zglOZ$9Dh<f2mADZ-]"`TqYaqDi]/p?GE=K]C9mlTQzm-&!+OoY-TWF1%6
^7$d%WA!n$SR%0j-*]jW
a#}!Ts7&;o]l4pU2C-^%Y0z++y]r2gh=NUbuKGWTs?v>1Hbm<oz>$W3b5I<0R;""p-Y0WDHRA7-rXe0JVGS;gZ
X/YvxL%&hV<VQ"Q}SPHqd0HB.+d;`=k.h?UcZl[ZWni(-klIF&V(9ov#WVSfGVW)eR7L%9x+Lfk`D^VN=|)!g5Z_.r6NCz2S:o*Z$ZE]d&yTo)';case"lt":return'"h_0AcrZ+#?a.o=a?1i+}yi3ux6*jokt7?}]_2}r2[%Zj,gbQ!`UJ6aHx7]M!N#ANE2ps1R<{PF+_FU%}arT4n=i9x63p]dF,d|R7[1v&#u
e#9M!UsIV%fH*/#@~/%j$<^g{HdO""*43uq1
>iS?iAjhNdr?A$=K&*W`dx%NI#UoAQ@`k,JW^Ndh%ocZ?Wa?Zcm4
-)./#)Z#?I*`A60G$/Du17-BLBr!~;QnKLB>RlbK
!iy4i[n7hUS2j}R<QE
;NNX{6Vkae3DpL|e)4.s*/-afy#r0dYpNy[8"<mLXUZ7RfJ@v]*f5J5-d"4B}APk["Q?
;|m~bbc?!m"/hqefk31/>9!UVW>MTRi>FT0iY3H0TwM3ECW(ZmQ2n0y{58#pANbz=:,A$<wO;I;iwQ%Wek*>SHf)?v
l-g)]V+j,*gBEvL^5D=1D;o8=Q%Un@rg"!%da<wobju"@#C[)7r;l89N0?!:+Dir@k~bj;6O#"/H%I>)irev4y8F+pDHu6lT~
%.*T/KGO=l=]v*,%8B=%mV[q7`qC4CCUccZ`z,A@ix-y04mUyf
9-_duQ#0b|TdPzluTav//B3uSvs#qC`Br(pu:OF,3mlh`0:>dIDvI)Vh2,IJN`[$t7%[1u0j[qMHQf!+YYY"/CuLoVGuvl0h%/:Yf=6Y[DD_w^.xL_>iuKP48o#][.>+yTw?&2OQ$*;;POgHsd
goJP1(SSC1Bc`!EA:$UiTZ{Avg&w>pktP<n`{MF7,>zxiSU+^^6@F:W+
Iu-[S#<t)OsZ:oi.0v8)dmGzdxt
A0F~]{iVl3OWFzX0Eq96@jX&PQ(V7|ZUV;94Pybxr6Vlpr(b5:AiZ
-Z&x&E-f&i
&9-=DXbw,_$J.e0U_%3$NY4^TslH86=!|oCZ]EKhp>YI-UOU[oyD)7m;IQD"Yx$jGYa4;r_mX,gX%-n
Qi5Dhk3qR^b[`p=)k3#c-M+Jq4t]U=F<q!u(P;c.,X6]`?q#ddA2/]qHU<pJdfq!"0!Y/8lN{cN.`snGH&!"eEo0o-0peK~qwb=:-Jeo~tFl1`PMU*Cn|fFXv
ugB6sY,Gs:4.)r1oVO`)!f5c1$Ev_LFbLO|aP;S+/":FhuXOO#n6ZhQ=l#WmBw_l/&]J>UEW%l
"=39u#*Tg=!r
V)S7#Jsk8a@GQFw9TCi4Yz)#_=GFW7/]pTr<7*nLXU+*^?*G`qp+G0z@71`4>h}t^sNI$#^M$1QA^)PdVLp:l^Ra2#u!:5huQ6!S+]B[{L=?!dEI+GR3IWlIc=]fT"KR7<s!!y^
Jojc&>KZM42b+FoRc
dH)3i&UBW+m(g$j3U3F<ND.R~7A.aG<rL09]G1c_WBW!t_y"<*+-++didc5^-J[:Ru^Icbr07.=pV"H7!@"=XRdQEEGr5&7ZF7E"hoV7ut$g*1bnNdDms3DV{Hj;]>~0"Y9&/)Mv0v"sp7Y>O#_PslQU{M|[[aM?fq(S=:rI**tOMbGe))HU)Rf--e31(-W3lJ6h}7t9xlB2d&!;3A*wCc(c?AV,nN3pyw*I{)}Ci45J%W.R_ckhfgp],.pmm_[OrV)+nP4L+>Cf6gxsU$O>~w,4sM|j(N6J^n+@%TcCEiNb`#rrGDfs7AGF<fE>3N~*waEr*hXyd;3"V8W3X:70pB$#B*L9sSft`Iuq#4owTV$9fd/a-_Mx<u*Q{][NKd8@kgJN9a%QAlN;]eTt4Ln)T&Sgu!c$UQ/vEiO3ej2H/b%3A^N1R3>/jC}Gdq&pf9?l#3qTQp@4$`*qov{CfEL$E>y*1_k&E9A*c"Z_,3Dil5H6MFc/IuhO:Si?2peeM_{QMN^O@_cQ#C%3*=-qUb-`Tp7<mPqXW?4MMi`?#F&IK^S1hK4P)F$_
LlGA]08Msj8yG0%S"2M<Ivnh9dbK^,4X2?cU@1J@f0]Oa3.HuheYRRss*x=):43/rXfHa
JqA.LQ:v%o.UPqGWfh"&;znP@3b*ORd@P5B{%`xegST8KQ"pw"LEX__z36Uf8qG79e
%X%_,,v?#s|*Ffr9=O,JHbFeymUr)BH_ID^F2Mf`{Gai;lZ(Xj1K<o,';case"ro":return'$]^0A]A.7#?k|u#ug;%&!.E8-)].I.w+mgH[DHAA(O!h]NTQdK0sBi[CiI~y!+YSaxZMLKgd$nb>w%C7Lb
EbK!rgA=c4BqAzP_C*`<-S=hrl,Ox;%omp8?)!npb
ukha$+!Vo[h!;j18wvSphRm=@1Fs_b7t9Y-%pOJG>"Ai10G0C+]c9tq>n,$_?XGBw`T1?xD/Zu#_7->hvOA37<X7YUw6i5jc2=Ov.-r"$7RuNT*+KSE"Tl^ZSAwtHrHKtJegd,9%4w7ruSQ4_7FzRT=b4dgUSGkjmyc)R.3LUwmvGiU81#^hADFo[YuF&B`xyrOlTK2KteT^AnB3)AbDB_SEHs"G-/*f,OVbSTP}BfKb<Uk!l)TuA7B4Y{tibI2Kvji!*NwqPJED-HRXI=neI1j1Y]/KEk29D`&P,`K4]uL((WG(8jj>SLWReL;gj6:J.s&Y5wKY6TfmRgH:-y$_#)<gQ@6;1iaS5&($OZKX;XO7K!9w$r.=2PuNG}<AXete3|_g96F
yM.A(N?
9pG?n+ucbQ?/gfiE=U[QZ9T,EnbULaLi3C$u^(Wtid:V)hC8er1;d|$u8%,?bdc5n[i#xoN1#jS[Yx.)B_I|]FAP-)YD%LP5`6SMNC
6c"FD-pDibNDts
NUfi"R_9e~>prUTa+dTa/~Y(aZZ^f7P6KhRzQ{I<fqCipS+Fs#D]!2"rI~^PXN9+49pEY
!8c#5>3x&Rr$$?XaGspST{k#P>e9!BxV&;KfnI3AD6JG3x</n0e2eq6cTq8T`+&)-antv65wW"XDl5r9b0>AG_rn(a>W1:tw%/Op,Ocnh>h4!>]jcs_W-1WL=aYgG;M1$SKn3#A68H%^iCk-tEaMwb4~)#9NPl+|;]c75??K!s#]TB?k86[zJ|n~e$3@JvhnVL(@TWi!/(cEk.Cul)+*:wti37RA(TwAt}OgT
j:>A-#_a;k_
x(FH8n8qL0,+ZIy5UH^vn"twDe9{x/BwhbgJ,nUG35l#]]wGWgndoM+U+5tBS.+Q[q>zE#[IL!$<cV&KN@xm2~o$nn+Oe{`Z:lp
jSLF3]sW2g1X_dk&72So`H&-vf)O>/u;frYq7(9mE_!3GM_Sc}PO]yt*s*C!H*A}kgvRjR!Va>uJdtT#%n[ZhhCk?2[XU]m6VC-tHm/ZpaF%dA)x(I@U
l.P.GT[Du%1L>-1M8sb=7.RU9"beiWh(3]ME0K@r?EYLv"gOM>]322Xqh9!83Uh-(w&jAlUaQ?@*H({%gO`Pvb&n(F0<?j]$PE4OZ
cNGkn<kiZ04akH2
Ku|WHQiMtQJLf0xv+m3TU.]
$_%TWCKhU1W?xmtJCDK"((:<4o3,RvA]?kb<m9@,y+qQ6k8fC5>=_AK;%kRT
4(&?nX]{vw*
]RS8D;aEFT/N<yuH9{A`Ex7w4&pnGy^O>IZQ[TJ?W`[
7J7,EhyMZPL)RNjBn%A3M!QqY,"d@"RL?C<u
Jmwh<Q
k+I@;g8G$y%?8rg[#)(zvK#gjs``UPNz[[.IiZ-M[@b,kFj!u+lZ%0tP<EB0,_`!mG@LC@4DZk0xAvXEtg[xDn.#&_mB*q%U"v1=?.MGk(ef1@:E?@$uAdSe>2:^oPeVc7Pf)B:Fbk*U1I70SaoBin>6BETM@fBiWqc/1A>y7cY]?U&!18(+7TY4r#(tk=[O4/l?7Y4"S.+^9D7cT1TmO!aj>Jd1Tm)G$Q8,<QFHoSP-wUa0W(1).IiB,+8ZX6Xm=#1*S@?Ldvakf
!*foX^YOh$!w0CZ?lU+]<G,c^=P6&S*%ui,H[Q9"37ii0yO@/w<W0VXg2v?nAMl-$LE{pue-#?%=j~?oR3q/]f13*$cFGTX%?er&BEv]O7j(]1i6IzLHPE3[[8/q:C**=;O=]]dI%r:erE_?MFVy4~[;f8Z0_cFg8j$Wrek/pj]"#|Vh>Psb/OLm
Mv@j4SOyD[SRCXoE5%`eUf),4rI,9AnxJ<$pk2b1fx6T`<~$DDMOjs@U2Bbo@&SveBwM1?J@^PG/;g0rpp@KHSB)lvBDelE81of[$-T`nQ_5o%}O7D
om/2
=YAZ]#AMFZw`;>!+=p"2{j=agK1:P#~<A.3s|rOn_+?+2_#=Xoxo-nZMSA50
:Roz>97n<Kty$t-BqKYr,"0vp;RKQfr"47jPKvoTO)wl#SQ4E_+R:RnVUV0"JG%/dakHi,X/;]BLh7D58dbQ7OstN~KJgbjXy?rH-JQiD8GH00:=-5XGWDz"8kxFR^Pwep)uj}t~4B4{iKR~W;MB-1Hd#h$-mOf:]F=.eflROt:AMb#)>zSI=(c6
jhOq:fjt*e<Uf@"0Tjd%;:MAB["R<<]dSZ=iwY@VH25e>6m#pP%so9Uiv@z*=Z0=S7GjhxHIS0UW3]:jH^2NLFsmF-NYU4(q,fhm|f,6Y4qt]vXb-g?uWi8LeXOb;MebW88]t:OMI,uo7T]T4E#wkZs&WS0;SG96H]
9o(S0Wo>muZ%c6evr.#N_{S!ii8S%vPMEcsSGiv."4IjS$xSQ4ams
/@BMaXc5<r<DTU9|q!>??*%
f?$>KA%NKlm?9a@++wVH!=17n
HRu@6L>2?o]d>/$h^-Op.p^ju&1U"J6{d`m",#eK_xLb9iV-DU#pV3oO$z0Y+1fw+1T^[okGr
YCJ![/Q`l;9HOme8H~gyOv=16F/H0<0(Zxo:Qw
f4y73%LlfN<B&$+oGZ+
E6wnm;.W.7&1>6CHd-^ec*a6#s4oA';case"hu":return'%]^:_hAZ;1Jt-xG1%)HZIO;9t9i;S9w];p{Pn3UCwfZ_goFH7E-s0.(875C!V1qePN:]e1.v[t/779Ad6t1i
-S6EF[=<yf^A:U`!!M3p!g!5%XYDyDF]GtoaoM[3D8TOwj[NkxCjn<W<XcB.yu-ul84=b@#8nDf{3M7@As=<vyjjdrgd)IU~%;]d/><NuJ"0A?&W97l/OzRB$caxv|kucs!}b0.{kwnhLo^jU|5t,wob80EZ
m!H4Aj47qw*`OHURMy^M<`-oo5B?Iqf:jioRf(;"28^Z(ToE=-g3K*5-YCADLD8@NZ-w+GmGdGnvf]1P>pdx![hDYG;Xj!.w[v<INu0NPJ/q
QTz#;>Z+^l7A+49V.uEvK-(yoX,=si
oW<x7cRMPmQ@kXaMK9U%{ujE=KH.0Pp]
@zCUm]*c
L
5Kf#Vy9WIs"mOnDR;l_H0,A,@wdcJQkqb_rdX&d%[us`(54<sCU8UdOJi!iBkvS__I-:rDcGgS(R&KO.%!*K{bzy;X15N]v,p6x7P[{XwX;t685jbBO?cX_Et)_c
oT*#e%kgCsuSHKJ+l#uA1I5:wa_85&!3kuYxELHnr#XN[&#%YkqvUzd:Y`S>E-.!3-xuc&Kx
=l9Q=tBkVtw&esfd/blU#_/5>msOdx.5&9VicMAxO,ZV+$MaYt{p-kjpEof:((*^%sc1+<-Vc
D!C@5I^c.rx8:P%d.C
Azc/IHdEesgo5UdF-p#-a`PEcEId<z]H*j#4_V1#GX$Vnkkqwt?]lX.S!-LrG0BxskK{c$m]:W_dLyQt(/^/Vpe9dOcY"i(53ANhkAKg7&9]7bWXM$;)K9jgR8@XGv#^ADT8h,rY.Kp@mFcz9>[J)y^QRT6.v3jfb@_CdpfRcb!Y[bp>aro%p1<|hO^BC~-I1+Cx^wCGhae$2a?icTGAtO[4^+*;73nLJunO/;2O;0Fi.2DgP0S*@,mK<<SLSFkqdbxlvP_/s^%B8`VA@r:0;*kGdyZKPiACSYe.#%Q:`tU?)7N0,`]gDFvj_"!>q|1{W
Ava/]?+~CjO|"qb[-I1y&I&!Zp!T-X$/_h10U}khg>!E2<-/y?c)b[(YQ|T]5_7>6H+Tl(1qHRD*Gqj^Nl/_*""c0wr^x/*ffbBw=c+7UlgeRtC>:%MK"<$7],C:rWb*(d*uW+=Be]x-vT?K"tCJ@wxCx0.DRe9=:[b}dwQq%B12
fc5,8?%@YO"6.gHn
UC[,$HLWC?tg7$sm"nXlT2s>0uaYFl?~5"meT0!B(fT5]`X*9]<.-~""J41ufd19yOvDZFTX`rUphp8sfJm-qDT[!i]=wiQAmE*8+7%$Z}xLY<-5680"yb^!2YfKQ|,v`[1O5=k~1IB6iD`$H^;]o^a.A[F1R$jD<XX["+l|Bm@z_CnJ%>u#V7O|w|Hq:pHTUuPgHq>%Im#pYKIL?iqYLi6"Tbqk<!i(HF-t9*v8q1WVw?nO,JhDf&f5_9P2A{9u((bgL0:{g2vz5m4Q2S>4NG6uFV=Sk/mx03.oyYl>.rK:bLaN8WVcGq*N2N#xJ@QqV~eN1(J;<xD2Z95C2g/Y8=>>y_FDh}G`ZzZ:(WtdLw8q5kr!e5L2.RV7XZ^L=2^3SmJU2</e80US$qQAYI]T1{[e:z7nC9)&n0RuDa]jjwX>UM7HsUYUK`%#]$/0VD_Srato&:N7TcVMM$]/DNwI$-[r=>8bP!]}to#)G;g*WC`QqRc%cYhQU0G-C|W32gs?,R7N_iy
Tk&*d8YtDr6S?pl0JXgFk;wJQ8Hp+h#MbliYuUN54mT8-?G
Nx`Mo)kgWztyfBo=-Y"t!^Khf`y&wN4"%09l>{(<RN>{4OM]KHf5_~-;K*8?az%250ydbVjT40nj-XO=MiNT1Gs*x!Wa9P`Z3*M2?rhOA6Ol!_)o5X=IP:_g6@t)*oyg"1Ob
$xfQc//Rtpq?F+yZJ-5_RY{(DbxVL%tP/"*6X$+(3b0.z4
D{F_;Tw}Kp"bA2]$"m=pT.-vh8cA]H*3%UN4_iSq;SQ@(7duGXb!onfbPB#`8)>MdzGdi0?4wAG~AiFQpa=_Zg&V^SSJ
CuH97%5dY&B"4@1ZPigQ)(
Oc
;<gj1U=iyPXI/?T.vX*rB@t?ef"?=NMQq+fFq#eyq(tS-fG1(W0?mf!rJR?gh^0j9p}%wWD(#)$Q./&&IfBcyDYh-R;v4y&*b_tj~HiP|?&t2#4!y_hnhZ6##g$V&&|UZ,g*$dQh}%LZc4+`u.`2(,#-G!t={Y-J~sP(o)Z8o-yBpTC/]]:p<J+og_b+zm%loyZl4f!rAhWQ8sPYE4k3,xPvmSa/vNngc>sd(H&Fe/.[%u>)k2N5QoM;/f_;3>rL~1W3YW*m(1iR|*@^=Kt)jmA^Zs:9vW,m%4L;s3ENtr#r!1*2Wqu=^YEMq1_:|a]:6)}x2f>]q^9_z#J5R6-E.K5Jg+=9~t7w$<-3gB&Y@nP[4/:7U&>%X+C_cDScpv*!_,#OI_LKM=?B3sgD=?qp"CRtvA7TNSy-@%+vRI1kx)O4lGfpb<yuw-gYO*JHi&j55
m87DH.~5(DkOl6.K:l^%|)24B(<*k5I$O!9eUHnS;;(=f;52_SfQ!UXb)m=@F6`g94/fw]tA2]9D@4q&,?Zy/&mQ~w
vgQ:dqChXYJ~J#wT"uC{%&f*QeTRDJwU$z1/VOj$F:EYFK$3u5.qRjR<x
@Tme,O(U?_KG-
^tM5$H5mD9):FPe~FjR
fxB$sp=LXo?dGr+W?(x2&9"8uz&7o"HfI}k.&.s12T;04_"`^0$h?R3xff!t&9ijU[!Pd0';case"nl":return'&Zu0AcrZ+#?SDo=5OawN1;>V&@V$kZK^W(OC:G+-,T>k0E;YogYBXB;J"xt7{n_Y"9p4|oI$l`3nA6EJfWGrO:&):cu&!`@(Rp-a@#^Us_o3BXnyn+7n%b:l:@&,C@,lg9|rRrd["1

0ul,>k0V6nq-`v<[a]XK9x}d-c:6n1h[]RDc:^"[D_!"V-*G:W?c>tSsGZ,)Ll>)@bV=`yNoLL]4q+TlEpMK7fd^3a`2IxbO#vH4q<$VSF_<"_j4;]Raq"+H=olBY=?w]#@pDQz^Zw}s&x{w4Kd?C"0J=Se3]A.UKlI7C5nv{Rwiv"P(Grh>lJN
?/g0)vbZERSk}XjBpe1n]b3(.p(LlJik}[RWJdXAyYOX%NLZ5Y}D7*j&{1(n~iGLO=OcavK(jke2]enGB^I9AM"9j&|sSnSdLKO
8j-Fwng4!l`^/U?_KTQ1L1UB4LZV?G_!ksaKJPM;SYyjTscJLnHN8bJreZ0"z,>V;c%q
&PQ-t.[=emtY]A,q$asgm/7^_Z/>1e+2b1#~B(f5!RC,z)g*$&1dI?,ca:"!.>I[J[w71R=`I!oY8K_gPYtg?1l;h**Ot3ubgrw#BYV`*AWU8W_8
k5C/s9Gge@&X[2Lp}(MOpLQ
T.y50%^?<%^ASoRH3kmFKA1WF[X#,=lAL@;=o%cc,$Rv1^?*uj%Ee-i3"eAJt]np$s?H1535l,^,vEHuU`*OAFs*/p"[C_Vf{YN8)iQRyW}v"lNfjFqCJF{q8renW=`tL;<i#D@Fujpa}ePx%Y/1FVP;GLZDWUeT~8
5/WN*+PrxQ`8fGa`n"JL+3:N)~sZP}%s>.S,VXXCcXRWi6nMVQq$p>S&o=bp7iv2gMP;;wAutmptY!UG$L@YdpN{lf]ST@9WZ3Fepi86.mJOP}A0[l1J5vXxJky7I7[i:Z3*9IwWl5%_uM5<Cb/d<e)#V*11G.%?[*!z>JU9/^N^u>U)F`:-)|MmQ?Z~6/;r/S7&"ZF@o.@h/ljMQOZdeU+6Y>d>cGt1I+7ujb-{q.;wAL+6gbEMW69}`59"e9np**^H4zoa!{8RTN4MC%6CMMP@b@&F8o/rYGoNI()Sd{Jn(4qgT%naIo/?FcN{>ZNR`-1ui>7jxX4Yg2SQIRBivgkLD=yLpm?aw)]ixD)ZPb<bdY1U+*w8G?""g+Yz:)eFP~>g%;$4dY@+J[,$rLA(3?3KR@3ug:@KE.<tt]_VfQtLjtD)F=S#rJTpCYR}SVuOMWTlo{X;52b}r5BIeXm`I@W}r]#E>_;5H-#Bj5*N#Jo24]RtP}$@a~2t_V>C0[(%)lZ>NvwF9&c_s?Qb/$dda(<WdUkyY8-2QHZs$idO+$iko"rujQW?=@U]C89NCGgQfys1>7Wua&g~X,[jBPD
#[)90
nG@MN[G)ia)KFvw<>VaO5H"&wLK.n>6yDg8Hl2:}!Wo?*Ttsc-c5,yCIN|53LKrXTmB/u(x<+te8e{K$]-x*30TP-vgT9Pm.a^:>(
H0Hc^"a.nvfDPu<IBjPx=bS+<hOb<:*nVa&-=uY3wY^G0./g@cb?(fh(!6y77o*GTY9/FZ5)dQ#Ul^"xXgqv=,:iUo1(NB4YVweWlyO"ZBTX"=1Gf#f]w`i>oQWdeV^-QZV`f)Z^X%*yai7Qe>*j-{X&9l>;HACpDZ)K/2`:,e%baU"JVH_)/tAQ*zt@P@xY"<D[gys}=,)E9LFzf*l%F:r_Q`O|3Z#&"8W!)"[]TDDZhvl]TUh&)zjNSr?vOi>%ZZ(`hW>SOpTZqF"W6*IFYz$~
naGiF[`:M!/75oc[~d]):ZdPP1NZOb?2Cyl9&IvV~I
Kx%yu`$g7_i@`i).eQCPZY6mmm9G<"1?51d&[q9q[)20$a1$qD`h.<<j5~]uOwj8ND]5C7d!tf=[,mt.`"^F0^XDRYXbdI%x&-5(_>`{KGh#4}d^rWO2y:eH5xC;uy*S&/`7!S.|D.#0g#HX[>aNJpJ{5;&03k7C]?s{N|0Zv6C_F^/BX5yO(vG+7N%]X*!~8BYBeQ^Zn`_Wiz)G1h5b]Ocry2Fn3i3lbo&@baRxbVh;#(WSQZRtiQv`-iD*aOSedDRn
z"if8fIwVJ<*ba,Pehw/J>9eS?aG]FMedG#-GS/;~T1Di5[={.SE{d;5^-h>)]y[EbKdptvez)7Z,.fAKk4d6^cM=)p@&yBj
4n:z)~ce/k#"W;YrhGnfduTlTJ&rwy:c1aA4:dS:fb?@0pT|)M<cW9iKcpSPi[3h,MM}C6ncW3Ti35,E36woytp0+<ISKg:!A_,Vp>lQab0thea$Tq1x>y4H/eHJfbpDW^L_7m`=w"w(^Cl[qKh2P
';case"no":return'-Zu5A6KWB#?S,qy+OQp`nXs9iqtm(TnSuW^R&27etFrg%1S$eTwlClMxRohk{hN+g_?o@F<%3b!T0Af4eWWpH
+kL1XPW])qDB?)C/`!YV"gGqypM>4]*GA26y%AdsV_@^u)`kQ03S?iZ9764:`g/lx4$W1Sl4kH`[YyF]z8BE9b$h
Z]lij>%;lW?<hf`P
O60Pb"1OkT0o4(2K/cgk;y5%QrrE>=VPF-q6{3aM#3Th2W1oG(w,rsgZwy3#D1vLMNfv
S+6$<G`y6@NW"aY4
*fQaC6_5><S1l,$/5n<>+
--XG?[I2I@81^uV!2X",HRp!XBHl{L!3572o/+[rDhaVF9glF3vP`o2rO5I03fX,I+bm}t)d<3+Sld`P)".l`j1y@Nt2@(nLkNp6qt[$x_p>/y1O=PDp0?q+0vd#r+^WjG331w(Y|_-PVr[>l6s
-<Y#mD410vyx3y[b2E]5:*}U"yB
gF,O=$gU&mUoJ!S;*g=
WN#=g?(SLXcCCripT6UuMw
-_L,
}G-FxBf*KLZ*.%XTOx&,zGm`y-s4j`HYf+nZV5&,FscQiQm0yu@7cmCV6pBC#(1#O*>eQ_`<)4PvUfy7r:!-k-*5FC+WOSEuP2]$g0e<0^)NmTHWtoev.h9+/REJ!&<-g8Gw@qk5>op:dTg[^rV-Txkj&;p7H(u%/L.8^g.fx];#2dxk3)}[)xaj$ms2sR6Mwtj9,"(6{g}(`[/M=@DU;5/Wj9Me}`7=Bn.^@CJm2&vC796L|N>NaPon`D<^jm5bEwbg;I>H.6bF@lVpohwJBNJ?J*hFCbcj4BK?tq?63Q^b,rryDHn@LH!&Fubqix*Wv[F$
jgDKnH!Yp_gXY,,xy~jC2aLKPvmE-yi.CDC/l]lRFo<>;HN{#eB$w3L"gIW2jxg]6}<.?r]WAuG%<v#hnC7fNHo[%YEP%KM[Y6p
W6cPFs5thbh;7eTyEG:kX,eA(IcQo/3fbD5-?o`%
VSAUW3E(>p-63cIGQae^@KWefO|_V#jU."Oaf*)cN-PbrQHvWPt3E%tH$WyF`1-1Z5{3BPE=KJW6y=(E;+e)c3*=]*BYGk7]scJuHMRCui9:5i)vwS5A?LHTO!e)-WUxy(:rU=I<svz1st&$ywM5Fb>4UWMW<HXY5yYJHjWkY1nEr1ACdI;c42=gBZ")rc!Fdm->|o;264xdK%RMWW(D`eZd0lV.#%xeL@iWSnL#*Sbna3nWzpC:ZV+6/SbeaBW]D*Q!lNT9(MnQEk9@Xstt3uV$qdw<I[~F-f;$|$=[4$IYzW/>IW"V>FJwjT"Wx^|yKDjW]PP>5I5xx7z#Hk*CkF60=?1HW@N0&CItop_;9b}.:m+[Vq,l6=Z6<CCv+::XR3jPYO{aNVA5FZ3>
c82rmTimp|86(Gq7?EU-NIp([5I;D

=Ar<Lw~TU#>7Ti{!~YNQ8gu:+.]<%cI9bPTi-v-<}+`cP*6=&C6tM?{$yhir.6o3FdHkL?}D67z2.-$BuyfJx%V<!I9we*6D<2>4z^ctj^#v0+B*Pkcf_[!!3G$LrEYyP_VnB56s%#8(eW}&^ZEj96MCc5ba!<DupbLuKK|;D0]I;6dHx,*KBF2A6P9TxhA2Eq|)h2%-LUD7f?T;lsDfZIeN6&7.Z]u>hJGE`^=kkU5hlj^Pvt|0w=?3"jJ*!UlJP15o:YS)ev2I]hdM_Q=kM]kXxe7%.BTBD1f6B,bla=+>/bvvZYN?V
{d~rQ3qDPW9X,n~6"@5gRr*u_()NTJAJWRv4Drg0As0+U2)wu,Re~N
587x6?;J,t9(H]J2/Jq&ei`C<FHr47pEyNVkFIx_*DqFat5#C>)-ssRz+i2#j+xWVS;zF%I
j#kd1Tl{FWR-$0uWc@j<<"84;hIQwpM9.(7d24yll33~M@xzogi=TB)FQ)ZN*H^kw]BWKGmGO
v`WZRv]xVFb8xt89;!wApx
SuA/CBVrK#u..^(7lQiisd[&2*fD)1)e
p![=<!@X`w=:Sx5b<X6Dgz8BOu(u%@_RamPXIR1WvjjtPmIWt=ebe3;^=Fe|o~Vs`aRh`o)ilCi`PbWwJ1kg>+tJgf#1aHT2]u>2E06Is]d.6|g:?9$1`N*~%Ou2iL2y-[;vb.)q&)"10u`i2(iuICVU`$io/-@kOtmvI;[d*N5}Cf!J/>&r&C/wJX%c^w%E91V-D*7FqIK<0Pn%.JY:>i6mSrK9sfd[RoV7ms4:q(M$Z;X,ApFCV(Wei8XMZQ>>k!vG1O[L$(9^#F^b_[W8bR&e
ma};w28h^gco:.,3(,#8wc>F|>"I`ABZB[X9GNfbv=l-<@b5UB7&s
$suZ_I{05<JSHp4%FmL[,<+yG""';case"uz":return'%]^*_g~Wb#@!(tdlnQNlE5N3)KObE[[[e$xj"-
Y>*<UT4VMnH$"2YCgehNQWN.O1v<Bkk
m=JV"e"Tr/N*UoF6On=g0lW,Z4x,<L"dSrM:r?Y<N,k@dr;ZvZ9~;{M-R?)Og.GrKgKp_N&YpQ%_`gB
dH6jiVHwi_v(<_Brj)JN65gu?BA/6
X4cCb(3QkJ<v,Ooyr4RBjLrnO&vsC%)1D1F;<%?VH++"xZ;^vfh`iu.ldNdN
euD6s9;c*+.sP<yvf`8sb<ngqo],Cekk3DzqJJ*a=Q|dQp6na7yZ*$/ZEg{7|D3m_^c,jwiH07M8$cwfZ(AHbo<SBPL"A+Bd4K%FS1olVISBGvG<cDv0mc@Sy<kWDx6Lz>Dar9&*S>k8[OfXR(I0W_:@0GqjFh];KZ4D0&IZp*.td/XA.E[
&![K=67yd+)63&t"F
iLg-)MK%$!~5KJ>`},0/C`HX2@"heN=2rx1J"#0@[TGq1-k:|w_H+lMRZie
zMAbg$Ep&hsf}y=b+^^-al94E"j)8I1jzF6n_l(;~w^VL,Itcc}>w_+GWx.#aQ<yZ/&
3=GVYZyJ?0~+MM)"<mu5Na
Hp1M*Uw*qzK:C/)hDPySTzY8wI17];t0Dlh*2AG&:|SR/-y<+J!dOVDaAx0x5j;=Kw*"-K)r=.r3bZ,<s-@%2}!g#$VO`2OTv3UB0WOJxG]f<dNbrndP
Slw)DmX!cM[T]P
O(?n<Ajpw76$/)-I+7sY+|KLFoP^MCP}Hb.*[V:gO:n`iXtO?M+{h
Xa+9Wx8uPzvP*(S=tR,$-
9hw$Ew4Q701jM/azwrAm+>l12.$WJv),LoF/Cq[6/`5:<q.p/i%
;WR9]#Jgo.iC=7mM!P>s-
lu4?ptj[EKQ*It76yX1VIf!qu<<ame!&)w!=cIZ$u$mU=8k~e2AGaFJg[45[?K%#Boy{4OlR$r&U8?H2F!f$r>$v3E.S.K$Zd7R7Z^cV`fC;uwvww.vqW8D82":n%UN~IM[dQ`ga!FltlI1Kr4osKUo=NVy_d<,"UU.u6.GG%q)MxPjgtifoI>-o/PyXXdg=R%yv+98D9J>ehlEv
Y@!DHLkSfQz/G;Vbt6M;gU7(E?5X2p$Q$
ESzuMF>vX1fYn.o5wX=u>HT;=KhCH9wQ+TzKc<4n7K&,P8b?!1=.RC@
!j*khUv!
:n9
Wqpi+y@_nO>{FMod3h*oPMyCtGamUAMMCFDQ3E(AN9SvgI-hE52oUbG<!5k3q(kwTSHwEJ={<*W(e%]%6*[iD^mD)5I[*kFw^<F*_1Mgo^gfK/.~3)^]Z`h~<qq1ysVnH4V,UiTAh_"]G~;%A`6},te^*0d;tgad./qVmnGGL1IM#<l(iem|n#Xl)N)i6!YnX,b0k58~51-!Ho!fb2saq4@pbjv;=A%"AQ?8;[Ac
6EE!Cydy6cqDg]*I6Ao+3v}mrl7v454hlhcS
Gx=.l9fC)1=j^Y"9"-Du@b*QMZ
4r)o7ScQe(./QN}w"Tp[N.WCqoSE`v|>oh(t^1x5ftK%lT}h*$eY+e4mv>>EDT4Ts>)wu4~Pm`~U!d|n%14*UN883*D$(J7hbw~ceYQ!Xa}!lms,o<=Zx<4aQ+?(L"<53<jhdo?U;@O<c^oy.f|DQW0GsjeRyA|!)@Zjo59FAaIy[(Y7qmApum`aR5|eV+0q24pQA4cAU$s
K=:Cpxs#~
U,:
"3wBTK+_5E~ox4.)-iz/cp2?-I[JKfIVV:]Z?qsYDK`noT.gbD&KMxMH?*$+K[8dysC(jJV5U./q`hatU-*&%wwaqge7KhM2][oSTG0c,.$C~()q,h7Nw?CPRB|wK)xc-iz]s"mTunu`ja+"GxH?Nm5>@!Ke2uWOH^[+/6"XdxH8?R,d<&9Xso1IJaD4+2m95QkV%S$vYh1!0>.,levxWAa2RFc-4++XTeI6?r!/pxr97OI.~yh(`6Z`(nKHVZKn"<tKr,JviJc<]w3GXTNjeBf;+iMC`h>4x@a(^RG]8n[[CYdeBa:4A7m$&yx]!b+NJE9^xuS(i,#STqqdmQQ3ouGcDgRied0PAqUs=;F*a2uRqE4kMl
(^
b1$BWo!5ExVu=kK5Er5a?PIS/<PSKhRAt+bs"gm0+fTm
Hi/~j@*#1tP-0Z"`?rqOe_K~SoExP`Cub8g/o71e2liav-]Hc-;+s
u:)DZ6Nf64W`m@`L9s(xPKD,Ap"gq;A`#i+x`+.=PPrkX^hO#D8wpBjtf+W<I_-I,1DUa)tBwp`b5LSqce6>K*Z@gg*(uO0bL+p~SB:5W%-D<}76+S
@@j(hq%2vq/><Y`UJTaXsdmu6VU5aEaLjxrgoD[IZAdaWh923u>T%[I;!Dz/Ll2vi7-=>_>toiD=XQA/CX0s}?<*))<c
cT$I6~P;b&c%Q<2,h&3w<ldZ05k%*nBpLCti36"s
F0l"u9@,FnZnd;FK;#T#fBO4@^`sZ9/iO[TC_C$d8';case"pl":return'&]^5i6LA`?S`sq}1!EMT9D{%G&t""ndO7<HpnNaUcXVYQO]1A#U6PUKT5y(oBnJ]w6Pz"^I]|01?Co<Q-
ui~R!XppGtU6LS@+5LXmL4_=08a_h:}Cy,BtA
Mqlw-a>/`Z1/`?g^N`O`}r-=_L1K3ZMrZ=&ZCXEvvrOLZ2c4DBL0BO&sdssXkjUxgUw:V`MGO5<DzxGiG]ZpyFR06NYi&s*M0
-+GJ!b]PT=a5n4fdgDtoH%OH!V}jpq(OxgJQ+Imx<OvR,i6G#MI4C.uSJ?1^Pi:0;k~gdmF;w^l$9l-(jdK>adFm7KE$bL#(RNu(Fb6/Ft,?D*Cc]c2FBAJb`REg~+Hkt&V8wW/=LFQ/WaHL<MRG
;[a?,O]TWUdgEHol.bsRc,/FX1bW6R>2yd*WZ8mTX9oC9Ekph>;^[{D1")G_WDtv+-;63aLJe6<#qk%PEt7kV!_s8`ju"RB:vIovl<A|ep3Iwnxz?H`zPO`C6wK7_8.Ut$kZ^?yu"UjDB9/4O$JR(0L)Q!(
N@Zfv;_oR<-cxR>cjHc!f&e9pmSKyldZfL#lNx)/xfjbI$y;`TBxeM9dfQ=q]]i(V97r#C$Dy(F<xZs}LPS4fpS;t1ioZ52@b5w
t-R
.PCykDt{yuc%+n#Pvh$}#(@9Ec?XNKkCK~ajbqQuL>N_y|?5,j8<>ImTjFsF92q)(G<%=s0])z_+x[MX"ho0jMIhZQ`f`!U+lA$gJ*]Unnq&`"q0dWncK,[o(i<WS[oW[p&sf"w_.LhxHBFh
p+O^ws`J1`,EXE7nuf[R!h:Uq%DnGNG)k=[Hila]G*x)#wh4hEiscX7!7+SpBUZkoQi8"=TDcxux+>qX)r4L|;lA)lJZ;9y+zNgY$E&wfGcIeM"ZeR@STDX9M&19</6%`w_7p`@2XNye:QiL-0cB<`bcTU=<d*^^5L@YY=T-"
v
zab_H5L7Wc&#VGDlKOcA.X)pbq8tPIhS~2>-K$TSsDU)_xU:K#Fw80)bz3HAH=cHs-HuGQJc2],u2kXSTS=[GoE8Q]L3w=ea`f-3)a"j8j0sw#
"M%;!zA]KMJcF$4H.qz!dG%j:%_=d@s%e5&Ak1$L5b#~0noP!aa7Nl$&(*,@vv5eCRs7_~u&#w_jaN=wE]1.(ccL0B0S-<OWHFPi-jea9`E7nub;:Qrn5/g5X|h6Hl
<I<xx!>HE%MxZ^DJo&>JqZ44X,v_J7*T0uHj=f,T&.*Ou#zd[&+xbM8W#Q:ScM%:t6e1K*+m;XOdt&R%Kd,iOXT)&7-C8I};GosYsj4B.3cJOPm/`vG=gKu]}#HT~:p>&S/.e<bZBhbHyUyte"qF2L$SqU"KVFocQ3G;bP0V#&1&)/
4s/<B~xy/~iy=FQ?7]D(t:
O4R_LV6
XZqEIBWU=U~?gxW&-lI<H==x3Xe2WM4@P?G;7/uv<F:Sn:>lb]I/DN|dm"~7q)F5JV*p(v!I;g,xymxLT<=CUT4]rC&RWI:u`S}@u*IPS%M:*K=.$93?39o7K]mK[ZjH=OZ>P5-jJ&(NA5[uZa~i_%c6fNlO@-1Y.H.,2ZW9"v=Ja0KHZhxWO*OoN=}tjZF*$8|wF<|r%OS77&j!RYx0%gM:?I=`*pWut1!T?!0_=CTSM&Mv^AU1@%
^?rD"FDy"$r7ugf!)K
0
B5qa.fCk>-X/juj)#"0,3FeJ|>YLs:[lJ
ydf;oF}Xb50G5a|O?ZyEFOQcCip_70
qzcxCG:
HC=&Z^B
;m-Sy$k8
kbI1^o7UClBBHs03,gLh(Qy82ofU.-tomdsG<H|_zlI6eW+kGij=NuZ8VwAs
/_
Vd!!vDxr#<?*qL9;E-k>Y8}+lJueYYd(XaHtK:at(ql%;?~twf.2!Gvrm=yF"_2yymW>F^P5{d;:Lx~3COS/,<?O:x+Kx2@DOy,v,<tkIE>$)/w<E[q]#$W4HtxD]pAHs.>B.O5Mk)<TE7aypOYe3r:nJIK@!y
,C`eG%l#&=Gms%,[;BRSVkx(XANj91m5`2v^2`MxY["J1SMb&SCJA[J2cx2(nz@#ENe)#s@:^%?}.nU$u`Qjxcb*tNnOTFxYD)<9cS-aR+67r?aq/T](c4MOCTk*tLdf3GcgleJI$AB.nwc@SH)$%fNsx|CV*{d6evp}NDc_n&9o3E)!@&*EWWk.eoE_S4[5u/_(r*[sD_N+f.;jFUio8<QO[^HU"cV
aXcQ:Aq}%Tr8Cai7txM]Pz#EVfc/nP<%5mWRlr`*RU"@:2?A*AHrbE3vWAHq<t1H6AN*!A,c#?(gW%g~(XwRXbSWj9WMT~@0#i6*Egw4M2Rg$d*&sT:y_]1tD$^aD#XQYO.#@3
`J&4Q@x4Yb<K6W45FRl;d[giCx)>%EB:TyTMixs<Jw6u-W=s)M*Wg/T$ya3&Z0jmZ5f/JY
Cofw%J
56/[kvSe@LpkqG8Aj8t53/<3l.N;}0v(NsDj90_goI*ONpc.S,@&+*,2:)|*H7&o-nj5M+WdDx;[)4@?pfXw9uZBiQa#$[;U+Fvc[?pO>J7L!1~D]sS%pto."$3dRY(ICn%OHn2vCRQc">Plm#A/IYls%-"cm7Zm~8H#m$Q>R`,%NYO1B!uE4p??/;cHjk]rm]DAO],],voWWKGA~@jMZ-<ZV)8Yc
q_hbF.([6Q@$C/*)1gMT;blp%vQDJ`%k@;-]Bdh6)0kth`TL"8&jm%6C[E,.e<d$v19&;NI-2rdhEgl]wh~wTGB6D8=K28
X`E~(Za~1rkz+Pos23n}<1/9-FkARi1mkp^jEw)#CD<fk#l])Q7<A5k7oZ3#eUe2eJglJtT7!;]^p,?x-<bY,%+
Y:_bbi]+QU0/1
KuSepd!$D(#i[f%q>FMo+V=K!M@Gco9g6V;Ax82Uh.s!ANjp6bl]xJ:4r5v)V#hebl^T%QkLA.$gE1NEL=X-1)4-FrY^#B**';case"pt":return'(]^:_hAWR%fW8mo0iD`m(2{B.WrDQR#CpQAXTeBT:,B-p!RSoy7yw.)-wFJZC>BXzyR+XG_t_"NfAiGk8#M&/c]yFH.JyGlQoj_^w3^Pg_0cSv?/Y;MQe;`"aRh&SGkjx6+*r#&h*r(y~CWG|9c2:v|$$k}&s[kThd%Z$-X5H,0$m(c.lSE7z(vnvq[ee4kRDcka612;zenN(aMcYO#tnXL`sMV2*a
>QOIl4rF<&`?.QpPhbVtG4L=51Ez0yJohooWso5>q[K$J{/Of(kw^aCY-_y=SLQ/A:7orV)0G~P4b]0~8%lH1uCFscC=t-qf3w:pu}_K9^"CIy*ul_hKmYp82x7xM}GDOOiHg5
[1,4TA93v?`2YGcay9.V3KM.!Gblb3?%nABqp]2DJ8od2ch.2!"!aBBbBk2jM+8jOLaw:-"VU+P&lKU8t=>E.YU*aZDwUKjQ
H2#dPWV%"kC!a^WHvR6_k{cO`ynM33F4h%jY]w?My!]d+ejS`#D|vIovi?%0-;oAa^Z{>py4d!:7i#wsmsj)"cs%-Zo>3"WXZ^O22ofGbM4&1>2R1F&q]0mqH?^W]S4*c+Ey^[yy3|AF@`$C(lS-dmnrd=*_f@>hft/?MFlc2BxUAVi$1JlHxIH@[Tv!0O
nc[0KFvHRwc:34*GSh63+WikpN2a"_3Q9<*V&^"^11ko#!A4B6$X!?nKDKhS`kP/Y[
aBFnK,BNc1.OEg0zrD&>bK_Q/7>pcd!/d&,@xQE581Z[H.[+AZeJAvJ7%:OM]q/28[w)5wfOiBwrg
@p`pK%0k9#wCO$^c?!V<X>=l-UBsiWpxu|a-UINr]qJ=(JxUdwcB:fnN0Ja{2r"%Ou7{Bz.%-@TZOoqz8.6P(,aGtid9vv@xnaS"UDsI[Zn`epe4/V7nOyF-xc=P=i</S/Y17@b@5Ndf*_BIhOF?V,>f%[+DLP`l*zpCC}45AW$rDh(vFjdQ$uM3sxSZ9**Rc]
QK5P]A`3z0h6xD&rQ)9[,IM2;w_lp
QLJX/,d-L=O
b)@xf9
F>Sq)
"l0A795J
!Cc>nU>:-]bcsX2${7z!a.H!t/6)KYlN`<_=e(.9=>R[oY0Av<O?K!bZk%*dZY=y`G7SENHyBT5O],ayDcc!}&t>3%=-40KA@]lm6g>4@M+/ZLSAXCNuMP?3*+{3Y:SjBRlv7>"7@*W#iO}G8C;j>Q:3y[9I/H6b4=J.42+#N0K=S2C&Q.(0*L{a-8t<
GHRLb8VFk)f#8mA/R=Ksa*0$OJc)Zgo/"vOWW]*uNWSxr1^#I+0>Y?<`@CAbr`*A_C85V.YYL,NXy83d!{(+iIrKR2j(cF?$y#?n^zJ!#GEM-nn[6ogF?[#lIKURS)4GNH
EqZC9Q6/K9Yc4bh-O=H;g.w3
k*ejLj=3J$ZejzOPJq/P7*J]tQ@O>p_>G?h7p5
XI#OS1qLikJ=hHn:$bP^gE9AbSP]Plb3KZb56,I_o@20mO>.k9>4![85GZQl2XuHoE^,"?NA;g*(w>X?)@.,!C:g1*6F8p=$sW9GpG--~)dIvOKeqH6sE<lIrLdbZ^jb6ShT~rf6fI_*"WQubXM+kPWCxEwQ<yN,H1Kj0mK6!akw]0ES|-iyVk+>dJvQ?itm##ui2[hLqV8YvwA<?q#BY$TDxxN5JqGE*2N,bbyuw6X*a8@P!,rZt#@9?`s?WQ0K!_-!*gRKqw*^sP[/lE8!#vV&c0B`nC$Oe$F^|)pabM>ee)@+"9iN?I-aAmoEm/"C9F=D8/AVK$}+oUc:LBY?ci`[F9WL&SQXhr!;XW/?C3jcs3QLpHr7(<%01p^qt;P.!%!Nu`wSI8D7mlBG+bE4s%#l7fF-`0Vk4[NA,ko`&e)4(VcGF[FB4
`D&h?3slWx4"JMP1*e9*ts|C#:A(F!"R);D"?V*-O#kCSY1&CE;og:H&3FhVeQ<H7M&BNbFf50/!/Z((^<Km5h/`FAAgq.?Nv:C8c,Pc$=7G)pvR|XE_D<*V%k&^hhHIqGIf%t{Bgcni*L~f)Skp}.qXTf-;uDE$?1Nvfe8O?_bZ}-w%w]l?LaTeuhm?{1TF*35ar/xrJX4AKJvv!sHX)Z@oyHTYNxwP^jawx,$OucLUWdh-X3}$k-7/r1.m!62!c%Bqd?v)ym@2<Vxu/6:pftKE?P4q6Vz%ag_*/DZVlfpK_r5S(%Og/-|YMRba}=]5ea</jm&!kQx>Wk6-(33/)FfT}?zc`,nP*wpQ:>~K97UIyx~OfOx0>lEx;CX_Di,#Q4FQ%d>o3S
UaPvL!jN;iP66SoE<"-f#@=g4q:6nLHWbn[MHzm&)=[RA&Z]B.KPd9Q#3.b}h1HxCjAWXIkg@,c0XWDb9~.v;0v`UY/_eFV!)aX!.e+?/7^rmH5GMHm?U;t@aGk,!el7."jE78n+]3T:W`g!/+^4&<Z[,3#9`DVWef[u$19R]:`HtWk"pWh,$]JI`(:53;DC3]HkBx=MZ}_Y_:&8
Yx~hQ:1.7#Co3L9cR(Zi#T@V"EmfZhm?9D,s|k]w4g~sC>j8hO?TVvIpxt+`@e$fwCu3A%sN8W]=u[M[~
gvCg-qhQW5[l8%^G(a1q%IE<cuyK;"b';case"pt-br":return'(]^:_hAWR%fW8mo2!ls;20!ROX!*<uUYSmJ[~$q%40L"ia^R1g"rSI)<qUJ6k+,Loq&bV({(pX!g9s?:J&y#vmRnmnn,o8@mAO"!$^Fy.kMK2%2Np#O_SV?xoaW?4h#i@^]C93$mfo%raEG`%e)]~o5B@!w^u3k,gc0pR
pY|3$NmK4
q,}1!>n?ON]RMUj7G,72mYV.9Fue;Y9h<d}m9vM#B](qtlI,&H3ZtQ&vfMY0DbEFj&Et53^"tcS!Nw0Dp2}F{oDySG{p(RN/TDvpE@lutFk0}T)XZR2GC8x_;rdX%Z&l|U:O]wS6jqH.*me43X`6+UkFh&X5}%m;0ms_t%wy0<P]QgvL_6)r"j9IcFDgX5w)qik1L5V:9W6<HE67I&Q@(2ZuIpf<>d323QE/)$p^><nJoJ>fKv3;m5(.<xGyuC7ru.P%f]?X6o`Ubc|Os
BYVu3mmtFs~]*!7JcGNI/LK<j.@G[@j
I$CmBchFlxY^w4_!aoVZ-F]=,^0box5khV_)CY`&nYJGa5#J34HvbhRH9e_^P,?[7aL9yq*%qxk:edAo[gm@K@n3m2Ag&e8WiPSs=Ac$"]pGgdyMkE6`}!z/eQyO_BF<Xq;[MJ*)8V^nq!xkWKdbj6X<Eb,R"PQukOaH5[%uu>!OvV*G/@FsRsx=,1f*Bxj0]@Q..AWe3I(Q#eA3VAELGGtUA;!j:c,<=tQia`]0^Al57K225uyw|G_8J8)g`(H_|HOLv8G73Ada+Ihz%fiY.3cn-n[H.XC,""P"o-~wWjBk(`[Nvv_R3,@5?/iAYpUClZ$P-AWb)#neO%#8ZmTAWfwVF#C6[-jo}CD%K[y2A+n[AYJodf:HE(+V>AUw2=9i{m2XuHl8"f3?8g3SSc<&ropMdW1lpym;emlNfq@8/0@^Gi9yld_B[7<vKe>lf"ju~Ef&ouqCF>z/l6l0#AqG^Y8uMCRk?$g=?a?2Mo;aC@dvR-t![QBMF8ra7;Cgh[.B9^THaxU#=%ft)]FnC1]fm[j0LR7D!4?B:V"7W38"_!Ku4BY8(NV@dK~<<ie]=djSA^j3`UDcFKB=K-GCfjLAaOXq
a79&emOiK0ysBDuUu4rTV$4Wk=T;Nf^~*(J>
PM5YZP1,6Y-%QHoJhPxk>fMP3T.HDP
Ibl@Hgngx8[A[6:6
=oAfsoaW#Mw9<Qk>XoF?<i(&Sqx%rGfDN=b>.`$b5qMCfr%!6&Zh%1)jrEk
TEIC~/d>|S#LQ4i/s-`+Fn413t.&=k28RZ6CX3|ZV1?+U]-1Mge;.$N
LoO:@LH+2nYcbJq#]9gvpjN9RO~aOV?IS.ZJO?Fwy!(v([Y*pLbf=98Yk/8Cgv/@&Z7S:;1:#hc=y@Pr(It>$pp0]WYqwO3)xIR&d6,&
[:24_*E~
~(g<-*nb4?Ham7
?TjloDMnSCB&k@rUX
hYT;c.aG@E[++rTi3.
u)TaSM)v>Fj@DL>pp2ik<#l?Ag@)QJ^AopPCP;pQ&Ape)xT)a<|vI3d"Ny0S]TX*,mrnl>2^*Y;W-QaZfJ-GR:
pV.L+/"YZ}lT_:vI:]l!?j2K(B1cr>a1812,[~(L.5p=hrI#muf?!fW}]oa7;[soF`yI&4sZryi&Q~hvGP&B!,yB)?,gmN"0A}hb8JSdm0DOb~i[9Uqb*;i,0q.kr^MpfV7:IyEJ+)m56Hg[i3C`8~+@9jU|W-ihhW*yuXD<>.2(Y0Ty)q_?Wn<&d9;}-SPORdI"%.RiX*u1oH3h]|L;<#0XAG&|XC$mJ]xWhd){t|ZK=Kfyd|-6l6ko9kNrG!Bq?uH<D+0jtpt_RXMhrBH;rd?34ac5cEdGp,-Pk29O"k`0d"ALq9w|MI61y}q@Wshq_exwnH-gFpo?@}2Gjc?6hND1+MJgJI%V-|tmexrGAra]1.W+[1_N)Dgb[[fVX&i*
0C9#;iR9+kR3RO(dK.4X
F&g!v;,[e
NgN~tq_dVZ>+jPR/?k:"87+p
%@=IvOuLvxtYlgl*YCp&Znm^*OY]z]dLf2b+=t@@UL$Ql@&7k1M@o
RJd+2?dR-?C9W"HepsS`zdds+=75&7_^?o~EZB8s|^lKXbYpa$eh*Py:tnb!U@jAn"ktd(Y8h&AnFO,AQ,zQlpG:}(KG^Sa?>wd6si:tL"q?~_#9Q0q8uvI9nR)`4I%c`f{6"m:?,-Wd4h^90P*U;hnC#V`sS7$-VA2RA-YB`dQ9btKveD^Lwa+c%+!fUaFyCd7`j4JK#<aP+KrdT,8v32DbpiYgFOK^o?{es-ydaasXIDG6s&T_JrBnlJIE@SDc51QH|_~=>HI(1H&+zhdt7-W3)Hh)0wpe@.U:YwJB-VctrQT)?bRe<LZk0LWNMg3==@aS*=Xpw];,3w^=%%[0Zlp"K/cV%Cf)mIrGL&}kNm6]}k(sBbL"<-MN8F)X@ERw~:$v{;0)K"
,fyL/{h:*smLHv07#n<Fx~#@-PUN+VW#34.rai]Ny
KG5PCtUul3T{7PbMX&
!uohQwL*a##>+6/=9Y3(x$bnQt|B2<ZtM/1,~2JCq0BfVn1]h-mVPJA(4vOh|egh"]=TrG*k40`*!#pIGtHf;IFSHqXrK6-<yo#%K';case"sk":return'#]^5)6OpM@W!(tc"YFo)Z&TP0v=Ca"(sB,1t`lN;Y9+PqH})[P^yw*AyGik<D/l8A4<#[>u4Tx^r|k_V5d*Gd=]:)AT,
uttUnHe.@o4Tv
q}:`R"xw<o*hd=Fm9dwGRhO0q3*?
[CU,ly,g,SGs,&L]Vj6G]I.
yLk-%Y
Nwp}_<7&Dx[nUI`y7BA")|UV!.,?+Hp~4_ny4MS:g^l;?>L919T+AUR[($c?@,b5w^&0Rkk8Gw6~1A7n2bLKxve]I@[
K=/L2,Ltaqh)_Dr2vyg$sL^p*;j[JaAkZ^X,:(5}/h?|FcV)w9WZoNpirJ2J!(h1!},Fn<(za3@3"y5mb;>Bj
>ZI2Z}hO4gAVCa1JwuyO%y9a]Z=QeYf7EXjx!cmFx)B|ZCflN%?Q]ClOg=r`Y-#/j$<*6x2_VQtJjdboS9`NK;[=WMWYci!$S4"PmSYw_dpl[.u1oJcE?
$ZJ.c4%r9NFuBpnx%!#Ulo0!!&/0"xu;XGJ
j75:!rUvaZp]7
=,g:x7+7M}Spke^9^*n5=X=cwz"1glg^U
VGFL+>SJ"EFd[%bz&6>f"SvRxEpn`7Fs-Y5$
:9/q$nIO[d?Sq4MwaH,*!,]`Lo1)4)Y4mc,*Evv^lfV7Gb;?X2dVrc}_`a;)Vd93F(lhy&WLv8n?`61Fzjquqy_7
NRutLuBT<B+HQ3gqmp]okxTq9(7TQkmdZ?M*2dBkTB_].gc?)XcM9{rX<e_5r`VDl(>K&4o6be.]>Q&wgAyS1=RO,v?`L5Lf)o4Q18)@F^HNeQT$f@1=2_%8a(S_Ftd"2pr-fm49()$W0hajeT>%=7m2&z/hkH%a!O,Q/<1sapNgv`oDk)fYK0[7!_o%PHs#A&J}U|yA8;RNY=s-He;cn0[)>RA<%1a|/mI,qLa!/6^28wo}V(C=YuYjba^~_3=wm60.jwrYeRCCu^oso8mqPP**o}3<`ll8D}otSl"rG.[pJV?:9SU2-U(pKWBU=.n}:OW0x|q!#p3=/*CY8DI-iS9AvT=Rw,%|d(h
H`wMuyaICfz(r2rsH*CjQ%UH_zBVWRhDSVIHF`)u&SpEpp2tx.nccdPN(5BF/&F7t/NcpnhB^2v"5kW,m<0<9Fbj%,Zu=XbFG+**X/0#73_~r
!JAJCBFwP!2*K4&T[2(g.)MIn
D!$$<*^a>(GyA5p
hwB)Z4oYW}#i?tAsO?es6lAJ<Zn!t;XX-Z!UIbS-UhIcY&oBO74qUT>),&w%CN9X%v&/+oE5l/IB61%AY1?qUm7|$RuWeM-6Y?PL^Fm4#diiCL.`:=^zn82ze9fiQ!^DTzoSb^dHek`C9]lV+[/:BlC+yn,cpEM;
)+2qP)<3]m?UmKCmR3u:H[%l40b*uW_2PA(Qq_=jmV1T|JiQ#Am]:w|;pq4q[R-RVuH%sGj!%1865q9KhZLG#1Y@riz[CY+%uCzN2@="+G$R;8&5~8*uTb+yFp1k/3QDux(WTOZ/+PIdD"~4fQ8wAcmS3Y!/_B;CwAzL?"/1CdGjBVfNfvXXH1r5@SJV
r-(OE<F!o>f/fCj?Y}FBY]f0.ATX^b@3I6,llMpcBO[+"443_8r>N_ITe"$ML|j{a@h@QKY1d*Gf)A.]gpkOJ^mh</BK+oYhj@`xp6oqUAL4:Z)W#&5Cx3WN;jt+5
;0="V(eCdtl{N=<8:LQ`AX>9jqGMt"4:5363M9<W.73aJpB#l`xssH.OYnJ
[y_io;
#k5SsCH5js(I!/lQ4Kc>nN$patO).S:(N7|e3r&>2o0LP@2avV*7WKG,829$DGwM#A-9{/C:cJ]"u2:;,ljLP[~UluMDf!F<?T|+9MW;zGDxWSwR8C{D~9R#HZ
U_%&U4W=@zx<?zBXa=nLD?ui@]vpDY0?mDU~2PBC-#CpYyq77G!%HCxGZ-]?p_.IaIlCOXZxtzfC$_ZTteF^S[Bo5^NHX)^<X;T
xB*P5nLR=]*I4j[=sz*+:{/7f.VNv`UsaVMy2~*,E4]BqM^UnS$A^YnOdu`7dCK^LG
i%`$)?RDiEEBzqxlhXPn]`:iS8;j+iuHvIJmrf_+:qNTre[,"n7)E^/9tZ?TA_-MVI{LtL)xepWYhm
YnY=s;!#J~lzY]nB"iP%/#8k7hR,pgk3Jbs4mI=w+.Kn
RIJ!)<?/M7fSv@dY.;DLC4X9)r/1I9i6U(q)6Wq=6PH97q)_[pB0ka
Pcp89Y$2:dwYC7vqj"E9eVnTyNwZhcd?6JV!e1[UG*R3kjJ+MY?TUj`{0sV[f9tuP2M,Zx@gJV(ub]NCiA9eDx@q^{<@A-VcBP(@$q
QZXk!4E)mv?c4HZ,)GwA>RJfIq{hTh)yXBno:T)l^o(V?Xlhl4Z]v&MDi>1U!^m#diH_N<1GuG9KT(mUUYhu4T?Ub,$:^2;eRZ]jjpkA#VO]FrAbtSVm
*Y0LiK3P>5APMrpc/45<YTwDn5LMcwtlN3OF9|`24bVe$//!6K
5p>6?/rg`^Z:/KCEsaBjfI3yZtELOx/E|KyHwfR6(BsmHB_r:-%c(tg
db@UR@u5-"#.p3TAIaTLdd(HV<,VjaYifM
HtphSru{g:kJTiPk:^EFk]-&QLx&y?-xmFIHczilg,liGAPe6XQ7o}/gLgg;k|
YrxP=OPGmy[fNi1Y&fh([tO_a$pr$ahK;.<)pHh)f*DsJ*>HurU&h@*J:4$NFu{?I*,vC4`<c/6)QZxMv*&=j52"Apc9rg#[q7epH09X;+xE5-VUpec1EChn@]6)q@bkZ,@-%1]LLLHkqnGT>CsDlTp@[-vZ[sIJ>KR"$^m;9CN>>BzPh)+#_Xj&t6<"du<K)D9LJSlf;mmR.&/lS=|KaV9q)Rmo!&89qnS-k=4nJ")CtM+diyw-4vx?v"q[,cTyx(Lc9yY_XQbH&r.nd';case"sl":return'$]^09g~Z+:"5$t^4SOIwagI
ngY9qCy^lm$^j[%N!$n#U%nOOwC*#-NI"n+x[cf8{)mkk:N"@HChGB?ymR_0ktU>QBmgjG?PLcRny]0rrt4!
D8LUg|@o6YKeu6wlY^pfn?4$],J`]hh&a
kNT:OgdHw3z("Qt#)VlAn*=&YXsfc"Btvp90eX6IO?jZFXp:,<VXn8]0DvT}RXtw"P2E`HAzX+%n@@@d-93|(7@6s[pd,I=PY]=+xJ8ZTi;<BYszGsy5:[NkX332I/0
5gCS]dL3by)53(EWCqQ-k:r+eW=+4a!zA2[n12*8,$p|XB34*Y<<MnmQFsaY`3w2wVS?u_1@b-OX[$4l]2KY/b`_O`a,
wuBJc`6ZGly^.N21I1m$t${D-4[f}kqGSKL8?cPfmH0ZE32cXu{f@G]6cYv
!a?5g-p2E]#OyJ;X,6X[=gs7`l8VILxlIMUi}s[o)_WE8A#FfhXeoQi_%
(7Hs}z(aF_]/%XaJ,b_P,w
QU&+aaj!pN/0-B[UPg9#6brS:DmRhf<X6=s]hv_uoL3QP+h27gYMgnX.8[2`^K+W9Wnw#W+?c&N#59Hb-2MWPK`?M#;k2n)pHAYG,X,L_^
E=7[PkPD.RbB*@B64"n8jB(E%s5
k7!m1Dcpfs`=EuZ@S@*`RJ7HG_
VG$"2v?rG"@f`VYFLqfjpL.HK6^12e]JWVTvfO3MQaa6,;6@wl+^ecLAi{GBdH3JeXF!K-o:GF*4Qjt2_Cu=%m%Vd[+*OFf03+s#YF@VUhcm5Fy|W9EjT!RtJ%xTs1[?"
-D$?k$;wXD]50,
N*kb(^/TAtqqLq~_V7.iW&nm??jRuN2mOs*P9PojCtv<,m<TF)p3u,@k>b/hAEZF7pO>y7:v,v|#Ie`+aeR7o6|F7`]^Jl[[c.Vt>K:w;t/y?+Zs6y^u*$;h>%_ofd).ieK&fp/^>[r"5Qr"[5MFt6U5"XUMRIgui&OI1Kq#0fyigi!^:P)_4=X=h8@Fw+
t9Z2-DY&MgLyL7OCPJN88`itn}tabjQQcH+V#V(5S!,Up@8.lssj]M`O?[:=PwfO,zdF#<A
2j9n^}:%aB8d3e&S(6SX5;ncT_$K9HQ2j>KE6X!=j;.f%0fWN/C(/:3Xq;&"OMb",.u[y%17oQySAanX(heBIWB&kNBmSI^eM]IK8w#Y/nu$NiDR@OyWp9I*%(.WVv:gY."{!$1;VY6,E|k3Gk=Jl%Ck,![0gIEzt?ZwjrGDjq0+7fOtW>O]nD^@n~L.F*N[;md|f>M(4_Szq4AlT>W.RDWEQ$w^9MX08!U@^_CF@ryv6;y6$p!h*4.?.o(ILQv&:B3}nL8%Nq:Sc-(ytrKt+YYy(F(/XsqYm4Ylo[KZp}p/oIg#V}XoF_n.&4@Ulx=uL4Yi<?As_O[nD.x"8Jl3/8E_E!XN[(HM=c%!elV_J]Ta"?suA}!)iISVN&PXfQ`n11q-E/-@?+h81vS/t~;v(]oV.hAD#Q90^"/j;xge*[-=rM6$)aR5X:msul(lohPDttir*RcDXDCaeGJVp)fla-I"Z&`G*Fi#pc&qSYruCWH7u4!5%m
!V}S).8d[=0f|352cq*N:do;s27P=!2*OLx#a0#_yh+uTy,_+IeD`ImT?i!hTc9rFlL04[jEJB]v#W-WK>?rv<i^xVsrP3XF$M6/wv4AL*)mf(N%Q4?i{o[Yu9MRir6"@[zFqd*mZ&K#`?>P@B{l2#.YDN,H&dgN7M,y;/p!=3lAedY?Gf!hY,KY$[fD+>nYKV~RB2J.L-UG"&q:kMjUsY_<kYmEk%tphtUD~p[+
"=[Th,`j(HK8&>U8/:H{WEpR=e5!xd16Z|q2&1^ro$Apaw<ZcCqHA<usgsnEE$?x1CcTT!nB_Pst6y$E@b+JgC[p7
=}il?<tT3)vz1KSXjyq
SEkZHa2St.h#eMkd[%/g&B`Q`$E`%CxmAUs68}2wFyitCIF_n4L}aH[2dSWAZku{i`,5.;7F!f+3n4l33%90[:O{gi`_sLs)kM(ais2R-Bor]4<VY.cPk@%x=[uk*HI4@e${`tdRiI**a=s}BvaL!>4j"<5hD;kzQqd*r%&Q67-$>zo>9`=3f;RjUbL{G6t^1cHj1P;e`:l>2u4d$3><;f`fk#.,h++sd=h&myy=qTsgJm#l@Q<FmLo*>Ru`0g>n2i?9aR#c/D#<(&srFx`^KQ<m0Z<;#:Wm9H5yaC3P5+_M$<oAI?
qD`PZBP&J=fqZ4tYD(OezE.A/CAuix2pf99WO/y(;C6*_(A*&sCssMuDcql1PcC1myWHO(3
(T9k{L}:FjmF-)*5^kQY]6<xJkdA,m[PC1,ee;T6UtVd=+_<0y%V|Iw/}!JxW?ru:@|xo?}VjnPtYSfy50dRmpC1^ApJy^`G
uH7=5<A-Q2a9QT`(-F
v-;%hybCB+CV~3xX
XRr9H9(]>JW*H)$pAa+ObD;-?<&^m:(2)*q5h,UFEq"15G?R<nn9meF4Ox5KMU;~>&e_(lghSfl?qxN*aY*_WA#+SsjMH~glPV]D`zl*pUPjmXA301gBwYA;cvcmu~Q1;(,#ffQX2iw&,@o{)hS
-9
"O!f,=VZP7>lF=$L+E&ul)Fy&De=>4m`45&qgUu5at<Yyr!3zYpSYI+GeyUh_^*gm
;I#K
N%7/anxca;';case"fi":return'+]^;{bOWB!LAt[yz(B1C,p`
2#F4vqG/2<`)"Y*r&bbF!J#No"]>5BJ$?P2L9o^-G[U#AY0G2Cp.6wOC^EOi<!](lMY!mWr;r9[/WJQ0}Mep:l3JqM[d^H=BPu9WH1p<#Vn;qna=A,Z"QROs*y)WS2FO;7]J@lMSX>Bj;VfELLN`!.ua{yeU&1nGwuI)v+KXkL7R)XN5(MJm%XBK0*n7mKW9hTD$(Lz+4hhSZkv(3E{idoYTumv#YSd66ay3T"Xht"Q)HtIaLP_SfoB:7dWG?N3!tmiw+)hE7d?gTdI+^Erw928G:bZqa=$mB"hLdtQelCWR9`TW/k+z#5:-,imA/.JB]juj0T;Nz#Z)R
6f&lZVpOC!/KeXt,kXW^.Yn8X+|u
$x%QfNS,V.CxXdLFDbei3L-=[#.&51UUyoRC0uYJK-:/e<h;h~:LmZ8eGP85
sv#`!Vb7qr#8WA(!!bKsK$RsX!7pY7J1&7a.9"UunW}_/3PP6.BA5jU1R,hVMu>,)<XOqHd$QJTKz50/1JvlWG:TgSDL-<}lAY"95##8;D!32s*fVNk9=,@$tU|go21jS_Mo
Ykb[w!r*WN3?b"TWNJUMg6Vc2KBa$/mm(bVt2I*rv7J5jamR/5;O:r;RT}j+vlGB07I4PEq/73ZEGYf!wZAN6ju^uK//7t.RPu[5*kH<qW$E^5GS5W&
cg54d(A0=zYFT
biD&1
cJ$Cdg?x^Bb{N!a5,~o"8+Ey,
+"m8Y~?~NB>97F+,7=ZLyrN8)FnlHyLP=KumIDsGr~7.$E+8"nn85OVsiIW|0ZWJHUMQ/)t77HGvB}olX_P*%G?zg|
3g?!bl@?4c5SUc0u[G;2~XT5Er-UOMUUKMqB]&rx5sn,6:wW%q__pN^Op8>+@=#qS3uw{qX&n:x9VFo+o2!pV1wVR$@jbQ/elVMWe4I!8N|r]7acJ&S:!5af7s<_i!GW@v2tSRLD$R>%oA|&n+eHr#<2r@SRK2!@_c9!yjAy>>Hy>OKR5KpOu%/Q+8QNMsN<yAo$rBL3flh=S;j%}pLd^`Z!+?.UBD~Z.#v_"XS^uxS5wN&y{d;:a8oPhp;?
=T9(#ENz=`d1=u0|hAy."7<089*R4YOYD;ie0xfG^W1(1uC2Abf]m64hx}y`_|T(u1[i#!K|-98|?33l$~1>E4Gsc.1ABUR5NJt4<0fP5!Ya4r45M{=ek^I)5^,%xog1pDMLa8D<s)6BEG^<7E;`=3^3Nb!`?&Ujgzn>W$PR0_V_-YqO$9MUYM;g4a<MqY%Ig
BR9}wrMlT$FpVA3XCt#2]95!#,"F=/,Y,{NjNNe`lA8"y~<&7,$sxl>:AV)Asod4$)cM!`&LLh!R:y$0"Tk{bxO
&p$;X%fHx,S&=B<-g//{v/JvT%`DLMxUtXs1!1w(5Z5O#fm`-;8L.4,`fqYU5k`Ph*9zsu3+;,)R7G4Yh0O{P@%%[njj3w!+3P.RJQ=|g83uoDZe].(1ImX_hNw@^hOtTZeiv&,%X-%aZ5poW?p5_MIFw3A}<iR-QQUBFz5,Ja#3hH&l"//a_
5T@}HXcK>?gsa^]w/1,_DKh}knPJ8*PscWBTX?Q*$ZgXl^lo-hcAUl5Wh}Z25?@:ti`T)>?Gqm;g:s"}`*n[?]C:78nK9@J@d/c^dw[f7DEK`6i137.(vW;O.j@;S[bIy@-p[QcjQsvR
jX7TAE&E25}xcTJtI?qWp6w=yh]U@_LS:?DG"8-"TUA;X&o6F@"BAW(Ad40dNX@x8!U[_jaMCrFPvY^.VUoU*YrcH)+",r!bh%8]t%X/b;&qRN1ud=$QdWf!/DEkNotQMBA1J/M31iZ(-(0c>*tw_IH>/*.RvlFffc09kIUxQE="O3W_&G8*aKwFOf^m+eccYqV7U_K0a1v<h
[vnP,?D-ME0U^R6ESB.)MW[x]gs+if*@e#U=36|x9Qe%QpYA+r0K@0HU=k:&5ESL&"6Q{V8YmD
7Urvd%(4x@fD:EL!cVO0I1g-4y1_R5-]Kv2:w#ig1u[XNk)38-E@d;guT~)97J!0hRi%C-LX>[g[uF._pba>n>.^t3q<,bc7UoU7@XebL4?-M[w(KK5<]XYt*PdH7LC4EYe.IeR;U7ktRHHJO&2W,8`ttUD:w$e:U&1G96[dt:d>>G+?9[<2uhkFU$IG3-*E%jI]9[6F,rU@b19Krmbv4[Sq@S&g$ZU#+q^7+5KkY`4Vx#V8O4b-Hf%Vrz:tNp
;9dJOm:^CcbH7K&gy7,R"&cR3@DB|M%JwU97R6W?^uOD}HaUWikBUxJGPSTH:P%`o!J.uM7DE+.
5cR=uX^k=&.xe>9B)G.GznG-H$JZ*.Zj;>E!XAz0O4H`mI|;_[7^HBUYz):u]Po(:M
>800>C-WjqCpUX>p>6$M%s3]g~FwF%!y^DV%r4P^/"?@li36hHf`PY*%D|ckQq@!%-r^>)U*/Ou-j)>}WMv%"<@QYn:
0<:!.?uEqYbg:w[A[Y5^OnZA#@l(o/Zzt%Bv3utlfJc0:L8nX!>+BzVGpg;QjDQ3TOAR]08z2)u}[y*Y!NS>>7Sn"yHQ1ux#"(sa-<BSs;NTEWc]ZjN@mt8n1hxD+WLEI14{cY;H:ziB;RSJ_4-?Bdb}o$kaIs2+:G@"7tP"y(2R';case"sv":return'&Zu09bOZK$#!:wB)bg)*jg^d@6_(L=1HxRxteEE989MHI9Y8nNDf;BN%GOxY(BGR+^K@+E=4XfrlI1=i6rg;kcT,`sD?Ihjr(ZqSEIMo^7_VmPLJb"ZI~4[T7Vfm7?X
wx#]tdN^,*GnvxmQ$a:d4jOHlUS"JAK":&UdZ;yZ+-oit)ZFjP34`[CU
:TAhUb5uVk#^g_n7jfY/5c",mPXdHOG?GkoU*im>4wR_3}5f"{^?p/%eVyy9q~8^bf;#`U0juc;;C&j;Q!5
m;H.V;KhZ?Qrg/DLkFK[!:_~NzObs;.*y@raghWUG#6;^%YH[8SK^9CfY%q<J{<9L<qPN2.9FQ6:HqG,$kcHYdHIt=mn^]
yw4Nk,ropa73=4cD^p%L0y=9.<=9s@8HYZG1nWmfPQ7b-,bM&>jP_q`fM+0la,r@&V]%u^axrQ2%qtc3jVlbCvt*3d^Z.s9Jtog)Jp)
#$N75B2;@@NqFGHaLdYtGK$F<K]Yf^)+T-8*v!jGK$X^#t5Kw-R3d_TbU#re`n-ny;BqBt3.-%v@LJPF
qe)C[8tbA/h.=xxrr`jVbJo@];*D*U?["8so1Xb6&kX`-VSIQ]$<KsTQ-5[cYzL$AvKE9ZUV*$grB
wh(^E
^9w
7F705JYz8
:6o^<vyNs/CW]-bj[&aZqNP(K*hWv*TL3Jx?__/5Y](nAa
`EL<UNK>RBv%CyPZt61=/cn<WQyVxANqmn<dw][&_T4ddrZq5RdU90yFk#m`@h]]$DCQlR~TK?bI~HmKAm]6:h8l(GBiC%DUucW0cO6kfr#$.#<Oy
gJUEjuS(,(=qN`)N>^s27jXDiiX&yEWkVwz"&5c+nfm7VkeGe
n@;Lukh(faHgbMO,ARNq9<7L;5oMo`s(
Azg2K_Qwvn!H7-R2g%C3m(6i1jqR4!r2-rh*@5bEF"bc*H/e$T6LXI+.q0PvTA^0myizoQx]4zMLIx/G:)gXmX3(?,$odWvGB~i+tLrpO:U(Rvw9moi;szicR{KbWJFGZffO#t)33.+gmWR*9ps)5]CQ_B*eI|AY07iWSxYKw1L5.m3)8+]*^OpHr!YZgw&qUn5D
Lg=!2J"XJ$8;@`y>dx;={
yAq%slPiXjCRPR(LN>kt[rr6isQrF
F<W4"q1Q:TH(Ol3"K$61qfg8?QtiYeETS*zs,#*v.J[EpcMYjo?DRNHuVp(*=9>W0INJ!6CW4cUY;?Ub
@`@M?FdUp$S)&WvfaKeN$~;IB?
4:{=G23>W3,=Vo0W}prvAUqX,`},p0D`pwFPBwp=|"9*K(FT1r^$pkF6+=F$t"Be`;;`Ir8sAN1Zj:kmmM*%M&H3=7-
}S|z%h2ymnmT!l;Iv7#&Lo$^PqLXCyh(gqE=we?ETn@GXdOP|%.s~2Du5@p&WtwB|?`=kX
s%jwS:wDQ0#$U4D|OyScP9]o
E3Kda<j*k%X!}02]t;U?3[n?qcY8OPKHrb9sIJ_jnPzu/#nO#sDm]f$J8W>Ktg33y6!BT]_RvbP%Z)wDw%M6c!kULJ8r`NJ0|Z=QQE95:nog>;"dY##ZT."R.[g@n_.^o&l+0p7t9<F>mJRGg1nA:s8[t;9?><UXwUqLWM,FQOXaL12*=JzCoYRZrE
??ns@}oQS/2dC
4l&`q4lk4p"N;j:)/l8=fv];PPl"UJlG"-Zi!Cww0UN.]+-0O]457ZuOTwuJTvQc!W/5HgWmPDkFbtw~2d3J:_33"7bSG5LWgkdOI/d&<7_[/xAJ#18|4{CB$wbUT9*]IAchoJXB0.TZGi*(e-DSp]sI-OT{X
g<;t&{j/uNvxe
(b.4u/+GX+6ATvkdu$6k^WC=B-J1snV
rz:K2tYuoh#*=7MB*vq8;rv<T%:mJDyW&xpFC=8BLPPuk_OF6C!nlQ&y]ZqBV7GBZY=SoH6i0my6)p[/`+6Q`~XF9e21^LC$<R0h@V13N*"K@|`=ojN9S2w^fnN(H1sFLu>>PZfF67H<46>IvF;!D-8wYJ/59Uj]i|6>8lM#$*#Wdwp!^9n?XWwqkD3,jvM+Y!T*IWhRK(/UIraXl/M|vLDiycgr!3s&vkh%].2}pX#qX?
A0]9L[|RU"u;p=(6h5j#yR$apk1AS
O.x?h1uPNw*VL^{<@(Wqqbf=hImxH?eXM0R>Fg($J#br0!cyY+.$cOg0QeW_In!6t-ZSs00h,U_a?^yp]AWC<C/U,7CQ`B1"F_vpq9>xeyLPP/QRk)DL9b&laKxN|JI3hc7R7Wgw;kA,Z."na>X_oLrfHK;==Z|3U9Ic`4@gJrAoBMARk(2i!q/>1c?C>1MgbuA
5@(:@(Rp5##Py/ftED1D.x(=x.x.:$H#"$XI!Adba9[PH
.g9[~u1IR2;YN]=/rP;5cU)$/f,V-6En@s40k1>a=TnyeUrn<A?D?s4N&';case"vi":return'+]^0A]@_j&+nqv^tZ&nX[KnRxR3(FCf:#gnJ#JL?j:a^L34"e/m1/<U#O+f)#0[*Rey
?/.;95Pwdyp6[XW6EJUTUX%Y9wTrQqb_6XS&IAf>(2H$L9uff>U1E=19@LD$,6wPkj!ys^.C,ydmB7cb/f_6X4]v!bx*
+UaVi[Ie+en].<MKXg^*8f=b:,xr^}C%3t]>q|BjrL&x=HUNv*m7<]P+3tg
T_(|G4XJNYY^uRyG5psKXdvO31Dabc<j[Ja1DE_7kOi}KK1#q(P{+E^#4q+6=U)b]8x|7Rq{y~927(+zqHMuOHNqPwsG=4n?aZt+#JMH?=6_^GD2VpIG+]e@a/-)^7;Dxu`L.$l.8>)$xuEDGxBIx"-#E;<9q]YXS[HBW:cu8"A4U3<r!{p]m1>LdSV5g6mhv;5c#t3.hNE)Fm7UcS7`Oz8mF:x+CJiqVzJqmD<mlDBQLNCVeNw}fvx^R9.GKXjeB=QBjzaD(.9nR6Ar<;r!UIB9Uli
9"0IX2/dnLh-RGisvLo-,@]0opVCoqi1Ga:yRrCjfaMjXi8a;C#|^Xg=rpGaIoR`3l6["_RFx4;o!~CT9^u+@(DRWD[tSp4iEZQlLHK,cIKN;mihj
xk&@kY,dFI4_<{)<Wu.=FTX;9=pH3u&dM28RmeG+VNX`M2VKy//7eM.<B}NW_;tFroyQwmyXCHER@p"
0IrIU]xpGaSJ4FG$@;"isBLfOlswUN_gCh)@"m2S8hu7Z*!rVU`u)_8f)1&CKv)hg_Ejjqpt(G9tjUi]=QW(#$?KxpOoL{!oHtyf^oI{xdwm.u6
UcAP^ox3LkGQ@"hL=Cmq.>.wPprP,T@77Hj9u)^0">$hf]+=Z|-^<.%fC]e/.MvXk59oa@885)8&mnHc3;=bCxsnM<Lqd!B%6<p4%v4.eR"rJ>X1?Ne3Kjd5Fl[Zg(HdX`/:,Zp
91"$H?^K9%Lo+q%a090Dbu$2%AUwCnH5n16^KxRQKk&-B_,T^w+xOU`90]Q3,ncU!Qk2H+2eZ(PnwOmle]<z(o4w>f"SpsWOaTSS"fy&M44N,<Xuyb(wt<5MNOH{_muroln2/EcpOiC,J-)2dzGWmYS1a#/<"@(J`wT6=S=iVg0UE
T1.`K<jOjMICOQSRc%5bIL6kn5-q4F8Vetd^M=xY/1*vXARRg5Xfc3[78+O_Row]
]eI!YO?$jM)#,oLirEzV|B<wSc[q@W>/mXfM(R;9=Fh^K2%%<j1u`S"E}Q:=l.<[unRyx&g
_1v"hGI2d>4I)I%Eu*~Ai6yY
]MYHB_Twt
(,BfsFK=6htNSEbh3^r.lXN2%KpQmf&>&@LmW:Z>`7_+vj:ha{e?o|$D$QQ5Tz;}]mT/&`#v.F0^t5g3blOY[$?Jr[r<a|2ZhekaKW71C9-/]=Nx9cN>n+$oCE>O.~2y/AUwmf
j__aO0oM2ce_82.bY0y&<)y^=9Ai9,0D"]wZJJ*K3fYGaoqBNqZsF
yC^FzAY4cU3!05LtJHc9r_#G>(sq`q=WRh;jUcS2?4S`C-{B^Hb?Dg,*uMb;PQiPm&et
s:tC3@Y=j3kE5p0XYW
(5|Lxs&1;(Lw4C^G?:PhN^B31A1E.:nMdjT;MgwG36~F:-ki8&*xso
j?:.WsSw#4J+cf5{1BQa!Lt%*PEzVUj]bA6#U8&jj
vbW[;-b?&k/A,zM7Qa8xj9;D./QS7yuB?0.]_%a..cwgKx,=&S,^UL.TQuYFJVma=hQROr4yP^e0e_9Bu;A5o&/:e!b!52(r`pZm2rtH8Tw2t:B5sAd)t<S`(d.v&I48[=(0*uX_6NR3]H
LEyoyjmf}?!N4Va&P,M!<Qbt
q{*|diN7*{G(?a-!%;.d0;h{Aj,9Lru$ud5]pM3kAX4Dqr%z-2kk
0b/[X!~%b,Jq~La"6Dx8N4N5iQ(VAO3b
S")Z
}pW_H<D0/SF@Gj>Ng5CZ:0&r
GbYjGzaRbxjl!2_kK1l)lK:5$pVck2vC@*18H9=f1PM63Pmbg{.l7:H5o+yl@%6>VEhYd^ncd(m}H7Dev1)hS#pVQKfew$??%*YZT,4E)1)dZwa}!psR`j;=;x78
,58%C;}y.FcTX-QYYlyeOg6Oi?Ll&8YYoJ=7Pog+mI"F6VT;^[#1+KPlZd&J..YQi8$GeT7BC%yB>4+W=qGWV;adFlJ<rd|(RvEN=03wUxwG"81r0m-@ooU&:T"p
=pZNC<];k=kYC@95ZQbR/EW1
lTS?0fE$A!;8Eg;"
d`%aYJd@YAK3hU_BsDV$*>7;N
qZm.YQMT_kNB)0-Ok@wFZ|[cFpNxVC$)9#5jVq^U]m[)f/pbbM>>nG>)C*ixZ0[dQ5OLD"YA3vWxZ"Pc!=%Rb&]v%Q%
P;DlfDGN_A!&:B6AQaGKiAh.jfX&&)%h
@a3({3JWJKYlz@&+
Rk_!Xj[/e]q$H6p2<%)Yxi_jMJVYsW`P5NwtW#KrjfWN/O6eA-
<N8%bO,xi3[G)Lzdh??^wAKQZ/Si]n*P,ai(+F4&;Jv1*I:_0T.w9Jj<Iuni>FJr@NA_vg^Ue&H"?=nO2nP-Y[)VX-$[3N#r}MiBGv1<5
=/LBYM;<*=!<Ave3ALMHr!*lVM?^M60UD*ld$nts;A?&0[dlNu&qSIitYbILtO_h%$#*3D=X5gQ$9B{g^w+p5eiWmp#<eB}!ZE.x;0jcZsPTJ25$?G0AVH>6Lo{
8Z2B1&:<eoEu}[
OBg:=Q9+=&:d;Z`XV!.i`6A4Ip_-<FQYyZc(8AKgZVz(t2gF,.<bqCd%UEwm0"`vq(#RMdHT';case"tr":return'#]^@ibPDI?T!(k)@UDHdh!cGCJzPOO2:0#W#8"{kND^ZMID)XPNBJ,~&,W,GefKG+fko(0(xUE)m6<0VB^[`eCl]rJ}n4?Rw?mkDi^X:^8xPF]Ns#!sSW@)6JK7x[$Cn`,>t=s#2K]1+>wwv/8(2.JUl,
rF,#I,faTu6sML;
mUI]R3VKM06oftkcqkRNc3!F9H-a(RSdRfrxOfW$8^C%b;y3mU*sTD@B8pM]_5cIBW=#GhF`|YTUbn3!?yY;>i&OTQ<B>I0]L!K:+.kXxN{*eU!,zE6$0JW%l)1moyrW-pRcrTaeo]s2H6^&Kh?5tU~PXy:EOr}sdIM/%)ir3y*$:5Xbfrp+gM92gM@k)UG^q*rs~d$St>iF@%XtHKk;~x[^a&y)Jr&WFO,2SEnGBgYSQOCp^>`<*.E?Ul0")-d@spI7uUeF]D6NxGv.`>]*AA$JE0_koB#r.3]X0P6c#Vf@?!cmc3SbWGOWXVKW4^1/L^Z@R%#VD0e4-%TgRi?KN):d@2[?ITHmN-a-"9[[+m(*ItW^OO#taL"kji85ZGMxrL%8BbBM6w1G`)DPTHY2l!.pBR^CsF,%"8wt:^|MU2E_M-=OkJ41c+"h!Qc*(%U?PR*+W>lrxBO+K;6@TPs!kL])n"0:i1WFg&
8?c@P0(.B)t=93u
-]QfX9FXo,6H(+Kw:7vOK;lFPO+>aQC&56<7em*38kTMbB%p;}si]8PO5,FRiQrL<b!ME"B=[
Txs)^GO-8&B]G
`V>on-/*"2Otk%GY1^JV^E6fiU"Mwpnwq+IdZ8_is37~F6tO*uPH^(0FG[!5_2/{4jG2%N&*-Y)83&wjD~yej9MmIfP^o:"eWNY%)2eX,*qlPYy/yBx$)gC:G7O.tT3zjWq~SH0D+2_<uSAz+{jvx?I/D#lB^jNwJB%M^!;U<hp^WGknmyu
iE3>wF<|YrIQo27r3UKMpi:u^dc;4}-cNs8;d0NX]-&.8*gkkek.qVhsJ5^O88mHCO,hxeY|gmYygV#fO>nC-3SG*zsBWm
Pv""py<y<,vvuIom16Y:Ne77T<p:.E}.kRx--QPHG`3[.qU/^(F,@Rw>38&oq_gEu7),d+r^JjEW/-I(O8s[Ta61fa=J,$3gsVV@JV9#,)":*2rQJ6{Oi<&5Sc+)pS?3@sF"f86`hH7`?@%)B6U=rfp"dIxT9?;%"eweOrqL<P,yi,A,A2w?(CSV>Zs32(2^#.:aQX_g"EjS9m"IhQ[G,)sA&9?D?TUI-j_wNGT,.P
YqQC^A,FHg"|()($;-:]2.uYyQ:6/-8VF#1&")AI3S*e]4LXJ?:9O<C8LVGcOvex@;G+X?HKLmiThZ&D$VsDJxO)6
iSUlM)QVjttM3(-nt]k[
s^"rY"(Rq)ylw_,IZ@l_inGitb>3:[LfmL7wb7hE+9g*|I|E[@xes=cVw,?@$]T
MZCG.#&#9EDq3-5p*nB>Q>FwY<tDX:Md}B%#SE?!UPm=d4
<IX2d<4<Le>@
Ra!!f"9i1fmOM;@WB2>!6l
_#s@Tzj)X
KznnH#K_,!92;M`jW^7gonMOcY5`9{g2lZWF<uNgg/kdM"bHl@#.E%(?_]dO4m]w<jay(}HDk)/uhDh-<4/8269ZW$=qY&:3Ws9]91ET/)ecjHtj(=boLxTC^)!j3UYh>>jWOrLH@Db"#z]6R6dU+1U9#`Rx709b_r^$adpf9Q:O;5vquA^*rkuJ7,Xv<|<i+VS1=dwWi;_-$MO0i-y@3
O_DPrO216^^@%>F,-Nr<"448DN
LC4nW1D"#^e4a@SZ.voJp,fs`jVPLpS3{u)7zlEPN5(23nQp`i!gwd,p"MTYv3Q5H-ZYjs~9_,$>NeXlAg|H*4`^~C5Sbyk-S.xIoH"Dw;>+hFYi{MI=1*"CX4"ZZrfhY_p0lT,S
MH+Mq-
|L@qK9t>d=Y1d^U(xgz4ILzc<>JT{7rP.-"a(;RE+AaS:MqWN.:A2_{,+[mu)!29@2IxbO;]`#~/VS66#;O?O]"(poD<{U
:FYtB2EY_#_y&=O|$y8xem6U
OmM2s0/(ARw!!I"J0bVNQBMZ>_kn6Nm#W-MgetJ4orF-D&bgwrC&o/;x$;.8FtdiQ^8Z!t4ksi!7S?;KkUZhI]62>W9<l)2J9Fi8]+kbdWM-tFu322z8k;lAZWwdBtCS^$g-b"yxy!fwKQD3tBTwx=*)>9AwPyEr1Z3%PatW(YhLi!+PnVeOx
T?E7W?G2+pdS#
:32`imG%Yo:T6@N!!;60/p}5)HXmMqk%Eeo?/Q#w&5$FNgqc
BIQx>P18_Od=IsVj&z6"+d$gZX2L4<s20r=hd;aL)FBr
>0V_4P-*U6+QfSJ)g&XLc5/Ac+
cD&?2}#r(@k2FCd|]GC.Z=Xrn[F,P!jj!B*.u.Nv?.=vg5Ca>3<*1p9Yta`MGi1r4dyiAqX002Rnc#xT-p,3lWg:/vh`!}8|LmXIK$S%cgQH,?Enm~?4A)YK"w
p1rG/59l%J%8%$o`j:uJrOnjm]89uh]`/:B9K3"sCZNT?+Y.8P*]5W6qcG_u-eZv0</,6RTYF7B]i3Mh<e{wh1Ay#IUjO"Bd|uC3{C|SGH]*R&vLLHTDU.uQ?0g^;/kKB(:2Ru0O~f^lq[Bd$2;VBkED7$2u59txSGQ6uQ|Z9@^l
8"Dfw60
^:jHROsgE/"<r;?d/LjeV*&a.xsPRF,oSr)!A,40)cBGA-.yWBINtX';case"bg":return'(ev;;:wWR/$*#rXG54y<)N1%>)f3;u[0T;Wg?vVGj-
kQ4
`W7!M0
YF`dRy
i]s}wled8$#iAI5^bg43]38oqVh$WV7X6Kjxr==18Kc<K%mBk:h;M_0GaxuWt<o$qBw&rk:Ag?^9:FAIl6C$pCNn0e=~w3cB</.?.;LOx),9[^snBv_`2NU-iU7snU5`)^@v@5Hnk+7}86S#FYwjnml/v
0r!1
BGgl-d91wOKDUv+3ZDqGz.
Oth-[_,rcdIyy~]$<Oley&eX=^CSJ4IQWFj`c
0ZxuKEx(d*Y7?p,-JGpuF:u?x75)<GRc*""w?ONl9}@py:K(L%#|0IQ)phZoUJv;2^JxLr"?+b7v.z<tP3:J^3s]bYt_+==-i$92k~@gMu[bGF0kSA
)bWFIv8wL(a,pN.1f2Y,goO4~Wa`Z?o!0Ej.j.8ljQy=V(#F3ZQiwiPnOSac_gRBS@CQ$mi2@`/Ygz$Z<(KIM+iF#Tz.YsJv:gxAnd}ZlI<dzc!6U?5w.-$X9*9sNQ-s.796VXBvr``S.@zR2loB8or"QnJ"xJMI_!o_mD9CmL10!>=)~kI
W=~.d7sj-QCtTJU/$j0+p$b&=4X5;n{KcM&,:ZA#?2}L&#=,!8]%,#=fMq%T1kGtc1m5~$K2pD;8fbju+-bi.VIp^x*[i0oRm9TtUN0J:5]J*<R:f-)megK(UnhN*G6?r-B&R_sS(8o)<!T%Gl#?=WegZ_<LT/Yn,X1]x$]M*#-e7*Ju{>.UvvC+MY1IJRQDkH`<I,=>r17[TT$x%s9Czk~-3Rt1>5?i(On<Oa|wYH~7vZ1g<<d<vri9=R%fd_sd+ZD3fVgb6]!X&08Q1erHkj"d]MmtcLYk%q@5vHrnJ3zHH;w8~F}.Re[?TKqT)AR<>6&AF
UQ/k|aq@uXOcCyQUDy5yRfGp42+#Limaif`>(LcZo6P)/wk3Z"SJw9@LzfNn8*[/xKb"3>Z
)Cj`4kN>Kt?Gj/R`1N2<-R?r;;w,hF2]?+"8+96gc(
A)v*&Xg|D&*Ug,FVh6w`%f6|kDlbVLq*[h66S-Ab,{Xr;$>S`g_0I=kR;A4eSMf^?@5J-GHT%Em!(Bcmw"tU?WG|v;8B]<6jgOo#BK:Hdx8$#[h}3CMm(o>sO:JP+)X>6;CkZHql(G-RfOkeu
wdTHQ}k|)CV#%5[yx5lfVf/p?[wwm2ra-MF{DZ]q(8b<
t]R&{q|!q>$uQmy>XSj*[%2@+_pyBeF/dT*deK.Y&:=%03=i[h`m9*`,*>xD0H|+RaB@QQ;U%<05E>wHEd8EhN
j"xnx
e7F"i*k@gwHam~#ot0oW!(Nba<%>!4L;>hERc7U/9N1*:i66&5:N^&"&&NNA:-?kmEy*w%b:FS;.4i=b^&+G#Ltw)T&I82nc8|xCrUIpHtD@.PnkTT-?/"k4w~2#Z^vFQ}ES2zG8n./
=t_zxd!v>|N*xqQ#c;dh-:<cINl64PZ81Vc-gaYSFH=O&v+O#_o<tXv|@](e8m-T`afs$F_6,Y>xJJ?RL?V|/7Rl;zgu-29@xjOZKCS6<o$cuX"V[y@J&UmN0fwkc=s>2x/4$4;(&%aQ%:kXkSA7;pC2J*&<Kul0QNrtf;>#uz^DWLSvR5lu%[fFAS`Kt%CYr1U/Ba7q5pw@^5uEm.fQ^BVFA*&#U+;A8XnM>g[e(r.x,D]:tVxdiq=}a`.)(Y@yp]<E;&V7*(S7F
9$U0kpuRb|POdUe_*ihKG*:]GjP+`-mCS==%^q#l;cQjGda$Zj4F@<T(lQKXep*j1DYRv0/GbCIHs|_f:e_f`sWT(g?o(#X)(T$Uf@)
2B6y%e=VxI
hEADd^PBGFNt*P|(&<h;0<[4)Qys5+3rU*fwt+}xX`U<$$&tnO>n
5Bst.#"@-zAb*7>=xTn9FkQ^MU3.*mv/(e_)yZye[Yi[F/[a:[^DOPBcVWY>fdb4v^ve#t00[]G~(ohV9
:b:c-9BcidrI@w!8BR2>6~/K1DrhNm/}KGp<`j+>?+o,2Fwxb}"da33OkB;6UI^,SHhG@X:/IPk7,}Q}=]p^:rSG1y@lkj=yOx20JErNle3UJ6Kq
2Un-l?n6d&5<7e0A7%^=TTFNg"Q<DndLq2Z?7<8N1/<`d593Tt6A9pG@&SmE%G83=BY@Q.r%tuTd-(=bASkRmY&a)0(FN
IqVX,S*[k4/=nqAnb3z3!>)WnJeIFw)lJgY(<$jXK[PPX?%yp?y-x8_+])Gg*wICTp=4SQsh:<e@}I6#MA,rTJn>JC^++bb-kJ|3K
}d|3#^m1_x>q>d|nXoFeG3|-D01o>kdSM>[1%x*=c[In5.R9l+B26IS"~_9suK%/LU&*_3_Jl.oJKdA!Cl&1,tyQcF:TJEhfl3Q
vM4i}#|2}SGb,8}3>
k;V.+>9-Nq)8p#lh~0Gw[<[k9<B5hRmeKpd1X?7b8p{.E&C4$?z:Cu}^sZ$A3I.&cTclYz(2uw>Z"!gr-Ug)zm]B52%f}TMse9]irFhmQ!DfCM*hb/}=M5sKm41Mptdaln%R/Fux~9=k#v@4K-V+:9}E|3f3y$E:)3yD<?%,pbZ?]E1_^Zv>eh+g?FEZKcmcW
r/nD,0)%-8Gk_n9$T`6*g
@5vnf8rYKi{KZOt,6RC!oPa=A#+Bu*98#7EPy6~*X.djXH!/>YL<+OnqR!Zm^BO/4ja
_lk=%87i0K3e.ZFymr{xKkgTi?$)R$(kL*@yGlE@y/ceF/phNsdJ)8oyf5pE49)$rTi:CjTtD4$>%f[JpA49`dq:nN]dS,IHuZJ:{-LCsRB-yQf^dI&uEO&ZzFDc
[6f2v9AQTq`1#rH}"j:Wg4i<psp]uIsz2=R|F`GTg6c0bJG^I-/3Nvj&n4*Q:el>s|/?"+b0y`1iwR:.D8lm/4Ee&yaL[M=)[]NTsaqJST7Dr(N?CE,20<!qLqrzQJO]YSI|XkTKbpP#J8CpTuS?^jE>@$QuhOLcp3hF5Rggxzt<:i/4ym4Ht/6`LDaEIsr5ZI_?tTQ,7bx&1$@/AYu^QHm!4Bj0n]Fa
BYZI*wNLnL6v/8*sZqEoT84=+,ivGS9KH1y_U*X8wNap^4T9*DhZ"#2nfUMrlLiphck%2K(T[P@x.cNis_D6D+rI+kY,sWR&y!KrPH&HBw_CAPNryT{:QrsoTy+U:k:U7@n*HtvO]3YW:
V*~>~J+7Q@HhJJj0_ROm:A}>)@U98Y$2:WujKu;tzicc`F+vSDS7hEQn.b3lU_^+5o&t=t:ED=,U|`-g`pjRra(7YdfA;TP3W@6tP.wA^i2d$w?&n';case"el":return'"h_;:aLs&-^!;x&;s"hu;`N%6dbKu0u%^u_xH#7Y2C-93N<<@s>Xe#h`E43(nPZqM>K;H@:xJjnCt,@+.Dcs4w6
]jg7wUV4qS.R0Vt(jDo2ksNY6[:eja)++m;C*Id:E0E_~c{hvDph?ip0I]L_QKWvtT{q9b}:@sXJfLkIXG8<&J(x7xVhS7g9|U^h~WEAx3sF/RohcPE]
V_!{W+Uv,vnGn)_Tq4_oR4LL<M3ai_*;wwu/%D5/(abs=va
72@,Or4:V.T]^dq1aSFD0g9.`{q}Mmo5#)mSqFfdV.e2E=Xf"9&$3["~S`^oe{_=f9YMRTDLxlz#)`N!D&7=UaIY2}^`0P;|#f6v&
/sS_j%F!PlcZ&B"[1<!r
IZ2%Rui$d;.r*j*q/YO.A+"VoX3t]Rax1NK01u[BKW5fe8w$^COm/@H*#fP95Hu_(<v%D)@E.*|lD!olz^)K=_I(Kgl2}nNN|91VIr8)iw!TQ&y&py83zA#u,ju2n({(N?1dB(LQ;Xoxyo_D8K+#(o1.Pk#YBV
pT9cth@mWoYKTxF{f3PV@nROX+4FchYEn6tzWbe@Cv3(KZjt=4=n$PC#6DCXgs
"<<aCa]Pv-bdmp;DGi7j<E$>(D
)D*4."vtNJ0AIj<[k//p&qAIsxu#]A?r5"^gNKQoW8A8VvVotU+z%A_;e8b~GO[,h7a#=w3BC1hgP|`wwgMUd=[`;dnMCR$[U)1`+Reblce(IUlq.*U8T>)23@x<C3&[qEVg*b_h*z#W-Y_^8q2O!Hh2Ase1#1/F+4pwfa(i&a-;<U#[,IHp1~2/7rZy)
wM:vP3M|.l0O8zFh)^?^WHt<1LIX%N@c"9+pA{JV2arSfd"p<D#6IUiCY8yN-S*pXq;?LjWS1Cv?4I*3w@S%P^XWW5LRE-;(C
]:VcJOuEr-=ieE]t2ZZT15`eQBt0W:/WM#$}<vuKu?e(_ZHJol"(<NNb4W!rr^uAxVXEi|<vf@;^Eoiuh;[cMGmn@D/W2Fg4mE:)Ac(0P.Jl(<8imz/jidSqy2Y/v?QQx8SI@`",Y7J>=i3C6zEvYgn5ynV1d@kv9E8c%-<@bLuBDgWzug4T)=DS?H>xA&=ocPYDg!*u7^l@sty@/;K&vsQGW~,v*o@-U{EQemAK)l:J9e;ntL6d*
qV3i>qC5Y9x}QWCQwda%L|4M,vI(r"b9@P8%@EKJ%$]{&D&$AQ<GR~4yL)J2/_$dZJVWA4^oFhf?9!02N~Qe^?2KGT%.IemG.*jxFQd%90ks)Ql&`KrGO8!p9ad*$;Y8mN3N3W$lC!$3R_K[A/Uz!+$|94p~<fu$BLkeA/
EC%33NQY-^F=&W&5WCZ%e0&cG7]pd3[Z;.fsq$T27oY_eb47&5#dt2RlRAGDGwxeN*Po%
{);cGFW=kq(_~@$aR81=a-(cWjcaGBU!{`OM_&/8zX>RUf<Kn&K"<DXXIo}l
TW(Qn"
T`|kHIY(gn:
%vhHe!9V;RH^wY^eWV]C8L"Y.,-=b:F^/`|q}@P.IaPUv8TPrxVU;S7JkcRBZj0
"R?91lLWA+5xsZZ3=<HX-&T*CIq2@,o=MbF@BV^PYu4wlHsZUf
/"I~p#be5P[a=
Y>+2&Oi;p4rnd1,Vgi=V(YL5KNO+X!X02#M8+JRAfHJ+XISiUL=m_R_
E$#fT|"p2teN@>vKMR!QM]YDZFg1*hcGP&,INn8o#nMY5BI%%UO
t%P1Z=TH,$`8;M2V$0])%OGAOgZ$`%uVlLZ_G6*Tp)&l
9ZK@*M(8u6#Q#Qv$`[T[O(tB=$:KSLRmb)#ifH`3}nm-g$GDKO<.
5AOK@)9)x-3)S//yEJ"9#Y]A]QNP$9&@Z,Dea.@H%{$uL^_E2s(7U@W"%Hx=VkR3<^Aq;(.}$m
RvS+^3%W!4}3xe^4"W9+/ZS/;^v.4d.h&Qu48#o16&:Kg9#ApG6boou"cN5n-i9GKM%F``uGQKBpl
yICt@*L01`~r:Nj"Cm<M~-(l/wB:[*8neCRuo3i+3X^=a4.TQYUIuO2F3GSsm$3A?Q}(Iy0kEq6R4ZJfBV"-M]j&Z_%NoKrgJ@x//huBloC7v#?X:7i+ZcKuZe[Gpt633(8y&Kx##I0+i
dpI4}2bq9=;EDN0G?s%p
yc%Q@~_Vp_8EAoo1VqU1(1P(T(a]M3+raGDMg)]LBh,seyptx{?zLIPmv<w!Fv!o
m]@KYx8)mbsre9e.C:P_O#[>@TXv_<hT
.03Sh?-y*btU$Ac:h|NhYh&8lCrcenj3aG:"fQ@I9z5X>}!Ua66oJ:`TcW*PY/Ul+7Ye
+?iZ?Qg/4=,LjjWp=va&1ax8)Cp.pctKjoH=|#JLG:}0^P?^965?>23nj
2Fccl?4$|&pVrZ,m.v{[,6nsv[-6`^?0^=lLPP@=+R+Dbn_DorY#m;-v(
`*QZ!$a$rMbbDucZ=Gg%xgLpaK;Kow
g]S*1an-##KqO{Ib#@qz,X9OXD;FA:G
wML!(RhLJ$`|M+O3>Nk<shf"Dsa4H5!h[cWdL5W3bNJU`GxdtI!Q]ZGIadH;BP21dIy!ZHurkm
Q`pPGNa!3:EWV.Ol(:8&~eX=!4GZ!`x]{o9sD@i$CVL,A"[A7xG;8ALXd>gISvvD{B04G??!v_L.S/~RNJ#fg?rB0tZ`Tq#Vds}eo]8,8hP?Ac6^Mf0*v+x3J3@*x*0Ft?waw`[z!w9J3X:F~.mg3HdD)g^52-m/gG.cR&2EG`]u1CpIwi
40/tf"tUBP::9[)pa5j43vb<Hwd$"
OCAQr8GIh%QeeFP
5JRd"c]eGdae%uu>3:8o"-Q>oqj3,
)5X?I^B`Q/ZeX?f</!HbN?@|h%F|2eq{L7N%-oI{4@(S=(:!31_n(sFTiPdz;K"Y>ND]l=0M(;p>99u+xY.8Q];gy58i98yBsxJ=<X"Am21tNBx[f5h$lxJq+kZ#d<[._;ko?*xp/eJ@@?PmLxgK%;9ba2#]/9C
QEy.H.fts=boGLN>MUtO>5K{A^SL7x3d9UfP)1Zje3=pk.mIw8H=`O&8>
>!7oy+QFC20_Y75Y^yOW3NIB-.&WFT==#/!kYq9e=(*]?1[,7.`mym4ZrXT,=
g:&htbeTI%;iG|U48^*HG(EIr-HKBIq6T%wOWl[PM*bvh7dPqSnPxmaz]*sIv{?K%#5Cx43ZgcKkUMcd*vI
L[BNy$cU+U5sz"wGyD9$-1(,g>9su~u/Bh%dn3KEIvjoM1>ff/+GlLC!>rTl%|I@fH?hN#;.sBafH)d-&bV
xA"Fr;ks3%i0)Y
kO`-8U8/;P^a)dT[tZU.r*wLeovr)hK*:k}M*yF,"`eU%Bslwfa<R
!*QItlhF)V
a8jn4V6Lpho<a]7Yr3mFae&!tQ*4FV2Oa38!*(y$")`V`BR_azU@@a_=j$D(6MZc/?w/*_QT[J^!P@fl?[mnBc/fZ{j=7RLFl:,cmSFl#P.bg,v:4zk|M_^.Df/B.4ycRMm}er/Py_Q|"w7O3Ucg_$Me.YCP$kD-/MZ&S1x`CML]+RBLEsBpofRrmr6}_@3R@}Or>gJB$TM/pnNVG2mnQ[aO/E7I4G*7O,NY4Q/W3?<zWY,,dt9Hm{+W_^w7Vax,`-Z@`!VbZ<s/J9=`^>P!tBqNi2n,W7+l9(@+kv7l"O%G5}IAZ;KyH`@z#jHsO8';case"ru":return'$ev@iaMG")Qw0[}c[RuOJKz%=&>9Yx
G`+]C+h,-Rqo3XQ;SsW#NJ)"%S={gZ=V"&r50Ei$K{N)qpGlwbBt:TX{@muDgt:v#_t/h*nu68[J4}rG?`vN.m4^sRCLuP@g3XIMJE,9%Fy4rtJfkg)*0s0SnIp5&-*9)];jkZ4
]e9-H2@l_C_lj
K%<}GOE)C0]8abGLVq]HBRa<Cro|eOE6h;m|D62H/,Yu^c]lbW;*f+U1dcUFyRhoJfb2EwD`G^8k-.I5*;w{GKxs<[b{1H5p</%z?;x/W_4OD}a2_}P)C$+4b9E8bbEq:Z./8JA#"x*%@RS0_[%|F%v6&t:LgDV$&(CQDmxie5%qA`I#^+;,]r]!o6h
[_(n;-E*Bkt8fb1HXh?O_I"~Kolt`PAIH|y-75o.f?0<c6WZCY5#8!*]6$J<Z+`t5|GiOU+ikg##,IJIcg[u1d1a8EZ_^=i[TIV#p#%u4[%k+q&+r3ZSIZ6ZEG!=[.lIOe>)5Vjyb3^K=Q(AC
2tGn)C;jQgqfSR]nJsk2Vx?u#oKI]ji+r+K&J#qirK

Y@U&IuXYUxbs3nPC4;&g+N^B_6M,a^u%7(E3]OdAP{!e/wTTijbF8w7#-b@_af^^0P#Aq@LO+FsBd8cx&-T+5E[P9$#Ub7%Po;jwTk&s5xn)fc(0NBOI`q"6g]X>2Y0QyQjaMZ6YWE$sOdA[dpx&5r/*#t1+DNdm[XRDf<(<uv4NY;:9o+bcaqU6PK>D^&X`J20+4)#!M9X^$2YyU5.]a7CqOf)b@osOwrh6Zr={gVZK`roxA)qx/#^%q&A1PV
Ca_fcQ_XyEA"QTP8Qa0%l<JEpS@er7~qFT)khji(a"orc]AD")1Lk(<<UyHH(;2<NYPFWe~i{x1cNT]IwsmNv6N^*
"4c:-5C0-B&<&v&2E]t:Q$^;GeVR(Ya(G@$5t7V;fdvg4[vig%e7wcFuhrPw7_een/;#`,b,qH.Ot
_%j5ou!VcLUM,;:aX0mE`5tYQQJ@g9@W:IJfOoFkNNw7}<@4CY+q~iB`?)O2=6/@k9vHY*(,G<Zt.eXB32]t6?ycL_9yJNIJbbe.v.T?n<dpsj,%HF6R51hqu-k`0].SL-K[DtLpXo_:ui<$J//-lk[qkI2-J^-m:Cf[UMU-3Z!Mvp/bCV0"oZuE:D[L1NZ=Y7VRX[y"fG&fJN7w"6~7g7yO$xmZsGtH^U2z#$f!p(?jT7p:kgH..m}iHQ!J1G6PneTU*Rs0""~.9KuVSt4e{wdXSc:G6f3,2(%gm"fa@j[<&3(JS>bE80wC"#$3(*D.G":XQR`Hd;^$L-{&&QY/`%3:u,3t>bvy8(w-,G6SRR~(ot|J+b-QV?#g7c>OmV$#!UNo^-skYAc!AY7:pe<CwA+;w)K@=
J+,yUD@V]n^uus,/L"UiOY=Ps
$w5bKuzaqq914H[m,XXEDNlqRa]Z9r,^l6ke5Y:M$eAG$MS+Q/x6XeqgtS3/]@QYF6~MTi|Z_B6u.U_5XThv*I>-o&L/j3
sx1}lHS/yAwSj7ykKw,
xfetW4L0h
iDP.y2mf3#JApPfTV}ON7M?#<TvI>$-~-gB"UtT0O.%1ka1jKqK}5^xJ1a1CM|Y->.XFluWqx$G]B0%%)i<%)z92lqO4*N?Ta&3/?Aka^c?W_hb9W5.b/@N_tK-9,`6yQYF1.0CIjScOAGg.MW5u*xJR`Sdg>KW
e!IKaN[#g|U4@s#fI%kE({U.Ms^=mt5J,+H>C<wK_[KwC&^/;nv$j@`wd><)0g^`O]KV-ho6%BnAc^-|!_a$Y8cI?.-{mIWPY9*75$owl^)p8vZ<B/Yz]DO-d9D."N&WbY%K"Mt8cUE&V7d#RsLN1EK11%re/Ttm/kfL5i/YAiKsP/3,m`#lL@%Kdf/:)>FJkG&pof!%$1e`AixV3#Zc.B06rk94eN`t+a2x`k<rHguj`
[xELc7*0W!Ash/g&yE0Ve]Vmtg3+7,;Z/6x+wKd>o$E>jh3ar[]T!A*2iQyg4?SdJa"KM-0"]~b;>F7]1
Zy;k4p3x9?km$Jg5#&G[`J%+W-+u8yFZ0D!6!H#7%bA>6P%d976Rm-.9eO#=O6>805R;9baoI}f>-.E-K>*^G4=V&7[+a36}qr[`#6V735>G2X/GxTE>r&E6p5Bto,D<ON&(
N%sf"?&NqP-^(0![<)&^?I$oMV%Gp,NZxEqxWe]0!WM!>WP:R)"jD:eJ(1C9IJW
htwvjJ~d~VZPW4h/76=<tnM`*!CC
n#gOrHcprW%QBpB6aIV#_<O5%6!PF9.#&d^/]xQ_`QIo19y:1VpPEcRt.]yv<:xuvR6)701XvXw#-ufTV%R.lkS?R_O$qj0>t:Ay4g5`6sAp`S9#G5?pD:)LPGVA<(+#T8i
l`p2K?^~aoOjx~38PRq$8(L2/R`kGM.qyOuA#=07D7`Bx0CMjy]2/f8zl9<wEV:CKJluqS4r8gyllw`rWF`ZF1VUt&fE!~9sfcvq#itEbmXxL-vA^#msLn45[I.~wys7Ibd~nO:8fS8_A;"z^)68rk8E1q2jFNlO
D,GnE<
<"P)5[!j2O@VUC[CC-opA8!ej,=_&y^h"5Ep?1P^yGC,Mf@#=O/@E*<eL5[+7&y(%tU%Mstc[3ZuXq4`+"9,1R%*9!(HWt!YS4f0(;gY%-N_ce=HIlB"^t(&#M;Au(YP*w&dsjR
"PS<>?T{cc#wOFsye;CBMl
^5h^j6uX.UxZCsq(0sc>$lLOU*rrkH&-L/"KgWau-%-anbk(`):y4BCOgXJD,Zr2"(}&7lj8L./amBO6b5&lO8jQ2NtKkOF3*2Q!
5O5hLz%k"@q09PRkeF%xTB@KFy>Yn]4LB"MH_"GVlnQH..hWS+ALn/Zdgl2PQHS^
<EJZe![p$I|2ll*a`vTEq/C0{E(mv#fjlj
YxA*/q-UJ2a]=Z<5!hUa5t2UH1lSni2c^a!]Jbf9W_q#+C1mL1TZbn5~[4>k1>ieKl8Yw@aj_|OA%yoMos4VlNH
*u`CX2QhH1ZwKF(ASc1px8L:,^Efj-4@dvUUeT7[b|HE3e5lDtt3[K:%fP-0X<R9+?8nZYxHw-M7w|@$lZSA[w]@Al:&[
Bgs>m5qfU7,VSj!kJ^#_x<5vEzuj-Q;G",Zvc}DCTrCY&|:?0`P{,^QnSMfedwsl+;d0rOt^h4#lC/b>ic7c`i/>!%KgmA6v750XqB;gUU0@Y.;,$
d+laGc]{J45_/S6;oPxk<2GHUlo.TkXUs[(c:N5#%B)}Qh8vyB#Fc*,,CSBD<vDdPj*RYT<n`F]@a;xoAfmvlSY32V-]OO._]s3TF,C7n*rCDFIRx0S^TMPH.}q7"*5[kpt%eas|O6e6C"4$NEfb?+VPhQT4V*i#FHVdBqdM_>i|wmARw:e,evGEFJsz[faxlQ2Utnecn@9.IrdTXk;3Hrg@4B)T.!PihMMe<PIlsE%R`$jL*bT=>zwgIePHo`pOX
T#1!:{`e`pWrNOMB!l5CnM!|n+L;f,DBxNxPiQ1}m6syA?DA+:E,*YF$qptF[(=+WR_F;Hg~g[Hl$^ZPX7VOkCVw7uD2Q&/$EP_po5cf*Yr
ay`0MyP6m~7`/6t
ij-&3vMmJCssp9:!AOF-X@UxR8H-T/Iq
z_HjA5
S.1R#gD)iFGLZ1)S-o
WN%V+jwyIw;R+yNE_s3pTwOJ9N%MXMbL~tT';case"sr":return'&c0<%bOZ+$e+p^ba?1a+}/kelr
HN"K/$QNbDo,)DaQ6Syrn*MP"hg52(*-:]cCH@IIr>q#[{8g!c3lJhyvP9]ns1kVh1]*E9q.v9Y2?{^7l]a-qDy%brF9R(o|;(Gj:FfD]n]^Kd$rFybXtlt0*Wg>AnMhu=>RBp?.)Bab6Cw)=?WjY2Iuqx>1G$PH:9jm/&Sl#:Km/
&S5}Oa6Wx`WL[O7]#Qy9qOLY`@<gJhpkVYZs)rW<=-WRV7?5;`0~RA7|O~CARM6PmAiCcqoEX]4bqwGR
ggL/=lVo=+]Vw!:;}1=@nf`n>WW:#>w:Y*.j(lmQ<pm7Vt$YSS")E>83QW9
gc>]#tTW)B0;cv.l`A0#PmX?YX{N9qz"%T0I%lr)i;R
yul85XH+&/X;app7W=iTJc?:CG0&F_"BQSMk_l
cb*uPnSd,AEMAw/aljH~5?uPJ~7~T_Q&^H67(t10M0%aZI
t
~S;
!8o_H,:)"rmZXKm5xaalEf6a5?*Z;YVfE>7DS_GX=rQ_oIW%[#ZW5
CJ~4n<2:3E}M76s>="?;l@70>0c9/vQ8;U/TJ%mXnP]s;BTJ~dFUR4]Z1xUIM,.Ljlp>>cYn$R$s.Ax"Ih7&v!iWX6[[l8pM=8bKrnh.5:OqWC{SaSai<Q.=p>@6kv[)?cQ;i1.-]fqUny0R)#/tJ]IIwF$XTM{1&3[$M
Uoa"tvb;*sP&-$L1p20/11C]^`J!4K<^V#D8
Gn-f(Y0K*;JsjXJYTB_@0^(ZKO2GmUad;bZ5W0)D&2>O$s0;Lx$~)9GWswxiDs,CG7N)5zq%irSjR6Ze:*j9E>:7!Yl5`,*RX{PQSW^+]V@EeIAuD]E,;bR4t4W/Fl)xUbPrvV:P,Myk"XA)0)/&`_82+t+[pXMjdU4o(D&=)v?oYQ!c1{d1CJKx3@wTqdZ>j3d2@l]gPZ5D_S+d"(xH,A@I/:2e<y2|0*^`N<5]0*8@SfB_f0,|jE0y(#^;,YVfwekV!kBa(=/8t<I+0J0k%k4Gwh=gbxFa>+2>A4@B`/x/c9QxCNfQnu!LQEsd4o7S[A2D)d6RorKY#F[<+B9mXRVWSuEf%d-w4uxh%]>Y,GJxjevS!p9Jfce_Z7P<;cRFSC8?gP/WdzG]LEN$8.QwGfHk9;(4gEP;@.[Fs}0W-C-FA=Y((#6_j$=BUN@_u*9NeEiAo]5#`=U&l{[9MGAyD%MvR|U_d#r9yj/1(~V1kc:-r)3C5zr6;I-aIAw.@g-VlBha-Ug-wS0Vj9xA[nX:-J)hmQ/KNs.1-QhMgfmf?`j-c%J4c.G~CaCNe![zMcUnu2#)$.xZh)eMT-)vH_kU7J6b.64QY5
"Sx&s6JR`Ndd=L:dV%2"Dr5Q9FT7t"^Ji,]A)Hg#FiYdqVYF`0,gBpl$<8t>K".^[1w:/E%kL=Y+qj.J@-aB6
zXg/#v#eWEe^5Fmq>k-+yjtSW$dZw#Z@P-FT(QviO?nr6%<Y_w~Pt:U&^?U-;N<`s
pwn./qsB&1D^B/`u@KD[C])qCm|"ROqv<v8<em"//8Ty/KAF=U7r*5?oymCi^ml(q0~(}1w4{@e_Ep5<KH>1er@eU2B`ljUJ>AVWpB7,8#:XUqb*KvMpWZ%Ofp"llSH#u
eV^RI1*nIY2A%nDKdRMI.J.TNx[[Kcg3F=kv12cW^R~]R/zD*b#F,+;ycffg)v][i`(Z[F8L#h?@zoyRz.,
eU~:?a*H*/oYXkNj[,s*WrB]PCw(#O[v1i~6|G[PC8uNy-JM
#_olc1iZq!o.efKy/qaz4lM9wlir&jb.FXZTA
d~5[)4t3vhrq!lIfgfj?^VDv@1cSSkU6y`B/_+al]::3F_!eFAkq9RZ_bio.2#I&:
GQ0&$xPOoJMPRCJkfDTn($[k<!ZK*qb75IaO&*KEW<j"VLsWwbvxt>]EX5y
Lc/{)9C`DWm=a@]z[&wDFKb|B9mp$U
273?zO]HL/">ev-(LS<QCIKnq&X;@B[oG"x!-REPM/>=sh/x.+WLJ
$X/@it76O`Te-1M;0?)M}Lb7bpU+1BsXj7U=H<=+{gGXk&.kgrN8%s`<`G>&i/z<:sn.j,^]/g1;+Cx9$vw7j0ixn*i8Y/5i41FI:k4A$4rN//9f8f]Vbs^D=uOYJo)DG.}R%)11GIX7C@#LR4E4mHeco,?e$]m9Wk7#,sY=pLKxat}dC*O7D.bCG_BlQel"0@yxPQIn]5xQ5lG4ka:>ClPGw9>2U:
&?,}?6
9Sp9"xi8hZx9a]gc&*8kRfbEHa;_NVa*Iosn`"ZWhvr)zCN=Z`)>$#`ST9v!duDK)?he}y4U]:#ZS:-S_WVD3WFc]tUE0nhkN0k_,w],`sAEm:`s68Dsaw}Om>(%(s]oaX,"#Ei71trxQ!sI&e4AB7^idnmBZ_WEgS._c4u*]].oxjFBU
-;xBCBp9zVmB69kxg9kpL6E%3=E>R%A%tX$kSRR>$_|0ywlk6MbixUE=CdI+2L#YrVXEou|H)[j3=JiX"_1?H;f6/s_qi1Vu,"CVbhzW|v@(L8"w_X:+w!5l;.FB4(Yy+`g
P"YRM[;HJ6pMSj1`k0
HeBO&DcXmP.^k^FIF]&<3[?wc`d80DsjQ?A,%nq/sRdlesQ|DBvGCGG}sqrO5-!L+&=HPPYKq9B|xC9+1h]jtdqUGL4}IBTA6geDl~=&GuuAkHZ?4^$Afc;`eYo6T^lqLQl%b)bMMlsMJ"s;1HH>0%&K?!V
5n;jU#s
Ub@O1$X~M8#1^7r1h4,z]^Mn#x9_!<aYi;^.!:umHd66SQ25or[o-Ksfh;$}]!<vE<EsC7?)C%UuKBf$5$>Wp>S:RuxalN-z4!gXFJ<eUeCUk&5T"#Pnw;Py/DlP:ckfq82[g>A%YQ/=TMfh0GloR&@Dlko%F0#6iT#EYC-$m}GiEqmoIi(SZ#x_;3:l@DGN%Zo=i#nveuHOjsp_A`t_q"#/^2Y:9pDB*`akf?a6Tojr;D``F90{W/ozvXw3&ifxybX5qEVNw?<sYZZEf-/g1(NYW&1+P}Rg7{E0`Q2lX?A??E*&7bcDD]:
gOq8/>v6`uj5fgEu]0.)J*[-]PmFdR5$PpMUm`xiq6$H/K?#_2g/<O6"O?SflM9(6-wrhNAge/AMh!<.3cNwMyfKfeQfdd$LXE2=.XNty:k@eT:Q7&n6(]<#tuN`mPC*YC$9*%8!v9j/YD!@[tg@D39TZSEq:9o=&5m[[Dm@Yd5(0Lw]xy&5oTT;G8pIqvN!&x]e%:y@XS=(mhl"@3Xu[j**';case"uk":return'#ev@)aPsF*U!(v,%%Te"ED8TXC=&qRka[*ZdXp82aC+SoSw<0-<dIj3nWp_:0@[G}<a;~;K+$yJs@yIA;qiSJl[TTkvCIH{K8[xI4=&vICX5!b4abIdt3?QW4#Oha
APW
]rmu2
Elp6%]0A{7}UZ2^0MKp3Qxy"
4gD.i,/L2E=8)$hMJ@MP5jE.)sf0UfLMQcKm__
QL5Wz$qC84Q*lkIpVr]uMKrE7hFTY9+,xAo=G_EAtxo]J?&y&)WGhD;i6-pr9PR2>s^S3,M
B!<64<s[(5f6<nz==@DP/c7>MyZ$;#we/5?jNh+6I0}Z#XxO,w)N^v{DMC?
[f;c_WKa
I;f=/=B{q:%D@a7~a"[71U,Y#ap),Tei>VwpuK![60Yd<G1}ZOh
Aw0k?V5]dP+.H9)>.[=D;$+W>k"j;rIwykeV_xJGmcH}/sY?;7M?43nh*HD1xOA3KAhp1?jMM]1o7DCO+v9mS[P%:qVfY9TE[vq2!~7dF`@f?BN[5nbFp[ai9$^ZCFmU[@]IAc+;L$AU[+yieE<}(D<[p;*+A~V>q>hpPI6y4OZtby8csq[U
#+Keb$_mm.n9{8/pr<xN(sm8(5iEZE&#VMi)*L&h]gmZ"ot;P^az"LR%NtU$}A;8RX3O5M.$N8YY9kUvp@W"|_]3jr$jd"QL^6LHQn7R!$]E4ZA4
=/)XU7JSRp:DJt30pnGtgb?2GWf,];k_7.k?sDjJ_Vv8LJ/99JwECr1
))6i)4KlDY,Hs48W$C)-t
%xt9o_r$##<VSL!daXsvQY6ro
O_3w3r[.eU;$iruw$`s&R$_k0mkLHu^IL7J,-:Hk%wxiFa_)QLl&n]BZv:+B2`/r%Fg"xqj)o4NOoX1F#SIbK|%$"pXqn+"z1OyUvhj*s]bJEv#Wx?UKnN=VbR)Ho`[}Pseb^%D7,YG>F6?TgpI<-]PVneG!Q|<v6e-%_MAQ7g2~&(.I%r$<D.c?`J/?-IWjx_#bEKj+1MKuTxko.z>5hE(*3On/x_q-Qn.-+08FVv;NctD$LOCYqz*_C>>8RiE-D49Nj5D^)yr!:O0kX(!YP]S0gC_[6Ku6tx1)3EWvHhr5IZZZn!]8^N3uky(GwrP%kNQ)]yeFX9np,[J5v}/W<Mcb(9x*N7xcn6oo8R-q[;W6UnvD$1pnip=,A
&kf01|KN.
%{M,C;f
]R.Qn!Y)KKAb1r+oWn,^Hq:D[Sb1x^y|*T-]dlP=`6l1Ep;sijN4U7j~b^.<(FDI^Z,sNq#["n,$O@=808Qaq6#xk~.8H(WE8BKeJ6I|Y"a&+m/XQ*2Z<[=-.a:.gFDE;v#}t)L_gPVu]~t%o0wi?zAVz#Qlk7J{2(I&d!Bv=Wgsm:qS?076c8
gH_p`fs8bcJ;xGc,"IqNC/2bUh#[qZ~i{,@]#mu+0PITn
aLa%";7lBAGYB^eq!vL2]d"xhbDN@1c9qiK932ATk2<oy4:U>VAWE,9`[1,jGPc-q3Kg^4D2>7NZNq3.#"/9HQkO"IB@>NA^gL2)$a">N(A/2sB/R"V8=NFi@*t7j1f5OQk)_a-Tq#*_5Hu(mSkn07X&8;{[g,qPk1u7"`emc#GlCuCnhFA%w#rW*XD^XEDliTZ]wp9lv.10c%I;o6S*k%1s|Ps
tM=8,/{MvrT6(p
J3@+hNb@E!efR@0Z+4->Dj1[6.nPQvYj_H,*L9_ww}rf1]ox@aq!i($%OTAd2ugt+);G^qJiYA>;W=DUbB7-GeASf6Zz&E2{_`^-/{
YgjY2?S4&j{Y<si@<A*?%Cv;`$1y0&/PtIzjk@aqV,Lo<K_R:=VJ<(n!x-G)JiY!kc[U*4<ox`H,/94)V]w)[$ZW)"PBnG<PSPqiZf|[=Jf_+n%vRu+t`p3/I10Y0ikND9kS)#n#B9gLZ4b[$8>28C9E#/2/f:UaY*D*k*vmWI#lo"Pw2a<QeN>5taR;8G(o9fc?LF;%`MH%,RMO,Gt^#52pBWaEx1uu|`NB}D%o/+IFpIP
}LqbK#s#}(bv2(r)t,w::RfOp/~e$/*gH1(i{<H$/7t4UAv#)+

R[K#biC]7LSnot/b(kR*y9dBu73+iny=O1^B?Id]<DIS^Em?/^sCwcGW80M8R(cdX,_6NfjVWG=2$5J7w_cOB5QlYJHBvAT>y8+G,9h(2RGrh1@D`m:M`2:5d591DB%B`axFkDHW~:}0QV~,LZ&S-)d_WFIYXSx#c>BWHHS)tq#4)>W9dJFq~P%n3=3%4d[FJoqxJQhRme<nFfB^":WM(rIkV9sdHrMgj_6:BEC`RV;CF"p+,shZ;uX918Q#f=ot=5T)_N<GlNW-)%/aVg;yI3r!XLLX2x~wiHT?Ch>YM6BQL`4ACY}Kl(`m3D(fW`R.ftKZ*;6`[:%IP[c2sQ{Iwk[DhuK$y1mM^Y3ri>3(]c@p}FS?(&+
u;~02q)izDv;|KyAT5Aw+oz@tbnka[Sie<{JcY;JPU97Q=1,*`);oBR]E9p.p+vczFUc2`uWxPS&(B}0yANLjEbYIy)?p+<1riMb[&v^$?soM:s<5apTGTUc6P%KzJNvbb%70DcEW6!D!]W]nUexa$IZW,Z7OqlK]u@G)@nF^=fjaQT*yF83KZ9b/Qy-
KSp[["hw!3Ty1u8R<*+.tcXm*Hsz96QC=;gQ-#]J3K?<$>5I]*&"Jk5mwB-p3])XN2100QnWbpfvirMxVgR}X.IKw:/!_KnxZ;mX*Ll1*O#i_#p~MMGr@FBIw"aYfua}8Fn=J<`8#s6OH$[]lYu-JjMFU}f>7lU(vF:a3<qBs#tHlp
F;FQoYf;?vuT<Yrt>RHHX;!;V;:Gue3s869g{hVFEC&)q<f0@rp!~3o;%V0,5t.nE<ak]:ahrug.mrx@faHwp618kPWL3+8*=%T<.3!.`wtxVXxr*M(pPtcU!rMq8Bgc#on+dGpi[A@pGf|9%swn6yQwZ082tB|8eSEt_U2d$twEq[#3;&JKs%$.C.6
HlR5c;f$i5-x(n?)|8YAN[-,dFByJI4;SxY/l+&T40Ha^+M:Y)a5
*`G4x;jAry!_lfk"pPkxm>p{GfEVf$"6t%JE:|[Ay^+:uI/~bpG)%fH((r?-(oHP%Yo^h:TcjvnaDn+|ZOBi]OCis<2^`Cahwq$kl{s6SID/m+IX+9iUcSN]DmNlx5$T"*(^F9saob?yL,Ez
kRnX^USY[Uh7C!zC=3AAkA&Q9${3dDm%V/=Ml)VT{!oc#Rk?*if;DvPv#^TZ/SDp%$?oUp,!-xOgg-I)t50_9]Xq!/abK%vcrr:+Q%K?hBmE5C5e%
7L>+J46R?/"N`K"6&+Q+AM]#7M8VXUt:J0r;r<{J?G`/5jgY&vE9lxU?$[{S>f,`=G~Y90r>dJ5N[[9C7EB"60["`aDJWK)l}rJfq<SI5#Ut?R_e@)~RAT_g!rL3F2uy8=kM,$<+[MT,(;}#3Xi&$O<IItwAwDRJ
IsdC2b]t]h+Wg?Gn<<9bBqvw*#BW^&r!hXl$K@dOS;1UwS6GoI8xcDtCa4w@%FmjtR5"QnyOvI@9z(vwtWt=batf';case"he":return'-h_%9;zWB&iq40OENu"<]B9;7
iKYS$aOPh%+H|Q3V
;qP*(ijWt,&0+t/"Cl?W?~T(QMS=iTHr6=,@ChVI]V&Jx2(?*M_.3EOl?&K`D=$uh_DexIaX3Fnt0dB/%r;qU=F-Z~DWYt#atOSw-<7XL")h`baEB[)tF)i=AU,#,k+ZOGAlVE.5FmS^3=?*>]D)[_tNX=u=d`h/SkNgojJccz+A,
c!EjSl"0jn9BagDsD=lMytSQ%QrwhzSy`Nwe/6hTs{`,B,Jh/2:#0<kpO2W8OUj"rVrykOH0eq[H;P3jRGV:02n[pRD&I1iX@O0n(K6fuDv"aq#zF!j
pey%*)m<JmDGkp
{Y2uK<OT*_DEbmT><@opeea@5NL>wW`9EGaHq(TNe_k@<]`r^ULY%C2i}Z,xa,KDJRkk_LrMkT4Qwt690V>Y~Ps-F_3(n=1QuP5z!OL4Rgbtv2Ey{t(Xivh&khyQ,W$O?5HHg7TUMpeH}Hw){d?g=WBoblK-a@6L6<EBbNd0QP*P`,Ux8K+I;PeO=<08</RE9,{_-FrN#Xgs>,+<O^bq[e[0,Q<McYwZ<B^hQ^t9*P<0nLg`am%S~)naPT5Co=hk)A5tt$l8@HnDNQf!N)>9]lx.-3]-/N~TPv(b@h/#p$@Dr?5HC#6+xrZ]I+J0g3JxYR1Y$hq=xG0HsR@i{DR^{b77:>}%*O>j9N]<2S:Ef_mJ(MBcVEJy3JvHr7xti3EWn4b0+kk+UHUP`C[faX0={bi4j,WUQ$QRs1%[u2.8m^dZ~M}&.Ja&F<|xgd-/]8Fp4u&u;_k0nI!N~2*?JhmV"&%?h%{rB]?6i2^Wk(Z]^y=R+/,(PjtN@fa^Yj-S`vl<X&Uhy(w35$I.z<c:RqDcug5
rjO@`6C%G?sk1-p%U5K1)DZfS=L7,Q*aTG[UiGI`}95`wUyYzfdHF2L#@iHI<Sku3@rYgRHq:q,l<q|hN_W1UpHju=021s7J_y+oY5NM/n$;Y5S#gH8<TPyF;Xoaf>pE|1D9Sj6=,7HXJ3&c_5CMd2F/uF0/5B~F#I:vxUdGth?hJRBuW98@gS#Q``<dHtX`7hv_XMC^fA/2KU8v73qd.t(`uC}6JDrU28=#ZtEnJb@C{6fl"=bPb"<68yQ2;=HE7E1ULy!0-GXOmoPWv:!0.[2-@JPk=8wixWtuMA:
]#CYLLCk@
8r.xpY*?e6j^BRHA>XX2Ygn(V[zBmk>"0US;|Sz&+C%
)Z`l|0W[d$qu%`6v;GW-e#>oPZ8)TR^fITd%9`m9w;&NjP3;!9_Q-cu$D->iE?+[sN0r~g{BA76m+8#X%Z-Tf(%bIDc#HRI@,h19~76CNF<"M`CK.LQ/`yE@xm;U(VD0[PBq0##%mSzB8sr`Lr7UbhL>=KC]laB
cv_pyh1Vo98R7nakYYQ%}DkHcbue0mQtJ:77Ii)&l53(yA3
Jk=^1Jc-5b-+~4?;[([LD`NQG:.8`X]08$$HRF.L|cT=Eyi@=`VY*j{aP9Op"mM=DQc;k-J+2/Ys4P(Tt.2vUw39(g;fbv;>{IZZvW<qCZAgo%(eGwQaZsUp`7<;Z)X<]%-6gNV`(?*@iAfqsaM&`?QhIR~J7RGyycO
_<C4mCBdy,vYuH%@iR:0-cbuY*gRd.QP
h<xi/U6&W1ICH}(?T,"ZHLej+BnWS/jph4)Mv=qPgJ)5i(^M,aJ?*0:qX.q2!J9I.z7TbKg*uDk
+TT&FYFz^I;^=BJ-e!-n0Gsmw@S*.`TVrlZmMg_~Vb3#bXxD[-th3,B{l2C#eb<sNiuSYQ>sQ*L(lPa%VbF<aJFb_gr:&a.i^}W1^<&|3{h]cyir.|Xn3BO|xGg}REA[U.*Z7,/38>Puf)&j)0
]0b6]Ke&g&LR_rZQ
ph1m.eXPPk*3]jW,twsBqeBI5/G7XhsOOo-&Z+j1a+.W90]%*PpmV^3?jn@h(Ei}oyM7cLz%qg]n&/rs[@aO>
I
+W7iIJ=kXRZ=_tOjT>3|^=[y72W4u`c1hfL5)M55DQRc-k.Bt:x^K`Uk3|&o54Hk4p"]xW^(A;DrkF9nD4R[8C_|*nk|$iDOn_yE7H&kx^cKBJ$<=73#k5;z1i7K>@d3PL!u^J)yAQ=wH,(j>nK,W<<)+<%y.oAr=xh9oY3!J73/]lNfJ4%"P8sT5!XXq"KGUD:k_q?8Zd3h)BiD%w,NeGRJCk0ixOSJA|4Co0@vNJFnmDEps^lO[tGy6sr<nIv!^M5AqeKWuR+DMC8$';case"ar":return'%h_0AbOZ+:!2;f.-KiW=g6&3R(~GGKeTUIy/;v^(8
=&%/r9#O{9y0W&sUGWm^8GptO_`OO.#[Pex*(eqnmV8E!z(ve6Sg+lYbXe8H>*ubPk}ogpjG9[UMZo00
BEBEW<kqH=ARN[6}mVWS"8XO[cij
Dy[j],<%r&W/FlsRZs-79ZCEqVcZON7"CeFb]r&u$wC^#ulD19.P^hNn;=i!90}DdWJHJ,{B-L2:oC
!X
$)oVJGx3*ngXI:UUAGovj![u*LJ7=E:>os7YeEVKml
$mI>W1DG/z3zRdv]
Li-K*#,=M)qBg^NKAI7w.q@)wGG*U=?.1#54RO"2
,)+1KqnU>)VKh~K]Haq3g9^&Z9hg?QX#nO^4#hF,F%;.u<9Kq"%BY?WunM(u&1wAj2]qJ3$~8eBt_cm+ivP}[,TLndImp}aAOzu3qxDDgbnCb;AdW#xgXTU11s">s]viHuM`d}EWUylsY.Ie8%7m(#xjvFI.m^/Uu7ai[~M$N
66A9JY"I:5-k0htrE`yKiwr*h(Vo`fo18D]?51;SxNVvi].7))S?j5qK%ovl60!C[NbrO3!-Ch5Y,PrR^>n388S[?]!%/<n"t,dwKw=:J+P0c+>_m
M40%9!2AgIXivc)2=:W{P]%vRZX|sMCR$`p&xVecj2Np!a8ZxU<#
vN0UPe,kpn:fS$:DhwapXE?/c%D5#:}kw5#XlnJ*WuV,sw:utUW@!By7*1I)aVvE~jq@+oYC5K:ua:O1by~tma>MoJxLV0>p2h&E:u(dC_#Qj2B:
y#6T7hNKv#FRv9Vn[@Zn2)Uvuv=]JLqF*1lAcy%RE6c_vJ4;n>.m,T;,96[QdOx!m}GG8<rBF#F?[KB~u-begxfV+i1)H41x3v?!&(xQLO)7^(9vHoj_A/LLa,s*K@9c)9<_q]Py0U1f6;@}>u,;(k%^4sT?q4[32P&o1x)UacjC_`=#O_)NY^P4Lwht?b&BGw>CK(9rA#<;%8y4AmtsVxYpvQeX%$k274)!B(_C#l5
:)`.Fa3.$4f"BEH*Y4_S^&8z-?Xd44)lpU^jjCGZ]4Yf3r@/4STOrI>9H((,^*q&%8H)P1B4YiXoY95.!.C2M#cM`-U>a!2P_(v`l<^hEu[$bhm~Q2tCgBp;:?NcHbC=$]?E=sc<[a]|cGw10BqZFX!`/~d~MN4jn,(^ES3HDK&?<~6Tf6WhMY$QfOEW8cegb.CERGjsKP`pao:HPo:?nXElZ9tWU`6f`*HsyWedn$P@bK<11O?gJD%k[{>m7ID@^fp?Cey@(KY{a5C{`I`I[arTlN3}UG>c5LPZ?n#1?Td,.9O2+fEsK7=svd5q5<QJQpykcK@7^<FMrr#5+kW<b37]!SE,%44{[%/S<xhmv8j<6.F!w&Z0hYM).YP,w]jfNOIr3f,
TH_-y:E%=PuvxeZA)`AZ1|%Q(^Lfy0Xn2$0#4~5GrOC^b-i3W`*7]((=bnE"9|e+rxn7O3.U&Inss=B1MdRBb0L*<*7vdl4c":QCJ#oA^9]|]0c&R>VwY@g}
HCd<XuZ2v2qBxtF^I#)iWq^MJIB[.ldJtjgRowv
ZIc6O<_U6Uqjiv/R.O`_JTaS[cL>8;NwU7;;r1S[$<7c_@_0z8xx
gg-ra(bsw}P-QsopQ2]+%|>$Bz.*nctcZv`.lp?,JK2WaEO;i@["R$vX*kkT9;LND/hlu5EI
3d#!gW5:Xtyi?>3ZY_Mw4h<xm;OMXo-_DV@Xt
C7?593##o<?EwCYOoT;IZUAV}^S0(GM"8i)0Jw;2!Q|t5n
C_b3K~Y`&(&_b/Sxpm`5u85#181s"DVC.tp&t))n@73}W{lNQL4eq|aBmXr!TNt5yKf`?c>hJPegUu
u_H,U[Kb^oWnkw3-3U_-Xkgqg=+=7o>cUoBy*+V6X
PW38u(D@g=oe->=/sI_uXX}R"U)+2
W!wn0@.<i-Q+Re#Wd_P"b>52k[uT9xSyCNkhO.5GM3ca&@}Snro7e]DsBJ"4MgLpAu$)Uo<vH4/U%D>FCrPyv@P>-b<]-ymj=nqr]KEMUIyW
PSC<4D%A^$Fyf45lQNod@4D]imXV,,)a`ef]micp@g$PoRG2M]2tz&PkX((puoI*Hvd/YH&VOUu-"LV_Q<8]u^<&#6lfw%(pmr[&qi`JBY^Qw:qhPd';case"fa":return'#h_/Vh%Z+&HokQ1Il$zstQy;+imYX@:"]aH=W#-gS%,0&+R#8h6(Sf+t77S8dJXksb%X40[o=flctmXjal76yh&7hK8mPbXD#l:^NlvMPfXy@E>%^xTbQPhnrEiSJwXxWRcM=bE=|5zIXV$Bww^D3InLy>m]j""b?W1,eslEf4MMmVk8"jQ0kxVWt58j3s_yi_iLO
sRz,+;w.0xn[|Ga=2n0cjt2ss*y/}@ir~j[[*!0`
LgoP**52o_Xtf<)Uo4G&-pmVsbH>0a!H!0Z5x<dI$tB|]F`7HhH_v`_LYmJrK&tGNO)]nA5"e?iaJwXzG|Hw7wExk,TL`NbK>bH~BP33tpoJ/FX=K_D`MR4/A8P4_jpj
]HtqCe=8"("x}<%QZ1jh_tyK!f7g[ZVXr9fPo.dCM7!cDFsj6wbwa7zYM</@@$iG~BW2IYB93ZH3p04">4jpirR2pZIa?H%I,N0G1aGtjvOY7Xlkj7+=Je}:SeVDdWwu<OC/%
F"8aoHtGcO71qiblVXh=+w]1G@ia4:@S%&sw$cb/vK[_6d%Vq10NxUf`RTX6+(u:itejoMn_O"-!{w%,nC
i<$qR*4$&.a9cgD7#y<.Ck/-X3K[^IkA;72(Tss56^`5idL1yk4"neb@
$;yk#/nh9L"l7D@-2]!On7/d7*Rw8bZm?h7)275kPM"0>J<#ePe.gHrVkD!-TD;gI)K!l"H3H97a}2lX)8N/Ql2aLl
m:-w1@PP?Bs"qyo!+>6>BIfJmlhi1o"+B
]9oJ:KRK0
%o_d?Vr.,gk,a*#iYtYpCPNDDLT@E;izV;u*X*]24>,wqZiH/UMA$3NjI1:(-46*ZmNzCZ8tZ<xVVU-kQ8Q{=s>(88I]yjuk*,_EUhauG=kdlO.Cg(APLEE$6q,x4<j@K:P$.g?m=w=l]IO*FNLzP?w<=x?JgC"j9X:RmBgDOE9uC7oDL)0q)4;6-U+_N`T}(olHdJ--
?B%(R,--1.1+8R|3/3;y&f?={jW(s4bM^!tJ;[7v//2*^`=]sNhX*2mj#pOdn2vT@>R05m!HTLX9sg?2VU
M~v279aC!n$Jd=cMH
A!bc/n7QO|"xyy#,Ek&|%F?J;BcNSF5-_<%Y@t
_x^"+S=VG%FFOV2bLUm9r[fOZ<rLb^4x#e}I(;*XtW=.uK0f6Y*2`i(!
j3%]oA,e8mLJT$bGaz?NkPIJO8(:Q(j0UA>;kt;QlRmh[aUcX(]u$>0_7&XYA9EEa~ecuW1K9kR,.Y^`lLP~r:_`nNw{s;?C;@Y+mTY!SngGi7?j*`-X>s9w766k!#-1k&_;3QTs#g[rS;(ogNE<PUSnr9]U*+M6=}aJ)j94x5]zFw<gT#?I0i>>uRBFacV"
G1^(
[W@!#O_Y^E(ww5dS;%c*m.uLd
"yvlSel]ogN34LC;*^YV7IwB=CP
NKdIl+tOQk)j=?i2;XqA[:Khem&
nX>(-7Yvfs#r.wL#J/Qr$qr<0iB#[iy?dYU-P>MZ*
91Z-msZyvIt{U+R@6+*&WYc=.z5LJ=nwM}ZGT(C}+R<>&ke4we#RCZ%Ur;[T)bvo8JXrE!8(Oygj!#Pz#fMK->w~m;vg<2#[QqsXv5M!(+yaUEcq?"b|K3AO-8`ARz8v[ai)+].{f=fmG%_$buEUXGnKj*C&
y?0({;M#f;+]?8@.|cf2p>eQ;qK3``12T%L7!
y*1OVshxv)
@lirtzqu!6gGO9*3^Q+SE~?;3|6
Jj?%ee4<@TX1vA6-0bAcHYlj<p_%K.F17zB1hBQ7Gu@!+G%.6?
+nS%^,ST,=q1n`|#9^IO|(y>;!7J8]&hK<r:I)JAXV6?Y^k=IV6ag7?;V)+"i]rrueR+UBU+/?fg*B1>B16E2pH=&Xo_{-#
rRgcfG/l"At#p5<&|xh%>S;n;(ODzapGVdFUc&).oj1(,qy;%VJiZ?i0l2_"V/ZA4mDTWQ,$JS(/6M*(rMGEA[L+ytQ)nshT%5DAJuy?_v8^CmmYS=7^qlm_yhQFLC<lQh4PZit$]A:hhQ>GdL`eHn6Tpq%]llW$"7<.
I?h$8dIM_]7(yRYjQ:={T#c|?T77D6GIz"+P<2@MVX+19+5j:<v6,8o<OdY^sOsMQ:f;N
_SAcjc&].Ngymafm@t(qUiW!FYPB4j-_@ea`]x:{,uST+wC;aUVp@MbNj6P_W1s+0NRGKj?
$1_14OfP2T3pG&9!%:9s%7S3J&88fQaC%^GKJlaoGo63..Os6XVijw=AZ|<4t2FPix-/ag8ULr>0+M-woRUz:Jb6?m7q2iJ-V2hqSGB.TERu/}H5(IEpM$ch4uC-LFrxrgoJ$4[f-X6nJC(St`';case"hi":return'$c0<%bSWB$dc,=dLYgtYRimB^)TNcQ"R43~#"JqP(6/4cal9Up-"<h)N.@60SF=.BW}31YLqQtM.
#Vg-!-y%G=s#
T]L@$D3`Q3L0Ax9NU$>WPBd`fnej5jwv;rHqO34k:<2L*r77j?pFx4Mx-OWM8m@L>]qV;F8yDJnavL+vqAJb4(uhtp5"Cb8Qf
~UNy^r]udhgeL=hr^SrlXje==*7q>$4&h?Snv_=;&d".80f?O_Rd_WG/6+iAI6x]$*M&v/Ox,>8A
Nq+F*&sryj/Ws6?/%CcBvXkjrz<~ua$<>
pL)Jf<Kge*2/6hN=LX!L]d
QE"kW^)h_%TbgDQBMDNEQ[KnB*xoz0x,E<4[~pgYNYb0}$!-a3uo&;hsTj!lS4l/n8amGLh1^u<[7g3!iSnmANxw6On:_OiIb!_EtO+DHrz%uA@&=EAXORBjb7nx{CM^C,J(FacRSMl(|WI#Wf4r0D3wBFP9g^Qc9q-1[>odBHcCmaM4$F*72r2Lvz)(HN%#OHz!yuaC=6)["Gyfu@aI?BhgS&ZM&"q2Kf.^Of&CZB%?V5R%q0&-qA*(%ixgPk?=BpH]gcL-RQ6!EohF?:ODzm@C5n^Ikn*D

FS@L!cT+on$B,HA^HMfO-mG):N,6DG1
<TVELn]X!R]%oGIO;`Vb~qzl
7t4"p@2IhRsy0L:+kt&DD^,&mag1QF"d:727Vp-lH]W|kR3qCu1<L0ir)8w%PnGO-V`Iwm2QT<.KS![jF4JI-eEZf,HI"{t?HLe$0Agac6Do%mn?_,cGWP;%o:.,Ci.d1=MAwxQqy5:,&-;hpP$wQVuUw^SMa4VX"#<gS|V1sNXToC.N!cP)f%M#ED&(7>DOR_!ZVry}O0xtb;yOpYeL!3cBH<myhe!VWglG3E2Q`9>Cq7!Z6KCNh
u9QhYaRu
&lz>)cU@]Wz;8T&6m%vfpjsU,E8Ymdd7EbR>`L`CmdpN9Q</-al^1lbA-9>
nH&-8XhP{0r=J^;8Y2aCg8RU-x!$x*{@gN5a61dl??^p6]})]D7]QjA<[J)?No:J5pV6vtxC4J%/_]W)watg4U})C6EMzlG]V7Bg5R
xIG23LN6BJk8@VgIH)ZX$ooi1PNJ!0YFiwQRiw0w*$VWnU/D
.E<
lFJ_rg!;NSs/?g<lp#;dZca%y!T[cn_PZZZT<S5:>w>T!/qMd++T%-{lxNU5^313@QX@ZjeW!g&-*]uQRs=iwrW(7khQC6CB?q__K*Vk?J$#s,b;YH<xDLI3V,+`IC{-f*L5+YmSq/Y0y.b<H;#6iPP*QlSYJ/Z3O_[aA+tqCi1j^k
J2g*%nE$Nu*vUHem&FX[0r276qisrI_@3@MCr,hn[W"IPv+>DL_v?.ZlQ~,R@0)r<?TzBc,FXvw.X9dmS>q~fkGp^|+,uiZC,"MeQ"W?Shge;?bNm<l)w&OY7,t!5w!?:mBF4Ep[Eg^B6]3#ewYSCrUh^+Y}IkGd:;X#X7>zd.+QX;?wO=O!^Z[u(>?bYtX0KOY/piF|;Rm^+
3AI$t"=AIjCRA@iEA7YZ5P;z[v:
!wK/xJ;7T3)=jF2^
F=CXuFgsnD`lZhBkOl[hm_bj?3;;%l5$#6V6Cju^WQqtagACR&g%g*Z;eb<EBhN.eSr=:lH:W0DeMk8u&:_B:NF>qfpJUD1@.3=HuT-k:]#/R.)`,8{07(B@RQLC^Q@h]QoQZM&s8:r7I-L$(XLdz5nvlA0>Mu:^p5|;|RR[F#XQo?I25?6#FSLSag]@psa[4nLYn.4U6/T%^Do0EFBfJ>0OEHj$w:cH2XD.)eR0xm`A<D&0X&Evm#l!tP,NzM~c.r-y!)TK,IB.pMK<iCyKUe;e@938WP.dC)OmmZ$?QIX"k60o/N1i"I;VR6C5U.
V$LI*G"j*IkYm
)FR"1b<N/%okL.e<p&ueo+ZgmyYmk(Cl=`^nS$"-S2x4EruEc~hT20sFWLyzi0*Cm^opI^yJ+mkro66krWKCE$J;x*Z
W.?cDlQQs;)S"#tv(/@@S|`eRZOQgrR4Q{N`tdH8K:bkQWu_p0`1na*oO*,EHpS+M=$"q++f1@1><qG{.UH(Y~"G
/
}e0Apw0j"L_Gb^5:#%KdP#*bj_.[QTj1p3KJ<yTS#j3&1,`=b7|H{9Zuc]5AR]g?7apS~]_T@omBi"=khkmlX.qY:.x6Pi2g>6l.,+T6~A9%w0vlJaQ!RC]X[RmZ
<c&l$#q:0HJ<vHLVY6fLI.]cl&)Z[c!rb$#dKOU85_@Fv[iVQ/WHlY308jq>:$_|ga_&?jQSBSxZh|u4-|Bba=&j!LWAM]#doYw2=u[1vymO@_CnUyrIBiJ+bQDD<?VXxPM-Gb;d(y$Ke>8Q(%]i0AkU,A?0
?_2DW?}OPD2SAO-1`O9^1bls48Noj5,Q?Y<=OIR00d?r0n
K#t8y>l+@X,Zp=6NVk8Ru-^LS[O8dhFFQO/Q+ZJ,nW.X,k8Uy,+jiTP*K[vh=,oZxI4.<B1>8fjzF7t^by@[%sl]wS_msMSrT?IR#/IYExj~_a2.U>7Ei>kxGfvNx])}W%dhM{42c.#9a8X6tR9|fSR"G*2ZenYvv0qhihbY
93z8.9]m;i;iRrn6Tc]PCb@2V%grq$-_7@<qfb@l!HIEkZ
a<vlxsG6.iM%<lJp)$!^yMNiqm",>&:wPA-5I&(v.mj?Ny1jEe/vE.bd`Ty0jG0SZO2o[S6`W=c2GomLmsk[ZFL{o74ag>E/g[U$S{Jq:!F@1,@REoG%y0HUPl9y:mv1R`/$q?0:cktp0^>F3==-Rjsg:g/Kogewg0+G*EbNwrc/k:.vgLdV_j]WQon>ce(9e,l/vtbUiIbR;BmStPnmp3d?(+LJ/3nPNxPu5wus>RGoB!q.`X>Rj(
3ksvO$oMIw)TlwbGXM#v=o4US-iMop7bKNSns$OBbS"57K*1pRt-zH^_v<bUqs{g@FRc6qk1
q
?6D{n,6:D?*OUlsD!SA6p7_fj}`stOid.5eql5x*k~"zI*M^"uX]E3Jr0ncG-DHGQB@~`QX8g-t9$c9<La9t09N)Sf9ef5MIY3CQR,01B,`6P>T3yH<7nPn@E=s#YzE#R]7%5*WsRYXsj5R{Ttjhj[EV_w)PRmS3/c]`E=C"u:a
=@/,u279bO!k)RY<YFo)';case"bn":return'$]^@qbSWB#BBiY;aA1a!G+<-%v(cO3k(eO-*nP$F5[sk@?B/@HvVJ,WNW=8@6(}?g,poyU@v3oMUcYe,yy3l`ras$W5AhX.vtey_pX>coi>Vs7JY#m=aT4fV7cT`ja8a>!*[jp!k<63Wcf/]z!3t-wHtMrHnGW$7y,;hnGSf5mC0%_fLgq:upyb7zu`:H,O$YHBa9b!P!BOagS,n1wMV$kTUkv"t0W*p1mq=`v87DH+lfR>bxX_b2kT#sC&BCpN"esiZOaoIG>nyXV0bx<8Jw+^7[/Q@C7H!"Jxj<Sb[{IIVtd;Wwh59kM3.W:9&`dBb8NpiJ.r
1KWK_^)9%yQD&5)=Ab!.:jTrF&EZbN@0Xo6sJtg5"xtFfa/HjbgO_YrZY/yhOHc#Gla3]2mdjor+]G9$s3e#Zz%iaaaVk.&#Kr"a)nEnP;1o=P"Epp~]1>[ZOL+aNG?,rJsBmi^1`KzI;u?/m/^+#u
(_oojYdC6=_z-rY<)Cr#_B[wr%1e,J4nanInRHq-`W<y3tC&!9fObNBU.y800"o2GCB
"/LP`sdRV<3e#j1i+[;iQ}L,AYOSo,7tf-F|"ZejPyqi%U6R/E,#:JThq08IB:wepOk@svTU1SFXgso:DKsGP0XGnGZ{lX)bVMwOOY
k&>gA9_XWyYxbd6-|d-N[/1fsanS5IbFs76e2-+*Wk5>x"Wy@)%]^KZ8=p-M;=&/k>Kgug>b}^~]}9bMm9|M_H^rx-(sr"-
`pPTK1VO;`?cs.YTKe>xiq3!Eg>4AZ^Zg;L6K.Tn`31+ptR.Tg6QpD7bQDifi[M0QWk##GwJ:<;q0K41pvuS<Pq/4u"WoXbJpvtP1`"h_=XB*MPPL"5wJclZv=]W+*T(gwlQpfORm6Ao9bpD)[n-$bVn9bDCJYy?(Y:#!xpF&IPeSqANhZZ7ZpNFr*$ffn!;0Ro:wIk8Ke.dn_h;(5=)Lfua2j}fMi{,R*Be;"s7]4+!1OL=2"Fn1V8GTY0nEfVg!ebH6"}wt[LgdpNg:-,!kHY&{^L5-Aw4-af#Hw<vPu9O%g6W/Ht#[fWL}C<q+>wPE1b-{rjZ)uyCa!*wlD13jsFI2lKpY=$hf:Ghtpo<L?-<e^_t*T.XB?_w`Et$7`h
FF.u)a_C@RTG-))7y1IRGTaTUI?UWJaC3Se?4f#EWIZOYfb[4Rz7CTP9p7!^fUF/BW-5emtIpO
%DtXIN/Jj@J}#7So*-=Ljkk
%o$$>g8h>K0xNYfB_^O9e!nFPvPl8~CMl*!w<Xb1NymO;?8U`1hPuM(P4^Vi",Vch6W"TZQBPP?=]e+C4tEs0_WX$mtV+U+v-.(o(-q5#[-/)"jz9ScxLR%?={R1105.Iu46""Bt`v!n
:daxk!Zf$ZQBZ:,t`Di[mBSe~ksu}PIUZ#]+m+[D>^]+THo0rYCE
R6]%Y99s?LQY,N2>Kh]M1kq5E@+;J=wPO<nxw_MFkKPYPE>;;pYUG~uy_HH|SdO@Ey[^FFK%u`29WMLG,IJl`3%}.8Ai5{_RoQY&APPw&Wt!PYt9a,Z|WAFbtd13jBhuwi3Y>J*&Y$AUk?S>r}gCI_HB_I
H$4m]YMHW
vT72cl|,}g;
PyRi?<4[&XwdZExVq_]8Z$(25"k5"aO2YolmAD1[las(@aS&?)iZKOu_c:ls?qwM>!|rXXN(1L<HTsf"?mWR-NjWAR~4!9t*?vr:r^O-Ci|x4T8K%L)7E?{_nh$x|b!;y?9V6({#f6gUAKIdih72mm(DI
0K&
iLleq&#:|(v1ro0P]_1h>ipp=K)(g-D,X`w!~!:Tup}dmQ*=@&^2qQ"dl$L"VJrKja.Fy]-nh4pyhQQTK>->COqR}CxbOsu"U
NmUm6aGe!"o9cUnvD
Q+{IUf"VmOTK1NcS?rc<>HcB7ayg9/r"*1!ZV`qvn
2
)oym68hcXWJ8;>[)w5ky>cydVRm
D>xxsJC:E+0({D^OI9[`o[babn
@bD@0Dm+bO1!)E`!^FUD+naz_K0~PIM@TM<#Wopg82MvJ]4s:;<.sx1[qJGml{DQXoLs(>s>SM1`lLZ.<0K)=Xk@?lKj8:%;pFXVbkC~rm_JM&y.]5&i^~:<0N=z;t
9ORFjCHw<+e,;=/@XYhGCY)G_`N=O!)>Q]~,_/l7#[Khj=UZ@HD;:=EUX&(KG;/)1b>A8bERwlPZ63WvIv;rI?G<.:q&/%x*6!widP{OHD-B<wALL)4h,Z;YX(B.(E`tt_UMfh$&{:sdp9:2|P{Cmdlndq&^D,N_M3i=vC7=!QG?aTA*!;EyE#O("1?Z3iA(~m6)D)j@4+uBRL3%M#+-6$wFI.*4$j|htBRL",%!I4e[!XE^C*/6R]OJZHFaz>WK=RI6JbL&ir|](Xy#IT;3Jt>DKWGZ+)Md8sws*SqjSUQSn%b4sQ-@P1p"al}]1*d0K:SUj?d,FSGIk([2m/2FD"y9!YL(PLw%vP/s$XrOzJ/n*M>&Do@GD)#B[N7^3"^#=VH7FO{(
JahMOXt[97OeF&,mK7Z-mbi?5MnFTHrYV9iYt?USo)_NZ7Y8x(Gvb>@M,nQTlT,irVF%drp)O?=tiCv/mN[I*t0L])sx$8h5-e@[qq/6&-d8sEnxLkNSQsIhIuh~Pqg2Q{a)a)p@$Is_a%f!og9%g_kp
qQ.jGcN>x7oZ1g`<vbv;H>/PNiJ=/Aj({w;7h9`P(!pDyvd;/.c0FVi]wJ5<&J+Av-]aUpHw@5i2tPI01*J1n,eKG(-
*C$O#Q68km3@8Z;?PA%vI@/wgpQUxBP>$%lir36,JYcJJq(QgqZ0}sK`TY
Gj[?Qa&Oemf>$r?}H5PJn3DBc(eH:0`_m@)e66@aYG9=2#L@h%APucp2q<WNdiw:NH
a6hH&m~G|9I[!%IQ=d_lO0*
meAd8CK/$d`3xh,cD0PB(hcm1..y8D"mxE?=J$BW?Osq^[F+Q;;1$k?B0M>]z9tR?nFZtlVPA+dy1OD;EUW+c##d"xRKpF8A;hK+swrueY^Epv)VxXzBv(;?+VLd</%(p0FHVECDG-EH`cmmwDwfw
%npq)=TZdpA:yZXv6:X(nM(^Ef?h!/%.&!.g|v5>aTidN/k/(W:OpK$+k5"wqcQ9v859LNPcz/;;8,mI8%Xu.iwE%.Qk2/$Z&JA3L$-x5DW)v$;&|);jR`$2CT)XlCH3Q+;]U0eGDAhr#S/9_$E(Y6Ey>&$%5RB6iiXbZ09vHq@k|QxnKp`re;`W;^3]"p=0;P4a+77<xwIVML^To8h`O@j(.Ep-#Jzh"CItb
[1&sE?ep{s$.D.6;L1iu{RVgz*(5&,&M<D<ETkZYaRM=H@;o-';case"ta":return'#h_KC6L+=eFBqikaA4b&acajNPnbbOk8Ry7!J$!2[j>3^vyG2"0K]L=9DWhWhbHi04S;
g&Z5x,.P?-YSgOlpbKE!"_U`!}Q#wj"a`yitisb1GXGpEboha9qvo
`YIyt@C$+yA659,t)B]2["Nly<Y5(hE$eM(c44%C#UpF!6s
[Db$&-&KY@*pID9N^P*yH$X%c:J]$d3{^=x9S,HnwFc
z!59b%a*o&)xPG&T-#SDWG7orzoP_"*9oY&jiiD~=(itmjmj!3&5hG?D(D=sbSik=
AQo&n7k`d+eF^Eub904
4m[YvO!=Wfo*&<4xQu8]7*(8-t[xI~w!ClJ*RUho3(Ayn`B?,YcEmg[:!k1Y$+&XN*f:F/Km>~aq$/I">?KcIz%&LAW3qB?xe3nuQ+E_bTi1v{I0#0K:0gP.`No%,(s%D$$GFxlJ%?J^OU-uZ#"x-98/P+KSq_`1"pZj6P>1E)pFuZ;!wa=rpD=1#mT>I{U5
uQf>8<zFP=TdLlE3Hd*J[fE
|<WL2%^PfN(,k&s$:s#Go$45VgODu$f%)h6bJeB4
uQni)t<iKcUSO*2_[
QBB6,/b~!dOlP.@;$w
$1OVIatE%4-$EE-R{e]bm+I*R]dT?rGSjEaD%srql1rsjlskVP_d7[U:a``0:8{einfg@nD8_8nQg%v>{d9R`8-m>k]W}2_Ch.i&n4zb28_[[,hGb8`0mU0f}^bDZyX
O0p/$M/]6</_%xT%c8A1el5DnY*BD05tatQ$t))et-KL"cp5[=!!Qw%!Dq
^>$WpWS/;WkCWPk^RgBwj#$-tK+;sT%Z=n)9K.%*6(i>TfR8+$:Ofy_H_iWf+X_~]`EvEie`j@4vAR32E;,&QA3wdQc/=o
tvOm#a,&F0rlS>qS@J]4Kq>PTW-JaVr4V"f@<=/<],?JD]l:]sF6l`6BR=t3GyPTv5tn[*Y$2M!r4a<wT":RFm%5Ow0&n<Xi,XKHXX^8Z@Rv*_|whT!PlGo6(eM@t?5O~%U]Drg_8R(fxKW.}x-HLu*bu[LH+^Mm%EzX_Z2#u_Uo:=ToZQ>rW*L`#UHF8npT1<l!{D9^3
TTi4KQ?!bye6Iphjj)5cuQ!^Nm[fU"4,-juw~Q%ns]>B&`llBlld@RGih$Z^-9#Sc>L11
)Tpt;q3v1d=jvjJ^T`_kmeTpF;33%S{lX
5"WSP(^
V,5:lU:0U]Y.YZf]EO|,,]4+hUwWBn!E&CmSw(|.]XtWra"&e$Nr@9e?5^d6}wh1ljzrz;UGhZi:$C^h"5vZLIR#5;J>>jAD:QgZK^*J
>;eF%p4VY/MXE49E1Xy@Z>:;4O_eJt$(
3r7blI0KOJ5es3!.!.?IzTba,n,M2WS+h)GZJ]|*&54Co`)uq5)-b*VPVDAp@&ELN:|kClT_UMXxpbN5TJ`_plRP&9$XJG>0j8g)iOL01Tmp:Oh,#2t&lSvR?aD)i1,%8*%=/_;/^9&-sQnpJ3P(Z&09}5m(s1pQp6wR,==&2<RRr6Ix.:JGROVCCrgIAEq??PvwQu@GgD%PvY,*YkQ-<1|QLnXoj((0#1Nl
v4O$8|?GiuP1<fUg^Yc(i[)%i|GRo-L@e4/6Pgp@#V>;2+:Ij?2l[K0V[k#n#bY[*~,)lo;<ydIIki7Z<r2Gn!$$t$Dt@pHdB}N#Z(Q|E.Ipbi8vP3N`9j4)QS[bq3WMNoX^Hs<{,@p6rU6E_HTRa&QdS0ZSAGx>=Wi$lDw*:ve:@N[UCKr4XdnVDsb4jTer=!=Q+ZQ=3vQ%qYf=vgXXD0/^>s8WrDjM*:obDUGUX3f+?<85`n`/-F
$N[UW8Y]yW`.B+u8ouWIyo<S1:tI"^P>A4.81*Y7M;3Ke.M"GItF[h(=t+g[~pc&_=cA51ME<l;^9?2xjUH,b
40!GAvr<gt:;"?,F^+S"yIhwAC^X$.lJ&`^
0t2^^do?w)RC=f{;_9~(II[*6/"*&Xqk-^j.1aEZM4nUENp?b,"0J+>c:@4CV:(^$c>"VFhF3%kOq@%XEHN/!x)>B+dr&u&N{p));$<W;r(9cCjiVAn-hJHrcuObY]bNHmOD1gk^0Q!@GIP7K,F"xov<Dnw>AY*aFlSDX&@?vrn&rx{*GmokJYo0Z3kf/TvuOlka<Kcvb/g$Ph*$5$8_J=$GTZ}x*FWX]Q%q$wi`NN5*]hT_d:+Lhru?Z1HIU8JY!sj$rCyMRp.4b4*4>Yc%8@{oWCOeTX~2d5-c3v5X0s|0H?/$ZEv=&a!cTBivZoK;]"Ov_`U4YNX=$yDV*mkCCg>Xa%36r@C!nqT3PloilN[J$CyWsAG_.6:xcx80UHX"-U*%CM4%^b5(bo@v7ek)o";5gUSOzWB:[+5gpS+0:FhPmgm$
wcJKYFjeWb-GwcfmS^dD
V.x(uqRAr(us#0Xxi4PgfsFBg-p^Yhj^%D)11lz]@89wT5ITAG89{Ok2L6rE9yqcpW72:`.4T!9F/!($[m]^IH.QlT-_,dcRHn_0qEL$Gb0-i!G+7gGT^/Jxd
(AZK!d)<::)d0wEL><ftsg{c`M$Qc8QFOVsxjYD:256IzS-@ZDSOh:p>C=hTRh+oBn#yq2kLjs]3
NGfd]w"5Drp?gfg<wm>?Zc`0sbk"r81Nc]1DefEVI?o0T$2yKq(c"J2bx[my]=
*NMei!@YwGyJr]3u<-aEmNX9IFd-2H)<"?Rg^/-+d,O$&i*-6e#m
eO+B-z`Fu}YcD>sYy>L
!m^JYXsd#8Dds!-f`x8Z?dbcN!a@9<=[XE-@Ua[KQlf@
X#/DYt}
a9I;Tu=/&Yu%pqyDbI&obd~yv6RZCx"L_7LE@]2tZ';case"th":return'"h_@qaLZ:h1nZx&Su5uPnb~QrnO.ZyX3n"Wsb>;Jj",9UDPsmU;PrY)"Ar552GogXgO9SqnX6_Cn:p)Qmhb%+5zXm1e
8rEjb
]0Za76[kErfe:Q_4R`~Zq<V
&H3hj]a
BnXgpC1_L]b+:@7[@:=kT[H?lkJjet4>zZ#6abg[.b@ko7@n@&kkZwAes77ha_D$0i1O7
?n!x{&U
9]gZ?^4=1)uV;9W/WW>@&Zqv%:-nK4yJ#=B<kMa,RYg);y3acO]i$e{.2^Ce.$[:M73kqivmXbRfgmdqr4GLi9_aBa.P{TCVIstSl<WY.VUL-HuT$M7:FK>TVqO$/_)2JN=n4;<A6`mj<7zJKCUb61bG!ty#KW>b1`PX|G&l7)af
hnSc(!md)|_8cs)D1)oVnB,5!xa>ttTZg;Q4I2HhX&^Z%n1As:]%s;j{oQLU-V6.d!y;]7R,.wJ9pDhHsrdMX&Jy`zyGr=-GB*XimD@2*|hGa-rtLKohl)dd?
mIYtDsD4pOo1C)hibaspxoKSo#W"hcTle9NZlHtLNFFsWO[jqn,VLS[b?Wq?Is+IfIg
%",>@6$/u2)^-]wGs<uc=C-|pqY0s~#"$E,54/`E^<K2#n_fYfqE`%5YVJLm&B.Ld6wvc|(D&PM>yGO&QOJkd~i=jZtEhE]:ypPtH:U~S%NNYI_D:pZe_"sHPvm3t#lPL9(Ja_f41s5HU:BLYSu>?8ZuiXd.i{P
4Io%-?^?Y`UJg$n21v-8c%Mcy-W_L@hAC]gVt)9MdF6kVC-N.2h/$hD)j]9,&/fPu"aEF"1-1mk"X3$k_{hz-@.Kxh@oaI*]DA9o/JPO1A:FBV+E8IrkYiGg%Paq8:9C!+DG@~9lbFVl[_u3/XhSg|Z{2L5>WZryaHC;c!d+-_oh?E#Hmm[=Txu;I).HS:1>Vd-iot+{7n4lDE)~4uK]PDaVm{WGC.(rq4ZXeLu,q1N+ns#zY7nd&$Q+.0JcZWt?B,;_]5<d6$C<Y!hGf<+n/0>enjpeCcuy2R>57E&u`O#0dTB@k?&AhzQHR.h:6}G~u_p.,r]R1(o{m_4K]FB)y
%#bsSmC>+Epb>>^^l`X"CoU,ucYi#
eYj]0"q!0^L=Q=*C"
L>=X3w?,`^
1/
Wi<,8`s)hC#5;_?=`ae}4O!05L;5N[?I>IT3%xoj+n*"osNRWyn@%BND-?^<D$${8PdOg,v(0FPDg{MfgOf~miAFE9_p8=s3[Db(Jd+}N,[j0&RGE3fU%^o9^N_`GUrc2`v%sLDFNm3W5]8%=xOEPWn2D,ClC!
Tx~W@7V&ASX8zn-6?h7
ahzD.ZjUS
2$=-kJ&6XOkT,<|K$v{kN(acVpza)c@Cv(;bL^l%6Z*X6o*TCD60CQ(@rO^P`(i`5Hy`|9,y]O6NKF"$iNms7hnjT#t/s>]hxRK9Wye!l[Cm*daPi^zQ?W?WMbi>dZ-T8.9

3Q_q7M_L
z
l-Dya?7V{)~u.p,JQ(x?},,0Y/."?P]a^kPC`0ArN4D=i+z)@I(yrYnirx~W8d2$6p6inFWOum:,
0r$X$6p+9-B&F0.s[d02dm_R,vwNLIbI503R7q=ULxA]D#97-vW%)z_GrWX7R%4:.`3"D6"oW&apF7BAaq!_j9:mmg1|c(m>%L6ppi"PQCx+FyVpnW-wlrn0+=6Wb!WNO[SHA@`>MyyqdH:!2)W(0(BM0IEEl-J@4b4
d}.
A3?
BD4u3}Xox*9&X//|5^qc;I?].!lzyNkeXJv;pQS"-AYA=^*T51*F=]#~vUITDXytI-HpJmGuG
6A8b(IL8"3B![Zqg;e+eI~_zPOVe,"c:IiQo@T4Qmsmhao:349_$#rwh9rvOiwVh`&svp2wET,@@v@_X#{]B1FlzhaZ9rCijHf@s*Y7ZX;Z<K7,Bx9_"K*D*j;u{.Tfx=V)ox]3EVC6=Is/9?+tgQ{4bc/e[pO`{/?
bRTLrS}8L[XSdQJ4PUfm<0^dN)o%gr@_7vhJT+nEtNyPPdE,njOTy5FyD+eN8=&R
sI`22Wk@u*yO%X?i!49w`@Y`ZSo&`-^#%xPUr@:sWn]m1L*hU_WcH"646wi:GYP_c^9<K#u:OYR}N/rFt)uyI1L:Fz5?F
7HedNrX6H@)=!^P<4!F>ax=XO=p3=)^H.9=fJ)R^VL+kZtfX?x2-&t@BC*Nd8t7OPU#JxtRU6X;K<1;!XXrS^YCel}jGj`LoZ#&B4xbGxhI/:RwRlGJwlNi&!*Vsk(;N$%%8H4<aR6n/Tt(5IcKC&INridD*1hl:LRUU4W=(&^9Ld6,P;7ZygQ]$;sW5K]MmpYI)H559A7p=.q2
mS
+9iluKQeDx73QQ"XS8_!|jt&`lEMsGbNDf!i!?h-M7l:/w~,3V#R,v[L>7pib';case"ka":return'&c0@iqa+=h1?[V1UN?A#p-3eI9]*4:k0X#;?;ee*1wfSMsc6la/+Om,v08HNF$h0^pE^Pqe:V>%(-eG#
kZkh
!?WLwk
GD>$]/GGk_V7AR@w7GxIvL;s`Lvfq}+<JpU[aK:,e8mq./xdEjP;1dX1eyPetuCcGtY6c}o_
]aZ$uO!yLc,0AZ|Ow
tu
`(:xW59BpF$@$MF|tz8-a(QK9BlwpsQ]l:HL6`[M1}2$WzPs=@&o6+Cnq>cyH84J2hI+,=u+NbQ_>h@yY!8Uu[d.4X"Y0w&>.MslP1:4[pl7,*5
n(1*AC1`?G)WR,To[/>=&V?DyI(ltH-5;OHM#<i%(0wt&=d>K,8%ry5Ij&B@TRH3gHn_kOCS2fs[q4)4QMvY)&SL1WMC`;g|ph
z;v:I;v6vK89S4=G/J`RI&"n%RQL?#=]qPX)upCYtsg`i5gXGV,d0Yy:[xdH=l`Dn"AtJe*(i]2R{n%-Gn7S~orO}]^FVZ_e,Kx9ElAwqyV(cV$M23vE.XG=-5Ojfp,/3xlD&#wiycAb`o2l!
TxZ`^Hf@5NF?K"VF5DELqDK@FVKgu&9k1w-P_fhNQFpC&m,:4FGR.Gv4du@*-RzdxPbYyDLTKFOJa<l=8UleQam5ccnag.&n&J.?Q&s=A&76h!O,wRsnWs#PtVy/d0HBj8?I2-0lA;]?&AtWh
4yhv0,W8qG6TO4dp_`wF6x.`o.
rOdan0.j<El`ra*(SNDLBVFWyI$RKr8%(<`_rHRR`f[uus5/O.qAD=*BJcAgEm$*D`gH9l#,vle^65os
uJ)(Dy9-ek#/_9|De3=tY2-CfxprZER_mjB^Fb+50t>Rw79s$cYO]Z46Y(z;*OYicc]j@3*TH%{4J4S`|`
UGp^M%e
Ee9PcN:Hg=*@xVYu0oq%-+ZC
psDIA6.Twb5dOq`xYv|d.S:9Rv5Zfich9[a_R&bUrsu49cIo.Gf"yF.r!SMd*Ll+I93P7,Y03p~]L,XEt6SrfewyhS<c,0Y
+f@2wSQR|IqlY&u#,7>d9E%Rv80<L8#_aCzO
@|o5LeCX/R0;;Uo`eErm;M?PMdNj,`VxG-A/JbEhLL_^sHk#yMD^mV<XS/Z?e/5[b5aejRkTVefHZWp=&&"xMk*B^xXExn5]])..T,.PyaU{]*5`*6Z;F.fY;<,x7x@xK2[5@=wJ&+hRn,KGNB>ZAao@QvxS.W/[C8aB][t8VCwQ/j<Clt&Xe9VJB$4<4Yun`*Fm]5=?g?vF5#<-kA@f=,o5*gfgQ2<3-W+bT}6H93X{iP7FV^x*Nuoslmo
Gl>g&jtHb/bSh0OR/Xfn!8:i!Q"i=!
x+b+ambK5WJ5|"5lx4daKN.mdVi$f%{/
Q`S,.Rq%/A^B@(1D(oTjS,PP+fX.g=pPYprkjmww>#Gz?,b>97S?!a#RY!/^b^8O2@@We(9;VH;Ev72lnm2sD
-{>d(URLY)@A2^^^HZ1ps(V!t%#{kcBb?kl$21*{/vec<."nYWI=48^pAnoq-^VJ<4$2:O6RS`9?kGXx$_w&Z@xTkg3]e)aaJsSi>]Q`rci$KV6kwg<kf[@Wr:&u4XTiSErJc%:i7D8x>XB8_<RsFvlcO"%Rr?ZFoPO=s2C1Ds/iRQ<fJ6/svd!#o]&.^x;
4K=ofL^aK]Jq2nh:@A5]-4r1OY<|%yf!&yI00
`:KQIOe)/amwy1!HHH]O(Mvbges]vhP7d^-h?j*<my^8Cf%4]N[O-ebc.TnIt9VXL4ZcA6l
+V3@BZ*?F$CI=Lnhjs6=_.%nF8O9ejMl-/AoM.`-0iX-o0%<&bL6Wia0TF+;OY`@*gO06(E{48FJ#XE~`m.z0u!N][!oQU?Ygm7[GkRWqaw^_V.YR([ZDX5l*kPdA:8&`B1huTl$`nXI?6g9bPFmje<n*$GcVsg^6D<z*3EVu|(A;R7p)UP4C]bV>o?
n$AGlqBfZ_
58"PJJ_je:Ji[2#^#q#./Ul+r
1E(dl9
kG74G5^)GsA/bUI1Ts$asf;)1-D4bK"ClJL!fU-Ek#.3o3*fN~4/c?.KSXz$rxdno6l=fkE3PeZ8"|`4$j8i,~*^5FjQE|<d,a96!%@Z?l!<]Mr71#(^`}]9$GBF^K,,)JTH%+X"SW.;c:Edk.DHwk-moK7k",tFhKsR7&Jz!rx&iNDNQ6&"TgH}SogNrTt=RqP>gw[[j<$Mnh5WfeAM
!O%9*"}?r!y;8ON
vY:w9bb!fJ0
5WSSy-q<sw_S"^..jNNG0vL>Twu85/6;(kw<CbvE%Q6-><!pT2*,q
8)pP)GeF@diKUBg@t;-LY<Z2l>eMC/<quQHo}/C3Oppk@$My=jl1[3sIBbZXd_D;n;%+.8J#[J+F6<oisVrAW#M00<nH22Nv<4IR5[/EfA(EyTxD:@PBzAhkv=FJ>-l-QK6^,-hA[WU:nAO1x*z@P$&rp5$r)U(a[pEIZvqVD9(*bIMM&gI)Uci35Co]&JK*$`HR}#k>)Uy,Be#VDHq]yOLHI`w,4MKN$$M9Rn@H3?,j]R(C4lf8g&p.l%}64V~fl,ggGA]dO4AEavY=WqdGGOw.;9wD;-}7r[(?{jh<e_IR}H"c<.:R!::rORv>`wjsbw.#kxT%*>|fu%P]NpZM>a+=9V&qHiw)|wN%@QdjD&4F%l74ruH_w7<g[8B,n2IV^fJX]&1Z`Rmq%r.5gUKtjbs1|AuB_w/pM/aSgy<x{S$_h&@f2w}r|wq]c^<U0L9ij$G_`tn=gnny/sO?+oW=ZK.7c22Z#eJ>7?
5+KzDPC}ryZd/A*aF_n=Q)pA(nq=.Ur}dJ^mR,_,Nyn:q$KkC9
Rx>fLX.!
N`Z^Hr(!NjpOCuZvgXK!u<^E--M[Z]40^nBkPWD%pUx~c>-h0qroul(galR,X//aRxSTpOO:^P.ec&2-c`ccv$GMr,!IU@LjA^NF';case"ja":return'%Zu;:crZK1,tCu<$nTAHWJvqE(S$?9,O`+{kwD1q/"9lX9;g0-5=NY[U3osa$Q)hf]?p_E`p>Q;m"E-`/jS2OPAkH`.GBI?K#TB&@hc21n0^!D!&7w<"yqWoPXD]fiAG9E>EX)N=J4gUB=Fb4H23@G4f)_bH8a"l$k_4u
.2f4[$&r%Rvsba*E
0uq%iN%^Tc+?lbf9s(q/;9InF4;cNdp:t`s<6&xTCm<a;z`SIKPa&$qnPQSZd"H/bh,>qIoKX5@:QgDSRnb;-i$LBU&q-y;A:6bS[=`K^sxyJ#9-VA+5AYUjrtViI]X8fAne>`BzfUC?mQb!x1L`h`K[#@b`V/(kd>0C`vHo:YSNkggvt&edlW&/T&Gt4tx4tC#6w10zq!ZsF_X7syq]_D^{w}Xs^p-wz$:qbyJgl{>,uuW]p*eN;@p-2:6&c,JwbVgZZ|mwWRxW:X,0"Wa!.hl4^#,lm
P/qKIjt6$aU!mFc>jbpY&kmW>lE#>jj(c9>U2tBi.NIVBsOD*0FS;g6N#-*)u8dNnfoyiOq{n/RoJQEB3+y7"{e@cRW
V,_5FbEGQOFkXXt9_DfC=!ft2u`tl3.^*8eb[K3ct"2jbNs5vMbp#|(v+#^NR@.&NfE2#Rj97w1w#]1qF9#CpgtKfDa
1,sP<#Qat^Rx6/^;

K.wYTl1l5]%?>h2d9e!3uo$6A}#!gKF"^=hvwiM<$s.u<//j0J[/6loRic"jN}EhV&RN3{Ufv?#%wn(XpGenidIy$:1Qg#mBDn
}Qt[m:m(dV5_fa9oOgOvkO2`:<kX4vb6p&h8LqQn$s[><v_A%&g6d:L89X5l[IRHgT[--Igx2@XaU-q:ZxlI2Pm[RDsTN_Z0^W"e(e(U`rWy3Jqb|y#c<*/I{0oX*CRJCj1vf.?t{4He3@IpJeITi#emj%<2V_N&:cnfalxY56tX^/bqjq:va/tRD#hS?]kr:T1Z>A
hG(m#Q`,ld.6UA!$,6_f%kWmr#Rgw<h
t=@!HQt~iWYLyCPAU)Z:)Uce2>BWB?*<<ama
J9bdeZDFCoYF{YmgK:z#d:6GWoU(IL6g6;5jCL^pihG$lOFVi/73jQWCqNUk>SCC/m1CHHd2_eyF8a(4=UMYv/rLVM=
~xhIAtIVF[PFtUvZ$bHUQo8N&=UhGb?5!YgC7.Fn6gbgj(>jTq<d(-^.?hm!aZa=~OlHnk%z%pV:<K>5[#n1,AbPCp4xn_6$T.dPX>ZV:V_di^-&(*7t"^(/}b[sFZ7clTDLO-Zhb`@[nA)H|c;9`E6V]<fpLw!Sp+~T?IXkF+@W
&uVi4uAQa(Ob
iNB`PORu6L+Cwi&Xquw$C01qVMN,f1X^x#i.HjB7ct6.?Wor7T9^01On!rdcIK5+<<:KV*7K`r"$^,zFiltW[yR."8c7u9"dZ@V>.3uo5=XeU2W1@jjdSeOQE7Yyf:_B]<JA~BDj
VH^<O)[gj{.y+e?r"!ftm>"PMeH_$]>ad,8bTK6sgkI|d*4wY2E.wq+&J^6"A]b8g:Qmw<eySNj!#TO8OYEI!QZ^"cXq7|@r1+<z*l9.H7-11IHjmfpYX`(XMEvp)cPE3Nhw1j5o`%ryexcG$}.U$heK/:,e>_%pT9h0]M(ghv*z6:6i(M<|VhbFpF$TY^#lKdlFnFYTuL$
E*hF>XvE@<m/u%%e@}wgQ1`+LK!t4!"42M,_tGbP_tmg@9%o73T`[g-U1{hYIJm>:RJFN(AUuKZ;<cV5G>mwfDF.u#s,*;a1lV"Z=|2oE`@mtNO_#8oN;B80/epv:VC0Ch)H50+E,7FaAZ
kyM]}l5"-iwX?6Ae_A6WE2*"!TlYzYh)|[33]rQ:smlFnp($
2W+u5ihW)HVgJbNHh7m+R)#&`tBu_*14>lTSnK-{h#P_8jq9.4^e(js&3?80UtR[,4>nFT[Ng.<(UAXX
]B4VL8H<a%ro>36/Q`wW32_Y`?Uw:3s9mEckQd57^(,$iPaN$[Z
P23>wX_RuSTz!P;exq|B$F,=Q%)!Za}$zExMCP6j`ia[R,aeU*<t{$.f)P^RCP|iqE^oMft<J;Fppt+<q+`b814P_F8,>XEV6v>sH)EQqZVuYj$:hAGv{H0-]YHeHM$I.a##l>K=f9ZvWTPO8%2PCi`#H<ig0[N1g&:*qZxN(=3r
9<*zL]eMm=Zg_zRQHa?3s=aV<p,h:!jER1NL"FoUl:Z>Ya9-`]f)56[=dgVO,NO+bY
aD{]^?&dQ
OVVS{I#lV!s&<TO#^A;P;F6i_*dn+OQ^uH|&$Kx$lL>1&>;ZmFGvcGKbyd}g^^D!XJyiW56,G+{Vx
b8!rp+PLg7{Ln@[IkSe%O2W"i1iIB,EX)#&OnSw?;Hn,s.Kb7&PwfxW0>7a2Mn)Yx<$O}fVYZ+IE4PlW705+l/34e])%Ig<i,fj=9Aop8,BVS*;"O,vtVF`N3$t(is7b<Z_K5VD")Xf
]jJbAPrUqOV;.i]A:b)8i+<S~@R.~^!)I2z@Ss:dne&bzl)kFLq!Vu^]bVOA8(X1@?uOt%,!UV`f>!)Zq6dO}sUcEJ&]x5J?K):VJ+`2;^/<
H~^NsD4-fLtX@rVQ$%%OfcD;ie*uY&&;gm!N!xD`&_-]+cVby)6dKQ#>9=Bd.@aWC]-Rn<Q^){=Y`KjCgaP&EG7`]JQ~^WX9qJNqEa?hny:);5m0
]rzBxfi";Lwv=fZE}v/w*OxSI]e0[1M9fk<yn;Rj2<9[!YzD1Ih7;(ha0#QIXZrmH?dSQ?c?BNk!.gZ(-/>-v>O(7!E^,tS"U`)%r2+_.+48jpy?atOu?MrH71mdO"/n;%-KbJu]Y%k@4]AASVW4#qA-fT(EE)Vr)!WJvg4*yt?`}w@S$&mnzv];lnX0MF*4KV{qnuvKtw@xcM{';case"zh":return'(UF*!g&.W2=vhlfqj"IM140uhPBVo$8XosMg"_[&T4hL`%,%)"pc1#i"BX$Qo,&0OSG2R+UV[4Jb=L(ckDT^*nt,6`TPLFcj$0qM2Sq]M?WpjZtcR&<[)Yrrl4ju!>4,:+<`V
YU)z!.<J)+4b9k&:wVf%WKVYVru/
%fjq*cQ<#V?w^HNewKMbV@Cd>,rN;(<LyyF@Z3Uw^~5CgF$cUrf;%doE
HDr2id]O)x`6%BC=z=adAYXK0s2j(?JMqXsA6GCAV:UiS4T1@cE*W`q(3q6S2>Ok@b*@S4W^1SiLYPPH7
ec6B,Z.DeX43(?$XA+P=9_r?Oi|MROF3gHb?ksxHSEydsrPqxy;/;G+eY[-wEj;X#G8C,Ar_IYJ_nnT`AF^oI
|E`-?C819syC-nV`RHHL%g0SFP{*Th]oiayX)G{@_MA+SUrpP-<9~1`v2.xU`AN?;wXV+C+PWpG<
e/qnj!aX<+(|f$X)X"+OX6gjmovKVGKuT!v7,nojTr`-M/R6<IXFfQe8oXgln|MUp?mN5/cfDk,|Vktn_sFTW5$Ns@Dpuqv`s|XU7kF_s-5eJ6iEtBLl`S5|&?`=7fhQY7qcr!5Uo*csNHd<r[dZ`oY@9%IEX~7&"J"9NCldbv@w2"K"V$+r(:cd-8,<[D!D`>MexQ5s4b;d2g7DD;<?Vze"w)u`fDKYM=a/B=N8Ismn#VDc!-YBdQ%^uR^O:#C_@|iRB*oz4Lg%ZQQ}jHJa`<e<D%Ge5;FX.)4%r/n.LFFJ,+:ROyC2lD7|i%dGN!fNeMoZw@Ddx>;QsX-LbOo{O#Dl>1cT5J:?02`T_`?B7"cgpD7QmBBemjsjbb9(c|Gtv_rX6OT$u0cd9HOWB5n*KVT=6Ek8%l_b6xCcK)(_vrpCcDp<cFAzs8Kcr8?.@^<5;P[ULl>|-9#Yb3w)qRH+MCay#1Y+9K8PodC5@7>42~u
k*v@fXboj1Z?je/St2utb,KV6B?r$V0J/c(c7t#:Nm<9)`a7a.RE)]n
!G(SDx0[%`]w/#LY^Jpg?2EUoj1Llzc",P-hA_JWw@e-Zo>prQc:u$rB8<N<JIj6b9tILk^y@?RSf`wbpmw3kO.|j,>>WzTzZaObaWPgsrxCd^:+6
7k?mEh??]VABB8
tW*R}=j&LQE$Qm[D(PlwKO
/m3RJD:oLH36tf8@DmePRW27UF>edI_&"aF+

9EX6TX`^4cwU4IR;6yaViSR}0PG]M^P~_`.,2H&3)O;1&WP|,!?cX/gTOR%qJ*=O6zfJiT/
enesHQJj=dQ&[Ag3&*!c"?n_gft8eF#:&qj|H4,347i>ict)vA*t&S+:b4mA.n!rOaUeZ"S&xVRF;AA+9I?).)M5e=iPo,xLT$l[QM!8C-$M,TBl8|Q{9(#Afu0?7`Hpv8R[yvjVQ2r<iqD/I`"xe]bdl:gnpGC*yP_Ko):qz%i^g!*gxNdBOdUO+A1m.x-}*S77Ov9{xg7<#CY&2%j|U.*N]|nv%Zu)
eh[Kn<X_oDYYG_q^iJXB}^sq^oZip@l<g*j_~-&8{LT2#I)<@>SQXATp53Hd{[S;{@uC:Cb/~Ut/2:-Ce1@cOQ~&j/!87wC7pff9?9v*np"KE[4+*maDbFg6$RY+g
w7]"uNpQOfOKrJX@`e%u;7-Iox,B3<G1Jk57a+`/s%
eMby5{_f0Nil^w[DnT@?)<mR6Kb}R{xy<SA5s9uNl-`~HiS4[,$[P29tQ#UOa`:6]Pr[QUoKjpv.<Qeg1+&OVaT}Z%&b&,Yk`Aesa^`r(8KJ=o[j2t`59CFFH{F%YGD2@&R>?7_hZTn-plIl=|<Q&gOwDiwq?d^1^W@4v"Lzy**8I_V6Dy>v7.UnA&ExYzF9;,d!V-Zn-.qeYlm,lr!K^RP"F>jl.x4e)X)6elG,Zinn[z307/9k_0:k1}"_!*i`DMFjjW/+`-1dC+A5A83wMm[)a+^X:7VBi%6&b6J(?~Ahe
!5Cf?>/[mmg
=+,fIC,ce{RrV>A6vQTh&yYiU7PDWylvdoR7q[HiCek$P*fd5:XzWuT
]*%p33B._hZh*TmbhU!ZuAdAc?k{KLNWPR&Wjh%n<tyxL+UJ8?J#Ly&<gE"#>K25"VI[<%9!V_[FX([0Ol0=0A@MpuqIqejFq4T6=?qzEyH#AA*G+L+>_BCqp@]ib>5k::>c$fc170KaH)uD^[/w@_AE*.R-f*Y:W:
t-7Qt>5fZ[)f|oB@ddCQQEAqw0_0.1)RiKp$ocqGjFV0W/C9]B2E{jtLlDsZ=,]9q0XwvjS$>[d[c43!&>1&susm;@Tj<%un=f4ldEDWu7dp)3v]Co^1K_):"*ol-X;:2FjfY>0
[NX@S,{iWLj#^:Y]%l_)Q_Y,{#XsGdB]r#P0Z*Vm%aQL*#9J+ZE3Y69^G#XO|7-8I2FIB_7#.uj^q]DafKT!hrT-~5:Y:t3^a1{s|vFw];duLrR,*6-SJVr[V0)6s3w_Rdl7W4{!|>@pKaHi:bHJ0P[S5q:l%.luVmO6kW?L_M^d&4gf5tW7X`[y;OIGX^Svsz)q^V9z$(4';case"zh-tw":return'!UF*!g&mt+xon8{JGm$c<435IR;"iAF3gpg9pA{s9!G"rosKJ06?"-Qel:)9P.VStL*HE-2u+!]/c:%oCRU-
qfO,J;(d"&FT7zhZj9/I@NPR>M:hX3uU>G
C@8Zo?eNOCz#/6T2&`kuJlzMbaUAM=V`u"%&e4/UJSE_x_M9?Z8J~,XLJKQ:SEh&vXL>U#8=x(:f%rOa}[@Z4be*{S,la+,j]E_`Sb8rQr?DdU~_xsk8^&Q2%exWPcl*L?7,Op}Gb]^=#So;/yvy#khbQD
8)9jA;5hKZYTj9?j_jxH>|ZdtK$!y,^HowPA0HRh`@&jV5x2kvxwe|5%w)62L8,G$?[I&i/2pS[*V=b}3LydL.K/vnRXA&KcL>GMyiO$Kzn%WB9J.`i8idCLJ?v&ajYWrIJUkaVFj}7o_^Shy(P3f
6Eh3"X3I/KFcuE;H<nbHWZamtP:NgNU@6|HT"
(Y?HjL[7JAM|=L-Z
pa5
LR"ys6byuOdV4Y4E"#XkhtmMpZO;XY[f=b|_IMoYl1QB<7r>I=QiqdQ_^Qo49(57jD;B}^e<1eF>5["s(oJ2I=#>IF^6gVc<MC~#HYNMPyCx.%jneLCvL9q+Ky
=]Y"1vSvRb>xK6JX5}SJmv9gs5tNeHj7%yTskEN
.T^RO`l89d*c?u:|<Q9EwZ@`L58}*k0#9y0]=,;VA3
4458UlU&@9X7NB3=cw^gX%f@@VAnAu{-)`Kxx({AkiN"Z@f2@>1C])Trg"o685U]+HI?/<UF>KSXPA}=j1x8^o^%#ci27](CRD"q;M6M`sp&~(lRK!n;nK8b*C$OKcGWXL[Q;p[fv5Nz#V1G`4]Z"
t*4@3k7%FFu:|h#-pC2FPdhDEPq+PqmFHM=6?%sa<r4K-4yfZxTd$ayKYa1DP%8LJ^+C,Of:(<7+0w/"/[wDr@(3a!.<!yyq8b0mN3<>NKVK"[w3?k|eQ?W$!-6uMv;
cUFGxC76>EHoZP1FX/8)J0@,R*93M6w"umSDZV=GZE/M+/MFn_Y$upZ1112^<14o"=?byLC%<Wu/VitvGBDZiwNmg+2Gb.UA$NpE>]j[Z5hvlX
(Bn_@1$)9r_Ax,qlEPF>?>4?H/fT?HJ7
lnwY$i`nw_2Sj
RJlHT%6/ij!7CO&,a5Gs[Dcsl$$S/h;m`]i5[VB,*aHAKJEqtAL,GDO6WXRf}S*&Eu2Y,wS_Q)U+;
bq/6!,&*I2V3YeJ7WD^IOW.Z[rep27@_RX?RFo*LQ=YUM"xA8G[R>bCa,$!)bC<x"t01QQR.wF_9wlH"9WEC0U<!6hEn{Ilr2^B`Bvw#]XXZ}p`ISiC<(Rg^[cO$"Hc"aW6BZ9.!soyXAcmAr2FispD#_C=ITTK)UwUXqHJeoOAsn&AN9fxOd*VhN!Uq4
/b<j"_vLd[Jn_^n<sEoj}lr5$$$h$;=B!ujt6SeY)V>`{/]Pprhle*{y,t[`(&wI,fiZCAhANHW/JXHjetWq2uRKe/|:&d/m3<k89@_."nfeW=7c9^e%T[{l]5@^Li;Cm(RU
HDLf1,p[3kC).%d(+>&e07?RiVL$7B,2!"TeOw9/!vA%`NxJ[=:zp601R`?9"l3Md,5B79dG8ILzrV[8id6|gfK
_
Kmma#ndnW^)(L2jjC%CsdqD6f9LS$5uaZ?P2G&W*#z]:qg?,dNKNF::p$lt,T8h@(KAE?pQh>1U,UC#.T*Ty?&?YV*A&saP6c5Dz*9s>Av)@E~P
Uei$dMc_iKG/uIi}qH&3B
V-mr:xW_hC+GMlrUAa8EH)Z83u$/mb@PpOwb4@48)m.6tzRho9$
+ZVZk$ZrFq*xH@@Nv6$|pKoK,~TZb?WU36QoPx0CR]X{cy6+o*kfpa]*IzkT8k$D3sT-p{CV(g8KE)fo<s6+#AQxn#ZsU1;LjA!s<l&pnM2Z:8]V2Wk/p$/"Z-l%&oJ=F]J@Iaj|]{yiQ`:(Y$eEZ,"dqW-9imCw/hrH"/s[l#Y$9Y-0!X!zvd`j$BMdk2R<gsk&JyKyF!pZPG$lPE,
a<cL1]<m]H,KPB,n5-<boV2i#}rmk;*AxiWN2Go<.@d*
LuZIJgsDX5w)AYKmWI((Hw4X.SeHj065`@~c{u<qq$v54-MO)Mkn6(O]|sp?V8;]y[wPzGfWWLNpoLf^_R1:kI*X#TJHLEGh.NQ%&Sz.E3X-,ftqWy/ZWEDIaooIO&ic@"CxlT|MTThr%`dUp8|Rcm1;@bzf+&[rF90*Zm9*SB[Sn<8hArKa5::)dPR52iG4@`Lc_ea;NrT18_vbHa))N`V$?N6d@4lIb3egIG<8-rkF?SI*Mve."+p4p=
GTopjG)5l/2`!fl"-_=x_q!H_sFiN/OsEU+kDHJ5;P(_,CtCQtvkNd"~MIjU)Y1mO(2RxuH|sspG8I$FefIoO{#+C^Oblv*bLyh"?-a[<:UBSiEW1Z?.q~+_OtmkI~kMgw!)@"lC9]8j4oq;#<o
4WioDj/9I;8EksUhL([cKo5_SPuV?!53@pPCr:Pi^%9x<,j[&3C~rNo.w""J,QF:(34b??szRN-Mbv9z"dZwm3[w@bD7J:)+_PQ=8whVAD+<$89~Q$S%YX
<uNM[YU>KAQs,f=HKK:=*y`uy[?xvw|
-2+xSManEz)M2d!#%';case"ko":return'&Zu1$bOZK/fnZxg$j.^#H#<Bpt])fIMp,1s+CjJ]7[F,RD#^~&^K_AQggI*(ab*h-ven9PzxNcY<fGV8(_wCo28_`M}yD?E1ASYj?BT,Wl(
q]=7x^yn
tqL/>)6_ZWvxKON
1)j]dgY[H<tfv,,X@Ntk?4wNyiiv:)=</JX5]?0?LEd3,u1wjKDXHJKCPk8s3(rx*>iOajPe]vja?"K~Lk6t[yczIUDk=60BB4R?+Vi+BI.biZ=n:Y;/oWEzLmDN(xsI=h&ZsJqLf^wqRCFcQn*3pYy_/;X(7w4I@KX~Lw8R^8Pl`z<PG%sryoTI*X5aQVjuYal,Y-weL)o9QJGZxH4/o>p3W,k=-<BZ@k5Op-<Nqn)P6wADp.oYQLk[&5=$v7B|$+Cv[d^%PYw8hJa1A2vnnw.OQ!k`=To.>Oa2lu6:Z`Xb,$`c<%c:OrZ(>m6w4Mk&dR+r-a%RS:M2-x%b.we*Dn++jsT2W77(jCl1)r?i:9/{A]DC3`J7,d9`)&":;%Ef<mTYh~yC@Ds!=$*;eJpj(9^"v{Tp$hp56+.T,_A~Kk?pu./q8.:]U5p]?&y?BlA)X@8YA=uSfH&dQ^Ijo.Q5rP?VTrJ%fO]a/PexA
Hs[(Qmz&.*O~v,Kbe]FTaXi:AYol2n7uTf=NPy
{*G7U],sA^f$K.bU*AS]^E_ly]uwMssB1!9xM3h2nFY$~8<XrO3C)0o<M055R5Hww-"iy!Fw1q~KL:-:c#HTHVi1pS[t|e]qzbdtgJuvEv[T,wfQQ
48nu._.Q--t<,?h>
i,I+fNA@iPU6vY+d=8.Xr0A^m<$+96^DNoTiFjT
G&9xW=<NpgrQ6asU_f+kHE[#?}^P>ucj!m_&1~Ea5Q%qZ4f2wLm-IPek@_K3A?K<ag7HqE_Pz$qk["Ba
2=B48B$4dmaT`0^d.XV
!Sx0$>-$n
&[.NXupgnz!w%J"8bG@co^U$OezT)]|(#nj7Iu!v^j1uWQp=m)~#ZdQ,~tr2jx;WL=|j{X15._U:&KfYQe.q<rE@)p$OX9=3&.E]}^T9zjnu2r8qv^1Tl..+e7{%7Taz")L0+v.MA?(caozR_P{(q*WG@MKt2F
5W^D.+"bu>Z3dwMZ7Z`Li}Gq,LVPiUSy%M;@%@CZ*8tE@s
5DU<r]II9T<5:<&/dcO
A
p5W":NGdId0@MpXu[aumvGPX1s~@Zb!R6L`E4:}l~Y3?bM|s=#XYhBQZ<vt`uI]ijGp%"N_O8P>*62x)tfeY87k3k"[:AhJWlsTu]Q8+a%(!Wveb#QO+lFNlN0,J<j)d#Dk4~e{L+DIl>ZBVC_?yNs@4/WcChXE;c%se}#(/!dV:wfb3#=cU5WuHY0<&R=yX&B=s-@uD"3JqQNc=E*}7a>NPHua>Q)t1a-gP.TJ:c4D+48)<3i"N10}TO".OKh
kDep<G4=AmPQOFnm11A)]+((#kl)LSQC$f6=ZTg8OENc8
M=pe["h:9}i!b<eK`%?nh][7@RPe6C7Nc[ZK/{*Z:X-m(,x*I~?!7.3D1"@jhSL2N-O$WgxJ-hs8CcI5/vaOm?5:PWU?:2d>tufq7-d&pW?@[GuelgUgE
a=TH)k/-$yfZ*#^htP
ism]>r0,FAFU+p=,@F6Ei]fu1L&5eE+YT6KXj9Xxwn.<yaD^_Zkl
/rt[0zMTd^.w*]82i9k,){@RFi?CF"CXxz.`6J5rmd:pA<lq_RS7yjQ/dFE!mI)|SHWO`n?P,-g%ZKxJt6MN^WlJr+)9yFl$M|UO#2W`&qHO^?5YK|sQ/b:%@)gv-,Xp!@",ds=9uRPau%k6/s0C6YFFal({c
.mEfY_&x%s[K`5)L;TMx4Q=@7b:gGcuO>6hSTYFSIli`6L"ORn%H(BJ*7g^:pZ*J(GtTM(X.)tJ{f$D?Mm-|Hi^&RfaUBX5x+GH85nkf"L7~g8W/0Oh$s0F
7>xqYIP_*:RHIIuvl&@&*x@ZMTMIs,?uSv)8nghJdMr6sj1MACLPw1eyCM,+m..Od9C@o,`9w9l1,5/C!F;fAwEr1HmT_gqihWDtFsPFdsJoAWj!ludq&5)<;vtPpfhjtpAfZdI^F5jq[.VYXTIO1b@za{[}`4+M@T.#?_8DKz77"XVt"6j+b5a>6JNA:+5(a-(ksQW]O!L{hb0iqfD6rpF;oi9=+T#bku!QJ`N_^LWwxy?:fXii5cNPf$+qxh?GBh_2gZT.WQEM(XTB_[T2D#!g;k:dEl=g`hAH!_>n"I6|Zqr^=7M(C$NqZ^y9#23z$yA^i2C@ZinRbNJ7B/A>"3g5Z%uqZ=M"i{_8$I_$mFY_jG+am2dZ;hQ#s

-2ZsKx3Td@u#Y)lT5javbF<?R1;kI)A!<B&!G9sG&RJh*<f]u^MMUbQ6M[]uh_U,OFDx$x/^|n;a9JhspLOTIEW4
m84DW=8>AN8hOCdMQ}S&cYJ<=uL][W4wl1?TR^o7RQ5"OXobjR?!6p+>^&)0pEO<N7G1VE1mmGL4?JL5g$0~jqYV_<k=,^[=;*v|NM=30eZ7VY162J@)T/9
l;0^I0b39&Pf9&i40`XLs7:s8HTNj@>3t8l)YF)(-1m73*undL4NryJx)`^)0RP|AuC8auv+B"4P6v/>ZQCYF,Q=f[J
o}K>ypWFQLhBd5wL>0v_&nuVIjfcX5J!Sh2}>TJT]Sv7Td>tD2,XwS-l[&pfK_<N.Cj>,ewS;0;W8Eye!7fq_M)"
;[Gt~3HO3%;aoqsBu=><7J)L^oQ/g:q[KH_ST#5';}}function
get_translations($De){$Vb=($De!="en"?decompress_string(get_compressed("en")):"");$Ti=array();foreach(explode("\n",decompress_string(get_compressed($De),$Vb))as$X)$Ti[]=(strpos($X,"\t")?explode("\t",$X):$X);return$Ti;}abstract
class
SqlDb{static$instance;static$untrusted=false;var$extension;var$flavor='';var$server_info;var$affected_rows=0;var$info='';var$errno=0;var$error='';protected$multi;abstract
function
attach($O,$V,$Eg);abstract
function
quote($Q);abstract
function
select_db($Gb);abstract
function
query($I,$dj=false);function
multi_query($I){return$this->multi=$this->query($I);}function
store_result(){return$this->multi;}function
next_result(){return
false;}function
inTransaction(){return
false;}}if(extension_loaded('pdo')){abstract
class
PdoDb
extends
SqlDb{protected$pdo;function
dsn($nc,$V,$Eg,array$cg=array()){$cg[\PDO::ATTR_ERRMODE]=\PDO::ERRMODE_SILENT;$cg[\PDO::ATTR_STATEMENT_CLASS]=array('Adminer\PdoResult');try{$this->pdo=new
\PDO($nc,$V,$Eg,$cg);}catch(\Exception$Fc){return$Fc->getMessage();}$this->server_info=@$this->pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);return'';}function
quote($Q){return$this->pdo->quote($Q);}function
query($I,$dj=false){$J=$this->pdo->query($I);$this->error="";if(!$J){list(,$this->errno,$this->error)=$this->pdo->errorInfo();if(!$this->error)$this->error=lang(25);return
false;}$this->store_result($J);return$J;}function
store_result($J=null){if(!$J){$J=$this->multi;if(!$J)return
false;}if($J->columnCount()){$J->num_rows=$J->rowCount();return$J;}$this->affected_rows=$J->rowCount();return
true;}function
next_result(){$J=$this->multi;if(!is_object($J))return
false;$J->_offset=0;return@$J->nextRowset();}function
inTransaction(){return$this->pdo->inTransaction();}}class
PdoResult
extends
\PDOStatement{var$_offset=0,$num_rows;function
fetch_assoc(){return$this->fetch_array(\PDO::FETCH_ASSOC);}function
fetch_row(){return$this->fetch_array(\PDO::FETCH_NUM);}private
function
fetch_array($zf){$K=$this->fetch($zf);return($K?array_map(array($this,'unresource'),$K):$K);}private
function
unresource($X){return(is_resource($X)?stream_get_contents($X):$X);}function
fetch_field(){$L=(object)$this->getColumnMeta($this->_offset++);$U=$L->pdo_type;$L->type=($U==\PDO::PARAM_INT?0:15);$L->charsetnr=($U==\PDO::PARAM_LOB||(isset($L->flags)&&in_array("blob",(array)$L->flags))?63:0);return$L;}function
seek($Qf){for($s=0;$s<$Qf;$s++)$this->fetch();}}}function
add_driver($t,$D){SqlDriver::$drivers[$t]=$D;}function
get_driver($t){return
SqlDriver::$drivers[$t];}abstract
class
SqlDriver{static$instance;static$drivers=array();static$extensions=array();static$jush;protected$conn;protected$types=array();var$delimiter=";";var$insertFunctions=array();var$editFunctions=array();var$unsigned=array();var$operators=array();var$functions=array();var$grouping=array();var$onActions="RESTRICT|NO ACTION|CASCADE|SET NULL|SET DEFAULT";var$partitionBy=array();var$inout="IN|OUT|INOUT";var$enumLength="'(?:''|[^'\\\\]|\\\\.)*'";var$generated=array();var$primary="";static
function
connect($O,$V,$Eg){$lb=new
Db;return($lb->attach($O,$V,$Eg)?:$lb);}function
__construct(Db$lb){$this->conn=$lb;}function
types(){return
call_user_func_array('array_merge',array_values($this->types));}function
structuredTypes(){return
array_map('array_keys',$this->types);}function
enumLength(array$k){}function
unconvertFunction(array$k){}function
select($R,array$N,array$Z,array$r,array$F=array(),$z=1,$G=0,$Yg=false){$qe=(count($r)<count($N));$I=adminer()->selectQueryBuild($N,$Z,$r,$F,$z,$G);if(!$I)$I="SELECT".limit(($_GET["page"]!="last"&&$z&&$r&&$qe&&JUSH=="sql"?"SQL_CALC_FOUND_ROWS ":"").implode(", ",$N)."\nFROM ".table($R),($Z?"\nWHERE ".implode(" AND ",$Z):"").($r&&$qe?"\nGROUP BY ".implode(", ",$r):"").($F?"\nORDER BY ".implode(", ",$F):""),$z,($G?$z*$G:0),"\n");$di=microtime(true);$K=$this->conn->query($I,(!$z&&!$Yg?1:0));if($Yg)echo
adminer()->selectQuery($I,$di,!$K);return$K;}function
delete($R,$fh,$z=0){$I="FROM ".table($R);return
queries("DELETE".($z?limit1($R,$I,$fh):" $I$fh"));}function
update($R,array$P,$fh,$z=0,$Jh="\n"){$yj=array();foreach($P
as$y=>$X)$yj[]="$y = $X";$I=table($R)." SET$Jh".implode(",$Jh",$yj);return
queries("UPDATE".($z?limit1($R,$I,$fh,$Jh):" $I$fh"));}function
insert($R,array$P){return
queries("INSERT INTO ".table($R).($P?" (".implode(", ",array_keys($P)).")\nVALUES (".implode(", ",$P).")":" DEFAULT VALUES").$this->insertReturning($R));}function
insertReturning($R){return"";}function
insertUpdate($R,array$M,array$Wg){foreach($M
as$P){$Z=array();foreach($P
as$y=>$X){if(isset($Wg[idf_unescape($y)]))$Z[]="$y = $X";}if(!($Z&&$this->update($R,$P," WHERE ".implode(" AND ",$Z))&&$this->conn->affected_rows)&&!$this->insert($R,$P))return
false;}return
true;}function
begin(){return
queries("BEGIN");}function
commit(){return
queries("COMMIT");}function
rollback(){return
queries("ROLLBACK");}function
slowQuery($I,$Fi){}function
convertSearch($u,array$X,array$k){return$u;}function
value($X,array$k){return(method_exists($this->conn,'value')?$this->conn->value($X,$k):$X);}function
quoteBinary($_h){return
q($_h);}function
typeName(\stdClass$k){return(isset($k->native_type)?$k->native_type:"");}function
warnings(){}function
tableHelp($D,$te=false){}function
inheritsFrom($R){return
array();}function
inheritedTables($R){return
array();}function
partitionsInfo($R){return
array();}function
hasCStyleEscapes(){return
false;}function
lineComment(){return"--";}function
engines(){return
array();}function
supportsIndex(array$S){return!is_view($S);}function
supportsAlterIndex(array$S){return
true;}function
indexAlgorithms(array$qi){return
array();}function
indexOpclasses(){return
array();}function
checkConstraints($R){return
get_key_vals("SELECT c.CONSTRAINT_NAME, CHECK_CLAUSE
FROM INFORMATION_SCHEMA.CHECK_CONSTRAINTS c
JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS t
	ON c.CONSTRAINT_SCHEMA = t.CONSTRAINT_SCHEMA AND c.CONSTRAINT_NAME = t.CONSTRAINT_NAME".($this->conn->flavor=='maria'?" AND c.TABLE_NAME = ".q($R):"")."
WHERE c.CONSTRAINT_SCHEMA = ".q($_GET["ns"]!=""?$_GET["ns"]:DB)."
AND t.TABLE_NAME = ".q($R).(JUSH=="pgsql"?"
AND CHECK_CLAUSE NOT LIKE '% IS NOT NULL'":""),$this->conn);}function
allFields(){$K=array();if(DB!=""){foreach(get_rows("SELECT c.TABLE_NAME AS tab, c.COLUMN_NAME AS field, c.IS_NULLABLE AS nullable,
	c.DATA_TYPE AS type, c.CHARACTER_MAXIMUM_LENGTH AS length,
	".(JUSH=='sql'?"c.COLUMN_KEY = 'PRI'":"k.COLUMN_NAME")." AS ".idf_escape("primary")."
FROM INFORMATION_SCHEMA.COLUMNS c".(JUSH=='sql'?"":"
LEFT JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS t ON c.TABLE_SCHEMA = t.TABLE_SCHEMA AND c.TABLE_NAME = t.TABLE_NAME AND t.CONSTRAINT_TYPE = 'PRIMARY KEY'
LEFT JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE k
	ON t.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA AND t.CONSTRAINT_NAME = k.CONSTRAINT_NAME AND c.TABLE_SCHEMA = k.TABLE_SCHEMA AND c.TABLE_NAME = k.TABLE_NAME AND c.COLUMN_NAME = k.COLUMN_NAME")."
WHERE c.TABLE_SCHEMA = ".q($_GET["ns"]!=""?$_GET["ns"]:DB)."
ORDER BY c.TABLE_NAME, c.ORDINAL_POSITION",$this->conn)as$L){$L["null"]=($L["nullable"]=="YES");$K[$L["tab"]][]=$L;}}return$K;}}add_driver("sqlite","SQLite");define('Adminer\DRIVER',"sqlite");if(class_exists("SQLite3")&&$_GET["ext"]!="pdo"){abstract
class
SqliteDb
extends
SqlDb{var$extension="SQLite3";private$link;function
attach($n,$V,$Eg){$this->link=new
\SQLite3($n);$Aj=$this->link->version();$this->server_info=$Aj["versionString"];return'';}function
query($I,$dj=false){$J=@$this->link->query($I);$this->error="";if(!$J){$this->errno=$this->link->lastErrorCode();$this->error=$this->link->lastErrorMsg();return
false;}elseif($J->numColumns())return
new
Result($J);$this->affected_rows=$this->link->changes();return
true;}function
quote($Q){return(is_utf8($Q)?"'".$this->link->escapeString($Q)."'":"x'".bin2hex($Q)."'");}}class
Result{var$num_rows;private$result,$offset=0;function
__construct($J){$this->result=$J;}function
fetch_assoc(){return$this->result->fetchArray(SQLITE3_ASSOC);}function
fetch_row(){return$this->result->fetchArray(SQLITE3_NUM);}function
fetch_field(){$cj=array(1=>"integer","real","text","blob","null");$d=$this->offset++;$U=$this->result->columnType($d);return(object)array("name"=>$this->result->columnName($d),"type"=>($U==SQLITE3_TEXT?15:0),"native_type"=>$cj[$U],"charsetnr"=>($U==SQLITE3_BLOB?63:0),);}}}elseif(extension_loaded("pdo_sqlite")){abstract
class
SqliteDb
extends
PdoDb{var$extension="PDO_SQLite";function
attach($n,$V,$Eg){return$this->dsn(DRIVER.":$n","","");}function
quote($Q){return(is_utf8($Q)?parent::quote($Q):"x'".bin2hex($Q)."'");}}}if(class_exists('Adminer\SqliteDb')){class
Db
extends
SqliteDb{function
attach($n,$V,$Eg){parent::attach($n,$V,$Eg);$this->query("PRAGMA foreign_keys = 1");$this->query("PRAGMA busy_timeout = 500");return'';}function
select_db($n){$I="ATTACH ".$this->quote(preg_match("~(^[/\\\\]|:)~",$n)?$n:dirname($_SERVER["SCRIPT_FILENAME"])."/$n")." AS a";if(is_readable($n)&&$this->query($I))return!self::attach($n,'','');return
false;}}}class
Driver
extends
SqlDriver{static$extensions=array("SQLite3","PDO_SQLite");static$jush="sqlite";protected$types=array(array("integer"=>0,"real"=>0,"numeric"=>0,"text"=>0,"blob"=>0));var$insertFunctions=array();var$editFunctions=array("integer|real|numeric"=>"+/-","text"=>"||",);var$operators=array("=","<",">","<=",">=","!=","LIKE","LIKE %%","IN","IS NULL","NOT LIKE","NOT IN","IS NOT NULL","SQL");var$functions=array("hex","length","lower","round","unixepoch","upper");var$grouping=array("avg","count","count distinct","group_concat","max","min","sum");static
function
connect($O,$V,$Eg){if($Eg!="")return
lang(26);return
parent::connect(":memory:","","");}function
__construct(Db$lb){parent::__construct($lb);if(min_version(3.31,0,$lb))$this->generated=array("STORED","VIRTUAL");if(min_version(3.37,0,$lb))$this->types[0]["any"]=0;}function
structuredTypes(){return
array_keys($this->types[0]);}function
quoteBinary($_h){return"x".q(bin2hex($_h));}function
engines(){$K=array("table");if(min_version("3.8.2")){if(min_version(3.37)){$K[]="STRICT";$K[]="STRICT, WITHOUT ROWID";}$K[]="WITHOUT ROWID";}return$K;}function
insertUpdate($R,array$M,array$Wg){$yj=array();foreach($M
as$P)$yj[]="(".implode(", ",$P).")";return
queries("REPLACE INTO ".table($R)." (".implode(", ",array_keys(reset($M))).") VALUES\n".implode(",\n",$yj));}function
tableHelp($D,$te=false){if(preg_match('~^sqlite_(seq|stat.)~',$D,$B))return"fileformat2.html#$B[1]tab";if(preg_match('~^sqlite(_temp)?_(master|schema)$~',$D))return"schematab.html";}function
checkConstraints($R){preg_match_all('~ CHECK *(\( *(((?>[^()]*[^() ])|(?1))*) *\))~',get_val("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ".q($R),0,$this->conn),$Ye);return
array_combine($Ye[2],$Ye[2]);}function
allFields(){$K=array();foreach(tables_list()as$R=>$U){foreach(fields($R)as$k)$K[$R][]=$k;}return$K;}}function
idf_escape($u){return'"'.str_replace('"','""',$u).'"';}function
table($u){return
idf_escape($u);}function
get_databases($fd){return
array();}function
limit($I,$Z,$z,$Qf=0,$Jh=" "){return" $I$Z".($z?$Jh."LIMIT $z".($Qf?" OFFSET $Qf":""):"");}function
limit1($R,$I,$Z,$Jh="\n"){return(preg_match('~^INTO~',$I)||get_val("SELECT sqlite_compileoption_used('ENABLE_UPDATE_DELETE_LIMIT')")?limit($I,$Z,1,0,$Jh):" $I WHERE rowid = (SELECT rowid FROM ".table($R).$Z.$Jh."LIMIT 1)");}function
db_collation($h,array$bb){return
get_val("PRAGMA encoding");}function
logged_user(){return
get_current_user();}function
tables_list(){return
get_key_vals("SELECT name, type FROM sqlite_master WHERE type IN ('table', 'view') ORDER BY (name LIKE 'sqlite_%'), name");}function
count_tables(array$g){return
array();}function
db_status(){$tg=get_val("PRAGMA page_size");$nd=get_val("PRAGMA freelist_count")*$tg;return
array("Data_length"=>get_val("PRAGMA page_count")*$tg-$nd,"Index_length"=>0,"Data_free"=>$nd,);}function
table_status($D="",$Rc=false){$K=array();$M=array();if(!$Rc&&$D==""){connection()->query("PRAGMA optimize = 0x10002");$M=get_key_vals("SELECT tbl, MAX(CAST(stat AS integer)) FROM sqlite_stat1 GROUP BY tbl");}foreach(get_rows("SELECT name AS Name, type AS Engine, sql, 'rowid' AS Oid, '' AS Auto_increment FROM sqlite_master WHERE type IN ('table', 'view') ".($D!=""?"AND name = ".q($D):"ORDER BY (name LIKE 'sqlite_%'), name"))as$L){if($L["Engine"]=="table"){$ki=preg_replace('~.*\)~s','',$L["sql"]);$L["Engine"]=implode(", ",array_filter(array((preg_match('~\bSTRICT\b~i',$ki)?"STRICT":0),(preg_match('~\bWITHOUT\s+ROWID\b~i',$ki)?"WITHOUT ROWID":0),)))?:"table";}unset($L["sql"]);$L["Rows"]=idx($M,$L["Name"],0);$K[$L["Name"]]=$L;}if(!$Rc){foreach(get_rows("SELECT * FROM sqlite_sequence".($D!=""?" WHERE name = ".q($D):""),null,"")as$L)$K[$L["name"]]["Auto_increment"]=$L["seq"];}return$K;}function
is_view(array$S){return$S["Engine"]=="view";}function
fk_support(array$S){return!get_val("SELECT sqlite_compileoption_used('OMIT_FOREIGN_KEY')");}function
fields($R){$K=array();$Yh=get_val("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ".q($R));$ah=array("select"=>1,"where"=>1,"order"=>1);if(!preg_match('~^sqlite(_temp)?_(master|schema)$~',$R))$ah+=array("insert"=>1,"update"=>1);foreach(get_rows("PRAGMA table_".(min_version(3.31)?"x":"")."info(".table($R).")")as$L){$D=$L["name"];$U=strtolower($L["type"]);$i=$L["dflt_value"];$K[$D]=array("field"=>$D,"type"=>(preg_match('~int~i',$U)?"integer":(preg_match('~char|clob|text~i',$U)?"text":(preg_match('~blob~i',$U)?"blob":(preg_match('~real|floa|doub~i',$U)?"real":(preg_match('~any~i',$U)?"any":"numeric"))))),"full_type"=>$U,"default"=>(preg_match("~^'(.*)'$~",$i,$B)?str_replace("''","'",$B[1]):($i=="NULL"?null:$i)),"null"=>!$L["notnull"],"privileges"=>$ah,"primary"=>$L["pk"],);if($L["pk"]&&preg_match('~\bAUTOINCREMENT\b~i',$Yh))$K[$D]["auto_increment"]=true;}$u='(("[^"]*+")+|[a-z0-9_]+)';preg_match_all('~'.$u.'\s+text\s+COLLATE\s+(\'[^\']+\'|\S+)~i',$Yh,$Ye,PREG_SET_ORDER);foreach($Ye
as$B){$D=str_replace('""','"',preg_replace('~^"|"$~','',$B[1]));if($K[$D])$K[$D]["collation"]=trim($B[3],"'");}preg_match_all('~'.$u.'\s.*GENERATED ALWAYS AS \((.+)\) (STORED|VIRTUAL)~i',$Yh,$Ye,PREG_SET_ORDER);foreach($Ye
as$B){$D=str_replace('""','"',preg_replace('~^"|"$~','',$B[1]));$K[$D]["default"]=$B[3];$K[$D]["generated"]=strtoupper($B[4]);}return$K;}function
indexes($R,$f=null){$f=connection($f);$K=array();$Yh=get_val("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ".q($R),0,$f);if(preg_match('~\bPRIMARY\s+KEY\s*\((([^)"]+|"[^"]*"|`[^`]*`)++)~i',$Yh,$B)){$K[""]=array("type"=>"PRIMARY","columns"=>array(),"lengths"=>array(),"descs"=>array());preg_match_all('~((("[^"]*+")+|(?:`[^`]*+`)+)|(\S+))(\s+(ASC|DESC))?(,\s*|$)~i',$B[1],$Ye,PREG_SET_ORDER);foreach($Ye
as$B){$K[""]["columns"][]=idf_unescape($B[2]).$B[4];$K[""]["descs"][]=(preg_match('~DESC~i',$B[5])?'1':null);}}if(!$K){foreach(fields($R)as$D=>$k){if($k["primary"])$K[""]=array("type"=>"PRIMARY","columns"=>array($D),"lengths"=>array(),"descs"=>array(null));}}$ci=get_key_vals("SELECT name, sql FROM sqlite_master WHERE type = 'index' AND tbl_pos_name = ".q($R),$f);foreach(get_rows("PRAGMA index_list(".table($R).")",$f)as$L){$D=$L["name"];$v=array("type"=>($L["unique"]?"UNIQUE":"INDEX"));$v["lengths"]=array();$v["descs"]=array();foreach(get_rows("PRAGMA index_info(".idf_escape($D).")",$f)as$zh){$v["columns"][]=$zh["name"];$v["descs"][]=null;}if(preg_match('~^CREATE( UNIQUE)? INDEX '.preg_quote(idf_escape($D).' ON '.idf_escape($R),'~').' \((.*)\)$~i',$ci[$D],$rh)){preg_match_all('/("[^"]*+")+( DESC)?/',$rh[2],$Ye);foreach($Ye[2]as$y=>$X){if($X)$v["descs"][$y]='1';}}if(!$K[""]||$v["type"]!="UNIQUE"||$v["columns"]!=$K[""]["columns"]||$v["descs"]!=$K[""]["descs"]||!preg_match("~^sqlite_~",$D))$K[$D]=$v;}return$K;}function
foreign_keys($R){$K=array();foreach(get_rows("PRAGMA foreign_key_list(".table($R).")")as$L){$o=&$K[$L["id"]];if(!$o)$o=$L;$o["source"][]=$L["from"];$o["target"][]=$L["to"];}return$K;}function
view($D){return
array("select"=>preg_replace('~^(?:[^`"[]+|`[^`]*`|"[^"]*")* AS\s+~iU','',get_val("SELECT sql FROM sqlite_master WHERE type = 'view' AND name = ".q($D))));}function
collations(){return(isset($_GET["create"])?get_vals("PRAGMA collation_list",1):array());}function
information_schema($h,$Ah=""){return
false;}function
error(){return
h(connection()->error);}function
check_sqlite_name($D){$Oc="db|sdb|sqlite";if(!preg_match("~^[^\\0]*\\.($Oc)\$~",$D)){connection()->error=lang(27,str_replace("|",", ",$Oc));return
false;}return
true;}function
create_database($h,$ab){if(file_exists($h)){connection()->error=lang(28);return
false;}if(!check_sqlite_name($h))return
false;try{$_=new
Db();$_->attach($h,'','');}catch(\Exception$Fc){connection()->error=$Fc->getMessage();return
false;}$_->query('PRAGMA encoding = "UTF-8"');$_->query('CREATE TABLE adminer (i)');$_->query('DROP TABLE adminer');return
true;}function
drop_databases(array$g){connection()->attach(":memory:",'','');foreach($g
as$h){if(!check_sqlite_name($h))return
false;if(!@unlink($h)){connection()->error=lang(28);return
false;}}return
true;}function
rename_database($D,$ab){if(!check_sqlite_name($D))return
false;connection()->attach(":memory:",'','');connection()->error=lang(28);return@rename(DB,$D);}function
auto_increment(){return" PRIMARY KEY AUTOINCREMENT";}function
alter_table($R,$D,array$l,array$hd,$eb,$wc,$ab,$za,$Cg){$qj=($R==""||$hd||$wc);foreach($l
as$k){if($k[0]!=""||!$k[1]||$k[2]){$qj=true;break;}}$pa=array();$og=array();foreach($l
as$k){if($k[1]){$pa[]=($qj?$k[1]:"ADD ".implode($k[1]));if($k[0]!="")$og[$k[0]]=$k[1][0];}}if(!$qj){foreach($pa
as$X){if(!queries("ALTER TABLE ".table($R)." $X"))return
false;}if($R!=$D&&!queries("ALTER TABLE ".table($R)." RENAME TO ".table($D)))return
false;}elseif(!recreate_table($R,$D,$pa,$og,$hd,$za,array(),"","",$wc))return
false;if($za){queries("BEGIN");queries("UPDATE sqlite_sequence SET seq = $za WHERE name = ".q($D));if(!connection()->affected_rows)queries("INSERT INTO sqlite_sequence (name, seq) VALUES (".q($D).", $za)");queries("COMMIT");}return
true;}function
recreate_table($R,$D,array$l,array$og,array$hd,$za="",$w=array(),$jc="",$ga="",$wc=""){if($R!=""){if(!$l){foreach(fields($R)as$y=>$k){if($w)$k["auto_increment"]=0;$l[]=process_field($k,$k);$og[$y]=idf_escape($y);}}$Xg=false;foreach($l
as$k){if($k[6])$Xg=true;}$lc=array();foreach($w
as$y=>$X){if($X[2]=="DROP"){$lc[$X[1]]=true;unset($w[$y]);}}foreach(indexes($R)as$ye=>$v){$e=array();foreach($v["columns"]as$y=>$d){if(!$og[$d])continue
2;$e[]=$og[$d].($v["descs"][$y]?" DESC":"");}if(!$lc[$ye]){if($v["type"]!="PRIMARY"||!$Xg)$w[]=array($v["type"],$ye,$e);}}foreach($w
as$y=>$X){if($X[0]=="PRIMARY"){unset($w[$y]);$hd[]="  PRIMARY KEY (".implode(", ",$X[2]).")";}}foreach(foreign_keys($R)as$ye=>$o){foreach($o["source"]as$y=>$d){if(!$og[$d])continue
2;$o["source"][$y]=idf_unescape($og[$d]);}if(!isset($hd[" $ye"]))$hd[]=" ".format_foreign_key($o);}queries("BEGIN");}$Na=array();foreach($l
as$k){if(preg_match('~GENERATED~',$k[3]))unset($og[array_search($k[0],$og)]);$Na[]="  ".implode($k);}$Na=array_merge($Na,array_filter($hd));foreach(driver()->checkConstraints($R)as$Pa){if($Pa!=$jc)$Na[]="  CHECK ($Pa)";}if($ga)$Na[]="  CHECK ($ga)";$Ai=($R!=""&&$R==$D?"adminer_$D":$D);if(!$wc&&$R!="")$wc=idx(table_status1($R),"Engine");if(!queries("CREATE TABLE ".table($Ai)." (\n".implode(",\n",$Na)."\n)".($wc!="table"&&in_array($wc,driver()->engines())?" $wc":"")))return
false;if($R!=""){if($og&&!queries("INSERT INTO ".table($Ai)." (".implode(", ",$og).") SELECT ".implode(", ",array_map('Adminer\idf_escape',array_keys($og)))." FROM ".table($R)))return
false;$Yi=array();foreach(triggers($R)as$Wi=>$Gi){$Ui=trigger($Wi,$R);$Yi[]="CREATE TRIGGER ".idf_escape($Wi)." ".implode(" ",$Gi)." ON ".table($D)."\n$Ui[Statement]";}$za=$za?"":get_val("SELECT seq FROM sqlite_sequence WHERE name = ".q($R));if(!queries("DROP TABLE ".table($R))||($R==$D&&!queries("ALTER TABLE ".table($Ai)." RENAME TO ".table($D)))||!alter_indexes($D,$w))return
false;if($za)queries("UPDATE sqlite_sequence SET seq = $za WHERE name = ".q($D));foreach($Yi
as$Ui){if(!queries($Ui))return
false;}queries("COMMIT");}return
true;}function
index_sql($R,$U,$D,$e){return"CREATE $U ".($U!="INDEX"?"INDEX ":"").idf_escape($D!=""?$D:uniqid($R."_"))." ON ".table($R)." $e";}function
alter_indexes($R,$pa){foreach($pa
as$Wg){if($Wg[0]=="PRIMARY")return
recreate_table($R,$R,array(),array(),array(),"",$pa);}foreach(array_reverse($pa)as$X){if(!queries($X[2]=="DROP"?"DROP INDEX ".idf_escape($X[1]):index_sql($R,$X[0],$X[1],"(".implode(", ",$X[2]).")")))return
false;}return
true;}function
truncate_tables(array$T){return
apply_queries("DELETE FROM",$T);}function
drop_views(array$Cj){return
apply_queries("DROP VIEW",$Cj);}function
drop_tables(array$T){return
apply_queries("DROP TABLE",$T);}function
move_tables(array$T,array$Cj,$zi){return
false;}function
trigger($D,$R){if($D=="")return
array("Statement"=>"BEGIN\n\t;\nEND");$u='(?:[^`"\s]+|`[^`]*`|"[^"]*")+';$Xi=trigger_options();preg_match("~^CREATE\\s+TRIGGER\\s*$u\\s*(".implode("|",$Xi["Timing"]).")\\s+([a-z]+)(?:\\s+OF\\s+($u))?\\s+ON\\s*$u\\s*(?:FOR\\s+EACH\\s+ROW\\s)?(.*)~is",get_val("SELECT sql FROM sqlite_master WHERE type = 'trigger' AND name = ".q($D)),$B);$Mf=$B[3];return
array("Timing"=>strtoupper($B[1]),"Event"=>strtoupper($B[2]).($Mf?" OF":""),"Of"=>idf_unescape($Mf),"Trigger"=>$D,"Statement"=>$B[4],);}function
triggers($R){$K=array();$Xi=trigger_options();foreach(get_rows("SELECT * FROM sqlite_master WHERE type = 'trigger' AND tbl_pos_name = ".q($R))as$L){preg_match('~^CREATE\s+TRIGGER\s*(?:[^`"\s]+|`[^`]*`|"[^"]*")+\s*('.implode("|",$Xi["Timing"]).')\s*(.*?)\s+ON\b~i',$L["sql"],$B);$K[$L["name"]]=array($B[1],$B[2]);}return$K;}function
trigger_options(){return
array("Timing"=>array("BEFORE","AFTER","INSTEAD OF"),"Event"=>array("INSERT","UPDATE","UPDATE OF","DELETE"),"Type"=>array("FOR EACH ROW"),);}function
last_id($J){return
get_val("SELECT LAST_INSERT_ROWID()");}function
explain(Db$lb,$I){return$lb->query("EXPLAIN QUERY PLAN $I");}function
found_rows(array$S,array$Z){}function
types($Oc=false){return
array();}function
create_sql($R,$za,$ii){$K=get_val("SELECT sql FROM sqlite_master WHERE type IN ('table', 'view') AND name = ".q($R));foreach(indexes($R)as$D=>$v){if($D=='')continue;$K
.=";\n\n".index_sql($R,$v['type'],$D,"(".implode(", ",array_map('Adminer\idf_escape',$v['columns'])).")");}return$K;}function
truncate_sql($R){return"DELETE FROM ".table($R);}function
use_sql($Gb,$ii=""){return"";}function
trigger_sql($R){return
implode(get_vals("SELECT sql || ';;\n' FROM sqlite_master WHERE type = 'trigger' AND tbl_pos_name = ".q($R)));}function
show_variables(){$K=array();foreach(get_rows("PRAGMA pragma_list")as$L){$D=$L["name"];if($D!="pragma_list"&&$D!="compile_options"){$K[$D]=array($D,'');foreach(get_rows("PRAGMA $D")as$L)$K[$D][1].=implode(", ",$L)."\n";}}return$K;}function
show_status(){$K=array();foreach(get_vals("PRAGMA compile_options")as$bg)$K[]=explode("=",$bg,2)+array('','');return$K;}function
convert_field(array$k){}function
unconvert_field(array$k,$K){return$K;}function
support($Sc){return
preg_match('~^(check|columns|database|drop_col|dump|indexes|descidx|move_col|sql|status|table|transaction_ddl|trigger|variables|view|view_trigger)$~',$Sc);}class
Adminer{static$instance;var$error='';function
name(){return"<a href='https://www.adminer.org/'".target_blank()." id='h1'><img src='".h(preg_replace("~\\?.*~","",ME)."?file=logo.png&version=6.0.0")."' width='24' height='24' alt='' id='logo'>Adminer</a>";}function
credentials(){return
array(SERVER,$_GET["username"],get_password());}function
connectSsl(){}function
permanentLogin($vb=false){return
password_file($vb);}function
bruteForceKey(){return$_SERVER["REMOTE_ADDR"];}function
serverName($O){return
h($O);}function
database(){return
DB;}function
databases($fd=true){return
get_databases($fd);}function
pluginsLinks(){}function
operators(){return
driver()->operators;}function
schemas(){$K=schemas();if($_GET["ns"]!=""&&!in_array($_GET["ns"],$K))array_unshift($K,$_GET["ns"]);return$K;}function
queryTimeout(){return
2;}function
afterConnect(){}function
headers(){}function
csp(array$yb){return$yb;}function
verifyVersion(){return
true;}function
head($Cb=null){return
true;}function
bodyClass(){echo" adminer";}function
css(){$K=array();foreach(array("","-dark")as$zf){$n="adminer$zf.css";if(file_exists($n)){$m=file_get_contents($n);$K["$n?v=".crc32($m)]=($zf?"dark":(preg_match('~prefers-color-scheme:\s*dark~',$m)?'':'light'));}}return$K;}function
loginForm(){echo"<table class='layout'>\n",adminer()->loginFormField('driver','<tr><th>'.lang(29).'<td>',input_hidden("auth[driver]","sqlite")."SQLite"),input_hidden("auth[server]",SERVER),adminer()->loginFormField('username','<tr><th>'.lang(30).'<td>','<input name="auth[username]" id="username" autofocus value="'.h($_GET["username"]).'" autocomplete="username" autocapitalize="off">'),adminer()->loginFormField('password','<tr><th>'.lang(31).'<td>','<input type="password" name="auth[password]" autocomplete="current-password">'),adminer()->loginFormField('db','<tr><th>'.lang(32).'<td>','<input name="auth[db]" value="'.h($_GET["db"]).'" autocapitalize="off">'),"</table>\n","<p><input type='submit' value='".lang(33)."'>\n",checkbox("auth[permanent]",1,$_COOKIE["adminer_permanent"],lang(34))."\n";}function
loginFormField($D,$Gd,$Y){return$Gd.$Y."\n";}function
login($Ve,$Eg){if($Eg==""||!password_required())return
lang(35,target_blank());return
true;}function
tableName(array$qi){return
h($qi["Name"]);}function
fieldName(array$k,$F=0){$U=$k["full_type"].($k["null"]?" NULL":"");$eb=$k["comment"];return'<span title="'.h($U.($eb!=""?($U?": ":"").$eb:'')).'">'.h($k["field"]).'</span>';}function
commentValue($U,$eb){if($eb==""||$U=='TABLE'||$U=='COLUMN')return
h($eb);$Tg=function($_h){return
preg_replace('~^~m','<tr>',preg_replace('~\|~','<td>',preg_replace('~\|$~m',"",rtrim($_h))));};$R='(\+--[-+]+\+\n)';$L='(\| .* \|\n)';return"<pre>\n".preg_replace_callback("~^$R?$L$R?($L*)$R?~m",function($B)use($Tg){$dd=$Tg($B[2]);return"<table>\n".($B[1]?"<thead>$dd<tbody>\n":$dd).$Tg($B[4])."\n</table>";},preg_replace('~(\n(    -|mysql)&gt; )(.+)~',"\\1<code class='jush-sql'>\\3</code>",preg_replace('~(.+)\n---+\n~',"<b>\\1</b>\n",h($eb))))."</pre>\n";}function
commentInput($U,$b,$eb){$Y=h($eb);return(preg_match('~\n~',$Y)?"<textarea$b rows='2' cols='".($U=='TABLE'?20:30)."' style='vertical-align: bottom;'>\n$Y</textarea>":"<input$b value='$Y'>");}function
selectLinks(array$qi,$P=""){$D=$qi["Name"];echo'<p class="links">';$Re=array("select"=>lang(36));if(support("table")||support("indexes"))$Re["table"]=lang(37);$te=false;if(support("table")){$te=is_view($qi);if($te){if(support("view"))$Re["view"]=lang(38);}elseif(function_exists('Adminer\alter_table')&&$D!="")$Re["create"]=lang(39);}if($P!==null)$Re["edit"]=lang(40);foreach($Re
as$y=>$X)echo" <a href='".h(ME)."$y=".url_escape($D).($y=="edit"?$P:"")."'".bold(isset($_GET[$y])).">$X</a>";echo
doc_link(array(JUSH=>driver()->tableHelp($D,$te)),"?"),"\n";}function
foreignKeys($R){return
foreign_keys($R);}function
backwardKeys($R,$pi){return
array();}function
backwardKeysPrint(array$Ca,array$L){}function
selectQuery($I,$di,$Qc=false){$K="\n";if(!$Qc&&($Fj=driver()->warnings())){$t="warnings";$K=", <a href='#$t' class='toggle'>".lang(41)."</a>"."$K<div id='$t' class='hidden'>\n$Fj</div>\n";}return"<p><code class='jush-".JUSH."'>".h(str_replace("\n"," ",$I))."</code> <span class='time'>(".format_time($di).")</span>".(support("sql")?" <a href='".h(ME)."sql=".url_escape($I)."' class='hover'>".lang(13)."</a>":"").$K;}function
sqlCommandQuery($I){return
shorten_utf8(trim($I),1000);}function
sqlPrintAfter(){}function
rowDescription($R){return"";}function
rowDescriptions(array$M,array$id){return$M;}function
selectLink($X,array$k){}function
selectVal($X,$_,array$k,$ng){$K=($X===null?"<i>NULL</i>":(preg_match("~char|binary|boolean~",$k["type"])&&!preg_match("~var~",$k["type"])?"<code>$X</code>":(preg_match('~^jsonb?$~',$k["full_type"])?"<code class='jush-json'>$X</code>":$X)));if(is_blob($k)&&!is_utf8($X))$K="<i>".lang(42,strlen($ng))."</i>";return($_?"<a href='".h($_)."'".(is_url($_)?target_blank():"").">$K</a>":$K);}function
editVal($X,array$k){return$X;}function
config(){return
array();}function
tableStructurePrint(array$l,$qi=null){echo"<div class='scrollable'>\n","<table class='nowrap odds'>\n","<thead><tr><th>".lang(43)."<td>".lang(44).(support("comment")?"<td>".lang(45):"")."<tbody>\n";$hi=driver()->structuredTypes();foreach($l
as$k){echo"<tr><th>".h($k["field"]);$U=h($k["full_type"]);$ab=h($k["collation"]);echo"<td><span title='$ab'>".(in_array($U,(array)$hi[lang(7)])?"<a href='".h(ME.'type='.url_escape($U))."'>$U</a>":$U.($ab&&isset($qi["Collation"])&&$ab!=$qi["Collation"]?" $ab":""))."</span>",($k["null"]?" <i>NULL</i>":""),($k["auto_increment"]?" <i>".lang(46)."</i>":""),(isset($k["default"])?" <span title='".lang(47)."'>[<b>".($k["generated"]?"<code class='jush-".JUSH."'>".shorten_utf8(preg_replace('~\s+~',' ',ltrim($k["default"])),80,"</code>"):h($k["default"]))."</b>]</span>":""),(support("comment")?"<td>".adminer()->commentValue('COLUMN',$k["comment"]):""),"\n";}echo"</table>\n","</div>\n";}function
tableIndexesPrint(array$w,array$qi){$yg=false;foreach($w
as$D=>$v)$yg|=!!$v["partial"];echo"<table>\n";$Lb=first(driver()->indexAlgorithms($qi));foreach($w
as$D=>$v){ksort($v["columns"]);$Yg=array();foreach($v["columns"]as$y=>$X)$Yg[]="<i>".h($X)."</i>".($v["lengths"][$y]?"(".h($v["lengths"][$y]).")":"").($v["descs"][$y]?" DESC":"");echo"<tr title='".h($D)."'>","<th>".h($v["type"]).($Lb&&$v['algorithm']!=$Lb?" (".h($v['algorithm']).")":""),"<td>".implode(", ",$Yg);if($yg)echo"<td>".($v['partial']?"<code class='jush-".JUSH."'>WHERE ".h($v['partial']):"");echo"\n";}echo"</table>\n";}function
selectColumnsPrint(array$N,array$e){print_fieldset("select",lang(48),$N);$s=0;$N[""]=array();foreach($N
as$y=>$X){$X=idx($_GET["columns"],$y,array());$d=select_input(" name='columns[$s][col]' data-default=''".on('change',($y!==""?'selectFieldChange':'selectAddRow')),$e,$X["col"]);echo"<div>".(driver()->functions||driver()->grouping?html_select("columns[$s][fun]",array(-1=>"")+array_filter(array(lang(49)=>driver()->functions,lang(50)=>driver()->grouping)),$X["fun"]," data-default=''".on('change',($y!==""?'helpClose':'selectFunAddRow')).on_help_value(' (.*)|$','($1)'))."($d)":$d)."</div>\n";$s++;}echo"</div></fieldset>\n";}function
selectSearchPrint(array$Z,array$e,array$w){print_fieldset("search",lang(51),$Z);foreach($w
as$s=>$v){if($v["type"]=="FULLTEXT")echo"<div>(<i>".implode("</i>, <i>",array_map('Adminer\h',$v["columns"]))."</i>) AGAINST"," <input type='search' name='fulltext[$s]' value='".h(idx($_GET["fulltext"],$s))."' data-default=''".on('input','selectFieldChange').">",(JUSH=='sql'?checkbox("boolean[$s]",1,isset($_GET["boolean"][$s]),"BOOL"):''),"</div>\n";}$Zf=adminer()->operators();foreach(array_merge((array)$_GET["where"],array(array()))as$s=>$X){if(!$X||("$X[col]$X[val]"!=""&&in_array($X["op"],$Zf)))echo"<div>".select_input(" name='where[$s][col]' data-default=''".on('change',($X?'selectFieldChange':'selectAddRow')),$e,$X["col"],"(".lang(52).")"),html_select("where[$s][op]",$Zf,$X["op"]," data-default='".h(first($Zf))."'".on('change','selectFirstChange')),"<input type='search' name='where[$s][val]' value='".h($X["val"])."' data-default=''".on('input','selectFirstChange').on('keydown','selectSearchKeydown').on('search','selectSearchSearch').">","</div>\n";}echo"</div></fieldset>\n";}function
selectOrderPrint(array$F,array$e,array$w){print_fieldset("sort",lang(53),$F);$s=0;foreach((array)$_GET["order"]as$y=>$X){if($X!=""){echo"<div>".select_input(" name='order[$s]' data-default=''".on('change','selectFieldChange'),$e,$X),checkbox("desc[$s]",1,isset($_GET["desc"][$y]),lang(54))."</div>\n";$s++;}}echo"<div>".select_input(" name='order[$s]' data-default=''".on('change','selectAddRow'),$e),checkbox("desc[$s]",1,false,lang(54))."</div>\n","</div></fieldset>\n";}function
selectLimitPrint($z){echo"<fieldset><legend>".lang(55)."</legend><div>","<input type='number' name='limit' class='size' value='".h($z?:"")."' data-default='50'".on('input','selectFieldChange').">","</div></fieldset>\n";}function
selectLengthPrint($Di){echo"<fieldset><legend>".lang(56)."</legend><div>","<input type='number' name='text_length' class='size' value='".h($Di)."' data-default='100'>","</div></fieldset>\n";}function
selectActionPrint(array$w){echo"<fieldset><legend>".lang(57)."</legend><div>","<input type='submit' value='".lang(48)."'>"," <span id='noindex' title='".lang(58)."'></span>","<script".nonce().">\n","const indexColumns = ";$e=array();foreach($w
as$v){$Bb=reset($v["columns"]);if($v["type"]!="FULLTEXT"&&$Bb)$e[$Bb]=1;}$e[""]=1;foreach($e
as$y=>$X)json_row($y);echo";\n","selectFieldChange.call(qs('#form')['select']);\n","</script>\n","</div></fieldset>\n";}function
selectCommandPrint(){return!information_schema(DB);}function
selectImportPrint(){return!information_schema(DB);}function
selectEmailPrint(array$tc,array$e){}function
selectColumnsProcess(array$e,array$w){$N=array();$r=array();foreach((array)$_GET["columns"]as$y=>$X){if($X["fun"]=="count"||($X["col"]!=""&&(!$X["fun"]||in_array($X["fun"],driver()->functions)||in_array($X["fun"],driver()->grouping)))){$N[$y]=apply_sql_function($X["fun"],($X["col"]!=""?idf_escape($X["col"]):"*"));if(!in_array($X["fun"],driver()->grouping))$r[]=$N[$y];}}return
array($N,$r);}function
selectSearchProcess(array$l,array$w){$K=array();foreach($w
as$s=>$v){if($v["type"]=="FULLTEXT"&&idx($_GET["fulltext"],$s)!="")$K[]="MATCH (".implode(", ",array_map('Adminer\idf_escape',$v["columns"])).") AGAINST (".q($_GET["fulltext"][$s]).(isset($_GET["boolean"][$s])?" IN BOOLEAN MODE":"").")";}$Zf=adminer()->operators();foreach((array)$_GET["where"]as$y=>$X){$X+=array("col"=>"","op"=>first($Zf),"val"=>"");$_GET["where"][$y]=$X;$Ya=$X["col"];if("$Ya$X[val]"!=""&&in_array($X["op"],$Zf)){if($X["op"]=="SQL"&&(!$_POST||!verify_token()))SqlDb::$untrusted=true;$jb=array();foreach(($Ya!=""?array($Ya=>$l[$Ya]):$l)as$D=>$k){$Ug="";$ib=" $X[op]";if(preg_match('~IN$~',$X["op"])){$Vd=process_length($X["val"]);$ib
.=" ".($Vd!=""?$Vd:"(NULL)");}elseif($X["op"]=="SQL")$ib=" $X[val]";elseif(preg_match('~^(I?LIKE) %%$~',$X["op"],$B))$ib=" $B[1] ".adminer()->processInput($k,"%$X[val]%");elseif($X["op"]=="FIND_IN_SET"){$Ug="$X[op](".q($X["val"]).", ";$ib=")";}elseif(!preg_match('~NULL$~',$X["op"]))$ib
.=" ".adminer()->processInput($k,$X["val"]);if($Ya!=""||(isset($k["privileges"]["where"])&&(preg_match('~^[-\d.'.(preg_match('~IN$~',$X["op"])?',':'').']+$~',$X["val"])||!preg_match('~'.number_type().'|bit~',$k["type"]))&&(!preg_match("~[\x80-\xFF]~",$X["val"])||preg_match('~char|text|enum|set~',$k["type"]))&&(!preg_match('~date|timestamp~',$k["type"])||preg_match('~^\d+-\d+-\d+~',$X["val"]))))$jb[]=$Ug.driver()->convertSearch(idf_escape($D),$X,$k).$ib;}$K[]=(count($jb)==1?$jb[0]:($jb?"(".implode(" OR ",$jb).")":"1 = 0"));}}return$K;}function
selectOrderProcess(array$l,array$w){$K=array();foreach((array)$_GET["order"]as$y=>$X){if($X!="")$K[]=(preg_match('~^((COUNT\(DISTINCT |[A-Z0-9_]+\()(`(?:[^`]|``)+`|"(?:[^"]|"")+")\)|COUNT\(\*\))$~',$X)?$X:idf_escape($X)).(isset($_GET["desc"][$y])?" DESC".(JUSH=='pgsql'&&idx($l[$X],"null")?" NULLS LAST":""):"");}return$K;}function
selectLimitProcess(){return(isset($_GET["limit"])?intval($_GET["limit"]):50);}function
selectLengthProcess(){return(isset($_GET["text_length"])?"$_GET[text_length]":"100");}function
selectEmailProcess(array$Z,array$id){return
false;}function
selectQueryBuild(array$N,array$Z,array$r,array$F,$z,$G){return"";}function
messageQuery($I,$Ei,$Qc=false){restart_session();$Jd=&get_session("queries");if(!idx($Jd,$_GET["db"]))$Jd[$_GET["db"]]=array();if(strlen($I)>1e6)$I=preg_replace('~[\x80-\xFF]+$~','',substr($I,0,1e6))."\n…";$Jd[$_GET["db"]][]=array($I,time(),$Ei);$ai="sql-".count($Jd[$_GET["db"]]);$K="<a href='#$ai' class='toggle'>".lang(59)."</a> ".copy_icon()."\n";if(!$Qc&&($Fj=driver()->warnings())){$t="warnings-".count($Jd[$_GET["db"]]);$K="<a href='#$t' class='toggle'>".lang(41)."</a>, $K<div id='$t' class='hidden'>\n$Fj</div>\n";}return" <span class='time'>".@date("H:i:s")."</span>"." $K<div id='$ai' class='hidden'><pre><code class='jush-".JUSH."'>".shorten_utf8($I,1e4)."</code></pre>".($Ei?" <span class='time'>($Ei)</span>":'').(support("sql")?'<p><a href="'.h(str_replace("db=".url_escape(DB),"db=".url_escape($_GET["db"]),ME).'sql=&history='.(count($Jd[$_GET["db"]])-1)).'">'.lang(13).'</a>':'').'</div>';}function
editRowPrint($R,array$l,$L,$lj){}function
editFunctions(array$k){$K=($k["null"]?"NULL/":"");$Dd=isset($_GET["select"])||where($_GET);foreach(array(driver()->insertFunctions,driver()->editFunctions)as$y=>$rd){if(!$y||(!isset($_GET["call"])&&$Dd)){foreach($rd
as$Gg=>$X){if(!$Gg||preg_match("~$Gg~",$k["type"]))$K
.="/$X";}}if($y&&$rd&&!preg_match('~set|bool~',$k["type"])&&!is_blob($k))$K
.="/SQL";}if($k["auto_increment"]&&!$Dd)$K=lang(46);return
explode("/",$K);}function
editInput($R,array$k,$b,$Y){if($k["type"]=="enum")return(isset($_GET["select"])?"<label><input type='radio'$b value='orig' checked><i>".lang(11)."</i></label> ":"").enum_input("radio",$b,$k,$Y,"NULL");return"";}function
editHint($R,array$k,$Y){return"";}function
processInput(array$k,$Y,$q=""){if($q=="SQL")return$Y;$D=$k["field"];$K=q($Y);if(preg_match('~^(now|getdate|uuid)$~',$q))$K="$q()";elseif(preg_match('~^current_(date|timestamp)$~',$q))$K=$q;elseif(preg_match('~^([+-]|\|\|)$~',$q))$K=idf_escape($D)." $q $K";elseif(preg_match('~^[+-] interval$~',$q))$K=idf_escape($D)." $q ".(preg_match("~^(\\d+|'[0-9.: -]') [A-Z_]+\$~i",$Y)&&JUSH!="pgsql"?$Y:$K);elseif(preg_match('~^(addtime|subtime|concat)$~',$q))$K="$q(".idf_escape($D).", $K)";elseif(preg_match('~^(md5|sha1|password|encrypt)$~',$q))$K="$q($K)";return
unconvert_field($k,$K);}function
dumpOutput(){$K=array('text'=>lang(60),'file'=>lang(61));if(function_exists('gzencode'))$K['gz']='gzip';return$K;}function
dumpFormat(){return(support("dump")?array('sql'=>'SQL'):array())+array('csv'=>'CSV,','csv;'=>'CSV;','tsv'=>'TSV');}function
dumpDatabase($h){}function
dumpTable($R,$ii,$te=0){if($_POST["format"]!="sql"){echo"\xef\xbb\xbf";if($ii)dump_csv(array_keys(fields($R)));}else{if($te==2){$l=array();foreach(fields($R)as$D=>$k)$l[]=idf_escape($D)." $k[full_type]";$vb="CREATE TABLE ".table($R)." (".implode(", ",$l).")";}else$vb=create_sql($R,$_POST["auto_increment"],$ii);set_utf8mb4($vb);if($ii&&$vb){if(($ii=="DROP+CREATE"&&!function_exists('Adminer\drop_sql'))||$te==1)echo"DROP ".($te==2?"VIEW":"TABLE")." IF EXISTS ".table($R).";\n";if($te==1)$vb=remove_definer($vb);echo"$vb;\n\n";}}}function
dumpData($R,$ii,$I,array$N=array(),array$Z=array(),array$r=array(),array$F=array()){if($ii){$ef=(JUSH=="sqlite"?0:1048576);$l=array();$Rd=false;if($_POST["format"]=="sql"){if($ii=="TRUNCATE+INSERT"&&!function_exists('Adminer\truncate_all_sql'))echo
truncate_sql($R).";\n";$l=fields($R);if(JUSH=="mssql"){foreach($l
as$k){if($k["auto_increment"]){echo"SET IDENTITY_INSERT ".table($R)." ON;\n";$Rd=true;break;}}}}$J=($I!=""?connection()->query($I,1):driver()->select($R,($N?:array("*")),$Z,$r,$F,0));if($J){$je="";$La="";$ze=array();$sd=array();$ki="";$Tc=($R!=''?'fetch_assoc':'fetch_row');$ub=0;while($L=$J->$Tc()){if(!$ze){$yj=array();foreach($L
as$X){$k=$J->fetch_field();if(idx($l[$k->name],'generated')){$sd[$k->name]=true;continue;}$ze[]=$k->name;$y=idf_escape($k->name);$yj[]="$y = VALUES($y)";}$ki=($ii=="INSERT+UPDATE"?"\nON DUPLICATE KEY UPDATE ".implode(", ",$yj):"").";\n";}if($_POST["format"]!="sql"){if($ii=="table"){dump_csv($ze);$ii="INSERT";}dump_csv($L);}else{if(!$je)$je="INSERT INTO ".table($R)." (".implode(", ",array_map('Adminer\idf_escape',$ze)).") VALUES";foreach($L
as$y=>$X){if($sd[$y]){unset($L[$y]);continue;}$k=$l[$y];$L[$y]=($X===null?"NULL":($X===false?0:unconvert_field($k,preg_match(number_type(),$k["type"])&&!preg_match('~\[~',$k["full_type"])&&is_numeric($X)?$X:(!is_blob($k)||is_utf8($X)?q($X):driver()->quoteBinary($X)))));}$_h=($ef?"\n":" ")."(".implode(",\t",$L).")";if(!$La)$La=$je.$_h;elseif(JUSH=='mssql'?$ub%1000!=0:strlen($La)+4+strlen($_h)+strlen($ki)<$ef)$La
.=",$_h";else{echo$La.$ki;$La=$je.$_h;}}$ub++;}if($La)echo$La.$ki;}elseif($_POST["format"]=="sql")echo"-- ".str_replace("\n"," ",connection()->error)."\n";if($Rd)echo"SET IDENTITY_INSERT ".table($R)." OFF;\n";}}function
dumpFilename($Qd){return
friendly_url($Qd!=""?$Qd:(SERVER?:"localhost"));}function
dumpHeaders($Qd,$Af=false){$rg=$_POST["output"];$Mc=(preg_match('~sql~',$_POST["format"])?"sql":($Af?"tar":"csv"));header("Content-Type: ".($rg=="gz"?"application/x-gzip":($Mc=="tar"?"application/x-tar":($Mc=="sql"||$rg!="file"?"text/plain":"text/csv")."; charset=utf-8")));if($rg=="gz"){ob_start(function($Q){return
gzencode($Q);},1e6);}return$Mc;}function
dumpFooter(){if($_POST["format"]=="sql")echo"-- ".gmdate("Y-m-d H:i:s e")."\n";}function
importServerPath(){return"adminer.sql";}function
importPrint(){}function
importProcess(){return
false;}function
homepage(){echo'<p class="links">'.($_GET["ns"]==""&&support("database")?'<a href="'.h(ME).'database=">'.lang(62)."</a>\n":""),(support("scheme")?"<a href='".h(ME)."scheme='>".($_GET["ns"]!=""?lang(63):lang(64))."</a>\n":""),($_GET["ns"]!==""?'<a href="'.h(ME).'schema=">'.lang(65)."</a>\n":""),(support("privileges")?"<a href='".h(ME)."privileges='>".lang(66)."</a>\n":"");if($_GET["ns"]!=="")echo(support("routine")?"<a href='#routines'>".lang(67)."</a>\n":""),(support("sequence")?"<a href='#sequences'>".lang(68)."</a>\n":""),(support("type")?"<a href='#user-types'>".lang(7)."</a>\n":""),(support("event")?"<a href='#events'>".lang(69)."</a>\n":"");return
true;}function
navigation($yf){echo"<h1>".adminer()->name()." <span class='version'>".VERSION;$Gf=$_COOKIE["adminer_version"];echo" <a href='https://www.adminer.org/#download'".target_blank()." id='version'>".(version_compare(VERSION,$Gf)<0?h($Gf):"").version_iframe()."</a>","</span></h1>\n";switch_lang();if($yf=="auth"){$rg="";foreach((array)$_SESSION["pwds"]as$_j=>$Lh){foreach($Lh
as$O=>$uj){$D=h(get_setting("vendor-$_j-$O")?:get_driver($_j));foreach($uj
as$V=>$Eg){if($D&&$Eg!==null){$Jb=$_SESSION["db"][$_j][$O][$V];foreach(($Jb?array_keys($Jb):array(""))as$h)$rg
.="<li><a href='".h(auth_url($_j,$O,$V,$h))."'>($D) ".h("$V@").($O!=""?adminer()->serverName($O):"").h($h!=""?" - $h":"")."</a>\n";}}}}if($rg)echo"<ul id='logins'".on('mouseover','menuOver').on('mouseout','menuOut').">\n$rg</ul>\n";}else{$T=array();if($_GET["ns"]!==""&&!$yf&&DB!=""){connection()->select_db(DB);$T=table_status('',true);}adminer()->syntaxHighlighting($T);adminer()->databasesPrint($yf);$fa=array();if(DB==""||!$yf){if(support("sql")){$fa['sql']="<a href='".h(ME)."sql='".bold(isset($_GET["sql"])&&!isset($_GET["import"])).">".lang(59)."</a>";$fa['import']="<a href='".h(ME)."import='".bold(isset($_GET["import"])).">".lang(70)."</a>";}$fa['dump']="<a href='".h(ME)."dump=".url_escape(isset($_GET["table"])?$_GET["table"]:$_GET["select"])."' id='dump'".bold(isset($_GET["dump"])).">".lang(71)."</a>";}$Wd=$_GET["ns"]!==""&&!$yf&&DB!="";if($Wd&&function_exists('Adminer\alter_table'))$fa['create']='<a href="'.h(ME).'create="'.bold($_GET["create"]==="").">".lang(72)."</a>";$fa=adminer()->menuActions($fa,$yf);echo($fa?"<p class='links'>\n".implode("\n",$fa)."\n":"");if($Wd){if($T)adminer()->tablesPrint($T);else
echo"<p class='message'>".lang(12)."</p>\n";}}}function
syntaxHighlighting(array$T){echo
script_src(preg_replace("~\\?.*~","",ME)."?file=jush.js&version=6.0.0",true);if(support("sql")){$we="adminer-plugins/jush-".JUSH.".js";echo(file_exists($we)?script_src($we,true):""),"<script".nonce().">\n";if($T){$Re=array();foreach($T
as$R=>$U)$Re[]=js_escape_re($R);echo"var jushLinks = { ".JUSH.":";json_row(js_escape(ME).(support("table")?"table":"select").'=$&','/\b(?<!\$)('.implode('|',$Re).')(?!\$)\b/g',false);$bi=array("sql","check","event","procedure","trigger","view","type","table","processlist");json_row('');echo"};\n";foreach(array("bac","bra","sqlite_quo","mssql_bra")as$X)echo"jushLinks.$X = jushLinks.".JUSH.";\n";if(isset($_GET["sql"])||isset($_GET["trigger"])||isset($_GET["check"])){$vi=array_fill_keys(array_keys($T),array());foreach(driver()->allFields()as$R=>$l){foreach($l
as$k)$vi[$R][]=$k["field"];}echo"addEventListener('DOMContentLoaded', () => { autocompleter = jush.autocompleteSql('".idf_escape("")."', ".json_encode($vi)."); });\n";}}echo"</script>\n";}echo
script("syntaxHighlighting('".(preg_match('~^\d\.?\d~',connection()->server_info,$B)?$B[0]:"")."', '".connection()->flavor."');");}function
databasesPrint($yf){$g=adminer()->databases();if(DB&&$g&&!in_array(DB,$g))array_unshift($g,DB);echo"<form action=''>\n<p id='dbs'>\n";hidden_fields_get();$Hb=on('mousedown','dbMouseDown').on('change','dbChange');echo"<label title='".lang(32)."'>".lang(73).": ".($g?html_select("db",array(""=>"")+$g,DB,$Hb):"<input name='db' value='".h(DB)."' autocapitalize='off' size='19'>\n")."</label>","<input type='submit' value='".lang(24)."'".($g?" class='hidden'":"").">\n";foreach(array("import","sql","schema","dump","privileges")as$X){if(isset($_GET[$X])){echo
input_hidden($X);break;}}echo"</p></form>\n";}function
menuActions(array$fa,$yf){return$fa;}function
tablesPrint(array$T){echo"<ul id='tables'".on('mouseover','menuOver').on('mouseout','menuOut').">";foreach($T
as$R=>$ei){$R="$R";$D=adminer()->tableName($ei);if($D!=""&&!$ei["partition"])echo'<li><a href="'.h(ME).'select='.url_escape($R).'"'.bold($_GET["select"]==$R||$_GET["edit"]==$R,"select hover")." title='".lang(36)."'>".lang(74)."</a> ",(support("table")||support("indexes")?'<a href="'.h(ME).'table='.url_escape($R).'"'.bold(in_array($R,array($_GET["table"],$_GET["create"],$_GET["indexes"],$_GET["foreign"],$_GET["trigger"],$_GET["check"],$_GET["view"])),(is_view($ei)?"view":"structure"))." title='".lang(37)."'>$D</a>":"<span>$D</span>")."\n";}echo"</ul>\n";}function
showVariables(){return
show_variables();}function
showStatus(){return
show_status();}function
processList(){return
process_list();}function
killProcess($t){return
kill_process($t);}}class
Plugins{private
static$append=array('dumpFormat'=>true,'dumpOutput'=>true,'editRowPrint'=>true,'editFunctions'=>true,'config'=>true);var$plugins;var$drivers=array();var$driverFiles=array();var$error='';private$hooks=array();function
__construct($Mg){$hc=SqlDriver::$drivers;$Hd=" href='https://www.adminer.org/plugins/#use'".target_blank();if($Mg===null){$Mg=array();$Ga="adminer-plugins";if(is_dir($Ga)){foreach(glob("$Ga/*.php")as$n){$Xc=SqlDriver::$drivers;$this->includeOnce($n);foreach(array_diff_key(SqlDriver::$drivers,$Xc)as$t=>$D)$this->driverFiles[$t]=$n;}}if(file_exists("$Ga.php")){$Yd=$this->includeOnce("$Ga.php");if(is_array($Yd)){foreach($Yd
as$y=>$Kg)$Mg[is_object($Kg)?get_class($Kg):$y]=$Kg;}else$this->error
.=lang(75,"<b>$Ga.php</b>",$Hd)."<br>";}foreach(get_declared_classes()as$Wa){if(!$Mg[$Wa]&&(preg_match('~^Adminer\w~i',$Wa)||is_subclass_of($Wa,'Adminer\Plugin'))){$oh=new
\ReflectionClass($Wa);$mb=$oh->getConstructor();if($mb&&$mb->getNumberOfRequiredParameters())$this->error
.=lang(76,$Hd,"<b>$Wa</b>","<b>$Ga.php</b>")."<br>";else$Mg[$Wa]=new$Wa;}}}$me=array_filter($Mg,function($Kg){return!is_object($Kg);});if($me){$this->error
.=lang(77,$Hd)."<br>";$Mg=array_diff_key($Mg,$me);}$this->drivers=array_diff_key(SqlDriver::$drivers,$hc);$this->plugins=$Mg;$ha=new
Adminer;$Mg[]=$ha;$oh=new
\ReflectionObject($ha);foreach($oh->getMethods()as$wf){foreach($Mg
as$Kg){$D=$wf->getName();if(method_exists($Kg,$D))$this->hooks[$D][]=$Kg;}}}function
includeOnce($n){return
include_once"./$n";}static
function
checksum($n){$m=str_replace("\r","",file_get_contents($n));$m=preg_replace('~\n\tprotected \$translations = array\(.*?\n\t\);~s','',$m);return
dechex(crc32($m));}function
checksums(){$Yc=array_values($this->driverFiles);foreach($this->plugins
as$Kg){$oh=new
\ReflectionObject($Kg);$Yc[]=$oh->getFileName();}$K=array();foreach($Yc
as$n)$K[basename($n,'.php')]=self::checksum($n);return$K;}static
function
officialChecksums(){return
array('adminer.js'=>'a0599090','backward-keys'=>'afce3b7d','before-unload'=>'48618ca0','config'=>'f49cc617','dark-switcher'=>'3d490dea','database-hide'=>'90c6c0dc','designs'=>'56f1c186','dump-alter'=>'d078b2db','dump-bz2'=>'f0d0e336','dump-date'=>'adc7f1c7','dump-json'=>'767dd321','dump-xml'=>'9f039895','dump-zip'=>'93817d96','edit-foreign'=>'8c874a58','edit-textarea'=>'a24c3cc','editor-setup'=>'a7dc3a37','editor-views'=>'5c12b185','enum-option'=>'a2563959','file-upload'=>'235eaa7a','foreign-system'=>'ebb4c654','frames'=>'b0e1d11a','highlight-codemirror'=>'f1a34275','highlight-monaco'=>'6a92cc58','highlight-prism'=>'4c12cf3','import-csv'=>'1d174088','login-ip'=>'b4766b62','login-otp'=>'62c517c0','login-passkey'=>'f69f2f06','login-password-less'=>'97c37010','login-reverse-proxy'=>'7bb63f11','login-servers'=>'f9ac2f28','login-ssl'=>'6ed147bc','login-table'=>'7b15c3cd','menu-links'=>'f1f86a60','remote-color'=>'33a766c2','row-numbers'=>'eec8698c','select-email'=>'ead22272','select-image'=>'f55c0231','slugify'=>'4d5adde6','sql-gemini'=>'fabc3537','sql-log'=>'b4355039','table-indexes-structure'=>'a90cc0c9','table-structure'=>'a8458e02','tables-filter'=>'f8f51976','timeout'=>'90597366','version-github'=>'497af47b','version-noverify'=>'966937e9','clickhouse'=>'5bb80dfb','elastic'=>'f7017c4','firebird'=>'5499d1a','igdb'=>'170d083','imap'=>'ac143217','mongo'=>'c3b8f5a4','redis'=>'12f1a73b','simpledb'=>'79488f8b',);}function
__call($D,array$wg){$ta=array();foreach($wg
as$y=>$X)$ta[]=&$wg[$y];$K=null;foreach($this->hooks[$D]as$Kg){$Y=call_user_func_array(array($Kg,$D),$ta);if($Y!==null){if(!self::$append[$D])return$Y;$K=$Y+(array)$K;}}return$K;}}abstract
class
Plugin{protected$translations=array();function
description(){return$this->lang('');}function
screenshot(){return"";}protected
function
lang($u,$E=null){$ta=func_get_args();$ta[0]=idx($this->translations[LANG],$u)?:$u;return
call_user_func_array('Adminer\lang_format',$ta);}}Adminer::$instance=(function_exists('adminer_object')?adminer_object():(is_dir("adminer-plugins")||file_exists("adminer-plugins.php")?new
Plugins(null):new
Adminer));define('Adminer\JUSH',Driver::$jush);define('Adminer\SERVER',"".$_GET[DRIVER]);define('Adminer\DB',"$_GET[db]");define('Adminer\ME',preg_replace('~\?.*~','',relative_uri()).'?'.(sid()?SID.'&':'').($_GET["ext"]?"ext=".url_escape($_GET["ext"]).'&':'').(isset($_GET[DRIVER])?DRIVER."=".url_escape(SERVER).'&':'').(isset($_GET["username"])?"username=".url_escape($_GET["username"]).'&':'').(DB!=""?'db='.url_escape(DB).'&'.(isset($_GET["ns"])?"ns=".url_escape($_GET["ns"])."&":""):''));function
page_header($Hi,$j="",$Ka=array(),$Ii=""){page_headers();if(is_ajax()&&$j){page_messages($j);exit;}if(!ob_get_level())ob_start('ob_gzhandler',4096);$Ji=$Hi.($Ii!=""?": $Ii":"");$Ki=strip_tags($Ji.(SERVER!=""&&SERVER!="localhost"?h(" - ".SERVER):"")." - ".adminer()->name());echo'<!DOCTYPE html>
<html lang=\'',LANG,'\' dir=\'',lang(78),'\' class=\'',lang(78),' nojs\'>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="robots" content="noindex">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>',$Ki,'</title>
<link rel="stylesheet" href="',h(preg_replace("~\\?.*~","",ME)."?file=default.css&version=6.0.0"),'">
';$zb=adminer()->css();if(is_int(key($zb)))$zb=array_fill_keys($zb,'light');$Bd=in_array('light',$zb)||in_array('',$zb);$_d=in_array('dark',$zb)||in_array('',$zb);$Cb=($Bd?($_d?null:false):($_d?:null));$mf=" media='(prefers-color-scheme: dark)'";if($Cb!==false)echo"<link rel='stylesheet'".($Cb?"":$mf)." href='".h(preg_replace("~\\?.*~","",ME)."?file=dark.css&version=6.0.0")."'>\n";echo"<meta name='color-scheme' content='".($Cb===null?"light dark":($Cb?"dark":"light"))."'>\n",script_src(preg_replace("~\\?.*~","",ME)."?file=functions.js&version=6.0.0");if(adminer()->head($Cb))echo"<link rel='icon' href='data:image/gif;base64,"."R0lGODlhEAAQAJEAAAQCBPz+/PwCBAROZCH5BAEAAAAALAAAAAAQABAAAAI2hI+pGO1rmghihiUdvUBnZ3XBQA7f05mOak1RWXrNq5nQWHMKvuoJ37BhVEEfYxQzHjWQ5qIAADs='>\n","<link rel='apple-touch-icon' href='".h(preg_replace("~\\?.*~","",ME)."?file=logo.png&version=6.0.0")."'>\n";foreach($zb
as$oj=>$zf){$b=($zf=='dark'&&!$Cb?$mf:($zf=='light'&&$_d?" media='(prefers-color-scheme: light)'":""));echo"<link rel='stylesheet'$b href='".h($oj)."'>\n";}echo"\n<body class='";adminer()->bodyClass();echo"'>\n",script((isset($_COOKIE["adminer_version"])||!adminer()->verifyVersion()?"":"onload = partial(verifyVersion, '".VERSION."');\n")."
const offlineMessage = '".js_escape(lang(79))."';
const thousandsSeparator = '".js_escape(lang(5))."';
const urlSeparators = '".js_escape(ini_get("arg_separator.input"))."';"),"<div id='help' class='jush-".JUSH." jsonly hidden'".on('mouseover','helpKeep').on('mouseout','helpMouseout')."></div>\n","<div id='content'>\n","<span id='menuopen' class='jsonly'".on('click','menuToggle')."><button title='".lang(80)."' class='icon icon-move' aria-expanded='false'></button></span>\n";if($Ka!==null){$_=substr(preg_replace('~\b(username|db|ns)=[^&]*&~','',ME),0,-1);echo'<p id="breadcrumb"><a href="'.h($_?:".").'">'.get_driver(DRIVER).'</a> » ';$_=substr(preg_replace('~\b(db|ns)=[^&]*&~','',ME),0,-1);$O=adminer()->serverName(SERVER);$O=($O!=""?$O:lang(81));if($Ka===false)echo"$O\n";else{echo"<a href='".h($_)."' accesskey='1' title='Alt+Shift+1'>$O</a> » ";if($_GET["ns"]!=""||(DB!=""&&is_array($Ka)))echo'<a href="'.h($_."&db=".url_escape(DB).(support("scheme")?"&ns=":"")).'">'.h(DB).'</a> » ';if(is_array($Ka)){if($_GET["ns"]!="")echo'<a href="'.h(substr(ME,0,-1)).'">'.h($_GET["ns"]).'</a> » ';foreach($Ka
as$y=>$X){$Qb=(is_array($X)?$X[1]:h($X));if($Qb!="")echo"<a href='".h(ME."$y=").url_escape(is_array($X)?$X[0]:$X)."'>$Qb</a> » ";}}echo"$Hi\n";}}echo"<h2>$Ji</h2>\n","<div id='ajaxstatus' role='status' class='jsonly'></div>\n";restart_session();page_messages($j);$g=&get_session("dbs");if(DB!=""&&$g&&!in_array(DB,$g,true))$g=null;stop_session();define('Adminer\PAGE_HEADER',1);ob_flush();flush();}function
page_headers(){header("Content-Type: text/html; charset=utf-8");header("Cache-Control: no-cache");header("X-Frame-Options: deny");header("X-XSS-Protection: 0");header("X-Content-Type-Options: nosniff");header("Referrer-Policy: origin-when-cross-origin");foreach(adminer()->csp(csp())as$yb){$Ed=array();foreach($yb
as$y=>$X)$Ed[]="$y $X";header("Content-Security-Policy: ".implode("; ",$Ed));}adminer()->headers();}function
csp(){return
array(array("script-src"=>"'self' 'unsafe-inline' 'nonce-".get_nonce()."' 'strict-dynamic'","connect-src"=>"'self' https://www.adminer.org","frame-src"=>"https://www.adminer.org","object-src"=>"'none'","base-uri"=>"'none'","form-action"=>"'self'",),);}function
design_checksums(){$tj=array();foreach(array_keys(adminer()->css())as$oj)$tj[preg_replace('~\?.*~','',$oj)]=true;$K=array();foreach(array("adminer.css","adminer-dark.css")as$n){if($tj[$n]&&file_exists($n)){preg_match('~^/\* Adminer design ([-\w]+) \*/~',file_get_contents($n),$B);$K[$n]=array((string)$B[1],Plugins::checksum($n));}}return$K;}function
official_design_checksums(){return
array('adminer-border/adminer-dark.css'=>'b2527e3','adminer-border/adminer.css'=>'430977ad','adminer-dark/adminer-dark.css'=>'a26bcd7b','brade/adminer.css'=>'be4161f0','bueltge/adminer.css'=>'1a8f00b4','dracula/adminer-dark.css'=>'cfaf61dd','esterka/adminer.css'=>'1f805f36','flat/adminer.css'=>'49a61af9','galkaev/adminer-dark.css'=>'16c46f94','haeckel/adminer.css'=>'147a3565','hever/adminer.css'=>'78b8cd43','konya/adminer.css'=>'3cc606c5','lavender-light/adminer.css'=>'bf03f5d7','lucas-sandery/adminer.css'=>'6596353','mancave/adminer-dark.css'=>'e1ac813d','mvt/adminer.css'=>'ebd3afdc','nette/adminer.css'=>'5ab360e7','ng9/adminer.css'=>'488583cf','nicu/adminer.css'=>'ecb9bd1e','pappu687/adminer.css'=>'b58d128c','paranoiq/adminer.css'=>'64d27e5','pepa-linha/adminer.css'=>'baf25f0','pokorny/adminer.css'=>'ee9eea6d','price/adminer.css'=>'b3c939b2','rmsoft/adminer.css'=>'391d54ad','rmsoft_blue-dark/adminer.css'=>'17714d77','rmsoft_blue/adminer.css'=>'c0f192ea','win98/adminer.css'=>'e82d63c3',);}function
version_iframe(){return(isset($_COOKIE["adminer_version"])||!adminer()->verifyVersion()?"":"<noscript><iframe sandbox src='https://www.adminer.org/version/?current=".VERSION."&amp;noscript=1'></iframe></noscript>");}function
get_nonce(){static$If;if(!$If)$If=base64_encode(rand_string());return$If;}function
page_messages($j){$nj=preg_replace('~^[^?]*~','',$_SERVER["REQUEST_URI"]);$sf=idx($_SESSION["messages"],$nj);if($sf){echo"<div class='message'>".implode("</div>\n<div class='message'>",$sf)."</div>".script("messagesPrint();");unset($_SESSION["messages"][$nj]);}if($j)echo"<div class='error'>$j</div>\n";if(adminer()->error)echo"<div class='error'>".adminer()->error."</div>\n";}function
page_footer($yf=""){echo"</div>\n\n<div id='foot' class='foot'>\n<div id='menu'>\n";adminer()->navigation($yf);echo"</div>\n";if($yf!="auth")echo'<form action="" method="post">
<p class="logout">
<span title="',lang(30),'">',h($_GET["username"])."\n",'</span>
<input type=\'submit\' name=\'logout\' value=\'',lang(82),'\' id=\'logout\'>
',input_token(),'</form>
';echo"</div>\n\n",script("setupSubmitHighlight(document);");}function
int32($Cf){while($Cf>=2147483648)$Cf-=4294967296;while($Cf<=-2147483649)$Cf+=4294967296;return(int)$Cf;}function
long2str(array$W,$Ej){$_h='';foreach($W
as$X)$_h
.=pack('V',$X);if($Ej)return
substr($_h,0,end($W));return$_h;}function
str2long($_h,$Ej){$W=array_values(unpack('V*',str_pad($_h,4*ceil(strlen($_h)/4),"\0")));if($Ej)$W[]=strlen($_h);return$W;}function
xxtea_mx($Mj,$Lj,$li,$xe){return
int32((($Mj>>5&0x7FFFFFF)^$Lj<<2)+(($Lj>>3&0x1FFFFFFF)^$Mj<<4))^int32(($li^$Lj)+($xe^$Mj));}function
encrypt_string($gi,$y){if($gi=="")return"";$y=array_values(unpack("V*",pack("H*",md5($y))));$W=str2long($gi,true);$Cf=count($W)-1;$Mj=$W[$Cf];$Lj=$W[0];$dh=floor(6+52/($Cf+1));$li=0;while($dh-->0){$li=int32($li+0x9E3779B9);$oc=$li>>2&3;for($sg=0;$sg<$Cf;$sg++){$Lj=$W[$sg+1];$Bf=xxtea_mx($Mj,$Lj,$li,$y[$sg&3^$oc]);$Mj=int32($W[$sg]+$Bf);$W[$sg]=$Mj;}$Lj=$W[0];$Bf=xxtea_mx($Mj,$Lj,$li,$y[$sg&3^$oc]);$Mj=int32($W[$Cf]+$Bf);$W[$Cf]=$Mj;}return
long2str($W,false);}function
decrypt_string($gi,$y){if($gi=="")return"";if(!$y)return
false;$y=array_values(unpack("V*",pack("H*",md5($y))));$W=str2long($gi,false);$Cf=count($W)-1;$Mj=$W[$Cf];$Lj=$W[0];$dh=floor(6+52/($Cf+1));$li=int32($dh*0x9E3779B9);while($li){$oc=$li>>2&3;for($sg=$Cf;$sg>0;$sg--){$Mj=$W[$sg-1];$Bf=xxtea_mx($Mj,$Lj,$li,$y[$sg&3^$oc]);$Lj=int32($W[$sg]-$Bf);$W[$sg]=$Lj;}$Mj=$W[$Cf];$Bf=xxtea_mx($Mj,$Lj,$li,$y[$sg&3^$oc]);$Lj=int32($W[0]-$Bf);$W[0]=$Lj;$li=int32($li-0x9E3779B9);}return
long2str($W,true);}$Ig=array();if($_COOKIE["adminer_permanent"]){foreach(explode(" ",$_COOKIE["adminer_permanent"])as$X){list($y)=explode(":",$X);$Ig[$y]=$X;}}function
add_invalid_login(){$Ea=get_temp_dir()."/adminer-invalid";foreach(glob("$Ea*")?:array($Ea)as$n){$p=file_open_lock($n);if($p)break;}if(!$p)$p=file_open_lock("$Ea-".rand_string());if(!$p)return;$ne=json_decode(stream_get_contents($p),true);$Ei=time();if($ne){foreach($ne
as$oe=>$X){if($X[0]<$Ei)unset($ne[$oe]);}}$me=&$ne[adminer()->bruteForceKey()];if(!$me)$me=array($Ei+30*60,0);$me[1]++;file_write_unlock($p,json_encode($ne));}function
check_invalid_login(array&$Ig){$ne=array();foreach(glob(get_temp_dir()."/adminer-invalid*")as$n){$p=file_open_lock($n);if($p){$ne=json_decode(stream_get_contents($p),true);file_unlock($p);break;}}$y=adminer()->bruteForceKey();$me=idx($ne,$y,array());$Hf=($me[1]>29?$me[0]-time():0);if($Hf>0){$j=lang(83,ceil($Hf/60));if($_SERVER["HTTP_X_FORWARDED_FOR"]!=""&&$y==$_SERVER["REMOTE_ADDR"])$j
.='<br>'.lang(84,'<b>login-reverse-proxy</b>'," href='https://www.adminer.org/plugins/?version=".VERSION."'".target_blank());auth_error($j,$Ig);}}function
password_required(){static$K;if($K===null){$K=(bool)get_session("password_required");if(!$K){$xb=adminer()->credentials();$K=!is_object(Driver::connect($xb[0],$xb[1],""));if($K)set_session("password_required",true);}}return$K;}$ya=$_POST["auth"];if($ya){session_regenerate_id();$_j=$ya["driver"];$O=$ya["server"];$V=$ya["username"];$Eg=(string)$ya["password"];$h=$ya["db"];set_password($_j,$O,$V,$Eg);$_SESSION["db"][$_j][$O][$V][$h]=true;if($ya["permanent"]){$y=implode("-",array_map('base64_encode',array($_j,$O,$V,$h)));$Zg=adminer()->permanentLogin(true);$Ig[$y]="$y:".base64_encode($Zg?encrypt_string($Eg,$Zg):"");cookie("adminer_permanent",implode(" ",$Ig));}if(count($_POST)==1||DRIVER!=$_j||SERVER!=$O||$_GET["username"]!==$V||DB!=$h)redirect(auth_url($_j,$O,$V,$h));}elseif($_POST["logout"]&&(!$_SESSION["token"]||verify_token())){foreach(array("pwds","db","dbs","queries")as$y)set_session($y,null);unset_permanent($Ig);redirect(substr(preg_replace('~\b(username|db|ns)=[^&]*&~','',ME),0,-1),lang(85).' '.lang(86));}elseif($Ig&&!$_SESSION["pwds"]){session_regenerate_id();$Zg=adminer()->permanentLogin();foreach($Ig
as$y=>$X){list(,$Va)=explode(":",$X);list($_j,$O,$V,$h)=array_map('base64_decode',explode("-",$y));set_password($_j,$O,$V,decrypt_string(base64_decode($Va),$Zg));$_SESSION["db"][$_j][$O][$V][$h]=true;}}function
unset_permanent(array&$Ig){foreach($Ig
as$y=>$X){list($_j,$O,$V,$h)=array_map('base64_decode',explode("-",$y));if($_j==DRIVER&&$O==SERVER&&$V==$_GET["username"]&&$h==DB)unset($Ig[$y]);}cookie("adminer_permanent",implode(" ",$Ig));}function
auth_error($j,array&$Ig){$Mh=session_name();if(isset($_GET["username"])){header("HTTP/1.1 403 Forbidden");if(($_COOKIE[$Mh]||$_GET[$Mh])&&!$_SESSION["token"])$j=lang(87);else{restart_session();add_invalid_login();$Eg=get_password();if($Eg!==null){if($Eg===false)$j
.=($j?'<br>':'').lang(88,target_blank(),'<code>permanentLogin()</code>');set_password(DRIVER,SERVER,$_GET["username"],null);}unset_permanent($Ig);}}if(!$_COOKIE[$Mh]&&$_GET[$Mh]&&ini_bool("session.use_only_cookies"))$j=lang(89);$wg=session_get_cookie_params();cookie("adminer_key",($_COOKIE["adminer_key"]?:rand_string()),$wg["lifetime"]);if(!$_SESSION["token"])$_SESSION["token"]=rand(1,1e6);page_header(lang(33),$j,null);echo"<form action='' method='post'>\n","<div>";if(hidden_fields($_POST,array("auth")))echo"<p class='message'>".lang(90)."\n";echo"</div>\n";adminer()->loginForm();echo"</form>\n";page_footer("auth");exit;}if(isset($_GET["username"])&&!class_exists('Adminer\Db')){unset($_SESSION["pwds"][DRIVER]);unset_permanent($Ig);page_header(lang(91),lang(92,implode(", ",Driver::$extensions)),false);page_footer("auth");exit;}$lb='';if(isset($_GET["username"])&&is_string(get_password())){list($Md,$Ng)=host_port(SERVER);if(preg_match('~[^-\w.:/]~',$Md.$Ng))auth_error(lang(93),$Ig);if(preg_match('~^-?\d+~',$Ng,$B)&&($B[0]<1024||$B[0]>65535))auth_error(lang(94),$Ig);check_invalid_login($Ig);$xb=adminer()->credentials();$lb=Driver::connect($xb[0],$xb[1],$xb[2]);if(is_object($lb)){Db::$instance=$lb;Driver::$instance=new
Driver($lb);if($lb->flavor)save_settings(array("vendor-".DRIVER."-".SERVER=>get_driver(DRIVER)));}}$Ve=null;if(!is_object($lb)||($Ve=adminer()->login($_GET["username"],get_password()))!==true){$j=(is_string($lb)?nl_br(h($lb)):(is_string($Ve)?$Ve:lang(95))).(preg_match('~^ | $~',get_password())?'<br>'.lang(96):'');auth_error($j,$Ig);}if($_POST["logout"]&&$_SESSION["token"]&&!verify_token()){page_header(lang(82),lang(97));page_footer("db");exit;}if(!$_SESSION["token"])$_SESSION["token"]=rand(1,1e6);stop_session(true);if($ya&&$_POST["token"])$_POST["token"]=get_token();$j='';if($_POST){if(!verify_token())$j=lang(97).' '.lang(98);}elseif($_SERVER["REQUEST_METHOD"]=="POST"){$j=lang(99,"<b>post_max_size</b>'");if(isset($_GET["sql"]))$j
.=' '.lang(100);}function
print_select_result($J,$f=null,array$hg=array(),&$z=0){$Re=array();$w=array();$e=array();$Ia=array();$cj=array();$K=array();for($s=0;(!$z||$s<$z)&&($L=$J->fetch_row());$s++){if(!$s){echo"<div class='scrollable'>\n","<table class='nowrap odds'>\n","<thead><tr>";for($x=0;$x<count($L);$x++){$k=$J->fetch_field();$D=$k->name;$gg=(isset($k->orgtable)?$k->orgtable:"");$fg=(isset($k->orgname)?$k->orgname:$D);if($hg&&JUSH=="sql")$Re[$x]=($D=="table"?"table=":($D=="possible_keys"?"indexes=":null));elseif($gg!=""){if(isset($k->table))$K[$k->table]=$gg;if(!isset($w[$gg])){$w[$gg]=array();foreach(indexes($gg,$f)as$v){if($v["type"]=="PRIMARY"){$w[$gg]=array_flip($v["columns"]);break;}}$e[$gg]=$w[$gg];}if(isset($e[$gg][$fg])){unset($e[$gg][$fg]);$w[$gg][$fg]=$x;$Re[$x]=$gg;}}if($k->charsetnr==63)$Ia[$x]=true;$cj[$x]=$k->type;echo"<th title='".h(trim(($gg!=""?"$gg.$fg":($k->name!=$fg?$fg:""))." ".driver()->typeName($k)))."'>".h($D).($hg?'':"");}echo"<tbody>\n";}echo"<tr>";foreach($L
as$y=>$X){$_="";if(isset($Re[$y])&&!$e[$Re[$y]]){if($hg&&JUSH=="sql"){$R=$L[array_search("table=",$Re)];$_=ME.$Re[$y].url_escape($hg[$R]!=""?$hg[$R]:$R);}else{$_=ME."edit=".url_escape($Re[$y]);foreach($w[$Re[$y]]as$Ya=>$x){if($L[$x]===null){$_="";break;}$_
.="&where[".url_escape(bracket_escape($Ya))."]=".url_escape($L[$x]);}}}$k=array('type'=>($Ia[$y]?'blob':($cj[$y]==254?'char':'')),);$X=select_value($X,$_,$k,null);echo"<td".($cj[$y]<=9||$cj[$y]==246?" class='number'":"").">$X";}}$z=$s;echo($s?"</table>\n</div>":"<p class='message'>".lang(15))."\n";return$K;}function
referencable_primary($Hh){$K=array();foreach(table_status('',true)as$ri=>$R){if($ri!=$Hh&&fk_support($R)){foreach(fields($ri)as$k){if($k["primary"]){if($K[$ri]){unset($K[$ri]);break;}$K[$ri]=$k;}}}}return$K;}function
textarea($D,$Y,$M=10,$cb=80){echo"<textarea name='".h($D)."' rows='$M' cols='$cb' class='sqlarea jush-".JUSH."' spellcheck='false' wrap='off'>";if(is_array($Y)){foreach($Y
as$X)echo
h($X[0])."\n\n\n";}else
echo
h($Y);echo"</textarea>";}function
select_input($b,array$cg,$Y="",$Jg=""){if($cg&&$Y!=""&&!isset($cg[$Y]))$cg=array($Y=>$Y)+$cg;$yi=($cg?"select":"input");return"<$yi$b".($cg?"><option value=''>$Jg".optionlist($cg,$Y,true)."</select>":" size='10' value='".h($Y)."' placeholder='$Jg'>");}function
json_row($y,$X=null,$Dc=true){static$cd=true;if($cd)echo"{";if($y!=""){echo($cd?"":",")."\n\t\"".addcslashes($y,"\r\n\t\"\\/").'": '.($X!==null?($Dc?'"'.addcslashes($X,"\r\n\"\\/").'"':$X):'null');$cd=false;}else{echo"\n}\n";$cd=true;}}function
edit_type($y,array$k,array$bb,array$jd=array(),array$Pc=array()){$U=(string)$k["type"];echo"<td><select name='".h($y)."[type]' class='type' aria-labelledby='label-type'".on_help_value().">";if($U&&!array_key_exists($U,driver()->types())&&!isset($jd[$U])&&!in_array($U,$Pc))$Pc[]=$U;$hi=driver()->structuredTypes();if($jd)$hi[lang(101)]=$jd;echo
optionlist(array_merge($Pc,$hi),$U),"</select><td>","<input name='".h($y)."[length]' value='".h($k["length"])."' size='3'".(!$k["length"]&&preg_match('~var(char|binary)$~',$U)?" class='required'":"")." aria-labelledby='label-length'>","<td class='options'>",($bb?"<input list='collations' name='".h($y)."[collation]'".option_types($U,'(char|text|enum|set)$')." value='".h($k["collation"])."' placeholder='(".lang(102).")'>":''),(driver()->unsigned?"<select name='".h($y)."[unsigned]'".option_types($U,'^$|'.number_type()).'><option>'.optionlist(driver()->unsigned,$k["unsigned"]).'</select>':''),(isset($k['on_update'])?"<select name='".h($y)."[on_update]'".option_types($U,'timestamp|datetime').'>'.optionlist(array(""=>"(".lang(103).")","CURRENT_TIMESTAMP"),(preg_match('~^CURRENT_TIMESTAMP~i',$k["on_update"])?"CURRENT_TIMESTAMP":$k["on_update"])).'</select>':''),($jd?"<select name='".h($y)."[on_delete]'".option_types($U,'`')."><option value=''>(".lang(104).")".optionlist(explode("|",driver()->onActions),$k["on_delete"])."</select> ":" ");}function
option_types($U,$cj){return" data-types='".h($cj)."'".(preg_match("~$cj~",$U)?"":" class='hidden'");}function
process_length($Le){$zc=driver()->enumLength;return(preg_match("~^\\s*\\(?\\s*$zc(?:\\s*,\\s*$zc)*+\\s*\\)?\\s*\$~",$Le)&&preg_match_all("~$zc~",$Le,$Ye)?"(".implode(",",$Ye[0]).")":preg_replace('~^[0-9].*~','(\0)',preg_replace('~[^-0-9,+()[\]]~','',$Le)));}function
process_type(array$k,$Za="COLLATE"){return" $k[type]".process_length($k["length"]).(preg_match(number_type(),$k["type"])&&in_array($k["unsigned"],driver()->unsigned)?" $k[unsigned]":"").(preg_match('~char|text|enum|set~',$k["type"])&&$k["collation"]?" $Za ".(JUSH=="mssql"?$k["collation"]:q($k["collation"])):"");}function
process_field(array$k,array$bj){if($k["on_update"])$k["on_update"]=str_ireplace("current_timestamp()","CURRENT_TIMESTAMP",$k["on_update"]);return
array(idf_escape(trim($k["field"])),process_type($bj),($k["null"]?" NULL":" NOT NULL"),default_value($k),(preg_match('~timestamp|datetime~',$k["type"])&&$k["on_update"]?" ON UPDATE $k[on_update]":""),(support("comment")&&$k["comment"]!=""?" COMMENT ".q($k["comment"]):""),($k["auto_increment"]?auto_increment():null),);}function
default_value(array$k){if($k["default"]===null)return"";$i=str_replace("\r","",$k["default"]);$sd=$k["generated"];return(in_array($sd,driver()->generated)?(JUSH=="mssql"?" AS ($i)".($sd=="VIRTUAL"?"":" $sd"):" GENERATED ALWAYS AS ($i) $sd"):(preg_match('~^GENERATED ~i',$i)?" $i":" DEFAULT ".(preg_match('~char|binary|text|json|enum|set|String~',$k["type"])||preg_match('~^(?![a-z])~i',$i)?(JUSH=="sql"&&preg_match('~text|json~',$k["type"])?"(".q($i).")":q($i)):str_ireplace("current_timestamp()","CURRENT_TIMESTAMP",(JUSH=="sqlite"?"($i)":$i)))));}function
type_class($U){foreach(array('char'=>'text','date'=>'time|year','binary'=>'blob','enum'=>'set',)as$y=>$X){if(preg_match("~$y|$X~",$U))return" class='$y'";}}function
edit_fields(array$l,array$bb,$U="TABLE",array$jd=array()){$l=array_values($l);$Mb=(($_POST?$_POST["defaults"]:get_setting("defaults"))?"":" class='hidden'");$fb=(($_POST?$_POST["comments"]:get_setting("comments"))?"":" class='hidden'");echo"<thead><tr>\n",($U=="PROCEDURE"?"<td>":""),"<th id='label-name'>".($U=="TABLE"?lang(105):lang(106)),"<td id='label-type'>".lang(44)."<textarea id='enum-edit' rows='4' cols='12' wrap='off' hidden></textarea>".script("qs('#enum-edit').onblur = editingLengthBlur;"),"<td id='label-length'>".lang(107),"<td>".lang(108);if($U=="TABLE")echo"<td id='label-null'>NULL\n","<td><input type='radio' name='auto_increment_col' value=''><abbr id='label-ai' title='".lang(46)."'>AI</abbr>",doc_link(array('sqlite'=>"autoinc.html",)),"<td id='label-default'$Mb>".lang(47),(support("comment")?"<td id='label-comment'$fb>".lang(45):"");$Fe=!support("move_col");echo"<td>".icon("plus","add[".($Fe?count($l):0)."]","+",lang(109),($Fe?on('click','editingAddLastRow'):"")),"<tbody".on('click','editingClick').on('input','editingInput').on('keydown','editingKeydown').">\n";foreach($l
as$s=>$k){$s++;$ig=$k[($_POST?"orig":"field")];$Xb=(isset($_POST["add"][$s-1])||(isset($k["field"])&&!idx($_POST["drop_col"],$s)))&&(support("drop_col")||$ig=="");echo"<tr".($Xb?"":" hidden").">\n",($U=="PROCEDURE"?"<td>".html_select("fields[$s][inout]",explode("|",driver()->inout),$k["inout"]):"")."<th>",(support("move_col")?icon("move","","↕",lang(110))." ":"");if($Xb)echo"<input name='fields[$s][field]' value='".h($k["field"])."' data-maxlength='64' autocapitalize='off' aria-labelledby='label-name'".(isset($_POST["add"][$s-1])?" autofocus":"").">";echo
input_hidden("fields[$s][orig]",$ig);edit_type("fields[$s]",$k,$bb,$jd);if($U=="TABLE"){echo"<td><label class='block'>".checkbox("fields[$s][null]",1,$k["null"],"","","","label-null")."</label>","<td><label class='block'><input type='radio' name='auto_increment_col' value='$s'".($k["auto_increment"]?" checked":"")." aria-labelledby='label-ai'></label>","<td$Mb>".(driver()->generated?html_select("fields[$s][generated]",array_merge(array("","DEFAULT"),driver()->generated),$k["generated"])." ":checkbox("fields[$s][generated]",1,$k["generated"],"","","","label-default"));$b=" name='fields[$s][default]' aria-labelledby='label-default'";$Y=h($k["default"]);echo(preg_match('~\n~',$k["default"])?"<textarea$b rows='2' cols='30' style='vertical-align: bottom;'>\n$Y</textarea>":"<input$b value='$Y'>");if(support("comment")){$b=" name='fields[$s][comment]' data-maxlength='".(min_version(5.5)?1024:255)."' aria-labelledby='label-comment'";echo"<td$fb>".adminer()->commentInput('COLUMN',$b,$k["comment"]);}}echo"<td>",(support("move_col")?icon("plus","add[$s]","+",lang(109))." ":""),($ig==""||support("drop_col")?icon("cross","drop_col[$s]","x",lang(111)):"");}}function
process_fields(array&$l){if($_POST["add"]){$l=array_values($l);array_splice($l,key($_POST["add"]),0,array(array()));}return$_POST["add"]||$_POST["drop_col"];}function
normalize_enum(array$B){$X=$B[0];return"'".str_replace("'","''",addcslashes(stripcslashes(str_replace($X[0].$X[0],$X[0],substr($X,1,-1))),'\\'))."'";}function
grant($td,array$ah,$e,$Tf){if(!$ah)return
true;if($ah==array("ALL PRIVILEGES","GRANT OPTION"))return($td=="GRANT"?queries("$td ALL PRIVILEGES$Tf WITH GRANT OPTION"):queries("$td ALL PRIVILEGES$Tf")&&queries("$td GRANT OPTION$Tf"));return
queries("$td ".preg_replace('~(GRANT OPTION)\([^)]*\)~','\1',implode("$e, ",$ah).$e).$Tf);}function
drop_create($ic,$vb,$kc,$Bi,$mc,$A,$rf,$pf,$qf,$Sf,$Ff){if($_POST["drop"])query_redirect($ic,$A,$rf);elseif($Sf=="")query_redirect($vb,$A,$qf);elseif(support("transaction_ddl")){driver()->begin();queries_redirect($A,$pf,queries($ic)&&queries($vb)&&driver()->commit());driver()->rollback();}elseif($Sf!=$Ff){$wb=queries($vb);queries_redirect($A,$pf,$wb&&queries($ic));if($wb)queries($kc);}else
queries_redirect($A,$pf,queries($Bi)&&queries($mc)&&queries($ic)&&queries($vb));}function
create_trigger($Tf,array$L){$Gi=" $L[Timing] $L[Event]".(preg_match('~ OF~',$L["Event"])?" $L[Of]":"");return"CREATE TRIGGER ".idf_escape($L["Trigger"]).(JUSH=="mssql"?$Tf.$Gi:$Gi.$Tf).rtrim(" $L[Type]\n$L[Statement]",";").";";}function
q_dollar($Q){$Pb='$$';while(strpos($Q.$Pb,$Pb)!=strlen($Q))$Pb='$_'.substr($Pb,1);return$Pb.$Q.$Pb;}function
create_routine($yh,array$L){$P=array();$l=(array)$L["fields"];ksort($l);foreach($l
as$k){if($k["field"]!="")$P[]=(preg_match("~^(".driver()->inout.")\$~",$k["inout"])?"$k[inout] ":"").idf_escape($k["field"]).process_type($k,"CHARACTER SET");}$Ob=rtrim($L["definition"],";");return"CREATE $yh ".idf_escape(trim($L["name"]))." (".implode(", ",$P).")".($yh=="FUNCTION"?" RETURNS".process_type($L["returns"],"CHARACTER SET"):"").($L["language"]?" LANGUAGE $L[language]":"").(JUSH=="pgsql"?" AS ".q_dollar("\n".trim($Ob)."\n"):"\n$Ob;");}function
remove_definer($I){return
preg_replace('~^([A-Z =]+) DEFINER=`'.preg_replace('~@(.*)~','`@`(%|\1)',logged_user()).'`~','\1',$I);}function
format_foreign_key(array$o){$h=$o["db"];$Jf=$o["ns"];return" FOREIGN KEY (".implode(", ",array_map('Adminer\idf_escape',$o["source"])).") REFERENCES ".($h!=""&&$h!=$_GET["db"]?idf_escape($h).".":"").($Jf!=""&&$Jf!=$_GET["ns"]?idf_escape($Jf).".":"").idf_escape($o["table"])." (".implode(", ",array_map('Adminer\idf_escape',$o["target"])).")".(preg_match("~^(".driver()->onActions.")\$~",$o["on_delete"])?" ON DELETE $o[on_delete]":"").(preg_match("~^(".driver()->onActions.")\$~",$o["on_update"])?" ON UPDATE $o[on_update]":"").($o["deferrable"]?" $o[deferrable]":"");}function
tar_file($n,$Li){$K=pack("a100a8a8a8a12a12",$n,644,0,0,decoct($Li->size),decoct(time()));$Ta=8*32;for($s=0;$s<strlen($K);$s++)$Ta+=ord($K[$s]);$K
.=sprintf("%06o",$Ta)."\0 ";echo$K,str_repeat("\0",512-strlen($K));$Li->send();echo
str_repeat("\0",511-($Li->size+511)%512);}function
doc_link(array$Fg,$Ci="<sup>?</sup>"){$Kh=connection()->server_info;$Aj=preg_replace('~^(\d\.?\d).*~s','\1',$Kh);$pj=array('sql'=>"https://dev.mysql.com/doc/refman/$Aj/en/",'sqlite'=>"https://www.sqlite.org/",'pgsql'=>"https://www.postgresql.org/docs/".(connection()->flavor=='cockroach'?"current":$Aj)."/",'mssql'=>"https://learn.microsoft.com/en-us/sql/",'oracle'=>"https://www.oracle.com/pls/topic/lookup?ctx=db".preg_replace('~^.* (\d+)\.(\d+)\.\d+\.\d+\.\d+.*~s','\1\2',$Kh)."&id=",);if(connection()->flavor=='maria'){$pj['sql']="https://mariadb.com/kb/en/";$Fg['sql']=(isset($Fg['mariadb'])?$Fg['mariadb']:str_replace(".html","/",$Fg['sql']));}return($Fg[JUSH]?"<a href='".h($pj[JUSH].$Fg[JUSH].(JUSH=='mssql'?"?view=sql-server-ver$Aj":""))."'".target_blank().">$Ci</a>":"");}function
db_size($h){if(!connection()->select_db($h))return"?";$K=0;foreach(table_status()as$S)$K+=$S["Data_length"]+$S["Index_length"];return
format_number($K);}function
set_utf8mb4($vb){static$P=false;if(!$P&&preg_match('~\butf8mb4~i',$vb)){$P=true;echo"SET NAMES ".charset(connection()).";\n\n";}}if(isset($_GET["status"]))$_GET["variables"]=$_GET["status"];if(isset($_GET["import"]))$_GET["sql"]=$_GET["import"];if(DB==""&&isset($_GET["ns"]))redirect(remove_from_uri('ns'));if(!(DB!=""?connection()->select_db(DB):isset($_GET["sql"])||isset($_GET["dump"])||isset($_GET["database"])||isset($_GET["processlist"])||isset($_GET["privileges"])||isset($_GET["user"])||isset($_GET["variables"])||$_GET["script"]=="connect"||$_GET["script"]=="kill")){if(DB!=""||$_GET["refresh"]){restart_session();set_session("dbs",null);}if(DB!=""){header("HTTP/1.1 404 Not Found");page_header(lang(32).": ".h(DB),lang(112),true);}else{if($_POST["db"]&&!$j)queries_redirect(substr(ME,0,-1),lang(113),drop_databases($_POST["db"]));page_header(lang(114),$j,false);echo"<p class='links'>\n";foreach(array('database'=>lang(115),'privileges'=>lang(66),'processlist'=>lang(116),'variables'=>lang(117),'status'=>lang(118),)as$y=>$X){if(support($y))echo"<a href='".h(ME)."$y='>$X</a>\n";}echo"<p>".lang(119,get_driver(DRIVER),"<b>".h(connection()->server_info)."</b>","<b>".connection()->extension."</b>")."\n","<p>".lang(120,"<b>".h(logged_user())."</b>")."\n";$g=adminer()->databases();if($g){$Bh=support("scheme");$bb=collations();echo"<form action='' method='post'>\n","<table class='checkable odds'".on('click','tableClick').on('dblclick','tableClick').">\n","<thead><tr>".(support("database")?"<td class='hover'>":"")."<th".(JUSH!='mssql'?" aria-sort='ascending'":"").">".lang(32).(get_session("dbs")!==null?" - <a href='".h(ME)."refresh=1'>".lang(121)."</a>":"")."<td>".lang(122)."<td>".lang(123)."<td>".lang(124)." - <a href='".h(ME)."dbsize=1'".on('click','ajaxSetHtml',ME."script=connect").">".lang(125)."</a>"."<tbody>\n";$g=($_GET["dbsize"]?count_tables($g):array_flip($g));foreach($g
as$h=>$T){$xh=h(ME)."db=".url_escape($h);$t=h("Db-".$h);echo"<tr>".(support("database")?"<td class='hover'>".checkbox("db[]",$h,in_array($h,(array)$_POST["db"]),"","","",$t):""),"<th><a href='$xh' id='$t'>".h($h)."</a>";$ab=h(db_collation($h,$bb));echo"<td>".(support("database")?"<a href='$xh".($Bh?"&amp;ns=":"")."&amp;database=' title='".lang(62)."'>$ab</a>":$ab),"<td align='right'><a href='$xh&amp;schema=' id='tables-".h($h)."' title='".lang(65)."'>".($_GET["dbsize"]?$T:"?")."</a>","<td align='right' id='size-".h($h)."'>".($_GET["dbsize"]?db_size($h):"?"),"\n";}echo"</table>\n",(support("database")?"<div class='footer'><div>\n"."<fieldset><legend>".lang(126)." <span id='selected'></span></legend><div>\n"."<input type='hidden' name='all' value=''".on('click','countDbs').">\n"."<input type='submit' name='drop' value='".lang(127)."'".confirm().">\n"."</div></fieldset>\n"."</div></div>\n":""),input_token(),"</form>\n",script("tableCheck();");}$ha=adminer();$Mg=($ha
instanceof
Plugins?$ha->plugins:array());$hc=($ha
instanceof
Plugins?$ha->drivers:array());$Ub=design_checksums();if($Mg||$hc||$Ub){$Ua=($ha
instanceof
Plugins?$ha->checksums():array());$Nf=Plugins::officialChecksums();$mj=function($oj){return" (<a href='$oj'".target_blank()." class='update'>".VERSION."</a>)";};$Lg=function($m)use($Ua,$Nf,$mj){return($Ua[$m]&&$Nf[$m]&&$Ua[$m]!==$Nf[$m]?$mj("https://www.adminer.org/plugins/?version=".VERSION):"");};echo"<div class='plugins'>\n","<h3>".lang(128)."</h3>\n<ul>\n";foreach($Mg
as$Kg){$oh=new
\ReflectionObject($Kg);$Rb=(method_exists($Kg,'description')?$Kg->description():"");if(!$Rb){if(preg_match('~^/[\s*]+(.+)~',$oh->getDocComment(),$B))$Rb=$B[1];}$Ch=(method_exists($Kg,'screenshot')?$Kg->screenshot():"");echo"<li><b>".get_class($Kg)."</b>".h($Rb?": $Rb":"").($Ch?" (<a href='".h($Ch)."'".target_blank().">".lang(129)."</a>)":"").$Lg(basename((string)$oh->getFileName(),'.php'))."\n";}foreach($hc
as$t=>$D)echo"<li><b>".h($t)."</b>: ".h($D).$Lg(basename((string)$ha->driverFiles[$t],'.php'))."\n";if($Ub){$Pf=official_design_checksums();foreach($Ub
as$n=>$Tb){list($D,$Ta)=$Tb;$Of=$Pf["$D/$n"];echo"<li><b>".h($n)."</b>".h($D?": $D":"").($Of&&$Of!==$Ta?$mj("https://www.adminer.org/?version=".VERSION."#extras"):"")."\n";}}echo"</ul>\n";adminer()->pluginsLinks();echo"</div>\n";}}page_footer("db");exit;}adminer()->afterConnect();class
TmpFile{private$handler;var$size=0;function
__construct(){$this->handler=tmpfile();}function
write($ob){$this->size+=strlen($ob);fwrite($this->handler,$ob);}function
send(){fseek($this->handler,0);fpassthru($this->handler);fclose($this->handler);}}if(isset($_GET["select"])&&($_POST["edit"]||$_POST["clone"])&&!$_POST["save"])$_GET["edit"]=$_GET["select"];if(isset($_GET["download"])){$a=$_GET["download"];$l=fields($a);header("Content-Type: application/octet-stream");header("Content-Disposition: attachment; filename=".friendly_url("$a-".implode("_",$_GET["where"])).".".friendly_url($_GET["field"]));$N=array(idf_escape($_GET["field"]));$J=driver()->select($a,$N,array(where($_GET,$l)),$N);$L=($J?$J->fetch_row():array());echo
driver()->value($L[0],$l[$_GET["field"]]);exit;}elseif(isset($_GET["table"])){$a=$_GET["table"];$l=fields($a);if(!$l)$j=error()?:lang(12);$S=table_status1($a);$D=adminer()->tableName($S);page_header(($l&&is_view($S)?$S['Engine']=='materialized view'?lang(130):lang(131):lang(132)).": ".($D!=""?$D:h($a)),$j);$wh=array();foreach($l
as$y=>$k)$wh+=$k["privileges"];adminer()->selectLinks($S,(isset($wh["insert"])||!support("table")?"":null));$eb=$S["Comment"];if($eb!="")echo"<p class='nowrap'>".lang(45).": ".adminer()->commentValue('TABLE',$eb)."\n";if($l)adminer()->tableStructurePrint($l,$S);function
tables_links(array$T){echo"<ul>\n";foreach($T
as$L){$_=preg_replace('~ns=[^&]*~',"ns=".url_escape($L["ns"]),ME);echo"<li><a href='".h($_."table=".url_escape($L["table"]))."'>".($L["ns"]!=$_GET["ns"]?"<b>".h($L["ns"])."</b>.":"").h($L["table"])."</a>";}echo"</ul>\n";}$fe=driver()->inheritsFrom($a);if($fe){echo"<h3>".lang(133)."</h3>\n";tables_links($fe);}if(support("indexes")&&driver()->supportsIndex($S)){echo"<div>\n","<h3 id='indexes'>".lang(134)."</h3>\n";$w=indexes($a);if($w)adminer()->tableIndexesPrint($w,$S);if(driver()->supportsAlterIndex($S))echo'<p class="links hover"><a href="'.h(ME).'indexes='.url_escape($a).'">'.lang(135)."</a>\n";echo"</div>\n";}if(!is_view($S)){if(fk_support($S)){echo"<div>\n","<h3 id='foreign-keys'>".lang(101)."</h3>\n";$jd=foreign_keys($a);if($jd){echo"<table>\n","<thead><tr><th>".lang(136)."<td>".lang(137)."<td>".lang(104)."<td>".lang(103)."<td class='hover'><tbody>\n";foreach($jd
as$D=>$o){echo"<tr title='".h($D)."'>","<th><i>".implode("</i>, <i>",array_map('Adminer\h',$o["source"]))."</i>";$_=($o["db"]!=""?preg_replace('~db=[^&]*~',"db=".url_escape($o["db"]),ME):($o["ns"]!=""?preg_replace('~ns=[^&]*~',"ns=".url_escape($o["ns"]),ME):ME));echo"<td><a href='".h($_."table=".url_escape($o["table"]))."'>".($o["db"]!=""&&$o["db"]!=DB?"<b>".h($o["db"])."</b>.":"").($o["ns"]!=""&&$o["ns"]!=$_GET["ns"]?"<b>".h($o["ns"])."</b>.":"").h($o["table"])."</a>","(<i>".implode("</i>, <i>",array_map('Adminer\h',$o["target"]))."</i>)","<td>".h($o["on_delete"]),"<td>".h($o["on_update"]),'<td class="hover"><a href="'.h(ME.'foreign='.url_escape($a).'&name='.url_escape($D)).'">'.lang(138).'</a>',"\n";}echo"</table>\n";}echo'<p class="links hover"><a href="'.h(ME).'foreign='.url_escape($a).'">'.lang(139)."</a>\n","</div>\n";}if(support("check")){echo"<div>\n","<h3 id='checks'>".lang(140)."</h3>\n";$Qa=driver()->checkConstraints($a);if($Qa){echo"<table>\n";foreach($Qa
as$y=>$X)echo"<tr title='".h($y)."'>","<td><code class='jush-".JUSH."'>".shorten_utf8(preg_replace('~\s+~',' ',ltrim($X)),80,"</code>"),"<td class='hover'><a href='".h(ME.'check='.url_escape($a).'&name='.url_escape($y))."'>".lang(138)."</a>","\n";echo"</table>\n";}echo'<p class="links hover"><a href="'.h(ME).'check='.url_escape($a).'">'.lang(141)."</a>\n","</div>\n";}}if(support(is_view($S)?"view_trigger":"trigger")){echo"<div>\n","<h3 id='triggers'>".lang(142)."</h3>\n";$Yi=triggers($a);if($Yi){echo"<table>\n";foreach($Yi
as$y=>$X)echo"<tr valign='top'><td>".h($X[0])."<td>".h($X[1])."<th>".h($y)."<td class='hover'><a href='".h(ME.'trigger='.url_escape($a).'&name='.url_escape($y))."'>".lang(138)."</a>\n";echo"</table>\n";}echo'<p class="links hover"><a href="'.h(ME).'trigger='.url_escape($a).'">'.lang(143)."</a>\n","</div>\n";}$ee=driver()->inheritedTables($a);if($ee){echo"<h3 id='partitions'>".lang(144)."</h3>\n";$zg=driver()->partitionsInfo($a);if($zg)echo"<p><code class='jush-".JUSH."'>BY ".h("$zg[partition_by]($zg[partition])")."</code>\n";tables_links($ee);}}elseif(isset($_GET["schema"])){page_header(lang(65),"",array(),h(DB.($_GET["ns"]?".$_GET[ns]":"")));$si=array();$ti=array();$Uc=array();$ba=($_GET["schema"]?:$_COOKIE["adminer_schema-".str_replace(".","_",DB)]);preg_match_all('~([^:]+):([-0-9.]+)x([-0-9.]+)(_|$)~',$ba,$Ye,PREG_SET_ORDER);foreach($Ye
as$s=>$B){$si[$B[1]]=array((float)$B[2],(float)$B[3]);$ti[]="\n\t'".js_escape($B[1])."': [ $B[2], $B[3] ]";}$Oi=0;$Fa=-1;$Ah=array();$nh=array();$Je=array();$na=driver()->allFields();foreach(table_status('',true)as$R=>$S){if(is_view($S))continue;$H=0;$Ah[$R]["fields"]=array();foreach($na[$R]as$k){$H+=1.25;$Uc[$R][$k["field"]]=$H;$Ah[$R]["fields"][$k["field"]]=$k;}$Ah[$R]["pos"]=($si[$R]?:array($Oi,0));foreach(adminer()->foreignKeys($R)as$X){if(!$X["db"]){$He=$Fa;if(idx($si[$R],1)||idx($si[$X["table"]],1))$He=min(idx($si[$R],1,0),idx($si[$X["table"]],1,0))-1;else$Fa-=.1;while($Je[(string)$He])$He-=.0001;$Ah[$R]["references"][$X["table"]][(string)$He]=array($X["source"],$X["target"]);$nh[$X["table"]][$R][(string)$He]=$X["target"];$Je[(string)$He]=true;}}$Oi=max($Oi,$Ah[$R]["pos"][0]+2.5+$H);}echo'<div id="schema" style="height: ',$Oi,'em;">
<script',nonce(),'>
const tablePos = {',implode(",",$ti)."\n",'};
const em = qs(\'#schema\').offsetHeight / ',$Oi,';
document.onmousemove = schemaMousemove;
document.onmouseup = event => schemaMouseup(event, \'',js_escape(DB),'\');
</script>
';foreach($Ah
as$D=>$R){echo"<div class='table'".on('mousedown','schemaMousedown')." style='top: ".$R["pos"][0]."em; left: ".$R["pos"][1]."em;'>",'<a href="'.h(ME).'table='.url_escape($D).'"><b>'.h($D)."</b></a>";foreach($R["fields"]as$k){$X='<span'.type_class($k["type"]).' title="'.h($k["type"].($k["length"]?"($k[length])":"").($k["null"]?" NULL":'')).'">'.h($k["field"]).'</span>';echo"<br>".($k["primary"]?"<i>$X</i>":$X);}foreach((array)$R["references"]as$_i=>$ph){foreach($ph
as$He=>$kh){$Ie=$He-idx($si[$D],1);$s=0;foreach($kh[0]as$Wh)echo"\n<div class='references' title='".h($_i)."' id='refs$He-".($s++)."' style='left: $Ie"."em; top: ".$Uc[$D][$Wh]."em; padding-top: .5em;'>"."<div style='border-top: 1px solid gray; width: ".(-$Ie)."em;'></div></div>";}}foreach((array)$nh[$D]as$_i=>$ph){foreach($ph
as$He=>$e){$Ie=$He-idx($si[$D],1);$s=0;foreach($e
as$zi)echo"\n<div class='references arrow' title='".h($_i)."' id='refd$He-".($s++)."' style='left: $Ie"."em; top: ".$Uc[$D][$zi]."em;'>"."<div style='height: .5em; border-bottom: 1px solid gray; width: ".(-$Ie)."em;'></div>"."</div>";}}echo"\n</div>\n";}foreach($Ah
as$D=>$R){foreach((array)$R["references"]as$_i=>$ph){if($Ah[$_i]){foreach($ph
as$He=>$kh){$xf=$Oi;$gf=-10;foreach($kh[0]as$y=>$Wh){$Og=$R["pos"][0]+$Uc[$D][$Wh];$Pg=$Ah[$_i]["pos"][0]+$Uc[$_i][$kh[1][$y]];$xf=min($xf,$Og,$Pg);$gf=max($gf,$Og,$Pg);}echo"<div class='references' id='refl$He' style='left: $He"."em; top: $xf"."em; padding: .5em 0;'><div style='border-right: 1px solid gray; margin-top: 1px; height: ".($gf-$xf)."em;'></div></div>\n";}}}}echo'</div>
<p class="links"><a href="',h(ME."schema=".url_escape($ba)),'" id="schema-link">',lang(145),'</a>
';}elseif(isset($_GET["dump"])){$a=$_GET["dump"];if($_POST&&!$j){$i=array("auto_increment"=>'');foreach(array("type","routine","event","trigger")as$ni){if(support($ni))$i[$ni."s"]='';}save_settings(array_intersect_key($_POST+$i,array_flip(array("output","format","db_style","table_style","data_style"))+$i),"adminer_export");$T=array_flip((array)$_POST["tables"])+array_flip((array)$_POST["data"]);$Mc=dump_headers((count($T)==1?key($T):DB),(DB==""||$_GET["ns"]===""||count($T)>1));$se=preg_match('~sql~',$_POST["format"]);if($se){echo"-- Adminer ".VERSION." ".get_driver(DRIVER)." ".str_replace("\n"," ",connection()->server_info)." dump\n\n";if(JUSH=="sql"){echo"SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
".($_POST["data_style"]?"SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';
":"")."
";connection()->query("SET time_zone = '+00:00'");connection()->query("SET sql_mode = ''");}}$ii=$_POST["db_style"];$g=array(DB);if(DB==""){$g=$_POST["databases"];if(is_string($g))$g=explode("\n",rtrim(str_replace("\r","",$g),"\n"));}foreach((array)$g
as$h){adminer()->dumpDatabase($h);if(connection()->select_db($h)){if($se&&$ii)echo
use_sql($h,$ii).";\n\n";foreach(($_GET["ns"]===""?(array)$_POST["schemas"]:(DB!=""||!support("scheme")?array(""):adminer()->schemas()))as$Ah){if($Ah!=""){if(DB==""&&information_schema(DB,$Ah))continue;set_schema($Ah);}$fi=($_POST["table_style"]||$_POST["data_style"]?table_status('',true):array());$Lc=array();$Fb=array();foreach($fi
as$D=>$S){if(DB==""||$_GET["ns"]===""||in_array($D,(array)$_POST["tables"]))$Lc[$D]=$S;if(DB==""||$_GET["ns"]===""||in_array($D,(array)$_POST["data"]))$Fb[$D]=$S;}if($se){if($_POST["table_style"]=="DROP+CREATE"&&function_exists('Adminer\drop_sql'))echo
drop_sql($Lc);if($_POST["data_style"]=="TRUNCATE+INSERT"&&function_exists('Adminer\truncate_all_sql')){$Zi=array();foreach($Fb
as$D=>$S){if(!is_view($S)&&!($_POST["table_style"]=="DROP+CREATE"&&isset($Lc[$D])))$Zi[]=$D;}echo
truncate_all_sql($Zi);}$qg="";if($_POST["types"]){foreach(types()as$t=>$U){$Ob=type_definition($t);$Lf=($Ob["kind"]=='d'?"DOMAIN":"TYPE");if($Ob["definition"])$qg
.=($ii!='DROP+CREATE'?"DROP $Lf IF EXISTS ".idf_escape($U).";;\n":"")."CREATE $Lf ".idf_escape($U)." $Ob[definition];\n\n";else$qg
.="-- Could not export type $U\n\n";}}if($_POST["routines"]){foreach(routines()as$L){$D=$L["ROUTINE_NAME"];$yh=$L["ROUTINE_TYPE"];$vb=create_routine($yh,array("name"=>$D)+routine($L["SPECIFIC_NAME"],$yh));set_utf8mb4($vb);$qg
.=($ii!='DROP+CREATE'?"DROP $yh IF EXISTS ".idf_escape($D).";;\n":"")."$vb;\n\n";}}if($_POST["events"]){foreach(get_rows("SHOW EVENTS",null,"-- ")as$L){$vb=remove_definer(get_val("SHOW CREATE EVENT ".idf_escape($L["Name"]),3));set_utf8mb4($vb);$qg
.=($ii!='DROP+CREATE'?"DROP EVENT IF EXISTS ".idf_escape($L["Name"]).";;\n":"")."$vb;;\n\n";}}echo($qg&&JUSH=='sql'?"DELIMITER ;;\n\n$qg"."DELIMITER ;\n\n":$qg);}if($_POST["table_style"]||$_POST["data_style"]){$Cj=array();foreach($fi
as$D=>$S){$R=array_key_exists($D,$Lc);$Db=array_key_exists($D,$Fb);if($R||$Db){$Li=null;if($Mc=="tar"){$Li=new
TmpFile;ob_start(array($Li,'write'),1e5);}adminer()->dumpTable($D,($R?$_POST["table_style"]:""),(is_view($S)?2:0));if(is_view($S))$Cj[]=$D;elseif($Db){$l=fields($D);$N=array("*");$rb=convert_fields($l,$l);if($rb)$N[]=substr($rb,2);adminer()->dumpData($D,$_POST["data_style"],"",$N);}if($se&&$_POST["triggers"]&&$R&&($Yi=trigger_sql($D)))echo"\nDELIMITER ;;\n$Yi\nDELIMITER ;\n";if($Mc=="tar"){ob_end_flush();tar_file((DB!=""?"":"$h/")."$D.csv",$Li);}elseif($se)echo"\n";}}if($se&&$_POST["table_style"]&&function_exists('Adminer\foreign_keys_sql')){foreach($Lc
as$D=>$S){if(!is_view($S))echo
foreign_keys_sql($D);}}if($se){foreach($Cj
as$Bj)adminer()->dumpTable($Bj,$_POST["table_style"],1);}if($Mc=="tar")echo
pack("x1024");}}}}adminer()->dumpFooter();exit;}page_header(lang(71),$j,($_GET["export"]!=""?array("table"=>$_GET["export"]):array()),h(DB));echo'
<form action="" method="post">
<table class="layout">
';$Ib=array('','USE','DROP+CREATE','CREATE');$ui=array('','DROP+CREATE','CREATE');$Eb=array('','TRUNCATE+INSERT','INSERT');if(JUSH=="sql")$Eb[]='INSERT+UPDATE';$L=get_settings("adminer_export");if(!$L)$L=array("output"=>"text","format"=>"sql","db_style"=>(DB!=""?"":"CREATE"),"table_style"=>"DROP+CREATE","data_style"=>"INSERT");echo"<tr><th>".lang(146)."<td>".html_radios("output",adminer()->dumpOutput(),$L["output"])."\n","<tr><th>".lang(147)."<td>".html_radios("format",adminer()->dumpFormat(),$L["format"])."\n",(JUSH=="sqlite"?"":"<tr><th>".lang(32)."<td>".html_select('db_style',$Ib,$L["db_style"]).(support("type")?checkbox("types",1,$L["types"],lang(7)):"").(support("routine")?checkbox("routines",1,$L["routines"],lang(67)):"").(support("event")?checkbox("events",1,$L["events"],lang(69)):"")),"<tr><th>".lang(123)."<td>".html_select('table_style',$ui,$L["table_style"]).checkbox("auto_increment",1,$L["auto_increment"],lang(46)).(support("trigger")?checkbox("triggers",1,$L["triggers"],lang(142)):""),"<tr><th>".lang(148)."<td>".html_select('data_style',$Eb,$L["data_style"]),'</table>
<p><input type=\'submit\' value=\'',lang(71),'\'>
',input_token(),'
<table',on('click','dumpClick'),'>
';$Vg=array();if($_GET["ns"]===""){echo"<thead><tr><th style='text-align: left;'>","<label class='block'><input type='checkbox' id='check-schemas' checked class='jsonly' title='".lang(149)."'".on('click','formCheck','^schemas\[').">".lang(150)."</label>","<tbody>\n";foreach(adminer()->schemas()as$Ah){if(!information_schema(DB,$Ah))echo"<tr><td>".checkbox("schemas[]",$Ah,true,$Ah,"","block")."\n";}}elseif(DB!=""){$Ra=($a!=""?"":" checked");echo"<thead><tr>","<th style='text-align: left;'><label class='block'><input type='checkbox' id='check-tables'$Ra class='jsonly' title='".lang(149)."'".on('click','formCheck','^tables\[').">".lang(132)."</label>","<th style='text-align: right;'><label class='block'>".lang(148)."<input type='checkbox' id='check-data'$Ra class='jsonly' title='".lang(149)."'".on('click','formCheck','^data\[')."></label>","<tbody>\n";$Cj="";$wi=tables_list();foreach($wi
as$D=>$U){$Ug=preg_replace('~_.*~','',$D);$Ra=($a==""||$a==(substr($a,-1)=="%"?"$Ug%":$D));$Yg="<tr><td>".checkbox("tables[]",$D,$Ra,$D,"","block");if($U!==null&&!preg_match('~table~i',$U))$Cj
.="$Yg\n";else
echo"$Yg<td align='right'><label class='block'><span id='Rows-".h($D)."'></span>".checkbox("data[]",$D,$Ra)."</label>\n";$Vg[$Ug]++;}echo$Cj;if($wi)echo
script("ajaxSetHtml('".js_escape(ME)."script=db');");}else{$g=adminer()->databases();echo"<thead><tr><th style='text-align: left;'>","<label class='block'>".($g?"<input type='checkbox' id='check-databases'".($a==""?" checked":"")." class='jsonly' title='".lang(149)."'".on('click','formCheck','^databases\[').">":"").lang(32)."</label>","<tbody>\n";if($g){foreach($g
as$h){if(!information_schema($h)){$Ug=preg_replace('~_.*~','',$h);echo"<tr><td>".checkbox("databases[]",$h,$a==""||$a=="$Ug%",$h,"","block")."\n";$Vg[$Ug]++;}}}else
echo"<tr><td><textarea name='databases' rows='10' cols='20'></textarea>";}echo'</table>
</form>
';$cd=true;foreach($Vg
as$y=>$X){if($y!=""&&$X>1){echo($cd?"<p>":" ")."<a href='".h(ME)."dump=".url_escape("$y%")."'>".h($y)."</a>";$cd=false;}}}elseif(isset($_GET["sql"])){if(!$j&&$_POST["export"]){save_settings(array("output"=>$_POST["output"],"format"=>$_POST["format"]),"adminer_import");dump_headers("sql");if($_POST["format"]=="sql")echo"$_POST[query]\n";else{adminer()->dumpTable("","");adminer()->dumpData("","table",$_POST["query"]);adminer()->dumpFooter();}exit;}restart_session();$Kd=&get_session("queries");$Jd=&$Kd[DB];if(!$j&&$_POST["clear"]){$Jd=array();redirect(remove_from_uri("history"));}stop_session();$ia=get_settings("adminer_import");if($_POST&&$ia)save_settings($ia,"adminer_import");page_header((isset($_GET["import"])?lang(70):lang(59)),$j);$Qe=driver()->lineComment();if(!$j&&$_POST&&!(isset($_GET["import"])&&adminer()->importProcess())){$Pb=driver()->delimiter;$p=false;if(!isset($_GET["import"]))$I=$_POST["query"];elseif($_POST["webfile"]){$Zh=adminer()->importServerPath();$p=@fopen((file_exists($Zh)?$Zh:"compress.zlib://$Zh.gz"),"rb");$I=($p?fread($p,1e6):false);}else$I=get_file("sql_file",true,$Pb);if(is_string($I)){if(($nf=ini_bytes("memory_limit"))!="-1")ini_set("memory_limit",max($nf,strval(2*strlen($I)+memory_get_usage()+8e6)));if($I!=""&&strlen($I)<1e6){$dh=$I.(preg_match("~$Pb\\s*\$~",$I)?"":$Pb);if(!$Jd||first(end($Jd))!=$dh){restart_session();$Jd[]=array($dh,time());set_session("queries",$Kd);stop_session();}}$Xh="(?:\\s|/\\*[\s\S]*?\\*/|(?:$Qe)[^\n]*\n?|--\r?\n)";$Qf=0;$vc=true;$tb=false;$f=connect();if($f&&DB!=""){$f->select_db(DB);if($_GET["ns"]!="")set_schema($_GET["ns"],$f);}$db=0;$Bc=array();$xg='[\'"'.(JUSH=="sql"?'`':(JUSH=="sqlite"?'`[':(JUSH=="mssql"?'[':''))).']|/\*|'.$Qe.'|$'.(JUSH=="pgsql"?'|\$([a-zA-Z]\w*)?\$':'');$Pi=microtime(true);while($I!=""){if(!$Qf&&preg_match("~^$Xh*+DELIMITER\\s+(\\S+)~i",$I,$B)){$Pb=preg_quote($B[1]);$I=substr($I,strlen($B[0]));}elseif(!$Qf&&JUSH=='pgsql'&&preg_match("~^($Xh*+COPY\\s+)[^;]+\\s+FROM\\s+stdin;~i",$I,$B)){$Pb="\n\\\\\\.\r?\n";$tb=true;$Qf=strlen($B[0]);}else{preg_match("($Pb\\s*|$xg)",$I,$B,PREG_OFFSET_CAPTURE,$Qf);list($ld,$H)=$B[0];if(!$ld&&$p&&!feof($p))$I
.=fread($p,1e5);else{if(!$ld&&rtrim($I)=="")break;$Qf=$H+strlen($ld);if($ld&&!preg_match("(^$Pb)",$ld)){$Ma=driver()->hasCStyleEscapes()||(JUSH=="pgsql"&&($H>0&&strtolower($I[$H-1])=="e"));$Gg=($ld=='/*'?'\*/':($ld=='['?']':(preg_match("~^(?:$Qe)~",$ld)?"\n":preg_quote($ld).($Ma?'|\\\\.':''))));while(preg_match("($Gg|\$)s",$I,$B,PREG_OFFSET_CAPTURE,$Qf)){$_h=$B[0][0];if(!$_h&&$p&&!feof($p))$I
.=fread($p,1e5);else{$Qf=$B[0][1]+strlen($_h);if(!$_h||$_h[0]!="\\")break;}}}else{$vc=false;$dh=substr($I,0,$H+($tb?3:0));$db++;$Yg="<pre id='sql-$db'><code class='jush-".JUSH."'>".adminer()->sqlCommandQuery($dh)."</code></pre>\n";if(JUSH=="sqlite"&&preg_match("~^$Xh*+(ATTACH|VACUUM\\b.*\\bINTO)\\b~is",$dh,$B)!==0){echo$Yg,"<p class='error'>".lang(151,preg_match('~ATTACH~i',$B[1])?'ATTACH':'VACUUM INTO')."\n";$Bc[]=" <a href='#sql-$db'>$db</a>";if($_POST["error_stops"])break;}else{if(!$_POST["only_errors"]){echo$Yg;ob_flush();flush();}$di=microtime(true);if(connection()->multi_query($dh)&&$f&&preg_match("~^$Xh*+USE\\b~i",$dh))$f->query($dh);do{$J=connection()->store_result();if(connection()->error){echo($_POST["only_errors"]?$Yg:""),"<p class='error'>".lang(152).(connection()->errno?" (".connection()->errno.")":"").": ".error()."\n";$Bc[]=" <a href='#sql-$db'>$db</a>";if($_POST["error_stops"])break
2;}else{$_=ME."sql=".url_escape(trim($dh));$Ei=" <span class='time'>(".format_time($di).")</span>".(strlen($_)<1900?" <a href='".h($_)."'>".lang(13)."</a>":"");$ka=connection()->affected_rows;$Fj=($_POST["only_errors"]?"":driver()->warnings());$Gj="warnings-$db";if($Fj)$Ei
.=", <a href='#$Gj' class='toggle'>".lang(41)."</a>";$Jc=null;$hg=null;$Kc="explain-$db";if(is_object($J)){$z=$_POST["limit"];$Kf=$z;$hg=print_select_result($J,$f,array(),$Kf);if(!$_POST["only_errors"]){echo"<form action='' method='post'>\n";$Kf=max($J->num_rows,$Kf);echo"<p class='sql-footer'>".($Kf?($z&&$Kf>$z?lang(153,$z):"").lang(154,$Kf):""),$Ei;if($f&&preg_match("~^($Xh|\\()*+SELECT\\b~i",$dh)&&($Jc=explain($f,$dh)))echo", <a href='#$Kc' class='toggle'>Explain</a>";$t="export-$db";echo", <a href='#$t' class='toggle'>".lang(71)."</a><span id='$t' class='hidden'>: ".html_select("output",adminer()->dumpOutput(),$ia["output"])." ".html_select("format",adminer()->dumpFormat(),$ia["format"]).input_hidden("query",$dh)."<input type='submit' name='export' value='".lang(71)."'".($z?"":on('click','sqlExport')).">".input_token()."</span>\n"."</form>\n";}}else{if(preg_match("~^$Xh*+(CREATE|DROP|ALTER)$Xh++(DATABASE|SCHEMA)\\b~i",$dh)){restart_session();set_session("dbs",null);stop_session();}if(!$_POST["only_errors"])echo"<p class='message' title='".h(connection()->info)."'>".lang(155,$ka)."$Ei\n";}echo($Fj?"<div id='$Gj' class='hidden'>\n$Fj</div>\n":"");if($Jc){echo"<div id='$Kc' class='hidden explain'>\n";print_select_result($Jc,$f,$hg);echo"</div>\n";}}$di=microtime(true);}while(connection()->next_result());}$I=substr($I,$Qf);$Qf=0;if($tb){$Pb=driver()->delimiter;$tb=false;}}}}}if($vc)echo"<p class='message'>".lang(156)."\n";else{$Xd=connection()->inTransaction();driver()->rollback();if($Xd)echo"<pre><code class='jush-".JUSH."'>ROLLBACK -- Adminer</code></pre>\n";if($_POST["only_errors"])echo"<p class='message'>".lang(157,$db-count($Bc))," <span class='time'>(".format_time($Pi).")</span>\n";elseif($Bc&&$db>1)echo"<p class='error'>".lang(152).": ".implode("",$Bc)."\n";}}else
echo"<p class='error'>".upload_error($I)."\n";}echo'
<form action="" method="post" enctype="multipart/form-data" id="form"',(isset($_GET["import"])?"":on('submit','sqlSubmit',remove_from_uri("sql|limit|error_stops|only_errors|history"))),'>
';$Hc="<input type='submit' value='".lang(158)."' title='Ctrl+Enter'>";if(!isset($_GET["import"])){$dh=$_GET["sql"];if($_POST)$dh=$_POST["query"];elseif($_GET["history"]=="all")$dh=$Jd;elseif($_GET["history"]!="")$dh=idx($Jd[$_GET["history"]],0);echo"<p>";textarea("query",$dh,20);echo($_POST?"":script("qs('textarea').focus();")),"<p>";adminer()->sqlPrintAfter();echo"$Hc\n",lang(159).": <input type='number' name='limit' class='size' value='".h($_POST?$_POST["limit"]:$_GET["limit"])."'>\n";}else{$xd=(extension_loaded("zlib")?"[.gz]":"");echo"<fieldset><legend>".lang(160)."</legend><div>","SQL$xd: ".file_input(" name='sql_file[]' multiple","\n$Hc"),"</div></fieldset>\n";$Ud=adminer()->importServerPath();if($Ud)echo"<fieldset><legend>".lang(161)."</legend><div>",lang(162,"<code>".h($Ud)."$xd</code>")," <input type='submit' name='webfile' value='".lang(163)."'>","</div></fieldset>\n";adminer()->importPrint();echo"<p>";}echo
checkbox("error_stops",1,($_POST?$_POST["error_stops"]:isset($_GET["import"])||$_GET["error_stops"]),lang(164))."\n",checkbox("only_errors",1,($_POST?$_POST["only_errors"]:isset($_GET["import"])||$_GET["only_errors"]),lang(165))."\n",input_token();if(!isset($_GET["import"])&&$Jd){print_fieldset("history",lang(166),$_GET["history"]!="");for($X=end($Jd);$X;$X=prev($Jd)){$y=key($Jd);list($dh,$Ei,$rc)=$X;echo'<div><a href="'.h(ME."sql=&history=$y").'" class="hover">'.lang(13)."</a>"." <span class='time' title='".@date('Y-m-d',$Ei)."'>".@date("H:i:s",$Ei)."</span>"." <code class='jush-".JUSH."'>".shorten_utf8(preg_replace('~\s+~',' ',ltrim(preg_replace("~^(?:$Qe).*~m",'',$dh))),80,"</code>").($rc?" <span class='time'>($rc)</span>":"")."</div>\n";}echo"<input type='submit' name='clear' value='".lang(167)."'>\n","<a href='".h(ME."sql=&history=all")."'>".lang(168)."</a>\n","</div></fieldset>\n";}echo'</form>
';}elseif(isset($_GET["edit"])){$a=$_GET["edit"];$l=fields($a);$Z=(isset($_GET["select"])?($_POST["check"]&&count($_POST["check"])==1?where_check($_POST["check"][0],$l):""):where($_GET,$l));$lj=(isset($_GET["select"])?$_POST["edit"]:$Z);foreach($l
as$D=>$k){if((!$lj&&!isset($k["privileges"]["insert"]))||adminer()->fieldName($k)=="")unset($l[$D]);}if($_POST&&!$j&&!isset($_GET["select"])){$A=$_POST["referer"];if($_POST["insert"])$A=($lj?null:$_SERVER["REQUEST_URI"]);elseif(!preg_match('~^.+&select=.+$~',$A))$A=ME."select=".url_escape($a);$w=indexes($a);$fj=unique_array($_GET["where"],$w);$gh="\nWHERE $Z";if(isset($_POST["delete"]))queries_redirect($A,lang(169),driver()->delete($a,$gh,$fj?0:1));else{$P=array();foreach($l
as$D=>$k){$X=process_input($k);if($X!==false&&$X!==null)$P[idf_escape($D)]=$X;}if($lj){if(!$P)redirect($A);queries_redirect($A,lang(170),driver()->update($a,$P,$gh,$fj?0:1));if(is_ajax()){page_headers();page_messages($j);exit;}}else{$J=driver()->insert($a,$P);$Ge=($J?last_id($J):0);queries_redirect($A,lang(171,($Ge?" $Ge":"")),$J);}}}$L=null;if($Z){$N=array();foreach($l
as$D=>$k){if(isset($k["privileges"]["select"])){$va=($_POST["clone"]&&$k["auto_increment"]?"''":convert_field($k));$N[]=($va?"$va AS ":"").idf_escape($D);}}$L=array();if(!support("table"))$N=array("*");if($N){$J=driver()->select($a,$N,array($Z),$N,array(),(isset($_GET["select"])?2:1));if(!$J)$j=error();else{$L=$J->fetch_assoc();if(!$L)$L=false;}if(isset($_GET["select"])&&(!$L||$J->fetch_assoc()))$L=null;}}if(!$l&&driver()->primary!=""){if(!$Z){$J=driver()->select($a,array("*"),array(),array("*"));$L=($J?$J->fetch_assoc():false);if(!$L)$L=array(driver()->primary=>"");}if($L){foreach($L
as$y=>$X){if(!$Z)$L[$y]=null;$l[$y]=array("field"=>$y,"null"=>($y!=driver()->primary),"auto_increment"=>($y==driver()->primary));}}}if($_POST["save"]){$Qg=array();foreach((array)$_POST["fields"]as$y=>$X)$Qg[bracket_escape($y,true)]=$X;$L=$Qg+($L?$L:array());}edit_form($a,$l,$L,$lj,$j);}elseif(isset($_GET["create"])){$a=$_GET["create"];$Ag=driver()->partitionBy;$Dg=($Ag&&$a!=""?driver()->partitionsInfo($a):array());$mh=referencable_primary($a);$jd=array();foreach($mh
as$ri=>$k)$jd[str_replace("`","``",$ri)."`".str_replace("`","``",$k["field"])]=$ri;$kg=array();$S=array();if($a!=""){$kg=fields($a);$S=table_status1($a);if(count($S)<2)$j=lang(12);}$L=$_POST;$L["fields"]=(array)$L["fields"];if($L["auto_increment_col"])$L["fields"][$L["auto_increment_col"]]["auto_increment"]=true;if($_POST&&!$j)save_settings(array("comments"=>$_POST["comments"],"defaults"=>$_POST["defaults"]));if($_POST&&!process_fields($L["fields"])&&!$j){if($_POST["drop"])queries_redirect(substr(ME,0,-1),lang(172),drop_tables(array($a)));else{$l=array();$na=array();$qj=false;$hd=array();$jg=reset($kg);$ma=" FIRST";foreach($L["fields"]as$y=>$k){$o=$jd[$k["type"]];$bj=($o!==null?$mh[$o]:$k);if($k["field"]!=""){if(!$k["generated"])$k["default"]=null;$ch=process_field($k,$bj);$na[]=array($k["orig"],$ch,$ma);if(!$jg||$ch!==process_field($jg,$jg)){$l[]=array($k["orig"],$ch,$ma);if($k["orig"]!=""||$ma)$qj=true;}if($o!==null)$hd[idf_escape($k["field"])]=($a!=""&&JUSH!="sqlite"?"ADD":" ").format_foreign_key(array('table'=>$jd[$k["type"]],'source'=>array($k["field"]),'target'=>array($bj["field"]),'on_delete'=>$k["on_delete"],));$ma=" AFTER ".idf_escape($k["field"]);}elseif($k["orig"]!=""){$qj=true;$l[]=array($k["orig"]);}if($k["orig"]!=""){$jg=next($kg);if(!$jg)$ma="";}}$Cg=array();if(in_array($L["partition_by"],$Ag)){foreach($L
as$y=>$X){if(preg_match('~^partition~',$y))$Cg[$y]=$X;}foreach($Cg["partition_names"]as$y=>$D){if($D==""){unset($Cg["partition_names"][$y]);unset($Cg["partition_values"][$y]);}}$Cg["partition_names"]=array_values($Cg["partition_names"]);$Cg["partition_values"]=array_values($Cg["partition_values"]);if($Cg==$Dg)$Cg=array();}elseif(preg_match("~partitioned~",$S["Create_options"]))$Cg=null;$C=lang(173);if($a==""){cookie("adminer_engine",$L["Engine"]);$C=lang(174);}$D=trim($L["name"]);$A=ME.(support("table")?"table=":"select=").url_escape($D);$J=alter_table($a,$D,(JUSH=="sqlite"&&($qj||$hd)?$na:$l),$hd,($L["Comment"]!=$S["Comment"]?$L["Comment"]:null),($L["Engine"]&&$L["Engine"]!=$S["Engine"]?$L["Engine"]:""),($L["Collation"]&&$L["Collation"]!=$S["Collation"]?$L["Collation"]:""),($L["Auto_increment"]!=""?number($L["Auto_increment"]):""),$Cg);if($J&&!Queries::$queries)redirect($A);queries_redirect($A,$C,$J);}}page_header(($a!=""?lang(39):lang(72)),$j,array("table"=>$a),h($a));if(!$_POST){$cj=driver()->types();$L=array("Engine"=>$_COOKIE["adminer_engine"],"fields"=>array(array("field"=>"","type"=>(isset($cj["int"])?"int":(isset($cj["integer"])?"integer":"")),"on_update"=>"")),"partition_names"=>array(""),);if($a!=""){$L=$S;$L["name"]=$a;$L["fields"]=array();if(!$_GET["auto_increment"])$L["Auto_increment"]="";foreach($kg
as$k){if($k["generated"])$k["default"]=ltrim($k["default"]);$k["generated"]=$k["generated"]?:(isset($k["default"])?"DEFAULT":"");$L["fields"][]=$k;}if($Ag){$L+=$Dg;$L["partition_names"][]="";$L["partition_values"][]="";}}}$bb=collations();if(is_array(reset($bb)))$bb=call_user_func_array('array_merge',array_values($bb));$xc=driver()->engines();foreach($xc
as$wc){if(!strcasecmp($wc,$L["Engine"])){$L["Engine"]=$wc;break;}}$bf=max_input_vars(12,20);if($bf){$Id=(count($L["fields"])>$bf?"":" hidden");echo"<p".($Id?" id='max-fields' data-columns='$bf'":"")." class='error$Id'>".max_input_vars_error()."\n";}echo'
<form action="" method="post" id="form">
<p>
';if(support("columns")||$a==""){echo
lang(175).": <input name='name'".($a==""&&!$_POST?" autofocus":"")." data-maxlength='64' value='".h($L["name"])."' autocapitalize='off'>\n",($xc?html_select("Engine",array(""=>"(".lang(176).")")+$xc,$L["Engine"],on('change','helpClose').on_help_value())."\n":"");if($bb)echo"<datalist id='collations'>".optionlist($bb)."</datalist>\n",(preg_match("~sqlite|mssql~",JUSH)?"":"<input list='collations' name='Collation' value='".h($L["Collation"])."' placeholder='(".lang(102).")'>\n");echo"<input type='submit' value='".lang(17)."'>\n";}if(support("columns")){echo"<div class='scrollable'>\n","<table id='edit-fields' class='nowrap'>\n";edit_fields($L["fields"],$bb,"TABLE",$jd);echo"</table>\n",script("editFields();"),"</div>\n<p>\n",lang(46).": <input type='number' name='Auto_increment' class='size' value='".h($L["Auto_increment"])."'>\n",checkbox("defaults",1,($_POST?$_POST["defaults"]:get_setting("defaults")),lang(177),on('click','columnShowClick',5),"jsonly");$gb=($_POST?$_POST["comments"]:get_setting("comments"));if(support("comment")){echo
checkbox("comments",1,$gb,lang(45),on('click','editingCommentsClick',true),"jsonly").' ';$b=" name='Comment' data-maxlength='".(min_version(5.5)?2048:60)."'".($gb?"":" class='hidden'");echo
adminer()->commentInput('TABLE',$b,$L["Comment"]);}echo'<p>
<input type=\'submit\' value=\'',lang(17),'\'>
';}echo'
';if($a!="")echo'<input type=\'submit\' name=\'drop\' value=\'',lang(127),'\'',confirm(lang(178,$a)),'>
';if($Ag&&(JUSH=='sql'||$a=="")){$Bg=preg_match('~RANGE|LIST~',$L["partition_by"]);print_fieldset("partition",lang(179),$L["partition_by"]);echo"<p>".html_select("partition_by",array_merge(array(""),$Ag),$L["partition_by"],on('change','partitionByChange').on_help_value('.','PARTITION BY $&'))."\n","(<input name='partition' value='".h($L["partition"])."'>)\n",lang(180).": <input type='number' name='partitions' class='size".($Bg||!$L["partition_by"]?" hidden":"")."' value='".h($L["partitions"])."'>\n","<table id='partition-table'".($Bg?"":" class='hidden'").">\n","<thead><tr><th>".lang(181)."<th>".lang(182)."<tbody>\n";foreach($L["partition_names"]as$y=>$X)echo'<tr>','<td><input name="partition_names[]" value="'.h($X).'" autocapitalize="off"'.($y==count($L["partition_names"])-1?on('input','partitionNameChange'):'').'>','<td><input name="partition_values[]" value="'.h(idx($L["partition_values"],$y)).'">';echo"</table>\n</div></fieldset>\n";}echo
input_token(),'</form>
';}elseif(isset($_GET["indexes"])){$a=$_GET["indexes"];$ce=array("PRIMARY","UNIQUE","INDEX");$S=table_status1($a,true);$ae=driver()->indexAlgorithms($S);if(preg_match('~MyISAM|M?aria'.(min_version(5.6,'10.0.5')?'|InnoDB':'').'~i',$S["Engine"]))$ce[]="FULLTEXT";if(preg_match('~MyISAM|M?aria'.(min_version(5.7,'10.2.2')?'|InnoDB':'').'~i',$S["Engine"]))$ce[]="SPATIAL";if(min_version('',11.7)&&preg_match('~MyISAM|InnoDB~i',$S["Engine"]))$ce[]="VECTOR";$w=indexes($a);$l=fields($a);$Wg=array();if(JUSH=="mongo"){$Wg=$w["_id_"];unset($ce[0]);unset($w["_id_"]);}$L=$_POST;if($L)save_settings(array("index_options"=>$L["options"]));if($_POST&&!$j&&!$_POST["add"]&&!$_POST["drop_col"]){$pa=array();foreach($L["indexes"]as$v){$D=$v["name"];if(in_array($v["type"],$ce)){$e=array();$Oe=array();$Sb=array();$Xf=array();$be=(support("partial_indexes")?$v["partial"]:"");$Zd=(in_array($v["algorithm"],$ae)?$v["algorithm"]:"");$P=array();ksort($v["columns"]);foreach($v["columns"]as$y=>$d){if($d!=""){$Le=idx($v["lengths"],$y);$Qb=idx($v["descs"],$y);$Wf=idx($v["opclasses"],$y);$P[]=($l[$d]?idf_escape($d):$d).($Le?"(".(+$Le).")":"").($Wf!=""?" ".idf_escape($Wf):"").($Qb?" DESC":"");$e[]=$d;$Oe[]=($Le?:null);$Sb[]=$Qb;$Xf[]="$Wf";}}$Ic=$w[$D];if($Ic){ksort($Ic["columns"]);ksort($Ic["lengths"]);ksort($Ic["descs"]);if($v["type"]==$Ic["type"]&&array_values($Ic["columns"])===$e&&(!$Ic["lengths"]||array_values($Ic["lengths"])===$Oe)&&array_values($Ic["descs"])===$Sb&&(!$Ic["opclasses"]||array_values($Ic["opclasses"])===$Xf)&&$Ic["partial"]==$be&&(!$ae||$Ic["algorithm"]==$Zd)){unset($w[$D]);continue;}}if($e)$pa[]=array($v["type"],$D,$P,$Zd,$be);}}foreach($w
as$D=>$Ic)$pa[]=array($Ic["type"],$D,"DROP");if(!$pa)redirect(ME."table=".url_escape($a));queries_redirect(ME."table=".url_escape($a),lang(183),alter_indexes($a,$pa));}page_header(lang(134),$j,array("table"=>$a),h($a));$Wc=array_keys($l);if($_POST["add"]){foreach($L["indexes"]as$y=>$v){if($v["columns"][count($v["columns"])]!="")$L["indexes"][$y]["columns"][]="";}$v=end($L["indexes"]);if($v["type"]||array_filter($v["columns"],'strlen'))$L["indexes"][]=array("columns"=>array(1=>""));}if(!$L){foreach($w
as$y=>$v){$w[$y]["name"]=$y;$w[$y]["columns"][]="";}$w[]=array("columns"=>array(1=>""));$L["indexes"]=$w;}$Oe=(JUSH=="sql"||JUSH=="mssql");$Xf=driver()->indexOpclasses();$Ph=($_POST?$_POST["options"]:get_setting("index_options"));echo'
<form action="" method="post">
<div class="scrollable">
<table class="nowrap odds">
<thead><tr>
<th id="label-type">',lang(184);$Sd=" class='idxopts".($Ph?"":" hidden")."'";if($ae)echo"<th id='label-algorithm'$Sd>".lang(185).'';echo'<th><input type="submit" hidden>',lang(186).($Oe?"<span$Sd> (".lang(187).")</span>":"");if($Oe||support("descidx"))echo
checkbox("options",1,$Ph,lang(108),on('click','indexOptionsShow'),"jsonly")."\n";echo'<th id="label-name">',lang(188);if(support("partial_indexes"))echo"<th id='label-condition'$Sd>".lang(189);echo'<th><noscript>',icon("plus","add[0]","+",lang(109)),'</noscript>
<tbody>
';if($Wg){echo"<tr><td>PRIMARY<td>";foreach($Wg["columns"]as$y=>$d)echo
select_input(" disabled",array_combine($Wc,$Wc),$d),"<label><input disabled type='checkbox'>".lang(54)."</label> ";echo"<td><td>\n";}$x=1;foreach($L["indexes"]as$v){if(!$_POST["drop_col"]||$x!=key($_POST["drop_col"])){echo"<tr><td>".html_select("indexes[$x][type]",array(-1=>"")+$ce,$v["type"],($x==count($L["indexes"])?on('change','indexesAddRow'):""),"label-type");if($ae)echo"<td$Sd>".html_select("indexes[$x][algorithm]",array_merge(array(""),$ae),$v['algorithm'],"","label-algorithm");echo"<td>";ksort($v["columns"]);$s=1;foreach($v["columns"]as$y=>$d){echo"<span>".select_input(" name='indexes[$x][columns][$s]' title='".lang(43)."'".on('change','indexesChangeColumn',(JUSH=="sql"?"":$_GET["indexes"]."_")),($l&&($d==""||$l[$d])?array_combine($Wc,$Wc):array()),$d)," <span$Sd>",($Oe?"<input type='number' name='indexes[$x][lengths][$s]' class='size' value='".h(idx($v["lengths"],$y))."' title='".lang(107)."'>":"");if($Xf){$Wf=idx($v["opclasses"],$y);echo
html_select("indexes[$x][opclasses][$s]",array(""=>"(".lang(190).")")+array_combine($Xf,$Xf)+($Wf!=""?array($Wf=>$Wf):array()),$Wf),'';}echo(support("descidx")?checkbox("indexes[$x][descs][$s]",1,idx($v["descs"],$y),lang(54)):""),"<br>","</span></span>";$s++;}echo"<td><input name='indexes[$x][name]' value='".h($v["name"])."' autocapitalize='off' aria-labelledby='label-name'>\n";if(support("partial_indexes"))echo"<td$Sd><input name='indexes[$x][partial]' value='".h($v["partial"])."' autocapitalize='off' aria-labelledby='label-condition'>\n";echo"<td>".icon("cross","drop_col[$x]","x",lang(111),on('click','editingRemoveRow','indexes$1[type]'));}$x++;}echo'</table>
</div>
<p>
<input type=\'submit\' value=\'',lang(17),'\'>
',input_token(),'</form>
';}elseif(isset($_GET["database"])){$L=$_POST;if($_POST&&!$j&&!$_POST["add"]){$D=trim($L["name"]);if($_POST["drop"]){$_GET["db"]="";queries_redirect(remove_from_uri("db|database"),lang(191),drop_databases(array(DB)));}elseif(DB!==$D){if(DB!=""){$_GET["db"]=$D;queries_redirect(preg_replace('~\bdb=[^&]*&~','',ME)."db=".url_escape($D),lang(192),rename_database($D,(string)$L["collation"]));}else{$g=explode("\n",str_replace("\r","",$D));$ji=true;$Ee="";foreach($g
as$h){if(count($g)==1||$h!=""){if(!create_database($h,(string)$L["collation"]))$ji=false;$Ee=$h;}}restart_session();set_session("dbs",null);queries_redirect(ME."db=".url_escape($Ee),lang(193),$ji);}}else{if(!$L["collation"])redirect(substr(ME,0,-1));query_redirect("ALTER DATABASE ".idf_escape($D).(preg_match('~^[a-z0-9_]+$~i',$L["collation"])?" COLLATE $L[collation]":""),substr(ME,0,-1),lang(194));}}page_header(DB!=""?lang(62):lang(115),$j,array(),h(DB));$bb=collations();$D=DB;if($_POST)$D=$L["name"];elseif(DB!="")$L["collation"]=db_collation(DB,$bb);elseif(JUSH=="sql"){foreach(get_vals("SHOW GRANTS")as$td){if(preg_match('~ ON (`(([^\\\\`]|``|\\\\.)*)%`\.\*)?~',$td,$B)&&$B[1]){$D=stripcslashes(idf_unescape("`$B[2]`"));break;}}}echo'
<form action="" method="post">
<p>
',($_POST["add"]||strpos($D,"\n")?'<textarea autofocus name="name" rows="10" cols="40">'.h($D).'</textarea><br>':'<input name="name" autofocus value="'.h($D).'" data-maxlength="64" autocapitalize="off">')."\n",($bb?html_select("collation",array(""=>"(".lang(102).")")+$bb,$L["collation"]).'':"")."\n",'<input type=\'submit\' value=\'',lang(17),'\'>
';if(DB!="")echo"<input type='submit' name='drop' value='".lang(127)."'".confirm(lang(178,DB)).">\n";elseif(!$_POST["add"]&&$_GET["db"]=="")echo
icon("plus","add[0]","+",lang(109))."\n";echo
input_token(),'</form>
';}elseif(isset($_GET["foreign"])){$a=$_GET["foreign"];$D=$_GET["name"];$L=$_POST;if($_POST&&!$j&&!$_POST["add"]&&!$_POST["change"]&&!$_POST["change-js"]){if(!$_POST["drop"]){$L["source"]=array_filter($L["source"],'strlen');ksort($L["source"]);$zi=array();foreach($L["source"]as$y=>$X)$zi[$y]=$L["target"][$y];$L["target"]=$zi;}if(JUSH=="sqlite")$J=recreate_table($a,$a,array(),array(),array(" $D"=>($L["drop"]?"":" ".format_foreign_key($L))));else{$pa="ALTER TABLE ".table($a);$J=($D==""||queries("$pa DROP ".(JUSH=="sql"?"FOREIGN KEY ":"CONSTRAINT ").idf_escape($D)));if(!$L["drop"])$J=queries("$pa ADD".format_foreign_key($L));}queries_redirect(ME."table=".url_escape($a),($L["drop"]?lang(195):($D!=""?lang(196):lang(197))),$J);if(!$L["drop"])$j=lang(198);}page_header(($D!=""?lang(199):lang(139)),$j,array("table"=>$a),h($D!=""?$D:$a));if($_POST){ksort($L["source"]);if($_POST["change"]||$_POST["change-js"])$L["target"]=array();else$L["source"][]="";}elseif($D!=""){$jd=foreign_keys($a);$L=$jd[$D];$L["source"][]="";}else{$L["table"]=$a;$L["source"]=array("");}echo'
<form action="" method="post">
';$Wh=array_keys(fields($a));if($L["db"]!="")connection()->select_db($L["db"]);if($L["ns"]!=""){$lg=get_schema();set_schema($L["ns"]);}$lh=array_keys(array_filter(table_status('',true),'Adminer\fk_support'));$zi=array_keys(fields(in_array($L["table"],$lh)?$L["table"]:reset($lh)));$b=on('change','foreignChange');echo"<p><label>".lang(200).": ".html_select("table",$lh,$L["table"],$b)."</label>\n";if(JUSH!="sqlite"){$Jb=array();foreach(adminer()->databases()as$h){if(!information_schema($h))$Jb[]=$h;}echo"<label>".lang(73).": ".html_select("db",$Jb,$L["db"]!=""?$L["db"]:$_GET["db"],$b)."</label>";}echo
input_hidden("change-js"),'<noscript><p><input type=\'submit\' name=\'change\' value=\'',lang(201),'\'></noscript>
<table>
<thead><tr><th id="label-source">',lang(136),'<th id="label-target">',lang(137),'<tbody>
';$x=0;foreach($L["source"]as$y=>$X){echo"<tr>","<td>".html_select("source[".(+$y)."]",array(-1=>"")+$Wh,$X,($x==count($L["source"])-1?on('change','foreignAddRow'):""),"label-source"),"<td>".html_select("target[".(+$y)."]",$zi,idx($L["target"],$y),"","label-target");$x++;}echo'</table>
<p>
<label>',lang(104),': ',html_select("on_delete",array(-1=>"")+explode("|",driver()->onActions),$L["on_delete"]),'</label>
<label>',lang(103),': ',html_select("on_update",array(-1=>"")+explode("|",driver()->onActions),$L["on_update"]),'</label>
',(support("deferrable")?html_select("deferrable",array('NOT DEFERRABLE','DEFERRABLE','DEFERRABLE INITIALLY DEFERRED'),$L["deferrable"]).' ':''),'','<p>
<input type=\'submit\' value=\'',lang(17),'\'>
<noscript><p><input type=\'submit\' name=\'add\' value=\'',lang(202),'\'></noscript>
';if($D!="")echo'<input type=\'submit\' name=\'drop\' value=\'',lang(127),'\'',confirm(lang(178,$D)),'>
';echo
input_token(),'</form>
';}elseif(isset($_GET["view"])){$a=$_GET["view"];$L=$_POST;$mg="VIEW";if(JUSH=="pgsql"&&$a!=""){$ei=table_status1($a);$mg=strtoupper($ei["Engine"]);}if($_POST&&!$j){$D=trim($L["name"]);$va=" AS\n$L[select]";$A=ME."table=".url_escape($D);$C=lang(203);$U=($_POST["materialized"]?"MATERIALIZED VIEW":"VIEW");if(!$_POST["drop"]&&$a==$D&&JUSH!="sqlite"&&$U=="VIEW"&&$mg=="VIEW")query_redirect((JUSH=="mssql"?"ALTER":"CREATE OR REPLACE")." VIEW ".table($D).$va,$A,$C);else{$Ai="adminer_".uniqid();drop_create("DROP $mg ".table($a),"CREATE $U ".table($D).$va,"DROP $U ".table($D),"CREATE $U ".table($Ai).$va,"DROP $U ".table($Ai),($_POST["drop"]?substr(ME,0,-1):$A),lang(204),$C,lang(205),$a,$D);}}if(!$_POST&&$a!=""){$L=view($a);$L["name"]=$a;$L["materialized"]=($mg!="VIEW");if(!$j)$j=error();}page_header(($a!=""?lang(38):lang(206)),$j,array("table"=>$a),h($a));echo'
<form action="" method="post">
<p>',lang(188),': <input name="name" value="',h($L["name"]),'" data-maxlength="64" autocapitalize="off">
',(support("materializedview")?" ".checkbox("materialized",1,$L["materialized"],lang(130)):""),'<p>';textarea("select",$L["select"]);echo'<p>
<input type=\'submit\' value=\'',lang(17),'\'>
';if($a!="")echo'<input type=\'submit\' name=\'drop\' value=\'',lang(127),'\'',confirm(lang(178,$a)),'>
';echo
input_token(),'</form>
';}elseif(isset($_GET["check"])){$a=$_GET["check"];$D=$_GET["name"];$L=$_POST;if($L&&!$j){if(JUSH=="sqlite")$J=recreate_table($a,$a,array(),array(),array(),"",array(),"$D",($L["drop"]?"":$L["clause"]));else{$J=($D==""||queries("ALTER TABLE ".table($a)." DROP CONSTRAINT ".idf_escape($D)));if(!$L["drop"])$J=queries("ALTER TABLE ".table($a)." ADD".($L["name"]!=""?" CONSTRAINT ".idf_escape($L["name"]):"")." CHECK ($L[clause])");}queries_redirect(ME."table=".url_escape($a),($L["drop"]?lang(207):($D!=""?lang(208):lang(209))),$J);}page_header(($D!=""?lang(210):lang(141)),$j,array("table"=>$a),h($D!=""?$D:$a));if(!$L){$Sa=driver()->checkConstraints($a);$L=array("name"=>$D,"clause"=>$Sa[$D]);}echo'
<form action="" method="post">
<p>';if(JUSH!="sqlite")echo
lang(188).': <input name="name" value="'.h($L["name"]).'" data-maxlength="64" autocapitalize="off"> ';echo
doc_link(array('sqlite'=>"lang_createtable.html#check_constraints",),"?"),'<p>';textarea("clause",$L["clause"]);echo'<p><input type=\'submit\' value=\'',lang(17),'\'>
';if($D!="")echo'<input type=\'submit\' name=\'drop\' value=\'',lang(127),'\'',confirm(lang(178,$D)),'>
';echo
input_token(),'</form>
';}elseif(isset($_GET["trigger"])){$a=$_GET["trigger"];$D="$_GET[name]";$Xi=trigger_options();$L=(array)trigger($D,$a)+array("Trigger"=>$a."_bi");if($_POST){if(!$j&&in_array($_POST["Timing"],$Xi["Timing"])&&in_array($_POST["Event"],$Xi["Event"])&&in_array($_POST["Type"],$Xi["Type"])){$Tf=" ON ".table($a);$ic="DROP TRIGGER ".idf_escape($D).(JUSH=="pgsql"?$Tf:"");$A=ME."table=".url_escape($a);if($_POST["drop"])query_redirect($ic,$A,lang(211));else{if($D!="")queries($ic);queries_redirect($A,($D!=""?lang(212):lang(213)),queries(create_trigger($Tf,$_POST)));if($D!="")queries(create_trigger($Tf,$L+array("Type"=>reset($Xi["Type"]))));}}$L=$_POST;}page_header(($D!=""?lang(214):lang(143)),$j,array("table"=>$a),h($D!=""?$D:$a));$Vi=on('change','triggerChange',"^".preg_quote($a,"/")."_[ba][iud]$",$a);echo'
<form action="" method="post" id="form">
<table class="layout">
<tr><th>',lang(215),'<td>',html_select("Timing",$Xi["Timing"],$L["Timing"],$Vi),'<tr><th>',lang(216),'<td>',html_select("Event",$Xi["Event"],$L["Event"],$Vi),(in_array("UPDATE OF",$Xi["Event"])?" <input name='Of' value='".h($L["Of"])."' class='hidden'>":""),'<tr><th>',lang(44),'<td>',html_select("Type",$Xi["Type"],$L["Type"]),'</table>
<p>',lang(188),': <input name="Trigger" value="',h($L["Trigger"]),'" data-maxlength="64" autocapitalize="off">
',script("fire(qs('#form')['Timing'], 'change');"),'<p>';textarea("Statement",$L["Statement"]);echo'<p>
<input type=\'submit\' value=\'',lang(17),'\'>
';if($D!="")echo'<input type=\'submit\' name=\'drop\' value=\'',lang(127),'\'',confirm(lang(178,$D)),'>
';echo
input_token(),'</form>
';}elseif(isset($_GET["select"])){$a=$_GET["select"];$S=table_status1($a);$w=indexes($a);$l=fields($a);$jd=column_foreign_keys($a);$Rf=$S["Oid"];$ja=get_settings("adminer_import");$wh=array();$e=array();$Dh=array();$eg=array();$Di=null;foreach($l
as$y=>$k){$D=adminer()->fieldName($k);$Df=html_entity_decode(strip_tags($D),ENT_QUOTES);if(isset($k["privileges"]["select"])&&$D!=""){$e[$y]=$Df;if(is_shortable($k))$Di=adminer()->selectLengthProcess();}if(isset($k["privileges"]["where"])&&$D!="")$Dh[$y]=$Df;if(isset($k["privileges"]["order"])&&$D!="")$eg[$y]=$Df;$wh+=$k["privileges"];}list($N,$r)=adminer()->selectColumnsProcess($e,$w);$N=array_unique($N);$r=array_unique($r);$qe=count($r)<count($N);$Z=adminer()->selectSearchProcess($l,$w);$F=adminer()->selectOrderProcess($l,$w);$z=adminer()->selectLimitProcess();if($_GET["val"]&&is_ajax()){header("Content-Type: text/plain; charset=utf-8");foreach($_GET["val"]as$gj=>$L){$va=convert_field($l[key($L)]);$N=array($va?:idf_escape(key($L)));$Z[]=where_check(bracket_escape($gj,true),$l);$K=driver()->select($a,$N,$Z,$N);if($K)echo
first($K->fetch_row());}exit;}$Wg=$ij=array();foreach($w
as$v){if($v["type"]=="PRIMARY"){$Wg=array_flip($v["columns"]);$ij=($N?$Wg:array());foreach($ij
as$y=>$X){if(in_array(idf_escape($y),$N))unset($ij[$y]);}break;}}if($Rf&&!$Wg){$Wg=$ij=array($Rf=>0);$w[]=array("type"=>"PRIMARY","columns"=>array($Rf));}if($_POST&&!$j){$Ij=$Z;if(!$_POST["all"]&&is_array($_POST["check"])){$Sa=array();foreach($_POST["check"]as$Pa)$Sa[]=where_check($Pa,$l);$Ij[]="((".implode(") OR (",$Sa)."))";}$Kj=$Ij;$Ij=($Ij?"\nWHERE ".implode(" AND ",$Ij):"");if($_POST["export"]){save_settings(array("output"=>$_POST["output"],"format"=>$_POST["format"]),"adminer_import");dump_headers($a);adminer()->dumpTable($a,"");$Fh=($N?:array("*"));$rb=convert_fields($e,$l,$N);if($rb)$Fh[]=substr($rb,2);$I="";if(is_array($_POST["check"])&&!$Wg){$od=implode(", ",$Fh)."\nFROM ".table($a);$vd=($r&&$qe?"\nGROUP BY ".implode(", ",$r):"").($F?"\nORDER BY ".implode(", ",$F):"");$ej=array();foreach($_POST["check"]as$X)$ej[]="(SELECT".limit($od,"\nWHERE ".($Z?implode(" AND ",$Z)." AND ":"").where_check($X,$l).$vd,1).")";$I=implode(" UNION ALL ",$ej);}adminer()->dumpData($a,"table",$I,$Fh,$Kj,($qe?$r:array()),$F);adminer()->dumpFooter();exit;}if(!adminer()->selectEmailProcess($Z,$jd)){if($_POST["save"]||$_POST["delete"]){$J=true;$ka=0;$P=array();if(!$_POST["delete"]){foreach($l
as$D=>$X){$u=bracket_escape($D);if(isset($_POST["fields"][$u])||$_FILES["fields-$u"]){$X=process_input($l[$D]);if($X!==null&&($_POST["clone"]||$X!==false))$P[idf_escape($D)]=($X!==false?$X:idf_escape($D));}}}if($_POST["delete"]||$P){$I=($_POST["clone"]?"INTO ".table($a)." (".implode(", ",array_keys($P)).")\nSELECT ".implode(", ",$P)."\nFROM ".table($a):"");if($_POST["all"]||($Wg&&is_array($_POST["check"]))||$qe){$J=($_POST["delete"]?driver()->delete($a,$Ij):($_POST["clone"]?queries("INSERT $I$Ij".driver()->insertReturning($a)):driver()->update($a,$P,$Ij)));$ka=connection()->affected_rows;if(is_object($J))$ka+=$J->num_rows;}else{foreach((array)$_POST["check"]as$X){$Hj="\nWHERE ".($Z?implode(" AND ",$Z)." AND ":"").where_check($X,$l);$J=($_POST["delete"]?driver()->delete($a,$Hj,1):($_POST["clone"]?queries("INSERT".limit1($a,$I,$Hj)):driver()->update($a,$P,$Hj,1)));if(!$J)break;$ka+=connection()->affected_rows;}}}$C=lang(217,$ka);if($_POST["clone"]&&$J&&$ka==1){$Ge=last_id($J);if($Ge)$C=lang(171," $Ge");}queries_redirect(remove_from_uri($_POST["all"]&&$_POST["delete"]?"page|next":""),$C,$J);if(!$_POST["delete"]){$Qg=(array)$_POST["fields"];edit_form($a,array_intersect_key($l,$Qg),$Qg,!$_POST["clone"],$j);page_footer();exit;}}elseif(!$_POST["import"]){$J=true;$ka=0;foreach((array)$_POST["val"]as$gj=>$L){$P=array();foreach($L
as$y=>$X){$y=bracket_escape($y,true);$P[idf_escape($y)]=(preg_match('~char|text~',$l[$y]["type"])||$X!=""?adminer()->processInput($l[$y],$X):"NULL");}$J=driver()->update($a,$P," WHERE ".($Z?implode(" AND ",$Z)." AND ":"").where_check(bracket_escape($gj,true),$l),($qe||$Wg?0:1)," ");if(!$J)break;$ka+=connection()->affected_rows;}queries_redirect(remove_from_uri(),lang(217,$ka),$J);}elseif(!is_string($m=get_file("csv_file",true)))$j=upload_error($m);elseif(!preg_match('~~u',$m))$j=lang(218);else{save_settings(array("output"=>$ja["output"],"format"=>$_POST["separator"]),"adminer_import");$cb=array_keys($l);$Jh=($_POST["separator"]=="csv"?",":($_POST["separator"]=="tsv"?"\t":";"));$_b=parse_csv($m,$Jh);$ka=count($_b);driver()->begin();$M=array();foreach($_b
as$y=>$yj){if(!$y&&!array_diff($yj,$cb)){$cb=$yj;$ka--;}else{$P=array();foreach($yj
as$s=>$Ya)$P[idf_escape($cb[$s])]=($Ya==""&&$l[$cb[$s]]["null"]?"NULL":q(csv_value($Ya)));$M[]=$P;}}$J=(!$M||driver()->insertUpdate($a,$M,$Wg));if($J)driver()->commit();queries_redirect(remove_from_uri("page|next"),lang(219,$ka),$J);driver()->rollback();}}}$ri=adminer()->tableName($S);if(is_ajax()){page_headers();ob_start();}else
page_header(lang(48).": $ri",$j);$P=null;if(isset($wh["insert"])||!support("table")){$P="";foreach((array)$_GET["where"]as$X){$Y=$X["val"];if(is_array($Y))$Y=(count($Y)==1&&preg_match('~^val-(.*)~s',reset($Y),$B)?$B[1]:"");if($X["col"]!=""&&$Y!=""&&($X["op"]=="="||(!$X["op"]&&(is_array($X["val"])||!preg_match('~[_%]~',$Y)))))$P
.="&set[".url_escape(bracket_escape($X["col"]))."]=".url_escape($Y);}}adminer()->selectLinks($S,$P);if(!$e&&support("table"))echo"<p class='error'>".lang(220).($l?".":": ".error())."\n";else{echo"<form action='' id='form'>\n","<div hidden>";hidden_fields_get();echo(DB!=""?input_hidden("db",DB).(isset($_GET["ns"])?input_hidden("ns",$_GET["ns"]):""):""),input_hidden("select",$a),"</div>\n";adminer()->selectColumnsPrint($N,$e);adminer()->selectSearchPrint($Z,$Dh,$w);adminer()->selectOrderPrint($F,$eg,$w);adminer()->selectLimitPrint($z);if($Di!==null)adminer()->selectLengthPrint($Di);adminer()->selectActionPrint($w);echo"</form>\n";foreach((array)$_GET["where"]as$X){if($X["op"]=="SQL"&&!in_array($_SERVER["HTTP_SEC_FETCH_SITE"],array("","same-origin"))){echo"<p class='error'>".lang(97).' '.lang(98)."\n";page_footer();exit;}}$G=$_GET["page"];$md=null;if($G=="last"){$md=get_val(count_rows($a,$Z,$qe,$r));$G=floor(max(0,intval($md)-1)/$z);}$Eh=$N;$ud=$r;if(!$Eh){$Eh[]="*";$rb=convert_fields($e,$l,$N);if($rb)$Eh[]=substr($rb,2);}foreach($N
as$y=>$X){$k=$l[idf_unescape($X)];if($k&&($va=convert_field($k)))$Eh[$y]="$va AS $X";}if(JUSH=="pgsql"||JUSH=="mssql"){foreach((array)$_GET["columns"]as$y=>$X){if(isset($Eh[$y])&&$X["fun"])$Eh[$y].=" AS ".idf_escape(apply_sql_function($X["fun"],($X["col"]!=""?$X["col"]:"*")));}}if(!$qe&&$ij){foreach($ij
as$y=>$X){$Eh[]=idf_escape($y);if($ud)$ud[]=idf_escape($y);}}$J=driver()->select($a,$Eh,$Z,$ud,$F,$z,$G,true);if(!is_object($J))echo"<p class='error'>".(error()?:lang(25))."\n";else{if(JUSH=="mssql"&&$G)$J->seek($z*$G);$uc=array();$M=array();while($L=$J->fetch_assoc()){if($G&&JUSH=="oracle")unset($L["RNUM"]);$M[]=$L;}$Cd=($z&&(support("cursor")?$_GET["next"]!="":count($M)>=$z));if(is_ajax()&&$Cd)header("X-Next-Page: ".pagination_href($G+1));if($_GET["modify"]&&$M){$hf=max_input_vars(count($M[0])+1,20);echo($hf&&count($M)>$hf?"<p class='error'>".max_input_vars_error()."\n":"");}echo"<form action='' method='post' enctype='multipart/form-data'>\n";if($_GET["page"]!="last"&&$z&&$r&&$qe&&JUSH=="sql")$md=get_val(" SELECT FOUND_ROWS()");if(!$M)echo"<p class='message'>".lang(15)."\n";else{$Da=adminer()->backwardKeys($a,$ri);echo"<div class='scrollable'>","<table id='table' class='nowrap checkable odds'".on('click','tableClick').on('dblclick','tableClick').on('keydown','editingKeydown').">\n","<thead><tr>".(!$r&&$N?"":"<td class='hover check'><input type='checkbox' id='all-page' class='jsonly' title='".lang(221)."'".on('click','formCheck','^check').">");$Ef=array();$rd=array();reset($N);$ih=1;foreach($M[0]as$y=>$X){if(!isset($ij[$y])){$X=idx($_GET["columns"],key($N))?:array();$k=$l[$N?($X?$X["col"]:current($N)):$y];$D=($k?adminer()->fieldName($k,$ih):($X["fun"]?"*":h($y)));if($D!=""){$ih++;$Ef[$y]=$D;$d=idf_escape($y);$Nd=remove_from_uri('(order|desc)[^=]*|page|next').'&order[0]='.url_escape($y);$Qb="&desc[0]=1";$Th=preg_replace('~ DESC( NULLS LAST)?$~','',$F[0]);$Vh=($Th==$d||$Th==$y);echo"<th id='th[".h(bracket_escape($y))."]'".($Vh?" aria-sort='".($Th==$F[0]?"ascending":"descending")."'":"").">";$qd=apply_sql_function($X["fun"],$D);$Uh=isset($k["privileges"]["order"])||$qd!=$D;echo($Uh?"<a href='".h($Nd.($Vh&&$Th==$F[0]?$Qb:''))."'>$qd</a>":$qd);$of=($Uh?"<a href='".h($Nd.$Qb)."' title='".lang(54)."' class='text'> ↓</a>":'');if(!$X["fun"]&&isset($k["privileges"]["where"]))$of
.="<a href='#fieldset-search' title='".lang(51)."' class='text jsonly'".on('click','selectSearch',$y)."> =</a>";echo($of?"<span class='column'>$of</span>":"");}$rd[$y]=$X["fun"];next($N);}}$Oe=array();if($_GET["modify"]){foreach($M
as$L){foreach($L
as$y=>$X)$Oe[$y]=max($Oe[$y],min(40,strlen(utf8_decode($X))));}}echo($Da?"<th>".lang(222):"")."<tbody>\n";if(is_ajax())ob_end_clean();foreach(adminer()->rowDescriptions($M,$jd)as$Cf=>$L){$fj=unique_array($M[$Cf],$w);if(!$fj){$fj=array();reset($N);foreach($M[$Cf]as$y=>$X){if(!preg_match('~^(COUNT|AVG|GROUP_CONCAT|MAX|MIN|SUM)\(~',current($N)))$fj[$y]=$X;next($N);}}$gj="";foreach($fj
as$y=>$X){$k=(array)$l[$y];$pe=is_blob($k);if((JUSH=="sql"||JUSH=="pgsql")&&($pe||preg_match('~char|text|enum|set~',$k["type"]))&&strlen($X)>64){$y=(strpos($y,'(')?$y:idf_escape($y));$y="MD5(".($pe||JUSH!='sql'||preg_match("~^utf8~",$k["collation"])?$y:"CONVERT($y USING ".charset(connection()).")").")";$X=md5($pe?(string)driver()->value($X,$k):$X);}$gj
.="&".($X!==null?"where[".url_escape(bracket_escape($y))."]=".url_escape($X===false?"f":$X):"null[]=".url_escape($y));}echo"<tr>".(!$r&&$N?"":"<td class='hover check'>".($qe||information_schema(DB)?"":"<a href='".h(ME."edit=".url_escape($a).$gj)."' class='edit'>".lang(223)."</a> ").checkbox("check[]",substr($gj,1),in_array(substr($gj,1),(array)$_POST["check"])));reset($N);foreach($L
as$y=>$X){if(isset($Ef[$y])){$d=current($N);$k=(array)$l[$y];if($X!=""&&(!isset($uc[$y])||$uc[$y]!=""))$uc[$y]=(is_mail($X)?$Ef[$y]:"");$_="";if(is_blob($k)&&$X!="")$_=ME.'download='.url_escape($a).'&field='.url_escape($y).$gj;if(!$_&&$X!==null){foreach((array)$jd[$y]as$o){if(count($jd[$y])==1||end($o["source"])==$y){$_="";foreach($o["source"]as$s=>$Wh)$_
.=where_link($s,$o["target"][$s],$M[$Cf][$Wh]);$_=($o["db"]!=""?preg_replace('~([?&]db=)[^&]+~','\1'.url_escape($o["db"]),ME):ME).'select='.url_escape($o["table"]).$_;if($o["ns"])$_=preg_replace('~([?&]ns=)[^&]+~','\1'.url_escape($o["ns"]),$_);if(count($o["source"])==1)break;}}}if($d=="COUNT(*)"){$_=ME."select=".url_escape($a);$s=0;foreach((array)$_GET["where"]as$W){if(!array_key_exists($W["col"],$fj))$_
.=where_link($s++,$W["col"],$W["val"],$W["op"]);}foreach($fj
as$xe=>$W)$_
.=where_link($s++,$xe,$W);}$Od=select_value($X,$_,$k,$Di);$u=bracket_escape($gj);$t=h("val[$u][".bracket_escape($y)."]");$Sg=idx(idx($_POST["val"],$u),bracket_escape($y));$lj=idx($k["privileges"],"update");$qc=!is_array($L[$y])&&!is_blob($k)&&is_utf8($X)&&$M[$Cf][$y]==$X&&!$rd[$y]&&!$k["generated"]&&$lj;$U=(preg_match('~^(AVG|MIN|MAX)\((.+)\)~',$d,$B)?$l[idf_unescape($B[2])]["type"]:$k["type"]);$Ci=preg_match('~text|json|lob~',$U);$re=preg_match(number_type(),$U)||preg_match('~^(CHAR_LENGTH|ROUND|FLOOR|CEIL|TIME_TO_SEC|COUNT|SUM)\(~',$d);echo"<td id='$t'".($re&&($X===null||is_numeric(strip_tags($Od))||$U=="money")?" class='number'":"");if(($_GET["modify"]&&$qc&&$X!==null)||$Sg!==null){$yd=h($Sg!==null?$Sg:$X);echo">".($Ci?"<textarea name='$t' cols='30' rows='".(substr_count($X,"\n")+1)."'>$yd</textarea>":"<input name='$t' value='$yd' size='$Oe[$y]'>");}else{$We=strpos($Od,"<i>…</i>");echo($lj?" data-text='".($We?2:($Ci?1:0))."'".($qc?"":" data-warning='".lang(224)."'"):"").">$Od";}}next($N);}if($Da)echo"<td>";adminer()->backwardKeysPrint($Da,$M[$Cf]);echo"</tr>\n";}if(is_ajax())exit;echo"</table>\n","</div>\n";}if(!is_ajax()){if($M||$G||$Cd){$Gc=true;if($_GET["page"]!="last"){if(!$z||(count($M)<$z&&($M||!$G)))$md=($G?$G*$z:0)+count($M);elseif(JUSH!="sql"||!$qe){$md=($qe?false:found_rows($S,$Z));if(intval($md)<max(1e4,2*($G+1)*$z))$md=first(slow_query(count_rows($a,$Z,$qe,$r)));elseif(JUSH=='sql'||JUSH=='pgsql')$Gc=false;}}if(!support("cursor"))$Cd=(($md===false?count($M)+1:$md-$G*$z)>$z);$ug=($z&&($Cd||$G));if($ug)echo($Cd?'<p><a href="'.h(pagination_href($G+1)).'" class="loadmore"'.on('click','selectLoadMore',lang(225)).'>'.lang(226).'</a>':''),"\n";echo"<div class='footer'><div>\n";if($ug){$ff=($md===false?$G+($M?(count($M)>=$z?2:1):0):floor(($md-1)/$z));echo"<fieldset><legend>".lang(227)."</legend>";if(!support("cursor")){echo
pagination(0,$G).($G>5?" …":"");for($s=max(1,$G-4);$s<min($ff,$G+5);$s++)echo
pagination($s,$G);if($ff>0)echo($G+5<$ff?" …":""),($Gc&&$md!==false?pagination($ff,$G):" <a href='".h(remove_from_uri("page")."&page=last")."' title='~$ff'>".lang(228)."</a>");}else
echo
pagination(0,$G).($G>1?" …":""),($G?pagination($G,$G):""),($Cd?pagination($G+1,$G)." …":"");echo"</fieldset>\n";}echo"<fieldset>","<legend>".lang(229)."</legend>";$Yb=($Gc?"":"~ ").$md;$Ae=($md!==false?($Gc?"":"~ ").lang(154,$md):"");echo
checkbox("all",1,0,$Ae,on('click','countRows',$Yb))."\n","</fieldset>\n";if(adminer()->selectCommandPrint())echo'<fieldset',($_GET["modify"]?'':" title='".lang(230)."'"),'>
<legend><a href=\'',h($_GET["modify"]?remove_from_uri("modify"):relative_uri()."&modify=1"),'\'>',lang(231),'</a></legend><div>
<input type=\'submit\' id=\'save\' value=\'',lang(17),'\'',($_GET["modify"]?'':" class='jsonly' disabled"),'>
</div></fieldset>

<fieldset><legend>',lang(126),' <span id="selected"></span></legend><div>
<input type=\'submit\' name=\'edit\' value=\'',lang(13),'\'>
<input type=\'submit\' name=\'clone\' value=\'',lang(232),'\'>
<input type=\'submit\' name=\'delete\' value=\'',lang(21),'\'',confirm(),'>
</div></fieldset>
';$kd=adminer()->dumpFormat();foreach((array)$_GET["columns"]as$d){if($d["fun"]){unset($kd['sql']);break;}}if($kd){print_fieldset("export",lang(71)." <span id='selected2'></span>");$rg=adminer()->dumpOutput();echo($rg?html_select("output",$rg,$ja["output"])." ":""),html_select("format",$kd,$ja["format"])," <input type='submit' name='export' value='".lang(71)."'>\n","</div></fieldset>\n";}adminer()->selectEmailPrint(array_filter($uc,'strlen'),$e);echo"</div></div>\n";}if(adminer()->selectImportPrint())echo"<p>","<a href='#import' class='toggle'>".lang(70)."</a>","<span id='import'".($_POST["import"]?"":" class='hidden'").">: ",file_input(" name='csv_file'"," ".html_select("separator",array("csv"=>"CSV,","csv;"=>"CSV;","tsv"=>"TSV"),$ja["format"])." <input type='submit' name='import' value='".lang(70)."'>"),"</span>";echo
input_token(),"</form>\n",(!$r&&$N?"":script("tableCheck();"));}}}if(is_ajax()){ob_end_clean();exit;}}elseif(isset($_GET["variables"])){$ei=isset($_GET["status"]);page_header($ei?lang(118):lang(117));$zj=($ei?adminer()->showStatus():adminer()->showVariables());if(!$zj)echo"<p class='message'>".lang(15)."\n";else{echo"<table>\n";foreach($zj
as$L){echo"<tr>";$y=array_shift($L);echo"<th><code class='jush-".JUSH.($ei?"status":"set")."'>".h($y)."</code>";foreach($L
as$X)echo"<td>".nl_br(h($X));}echo"</table>\n";}}elseif(isset($_GET["script"])){header("Content-Type: application/json; charset=utf-8");if($_GET["script"]=="db"){$mi=array("Data_length"=>0,"Index_length"=>0,"Data_free"=>0);foreach(table_status()as$D=>$S){json_row("Comment-$D",h($S["Comment"]));if(!is_view($S)||preg_match('~materialized~i',$S["Engine"])){foreach(array("Engine","Collation")as$y)json_row("$y-$D",h($S[$y]));foreach(array_keys($mi+array("Auto_increment"=>0,"Rows"=>0))as$y){if(array_key_exists($y,$S))json_row("$y-$D",format_status($S,$y));if($S[$y]!=""&&isset($mi[$y]))$mi[$y]+=($S["Engine"]!="InnoDB"||$y!="Data_free"?$S[$y]:0);}}}if(function_exists('Adminer\db_status'))$mi=db_status();foreach($mi
as$y=>$X)json_row("sum-$y",format_number($X));json_row("");}elseif($_GET["script"]=="kill")connection()->query("KILL ".number($_POST["kill"]));else{foreach(count_tables(adminer()->databases(false))as$h=>$X){json_row("tables-$h",$X);json_row("size-$h",db_size($h));}json_row("");}exit;}else{$xi=array_merge((array)$_POST["tables"],(array)$_POST["views"]);if($xi&&!$j&&!$_POST["search"]){$J=true;$C="";if(JUSH=="sql"&&$_POST["tables"]&&count($_POST["tables"])>1&&($_POST["drop"]||$_POST["truncate"]||$_POST["copy"]))queries("SET foreign_key_checks = 0");if($_POST["truncate"]){if($_POST["tables"])$J=truncate_tables($_POST["tables"]);$C=lang(233);}elseif($_POST["move"]){$J=move_tables((array)$_POST["tables"],(array)$_POST["views"],$_POST["target"]);$C=lang(234);}elseif($_POST["copy"]){$J=copy_tables((array)$_POST["tables"],(array)$_POST["views"],$_POST["target"]);$C=lang(235);}elseif($_POST["drop"]){if($_POST["views"])$J=drop_views($_POST["views"]);if($J&&$_POST["tables"])$J=drop_tables($_POST["tables"]);$C=lang(236);}elseif(JUSH=="sqlite"&&$_POST["check"]){foreach((array)$_POST["tables"]as$R){foreach(get_rows("PRAGMA integrity_check(".q($R).")")as$L)$C
.="<b>".h($R)."</b>: ".h($L["integrity_check"])."<br>";}}elseif(JUSH!="sql"){$J=(JUSH=="sqlite"?queries("VACUUM"):apply_queries("VACUUM".($_POST["optimize"]?" ANALYZE":""),(array)$_POST["tables"]));$C=lang(237);}elseif(!$_POST["tables"])$C=lang(12);elseif($J=queries(($_POST["optimize"]?"OPTIMIZE":($_POST["check"]?"CHECK":($_POST["repair"]?"REPAIR":"ANALYZE")))." TABLE ".implode(", ",array_map('Adminer\idf_escape',$_POST["tables"])))){while($L=$J->fetch_assoc())$C
.="<b>".h($L["Table"])."</b>: ".h($L["Msg_text"])."<br>";}queries_redirect($_SERVER["REQUEST_URI"],$C,$J);}page_header(($_GET["ns"]==""?lang(32).": ".h(DB):lang(150).": ".h($_GET["ns"])),$j,true);if(adminer()->homepage()){if($_GET["ns"]!==""){$F=$_GET["order"];$pd=($F||support("fast_status"));echo"<div>\n","<h3 id='tables-views'>".lang(238)."</h3>\n";$wi=($pd?table_status():tables_list());if(!$wi)echo"<p class='message'>".lang(12)."\n";else{echo"<form action='' method='post'>\n";if(support("table")){echo"<fieldset><legend>".lang(239)." <span id='selected2'></span></legend><div>",html_select("op",adminer()->operators(),idx($_POST,"op",JUSH=="elastic"?"should":"LIKE %%"))," <input type='search' name='query' value='".h($_POST["query"])."'".on('keydown','submitKeydown','search').">"," <input type='submit' name='search' value='".lang(51)."'>\n","</div></fieldset>\n";if(!$j&&$_POST["search"]&&$_POST["query"]!=""){$_GET["where"][0]["op"]=$_POST["op"];search_tables();}}echo"<div class='scrollable'>\n","<table class='nowrap checkable odds'".on('click','tableClick').on('dblclick','tableClick').">\n",'<thead><tr class="wrap">','<td class="hover"><input id="check-all" type="checkbox" class="jsonly" title="'.lang(149).'"'.on('click','formCheck','^(tables|views)\[').'>','<th'.(!$F&&JUSH!='sqlite'?" aria-sort='ascending'":'').'><a href="'.h(substr(ME,0,-1)).'">'.lang(132).'</a>';$e=array("Engine"=>array(lang(240).''));if(collations())$e["Collation"]=array(lang(122).'');if(function_exists('Adminer\alter_table'))$e["Data_length"]=array(lang(241).'',"create",lang(39),);if(support("indexes"))$e["Index_length"]=array(lang(242).'',"indexes",lang(135),);$e["Data_free"]=array(lang(243).'',"edit",lang(40));if(function_exists('Adminer\alter_table'))$e["Auto_increment"]=array(lang(46).'',"auto_increment=1&create",lang(39),);$e["Rows"]=array(lang(244).'',"select",lang(36),);if(support("comment"))$e["Comment"]=array(lang(45).'');$wa=array('Engine','Collation','Comment');foreach($e
as$y=>$d)echo"<th".($F==$y?" aria-sort='".(in_array($y,$wa)?"ascending":"descending")."'":"")."><a href='".h(ME)."order=$y'>$d[0]</a>";echo"<tbody>\n";if($F){uasort($wi,function($da,$Aa)use($F,$wa){$K=($da[$F]<$Aa[$F]?-1:($da[$F]>$Aa[$F]?1:0));return(in_array($F,$wa)?$K:-$K);});}$T=0;$mi=array("Data_length"=>0,"Index_length"=>0,"Data_free"=>0);foreach($wi
as$D=>$ei){$Bj=($pd?is_view($ei):$ei!==null&&!preg_match('~table|sequence~i',$ei));$ei=($pd?$ei:array('Engine'=>$ei));$t=h("Table-".$D);echo'<tr><td class="hover">'.checkbox(($Bj?"views[]":"tables[]"),$D,in_array("$D",$xi,true),"","","",$t),'<th>'.(support("table")||support("indexes")?"<a href='".h(ME)."table=".url_escape($D)."' title='".lang(37)."' id='$t'>".h($D).'</a>':h($D));if($Bj&&!preg_match('~materialized~i',$ei['Engine'])){$Hi=lang(131);echo'<td colspan="'.(count($e)-(support("comment")?2:1)).'">'.(support("view")?"<a href='".h(ME)."view=".url_escape($D)."' title='".lang(38)."'>$Hi</a>":$Hi),"<td align='right'><a href='".h(ME)."select=".url_escape($D)."' title='".lang(36)."'>?</a>";if(support("comment"))echo'<td>'.h($ei['Comment']);}else{if($pd){foreach(array_keys($mi)as$y)$mi[$y]+=($ei["Engine"]!="InnoDB"||$y!="Data_free"?idx($ei,$y):0);}foreach($e
as$y=>$d){$t=" id='$y-".h($D)."'";echo($d[1]?"<td align='right'><a href='".h(ME."$d[1]=").url_escape($D)."'$t title='$d[2]'>".format_status($ei,$y)."</a>":"<td$t>".h(idx($ei,$y,'?')));}$T++;}echo"\n";}echo"<tr><td class='hover'><th>".lang(245,count($wi)),"<td>".h(JUSH=="sql"?get_val("SELECT @@default_storage_engine"):""),(collations()?"<td>".h(db_collation(DB,collations())):'');if($pd&&function_exists('Adminer\db_status'))$mi=db_status();foreach($mi
as$y=>$li)echo($e[$y]?"<td align='right' id='sum-$y'>".($pd?format_number($li):""):"");echo"\n","</table>\n",($pd?'':script("ajaxSetHtml('".js_escape(ME)."script=db');")),"</div>\n";if(!information_schema(DB)){$wj="<input type='submit' value='".lang(246)."'".on_help("VACUUM")."> ";$ag="<input type='submit' name='optimize' value='".lang(247)."'".on_help(JUSH=="sql"?"OPTIMIZE TABLE":"VACUUM ANALYZE")."> ";$Yg=(JUSH=="sqlite"?$wj."<input type='submit' name='check' value='".lang(248)."'".on_help("PRAGMA integrity_check")."> ":(JUSH=="pgsql"?$wj.$ag:(JUSH=="sql"?"<input type='submit' value='".lang(249)."'".on_help("ANALYZE TABLE")."> ".$ag."<input type='submit' name='check' value='".lang(248)."'".on_help("CHECK TABLE")."> "."<input type='submit' name='repair' value='".lang(250)."'".on_help("REPAIR TABLE")."> ":""))).(function_exists('Adminer\truncate_tables')?"<input type='submit' name='truncate' value='".lang(251)."'".confirm().on_help(JUSH=="sqlite"?"DELETE":"TRUNCATE".(JUSH=="pgsql"?"":" TABLE"))."> ":"").(function_exists('Adminer\drop_tables')?"<input type='submit' name='drop' value='".lang(127)."'".confirm().on_help("DROP TABLE").">":"");echo($Yg?"<div class='footer'><div>\n<fieldset><legend>".lang(126)." <span id='selected'></span></legend><div>$Yg\n</div></fieldset>\n":"");$g=(support("scheme")?adminer()->schemas():adminer()->databases());if(count($g)!=1&&function_exists('Adminer\move_tables')){echo"<fieldset><legend>".lang(252)." <span id='selected3'></span></legend><div>";$h=(isset($_POST["target"])?$_POST["target"]:(support("scheme")?$_GET["ns"]:DB));echo($g?html_select("target",$g,$h):'<input name="target" value="'.h($h).'" autocapitalize="off">'),"</label> <input type='submit' name='move' value='".lang(110)."'>",(support("copy")?" <input type='submit' name='copy' value='".lang(22)."'> ".checkbox("overwrite",1,$_POST["overwrite"],lang(253)):""),"</div></fieldset>\n";}echo"<input type='hidden' name='all' value=''".on('click','countTables',$T).">\n",input_token(),"</div></div>\n";}echo"</form>\n",script("tableCheck();");}echo(function_exists('Adminer\alter_table')?"<p class='links hover'><a href='".h(ME)."create='>".lang(72)."</a>\n":''),(support("view")?"<a href='".h(ME)."view='>".lang(206)."</a>\n":""),"</div>\n";}}}page_footer();