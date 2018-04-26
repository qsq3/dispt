<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

require_once DISCUZ_ROOT.'./include/post.func.php';

if(empty($forum['allowview'])) {
	if(!$forum['viewperm'] && !$readaccess) {
		showmessage('group_nopermission', NULL, 'NOPERM');
	} elseif($forum['viewperm'] && !forumperm($forum['viewperm'])) {
		showmessage('forum_nopermission', NULL, 'NOPERM');
	}
}

$thread = $db->fetch_first("SELECT * FROM {$tablepre}threads WHERE tid='$tid' AND displayorder>='0'");
if($thread['readperm'] && $thread['readperm'] > $readaccess && !$forum['ismoderator'] && $thread['authorid'] != $discuz_uid) {
	showmessage('thread_nopermission', NULL, 'NOPERM');
}

if($forum['password'] && $forum['password'] != $_DCOOKIE['fidpw'.$fid]) {
	showmessage('forum_passwd', "forumdisplay.php?fid=$fid");
}


if(!$thread) {
	showmessage('thread_nonexistence');
}

if($forum['type'] == 'forum') {
	$navigation = "&raquo; <a href=\"forumdisplay.php?fid=$fid\">$forum[name]</a> &raquo; <a href=\"viewthread.php?tid=$tid\">$thread[subject]</a> ";
	$navtitle = strip_tags($forum['name']).' - '.$thread['subject'];
} elseif($forum['type'] == 'sub') {
	$fup = $db->fetch_first("SELECT name, fid FROM {$tablepre}forums WHERE fid='$forum[fup]'");
	$navigation = "&raquo; <a href=\"forumdisplay.php?fid=$fup[fid]\">$fup[name]</a> &raquo; <a href=\"forumdisplay.php?fid=$fid\">$forum[name]</a> &raquo; <a href=\"viewthread.php?tid=$tid\">$thread[subject]</a> ";
	$navtitle = strip_tags($fup['name']).' - '.strip_tags($forum['name']).' - '.$thread['subject'];
}

$polloptionid = is_numeric($polloptionid) ? $polloptionid : '';

$overt = $db->result_first("SELECT overt FROM {$tablepre}imgpolls WHERE tid='$tid'");

$polloptions = array();
$query = $db->query("SELECT polloptionid, polloption FROM {$tablepre}imgpolloptions WHERE tid='$tid'");
while($options = $db->fetch_array($query)) {
	if(empty($polloptionid)) {
		$polloptionid = $options['polloptionid'];
	}
	$options['polloption'] = preg_replace("/\[url=(https?|ftp|gopher|news|telnet|rtsp|mms|callto|bctp|ed2k|thunder|synacast){1}:\/\/([^\[\"']+?)\](.+?)\[\/url\]/i",
		"<a href=\"\\1://\\2\" target=\"_blank\">\\3</a>", $options['polloption']);
	$polloptions[] = $options;
}

$arrvoterids = array();
if($overt || $adminid == 1) {
	$voterids = '';
	$voterids = $db->result_first("SELECT voterids FROM {$tablepre}imgpolloptions WHERE polloptionid='$polloptionid'");
	$arrvoterids = explode("\t", trim($voterids));
}

if(!empty($arrvoterids)) {
	$arrvoterids = array_slice($arrvoterids, -100);
}
$voterlist = $voter = array();
if($voterids = implodeids($arrvoterids)) {
	$query = $db->query("SELECT uid, username FROM {$tablepre}members WHERE uid IN ($voterids)");
	while($voter = $db->fetch_array($query)) {
		$voterlist[] = $voter;
	}
}

include template('imgpoll:viewthread_imgpoll_voters');
	
?>