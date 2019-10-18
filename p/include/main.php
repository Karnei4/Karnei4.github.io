<?php
if(!defined('XMLCMS')) exit();
error_reporting(E_ERROR | E_PARSE);
$version="1.0.0";
//print_r($GLOBALS);
if(isset($_GET["box_mac"])){
	$box_mac=@$_GET["box_mac"];
}

if(isset($_GET["do"])){
	$do=@$_GET["do"];
}
else $do=""; 
$initial=(isset($_GET['initial']))?explode("|",$_GET['initial']):array();
$act=@$_GET["act"];
$p=@$_GET["p"];
$logged=false;
$userinfo=array();
if(isset($_GET["id"])&&strpos($_GET["id"],"-")!==false){
	preg_match("/^(.*)-/",$_GET["id"],$tmp);
	$_GET["id"]=$tmp[1];
}
$ip=$_SERVER['REMOTE_ADDR'];
include(dirname(__FILE__)."/geo/ipgeobase.php");
include(dirname(__FILE__)."/version.php");
$gb = new IPGeoBase();
$data = $gb->getRecord($ip);
$COUNTRY=$data["cc"];
if(isset($_GET["box_mac"])) {
	$_ISPC=false;
	foreach($_GET["cookie"] as $k=>$v){
		$_COOKIE[$k]=$v;
	}
}
else $_ISPC=true;
	
if(!file_exists("config.php")){
	header("Location: /install.php");
	exit;
}
else include "config.php";

include dirname(__FILE__).'/functions.php';

$_PL=array();
$_CH=array();
$_MENU=array();

if (version_compare(PHP_VERSION, '7.0.0','>=')) {
	include_once dirname(__FILE__).'/mysql.php';
}
$dbh=mysql_connect($host, $user, $password) or exit("Не удалось подключится к базе данных MySql");
$dbn=mysql_select_db($db) or exit("Unknown database $db");
mysql_query("set names 'utf8'");
if(!isset($showStat)) $showStat='1';
if($showStat){
	$d=date("Ymd");
	$STAT=getPluginMetaKey("S$d",true);
	$STAT[0]["src"]++;
	savePluginMetaKey("S$d",$STAT[0]["src"],$STAT[0]["id"]);
}
$modules=getModules();
get_role();
foreach($modules as $k=>$v){
	if($v["settings"]["enabled"]) {
		include_once(dirname(__FILE__)."/module/$v[id]/header.php");
	}
}

$err="";

$q=get_menu(true);
$_PL["style"]["cssid"]["menu"]=$q["menuSets"];
if($q!=null){
	$a=$q["src"];
	if($logged&&$userinfo["role"]=="10"&&$_ISPC) $_MENU[]=["title"=>"Админка","playlist_url"=>"$siteurl/?do=/admin"];

		
	foreach($a as $k=>$v){
		if($v["type"]=="module") {
			include_once(dirname(__FILE__)."/module/$v[title]/menu.php");
		}
		elseif($v["title"]=="main") $_MENU[]=["logo_30x30"=>$siteicon,"title"=>"$sitename","playlist_url"=>"$siteurl/"];
		elseif($v["title"]=="category"){
			$plugins=getPlugins("menu");
			$submenu=[];
			for($i=0;$i<count($plugins);$i++){
				if($do=="/plugin"&&$_GET["id"]==$plugins[$i]["id"]) $submenu[]=["logo_30x30"=>$plugins[$i]["icon"],"search_on"=>$plugins[$i]["search_on"],"presearch"=>$plugins[$i]["presearch"],"title"=>"<b>".$plugins[$i]["name"]."</b>","playlist_url"=>$plugins[$i]["link"]];
				else $submenu[]=["logo_30x30"=>$plugins[$i]["icon"],"search_on"=>$plugins[$i]["search_on"],"presearch"=>$plugins[$i]["presearch"],"title"=>"".$plugins[$i]["name"]."","playlist_url"=>$plugins[$i]["link"]];

				$div.=$tpl;
			}
			$cq=q("select * from {$dbprefix}category");
			foreach($cq as $kk=>$vv){ 
				if($do=="/category"&&$_GET["id"]==$vv["id"]) $submenu[]=["logo_30x30"=>$vv["icon"],"title"=>"<b>".$vv["title"]." ($vv[count])</b>","playlist_url"=>""];
				else $submenu[]=["logo_30x30"=>$vv["icon"],"title"=>$vv["title"]." ($vv[count])","playlist_url"=>"$siteurl/?do=/category&id=$vv[id]"];
			}
			if(count($submenu)) $_MENU[]=["logo_30x30"=>"$siteurl/include/templates/images/menu.png","title"=>"Меню","playlist_url"=>"submenu","submenu"=>$submenu];
		}
		elseif($v["title"]=="new"){
			$qp=get_pages(""," limit 0,5","created desc");
			$submenu=[];
			foreach($qp as $kk=>$vv) $submenu[]=["logo_30x30"=>$vv["icon"],"title"=>$vv["title"],"playlist_url"=>"$siteurl/?do=/fml&id=".$vv["id"].addInf($vv["title"])];
			if(count($submenu)) $_MENU[]=["title"=>"Последнее","playlist_url"=>"submenu","submenu"=>$submenu];
		}
		elseif($v["title"]=="pop"){
			$qp=get_pages(""," limit 0,5","view desc");
			$submenu=[];
			foreach($qp as $kk=>$vv) $submenu[]=["logo_30x30"=>$vv["icon"],"title"=>$vv["title"],"playlist_url"=>"$siteurl/?do=/fml&id=".$vv["id"].addInf($vv["title"])];
			if(count($submenu)) $_MENU[]=["title"=>"Популярное","playlist_url"=>"submenu","submenu"=>$submenu];
		}
		elseif(isset($v["playlist_url"])){
			$_MENU[]=$v;
		}
	}
	if($logged){
		$MYCH=getPluginMetaKey("[MYMENU_$userinfo[id]]",true);
		$MYCH=$MYCH[0]["src"];
		if($MYCH["mylinkonmenu"]&&count($MYCH["channels"])>0) {
			$submenu=$MYCH["channels"];
			if(count($MYCH["channels"])==1)	 $_MENU[]=$submenu[0];
			else $_MENU[]=["title"=>"Мои ссылки","playlist_url"=>"submenu","submenu"=>$submenu];
		}
	}
	foreach($a as $k=>$v){
		if($v["title"]=="roles"){
			$_MENU[]=["title"=>getmyrolesText(),"playlist_url"=>"cmd:info(".getmyrolesText().")"];
		}
	}
}
{
	$smenu="<ul id='menu' style='background-color:".$_PL["style"]["cssid"]["menu"]["background_color"].";'>"; 
	for($i=0;$i<count($_MENU);$i++){
		$a=$_MENU[$i];
		$smenu.="<li><a href='".(($a["playlist_url"]=="submenu")?"#":$a["playlist_url"].$a["stream_url"])."' style='color:".$_PL["style"]["cssid"]["menu"]["color"].";' >".(empty($a["logo_30x30"])?"":"<img align='left' src='$a[logo_30x30]' onerror=\"this.style.display='none'\" id='img_$pltag[$i]' width=20 height=18 style='margin: 2px;'/>")." ".$a["title"]."</a>";
		if($a["playlist_url"]=="submenu") {
			$smenu.="<ul id='sub$i' style='background-color:".$_PL["style"]["cssid"]["menu"]["background_color"].";'>";
			foreach($a["submenu"] as $k=>$v){
				$smenu.="<li><a href='".$v["playlist_url"]."'><img align='left' src='$v[logo_30x30]' onerror=\"this.style.display='none'\" id='img_$pltag[$i]' width=20 height=18 style='margin: 2px;color:".$_PL["style"]["cssid"]["menu"]["color"].";'/> ".$v["title"]."</a></li>";
			}
			$smenu.="</ul></li>";
		}
		else $smenu.="</li>";		
	}
	$smenu.="</ul><br clear='both'>";
	
	$t=file_get_contents("include/templates/web.xml");
	$t=str_replace("{LOGO}",$logo,$t);
	$t=str_replace("{MENU}",$smenu,$t);
	$t=str_replace("{COPYRIGHT}","© $sitename ".date("Y"),$t);
	if($do=="/admin"&&$logged&&$userinfo["role"]=="10") {
		if($_GET["act"]=="imgtofon"){
			if(preg_match("/\.(jpg|jpeg|png)$/i",$_FILES["file"]["name"])){
				if(!file_exists("uploads/backgrounds")) mkdir("uploads/backgrounds",0777);
				$path="uploads/backgrounds/".preg_replace("/[^a-z0-9_\.]/","_",$_FILES["file"]["name"]);
				move_uploaded_file($_FILES["file"]["tmp_name"],$path);
				print "$siteurl/".$path;
			}
			else print "Поддерживается png,jpg,jpeg";
			exit;
		}
		if($_GET["act"]=="imgto64"){
			//print_r($_FILES);$_FILES["file"]["name"];
			$f=resize_image($_FILES["file"]["tmp_name"],90,84,$_FILES["file"]["type"]);
			if(!file_exists("uploads/icons")) mkdir("uploads/icons",0777);
			$path="uploads/icons/".preg_replace("/\..{2,4}$/","",$_FILES["file"]["name"]).".png";
			imagepng($f, $path);
			print "$siteurl/".$path;
			/*
			$f=file_get_contents($path);
			$type = pathinfo($path, PATHINFO_EXTENSION);
			print 'data:image/' . $type . ';base64,' . base64_encode($f);*/
			exit;
		}
		
		$link[]="/?do=/admin&act=addpage|Добавить страницу";
		$link[]="/?do=/admin&act=listpage|Список страниц";
		$link[]="/?do=/admin&act=cats|Категории";
		$link[]="/?do=/admin&act=devices|Пользователи и устройства";
		$link[]="/?do=/admin&act=menu|Редактировать меню";
		$link[]="/?do=/admin&act=plugin|Плагины и Модули";
		$link[]="/?do=/admin&act=sets|Настройки сайта";
		$link[]="/?do=/admin&act=update|Обновление FXML CMS";
		$link[]="/?do=/admin&act=sync|Импорт/Экспорт сайта";
		$link[]="http://forkplayer.tv/donate/|\$Поддержать разработку FXML CMS\$";
		$lmenu="";
		for($i=0;$i<count($link);$i++){
			$a=explode("|",$link[$i]);
			if(!$friendly_links) { 
				
			}
			$lmenu.="<a class='left_link' href='".$a[0]."'>".$a[1]."</a><br>";
		}
		if($act=="sync"){
			$div.="Ваша версия: $siteversion<br>";
			if(!empty($_GET["import"])){
				if($_GET["cmd"]=="del"){
					if(unlink($_GET["import"])) $div.="$_GET[import] удален!<br>";
					else $div.="$_GET[import] ошибка удаления!<br>";
				}
				else{
					$zip = new ZipArchive;  
					$res = $zip->open("$_GET[import]");  
					if ($res === TRUE) {
						if($_POST["cmd"]=="apply"){
							if($_POST["db"]) {
								$sql=$zip->getFromName('fxmlcms.sql');
								$sql=str_replace("\r","",$sql);
								$errm="";$nf=0;
								foreach($result = explode(";\n", $sql) as $key=>$qq){
									$q=str_replace("\n","",trim($result[$key]));
									if(!empty($q)){
										mysql_query($q);
										$nf++;
										$errm.=mysql_error();
									}
								}
								$div.= "Mysql запросов: $nf<br>";
								if(!empty($errm)) $div.="Ошибки mysql: $errm<br>";
							}						
							if($_POST["config"]) {
								$tmp=$zip->getFromName('config.php');
								$tmp.="\$host = '$host';\n\$user = '$user';\n\$password = '$password';\n\$db = '$db';\n";
								if(file_put_contents("config.php",$tmp)) $div.= "config.php: OK (".filesize("config.php")."bytes)<br>";
								else $div.= "config.php: ERROR. Проверьте права на запись<br>";
							}
							if($_POST["uploads"]) {
								$nf=0;
								for($i = 0; $i < $zip->numFiles; $i++) {
									if(strpos($zip->getNameIndex($i),"uploads")===0){
										$zip->extractTo('.', array($zip->getNameIndex($i)));
										$nf++;
									}		
								}
												
								
								//$zip->extractTo("test");
								$div.= "uploads extract from backup ($nf files)<br>";
							}
							
						}
						else{
							$div.= "<form method=post>
						<input type=hidden name='cmd' value='apply'>
							$_GET[import]<br>";	
						
							if ($zip->locateName('fxmlcms.sql') !== false) $div.= "<input type=checkbox checked name='db' value=1> База данных (старые таблицы будут заменены)<br>";
							else $div.= "<input type=checkbox name='db' disabled> База данных (старые таблицы будут заменены)<br>";
							
							if ($zip->locateName('config.php') !== false) $div.= "<input type=checkbox checked name='config' value=1>  Конфигурация сайта (без mysql авторизации)<br>";
							else $div.= "<input type=checkbox name='config' disabled> Конфигурация сайта (без mysql авторизации)<br>";
							if ($zip->locateName('uploads/') !== false||1) $div.= "<input type=checkbox checked name='uploads' value=1>   Загруженные файлы (uploads) <br>";
							else $div.= "<input type=checkbox name='uploads' disabled>  Загруженные файлы (uploads) <br>";
							
							$div.= "<input type=submit value='Импортировать!' /></form>";
						}	
						$zip->close();				
					}
					else {  
						$div.= "$_GET[import] open failed<br>";  
					}
				}
			}
			elseif($_POST["cmd"]=="export"){
				if(!file_exists("backup")) mkdir("backup",0777);
				$zip = new ZipArchive();
				$destination="backup/fxmlcmsbackup_".rand(1000,9999)."_".date("Ymd_His").".zip";
				if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
					$div.= "failed to create zip file on destination";
				}
				else{
					if($_POST["db"]) {
						$tmp=backup_tables($host, $user, $password, $db, $dbprefix);
						$zip->addFromString("fxmlcms.sql", $tmp);
						$div.= "mysql dump {$dbprefix}* size ".strlen($tmp)."bytes<br>";
					}
					if($_POST["config"]) {
						$tmp=file_get_contents("config.php");
						$tmp=preg_replace("/\\$(host|user|password|db).*\r?\n?/","",$tmp).'$dbprefix = \''.$dbprefix.'\';'."\n";
						$zip->addFromString("config.php", $tmp);
						$div.= "config.php<br>";
					}
					
					if($_POST["uploads"]) {
						$source="uploads";
						$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);
						$nf=0;
						foreach ($files as $file) {
							$file = str_replace("\\", "/", $file);
							
							// Ignore "." and ".." folders					
							if(preg_match("/^uploads\/(updates|include)/",$file)) continue;
							if( in_array(substr($file, strrpos($file, '/')+1), array('.', '..')) )
								continue; 
				  
							if (is_dir($file) === true) {
								$zip->addEmptyDir($file . '/');
							} else if (is_file($file) === true) {
								$text=file_get_contents($file);
								$zip->addFromString($file, $text);
								$nf++;
							}
						}						
						$div.= "uploads add to backup ($nf)<br>";
					}
					$zip->close();
				}
				
				$div.= "Архив <a href='$destination'>$destination</a> создан  ".filesize($destination)."bytes<br>";
			}
			else{
				$div.="<br><b>Импортирование</b><br><i>Поместите бекап fxmlcmsbackup_rand_date_time.zip в папку backup</i><br><br>";
				$files = scandir("backup");
				rsort($files);
				foreach ($files as $file) {
					if (is_dir($file) !== true&&preg_match("/^fxmlcmsbackup_.*?_(.*)\.zip$/",$file,$arr)){
						$destination="backup/$file";
						$div.="<a href='$destination'>$arr[1]</a> размер  ".filesize($destination)."bytes - <a href='$siteurl/?do=/admin&act=sync&import=$destination&cmd=del'>Удалить</a> <a href='$siteurl/?do=/admin&act=sync&import=$destination'>Восстановить этот бекап</a> <br>";
					}
				}
				$div.="<br><br><b>Экспортирование</b><br>
				<form method=post>
				<input type=hidden name='cmd' value='export'>
				<input type=checkbox checked name='db' value=1> База данных<br>
				<input type=checkbox checked name='config' value=1> Конфигурация сайта (без mysql авторизации)<br>
				<input type=checkbox checked name='uploads' value=1> Загруженные файлы (uploads)<br>
				<input type=submit value='Создать бекап' /></form>";
				
			}
		}
		if($act=="update"){
			$s=explode("|",file_get_contents("http://xml.forkplayer.tv/updates/version.php"));
			if(preg_match("/[^0-9\.]/",$s[0])) exit("err version updates");
			if(!file_exists("uploads/updates/")) mkdir("uploads/updates",0777);
			if(!empty($_GET["cmd"])){
				$s2=file_get_contents($_GET["cmd"]);
				file_put_contents("uploads/updates/updates_$s[0].zip",$s2);
				$zip = new ZipArchive;  
				$res = $zip->open("uploads/updates/updates_$s[0].zip");  
				if ($res === TRUE) {
					$nd="include_old_".time()."_$siteversion";
					rename("include",$nd);
					 $div.= "Предыдущые файлы сохранены в папке $nd<br>";
					 $zip->extractTo(".");  
					 $zip->close();  
					 include(dirname(__FILE__)."/version.php");
					 $div.= "extract ok<br>Обновления $s[0] распакованы!<br>Ваша текущая версия: $siteversion";
				} else {  
					$div.= "zip updates failed extract<br>";  
				} 
			}
			else{
				$div.="Ваша версия: $siteversion<br>";
				$div.="Последняя версия: $s[0] ($s[1])<br>";
				 if (version_compare($s[0],$siteversion, '>')) {
					$div.="<a href='/?do=/admin&act=update&cmd=".urlencode($s[2])."'>Обновить</a><br><br>Как обновить вручную?<br> Скачайте архив <a href='$s[2]'>$s[2]</a> и залейте с него папку include на сервер c заменой файлов";
				}
			}
		}
		if($act=="addpage"){
			if(empty($_GET["op"])) $div="<div style='text-align:center;font-weight:bold;'>Добавить новую страницу</div>
			Создать из файла XML/M3U/XSPF <form action='/?do=/admin&act=addpage&op=uploadfile' enctype='multipart/form-data' method='POST'>    <input type='hidden'>    <input name='file' type='file'>     <input type='submit' value='Создать'></form><br>
			<a href='/?do=/admin&act=addpage&op=createxml'>Создать пустую страницу</a> - страница со всеми возможностями<br>
			";
			else{ 
				if($_GET["op"]=="uploadfile"){
					$f=file_get_contents($_FILES["file"]["tmp_name"]);
					if(strpos($f,"<channel>")!==false){
						$pf=parsexml($f);
					}
					elseif(strpos($f,"EXTINF")!==false){
						$pf=parsem3u($f);
					}					
					elseif(preg_match("/<track>(.*?)<\/track>/is",$f)){
						$pf=parsexspf($f);
					}
					else $div="<b>Файл не содержит поддерживаемого формата страницы (XML,M3U,XSPF)</b><hr>";
					if(!isset($_GET["editid"])){
						if(empty($pf["title"])) $pf["title"]=$_FILES["file"]["name"];
						if(empty($pf["icon"])) $pf["icon"]="$siteurl/include/templates/images/logo.png";
					}
					$_GET["op"]="createxml";					
				}
				if($_GET["op"]=="delete"){
					if(mysql_query("delete from {$dbprefix}page where id=\"".mysql_real_escape_string($_GET["id"])."\"")) $div.="Страница id #$_GET[id] удалена<hr>";
					else $div.="Такая страница id #$_GET[id] не найдена ".mysql_error()."<hr>";
					$act="listpage";
				}
				elseif($_GET["op"]=="savexml"){
					$ch=$_POST["pltag"];
					$ch["channels"]=json_decode($_POST["channels"],true);
					$category=",";
					foreach($_POST["category"] as $k=>$v) $category.="$v,";
					$access=",";
					foreach($_POST["access"] as $k=>$v) $access.="$v,";
					if(!empty($_GET["id"])){						
						if(($id=mysql_query("update {$dbprefix}page set category=\"".mysql_real_escape_string($category)."\",access=\"".mysql_real_escape_string($access)."\",src=\"".mysql_real_escape_string(json_encode($ch,JSON_UNESCAPED_UNICODE))."\", modified=".time().",icon=\"".mysql_real_escape_string($_POST["pltag"]["icon"])."\",title=\"".mysql_real_escape_string($_POST["pltag"]["title"])."\",seourl=\"".mysql_real_escape_string($_POST["seo"])."\",description=\"".mysql_real_escape_string($_POST["description"])."\",encrypt=\"".mysql_real_escape_string($_POST["encrypt"])."\",created=\"".mysql_real_escape_string($_POST["created"])."\",is_iptv=\"".mysql_real_escape_string($_POST["is_iptv"])."\",sticked=\"".mysql_real_escape_string($_POST["sticked"])."\" where id=\"".mysql_real_escape_string($_GET["id"])."\""))!==false) $div.="Страница #$_GET[id] $ch[title] сохранена<hr>";
						else $div.="Ошибка сохранения страницы ".mysql_error()."<hr>";
					}
					else{
						if(($id=mysql_query("insert into {$dbprefix}page set category=\"".mysql_real_escape_string($category)."\",access=\"".mysql_real_escape_string($access)."\",src=\"".mysql_real_escape_string(json_encode($ch))."\",icon=\"".mysql_real_escape_string($_POST["pltag"]["icon"])."\",title=\"".mysql_real_escape_string($_POST["pltag"]["title"])."\",description=\"".mysql_real_escape_string($_POST["description"])."\", modified=".time().",encrypt=\"".mysql_real_escape_string($_POST["encrypt"])."\",is_iptv=\"".mysql_real_escape_string($_POST["is_iptv"])."\",sticked=\"".mysql_real_escape_string($_POST["sticked"])."\",author=".$userinfo["id"].",seourl=\"".mysql_real_escape_string($_POST["seo"])."\""))!==false) $div.="Страница $ch[title] создана<hr>";
					else $div.="Ошибка создания страницы ".mysql_error()."<hr>";
					}
					category_recount();
					$act="listpage";
					//print_r($ch);
				}
				if($_GET["op"]=="createxml"){
					$q=q("select max(id) from {$dbprefix}page",1);
					if(empty($q[0])) $news_id=1;
					else $news_id=$q[0]+1;
					if(isset($pf)){
						$div.="\n<script>\nvar qch=".json_encode($pf).";\n channels=qch.channels;\n</script>\n";
					}
					
					if(isset($_GET["editid"])){
						$qch=q("select * from {$dbprefix}page where id=\"".mysql_real_escape_string($_GET["editid"])."\"",1);
						if(isset($pf)){
							$div.="\n<script>\n var qchfile=".json_encode($pf).";\n var qch=".$qch["src"].";\n channels=qchfile.channels;\n</script>\n";
						}
						else $div.="\n<script>\nvar qch=".$qch["src"].";\n channels=qch.channels;\n</script>\n";
						$news_id=$qch["id"];
					}
					if(empty($news_id)) $div.="Страницы $_GET[editid] нет<br>";
					else{
						
						$div.="<script>var chtag=\"$chtag\".split('|');
						var pltag=\"$pltag\".split('|');
						</script>";
						$chtag=explode("|",$chtag);
						$pltag=explode("|",$pltag);
						$div.="Свойства страницы:".((isset($pf))?" загружено с файла ".$_FILES["file"]["name"]." (".$_FILES["file"]["size"]."byte)":"")."<br>ID страницы: $news_id<br>";
						$pltaginfo=explode("|","Заголовок|Иконка(введите url или загрузите jpg, png - если оставить пустым будет браться иконка с родительской ссылки)|Вид(список,плитка)|Фоновая картинка(рекомендуется оставить пустым - будет значение фона из Настроек сайта)|url|server|next_page_url|prev_page_url|access|timeout|is_iptv|all_description|pageinfo");
						$div.="<form id='formxml' action='/?do=/admin&act=addpage&op=savexml&id=$_GET[editid]' method='POST'>";
						
						if(!isset($_GET["editid"])){
							$pltagvalue[0]="";
							$pltagvalue[1]="$siteicon";
						}
						else{
							$div.="\nДата (чем новее тем выше в публикациях на главной и в категориях)<br>\n<input type='datetime-local' name='created' value=\"".date("Y-m-d\TH:i",strtotime($qch["created"]))."\" REQUIRED/><br>\n";
							$pjson=json_decode($qch["src"],true);
							$pltagvalue[3]="$pjson[background_image]";
						}
						for($i=0;$i<count($pltag);$i++){
							if($i<4){ 
								$div.="$pltaginfo[$i]<br>";
								if($pltag[$i]=="icon"||$pltag[$i]=="background_image") $div.="<img align='left' src='$pltagvalue[$i]' onerror=\"this.style.display='none'\" id='img_$pltag[$i]' onmouseover=\"this.width='288';this.height='162';this.style.position='absolute';this.style.zIndex='2';\"  onmouseout=\"this.width='24';this.height='20';this.style.position='relative';this.style.zIndex='';\" width=24 height=20 />";
								
								if($pltag[$i]=="typeList") $div.= "<input type='radio' checked='true' name='pltag[$pltag[$i]]' value='' /> Список <input type='radio' name='pltag[$pltag[$i]]' value='start' /> Плитка";
								else $div.="<input style='width:400px;' id='pltagid_$pltag[$i]' name='pltag[$pltag[$i]]' value='$pltagvalue[$i]' />";
								if($pltag[$i]=="icon"||$pltag[$i]=="background_image") $div.=" <input type=\"file\" name=\"file_$pltag[$i]\" id=\"file_$pltag[$i]\" alt='ico_$news_id' />"; 
								$div.="<br>";							
							}
							else continue;						
						}
						$div.="Описание страницы<br>
						<div style=\"width: 450px;background: url($sitebackground);\">
						<textarea rows=10 cols=60 id='description' name='description' />$qch[description]</textarea></div>";
						$div.='
						<script>
							$("#description").htmlarea({
								css: "/include/templates/js/jHtml/jHtmlArea.Editor.css",
								toolbar: [
									["html","|","bold", "italic", "underline", "|", "forecolor"],
									["justifyLeft", "justifyCenter", "justifyRight","p"],
									["|", "image"]
								]}).parent().resizable({ alsoResize: $(this).find("iframe") });	
						</script>';
				
						$ch=q("select * from {$dbprefix}category");
						foreach ($ch as $k=>$v) { //Обходим массив
							if($v["parent"]==$v["id"]) $v["parent"]=0;
							$_getCat[$v["parent"]][] = $v;
						}
						function outTreeCat($parent, $level) {
							global $_getCat,$c,$qch;
							if (isset($_getCat[$parent])) { //Если категория с таким parent_id существует
								foreach ($_getCat[$parent] as $value) { //Обходим ее
									/**
									 * Выводим категорию 
									 *  $level * 25 - отступ, $level - хранит текущий уровень вложености (0,1,2..)
									 */
									$dp="";
									for($i=0;$i<$level;$i++) $dp.=" -";
									$c.= "<option value='$value[id]'".((isset($_GET["editid"])&&strpos($qch["category"],",$value[id],")!==false)?" selected":"")."> $dp $value[title] (".get_cat_roles($value["access"]).")</option>";
									$level++; //Увеличиваем уровень вложености
									//Рекурсивно вызываем этот же метод, но с новым $parent_id и $level
									outTreeCat($value["id"], $level);
									$level--; //Уменьшаем уровень вложености
								}
							}
						}
						$c="";
						outTreeCat(0, 0);
						$div.="Категории (Удерживайте ctrl чтобы отметить несколько!)<br>";
					

						$div.="<select multiple name='category[]' style='height:80px;'>
						<option value=''>Нет. (Всем)</option>
						$c 
						</select><br>
						";
						
						get_role();
						$c="";
						foreach($ROLE as $k=>$v){
							if($k==10||$k==9) continue;
							$ac.="<option value='$k'".((isset($_GET["editid"])&&strpos($qch["access"],",$k,")!==false)?" selected":"").">$v</option>";
						}
						mysql_query("ALTER TABLE {$dbprefix}page ADD access VARCHAR(20) default ''");	
						
						$div.="Роли сайта которым доступна страница (Удерживайте ctrl чтобы отметить несколько!)<br>";	
						$div.="<select multiple name='access[]' style='height:60px;'>
						<option value='0'".((empty($qch["access"])||strpos($qch["access"],",0,")!==false)?" selected":"").">Автоматически (Из настроек категории, рекомендуется)</option>
						$ac 
						</select><br>
						";
						
						$div.="<input type='checkbox' name='sticked'".(($qch["sticked"]>0)?" checked":"")." value='1' /> Отображать страницу всегда вверху на главной и в категориях<br>";
						$div.="<input type='checkbox' id='encrypt' name='encrypt'".(($qch["encrypt"]>0)?" checked":"")." value='1' /> Шифровать ссылки (только на видеопотоки stream_url)<br>";
						$div.="<input type='checkbox' name='is_iptv'".(($qch["is_iptv"]>0)?" checked":"")." value='1' /> Это страница со стримами  телеканалов (для отображения тв программы)<br>";
						$div.="<input type='hidden' value='' id='pl_channels' name='channels' /><br><input type='button' value='Сохранить' onclick='upload_page();' />
						</form><br>";
						
						if(isset($_GET["editid"])) {
							if(isset($pf)) $div.="<div style='border:1px solid gray;border-radius:4px;padding:4px;'>Список из файла загружен но не сохранен! Нажмите Сохранить для внесения изменений!</div>";
							else $div.="<div style='border:1px solid gray;border-radius:4px;padding:4px;'>Загрузить список из файла XML/M3U/XSPF <form action='/?do=/admin&act=addpage&op=uploadfile&editid=$_GET[editid]' enctype='multipart/form-data' method='POST'>    <input type='hidden'>    <input name='file' type='file'>     <input type='submit' value='Загрузить файл'></form></div>";
						}
						$div.="<div id='edit' style='width:780px;padding: 10px;position:absolute;display:none;    z-index: 1;    margin: 100px 0px 0px 30px;borrder:1px solid gray;'></div> 
						<div id='pr' style='background: url(/include/templates/images/fon20.jpg);'>
						<div id='ch' style='padding: 4px 4px 250px 4px;font-size:20px;margin-top:20px;;min-height:600px;color: $sitecolor;'></div>
						</div>
						<script>
						var channelsmenu=".json_encode($_MENU).";
						
						".((isset($_GET["editid"])||isset($pf))?"for(var i=0;i<pltag.length;i++){
								if(pltag[i]=='background_image') continue;
								if(typeof qch[pltag[i]] == 'undefined') qch[pltag[i]]='';
								if(pltag[i]=='typeList'){
									if(qch.typeList=='start') $('[name=pltag\\\\[typeList\\\\]]').val(['start']);
								}
								if($('#pltagid_'+pltag[i])!=null) $('#pltagid_'+pltag[i]).val(qch[pltag[i]]);
								
								if(pltag[i]=='icon'||pltag[i]=='background_image'){
									$('#img_'+pltag[i]).prop('src', qch[pltag[i]]);
									$('#img_'+pltag[i]).show();
								}
							}
							for(var i=0;i<channels.length;i++){
								for(var j=0;j<chtag.length;j++) if(typeof channels[i][chtag[j]] == 'undefined') channels[i][chtag[j]]='';
							}
							listch();":"addch();")."</script>";
					}
				}
				
				
				
			}
			
		}
		if($act=="plugin"){
			$qe=q("select * from {$dbprefix}meta where `key`='[PLUGIN_ENABLED]'",1);
			if($_GET["op"]=="save"){
				$div="";
				$ch=[];
				foreach($_POST["op"] as $k=>$v){
					if($v) $ch[$k]=["enabled"=>1];
				}				
				
				if(!isset($qe["id"])) mysql_query("insert into {$dbprefix}meta set uid='".mysql_real_escape_string($userinfo["id"])."', `key`=\"".mysql_real_escape_string('[PLUGIN_ENABLED]')."\", src=\"".mysql_real_escape_string(json_encode($ch))."\"");
				else mysql_query("update {$dbprefix}meta set uid='".mysql_real_escape_string($userinfo["id"])."', src=\"".mysql_real_escape_string(json_encode($ch))."\" where id='$qe[id]'");
				$qe=q("select * from {$dbprefix}meta where `key`='[PLUGIN_ENABLED]'",1);
			}
			if(isset($qe["id"])) $a=json_decode($qe["src"],true);
			if(isset($_GET["id"])){
				$PLUGIN=getInfoPlugin($_GET["id"]); 
				$div.="<b>Настройки плагина</b><br>id: $PLUGIN[id]<br>Имя: $PLUGIN[name]<br><br>";
				if($PLUGIN["enabled"]){
					include dirname(__FILE__)."/plugin/$PLUGIN[id]/settings.php";
					$div.=$echo;
					
				}
				else $div.="Плагин $PLUGIN[id] $PLUGIN[name] выключен!<br>";
			}
			else{
				$d=scandir(dirname(__FILE__).'/plugin');
				$div.="<form id='formxml' action='/?do=/admin&act=plugin&op=save' method='POST'>";
				foreach($d as $k=>$v){
					if(is_dir(dirname(__FILE__)."/plugin/$v")&&$v!="."&&$v!=".."){
						$inf=getInfoPlugin($v);
						$div.="<input type='checkbox' name='op[$v]'".(isset($a[$v])?" checked":"")." value='1' title='$inf[description]' /> <a href='/?do=/plugin&id=$v'>$v</a> $inf[name] <a href='/?do=/admin&act=plugin&id=$v'>Настроить</a> $inf[version]<br><i>$inf[description]</i><hr>";
					}
				}
				
				$d=scandir(dirname(__FILE__).'/module');
				foreach($d as $k=>$v){
					if(is_dir(dirname(__FILE__)."/module/$v")&&$v!="."&&$v!=".."){
						$inf=getInfoModule($v);
						$div.="<input type='checkbox' name='op[module$v]' disabled checked value='1' title='$inf[description]' /> <a href='/?do=/module&id=$v&act=profile'>Модуль $v</a> $inf[name] <a href='/?do=/module&id=$v&act=settings'>Настроить</a> $inf[version]<br><i>$inf[description]</i><hr>";
					}
				}
				
				$div.="<br><input type='submit' value='Сохранить' />
					</form><br>";
			}
		}
		if($act=="menu"){
			$qe=q("select * from {$dbprefix}meta where `key`='[MENU_ENABLED]'",1);
			$menuSets=json_decode($qe["src"],true);
			if($_GET["op"]=="save"){
				$div="";
				$ch=[];
				foreach($_POST["op"] as $k=>$v){
					if($v) $ch[]=["title"=>$k];
				}
				
				foreach($_POST["userlink"] as $k=>$v){
					$ch[]=json_decode($v,true);	
				}
				if(!empty($_POST["userlink"])) {
									
				}
				$menuSets=["enabled"=>$_POST["enabled"]];
				if($_POST["enabledbackground_color"]) $menuSets["backgroundColor"]=$_POST["background_color"];
				if($_POST["enabledcolor"]) $menuSets["color"]=$_POST["color"];
				if(!isset($qe["id"])) mysql_query("insert into {$dbprefix}meta set uid='".mysql_real_escape_string($userinfo["id"])."', `key`=\"".mysql_real_escape_string('[MENU_ENABLED]')."\", src=\"".mysql_real_escape_string(json_encode($menuSets))."\"");				
				else mysql_query("update {$dbprefix}meta set uid='".mysql_real_escape_string($userinfo["id"])."', src=\"".mysql_real_escape_string(json_encode($menuSets))."\" where id=\"".mysql_real_escape_string($qe["id"])."\"");
				
				$q=q("select id from {$dbprefix}meta where `key`='[MENU]'",1);
				if(isset($q["id"])){
						if(mysql_query("update {$dbprefix}meta set uid='".mysql_real_escape_string($userinfo["id"])."',src=\"".mysql_real_escape_string(json_encode($ch))."\" where id=\"".mysql_real_escape_string($q["id"])."\"")) $div.="Меню сохранено<hr>";
						else $div.="Ошибка сохранения меню ".mysql_error()."<hr>";
					}
					else{
						if(mysql_query("insert into {$dbprefix}meta set uid='".mysql_real_escape_string($userinfo["id"])."', `key`=\"".mysql_real_escape_string('[MENU]')."\", src=\"".mysql_real_escape_string(json_encode($ch))."\"")) $div.="Меню создано<hr>";
						else $div.="Ошибка создания меню ".mysql_error()."<hr>";
					}
				header("Location: $siteurl/?do=/admin&act=menu&text=".urlencode($div));
				exit;
			}
			$div.="<b>Настройки меню</b><br>";
			$div.=$_GET["text"];
			$a=[];
			$q=get_menu(); 
			$q=$q["src"];
			$sm="";		
			for($i=0;$i<count($q);$i++){
				$a[$q[$i]["title"]]=1;
				if($q[$i]["type"]=="module"){
					$infModule=getInfoModule($q[$i]["title"]);
					$sm.="<input type='checkbox' name='userlink[".$q[$i]["title"]."]'".($a[$q[$i]["title"]]?" checked":"")." value='".str_replace("'","\\'",json_encode($q[$i]))."' /> Модуль ".$q[$i]["title"]." (".$infModule["name"].")<br>";
				}
				elseif(isset($q[$i]["playlist_url"]))
					$sm.="<input type='checkbox' name='userlink[]' checked value='".str_replace("'","\\'",json_encode($q[$i]))."' /> ".$q[$i]["title"]." (".$q[$i]["playlist_url"]." ".$q[$i]["stream_url"].")<br>";
			}
			
			$modules=getModules();
			foreach($modules as $k=>$v){
				$infModule=getInfoModule($v["id"]);
				if(!$a[$v["id"]]) $sm.="<input type='checkbox' name='userlink[".$v["id"]."]'".($a[$v["id"]]?" checked":"")." value='".str_replace("'","\\'",json_encode(["title"=>$v["id"],"type"=>"module"]))."' /> Модуль ".$v["id"]." (".$infModule["name"].")<br>";
			}
			$div.="<form id='formxml' action='/?do=/admin&act=menu&op=save' method='POST'>
				<input type='hidden' id='pltagid_title' name='o[title]' value='".$sitename."' />
				<input type='hidden' id='pltagid_icon' name='pltag[icon]' value='$siteicon' />
				<input type='hidden' id='pltagid_background_image' name='pltag[background_image]' value='$sitebackground' />
				<input type='hidden' id='pltagid_color' name='o[color]' value='$sitecolor' />
				<input type='checkbox' id='enabled' name='enabled'".($menuSets["enabled"]?" checked":"")." value='1' /> Отображать меню сайта<br>
				<input type='checkbox' id='enabledbackground_color' name='enabledbackground_color'".(!empty($menuSets["backgroundColor"])?" checked":"")." value='1' /> <input type='color' id='background_color' name='background_color' value='".(!empty($menuSets["backgroundColor"])?$menuSets["backgroundColor"]:"")."' /> Задать цвет фона меню<br>
				<input type='checkbox' id='enabledcolor' name='enabledcolor'".(!empty($menuSets["color"])?" checked":"")." value='1' />  <input type='color' id='color' name='color' value='".(!empty($menuSets["color"])?$menuSets["color"]:"")."' /> Задать цвет текста меню<br>
				<hr>
				Структура меню<br>
				<input type='checkbox' name='op[main]'".($a["main"]?" checked":"")." value='1' /> Главная<br> 
				<input type='checkbox' name='op[category]'".($a["category"]?" checked":"")." value='1' /> Выпадающий список всех категорий и плагинов<br>
				<input type='checkbox' name='op[new]'".($a["new"]?" checked":"")." value='1' /> Выпадающий список последних новых страниц<br>
				<input type='checkbox' name='op[pop]'".($a["pop"]?" checked":"")." value='1' /> Выпадающий список популярных страниц<br>
				<input type='checkbox' name='op[roles]'".($a["roles"]?" checked":"")." value='1' /> Показывать роль пользователя в конце меню<br>
				$sm
				<input id='userlink' type='hidden' name='userlink[]' value='' />
				<a id='ch-1' href='javascript:edit(-1);'>Добавить свою ссылку в меню</a>
				<hr><br>
				
				<br><input type='submit' value='Сохранить' />
				</form><br>
				Пример отображения портала в ForkPlayer<br>";
				$div.="<div id='edit' style=' background-color: white;width:780px;padding: 10px;position:absolute;display:none;    z-index: 1;    margin: 100px 0px 0px 30px;borrder:1px solid gray;'></div> 
						<div id='pr' style='background: url(/include/templates/images/fon20.jpg);'>
						<div id='ch' style='padding: 4px 4px 250px 4px;font-size:20px;margin-top:20px;;min-height:600px;color: rgb(238, 238, 238);'></div>
						</div>
						<script>
						var chtag=\"$chtag\".split('|');
						var pltag=\"$pltag\".split('|');						
						var menu=".json_encode($_MENU).";
						var channelsmenu=menu;
						addch();
						</script>	
						";
			
		}		
		
		if($act=="sets"){
			$div.="Настройки сайта<br>";
			if($_GET["op"]=="save"){
				savePluginMetaKey("[MAINBEFORE]",$_POST["pltag"]["before"],"update");
				$f=file_get_contents("config.php");
				$f=preg_replace("/\\\$sitename.*?;/","\$sitename='".preg_replace("/('|\"|\\\)/","",$_POST["op"]["title"])."';",$f);
				$f=preg_replace("/\\\$sitebackground.*?;/","\$sitebackground='".preg_replace("/('|\"|\\\)/","",$_POST["pltag"]["background_image"])."';",$f);				
				$f=preg_replace("/\\\$sitecolor.*?;/","\$sitecolor='".preg_replace("/('|\"|\\\)/","",$_POST["op"]["color"])."';",$f);
				$f=preg_replace("/\\\$siteicon.*?;/","\$siteicon='".preg_replace("/('|\"|\\\)/","",$_POST["pltag"]["icon"])."';",$f);
				$f=preg_replace("/\\\$sitepageinfo.*?;/","\$sitepageinfo='".preg_replace("/('|\"|\\\)/","",$_POST["op"]["pageinfo"])."';",$f);
				$f=preg_replace("/\\\$typelistStart.*?;/","\$typelistStart='".preg_replace("/('|\"|\\\)/","",$_POST["pltag"]["typeList"])."';",$f);
				
				if(strpos($f,'$typelinkPage')===false) $f.="\n\$typelinkPage='".$_POST["pltag"]["typelinkPage"]."';";
				else $f=preg_replace("/\\\$typelinkPage.*?;/","\$typelinkPage='".preg_replace("/('|\"|\\\)/","",$_POST["pltag"]["typelinkPage"])."';",$f);
				
				if(strpos($f,'$showStat')===false) $f.="\n\$showStat='".$_POST["pltag"]["showStat"]."';";
				else $f=preg_replace("/\\\$showStat.*?;/","\$showStat='".preg_replace("/('|\"|\\\)/","",$_POST["pltag"]["showStat"])."';",$f);
				
				
				$f=preg_replace("/\\\$sitechbkg.*?;/","\$sitechbkg='".preg_replace("/('|\"|\\\)/","",hex2rgba($_POST["op"]["chbkg"],$_POST["op"]["chbkgrange"]))."';",$f);
				
				$f=preg_replace("/\\\$sitechcolor.*?;/","\$sitechcolor='".preg_replace("/('|\"|\\\)/","",hex2rgba($_POST["op"]["chcolor"]))."';",$f);
				
				if(file_put_contents("config.php",$f)) {
					print "Настройки сохранены в config.php<hr><a href='/?do=/admin&act=sets'>Продолжить</a>";
					
					exit;
				}
				else $div.= "Ошибка записи в файл! Запишите текст ниже в файл config.php вручную!<br><textarea cols=150 rows=30 >$f</textarea><hr>";
			}
			$before=getPluginMetaKey("[MAINBEFORE]",false);
			$before=$_PL["style"]["cssid"]["content"]["before"]=$before[0]["src"];

			if(strpos($sitechbkg,"rgb")!==false)
				$sitechbkg=rgba2hex($sitechbkg);
			if(strpos($sitechcolor,"rgb")!==false)
				$sitechcolor=rgba2hex($sitechcolor);
			$sitechbkgC=substr($sitechbkg,0,7);
			$sitechbkgA=substr($sitechbkg,7,2);
			if(strlen($sitechbkgA)==2) $sitechbkgA=round(hexdec($sitechbkgA)/255,2);
			else $sitechbkgA="1.0";
			$before=getPluginMetaKey("[MAINBEFORE]",false);
			$before=$before[0]["src"];
			$div.="<form id='formxml' action='/?do=/admin&act=sets&op=save' method='POST'>
				Название
				<br>
				<input id='pltagid_title' name='op[title]' value='".$sitename."' /><br>
				Описание портала (256 символов, учитывается поисковой системой)
				<br>
				<input id='pltagid_pageinfo' name='op[pageinfo]' style='width:800px;' value='".$sitepageinfo."' /><br>
				Иконка главной страницы<br>
				<img  align='left' src='' onerror=\"this.style.display='none'\" id='img_icon' width=20 height=18 />
				<input style='width:400px;' id='pltagid_icon' name='pltag[icon]' value='$siteicon' />
				<input type=\"file\" name=\"file_icon\" id=\"file_icon\" /><br>
				
				Фон для всех страниц (1280x720)<br>
				<input style='width:400px;' id='pltagid_background_image' name='pltag[background_image]' value='$sitebackground' />
				<input type=\"file\" name=\"file_background_image\" id=\"file_background_image\" /><br>
				Вид главной страницы
				<input type='radio' ".($typelistStart==''?" checked":"")." name='pltag[typeList]' value='' /> Список <input type='radio' name='pltag[typeList]' ".($typelistStart=='start'?" checked":"")." value='start' /> Плитка<br>
				
				Вид адресов ссылок на страницы:<br>
				<input type='radio' ".($typelinkPage==''?" checked":"")." name='pltag[typelinkPage]' value='' /> /?do=/fml&id=<b>ID-TITLE</b> (рекомендуется для индексации поисковой системой Spider)<br> 
				<input type='radio' name='pltag[typelinkPage]' ".($typelinkPage=='id'?" checked":"")." value='id' /> /?do=/fml&id=<b>ID</b> <br>
				
				<input type='checkbox' ".($showStat?" checked":"")." name='pltag[showStat]' value='1' /> Показывать посещаемость (хиты)<br>
				
				Цвет текста<br>
				<input id='pltagid_color' type='color' name='op[color]' value='$sitecolor' /><br>
				Фон ссылки при выделении<br>
				Цвет: <input id='pltagid_chbkg' type='color' name='op[chbkg]' value='$sitechbkgC' /> Прозрачность: прозр. <input id='pltagid_chbkgrange' name='op[chbkgrange]' type='range' min='0' max='1' step='0.05' value='$sitechbkgA'> непрозр. - если полностью прозр. то будет установлено none<br>
				Цвет текста ссылки при выделении<br>
				<input id='pltagid_chcolor' type='color' name='op[chcolor]' value='$sitechcolor' /><br>
				Текст вверху сайта на главной странице<br>
						<div style=\"width: fit-content;background: url($sitebackground);\">
						<textarea rows=18 cols=80 id='pltag_before' name='pltag[before]' />$before</textarea></div>".'
						<script>
							$("#pltag_before").htmlarea({
								css: "/include/templates/js/jHtml/jHtmlArea.Editor.css",
								toolbar: [
									["html","|","bold", "italic", "underline", "|", "forecolor"],
									["justifyLeft", "justifyCenter", "justifyRight","p"],
									["|", "image"]
								]});	
						</script>'."
				<br>
				<br><input type='submit' value='Сохранить' />
				</form><br>
				Пример отображения портала в ForkPlayer<br>";
				$div.="<div id='edit' style='width:780px;padding: 10px;position:absolute;display:none;    z-index: 1;    margin: 100px 0px 0px 30px;borrder:1px solid gray;'></div> 
						<div id='pr' style='background: url(/include/templates/images/fon20.jpg);'>
						<div id='ch' style='padding: 4px 4px 250px 4px;font-size:20px;margin-top:20px;;min-height:600px;color: rgb(238, 238, 238);'></div>
						</div>
						<script>
						var chtag=\"$chtag\".split('|');
						var pltag=\"$pltag\".split('|');						
						var menu=".json_encode($_MENU).";					
						var PL=".json_encode($_PL).";
						var channelsmenu=menu;
						addch();
						listch(true);
						</script>	
						";
			
		}
		if($act=="adddevice"){
			if($_GET["opdev"]=="delall"){
				$div.="Удалить все устройства из роли<hr>";
				foreach($ROLE as $k=>$v){
					$qc=q("select count(id) from {$dbprefix}device where role='$k'",1);
					if($qc[0]>0) $div.=get_role($k)."<a style='float:right;' href=\"javascript:if(confirm('Вы уверены что хотите удалить все устройства($qc[0] из ".get_role($k).")?')) location='/?do=/admin&act=$act&is_user=0&op=delete&roleid=$k&mac=all';\">Удалить все устройства($qc[0])</a><br>";
				}
				$qc=q("select count(id) from {$dbprefix}device where role=''",1);
				if($qc[0]>0) $div.="Без роли <a style='float:right;' href=\"javascript:if(confirm('Вы уверены что хотите удалить все устройства($qc[0] без роли)?')) location='/?do=/admin&act=$act&is_user=0&op=delete&roleid=&mac=all';\">Удалить все устройства($qc[0])</a><br>";
			}
			elseif($_GET["opdev"]=="import"){
				get_role();
				$c=""; 
				foreach($ROLE as $k=>$v){
					if($k!=10&&$k!=9&&$k>5) $c.="<option value='$k'".((isset($_GET["editid"])&&$q["role"]==$k)?" selected":"").">$v</option>";
				}
				$div.=" Импортировать текстовый документ с мак адресами <br>
			 Формат: mac info ДД/ММ/ГГГГ в конце строки до которой предоставлять доступ<br>
			<form action='/?do=/admin&act=adddevice&op=addfile&is_user=0' enctype='multipart/form-data' method='POST'>
			<input type=\"file\" name=\"file_mac\" id=\"file_mac\" alt='' /><br>
			<input type='checkbox' name='repl' value='1' /> Заменять существующие<br>
			Роль сайта: <br>
			<select name='role' onchange=''>
					$c
			</select><br><br>
			<input type='submit' value='Загрузить' /></form><br>";
				
				
			}
			elseif($_GET["opdev"]=="export"){
				$div.="Экспортировать список в виде текстового документа<hr>";
				foreach($ROLE as $k=>$v){
					$qc=q("select count(id) from {$dbprefix}device where role='$k'",1);
					if($qc[0]>0) $div.=get_role($k)." <a href='/?do=/admin&act=adddevice&op=exportfile&roleid=$k&is_user=0'>Скачать ($qc[0])</a><br>";
				}
				
			}
			elseif($_GET["is_user"]||isset($_POST["op"]["email"])){
				if($_GET["op"]=="delete"){
					if(mysql_query("delete from {$dbprefix}users where id=\"".mysql_real_escape_string($_GET["id"])."\"")) $div.="Пользователь id #$_GET[id] удален<hr>";
					else $div.="Такой пользователь с id #$_GET[id] не найден ".mysql_error()."";
				} 
				elseif($_GET["op"]=="save"){
					$dd=0;
					if($_POST["op"]["dateto"]==0){
						$div.="Доступ навсегда<br>";
						$d=",dateto='0'";
					}
					elseif(strpos($_POST["op"]["dateto"],".")>0) {
						$dd=strtotime($_POST["op"]["dateto"]);
						$div.="Доступ до даты: ".date("d.m.Y",$dd)." <br>";
						$d=",dateto='".mysql_real_escape_string($dd)."'";
					}
					elseif(intval($_POST["op"]["dateto"])>0) {
						$div.="Доступ до даты: ".date("d.m.Y",time()+intval($_POST["op"]["dateto"])*24*3600)." <br>";
						$dd=time()+intval($_POST["op"]["dateto"])*24*3600;
						$d=",dateto='".mysql_real_escape_string($dd)."'";
					}
					else $d="";
						
					$access=",";
					if(preg_match("/[^a-z0-9_\.]/i",$_POST["op"]["login"]))  $err.="Ошибка!<br><span style='color:red;'>Логин может содержать только латинницу, _ и . (".$_POST["op"]["login"]."!)</span><br>";					
					elseif(!filter_var($_POST["op"]["email"], FILTER_VALIDATE_EMAIL))  $err.="<br><span style='color:red;'>Email ".$_POST["op"]["email"]." не верный!</span><br>";
					elseif($_POST["op"]["pass1"]!=$_POST["op"]["pass1"]) $div.="Ошибка! Пароли не совпадают<br>";
					else if(!empty($_GET["id"])){
						if(empty($_POST["op"]["pass1"])) $setp="";
						else $setp=", password=\"".mysql_real_escape_string(md5($secret.$_POST["op"]["pass1"]))."\"";
						if($_POST["op"]["role"]<10) $setp.= ",role=\"".mysql_real_escape_string($_POST["op"]["role"])."\"";
						
						$MYCH=["mylinkonmain"=>$_POST["mylinkonmain"]?1:0,"mylinkonmenu"=>$_POST["mylinkonmenu"]?1:0,"channels"=>[]];
						foreach($_POST["mych"] as $v){
							if(!empty($v["playlist_url"])) $MYCH["channels"][]=$v;
						}
						savePluginMetaKey("[MYMENU_$_GET[id]]",json_encode($MYCH),"update");
						
						
						if(($id=mysql_query("update {$dbprefix}users set login=\"".mysql_real_escape_string($_POST["op"]["login"])."\",`email`=\"".mysql_real_escape_string($_POST["op"]["email"])."\"$setp$d where id=\"".mysql_real_escape_string($_GET["id"])."\""))!==false) $div.="Пользователь ".$_GET["id"]." сохранен<br>";
						else $div.="Ошибка! ".mysql_error()."<br>";
					}
					else{
						if(strlen($_POST["op"]["pass1"])<4) $div.="Ошибка! Пароль должен иметь длину не меньше 4!<br>";
						elseif(register_user($_POST["op"]["login"],$_POST["op"]["email"],$_POST["op"]["pass1"],$_POST["op"]["mac"],$_POST["op"]["role"],$dd)) $div.="Пользователь ".$_POST["op"]["login"]." добавлен<br>";
						else $div.="Ошибка! ".mysql_error()."<br>";
						
					}					
					$_GET["editid"]=$_GET["id"];
				}
				$div.="<hr><a href='/?do=/admin&act=devices&roleid=".$_POST["op"]["role"].$_GET["roleid"]."' style='border: 1px solid #ccc;
    border-radius: 2px;
    padding: 3px;
	margin:3px 0px;    text-decoration: none;
    background: #f7f7f72e;'>Вернуться в список пользователей и устройств</a>  <hr>";
				
				if($_GET["op"]!="delete"){
					if(isset($_GET["editid"])){
							$q=q("select * from {$dbprefix}users where id='".mysql_real_escape_string($_GET["editid"])."'",1);
							$MYCH=getPluginMetaKey("[MYMENU_$_GET[editid]]",true);
							$MYCH=$MYCH[0]["src"];
					}
					if(isset($_GET["editid"])&&!isset($q[0])) $div.="Такого пользователя нет или вы его только создали<br>";
					else{
						get_role();
						$c=""; 
						foreach($ROLE as $k=>$v){
							if($k!=10&&$k!=3) $c.="<option value='$k'".((isset($_GET["editid"])&&$q["role"]==$k||!isset($_GET["editid"])&&4==$k)?" selected":"").">$v</option>";
						}
						$div.="".(isset($_GET["editid"])?"Редактирование пользователя":"Создать пользователя")." $_GET[editid]<br>
						<form id='formxml' action='/?do=/admin&act=$act&op=save&id=$_GET[editid]&is_user=$_GET[is_user]' method='POST'>
						Логин<br>
						<input name='op[login]' value='".(isset($_GET["editid"])?"$q[login]":"")."' /><br>
						Email<br>
						<input type='email' name='op[email]' value='".(isset($_GET["editid"])?"$q[email]":"")."' /><br>
						Мак адрес (не обязательно)<br>
						<input name='op[mac]' value='".(isset($_GET["editid"])?"$q[mac]":"")."' /><br>
						".(isset($_GET["editid"])?"Установить новый":"Задать")." пароль<br>
						<input type='password' name='op[pass1]' value='' /><br>
						Повторите пароль<br>
						<input type='password' name='op[pass2]' value='' /><br>
						
						Роль сайта (По умолчанию после регистрации дается роль Пользователь)<br>
						".($q["role"]==10?"<input type=hidden name='op[role]' value='10' >Администратор":"
						<select name='op[role]' onchange=''>
						$c
						</select>")."<br>
						 На дней (0 - навсегда) Или дата dd.mm.YYYY - По окончанию срока вернется роль Пользователь<br>".'			
			<input name="op[dateto]" id="dateto" list="term" value="'.($q["dateto"]>0?date("d.m.Y",$q["dateto"]):$q["dateto"]).'" />
			<datalist id="term">
				<option value="0">Навсегда</option>
				<option value="03">3 дня</option>
				<option value="30">30 дней</option>
				<option value="90">90 дней</option>
				<option value="01.01.2020">До 01.01.2020</option>
			</datalist>	<br>';
						if(isset($_GET["editid"])){
							$div.="Мои ссылки<br>
							<div style='border:1px solid gray;border-radius:5px;'>
							<input type='checkbox'".($MYCH["mylinkonmain"]?" checked":"")." name='mylinkonmain' value='1' /> Отобразить на главной сайта<br>
							<input type='checkbox'".($MYCH["mylinkonmenu"]?" checked":"")." name='mylinkonmenu' value='1' /> Отобразить в меню сайта<br>
							<table><tr><td>URL</td><td>TITLE</td><td>ICON</td></tr>";
							for($i=0;$i<count($MYCH["channels"])||$i<3;$i++){						
								$div.="<tr><td><input name='mych[$i][playlist_url]' value='".$MYCH["channels"][$i]["playlist_url"]."' /> </td><td><input name='mych[$i][title]' value='".$MYCH["channels"][$i]["title"]."' /></td><td> <input name='mych[$i][logo_30x30]' value='".$MYCH["channels"][$i]["logo_30x30"]."' /> </tr>";
							}					
							$div.="</table>
							</div>";
						}
						$div.="<br>
						<input type='submit' value='".(isset($_GET["editid"])?"Сохранить":"Создать")."' />
						</form>";
						
					}
				}
			}
			
			
			else{
				mysql_query("ALTER TABLE {$dbprefix}device ADD about VARCHAR(200) default ''");	
				if($_GET["op"]=="exportfile"){
					$a=q("select * from {$dbprefix}device where role='".mysql_real_escape_string($_GET["roleid"])."' order by id asc");
					$s="";
					for($i=0;$i<count($a);$i++){
						$s.=$a[$i]["mac"]." ".$a[$i]["about"]."".($a[$i]["dateto"]>0?" ".date("d/m/Y",$a[$i]["dateto"]):"")."\r\n";
					}
					header('Content-Type: application/octet-stream');
					header("Content-Transfer-Encoding: Text"); 
					header("Content-disposition: attachment; filename=\"" . date("Y_m_d_H_i")."_role_".$_GET["roleid"]."_forkmac($i).txt" . "\""); 
					print $s;
					exit;
				}
				if($_GET["op"]=="addfile"){
					$s= file($_FILES["file_mac"]["tmp_name"]);
					for($i=0;$i<count($s);$i++){
						preg_match_all("/([0-9a-fntsphlgyz]{12,16})/i",$s[$i],$a);
						preg_match("/(\d{1,2}\/\d{1,2}\/\d{1,4}).?$/i",$s[$i],$dt);
						$about=preg_replace("/[0-9a-fntsphlgyz]{12,16}/","",$s[$i]);
						$about=preg_replace("/(\d{1,2}\/\d{1,2}\/\d{1,4}).?$/","",$about);
						$about=trim(preg_replace("/\s+/"," ",$about));
						for($j=0;$j<count($a[1]);$j++){
							$a[1][$j]=strtolower($a[1][$j]);
							if(strlen($a[1][$j])<12) continue;
							$div.= "<br> add ".$a[1][$j]." $dt[1] ($about) "; 
							if(isset($dt[1])){
								$tm=strtotime(str_replace('/', '-',$dt[1]));
							}
							else $tm=0;
							$mq=q("select * from {$dbprefix}device where mac='".mysql_real_escape_string($a[1][$j])."' and role='".mysql_real_escape_string($_POST["role"])."'",1);
							if($_POST["repl"]&&isset($mq[0])){
								mysql_query("delete from axml_device where id='$mq[id]'");
								$div.=" удалена старая запись ";
								$mq=[];
							}
								
							if(!isset($mq[0])) {
								if(mysql_query("insert into {$dbprefix}device set mac='".mysql_real_escape_string($a[1][$j])."',role='".mysql_real_escape_string($_POST["role"])."',about='".mysql_real_escape_string($about)."',modified=NOW(),dateto='$tm'")) $div.=" добавлен в ".get_role($_POST["role"])."";
								else $div.= "Ошибка ".mysql_error()."<br>";
							}
							else $div.=" уже существует в ".get_role($_POST["role"])."";
						}
						
					}
					}
					if($_GET["op"]=="delete"){
						if($_GET["mac"]=="all"){
							mysql_query("delete from axml_device where role='".mysql_real_escape_string($_GET["roleid"])."'");
						}
						else mysql_query("delete from axml_device where mac='".mysql_real_escape_string($_GET["mac"])."' and id='".mysql_real_escape_string($_GET["id"])."'");
						$div.=" Удален  $_GET[mac]<br>";
					}
						
					if($_GET["op"]=="add"){
						if($_GET["dateto"]==0){
							$div.="Доступ навсегда<br>";
							$d=",dateto='0'";
						}
						elseif(strpos($_GET["dateto"],".")>0) {
							$d2=strtotime($_GET["dateto"]);
							$div.="Доступ до даты: ".date("d.m.Y",$d2)." <br>";
							$d=",dateto='".mysql_real_escape_string($d2)."'";
						}
						elseif(intval($_GET["dateto"])>0) {
							$div.="Доступ до даты: ".date("d.m.Y",time()+intval($_GET["dateto"])*24*3600)." <br>";
							$d=",dateto='".mysql_real_escape_string(time()+intval($_GET["dateto"])*24*3600)."'";
						}
						else $d="";
						if(!empty($_GET["editid"])) $mq=q("select * from {$dbprefix}device where id='".mysql_real_escape_string($_GET["editid"])."'",1);
						else $mq=q("select * from {$dbprefix}device where mac='".mysql_real_escape_string($_GET["mac"])."' and role='".mysql_real_escape_string($_GET["roleid"])."'",1);
						if(!isset($mq[0])) {
							if(mysql_query("insert into {$dbprefix}device set mac='".mysql_real_escape_string($_GET["mac"])."'$d,role='".mysql_real_escape_string($_GET["roleid"])."',about='".mysql_real_escape_string($_GET["about"])."',modified=NOW()")) $div.="Идентификатор $_GET[mac] еще ниразу не входил на сайт, добавлен в ".get_role($_GET["roleid"])."<br>";
							else print "Ошибка ".mysql_error()."<br>";
						}
						else{
							if(!empty($mq["userid"])) $mu=q("select * from {$dbprefix}users where id='".mysql_real_escape_string($mq["userid"])."'",1);
							if(mysql_query("update {$dbprefix}device set role='".mysql_real_escape_string($_GET["roleid"])."'$d,about='".mysql_real_escape_string($_GET["about"])."',modified=NOW() where id='$mq[id]'")) $div.="Идентификатор $_GET[mac] добавлен в ".get_role($_GET["roleid"])."<br>Информация: ".((!empty($mq["ip"]))?"IP: ".$mq["ip"]." Вход на сайт ".date("d.m.Y H:i",$mq["last"]):"Не входил на сайт")."   <br>".((isset($mu["login"]))?" Логин: $mu[login] Email: $mu[email]":"");
						}
					$div.="<hr>";
					}
					if(isset($_GET["editid"])) $q=q("select * from {$dbprefix}device where id='".mysql_real_escape_string($_GET["editid"])."'",1);
					get_role();
					$c=""; 
					foreach($ROLE as $k=>$v){
						if($k!=10&&$k!=4&&$k!=5&&$k!=9) $c.="<option value='$k'".((isset($_GET["editid"])&&$q["role"]==$k)?" selected":"").">$v</option>";
					}
						
				$div.="
			ForkPlayerID это email адрес пользователя сайта http://forkplayer.tv/<br>
			Мак - это мак адрес из настроек ForkPlayer<br>
	<div style='border:1px solid gray;border-radius:4px;padding:4px;'>
			<table>
			<tr><td>Мак или ForkPlayerID<br><input id='mac' value='$q[mac]' /></td>
			
			<td>Роль сайта<br>
						".($q["role"]==10?"<input type=hidden name='op[role]' value='10' >Администратор":"
						<select id='role' onchange=''>
						$c
						</select>")."
			</td>
			<td>Примечание (необяз.)<br><input id='about' name='about' value='$q[about]' /></td>
			<td> На дней (0 - навсегда) Или дата<br>".'
			
			<input id="dateto" list="term" value="'.($q["dateto"]>0?date("d.m.Y",$q["dateto"]):$q["dateto"]).'" />
			<datalist id="term">
				<option value="0">Навсегда</option>
				<option value="03">3 дня</option>
				<option value="30">30 дней</option>
				<option value="90">90 дней</option>
				<option value="01.01.2020">До 01.01.2020</option>
			</datalist>
    '."</td><td><br>
	<input type='button' onclick=\"javascript:if(\$('#mac').val().match(/[a-z0-9]{12,16}/)||\$('#mac').val().match(/\S+@\S+\.\S+/)) location='/?do=/admin&act=$act&editid=$_GET[editid]&is_user=$_GET[is_user]&op=add&roleid='+\$('#role').val()+'&mac='+\$('#mac').val()+'&about='+\$('#about').val()+'&dateto='+\$('#dateto').val(); else confirm('Мак адрес должен состоять из 12-16 символов латинницей и цифр в нижнем регистре без двоеточий. \\nИли ForkPlayerID должен быть в виде email');\" value='".(isset($_GET["editid"])?"Сохранить #$_GET[editid]":"Добавить новое устройство")."' /></table>";
		$div.="<hr><a href='/?do=/admin&act=devices&roleid=".$_GET["roleid"]."' style='border: 1px solid #ccc;
		border-radius: 2px;
		padding: 3px;
		margin:3px 0px;    text-decoration: none;
		background: #f7f7f72e;'>Вернуться в список пользователей и устройств</a>  <hr>";
			}
			
		}
if($act=="devices"){
	if(!empty($_GET["qc"])){
		$qc=$_GET["qc"];
		setcookie("qc",$qc,time()+365*24*3600);
	}
	else $qc=$_COOKIE["qc"];
	if(empty($qc)) $qc=20;
	
			mysql_query("ALTER TABLE {$dbprefix}users ADD `modified` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");	
			mysql_query("ALTER TABLE {$dbprefix}sessions ADD `last` int(11) NOT NULL default 0");		
			mysql_query("ALTER TABLE {$dbprefix}sessions ADD `c` int(11) NOT NULL default 0");	
			
			$div.="<span style='font-weight: bold;
    font-size: 130%;'>Пользователи и устройства</span>  <div style='float:right;margin-right:40px;'>
			<form action='$siteurl/?do=/admin&act=$act' method='POST'>
			<input name='search' value='$_POST[search]' />
			<input type='submit' value='Поиск по логину/ip/mac/desc' />
			</form>
			</div><hr>  
			<a href='/?do=/admin&act=adddevice&is_user=1' style='border: 1px solid #ccc;
    border-radius: 2px;
    padding: 3px;
	margin:0px 10px;    text-decoration: none;
    background: #f7f7f72e;'>Добавить пользователя</a>
	
			<a href='/?do=/admin&act=adddevice&is_user=0' style='border: 1px solid #ccc;
    border-radius: 2px;
    padding: 3px;
	margin:0px 0px;    text-decoration: none;
    background: #f7f7f72e;'>Добавить устройство</a>
	
	<a href='/?do=/admin&act=adddevice&opdev=export' style='border: 1px solid #ccc;
    border-radius: 2px;
    padding: 3px;
	margin:0px 0px;    text-decoration: none;
    background: #f7f7f72e;'>Экспорт устройств</a>
	
	<a href='/?do=/admin&act=adddevice&opdev=import' style='border: 1px solid #ccc;
    border-radius: 2px;
    padding: 3px;
	margin:0px 0px;    text-decoration: none;
    background: #f7f7f72e;'>Импорт устройств</a>
	
	<a href='/?do=/admin&act=adddevice&opdev=delall' style='border: 1px solid #ccc;
    border-radius: 2px;
    padding: 3px;
	margin:0px 0px;    text-decoration: none;
    background: #f7f7f72e;'>Удалить устройства</a>
	
	<hr>	
	";
			$rAll=q("select role, sum(`count(id)`) FROM (
(select role,count(id) from {$dbprefix}device group by role) 
union all
(select role,count(id) from {$dbprefix}users group by role)
) c
group by role");
			$all=0;
			foreach($rAll as $v) { $rAllid[$v[0]]=$v[1]; $rAllid[0]+=$v[1]; }
			
			$uAll=q("select count(id) from {$dbprefix}users",1);
			$rAllid[4]=$uAll[0];
			
			if(!empty($_GET["roleid"])) $div.="<br><a href='$siteurl/?do=/admin&act=devices'>Все </a>($rAllid[0])";
			else $div.="<br><span style='font-weight:bold;color:#fa5961;'>Все</span> ($rAllid[0])";
			krsort($ROLE);
			foreach($ROLE as $k=>$v){
				$div.=" | ";
				if(empty($rAllid[$k])) $rAllid[$k]=0;
				if($_GET["roleid"]==$k) $div.="<span style='font-weight:bold;color:#fa5961;'>$v</span> "; else $div.="<a href='$siteurl/?do=/admin&act=devices&roleid=$k'>$v </a>";
				$div.=" ($rAllid[$k])";
			}
			ksort($ROLE);
					
	if($p<2) $limit="0,$qc";
	else $limit=(($p-1)*$qc).",$qc";
	$div.="<br>";
	if($_GET["order"]=="last"){
		$div.="Сортировка: по последнему входу с устройства<br>";
		$dpo="order by last desc";
	}
	elseif($_GET["order"]=="c"){
		$div.="Сортировка: по количеству входов с устройства<br>";
		$dpo="order by c desc";
	}
	elseif($_GET["order"]=="id"){
		$div.="Сортировка: по ID<br>";
		$dpo="order by id asc";
	}
	elseif($_GET["order"]=="dateto"){
		$div.="Сортировка: показывать сначала просроченные, исключить безсрочные<br>";
		$dpo="where dateto>0 order by dateto asc";
	}
	else {
		$div.="Сортировка по дате изменения<br>";
		$dpo="order by modified desc";
	}
	if(!empty($_POST["search"]))  {
		$search=mysql_real_escape_string($_POST["search"]);
		$dpo="where (about like '%$search%' or ip like '%$search%' or mac like '%$search%' or initial like '%$search%') $dpo";
	}
	elseif(!empty($_GET["roleid"])) {
		if($_GET["roleid"]==4) $dpo="where (is_user='1') $dpo";
		else $dpo="where role='$_GET[roleid]' $dpo";
	}
	
		
			$q="select * FROM (
	(select id, mac, role, dateto, ip,  last, modified, about, initial, 0 as is_user, c from {$dbprefix}device) 
	union all
	(
		SELECT * FROM 
		(
			SELECT
			u.id, u.login as mac, u.role, u.dateto,s.ip,s.last, u.modified,  CONCAT(u.email, '~|~', u.forkplayerid) as about, s.initial, 1 as is_user, s.c as c
			FROM {$dbprefix}users u 
			LEFT OUTER JOIN `{$dbprefix}sessions` s
			ON u.id=s.userid
			ORDER BY s.last desc, s.time desc 
		) c2
		GROUP BY mac
	)
) c
 $dpo limit $limit
";
//print $q;
$div.=mysql_error()." ";
	$ch=q($q);
	$div.= "<br>";
		if($p>1) $div.= "<a href='/?do=/admin&act=$act&roleid=".$_GET["roleid"]."&order=$_GET[order]&p=1'>В начало</a> ";
		 $div.= "<span style='font-weight:bold;color:#fa5961;'> Страница $p </span>";
		if(count($ch)==$qc) {
			if($p<1) $p=1;
			$div.= " <a href='/?do=/admin&act=$act&roleid=".$_GET["roleid"]."&order=$_GET[order]&p=".($p+1)."'>На страницу ".($p+1)."</a>";
		}
	
	$div.="
	<div style='float:right;margin-right:50px;'>
	<select id='qc' onchange=\"document.location='$siteurl/?do=/admin&act=$act&roleid=".$_GET["roleid"]."&order=$_GET[order]&p=$p&qc='+this.value\">
	<option value='20'".($qc==20?" selected":"").">20 на страницу</option>
	<option value='50'".($qc==50?" selected":"").">50 на страницу</option>
	<option value='100'".($qc==100?" selected":"").">100 на страницу</option>
	<option value='200'".($qc==200?" selected":"").">200 на страницу</option>
	<option value='500'".($qc==500?" selected":"").">500 на страницу</option>
	</select></div>
	<table>
	<tr><td><a href='/?do=/admin&act=$act&roleid=".$_GET["roleid"]."&order=id'>ID ред.</a></td><td><a href='/?do=/admin&act=$act&roleid=".$_GET["roleid"]."'>Обновить</a> </td><td><a href='/?do=/admin&act=$act&roleid=".$_GET["roleid"]."&order=dateto'>Срок</a></td><td>Роль</td><td>Примечание</td><td>Изменено</td><td><a href='/?do=/admin&act=$act&roleid=".$_GET["roleid"]."&order=last'>Последний вход</a></td><td>IP</td><td><a href='/?do=/admin&act=$act&roleid=".$_GET["roleid"]."&order=c'>Входов</a></td></tr>";
		if(count($ch)<1) $div.="<tr><td>Нет.</td></tr>";  
			for($i=0;$i<count($ch);$i++){
				$div.="<tr onmouseover=\"this.style.backgroundColor='#808080a1';\" onmouseout=\"this.style.backgroundColor='';\" ><td><a href='$siteurl/?do=/admin&act=adddevice&editid=".$ch[$i]["id"]."&is_user=".$ch[$i]["is_user"]."'>".$ch[$i]["id"]."</a></td><td title=\"".$ch[$i]["initial"]."\">".$ch[$i]["mac"]."</td><td style='".(($ch[$i]["dateto"]>0&&$ch[$i]["dateto"]<time())?"color:red;":"")."'> ".(($ch[$i]["dateto"]=="0")?"Безсрочно":date("d.m.Y",$ch[$i]["dateto"]))."</td><td> ".(($ch[$i]["is_user"]&&$ch[$i]["role"]!=4)?get_role(4).", ".get_role($ch[$i]["role"]):get_role($ch[$i]["role"]))."</td><td> ".$ch[$i]["about"]."</td><td> ".$ch[$i]["modified"]."</td><td> ".(($ch[$i]["last"]<1)?"Не входил":date("d.m.Y H:i",$ch[$i]["last"]))."</td> <td> ".$ch[$i]["ip"]."</td><td> ".$ch[$i]["c"]."</td><td><a href='/?do=/admin&act=adddevice&op=delete&roleid=".$ch[$i]["role"]."&mac=".$ch[$i]["mac"]."&id=".$ch[$i]["id"]."&is_user=".$ch[$i]["is_user"]."'>Удалить</a></td>";
				$div.="</tr>";
			}
			
			$div.="</table>";
			
		$div.= "<br>";
		if($p>1) $div.= "<a href='/?do=/admin&act=$act&roleid=".$_GET["roleid"]."&order=$_GET[order]&p=1'>В начало</a> ";
		 $div.= "<span style='font-weight:bold;color:#fa5961;'> Страница $p </span>";
		if(count($ch)==$qc) {
			if($p<1) $p=1;
			$div.= " <a href='/?do=/admin&act=$act&roleid=".$_GET["roleid"]."&order=$_GET[order]&p=".($p+1)."'>На страницу ".($p+1)."</a>";
		}
	
	
		//print_r($ch);
		}
	
		if($act=="addcat"){
			mysql_query("ALTER TABLE {$dbprefix}category ADD parent INT default 0");	
			mysql_query("ALTER TABLE {$dbprefix}category ADD description TEXT");	
			mysql_query("ALTER TABLE {$dbprefix}category ADD needaccess TEXT");	
			if($_GET["op"]=="delete"){
				if(mysql_query("delete from {$dbprefix}category where id=\"".mysql_real_escape_string($_GET["id"])."\"")) $div.="Категория #".$_GET["id"]." удалена!";
				
			}
			elseif($_GET["op"]=="save"){
				$access=",";
				if(empty($_POST["op"]["onlyfid"])) $_POST["op"]["onlyfid"]=0;
				if(empty($_POST["op"]["onlymac"])) $_POST["op"]["onlymac"]=0;
				if(empty($_POST["op"]["showmain"])) $_POST["op"]["showmain"]=0;
				if(empty($_POST["op"]["showpage"])) $_POST["op"]["showpage"]=0;
				
				foreach($_POST["access"] as $k=>$v) $access.="$v,";
				if(!empty($_GET["id"])){
					if(($id=mysql_query("update {$dbprefix}category set title=\"".mysql_real_escape_string($_POST["op"]["title"])."\",description=\"".mysql_real_escape_string($_POST["op"]["description"])."\",needaccess=\"".mysql_real_escape_string($_POST["op"]["needaccess"])."\",parent=\"".mysql_real_escape_string($_POST["op"]["parent"])."\",icon=\"".mysql_real_escape_string($_POST["op"]["icon"])."\",`access`=\"".mysql_real_escape_string($access)."\",`showmain`=\"".mysql_real_escape_string($_POST["op"]["showmain"])."\",`showpage`=\"".mysql_real_escape_string($_POST["op"]["showpage"])."\",`webplayer`=\"".mysql_real_escape_string($_POST["webplayer"])."\",`onlymac`=\"".mysql_real_escape_string($_POST["op"]["onlymac"])."\",`typeList`=\"".mysql_real_escape_string($_POST["pltag"]["typeList"])."\", seo_url=\"".mysql_real_escape_string($_POST["op"]["seo_url"])."\",numsess=\"".mysql_real_escape_string($_POST["op"]["numsess"])."\",onlyfid=\"".mysql_real_escape_string($_POST["op"]["onlyfid"])."\" where id=\"".mysql_real_escape_string($_GET["id"])."\""))!==false) $div.="Категория ".$_GET["id"]." ".$_POST["op"]["title"]." сохранена<br>";
					else $div.="Ошибка! ".mysql_error()."<br>";
				}
				else{
					if(($id=mysql_query("insert into {$dbprefix}category set title=\"".mysql_real_escape_string($_POST["op"]["title"])."\",description=\"".mysql_real_escape_string($_POST["op"]["description"])."\",needaccess=\"".mysql_real_escape_string($_POST["op"]["needaccess"])."\",parent=\"".mysql_real_escape_string($_POST["op"]["parent"])."\",icon=\"".mysql_real_escape_string($_POST["op"]["icon"])."\",`access`=\"".mysql_real_escape_string($access)."\",`showmain`=\"".mysql_real_escape_string($_POST["op"]["showmain"])."\",`showpage`=\"".mysql_real_escape_string($_POST["op"]["showpage"])."\",`webplayer`=\"".mysql_real_escape_string($_POST["webplayer"])."\",numsess=\"".mysql_real_escape_string($_POST["op"]["numsess"])."\",`typeList`=\"".mysql_real_escape_string($_POST["pltag"]["typeList"])."\",`onlymac`=\"".mysql_real_escape_string($_POST["op"]["onlymac"])."\",onlyfid=\"".mysql_real_escape_string($_POST["op"]["onlyfid"])."\", seo_url=\"".mysql_real_escape_string($_POST["op"]["seo_url"])."\""))!==false) $div.="Категория ".$_POST["op"]["title"]." добавлена<br>";
					else $div.="Ошибка! ".mysql_error()."<br>";
				}
				$act="cats";
				$div.="<hr>";
			}
			else{
				if(isset($_GET["editid"])){
					$q=q("select * from {$dbprefix}category where id='".mysql_real_escape_string($_GET["editid"])."'",1);
				}
				if(isset($_GET["editid"])&&!isset($q[0])) $div.="Ошибка! Такой категории нет<br>";
				else{
					
				$pq=q("select * from {$dbprefix}category");
				foreach ($pq as $k=>$v) { //Обходим массив
					if($v["parent"]==$v["id"]) $v["parent"]=0;
					$_getCat[$v["parent"]][] = $v;
				}
				function outTreeCat($parent, $level) {
					global $_getCat,$cp,$q;
					if (isset($_getCat[$parent])) { //Если категория с таким parent_id существует
						foreach ($_getCat[$parent] as $value) { //Обходим ее
							/**
							 * Выводим категорию 
							 *  $level * 25 - отступ, $level - хранит текущий уровень вложености (0,1,2..)
							 */
							$dp="";
							for($i=0;$i<$level;$i++) $dp.=" -";
							$cp.= "<option value='".$value["id"]."'".((isset($_GET["editid"])&&$q["parent"]==$value["id"])?" selected":"").">$dp ".$value["title"]."</option>" . "";
							$level++; //Увеличиваем уровень вложености
							//Рекурсивно вызываем этот же метод, но с новым $parent_id и $level
							outTreeCat($value["id"], $level);
							$level--; //Уменьшаем уровень вложености
						}
					}
				}
				$cp="";
				outTreeCat(0, 0);
				//print $cp;exit;
				for($i=0;$i<count($pq);$i++){
					
					get_role();
					$c="";
					foreach($ROLE as $k=>$v){
						if($k==10||$k==9) continue;
						$c.="<option value='$k'".((isset($_GET["editid"])&&strpos($q["access"],",$k,")!==false)?" selected":"").">$v</option>";
					}
				}	
					
					$div.="Категория $_GET[editid]<br>
					<form action='/?do=/admin&act=addcat&op=save&id=$_GET[editid]' method='POST'>
					Название<br>
					<input name='op[title]' value='".(isset($_GET["editid"])?"$q[title]":"Новая категория")."' /><br>
					Иконка(введите url или загрузите jpg, png) <img  align='left' src='$q[icon]' onerror=\"this.style.display='none'\" id='img_icon' width=20 height=18 /><br><input style='width:400px;' id='pltagid_icon' name='op[icon]' value='$q[icon]' /> <input type=\"file\" name=\"file_icon\" id=\"file_icon\" alt='' /><br>
					
					Родительская категория<br>
					<select name='op[parent]' onchange=''>
					<option value=''".((!isset($_GET["editid"]) || isset($_GET["editid"])&&strpos($q["parent"],"0")!==false)?" selected":"").">Нет</option>
					$cp
					</select><br>
					Описание категории (отображается при виде страницы списком)<br>
						<div style=\"width: 450px;background: url($sitebackground);\">
						<textarea rows=10 cols=60 id='description' name='op[description]' />$q[description]</textarea></div>";
					$div.='
						<script>
							$("#description").htmlarea({
								css: "/include/templates/js/jHtml/jHtmlArea.Editor.css",
								toolbar: [
									["html","|","bold", "italic", "underline", "|", "forecolor"],
									["justifyLeft", "justifyCenter", "justifyRight","p"],
									["|", "image"]
								]}).parent().resizable({ alsoResize: $(this).find("iframe") });	
						</script>';	
						
					$div.="Сообщение о закрытом доступе к категории (отображается если у пользователя нет прав)<br>
						<div style=\"width: 450px;background: url($sitebackground);\">
						<textarea rows=10 cols=60 id='needaccess' name='op[needaccess]' />$q[needaccess]</textarea></div>";
					$div.='
						<script>
							$("#needaccess").htmlarea({
								css: "/include/templates/js/jHtml/jHtmlArea.Editor.css",
								toolbar: [
									["html","|","bold", "italic", "underline", "|", "forecolor"],
									["justifyLeft", "justifyCenter", "justifyRight","p"],
									["|", "image"]
								]}).parent().resizable({ alsoResize: $(this).find("iframe") });	
						</script>';
					$div.="Доступ (Гость - все неавторизованные, Пользователь - все авторизованные)<br>
					<select multiple name='access[]' onchange='' style='height:150px;' title='Удерживайте ctrl чтобы отметить несколько!'>
					<option value='0'".((!isset($_GET["editid"]) || isset($_GET["editid"])&&strpos($q["access"],",0,")!==false)?" selected":"").">Всем кроме заблокированных</option>
					$c
					</select><br>
					<input type='checkbox' name='op[onlyfid]'".(($q["onlyfid"]==1)?" checked":"")." value=1 /> Доступ для пользователей только с ForkPlayer ID<br>
					<input type='checkbox' name='op[onlymac]'".(($q["onlymac"]==1||!isset($_GET["editid"]))?" checked":"")." value=1 /> Отключить доступ через web версию сайта<br>
					<br>
					Вид категории 
					<input type='radio' ".($q["typeList"]==''?" checked":"")." name='pltag[typeList]' value='' /> Список <input type='radio' name='pltag[typeList]' ".($q["typeList"]=='start'?" checked":"")." value='start' /> Плитка<br>
					<input type='checkbox' name='op[showpage]'".(($q["showpage"]==1||!isset($_GET["editid"]))?" checked":"")." value=1 /> Показывать страницы с этой категории на главной<br>
					<input type='checkbox' name='op[showmain]'".(($q["showmain"]==1||!isset($_GET["editid"]))?" checked":"")." value=1 /> Разместить ссылку на эту категорию на главной
					<br> 
					
					
					Количество устройств(сессий) пользователя<br> 
					<input name='op[numsess]' value='".(isset($_GET["editid"])?"$q[numsess]":"3")."' /><br>
					
					Разрешить показывать видеоплеер на web версии сайта<br> 
					<select name='webplayer'>
					<option value='0'".(($q["webplayer"]==0)?" selected":"").">Только для youtube, rutube</option>
					<option value='1'".(($q["webplayer"]==1)?" selected":"").">Для всех видео кроме IPTV</option>
					<option value='2'".(($q["webplayer"]==2)?" selected":"").">Для всех файлов</option>
					</select><br> 
					Разрешить показывать видеоплеер на xml версии сайта (через ForkPlayer и другие)<br> 
					Для всех файлов<br> 
					<br><input type='submit' value='".(isset($_GET["editid"])?"Сохранить":"Создать")."' />
					</form>";
				}
			}
		}



		if($act=="cats"){
			$ch=q("select * from {$dbprefix}category");
			if(count($ch)<1) $div.="Нет.";

			//print_r($_getCategory);		
			function desCat($tmp){
				return "<img  align='left' src='".$tmp["icon"]."' onerror=\"this.style.display='none'\" id='img_icon' width=20 height=18 /> <a href='/?do=/admin&act=addcat&editid=".$tmp["id"]."'>id #".$tmp["id"]." ".$tmp["title"]."</a>  страниц:$count[0]</td><td> Доступ: ".get_cat_roles($tmp["access"])." 
				".($tmp["showmain"]?"<img height=14 src='$siteurl/include/templates/images/dom.png' title='Отображается на главной'>":"<img height=14 src='$siteurl/include/templates/images/nodom.png' title='Не отображается на главной'>")." 
				".($tmp["showpage"]?"<img height=14 src='$siteurl/include/templates/images/page_on.jpg' title='Показывать страницы на главной'>":"<img height=14 src='$siteurl/include/templates/images/page_off.jpg' title='Не показывать страницы на главной'>")." 
				".($tmp["onlyfid"]?"<img height=14 src='$siteurl/include/templates/images/authorize.png' title='Только авторизованных через ForkPlayerID'><img height=14 src='$siteurl/include/templates/images/fid.png' title='Только авторизованных через ForkPlayerID'>":"")." 
				".($tmp["onlymac"]?"<img height=16 src='$siteurl/include/templates/images/noweb.png' title='Отключено для WEB. Только через ForkPlayer и другие приложения'>":"")." 
				
				<a href=\"javascript:if(confirm('Вы уверены что хотите удалить категорию ".$tmp["title"]."?')) location='/?do=/admin&act=addcat&op=delete&id=".$tmp["id"]."';\">удалить</a><br>";
			}
			foreach ($ch as $k=>$v) { //Обходим массив
				if($v["parent"]==$v["id"]) $v["parent"]=0;
				$_getCategory[$v["parent"]][] = $v;
			}
			function outTree($parent, $level) {
				global $_getCategory,$div;
				if (isset($_getCategory[$parent])) { //Если категория с таким parent_id существует
					foreach ($_getCategory[$parent] as $value) { //Обходим ее
						/**
						 * Выводим категорию 
						 *  $level * 25 - отступ, $level - хранит текущий уровень вложености (0,1,2..)
						 */
						$div.= "<div style='margin-left:" . ($level * 25) . "px;'>" . desCat($value) . "</div>";
						$level++; //Увеличиваем уровень вложености
						//Рекурсивно вызываем этот же метод, но с новым $parent_id и $level
						outTree($value["id"], $level);
						$level--; //Уменьшаем уровень вложености
					}
				}
			}
			
			outTree(0,0);
			$div.="<hr>";
			$div.="<a href='/?do=/admin&act=addcat'>Создать категорию</a><br>";
			
		}
		if($act=="listpage"){
			//if($p<2) $limit=" limit 0,50";
			//else $limit=" limit ".(($p-1)*50).",".(($p-1)*50+50);
			$ch=q("select * from {$dbprefix}page order by created desc$limit");
			$div.="<table>";
			for($i=0;$i<count($ch);$i++){
				$page=json_decode($ch[$i]["src"],true);
				$div.="<tr style='background-color:".($i%2?"#7e7e7e61":"#cdadad61").";'><td>id#".$ch[$i]["id"]."<td><a href='/?do=/fml&id=".$ch[$i]["id"].addInf($ch[$i]["title"])."'>$page[title]</a></td><td><a href='/?do=/admin&act=addpage&op=createxml&editid=".$ch[$i]["id"]."'>Редакт.</a></td><td> ссылок:".count($page["channels"])."</td><td style='max-width:420px;'>  ".get_cat_roles_by_id($ch[$i]["category"])." </td><td>".((empty($ch[$i]["access"])||strpos($ch[$i]["access"],",0,")!==false)?"":"Доступ только:<br> ".get_cat_roles($ch[$i]["access"]))."</td><td>".$ch[$i]["created"]."</td><td>".date("d.m.Y H:i",$ch[$i]["modified"])."</td><td> <a href=\"javascript:if(confirm('Вы уверены что хотите удалить $page[title]?')) location='/?do=/admin&act=addpage&op=delete&id=".$ch[$i]["id"]."';\">удалить</a></td></tr>";
			}
			$div.="</table>";
		}
		$content.="$div";
	}
	elseif($do=="/auth"){
		
		if(!$logged) $content.="<form method='post'>
		Логин:<br>
		<input name=login><br>
		Пароль:<br>
		<input type=password name=password><br>
		<input type=submit value='Войти'>
		</form>
		<br>
		<a href='/remind'>Забыли пароль</a>
		<a href='/register'>Регистрация</a>
		";
		else {header("Location: /");exit;}
	}	
	elseif($do=="/module"){
		include "include/module.php";
	}
	elseif($do=="/plugin"){
		include "include/plugin.php";
	}
	elseif($do=="/fml"){
		if(!empty($_GET["id"]))	$page=get_page($_GET["id"]);
		
		
		$a=json_decode($page["src"],true);
		
		if(isset($_GET["proxylink"])){
			$s=file_get_contents($a["channels"][$_GET["proxylink"]]["playlist_url"]);
			print listEncrypt($s);
			exit;
		} 
		
		$TITLE=$page["title"];
		$info=$_PL["info"];
		$_PL=array_merge($_PL,$a);	
		if(!empty($info)) $_PL["info"]=$info;
		if($page["encrypt"]){
			for($i=0;$i<count($a["channels"]);$i++){
				if($a["channels"][$i]["stream_url"]!=""){
					$a["channels"][$i]["stream_url"]=fEncrypt($a["channels"][$i]["stream_url"]);
				}
			}
		}
		for($i=0;$i<count($a["channels"]);$i++){
			if($a["channels"][$i]["proxyurl"]=="1"&&$a["channels"][$i]["playlist_url"]!=""){
				$a["channels"][$i]["playlist_url"]="$siteurl/?do=/fml&proxylink=$i&id=$_GET[id]".addInf($a["channels"][$i]["title"]);
			}
		}
		$_CH=$a["channels"];
		
		$tp=file_get_contents("include/templates/singlepage.xml");
		$tpl=str_replace("{TITLE}",$page["title"],$tp);
		$tpl=str_replace("{VIEW}",$page["view"],$tpl);
		$tpl=str_replace("{CATEGORY}",get_cat_roles_by_id($page["category"]),$tpl);
		$tpl=str_replace("{AUTHOR}",get_author($page["author"]),$tpl);
		$tpl=str_replace("{ICON}",$page["icon"],$tpl);
		$tpl=str_replace("{DESCRIPTION}",(empty($page["description"]))?"Описания нет.":$page["description"],$tpl);
		$tpl=str_replace("{DATE}",$page["created"],$tpl);
		$tpl=str_replace("{EDIT}",(($userinfo["role"]==10)?"<a href='/?do=/admin&act=addpage&op=createxml&editid=".$page["id"]."'>Редактировать</a>":""),$tpl);
		$div="";
		for($i=0;$i<count($a["channels"]);$i++){
			$ch=$a["channels"][$i];
			if($ch["stream_url"]!="") {
				if($page["webplayer"]==2||($page["webplayer"]==1&&!$page["is_iptv"])||$userinfo["role"]==10||preg_match("/(youtube\.com||rutube\.)/",$ch["stream_url"])){
					 $u="javascript: show_player(\"$ch[stream_url]\",$page[is_iptv],$i)";
				}
				else $u="#";
			}
			else $u=$ch["playlist_url"];
			$div.="<div style='clear:both;'>".($i+1).". <a id='ch$i' href='$u'>$ch[title]</a><div style='color:$sitecolor;font-size:85%;margin-left:14px;max-height:300px;overflow:hidden;background:url($sitebackground);'>".((empty($ch["description"]))?"":$ch["description"])."</div></div>";
		}
		$tpl=str_replace("{CONTENT}",$div,$tpl);
		if(!empty($page["icon"])) $siteicon=$page["icon"];
		if(!empty($a["background_image"])) $sitebackground=$a["background_image"];
		$content.="$tpl
		<div id='player' style='display:none;top:0px;left:0px;position:absolute;width:640px;height:360px;'></div>";
	}
	elseif(empty($do)||$do=="/"||$do=="/category"){
		
		$lmenu="";
		if(empty($do)||$do=="/") { 
			$_PL["typeList"]=$typelistStart;
			$before=getPluginMetaKey("[MAINBEFORE]",false);
			$_PL["style"]["cssid"]["content"]["before"]=$before[0]["src"];
		}
		elseif($do=="/category") {
			$qc=q("select * from {$dbprefix}category where id='".mysql_real_escape_string($_GET["id"])."'",1);
			$_PL["typeList"]=$qc["typeList"];
		}
		
		$plugins=getPlugins("main");
		for($i=0;$i<count($plugins);$i++){
			if(!$_ISPC) $_CH[]=["logo_30x30"=>$plugins[$i]["logo_30x30"],"playlist_url"=>$plugins[$i]["link"],"title"=>$plugins[$i]["name"]];
			$lmenu.="<img align='left' src='".$plugins[$i]["logo_30x30"]."' onerror=\"this.style.display='none';\" height=16 width=18 style='margin:-2px 2px;' /><a href='".$plugins[$i]["link"]."'>".$plugins[$i]["name"]."</a><br>";
			$div.=$tpl;
		}
		
		$ch=q("select * from {$dbprefix}category where showmain='1'");
		if($_ISPC){

			foreach ($ch as $k=>$v) { //Обходим массив
				if($v["parent"]==$v["id"]) $v["parent"]=0;
				$_getCategory[$v["parent"]][] = $v;
			}
			function outTree($parent, $level) {
				global $_getCategory,$lmenu; 
				if (isset($_getCategory[$parent])) { //Если категория с таким parent_id существует
					foreach ($_getCategory[$parent] as $value) { //Обходим ее
						$lmenu.= "<div style='margin-left:" . ($level * 5) . "px;font-size:" . (100-$level * 5) . "%;'>";
						
						$lmenu.="<img align='left' src='".$value["icon"]."' height=16 width=18 style='margin:-2px 2px;' />";
						if($_GET["id"]==$value["id"]) $lmenu.="<b>".$value["title"]."</b> (".$value["count"].")<br>";
						else $lmenu.="<a href='/?do=/category&id=".$value["id"]."'>".$value["title"]."</a>(".$value["count"].")<br>";
						$lmenu.= "</div>";
						$level++; //Увеличиваем уровень вложености
						//Рекурсивно вызываем этот же метод, но с новым $parent_id и $level
						outTree($value["id"], $level);
						$level--; //Уменьшаем уровень вложености
					}
				}
			}
			
			outTree(0,0);
		}
		else{	
			for($i=0;$i<count($ch);$i++){
				$_CH[]=["logo_30x30"=>$ch[$i]["icon"],"playlist_url"=>"$siteurl/?do=/category&id=".$ch[$i]["id"]."-".toTranslit($ch[$i]["title"]),"title"=>($_GET["id"]==$ch[$i]["id"]?"<b>".$ch[$i]["title"]."</b>":$ch[$i]["title"])."(".$ch[$i]["count"].")"];
				
			}
		}
		
		if($logged){
			$MYCH=getPluginMetaKey("[MYMENU_$userinfo[id]]",true);
			$MYCH=$MYCH[0]["src"];
			if($MYCH["mylinkonmain"]&&count($MYCH["channels"])>0){
				$submenu=$MYCH["channels"];
				if(count($MYCH["channels"])==1)	 $_CH[]=$submenu[0];
				else $_CH[]=["title"=>"Мои ссылки","playlist_url"=>"submenu","submenu"=>$submenu];
				if($_ISPC) {
					foreach($submenu as $v)
						$lmenu.="<img align='left' src='".$v["logo_30x30"]."' onerror='this.style.display=\'none\';' height=16 width=18 style='margin:-2px 2px;' /><a href='$v[playlist_url]'>".$v["title"]."</a><br>";
				} 
			}
		}
		
		if($do=="/category"&&($_PL["typeList"]=="start"||$typelistStart=="start"))  $_CH=[];
		$pages=get_pages($_GET["id"]);

		
		if(!empty($_GET["id"])) {
			$cq=q("select * from {$dbprefix}category where parent='".mysql_real_escape_string($_GET["id"])."'");

			for($i=0;$i<count($cq);$i++){
				$el=["icon"=>$cq[$i]["icon"],"playlist_url"=>"$siteurl/?do=/category&id=".$cq[$i]["id"]."-".toTranslit($cq[$i]["title"]),"title"=>($_GET["id"]==$cq[$i]["id"]?"<b>".$cq[$i]["title"]."</b>":$cq[$i]["title"])."(".$cq[$i]["count"].")","description"=>"Категория<br>".$cq[$i]["description"],"category"=>$cq[$i]["parent"]];
				if(count($pages))  array_unshift($pages,$el);
				else $pages[]=$el;
				
			}
			$TITLE=get_cat_roles_by_id($_GET["id"],false)." ";
		}
		
				
		if(count($pages)<1&&$do=="/category"&&$qc["count"]>0) {
			$dd="";
			foreach(explode(",",$qc["access"]) as $v){
				if($v>3){
					if(!empty($dd)) $dd.=", ";
					$dd.=$ROLE[$v]." ";
				}
			}
			
			$_PL["info"]="Нет доступа!<div style='text-align:left;padding:3px;'>".$qc["title"]." (".$qc["count"].") доступна только для <span style='color:red;'>$dd</span><br>Ваш текущий доступ: ".getmyrolesText()."<br>Ваш МАК адрес: $_GET[box_mac]<br>Ваш логин: ".(isset($userinfo["login"])?$userinfo["login"]:"Нет.<i>(Доступно после авторизации)</i>")."<br>Ваш ForkPlayerID: ".(isset($userinfo["forkplayerid"])?$userinfo["forkplayerid"]:"Нет.<i>(Доступно после авторизации и привязки в профиле)</i>")."</div><div style='text-align:center;'>$qc[needaccess]</div>";
			
			$catAC=$qa["access"];
		}
		
		if(count($pages)<1&&count($_CH)<1) {
			$div.="Нет страниц или нет доступа к этому разделу сайта!";
			$_PL["notify"]=$div;
		} 
		$tp=file_get_contents("include/templates/page.xml");
		for($i=0;$i<count($pages)&&$i<$siteperpage;$i++){
			if(empty($do)||$do=="/") {
				
			}

			$link=(isset($pages[$i]["playlist_url"])?$pages[$i]["playlist_url"]:"$siteurl/?do=/fml&id=".$pages[$i]["id"].addInf($pages[$i]["title"]));
			if(!$_ISPC) {
				$_CH[]=["title"=>$pages[$i]["title"],"playlist_url"=>$link,"logo_30x30"=>$pages[$i]["icon"],"description"=>$pages[$i]["description"]];
			}
			$tpl=str_replace("{TITLE}",$pages[$i]["title"],$tp);
			$tpl=str_replace("{CATEGORY}",get_cat_roles_by_id($pages[$i]["category"],false),$tpl);
			$tpl=str_replace("{LINK}",$link,$tpl);
			$tpl=str_replace("{EDIT}",(($userinfo["role"]==10)?"<a href='/?do=/admin&act=addpage&op=createxml&editid=".$pages[$i]["id"]."'>Редактировать</a>":""),$tpl);
			$tpl=str_replace("{DATE}","Дата ".$pages[$i]["created"]." ".($pages[$i]["sticked"]?"Прилеплена":""),$tpl);
			$tpl=str_replace("{DESCRIPTION}","<div id='linkdesc'>".((empty($pages[$i]["description"]))?"Описания нет.":$pages[$i]["description"])."</div>",$tpl);			
			$tpl=str_replace("{AUTHOR}","Автор ".get_author($pages[$i]["author"]),$tpl);
			$tpl=str_replace("{ICON}",$pages[$i]["icon"],$tpl);
			
			$div.=$tpl;
			//print_r($pages);
		}
		
		$content.="$div";
	}
	$footerPlugins=getPlugins();
	foreach($footerPlugins as $k=>$v){
		if($v["enabled"]&&$v["includeFoorter"]) {
			$PLUGIN=$v; 
			include_once(dirname(__FILE__)."/plugin/$v[id]/footer.php");
		}
	}
		
			
	if(strpos($sitechbkg,"rgb")!==false)
		$sitechbkg=rgba2hex($sitechbkg);
	if(strpos($sitechcolor,"rgb")!==false)
		$sitechcolor=rgba2hex($sitechcolor);
	$sitechbkgC=substr($sitechbkg,0,7);
	$sitechbkgA=substr($sitechbkg,7,2);
	if(strlen($sitechbkgA)==2&&abs($sitechbkgA)==0) $sitechbkg=" ";	
	if(!empty($sitebackground)) $_PL["style"]["cssid"]["site"]["background"]="url($sitebackground)";
	if(!empty($sitechbkg)) $_PL["style"]["channels"]["parent"]["default"]["background"]="none";
	if(!empty($sitecolor)) {
		$_PL["style"]["channels"]["parent"]["default"]["color"]=$sitecolor;
		$_PL["style"]["cssid"]["infoList"]["color"]=$sitecolor;
		$_PL["style"]["cssid"]["site"]["color"]=$sitecolor;
		$_PL["style"]["cssid"]["navigate"]["color"]=$sitecolor;
	}		
	if(!empty($sitechbkg)) $_PL["style"]["channels"]["parent"]["selected"]["background"]="none $sitechbkg";
	if(!empty($sitechcolor)) $_PL["style"]["channels"]["parent"]["selected"]["color"]=$sitechcolor;
	
	$content.="<script>
	var siteurl='$siteurl';
	applyStyles(".json_encode($_PL["style"]).");</script>";
	$t=str_replace("{STYLE}","a:link, a:visited {
   color: $sitecolor;
   text-decoration:underline;
}
a:hover {
   color: $sitechcolor;
   text-decoration:underline;
}
",$t);
	$t=str_replace("{TITLE}",$TITLE.$sitename,$t);
	$t=str_replace("{SITENAME}",$sitename,$t);
	$t=str_replace("{SIDE}",$lmenu,$t);
	$t=str_replace("{CONTENT}",$err.$content,$t);
	$t=str_replace("{VERSION}",$siteversion,$t);

	if($_ISPC) {
		if($logged&&$userinfo["role"]=="10"&&$act!="update"){
			$t.="<script>
	var xhr = null;
	xhr = new XMLHttpRequest();
	xhr.onreadystatechange = function () {	
		if (xhr.readyState == 4) {
			var ver=xhr.responseText.split('|')[0];
			if(ver!='$siteversion') {
				$('#leftmenu').append('<div style=\"display: inline-block;  padding: 3px 3px;     text-align: left;    margin: 0px 2px 0 2px;   border-left: 4px solid #ffba00;\"><a href=\"/?do=/admin&act=update\"><font>Доступна FXML CMS '+ver+'! Пожалуйста, обновитесь.</font></a></div>');
			}
		};
	};
	url='http://xml.forkplayer.tv/updates/version.php?js=1&ver=$siteversion';
	xhr.open('GET', url, true);
	xhr.send();
	</script>";
		}
		print "$t";
	}
	else{
		if(empty($TITLE)) $_PL["navigate"]=$_PL["title"]=$sitename;
		else {
			$_PL["navigate"]="$sitename &raquo; $TITLE";
			$_PL["title"]=$TITLE;
		}
		$_PL["icon"]=$siteicon;
		$_PL["menu"]=$_MENU;
		if($_CH==null) $_CH=[];
		$_PL["channels"]=$_CH;
		if($showStat){
			$_PL["before"].="<div style='    position: absolute;    top: 573px;    left: 1190px;    width: 72px;    text-align: right;    background-color: #e2e2e2;    color: black;    border-radius: 3px;    padding: 1px 5px;    font-size: 50%;'>".$STAT[0]["src"]."</div>";
		}
	
		if($_GET["box_client"]=="lg") print json_encode($_PL);
		else{ 
			//print_r($_PL);
			print cxmljson($_PL);
		}
	}
}


























