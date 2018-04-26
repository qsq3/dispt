<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$query = $db->query("UPDATE {$tablepre}imgpolloptions SET views = views + 1 WHERE tid='$tid' AND polloptionid='$optionid' LIMIT 1");

$query = $db->query("SELECT ipo.*, ip.multiple, ip.allowguestpoll, a.attachment, a.remote, a.isimage FROM {$tablepre}imgpolloptions ipo LEFT JOIN {$tablepre}attachments a ON ipo.aid=a.aid LEFT JOIN {$tablepre}imgpolls ip ON ipo.tid=ip.tid WHERE ipo.tid='$tid' AND ipo.polloptionid='$optionid' LIMIT 1");

$option = $db->fetch_array($query);

$refcheck = (!$option['remote'] && $attachrefcheck) || ($option['remote'] && ($ftp['hideurl'] || ($option['isimage'] && $attachimgpost && strtolower(substr($ftp['attachurl'], 0, 3)) == 'ftp')));
$aidencode = aidencode($option['aid']);
$imgatturl = $option['remote'] ? $ftp['attachurl'] : $attachurl;


$option['polloption'] = preg_replace("/\[url=(https?|ftp|gopher|news|telnet|rtsp|mms|callto|bctp|ed2k|thunder|synacast){1}:\/\/([^\[\"']+?)\](.+?)\[\/url\]/i",
		"<a href=\"\\1://\\2\" target=\"_blank\">\\3</a>", $option['polloption']);
$option['describe'] = preg_replace("/\[url=(https?|ftp|gopher|news|telnet|rtsp|mms|callto|bctp|ed2k|thunder|synacast){1}:\/\/([^\[\"']+?)\](.+?)\[\/url\]/i",
		"<a href=\"\\1://\\2\" target=\"_blank\">\\3</a>", $option['describe']);
$option['attachment'] = $refcheck ? "attachment.php?aid=$aidencode&amp;noupdate=yes" : "$imgatturl/$option[attachment]";

include template('imgpoll:viewthread_imgpoll_more');

?>