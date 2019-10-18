<?php
if(!defined('XMLCMS')) exit();
function resize_image($file, $w, $h, $type="", $crop=FALSE) {
    list($width, $height) = getimagesize($file);
    $r = $width / $height;
    if ($crop) {
        if ($width > $height) {
            $width = ceil($width-($width*abs($r-$w/$h)));
        } else {
            $height = ceil($height-($height*abs($r-$w/$h)));
        }
        $newwidth = $w;
        $newheight = $h;
    } else {
        if ($w/$h > $r) {
            $newwidth = $h*$r;
            $newheight = $h;
        } else {
            $newheight = $w/$r;
            $newwidth = $w;
        }
    }
	if($type=="image/png") {
		$src = imagecreatefrompng($file);
		imagesavealpha($src, true);
	}
	elseif($type=="image/jpeg") $src = imagecreatefromjpeg($file);
	else print "error type file";
    $dst = imagecreatetruecolor($newwidth, $newheight);
	if($type=="image/png") {
		$background = imagecolorallocatealpha($dst, 255, 255, 255, 127);
		imagecolortransparent($dst, $background);
		imagealphablending($dst, false);
		imagesavealpha($dst, true);
	}
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

    return $dst;
}

function register_user($login,$email,$password,$mac="",$role=4,$dateto=0){
	global $dbprefix,$secret;
	mysql_query("insert into {$dbprefix}users set login='".mysql_real_escape_string($login)."', email='".mysql_real_escape_string($email)."', password='".mysql_real_escape_string(md5($secret.$password))."', mac='".mysql_real_escape_string($mac)."', role='".mysql_real_escape_string($role)."', dateto='".mysql_real_escape_string($dateto)."'");
	print mysql_error();
	return 1;
}

function auth_user_sid($sid){
	global $dbprefix,$secret,$logged,$userinfo,$ip;
	if(empty($sid)) return false; 
	$q=q("select * from {$dbprefix}sessions as s,{$dbprefix}users as u where s.sid='".mysql_real_escape_string($sid)."' and s.userid=u.id",1);
	if(!isset($q[0])) return false;
	else{
		mysql_query("update {$dbprefix}sessions set ip='$ip',mac='".mysql_real_escape_string($_GET["box_mac"])."',ua='".mysql_real_escape_string($_SERVER['HTTP_USER_AGENT'])."',time=".time()." where id=$q[id]");
		$logged=true;
		$userinfo=$q;
	}
}
function auth_user_hash($email,$hash){
	global $dbprefix,$secret,$logged,$userinfo,$ip,$_PL,$_CH,$_ISPC,$siteurl;
	if(empty($email)||strlen($hash)!=32) return false; 
	$q=q("select * from {$dbprefix}users where (email='".mysql_real_escape_string($email)."' or forkplayerid='".mysql_real_escape_string($email)."') and hash='".mysql_real_escape_string($hash)."'",1);
	if(!isset($q[0])) return false;
	else{
		$sid=md5($secret.time().$ip);
		mysql_query("insert into {$dbprefix}sessions set userid=$q[id], ip='$ip',mac='".mysql_real_escape_string($_GET["box_mac"])."',ua='".mysql_real_escape_string($_SERVER['HTTP_USER_AGENT'])."', sid='$sid',time=".time()."");
		$logged=true;
		$userinfo=$q;
		setcookie("user",$login,time()+14*24*3600);
		setcookie("sid",$sid,time()+14*24*3600);
		if($_ISPC) header("Location: $siteurl/");
		else {
			$_PL["setcookie"]["sid"]=$sid;
			$_CH[]=["location"=>1,"title"=>"Нажмите для переадресации...","playlist_url"=>"$siteurl/"];
		}
	}
}
function auth_user($login,$password,$sid=""){
	global $dbprefix,$secret,$logged,$userinfo,$ip,$_PL,$_CH,$_ISPC,$siteurl;
	$q=q("select * from {$dbprefix}users where login='".mysql_real_escape_string($login)."' and password='".mysql_real_escape_string(md5($secret.$password))."'",1);
	if(!isset($q[0])) {
		$_PL["info"]="Неверный логин или пароль!";
		return "Неверный логин или пароль!";
	}
	else{
		$sid=md5($secret.time().$ip);
		mysql_query("insert into {$dbprefix}sessions set userid=$q[id], ip='$ip',mac='".mysql_real_escape_string($_GET["box_mac"])."',ua='".mysql_real_escape_string($_SERVER['HTTP_USER_AGENT'])."', sid='$sid',time=".time()."");
		$logged=true;
		$userinfo=$q;
		setcookie("user",$login,time()+14*24*3600);
		setcookie("sid",$sid,time()+14*24*3600);
		if($_ISPC) header("Location: $siteurl/");
		else {
			$_PL["setcookie"]["sid"]=$sid;
			$_CH[]=["location"=>1,"title"=>"Нажмите для переадресации...","playlist_url"=>"$siteurl/"];
		}
	}
} 
function get_search($text){
	global $dbprefix,$ROLE,$logged,$userinfo,$initial,$_ISPC,$siteurl;
	$q=q("select * from {$dbprefix}category where title like '%".mysql_real_escape_string($text)."%'");
	$q2=q("select * from {$dbprefix}page where title like '%".mysql_real_escape_string($text)."%'");
	$q3=q("select * from {$dbprefix}page where description like '%".mysql_real_escape_string($text)."%'");	
	 
	$q4=q("select * from {$dbprefix}meta where `key`='PluginMeta_userlink_links' and (src RLIKE '\"title\":\"[^\"]*".mysql_real_escape_string($text)."' or src RLIKE '\"playlist_url\":\"[^\"]*".mysql_real_escape_string($text)."')");

	//mysql_query("SET NAMES utf8");
	$qmb=q("SELECT *  FROM `{$dbprefix}page` WHERE LOWER(`src`) RLIKE '\\\"title\\\":\\\"[^\\\"]*".mysql_real_escape_string(mb_strtolower($text,'UTF-8'))."'");
	for($i=0;$i<count($qmb);$i++){
		$tmp=json_decode($qmb[$i]["src"],true);
		foreach($tmp["channels"] as $k=>$v){
			if(mb_stripos($v["title"],$text,null,'UTF-8')!==false){
				$v["menu"][]=["logo_30x30"=>$tmp["icon"],"title"=>"На страницу ".$tmp["title"],"playlist_url"=>"$siteurl/?do=/fml&id=".$qmb[$i]["id"]];
				$v["description"]="Найдено на странице сайта<br>".$v["description"];
				$r[]=$v;
			}
		}
	}

	
	for($i=0;$i<count($q);$i++){
		$r[]=["logo_30x30"=>$q[$i]["icon"],"title"=>$q[$i]["title"],"playlist_url"=>"$siteurl/?do=/category&id=".$q[$i]["id"],"description"=>"Найдено в категориях сайта"];
	}	
	$ide=",";
	for($i=0;$i<count($q2);$i++){
		$r[]=["logo_30x30"=>$q2[$i]["icon"],"title"=>$q2[$i]["title"],"playlist_url"=>"$siteurl/?do=/fml&id=".$q2[$i]["id"],"description"=>"Найдено в страницах сайта по названию<br>".$q2[$i]["description"]];
		$ide.=$q2[$i]["id"].",";
	}
	for($i=0;$i<count($q3);$i++){
		if(strpos($ide,",".$q3[$i]["id"].",")!==false) continue;
		$r[]=["logo_30x30"=>$q3[$i]["icon"],"title"=>$q3[$i]["title"],"playlist_url"=>"$siteurl/?do=/fml&id=".$q3[$i]["id"],"description"=>"Найдено в страницах сайта по описанию<br>".$q3[$i]["description"]];
		$ide.=$q3[$i]["id"].",";
	}
	for($i=0;$i<count($q4);$i++){
		$j=json_decode($q4[$i]["src"],true);
		$reports="";
		foreach($j["reports"] as $k=>$v) $reports.="<div style='margin:2px;font-size:90%;background-color:gray;color:white;'>".date("d.m.Y H:i",$v["date"])."<br><i>".($v["login"]!=null?$v["login"]:$v["ip"])." пишет</i> $v[text]</div>";
		$desc="Просмотров: ".$j["views"]."<br>$reports
			<div style='margin:2px;font-size:90%;background-color:gray;color:white;'>".date("d.m.Y H:i",$j["date"])."<br>
			".($j!=null?$j["login"]:$j["ip"])." загрузил ссылку/файл</div>";
			
			
		$r[]=["logo_30x30"=>$j["logo_30x30"],"title"=>$j["title"],"playlist_url"=>$j["playlist_url"],"description"=>"Найдено в плагине Пользовательские ссылки по названию<br>".$desc];
	}
	return $r;
}
function get_page($id){
	global $_PL,$dbprefix,$ROLE,$logged,$userinfo,$initial,$_ISPC;
	$q=q("select * from {$dbprefix}page where id='".mysql_real_escape_string(intval($id))."'",1);
		
	$pr=getArrayRolesByStringCats($q["category"]);
	$ur=getArrayAvailableUserRoles();
	if(!empty($q["access"])&&strpos($q["access"],",0,")===false){		
		$t=explode(",",$q["access"]);
		foreach($t as $k=>$v) if($v!="") $d[]=$v;
		$pr[0]=$d;
	}
	//print_r($pr);print_r($ur);
	$dst=false;
	$q["webplayer"]=$pr[1];
	$q["onlymac"]=$pr[2];
	$q["onlyfid"]=$pr[3];
	if($_ISPC&&$q["onlymac"]) return ["src"=>'{"channels":[{"title":"Просмотр страниц с этого раздела не доступно через WEB версию!","description":"'.$q['description'].'"}]}'];
	if(empty($userinfo["forkplayerid"])&&$q["onlyfid"]) return ["src"=>'{"channels":[{"title":"Просмотр страниц с этого раздела только для ForkPlayerID! Выйдите и авторизуйтесь с помощью ForkPlayerID","description":"'.$q['description'].'"}]}'];
	$dd="";
	foreach($pr[0] as $k=>$v){
		$d="";
		foreach($ur as $kk=>$vv) {
			if($v==$vv) {
				mysql_query("update {$dbprefix}page set `view`=`view`+1 where id='".mysql_real_escape_string(intval($id))."'");
				return $q;
			}
		}
		$dd.=$ROLE[$v]." ";
	}
			
	$_PL["info"]="Нет доступа!<div style='text-align:left;padding:3px;'>".$q["title"]." доступна только для <span style='color:red;'>$dd</span><br>Ваш текущий доступ: ".getmyrolesText()."<br>Ваш МАК адрес: $_GET[box_mac]<br>Ваш логин: ".(isset($userinfo["login"])?$userinfo["login"]:"Нет.<i>(Доступно после авторизации)</i>")."<br>Ваш ForkPlayerID: ".(isset($userinfo["forkplayerid"])?$userinfo["forkplayerid"]:"Нет.<i>(Доступно после авторизации и привязки в профиле)</i>")."</div><div style='text-align:center;'></div>";
	//print_r($_PL);
	$q["src"]='{"channels":[{"title":"У вас недостаточно прав для просмотра этой страницы!","description":"'.$q["description"].'"}]}';
	return $q;
}
function getmyrolesText(){
	global $ROLE,$roleEstimated,$roleTo;
	$d="";
	$ur=getArrayAvailableUserRoles();
	asort($ur);
	foreach($ur as $v){
		if($v<5&&$v>0||$v>9) $d="";
		if(!empty($d)) $d.="; ";
		$d.=$ROLE[$v];
		if($roleTo[$v]>0) $d.=" [".date("d.m.y",$roleTo[$v])."]";
		
	}
	$b=explode("; ",$d);
	$d="";
	for($i=count($b)-1;$i>=0;$i--) {
		if(!empty($d)) $d.=", ";
		$d.=$b[$i];
	}
	
	foreach($roleEstimated as $k=>$v){
		if($v<5&&$v>0||$v>9) $dd="";
		if(!empty($d)) $d.=", ";
		$d.="<span style='color: #acaaaa;    text-decoration: line-through;'>".$ROLE[$k]." [до ".date("d.m.y",$v)."]</span>";		
	}	
	return $d;
}
function is_access($UserRoles,$NeedRoles){
	foreach($NeedRoles as $k=>$v){
		foreach($UserRoles as $kk=>$vv) if($v==$vv||($v==3330&&$vv!=1)) return true;
	}
	return false;
}
function getArrayRolesByStringCats($catString){
	global $dbprefix;
	if($catString==","||$catString==",,") return [[0],0,0,0];
	$d=[];$webplayer=0;$onlymac=1;$onlyfid=1;
	$r=explode(",",$catString);
	for($j=0;$j<count($r);$j++) {
		if($r[$j]!="") {
			$q=q("select * from {$dbprefix}category where id='".intval($r[$j])."'",1);
			if($q["webplayer"]>$webplayer) $webplayer=$q["webplayer"];
			if($q["onlymac"]<$onlymac) $onlymac=$q["onlymac"];
			if($q["onlyfid"]<$onlyfid) $onlyfid=$q["onlyfid"];
			$t=explode(",",$q["access"]);
			foreach($t as $k=>$v) if($v!="") $d[]=$v;
		}
	}
	return [$d,$webplayer,$onlymac,$onlyfid];
}
$getArrayAvailableUserRolesCache=null;
function getArrayAvailableUserRoles(){
	global $dbprefix,$ROLE,$logged,$userinfo,$initial,$ip,$getArrayAvailableUserRolesCache,$roleEstimated,$roleTo;
	if($getArrayAvailableUserRolesCache!=null) return $getArrayAvailableUserRolesCache;
	$ac=[0,1,2,3];
	if($logged){
		$ac[]=4;
		if($userinfo["dateto"]<1||$userinfo["dateto"]>time()) {
			if($userinfo["role"]<3) $ac=[$userinfo["role"]];
			elseif($userinfo["role"]==9){$ac[]=5;$ac[]=6;$ac[]=7;$ac[]=8;$ac[]=9;}
			elseif($userinfo["role"]==10){$ac[]=5;$ac[]=6;$ac[]=7;$ac[]=8;$ac[]=9;$ac[]=10;}
			else $ac[]=$userinfo["role"];
			if($userinfo["role"]>4&&$userinfo["dateto"]>0)	$roleTo[$userinfo["role"]]=$userinfo["dateto"];
		}
		elseif($userinfo["role"]>4)	$roleEstimated[$userinfo["role"]]=$userinfo["dateto"];
		
		//print $userinfo["dateto"].">".time();print_r($ac);
		mysql_query("update {$dbprefix}sessions set ip='".mysql_real_escape_string($ip)."',last=".time().",initial='".mysql_real_escape_string($_GET["initial"])."',c=c+1 where sid='$userinfo[sid]'");
		if(!empty($userinfo["forkplayerid"])) {
			$qd=q("select * from {$dbprefix}device where mac='".mysql_real_escape_string($userinfo["forkplayerid"])."'",1);			
			if(isset($qd[0])){
				mysql_query("update {$dbprefix}device set ip='".mysql_real_escape_string($ip)."',last=".time().",initial='".mysql_real_escape_string($_GET["initial"])."',c=c+1 where id=$qd[id]");
				if($qd["dateto"]<1||$qd["dateto"]>time()) {
					if($qd["role"]<3) $ac=[$qd["role"]];
					elseif($qd["role"]<9) $ac[]=$qd["role"];
					if($qd["role"]>4&&$qd["dateto"]>0) $roleTo[$qd["role"]]=$qd["dateto"];
				}
				elseif($qd["role"]>4) $roleEstimated[$qd["role"]]=$qd["dateto"];
			}
		}
		
		if(!empty($userinfo["login"])) {
			$qd=q("select * from {$dbprefix}device where mac='".mysql_real_escape_string($userinfo["login"])."'",1);			
			if(isset($qd[0])){
				mysql_query("update {$dbprefix}device set ip='".mysql_real_escape_string($ip)."',last=".time().",initial='".mysql_real_escape_string($_GET["initial"])."',c=c+1 where id=$qd[id]");
				if($qd["dateto"]<1||$qd["dateto"]>time()) {
					if($qd["role"]<3) $ac=[$qd["role"]];
					elseif($qd["role"]<9) $ac[]=$qd["role"];
					if($qd["role"]>4&&$qd["dateto"]>0) $roleTo[$qd["role"]]=$qd["dateto"];
				}
				elseif($qd["role"]>4) $roleEstimated[$qd["role"]]=$qd["dateto"];
			}
		}
	}
	if(!empty($_GET["box_mac"])) {
		$qd=q("select * from {$dbprefix}device where mac='".mysql_real_escape_string($_GET["box_mac"])."'");
		mysql_query("update {$dbprefix}device set ip='".mysql_real_escape_string($ip)."',last=".time().",initial='".mysql_real_escape_string($_GET["initial"])."',c=c+1 where  mac='".mysql_real_escape_string($_GET["box_mac"])."'");
		for($i=0;$i<count($qd);$i++){
			if($qd[$i]["dateto"]<1||$qd[$i]["dateto"]>time()) {
				if($qd[$i]["role"]<3) {$ac=[$qd[$i]["role"]];break;}
				elseif($qd[$i]["role"]<9) $ac[]=$qd[$i]["role"];
				if($qd[$i]["role"]>4&&$qd[$i]["dateto"]>0) $roleTo[$qd[$i]["role"]]=$qd[$i]["dateto"];
			}
			elseif($qd[$i]["role"]>4) $roleEstimated[$qd[$i]["role"]]=$qd[$i]["dateto"];			
		}
	}
	$getArrayAvailableUserRolesCache=$ac;
	return $ac;
}
function category_recount($id=-1){
	global $dbprefix,$ROLE,$logged,$userinfo,$initial;
	if($id==-1) $qc=q("select * from {$dbprefix}category");
	else $qc=q("select * from {$dbprefix}category where id='".mysql_real_escape_string($id)."'");
	foreach($qc as $k=>$v) {
		$q=q("select count(id) from {$dbprefix}page where category like '%,$v[id],%'",1);	
		$q2=q("select count(id) from {$dbprefix}category where parent = '$v[id]'",1);
		mysql_query("update {$dbprefix}category set `count`='".($q2[0]+$q[0])."' where id='$v[id]'");
	}
}
function get_pages($cat='',$limit='',$sort='sticked desc, created desc'){
	global $dbprefix,$ROLE,$logged,$userinfo,$initial,$_ISPC,$do;
	if($ROLE==null) get_role();
	$ac=getArrayAvailableUserRoles();
	//print_r($ac);
	$acc="";
	foreach($ac as $k=>$v) $acc.="  or access like '%,$v,%'";
	$doppc="";
	if($_ISPC) $doppc="onlymac=0 and ";	
	if(empty($do)||$do=="/") $doppc.="showpage=1 and ";
	if(empty($cat))	$qc=q("select * from {$dbprefix}category where $doppc(access like '%,0,%'$acc)");//
	else $qc=q("select * from {$dbprefix}category where id='".mysql_real_escape_string($cat)."' and (access like '%,0,%'$acc)");//
	if(!empty($cat)&&count($qc)<1) return [];
	$qcc="";
	foreach($qc as $k=>$v) $qcc.="  or category like '%,$v[id],%'";
	$q=q("select id,title,icon,title,category,created,description,author from {$dbprefix}page where category=',' or category=',,'$qcc order by $sort$limit");
	
	return $q; 
}
function getPluginMetaKey($k,$json=false,$id=""){
	global $dbprefix,$MODULE,$PLUGIN;
	if(isset($MODULE["id"])) $mid=$MODULE["id"];
	else $mid=$PLUGIN["id"];
	if(!empty($id)) $q=q("select * from {$dbprefix}meta where `key`='".mysql_real_escape_string("PluginMeta_{$mid}_$k")."' and id='".mysql_real_escape_string($id)."'");
	elseif(strpos($k,"%")!==false) $q=q("select * from {$dbprefix}meta where `key` like '".mysql_real_escape_string("PluginMeta_{$mid}_$k")."' order by id desc");
	else $q=q("select * from {$dbprefix}meta where `key`='".mysql_real_escape_string("PluginMeta_{$mid}_$k")."' order by id desc");

	if($json) {
		foreach($q as $k=>$v) $q[$k]["src"]=json_decode($q[$k]["src"],true);	
	}
	
	if(!empty($id)&&count($q)<2) return $q[0];
	else return $q;
}

function deletePluginMetaKey($k,$id=null){
	global $dbprefix,$siteurl,$PLUGIN,$MODULE,$logged,$userinfo,$initial,$ip; 
	if(isset($MODULE["id"])) $mid=$MODULE["id"];
	else $mid=$PLUGIN["id"];
	if(preg_match("/[^a-z_0-9]/i",$PLUGIN["id"])) return ["name"=>"Error name of plugin"];
	if(mysql_query("delete from {$dbprefix}meta where `key`='".mysql_real_escape_string("PluginMeta_{$mid}_$k")."' and id='".mysql_real_escape_string("$id")."'")) return true;
	else return false;
}

function savePluginMetaKey($k,$v=null,$id=null,$inc=null){
	global $dbprefix,$siteurl,$PLUGIN,$MODULE,$logged,$userinfo,$initial,$ip;
	if(isset($MODULE["id"])) $mid=$MODULE["id"];
	else $mid=$PLUGIN["id"]; 
	if(preg_match("/[^a-z_0-9]/i",$PLUGIN["id"])) return ["name"=>"Error name of plugin"];
	if($logged) $uid=$userinfo["id"];
	elseif(!empty($_GET["initial"])) $uid=$_GET["initial"];
	else $uid=$ip;
	$s="";
	if($v!=null) $s.=",src='".mysql_real_escape_string("$v")."'";
	if($inc!=null) $s.=",inc='".mysql_real_escape_string("$inc")."'";
	if($id=="update"){
		$q=getPluginMetaKey($k);
		$id=$q[0]["id"];
	}
	if(!empty($id)){
		if(mysql_query("update {$dbprefix}meta set uid='".mysql_real_escape_string($uid)."'$s where id='".mysql_real_escape_string($id)."'")) return true;
		else return false;
	}
	elseif(mysql_query("insert into {$dbprefix}meta set uid='".mysql_real_escape_string($uid)."',`key`='".mysql_real_escape_string("PluginMeta_{$mid}_$k")."'$s")) return true;
	else return false;
}
function getPlugins($m=""){
	global $dbprefix;
	$d=scandir(dirname(__FILE__).'/plugin');
	foreach($d as $k=>$v){
		if(is_dir(dirname(__FILE__)."/plugin/$v")&&$v!="."&&$v!=".."){
			$inf=getInfoPlugin($v);
			if($m=="main"){
				if($inf["enabled"]&&$inf["settings"]["showonmain"]&&is_access(getArrayAvailableUserRoles(),explode(",",$inf["settings"]["allowread"]))) $p[]=$inf;
			}
			elseif($m=="menu"){
				if($inf["enabled"]&&$inf["settings"]["showonmenu"]&&is_access(getArrayAvailableUserRoles(),explode(",",$inf["settings"]["allowread"]))) $p[]=$inf;
			}
			else $p[]=$inf;
		}
	}
	return $p;
}
function getModules($m=""){
	global $dbprefix;
	$d=scandir(dirname(__FILE__).'/module');
	foreach($d as $k=>$v){
		if(is_dir(dirname(__FILE__)."/module/$v")&&$v!="."&&$v!=".."){
			$inf=getInfoModule($v);
			if($m=="main"){
				if(($inf["settings"]["enabled"]&&$inf["settings"]["showonmain"]||$inf["enabled"]&&$inf["settings"]["linkonmain"])&&is_access(getArrayAvailableUserRoles(),explode(",",$inf["settings"]["allowread"]))) $p[]=$inf;
			}
			elseif($m=="menu"){
				if($inf["settings"]["enabled"]&&$inf["settings"]["showonmenu"]&&is_access(getArrayAvailableUserRoles(),explode(",",$inf["settings"]["allowread"]))) $p[]=$inf;
			}
			else $p[]=$inf;
		}
	}
	return $p;
}
function getInfoPlugin($v){
	global $dbprefix,$siteurl; 
	if(preg_match("/[^a-z_0-9]/i",$v)) return ["name"=>"Error directory name of plugin"];
	$s=file_get_contents(dirname(__FILE__)."/plugin/$v/version.xml");
	$xml = simplexml_load_string($s);
	$json = json_encode($xml);
	$a = json_decode($json,TRUE);
	$a["id"]=$v;
	$qe=q("select * from {$dbprefix}meta where `key`='[PLUGIN_ENABLED]'",1);
	if(isset($qe["id"])) $be=json_decode($qe["src"],true);
	$a["enabled"]=$be[$v]["enabled"];	
	$q=q("select * from {$dbprefix}meta where `key`='".mysql_real_escape_string("PluginMeta_{$v}_settings")."'",1);
	if(isset($q["id"])) {
		$b=json_decode($q["src"],true);
		$a["settings"]=array_merge($a["settings"],$b);
	}
	if(!empty($a["icon"])&&strpos($a["icon"],"http")!==0&&strpos($a["icon"],"/")!==0) $a["icon"]="$siteurl/include/plugin/$v/$a[icon]";
	$a["logo_30x30"]=$a["icon"];
	if($a["search_on"]==null) $a["search_on"]="";
	if($a["presearch"]==null) $a["presearch"]="";
	if(!empty($a["presearch"])) $a["presearch"]="$siteurl/?do=/plugin&id=$v&act=presearch";
	$a["path"]="$siteurl/include/plugin/$v";
	$a["link"]="$siteurl/?do=/plugin&id=$v-".toTranslit($a["name"]);
	//print_r($a);
	return $a;
}
function getInfoModule($v){
	global $dbprefix,$siteurl; 
	if(preg_match("/[^a-z_0-9]/i",$v)) return ["name"=>"Error directory name of module"];
	$s=file_get_contents(dirname(__FILE__)."/module/$v/version.xml");
	$xml = simplexml_load_string($s);
	$json = json_encode($xml);
	$a = json_decode($json,TRUE);
	$a["id"]=$v;
	$q=q("select * from {$dbprefix}meta where `key`='".mysql_real_escape_string("PluginMeta_{$v}_settings")."'",1);
	if(isset($q["id"])) {
		$b=json_decode($q["src"],true);
		$a["settings"]=array_merge($a["settings"],$b);
	}
	$a["enabled"]=$a["settings"]["enabled"];	
	if(!empty($a["icon"])&&strpos($a["icon"],"http")!==0&&strpos($a["icon"],"/")!==0) $a["icon"]="$siteurl/include/module/$v/$a[icon]";
	$a["logo_30x30"]=$a["icon"];
	if($a["search_on"]==null) $a["search_on"]="";
	if($a["presearch"]==null) $a["presearch"]="";
	if(!empty($a["presearch"])&&strpos($a["presearch"],"http")!==0&&strpos($a["presearch"],"/")!==0) $a["presearch"]="$siteurl/include/module/$v/".$a["presearch"];
	$a["path"]="$siteurl/include/module/$v";
	$a["link"]="$siteurl/?do=/module&id=$v";
	return $a;
}
function get_menu($m=false){
	global $dbprefix;
	
	$q=q("select * from {$dbprefix}meta where `key`='[MENU_ENABLED]'",1);
	$menuSets=json_decode($q["src"],true);
	if($m)	if(isset($q["id"])&&!$menuSets["enabled"]) return null;
	 
	$q=q("select * from {$dbprefix}meta where `key`='[MENU]'",1);
	if(!isset($q["id"])) {
		$q=["id"=>"auto","menuSets"=>$menuSets,"src"=>'[{"title":"main"},{"title":"category"},{"title":"new"},{"title":"roles"}]'];	
		$q["src"]=json_decode($q["src"],true);	
		if($m) {
			$modules=getModules("main");
			foreach($modules as $k=>$v){
				if($v["settings"]["enabled"]) $q["src"][]=["logo_30x30"=>$v["icon"],"title"=>$v["id"],"type"=>"module","playlist_url"=>"$v[link]"];
			}
		}
	}
	else $q["src"]=json_decode($q["src"],true);	

	$q["menuSets"]=$menuSets;

	return $q;
}
$ROLE=null;
function get_role($id="get"){
	global $dbprefix,$ROLE;
	if($id=="0") return "Всем";
	if(isset($ROLE[$id])) return $ROLE[$id];
	$q=q("select * from {$dbprefix}role");
	for($i=0;$i<count($q);$i++) $ROLE[$q[$i]["id"]]=$q[$i]["name"];
	return $ROLE[$id];
}
function chToHtml($ch,$template){
	global $siteurl;
	if(!empty($ch["search_on"])){
		if(empty($ch["type"])) $ch["type"]="text";
		if($ch["type"]!="checkbox") $befor=$ch["search_on"]."<br>";
		if(strpos($ch["playlist_url"],"payd_")===0) {
			$name=$ch["playlist_url"];
			$ch["playlist_url"]="#";
			$ch["description"].="$befor<input type='$ch[type]'".($ch["search_on"]=="checked"?$ch["search_on"]:"")." id='$name' name='$name' value='".$ch["value"]."' size=80 /><br><small>$ch[description]</small>";
		}
		else {
			$name="search";
			$ch["description"].="$befor<form id='form$i' onsubmit=\"if($('#$name$i').val().length>0) document.location ='".$ch["playlist_url"].(strpos($ch["playlist_url"],"?")===false?"?":"&")."search='+encodeURIComponent($('#$name$i').val());else alert('Введите текст!');return false;\" method=\"get\"><input type='$ch[type]'".($ch["search_on"]=="checked"?$ch["search_on"]:"")." id='$name$i' name='$name' value='".$ch["value"]."' size=80 /><br><small>$ch[description]</small></form>";
			$ch["playlist_url"]="javascript:$('#form$i').submit();";
		}
	}
	$tpl=str_replace("{CATEGORY}",$ch["CATEGORY"],$template);
	if($ch["playlist_url"]=="submenu"){
		$ch["playlist_url"]="javascript:";
			$st="<ul id='menu'>";
			$st.="<li><a href='#' style='' >".$ch["title"]."</a>";
			$st.="<ul id='subs$i'>";
			foreach($ch["submenu"] as $k=>$v){
				$st.="<li><a href='".$v["playlist_url"]."'><img align='left' src='$v[logo_30x30]' onerror=\"this.style.display='none'\" id='img_$pltag[$i]' width=20 height=18 style='margin: 2px;'/> ".$v["title"]."</a></li>";
			}
			$st.="</ul></li>";
			$st.="</ul>";
			$ch["title"]="";
			$tpl=str_replace("{SUBMENU}",$st,$tpl);
	}
	else $tpl=str_replace("{SUBMENU}","",$tpl);
	$tpl=str_replace("{AUTHOR}",get_author($ch["author"]),$tpl);
	if($ch["type"]=="button") $tpl=str_replace("{TITLE}","<span class='button'>".$ch["title"]."</span>",$tpl);
	else $tpl=str_replace("{TITLE}",$ch["title"],$tpl);
	if(strpos($ch["playlist_url"],"http")===0&&strpos($ch["playlist_url"],"payd_")>0) {

		$ch["playlist_url"]="javascript: location='".preg_replace("/(payd_.*?)(&|$)/","'+(($('input[name=\'$1\']').prop('type')=='checkbox')?".
			"($('input[name=\'$1\']').prop('checked')?encodeURIComponent($('input[name=\'$1\']').val()):'')".
			":encodeURIComponent($('input[name=\'$1\']').val()))+'$2",$ch["playlist_url"])."';";
	
	}
	$tpl=str_replace("{LINK}",$ch["playlist_url"].$ch["stream_url"],$tpl); 
	$tpl=str_replace("{INFOLINK}",$ch["infolink"],$tpl);
	$tpl=str_replace("{DATE}",$ch["created"]." ".($ch["sticked"]?"Прилеплена":""),$tpl);
	$tpl=str_replace("{DESCRIPTION}","<small>".(empty($ch["description"])?"":$ch["description"])."</small>",$tpl);			
	
	$tpl=str_replace("{ICON}",$ch["logo_30x30"],$tpl);
	$m="";
	foreach($ch["menu"] as $k=>$v) $m.="<img src='$v[logo_30x30]' onerror=\"this.style.display='none';\" width=20 height=18 /><a href=\"$v[playlist_url]$v[stream_url]\">$v[title]</a> ";
	$tpl=str_replace("{MENU}",$m,$tpl);
	if($ch["location"]==1) {
		header("Location: $ch[playlist_url]");
		exit;
	}
	return "\n".$tpl;
}
function get_cat_roles($s){
	$dst="";
	$r=explode(",",$s);
	for($j=0;$j<count($r);$j++) if($r[$j]!="") $dst.=get_role($r[$j])." ";
	return $dst;
}
function get_cat_roles_by_id($id,$m=true){
	global $dbprefix; 
	if($id==""||$id==","||$id==",,") return "Нет.(Всем)";	
	$dst="";
	$r=explode(",",$id);
	for($j=0;$j<count($r);$j++) {
		if($r[$j]!="") {
			$q=q("select * from {$dbprefix}category where id='".intval($r[$j])."'",1);
			if($m){
				if(isset($q["access"])) {
					$dst.="$q[title] (".get_cat_roles($q["access"]).") ";
				}
			}
			else $dst.="$q[title] ";
		}
	}
	return $dst;
}
$USERS=null;
function get_author($id){	
	global $dbprefix,$USERS; 
	if(isset($USERS[$id])) return $USERS[$id];
	$q=q("select * from {$dbprefix}users where id='".intval($id)."'",1);
	$USERS[$id]=$q["login"];
	return $USERS[$id];
}
function get_seo($s){
	if(empty($s)){
		$s="";
	}
}
 
if ( ! function_exists('is_https'))
{
    /**
     * Is HTTPS?
     *
     * Determines if the application is accessed via an encrypted
     * (HTTPS) connection.
     *
     * @return  bool
     */
    function is_https()
    {
        if ( ! empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
        {
            return TRUE;
        }
        elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        {
            return TRUE;
        }
        elseif ( ! empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off')
        {
            return TRUE;
        }
        return FALSE;
    }
}

function fEncrypt($s){
	global $initial,$ip,$siteurl;
	$urllist = (is_https()?"https":"http")."://$_SERVER[HTTP_HOST]".$_SERVER['REQUEST_URI']; 
	header("urllist: $urllist");
	
	for($i=0;$i<7;$i++){
		$b[$i]=substr(md5($ip.$initial[1]),$i*4,4);
	}
	if(isset($initial[4])&&strlen($initial[4])>4){
		$s.="-9-$urllist";
	}
	else return "http://www.youtube.com/watch?v=kNUE2ZV9Vzk";
	$s.="-9-$ip-9-".time()."-9-$_GET[box_mac]";
	$l=base64_encode($s);
	$a=["aHR0cDovL","=","S","z","M","b","1"];
	$l=str_replace($a,$b,$l);
	if(strpos($s,".m3u8")>0) $l.=".m3u8";	
	return "$siteurl/hls/userencrypt/$l";
} 
function uencxml($matches){
	$matches[2]=str_replace("<![CDATA[","",$matches[2]);
	$matches[2]=str_replace("]]>","",$matches[2]);
	return 'stream_ur'.$matches[1].'><![CDATA['.fEncrypt($matches[2]).']]></stream_url';
}
function uencm3u($matches){ 
	return "$matches[1]".fEncrypt($matches[2])."$matches[3]";
}
function listEncrypt($s){
	$s=str_replace("\r","",$s);
	if(strpos($s,"#EXTM3U")!==false||strpos($s,"#EXTINF")!==false) 
		return preg_replace_callback("/(EXTINF.*?,.*?\n)([^#].*?)(\n|$)/is",'uencm3u',$s);
	else if(strpos($s,"<channel>")!==false){
		return preg_replace_callback("/stream_ur(.*?)>(.*?)<\/stream_url/is",'uencxml',$s);		
	}
	return $s;
}
function parsexspf($s){
	$channels=array();
	preg_match_all("/<track>(.*?)<\/track>/is",$s,$arr);
	for($i=0;$i<count($arr[1]);$i++) {
		preg_match("/<title>(.*?)<\/title>/is",$arr[1][$i],$res);
		if(isset($res[1])){
			$res=str_replace("<![CDATA[","",$res[1]);
			$res=str_replace("]]>","",$res);
		} 
		else $res=""; 
		$title=$res;
		preg_match("/<location>(.*?)<\/location>/is",$arr[1][$i],$res);
		if(isset($res[1])){
			$res=str_replace("<![CDATA[","",$res[1]);
			$res=str_replace("]]>","",$res);
		} 
		else $res="";
		if(empty($title)) $title=basename($res);
		$channels[$i]["title"]=$title;
		$channels[$i]["stream_url"]=$res;
	}	
	$output['channels']=$channels;
	return $output;
}
function parsetxt($s){
	$channels=array();
	$e=explode("\n",str_replace("\r","",$s));
	for($i=0;$i<count($e);$i++) {
		if(strpos($e[$i],"http://")===0||strpos($e[$i],"https://")===0||strpos($e[$i],"ftp://")===0||strpos($e[$i],"file://")===0){
			$z=array_reverse(explode("/",$e[$i]));			
			$channels[]=["title"=>$z[0],"stream_url"=>$e[$i]];
		}
	}
	return $channels;
}

function parsem3u($s){
	preg_match_all("/EXTINF(.*?,)(.*?)\n([^#].*?)(\n|$)/is",str_replace("\r","",$s)."\n",$arr);
		//print_r($arr);
		//print $s;
		for($i=0;$i<count($arr[1]);$i++) {
		preg_match("/tvg-logo=(.*?)(\s|,)/i",$arr[1][$i],$tvg);
		$tvg_logo=(isset($tvg[1]))?str_replace("\"","",$tvg[1]):"";
		$tvg_logo=str_replace("'","",$tvg_shift);
		
		preg_match("/tvg-name=.(.*?)(\"|,|')/i",$arr[1][$i],$tvgname);
		$tvgname=(isset($tvgname[1]))?str_replace("\"","",$tvgname[1]):"";
		
		preg_match("/group-title=.(.*?)(\"|,|')/i",$arr[1][$i],$group);
		//print_r($group);
		$group=(isset($group[1]))?str_replace("\"","",$group[1]):"";
		$group=str_replace("'","",$group); 
		$ex=explode("\n",$arr[2][$i]);
		
		preg_match("/#EXTGRP:(.*?)(\n|$)/is",$arr[2][$i],$group2);
		if(empty($group)){
			$group=(isset($group2[1]))?trim($group2[1]):"";
		}
		
		$channels[]=array("title"=>$ex[0],"logo_30x30"=>trim($tvg_logo),"stream_url"=>trim($arr[3][$i]),"group"=>$group,"jtvname"=>$tvgname);
		}
		$output=array("channels"=>$channels);
		preg_match("/#PLAYLIST:(.*)/i",$s,$a);
		if(isset($a[1])) $output["title"]=$a[1];
		else $output["navigate"]=" > $bylist";
		preg_match("/#ICON:(.*)/i",$s,$a);
		if(isset($a[1])) $output["icon"]=$a[1];
		return $output;
}
$chtag="title|logo_30x30|stream_url|playlist_url|description|category|parser|mb_parser|menu_url|search_on|presearch|jtvname|submenu|tvg-shift|value|group|subtitles|yellow_url|yellow_title|yellow_parser|infolink|location|advert|type|menu";
$pltag="title|icon|typeList|background_image|url|next_page_url|prev_page_url|access|timeout|is_iptv|all_description|color|pageinfo|menu|style";
function parsexml($s){
	global $chtag,$pltag;
	$channels=array();	
	preg_match_all("/<category>(.*?)<\/category>/is",$s,$arr);
	
	for($i=0;$i<count($arr[1]);$i++) {
		preg_match("/<category_id>(.*?)<\/category_id>/is",$arr[1][$i],$aid);
		preg_match("/<category_title>(.*?)<\/category_title>/is",$arr[1][$i],$ati);
		$aid=str_replace("<![CDATA[","",$aid[1]);
		$aid=str_replace("]]>","",$aid);
		$ati=str_replace("<![CDATA[","",$ati[1]);
		$ati=str_replace("]]>","",$ati);
		$cat_id[$aid]=$ati;
	}
	//print_r($cat_id);
	preg_match_all("/<channel>(.*?)<\/channel>/is",$s,$arr);
	for($i=0;$i<count($arr[1]);$i++) {
		$tags=explode("|",$chtag);
		for($k=2;$k<10;$k++) {
			if(strpos($arr[1][$i],"<playlist_url$k>")!==false) $tags[]="playlist_url$k";			
			else break;
		}
		for($k=2;$k<10;$k++) {
			if(strpos($arr[1][$i],"<stream_url$k>")!==false) $tags[]="stream_url$k";
			else break;
		}
		for($j=0;$j<count($tags);$j++){
			preg_match("/<$tags[$j].*?>(.*?)<\/$tags[$j]>/is",$arr[1][$i],$res);
			if(isset($res[1])){
			$res=str_replace("<![CDATA[","",$res[1]);
			$res=str_replace("]]>","",$res);
			} 
			else $res=""; 
			if($tags[$j]=="category_id"){
					$resspl=explode(",",$res);
					$res="";
					for($k=0;$k<count($resspl);$k++) {
						if($res!="") $res.=";";
						if(isset($cat_id[$resspl[$k]])) $res.=$cat_id[$resspl[$k]];
						else $res.=$resspl[$k];
					}
				}
			if($tags[$j]=="category_id") $channels[$i]["group"]=$res;
			else {
				if(!empty($res)) $channels[$i][$tags[$j]]=$res;
			}
			$arr[1][$i]=preg_replace("/<$tags[$j].*?>(.*?)<\/$tags[$j]>/is","",$arr[1][$i]);
		}
	}
	$tags=explode("|",$pltag);
	$output=array();
	$s1=preg_replace("/<channel>.*<\/channel>/is","",$s);
	for($j=0;$j<count($tags);$j++){
			preg_match("/<$tags[$j].*?>(.*?)<\/$tags[$j]>/is",$s1,$res);
			if(isset($res[1])){
			$res=str_replace("<![CDATA[","",$res[1]);
			$res=str_replace("]]>","",$res);
			}
			else $res="";
			$output[$tags[$j]]=$res;
		}
	$output['channels']=$channels;
	return $output;
}


$tabs="";


function cxmljson($arr){
	if(is_array($arr["channels"])){
		$arr["channel"]=$arr["channels"];
		unset($arr["channels"]);
	}
	
	$xml = Array2XML::createXML('items', $arr, true);
	return $xml->saveXML();
	
	$simpleXml = new SimpleXMLElementExtended("<?xml version=\"1.0\"?><items></items>");

	
	array_to_xml($arr,$simpleXml);
	print_r($simpleXml);
	$dom = dom_import_simplexml($simpleXml)->ownerDocument;

	$dom->formatOutput = true;
	//return $dom->saveXML();
}
function createvast($arr){
	$xml = Array2XML::createXML('VAST', $arr);
	return $xml->saveXML();
}

function array_to_xml($template_info, &$xml_template_info) {
	foreach($template_info as $key => $value) {
		if(is_array($value)) {
			if(!is_numeric($key)){

				$subnode = $xml_template_info->addChild("$key");

				if(count($value) >1 && is_array($value)){
					
					$jump = false;
					$count = 1;
					foreach($value as $k => $v) {
						if(is_array($v)){
							if($count++ > 1)
								$subnode = $xml_template_info->addChild("$key");

							array_to_xml($v, $subnode);
							$jump = true;
						}
					}
					if($jump) {
						goto LE;
					}
					array_to_xml($value, $subnode);
				}
				else
					array_to_xml($value, $subnode);
			}
			else{
				array_to_xml($value, $xml_template_info);
			}
		}
		else {
			$xml_template_info->addChildWithCDATA("$key","$value");
		}

		LE: ;
	}
}


function q($q,$m=0){
	$ath=mysql_query($q);
	//if(!$ath) print "<br/>".mysql_error();
	if(!@mysql_num_rows($ath)) return;
	while(@$rq=@mysql_fetch_array($ath)) $res[]=@$rq;
	if($m) $res=@$res[0];
	return $res;
}


class Array2XML
{
    /**
     * @var string
     */
    private static $encoding = 'UTF-8';

    /**
     * @var DomDocument|null
     */
    private static $xml = null;
    private static $WithCdata = false;

    /**
     * Convert an Array to XML.
     *
     * @param string $node_name - name of the root node to be converted
     * @param array $arr - array to be converted
     *
     * @return DomDocument
     * @throws Exception
     */
    public static function createXML($node_name, $arr = [],$wcd=false)
    {
		self::$WithCdata = $wcd;
        $xml = self::getXMLRoot();
        $xml->appendChild(self::convert($node_name, $arr));
        self::$xml = null;    // clear the xml node in the class for 2nd time use.

        return $xml;
    }

    /**
     * Initialize the root XML node [optional].
     *
     * @param string $version
     * @param string $encoding
     * @param bool   $standalone
     * @param bool   $format_output
     */
    public static function init($version = '1.0', $encoding = 'utf-8', $standalone = false, $format_output = true)
    {
        self::$xml = new DomDocument($version, $encoding);
        self::$xml->xmlStandalone = $standalone;
        self::$xml->formatOutput = $format_output;
        self::$encoding = $encoding;
    }

    /**
     * Get string representation of boolean value.
     *
     * @param mixed $v
     *
     * @return string
     */
    private static function bool2str($v)
    {
        //convert boolean to text value.
        $v = $v === true ? 'true' : $v;
        $v = $v === false ? 'false' : $v;
		return $v;
    }

    /**
     * Convert an Array to XML.
     *
     * @param string $node_name - name of the root node to be converted
     * @param array  $arr       - array to be converted
     *
     * @return DOMNode
     *
     * @throws Exception
     */
    private static function convert($node_name, $arr = [])
    {
        //print_arr($node_name);
        $xml = self::getXMLRoot();
        $node = $xml->createElement($node_name);

        if (is_array($arr)) {
            // get the attributes first.;
            if (array_key_exists('@attributes', $arr) && is_array($arr['@attributes'])) {
                foreach ($arr['@attributes'] as $key => $value) {
                    if (!self::isValidTagName($key)) {
                        throw new Exception('[Array2XML] Illegal character in attribute name. attribute: '.$key.' in node: '.$node_name);
                    }
                    $node->setAttribute($key, self::bool2str($value));
                }
                unset($arr['@attributes']); //remove the key from the array once done.
            }

            // check if it has a value stored in @value, if yes store the value and return
            // else check if its directly stored as string
            if (array_key_exists('@value', $arr)) {
                $node->appendChild($xml->createTextNode(self::bool2str($arr['@value'])));
                unset($arr['@value']);    //remove the key from the array once done.
                //return from recursion, as a note with value cannot have child nodes.
                return $node;
            } elseif (array_key_exists('@cdata', $arr)) {
                $node->appendChild($xml->createCDATASection(self::bool2str($arr['@cdata'])));
                unset($arr['@cdata']);    //remove the key from the array once done.
                //return from recursion, as a note with cdata cannot have child nodes.
                return $node;
            }
        }

        //create subnodes using recursion
        if (is_array($arr)) {
            // recurse to get the node for that key
            foreach ($arr as $key => $value) {
                if (!self::isValidTagName($key)) {
                    throw new Exception('[Array2XML] Illegal character in tag name. tag: '.$key.' in node: '.$node_name);
                }
                if (is_array($value) && is_numeric(key($value))) {
                    // MORE THAN ONE NODE OF ITS KIND;
                    // if the new array is numeric index, means it is array of nodes of the same kind
                    // it should follow the parent key name
                    foreach ($value as $k => $v) {
                        $node->appendChild(self::convert($key, $v));
                    }
                } else {
                    // ONLY ONE NODE OF ITS KIND
                    $node->appendChild(self::convert($key, $value));
                }
                unset($arr[$key]); //remove the key from the array once done.
            }
        }

        // after we are done with all the keys in the array (if it is one)
        // we check if it has any text value, if yes, append it.
        if (!is_array($arr)) {
			if(!empty($arr)&&!is_bool($arr)&&!is_integer($arr)&&self::$WithCdata) $node->appendChild($xml->createCDATASection(self::bool2str($arr)));
            else $node->appendChild($xml->createTextNode(self::bool2str($arr)));
        }

        return $node;
    }

    /**
     * Get the root XML node, if there isn't one, create it.
     *
     * @return DomDocument|null
     */
    private static function getXMLRoot()
    {
        if (empty(self::$xml)) {
            self::init();
        }

        return self::$xml;
    }

    /**
     * Check if the tag name or attribute name contains illegal characters
     * Ref: http://www.w3.org/TR/xml/#sec-common-syn.
     *
     * @param string $tag
     *
     * @return bool
     */
    private static function isValidTagName($tag)
    {
        $pattern = '/^[a-z_]+[a-z0-9\:\-\.\_]*[^:]*$/i';

        return preg_match($pattern, $tag, $matches) && $matches[0] == $tag;
    }
}




function rgba2hex($string) {
				$rgba  = array();
				$hex   = '';
				$regex = '#\((([^()]+|(?R))*)\)#';
				if (preg_match_all($regex, $string ,$matches)) {
					$rgba = explode(',', implode(' ', $matches[1]));
				} else {
					$rgba = explode(',', $string);
				}
				
				$rr = dechex($rgba['0']);
				$gg = dechex($rgba['1']);
				$bb = dechex($rgba['2']);
				$aa = '';
				
				if (array_key_exists('3', $rgba)) {
					$aa = dechex($rgba['3'] * 255);
					if(strlen($aa)<2) $aa="0$aa";
				}
				if(strlen($rr)<2) $rr="0$rr";
				if(strlen($gg)<2) $gg="0$gg";
				if(strlen($bb)<2) $bb="0$bb";
				return strtoupper("#$rr$gg$bb$aa");
			}
			function hex2rgba($color, $opacity = false) {
				$default = 'rgb(0,0,0)';
				if(empty($color))
					  return $default; 
				if ($color[0] == '#' ) {
					$color = substr( $color, 1 );
				}
		 
				if (strlen($color) == 6) {
						$hex = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
				} elseif ( strlen( $color ) == 3 ) {
						$hex = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
				} else {
						return $default;
				}
				$rgb =  array_map('hexdec', $hex);				
				if($opacity!==false){
					if(abs($opacity) > 1)
						$opacity = 1.0;
					if(abs($opacity) <0)
						$opacity = 0;
					$output = 'rgba('.implode(",",$rgb).','.$opacity.')';
				} else {
					$output = 'rgb('.implode(",",$rgb).')';
				}
				return $output;
			}
function randtext($n){
	$s="";
	$range = range('a', 'z');
	for($i=0;$i<$n;$i++){
		$index = array_rand($range);
		$s.=$range[$index];
	}
	return $s;
}
function backup_tables($host, $user, $password, $dbname, $prefix = 'axml_') {
    $link = mysqli_connect($host, $user, $password, $dbname);

    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL $host, $user, $password, $dbname : " . mysqli_connect_error();
        exit;
    }

    mysqli_query($link, "SET NAMES 'utf8'");

    //get all of the tables

	$tables = array();
	$result = mysqli_query($link, 'SHOW TABLES');
	while($row = mysqli_fetch_row($result))
	{
		if(strpos($row[0],$prefix)===0) $tables[] = $row[0];
	}
   

    $return = '';
    //cycle through
    foreach($tables as $table)
    {
        $result = mysqli_query($link, 'SELECT * FROM '.$table);
        $num_fields = mysqli_num_fields($result);
        $num_rows = mysqli_num_rows($result);

        $return.= 'DROP TABLE IF EXISTS '.$table.';';
        $row2 = mysqli_fetch_row(mysqli_query($link, 'SHOW CREATE TABLE '.$table));
        $return.= "\n\n".$row2[1].";\n\n";
        $counter = 1;

        //Over tables
        for ($i = 0; $i < $num_fields; $i++) 
        {   //Over rows
            while($row = mysqli_fetch_row($result))
            {   
                if($counter == 1){
                    $return.= 'INSERT INTO '.$table.' VALUES(';
                } else{
                    $return.= '(';
                }

                //Over fields
                for($j=0; $j<$num_fields; $j++) 
                {
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = str_replace("\n","\\n",$row[$j]);
                    if (isset($row[$j])) { $return.= '"'.$row[$j].'"' ; } else { $return.= '""'; }
                    if ($j<($num_fields-1)) { $return.= ','; }
                }

                if($num_rows == $counter){
                    $return.= ");\n";
                } else{
                    $return.= "),\n";
                }
                ++$counter;
            }
        }
        $return.="\n\n\n";
    }

    return $return;
}
function toTranslit($s){
	 $converter = array(
        'а' => 'a',   'б' => 'b',   'в' => 'v',
        'г' => 'g',   'д' => 'd',   'е' => 'e',
        'ё' => 'e',   'ж' => 'zh',  'з' => 'z',
        'і' => 'i', 'и' => 'i',   'й' => 'y',   'к' => 'k',
        'л' => 'l',   'м' => 'm',   'н' => 'n',
        'о' => 'o',   'п' => 'p',   'р' => 'r',
        'с' => 's',   'т' => 't',   'у' => 'u',
        'ф' => 'f',   'х' => 'h',   'ц' => 'c',
        'ч' => 'ch',  'ш' => 'sh',  'щ' => 'sch',
        'ь' => '',  'ы' => 'y',   'ъ' => '',
        'э' => 'e',   'ю' => 'yu',  'я' => 'ya',
        
        'А' => 'A',   'Б' => 'B',   'В' => 'V',
        'Г' => 'G',   'Д' => 'D',   'Е' => 'E',
        'Ё' => 'E',   'Ж' => 'Zh',  'З' => 'Z',
        'І' => 'i', 'И' => 'I',   'Й' => 'Y',   'К' => 'K',
        'Л' => 'L',   'М' => 'M',   'Н' => 'N',
        'О' => 'O',   'П' => 'P',   'Р' => 'R',
        'С' => 'S',   'Т' => 'T',   'У' => 'U',
        'Ф' => 'F',   'Х' => 'H',   'Ц' => 'C',
        'Ч' => 'Ch',  'Ш' => 'Sh',  'Щ' => 'Sch',
        'Ь' => '',  'Ы' => 'Y',   'Ъ' => '',
        'Э' => 'E',   'Ю' => 'Yu',  'Я' => 'Ya',
    );
	
    $s=strtr($s, $converter);

    // в нижний регистр
    $str = strtolower($s);
    // заменям все ненужное нам на "-"
    $str = preg_replace('~[^-a-z0-9_]+~u', '_', $str);
    $str = trim($str, "_");
    return $str;
}
function addInf($s){
	global $typelinkPage;
	if(empty($typelinkPage)) return "-".toTranslit($s);
	elseif($typelinkPage=="id") return "";
}






