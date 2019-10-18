<?php
if(!defined('XMLCMS')) exit();
$divpc="";
$MyRoles=getArrayAvailableUserRoles();
$AllowRead=is_access($MyRoles,explode(",",$PLUGIN["settings"]["allowread"]));

if($AllowRead){
	if($_GET["act"]=="presearch"){
		$Channels=get_search($_GET["presearch"]);
		$presearch=[];
		for($i=0;$i<count($Channels)&&$i<10;$i++){
			$presearch[]=$Channels[$i]["title"];
		}
		print json_encode($presearch);
	}
	elseif(!empty($_GET["search"])){
		$Channels=get_search($_GET["search"]);
		$TITLE=$PLUGIN["name"]." \"$_GET[search]\" (".count($Channels).")";
	}
	else $Channels[]=["title"=>"Поиск!","presearch"=>"$PLUGIN[link]&act=presearch","search_on"=>"Введите текст поиска и нажмите Enter","playlist_url"=>"$PLUGIN[link]"];
}
else $Channels[]=["title"=>"У вас недостаточно прав для поиска.<br>Авторизуйтесь или обратитесь к администратору сайта!"];
if($_GET["act"]=="presearch") exit;