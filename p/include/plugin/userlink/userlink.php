<?php
if(!defined('XMLCMS')) exit();
include_once dirname(__FILE__)."/header.php";
$divpc="<script src='$siteurl/include/plugin/$PLUGIN[id]/page.js'></script>\n";
$MyRoles=getArrayAvailableUserRoles();
$AllowRead=is_access($MyRoles,explode(",",$PLUGIN["settings"]["allowread"]));
$AllowWrite=is_access($MyRoles,explode(",",$PLUGIN["settings"]["allowwrite"]));
$AllowUpload=is_access($MyRoles,explode(",",$PLUGIN["settings"]["allowupload"]));
$AllowDelete=is_access($MyRoles,explode(",",$PLUGIN["settings"]["allowdelete"]));
$divpc.="Ссылки пользователей на плейлисты XML/M3U<br>";
if($_GET["cmd"]=="open"){
	$link=getPluginMetaKey("links",true,$_GET["lid"]);
	$ch=$link["src"];
	if(!empty($link[0])){
		$ch["views"]++;
		savePluginMetaKey("links",json_encode($ch),$link["id"]);
		if($_ISPC) {
			header("Location: $ch[playlist_url]$ch[stream_url]");
			exit; 
		}
		else{
			$TITLE=$PLUGIN["name"]." / ".$ch["title"];
			$Channels[]=$ch;
			$Channels[count($Channels)-1]["location"]=1;
		}
	}
}
elseif($_GET["cmd"]=="report"){
	
	$link=getPluginMetaKey("links",true,$_GET["lid"]);
	$ch=$link["src"];
	
	if(!empty($_GET["text"])){
		if(!empty($link[0])){
			if($ch["reports"][0]["login"]==$userinfo["login"]&&$ch["reports"][0]["ip"]==$_SERVER["REMOTE_ADDR"]) $ch["reports"][0]["text"]=$_GET["text"]."<br><i>modified</i>";
			else array_unshift($ch["reports"],["text"=>$_GET["text"],"date"=>time(),"login"=>$userinfo["login"],"ip"=>$_SERVER["REMOTE_ADDR"],"initial"=>$_GET["initial"]]);
			savePluginMetaKey("links",json_encode($ch),$link["id"]);
			$div.="Отзыв добавлен<br>";
		}
	}

	$Channels[]=["title"=>"Отзыв: ","playlist_url"=>"payd_report","description"=>"","search_on"=>"Введите отзыв"];
	$Channels[]=["title"=>"Отправить","playlist_url"=>"$PLUGIN[link]&cmd=report&lid=$_GET[lid]&text=payd_report","description"=>""];
	
	$Channels[]=["title"=>"Вернутья к списку ссылок","playlist_url"=>"$PLUGIN[link]","description"=>""];	
	foreach($ch["reports"] as $k=>$v) {
		$reports.="<div style='margin:2px;font-size:90%;background-color:gray;color:white;'>".date("d.m.Y H:i",$v["date"])."<br>
			<i>".($v["login"]!=null?$v["login"]:$v["ip"])." пишет</i> $v[text]</div>";
	}
	$Channels[]=["title"=>"Все отзывы","playlist_url"=>"description","description"=>"$reports"];	
		
	
}
elseif($_GET["mode"]=="addurl"){
	
	if($userinfo["role"]==9||$userinfo["role"]==10||$AllowWrite){
		if(isset($PLUGIN["settings"]["rooms"][$_GET["room"]])) $rm=$PLUGIN["settings"]["rooms"][$_GET["room"]];
		else $rm="";
		if($_GET["opt"]=="delete"){
			if($AllowDelete) {
				if(deletePluginMetaKey("links",$_GET["lid"])) $div.="Удалено!<br>";
				else $div.="Ошибка удаления ссылки #$_GET[lid]!<br>";
			}
			else $div.="У вас недостаточно прав для удаления ссылок. Авторизуйтесь или обратитесь к администратору сайта!<br>";
			$Channels[]=["title"=>"Вернутья к списку ссылок","playlist_url"=>"$PLUGIN[link]","description"=>""];
		}
		elseif(isset($_GET["url"])){
			if(strpos($_GET["url"],"http")===0&&filter_var($_GET["url"], FILTER_VALIDATE_URL)){
				if(empty($_GET["title"])) {
					preg_match("/.*\/(.*?)(\?|&|$)/",$_GET["url"],$a);
					$title=$a[1];
				}
				else $title=$_GET["title"];
				$ch=["logo_30x30"=>$_GET["icon"],"room"=>$_GET["room"],"title"=>$title,"playlist_url"=>$_GET["url"],"infolink"=>$_GET["url"],"date"=>time(),"login"=>$userinfo["login"],"ip"=>$_SERVER["REMOTE_ADDR"],"initial"=>$_GET["initial"],"views"=>0,"reports"=>[]];
				$links=getPluginMetaKey("links",true);
				foreach($links as $k=>$v) {
					if($v["src"]["playlist_url"]==$_GET["url"]) {
						$div.="Такой URL $_GET[url] с названием ".$v["src"]["title"]." уже есть! Удалите его сначала<br>";
						$Channels[]=["title"=>"Вернутья к списку ссылок","playlist_url"=>"$PLUGIN[link]","description"=>""];
						$Channels[]=["title"=>"Вернуться назад","playlist_url"=>"javascript:OpenGoBack();","description"=>""];
						return;
					}
				}
					
				if(savePluginMetaKey("links",json_encode($ch))) $div.="Ссылка $_GET[url] успешно добавлена<br>";
				else $div.="Error insert to database<br>";	
				$Channels[]=["title"=>"Вернутья к списку ссылок","playlist_url"=>"$PLUGIN[link]&room=$_GET[room]","description"=>""];			
			}
			else {
				$div.="Невалидный URL $_GET[url]<br>";
				$Channels[]=["title"=>"Вернуться назад","playlist_url"=>"javascript:OpenGoBack();","description"=>""];
			}
			
		}
		elseif($_ISPC&&($AllowUpload||$userinfo["role"]==9||$userinfo["role"]==10)){
			if($_GET["upload"]=="xml"){
				if(preg_match("/\.(xml|fxml|m3u|m3u8|xspf)$/i",$_FILES["file"]["name"])){
					if($logged) $dir="uploads/plugin/$PLUGIN[id]/$userinfo[id]";
					else $dir="uploads/plugin/$PLUGIN[id]/$_SERVER[REMOTE_ADDR]";
					if(!file_exists($dir)) mkdir($dir,0777,true);
					$path="$dir/".preg_replace("/[^a-z0-9_\.]/","_",$_FILES["file"]["name"]);
					move_uploaded_file($_FILES["file"]["tmp_name"],$path);
					print "$siteurl/".$path;
				}
				else print "Поддерживается .xml|fxml|m3u|m3u8|xspf";
				exit;
			}
			elseif($_GET["upload"]=="icon"){
				if(preg_match("/\.(jpg|jpeg|png)$/i",$_FILES["file"]["name"])){
					if($logged) $dir="uploads/plugin/$PLUGIN[id]/$userinfo[id]";
					else $dir="uploads/plugin/$PLUGIN[id]/$_SERVER[REMOTE_ADDR]";
					if(!file_exists($dir)) mkdir($dir,0777,true);
					$f=resize_image($_FILES["file"]["tmp_name"],90,84,$_FILES["file"]["type"]);
					$path="$dir/".preg_replace("/\..{2,4}$/","",$_FILES["file"]["name"]).".png";
					imagepng($f, $path);
					print "$siteurl/".$path;
				}
				else print "Поддерживается png,jpg,jpeg";
				exit;
			}
		
		
			 $div.="
			 <a href=\"#\"> URL адрес</a> <div style=\"float:right;\">  </div> <br>Введите URL адрес XML/M3U страницы<br><input id='payd_url' name='payd_url' value='' size=70 /> <input type=\"file\" name=\"file_xml\" id=\"file_xml\" />
	
			 <div style=\"clear:both;width:600px;margin-top:15px;\"> 
	<img src=\"\" style=\"float:left;width:60; height:40px;\" />
	<a href=\"#\"> Название</a> <div style=\"float:right;\">  </div> <br>Название (Можно оставить пустым)<br><input id='payd_title' name='payd_title' value='' size=70 />
</div><div style=\"clear:both;width:600px;margin-top:15px;\"> 
	<img id='img_icon' src=\"\" style=\"float:left;width:60; height:40px;\" />
	<a href=\"#\"> URL иконки</a> <div style=\"float:right;\">  </div> <br>URL иконки (Можно оставить пустым)<br><input id='payd_icon' name='payd_icon' value='' size=70 /> <input type=\"file\" name=\"file_xmlicon\" id=\"file_xmlicon\" />
</div><div style=\"clear:both;width:600px;margin-top:15px;\"> 
	<img src=\"\" style=\"float:left;width:60; height:40px;\" />
	<br>
	<a href=\"javascript:location='$PLUGIN[link]&mode=addurl&room=$_GET[room]&url='+encodeURIComponent($('#payd_url').val())+'&title='+encodeURIComponent($('#payd_title').val())+'&icon='+encodeURIComponent($('#payd_icon').val())+'';\"> Отправить</a>
			 ";
		}
		else{
			$Channels[]=["title"=>"URL адрес","playlist_url"=>"payd_url","description"=>"","search_on"=>"Введите URL адрес"];
			$Channels[]=["title"=>"Название","playlist_url"=>"payd_title","description"=>"","search_on"=>"Название (Можно оставить пустым)"];
			$Channels[]=["title"=>"URL иконки","playlist_url"=>"payd_icon","description"=>"","search_on"=>"URL иконки (Можно оставить пустым)"];
			$Channels[]=["title"=>"Отправить","playlist_url"=>"$PLUGIN[link]&mode=addurl&url=payd_url&title=payd_title&icon=payd_icon&room=$_GET[room]","description"=>""];
		}
	}
	else $div.="У вас недостаточно прав!<br>";
}
elseif($userinfo["role"]==9||$userinfo["role"]==10||$AllowRead){
	
	$submenu2=[];
	$submenu2[]=["logo_30x30"=>"$PLUGIN[path]/$v[icon]","title"=>"Все","playlist_url"=>"$PLUGIN[link]","description"=>""]; 
	foreach($PLUGIN["settings"]["rooms"] as $k=>$v){
		if($v["enable"]) $submenu2[]=["logo_30x30"=>"$PLUGIN[path]/$v[icon]","title"=>"$v[name]","playlist_url"=>"$PLUGIN[link]&room=$k","description"=>"$v[description]"]; 	
	}
	if(isset($PLUGIN["settings"]["rooms"][$_GET["room"]])) $rm=$PLUGIN["settings"]["rooms"][$_GET["room"]]["name"];
	else $rm="Все";
	$links=getPluginMetaKey("links",true);
	$sorted=["pop"=>"популярные","old"=>"старые","new"=>"новые","reports"=>"самые комментируемые"];
	if(empty($_GET["sort"])) $sort="new";
	else $sort=$_GET["sort"];
	$submenu=[];
	foreach($sorted as $k=>$v) $submenu[]=["title"=>$v,"playlist_url"=>"$PLUGIN[link]&sort=$k&room=$_GET[room]"];

	for($i=0;$i<count($links);$i++){
		if($rm!="Все") if($_GET["room"]!=$links[$i]["src"]["room"]) continue;
		if($AllowDelete||$links[$i]["uid"]==$userinfo["id"]) {
			if($_ISPC) $links[$i]["src"]["menu"][]=["logo_30x30"=>"$siteurl/include/templates/images/delete.png","title"=>"Удалить","playlist_url"=>"javascript:ConfirmMessage('Вы уверены что хотите удалить?',function(){OpenUrl('$PLUGIN[link]&mode=addurl&opt=delete&lid=".$links[$i]["id"]."')});"];
			else $links[$i]["src"]["menu"][]=["logo_30x30"=>"$siteurl/include/templates/images/delete.png","title"=>"Удалить","description"=>"Вы уверены что хотите удалить?","playlist_url"=>"confirm","confirm"=>["$PLUGIN[link]&mode=addurl&opt=delete&lid=".$links[$i]["id"],""]];
			
			$links[$i]["src"]["menu"][]=["logo_30x30"=>"","title"=>"Оставить отзыв","playlist_url"=>"$PLUGIN[link]&cmd=report&lid=".$links[$i]["id"].""];
		} 
		$links[$i]["src"]["playlist_url"]="$PLUGIN[link]_".toTranslit($links[$i]["src"]["title"])."&cmd=open&lid=".$links[$i]["id"];
		$reports="";
		foreach($links[$i]["src"]["reports"] as $k=>$v) $reports.="<div style='margin:2px;font-size:90%;background-color:gray;color:white;'>".date("d.m.Y H:i",$v["date"])."<br><i>".($v["login"]!=null?$v["login"]:$v["ip"])." пишет</i> $v[text]</div>";
		$links[$i]["src"]["description"]="Просмотров: ".$links[$i]["src"]["views"]."<br><b>".$PLUGIN["settings"]["rooms"][$links[$i]["src"]["room"]]["name"]."</b><br>".(!$_ISPC?"Нажмите Меню/Оставить отзыв для сообщения<br>":"")."$reports
			<div style='margin:2px;font-size:90%;background-color:gray;color:white;'>".date("d.m.Y H:i",$links[$i]["src"]["date"])."<br>
			".($links[$i]["src"]["login"]!=null?$links[$i]["src"]["login"]:$links[$i]["src"]["ip"])." загрузил ссылку/файл</div>";
		$Channels[]=$links[$i]["src"];
	}
	if($sort=="old") usort($Channels, function($a, $b){
					return ($a['date'] - $b['date']);
				});
	
	if($sort=="pop") usort($Channels, function($a, $b){
					return -($a['views'] - $b['views']);
				});
	if($sort=="reports") usort($Channels, function($a, $b){
					return -(count($a['reports']) - count($b['reports']));
				});	
				
	if(count($submenu2)>0) array_unshift($Channels,["title"=>"Категория ссылок: $rm","playlist_url"=>"submenu","submenu"=>$submenu2]);
	array_unshift($Channels,["title"=>"Показывать сначала: $sorted[$sort]","playlist_url"=>"submenu","submenu"=>$submenu]);

	if(count($links)<1) $div.="Ссылок еще нет.<br>";
		if(isset($_GET["room"])||count($submenu2)<1){
		if($userinfo["role"]==9||$userinfo["role"]==10||$AllowWrite) $Channels[]=["title"=>"Добавить ссылку","playlist_url"=>"$PLUGIN[link]&mode=addurl&room=$_GET[room]","description"=>""];
		else $Channels[]=["title"=>"Добавить ссылку","playlist_url"=>"javascript:alert('У вас недостаточно прав для добавления ссылок.<br>Авторизуйтесь или обратитесь к администратору сайта!');","description"=>""];
	}
}
else $div.="У вас недостаточно прав для просмотра этой страницы $PLUGIN[name]! Авторизуйтесь или обратитесь к администратору сайта!<br>";
