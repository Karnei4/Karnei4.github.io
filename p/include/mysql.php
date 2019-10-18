<?php
/** Simple layer for supporting most common deprecated mysql_* functions in PHP 7.
 *  It replaces their calls to MySQLi function calls.
 *  Just include this file in start of your index.php.
 *
 *  Written by 4X_Pro, http://xpro.su
 *  Distributed under MIT license terms.
 * **/

function mysql_connect($server,$username,$password,$new_link=null,$client_flags=null) {
  $GLOBALS['mysql_oldstyle_link']=mysqli_connect($server,$username,$password);
  return $GLOBALS['mysql_oldstyle_link'];
}

function mysql_query($sql) {
  return mysqli_query($GLOBALS['mysql_oldstyle_link'],$sql);
}

function mysql_fetch_row($res) {
  return mysqli_fetch_row($res);
}
function mysql_real_escape_string($res) {
  return mysqli_real_escape_string($GLOBALS['mysql_oldstyle_link'],$res);
}
function mysql_fetch_assoc($res) {
  return mysqli_fetch_assoc($res);
}

function mysql_fetch_array($res) {
  return mysqli_fetch_array($res);
}

function mysql_fetch_object($res) {
  return mysqli_fetch_object($res);
}

function mysql_affected_rows($link=NULL) {
  if ($link===NULL) $link=$GLOBALS['mysql_oldstyle_link'];
  return mysqli_affected_rows($link);
}

function mysql_insert_id($link=NULL) {
  if ($link===NULL) $link=$GLOBALS['mysql_oldstyle_link'];
  return mysqli_insert_id ($link);
}

function mysql_select_db($database_name) {
  return mysqli_select_db($GLOBALS['mysql_oldstyle_link'],$database_name);
}

function mysql_errno($link=NULL) {
  if ($link===NULL) $link=$GLOBALS['mysql_oldstyle_link'];
  return mysqli_errno($link);
}

function mysql_error($link=NULL) {
  if ($link===NULL) $link=$GLOBALS['mysql_oldstyle_link'];
  return mysqli_error($link);
}

function mysql_num_rows($res) {
  return mysqli_num_rows($res);
}

function mysql_free_result($res) {
  return mysqli_free_result($res);
}

function mysql_close($link) {
  return mysqli_close($link);
}