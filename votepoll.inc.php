<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

require_once './include/post.func.php';

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
	showmessage('thread_nonexistence', NULL, 'NOPERM');
}

if($forum['type'] == 'forum') {
	$navigation = "&raquo; <a href=\"forumdisplay.php?fid=$fid\">$forum[name]</a> &raquo; <a href=\"viewthread.php?tid=$tid\">$thread[subject]</a> ";
	$navtitle = strip_tags($forum['name']).' - '.$thread['subject'];
} elseif($forum['type'] == 'sub') {
	$fup = $db->fetch_first("SELECT name, fid FROM {$tablepre}forums WHERE fid='$forum[fup]'");
	$navigation = "&raquo; <a href=\"forumdisplay.php?fid=$fup[fid]\">$fup[name]</a> &raquo; <a href=\"forumdisplay.php?fid=$fid\">$forum[name]</a> &raquo; <a href=\"viewthread.php?tid=$tid\">$thread[subject]</a> ";
	$navtitle = strip_tags($fup['name']).' - '.strip_tags($forum['name']).' - '.$thread['subject'];
}

$pollarray = $db->fetch_first("SELECT maxchoices, expiration, allowpollnum, allowguestpoll FROM {$tablepre}imgpolls WHERE tid='$tid'");
$allowpollnum = $pollarray['allowpollnum'];
$allowguestpoll = $pollarray['allowguestpoll'];
if(!$allowguestpoll && (empty($discuz_uid) || $groupid == 7)) {
	showmessage('imgpoll:only_for_members', NULL, 'NOPERM');
}

if(submitcheck('pollsubmit', 1)) {

	if(!empty($thread['closed'])) {
		showmessage('thread_poll_closed', NULL, 'NOPERM');
	} elseif(empty($pollanswers)) {
		showmessage('thread_poll_invalid', NULL, 'NOPERM');
	}

	if(!$pollarray) {
		showmessage('undefined_action', NULL, 'HALTED');
	} elseif($pollarray['expiration'] && $pollarray['expiration'] < $timestamp) {
		showmessage('poll_overdue', NULL, 'NOPERM');
	} elseif($pollarray['maxchoices'] && $pollarray['maxchoices'] < count($pollanswers)) {
		showmessage('poll_choose_most', NULL, 'NOPERM');
	}

	$voterids = $discuz_uid ? $discuz_uid : $onlineip;

	$polloptionid = array();
	$query = $db->query("SELECT polloptionid, voterids FROM {$tablepre}imgpolloptions WHERE tid='$tid'");
	while($option = $db->fetch_array($query)) {
		if ($option['voterids']) {
			$polloptionid[$option['polloptionid']] = explode("\t", $option['voterids']);
		} else {
			$polloptionid[$option['polloptionid']] = array();
		}
	}

	$imgpollnum = 0;
	$polloptionids = '';
	foreach($polloptionid as $id => $value) {
		$in_pollanswers = 0;
		if (in_array($id, $pollanswers)) {
			$in_pollanswers = 1;
		}
		$have = 0;
		foreach ($value as $k => $v) {
			list($uid, $pollnum) = explode('|', $v);
			if ($uid == $voterids) {
				if (empty($pollnum)) {
					$pollnum = 1;
				}
				if ($in_pollanswers) {
					$pollnum++;
					$polloptionid[$id][$k] = $uid.'|'.$pollnum;
				}
				$imgpollnum += $pollnum;
				$have = 1;
			}
		}
		if ($in_pollanswers) {
			!$have && $polloptionid[$id][] = $voterids;
			!$have && $imgpollnum++;
			$polloptionids[$id] = implode("\t", $polloptionid[$id]);
		}
	}
	empty($allowpollnum) && $allowpollnum = 1;
	if ($imgpollnum > $allowpollnum) {
		showmessage('imgpoll:have_not_allowpollnum', NULL, 'NOPERM');
	}

	foreach ($polloptionids as $optid => $optvoterids) {
		$db->query("UPDATE {$tablepre}imgpolloptions SET votes=votes+1, voterids='$optvoterids' WHERE polloptionid='$optid'", 'UNBUFFERED');
	}
	$db->query("UPDATE {$tablepre}threads SET lastpost='$timestamp' WHERE tid='$tid'", 'UNBUFFERED');

	updatecredits($discuz_uid, $creditspolicy['votepoll']);

	if($customaddfeed & 4) {
		$feed['icon'] = 'poll';
		$feed['title_template'] = 'feed_thread_votepoll_title';
		$feed['title_data'] = array(
			'subject' => "<a href=\"{$boardurl}viewthread.php?tid=$tid\">$thread[subject]</a>",
			'author' => "<a href=\"space.php?uid=$thread[authorid]\">$thread[author]</a>"
		);
		postfeed($feed);
	}

	$pid = $db->result_first("SELECT pid FROM {$tablepre}posts WHERE tid='$tid' AND first='1'");

	if(!empty($inajax)) {
		showmessage('thread_poll_succeed', "viewthread.php?tid=$tid&viewpid=$pid&inajax=1&imgpollsuccess=1", 'NOPERM');
	} else {
		showmessage('thread_poll_succeed', "viewthread.php?tid=$tid&imgpollsuccess=1", 'NOPERM');
	}

}

?>