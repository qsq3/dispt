<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class threadplugin_imgpoll {

	var $name = '图片投票主题';			//主题类型名称
	var $iconfile = 'imgpoll.gif';		//images/icons/ 目录下新增的主题类型图片文件名
	var $buttontext = '发布图片投票';	//发帖时按钮文字
	var $config = array();				//配置变量
	var $var = array();					//变量

	function threadplugin_imgpoll() {
		$this->config = $this->_getconfig();
	}

	function newthread($fid) {
		$action = 'newthread';
		$maxpolloptions = $this->config['vars']['maximgpolloptions'];
		$imgpoll = array('allowpollnum' => 0);

		include template('imgpoll:post_imgpoll');
		return $return;
	}

	function newthread_submit($fid) {
		global $timestamp, $attachnew;
		global $imgpolloption, $imgpolloptionaid, $imgpolloptiondesc; //投票选项的标题、图片aid、详细说明
		global $multiplepoll, $maxchoices, $expirationchecked, $expiration, $visibilitypoll, $overt, $allowguestpoll, $allowpollnumcheck, $allowpollnum;
		global $imgpollarray;

		$imgpollarray = array();
		foreach($imgpolloption as $key => $value) {
			if(trim($value) === '' || empty($imgpolloptionaid[$key])) {
				unset($imgpolloption[$key]);
			} else {
				$imgpolloption[$key] = array('polloption'=>daddslashes($value), 'aid'=>intval($imgpolloptionaid[$key]), 'describe'=>daddslashes($imgpolloptiondesc[$key]));
				$attachnew[$imgpolloption[$key]['aid']] = array('description'=>daddslashes($value));
			}
		}

		if(count($imgpolloption) > $this->config['vars']['maximgpolloptions']) {
			showmessage('post_poll_option_toomany');
		} elseif(count($imgpolloption) < 2) {
			showmessage('post_poll_inputmore');
		}

		$maxchoices = !empty($multiplepoll) ? (!$maxchoices || $maxchoices >= count($imgpolloption) ? count($imgpolloption) : $maxchoices) : '';
		$imgpollarray['options'] = $imgpolloption;
		$imgpollarray['multiple'] = !empty($multiplepoll);
		$imgpollarray['visible'] = empty($visibilitypoll);
		$imgpollarray['overt'] = !empty($overt);
		$imgpollarray['allowguestpoll'] = !empty($allowguestpoll);
		$imgpollarray['allowpollnum'] = !empty($allowpollnumcheck) ? (!$allowpollnum ? 0 : $allowpollnum) : '';

		if(preg_match("/^\d*$/", trim($maxchoices)) && preg_match("/^\d*$/", trim($expiration))) {
			if(!$imgpollarray['multiple']) {
				$imgpollarray['maxchoices'] = 1;
			} elseif(empty($maxchoices)) {
				$imgpollarray['maxchoices'] = 0;
			} elseif($maxchoices == 1) {
				$imgpollarray['multiple'] = 0;
				$imgpollarray['maxchoices'] = $maxchoices;
			} else {
				$imgpollarray['maxchoices'] = $maxchoices;
			}
			if(empty($expiration)) {
				$imgpollarray['expiration'] = 0;
			} else {
				$imgpollarray['expiration'] = $timestamp + 86400 * $expiration;
			}
		} else {
			showmessage('poll_maxchoices_expiration_invalid');
		}

	}

	function newthread_submit_end($fid) {
		global $tid, $pid;
		global $db, $tablepre, $imgpollarray;

		$aids = array();
		$db->query("INSERT INTO {$tablepre}imgpolls (tid, multiple, visible, maxchoices, expiration, overt, allowguestpoll, allowpollnum)
			VALUES ('$tid', '$imgpollarray[multiple]', '$imgpollarray[visible]', '$imgpollarray[maxchoices]', '$imgpollarray[expiration]', '$imgpollarray[overt]', '$imgpollarray[allowguestpoll]', '$imgpollarray[allowpollnum]')");
		foreach($imgpollarray['options'] as $polloptvalue) {
			$polloptvalue['polloption'] = dhtmlspecialchars(trim($polloptvalue['polloption']));
			$polloptvalue['describe'] = dhtmlspecialchars(trim($polloptvalue['describe']));
			$db->query("INSERT INTO {$tablepre}imgpolloptions (tid, polloption, aid, `describe`) VALUES ('$tid', '$polloptvalue[polloption]', '$polloptvalue[aid]', '$polloptvalue[describe]')");
		}
	}

	function editpost($fid, $tid) {
		global $db, $tablepre, $imgpollarray;
		global $attachrefcheck, $ftp, $attachimgpost, $attachurl, $thread, $forum, $alloweditpoll, $attachs;

		$action = 'edit';

		$imgpoll = array();
		$imgpoll = $db->fetch_array($db->query("SELECT * FROM {$tablepre}imgpolls WHERE tid ='$tid'"));

		$imgpolloption = array();
		$query = $db->query("SELECT ipo.polloptionid, ipo.aid, ipo.aid, ipo.displayorder, ipo.polloption, ipo.describe, a.attachment, a.remote, a.isimage FROM {$tablepre}imgpolloptions ipo LEFT JOIN {$tablepre}attachments a ON ipo.aid=a.aid WHERE ipo.tid='$tid' ORDER BY ipo.displayorder");
		while($option = $db->fetch_array($query)) {
			$attachs['used'][] = $option['aid'];

			$refcheck = (!$option['remote'] && $attachrefcheck) || ($option['remote'] && ($ftp['hideurl'] || ($option['isimage'] && $attachimgpost && strtolower(substr($ftp['attachurl'], 0, 3)) == 'ftp')));
			$aidencode = aidencode($option['aid']);
			$imgatturl = $option['remote'] ? $ftp['attachurl'] : $attachurl;
			$option['attachment'] = $refcheck ? "attachment.php?aid=$aidencode&amp;noupdate=yes" : "$imgatturl/$option[attachment]";
			$option['polloption'] = stripslashes($option['polloption']);
			$option['describe'] = stripslashes($option['describe']);
			$imgpolloption[$option['polloptionid']] = $option;
		}
		$imgpoll['polloption'] = $imgpolloption;

		$maxpolloptions = $this->config['vars']['maximgpolloptions'];

		include template('imgpoll:post_imgpoll');
		return $return;
	}

	function editpost_submit($fid, $tid) {
		global $db, $tablepre, $timestamp, $imgpollarray;
		global $alloweditpoll, $isorigauthor, $close, $polladd;
		global $imgpolloption, $imgpolloptionaid, $imgpolloptiondesc, $displayorder, $imgpolloptionid; //投票选项的标题、图片aid、详细说明、排序、id
		global $multiplepoll, $maxchoices, $expirationchecked, $expiration, $visibilitypoll, $overt, $allowguestpoll, $allowpollnumcheck, $allowpollnum;
		global $db, $tablepre, $attachsave, $attachdir, $discuz_uid, $postattachcredits, $tid, $pid, $attachextensions, $attachnew, $attachdel, $allowsetattachperm, $maxprice, $watermarkstatu;

		if($alloweditpoll || $isorigauthor) {
			$imgpollarray = array();

			if($imgpolloption) {
				if(count($imgpolloption) > $this->config['vars']['maximgpolloptions']) {
					showmessage('post_poll_option_toomany');
				}

				foreach($imgpolloption as $key => $value) {
					if(!trim($value) || empty($imgpolloptionaid[$key])) {
						$db->query("DELETE FROM {$tablepre}imgpolloptions WHERE polloptionid='$key' AND tid='$tid'");
						$attach = $db->fetch_first("SELECT attachment, thumb, remote FROM {$tablepre}attachments WHERE aid='".$imgpolloptionaid[$key]."'");
						$attachdel[] = $imgpolloptionaid[$key];
						unset($imgpolloption[$key], $imgpolloptionaid[$key], $imgpolloptiondesc[$key], $displayorder[$key]);
					}
				}

				foreach($displayorder as $key => $value) {
					if(preg_match("/^-?\d*$/", $value)) {
						$displayorder[$key] = $value;
					}
				}

				foreach($imgpolloptionaid as $key => $value) {
					if(preg_match("/^\d*$/", $value)) {
						$imgpolloptionaid[$key] = $value;
					}
				}

				$imgpollarray['options'] = $imgpolloption;
				$imgpollarray['expiration'] = $expiration;
				$imgpollarray['multiple'] = !empty($multiplepoll);
				$imgpollarray['visible'] = empty($visibilitypoll);
				$imgpollarray['overt'] = !empty($overt);
				$imgpollarray['allowguestpoll'] = !empty($allowguestpoll);
				$imgpollarray['allowpollnum'] = !empty($allowpollnumcheck) ? (!$allowpollnum ? 0 : $allowpollnum) : '';

				foreach($imgpolloptionid as $key => $value) {
					if(!preg_match("/^\d*$/", $value)) {
						showmessage('submit_invalid');
					}
				}
				$maxchoices = !empty($multiplepoll) ? (!$maxchoices || $maxchoices >= count($imgpolloption) ? count($imgpolloption) : $maxchoices) : '';
				if(preg_match("/^\d*$/", $maxchoices)) {
					if(!$imgpollarray['multiple']) {
						$imgpollarray['maxchoices'] = 1;
					} elseif(empty($maxchoices)) {
						$imgpollarray['maxchoices'] = 0;
					} else {
						$imgpollarray['maxchoices'] = $maxchoices;
					}
				}
				$expiration = intval($expiration);
				if($close) {
					$imgpollarray['expiration'] = $timestamp;
				} elseif($expiration) {
					if(empty($imgpollarray['expiration'])) {
						$imgpollarray['expiration'] = 0;
					} else {
						$imgpollarray['expiration'] = $timestamp + 86400 * $expiration;
					}
				}

				$optid = array();
				$oldaid = array();
				$query = $db->query("SELECT polloptionid, aid FROM {$tablepre}imgpolloptions WHERE tid='$tid'");
				while($tempoptid = $db->fetch_array($query)) {
					$optid[] = $tempoptid['polloptionid'];
					$oldaid[$tempoptid['polloptionid']] = $tempoptid['aid'];
				}

				$aids = array();
				foreach($imgpollarray['options'] as $key => $value) {
					$aids[$key] = array('aid'=>$imgpolloptionaid[$key], 'desc'=>$value);
					if(in_array($imgpolloptionid[$key], $optid)) {
						if($alloweditpoll) {
							$db->query("UPDATE {$tablepre}imgpolloptions SET displayorder='".$displayorder[$key]."', polloption='".dhtmlspecialchars(trim($value))."', aid='".$imgpolloptionaid[$key]."', `describe`='".dhtmlspecialchars(trim($imgpolloptiondesc[$key]))."' WHERE polloptionid='$imgpolloptionid[$key]' AND tid='$tid'");
						} else {
							$db->query("UPDATE {$tablepre}imgpolloptions SET displayorder='".$displayorder[$key]."' WHERE polloptionid='$imgpolloptionid[$key]' AND tid='$tid'");
						}
					} else {
						$attachnew[$aids[$key]['aid']] = array('description'=>$aids[$key]['desc']);
						$db->query("INSERT INTO {$tablepre}imgpolloptions (tid, displayorder, polloption, aid, `describe`) VALUES ('$tid', '".$displayorder[$key]."', '".dhtmlspecialchars(trim($value))."', '".$imgpolloptionaid[$key]."', '".dhtmlspecialchars(trim($imgpolloptiondesc[$key]))."')");
					}
				}
				$db->query("UPDATE {$tablepre}imgpolls SET multiple='$imgpollarray[multiple]', visible='$imgpollarray[visible]', maxchoices='$imgpollarray[maxchoices]', expiration='$imgpollarray[expiration]', overt='$imgpollarray[overt]', allowguestpoll='$imgpollarray[allowguestpoll]', allowpollnum='$imgpollarray[allowpollnum]' WHERE tid='$tid'", 'UNBUFFERED');

				//删除多余的附件
				foreach ($oldaid as $ipoid => $ipoaid) {
					if ($ipoaid != $aids[$ipoid]['aid']) {
						$attachnew[$aids[$ipoid]['aid']] = array('description'=>$aids[$ipoid]['desc']);
						$attachdel[] = $ipoaid;
					}
				}
			} else {
				$polladd = ', special=\'0\'';
				$db->query("DELETE FROM {$tablepre}imgpolls WHERE tid='$tid'");
				$db->query("DELETE FROM {$tablepre}imgpolloptions WHERE tid='$tid'");
			}
		}
	}

	function editpost_submit_end() {

	}

	function newreply_submit_end() {

	}

	function viewthread($tid) {
		global $imgpollsuccess;
		global $db, $sdb, $tablepre, $timestamp;
		global $attachrefcheck, $ftp, $attachimgpost, $attachurl, $thread, $forum, $fid;
		global $groupid, $alloweditpoll, $discuz_uid, $onlineip;
		global $extra, $highlight, $authorid, $specialextra, $imgpollorder;
		global $attachpids, $attachtags, $postlist, $showimages, $skipaids;

		$messageshowposition = $this->config['vars']['messageshowposition'];
		$showallowpollnum = $this->config['vars']['showallowpollnum'];
		$showpollvoters = $this->config['vars']['showpollvoters'];

		$thethreadurl = "viewthread.php?tid=$tid&amp;extra=$extra".(isset($highlight) ? "&amp;highlight=".rawurlencode($highlight) : '').(!empty($authorid) ? "&amp;authorid=$authorid" : '').$specialextra;

		$post = $postlist;
		$post = array_shift($post);
		if ($messageshowposition == 1) {
			$temp = array();
			$temp[$post['pid']] = $post;
			$sppos = strrpos($temp[$post['pid']]['message'], chr(0).chr(0).chr(0));
			$specialextra = substr($temp[$post['pid']]['message'], $sppos + 3);
			if($attachpids != '-1') {
				require_once DISCUZ_ROOT.'./include/attachment.func.php';
				parseattach($attachpids, $attachtags, $temp, $showimages, $skipaids);
			}
			$temp[$post['pid']]['message'] = substr($temp[$post['pid']]['message'], 0, (strlen($specialextra) + 3)*(-1));
			$post = $temp[$post['pid']];
			$postlist[$post['pid']]['message'] = '';
		}

;
		$polloptions = array();
		$votersuid = '';

		if($count = $sdb->fetch_first("SELECT MAX(votes) AS max, SUM(votes) AS total FROM {$tablepre}imgpolloptions WHERE tid = '$tid'")) {
			$options = $sdb->fetch_first("SELECT multiple, visible, maxchoices, expiration, overt, allowpollnum, allowguestpoll FROM {$tablepre}imgpolls WHERE tid='$tid'");
			$multiple = $options['multiple'];
			$visible = $options['visible'];
			$imgpollmaxchoices = $maxchoices = $options['maxchoices'];
			$expiration = $options['expiration'];
			$overt = $options['overt'];
			$allowpollnum = $options['allowpollnum'];
			$allowguestpoll = $options['allowguestpoll'];

			if (in_array(trim($imgpollorder), array('votes','views'))) {
				$ordersql = $imgpollorder.' DESC';
			} else {
				$imgpollorder = '';
				$ordersql = "displayorder";
			}

			$query = $sdb->query("SELECT ipo.*, a.attachment, a.remote, a.isimage FROM {$tablepre}imgpolloptions ipo LEFT JOIN {$tablepre}attachments a ON ipo.aid=a.aid WHERE ipo.tid='$tid' ORDER BY ipo.".$ordersql);
			$voterids = '';
			$bgcolor = rand(0, 9);
			while($option = $sdb->fetch_array($query)) {
				$skipaids[] = $option['aid'];
				$refcheck = (!$option['remote'] && $attachrefcheck) || ($option['remote'] && ($ftp['hideurl'] || ($option['isimage'] && $attachimgpost && strtolower(substr($ftp['attachurl'], 0, 3)) == 'ftp')));
				$aidencode = aidencode($option['aid']);
				$imgatturl = $option['remote'] ? $ftp['attachurl'] : $attachurl;

				if($bgcolor > 9) {
					$bgcolor = 0;
				}
				$viewvoteruid[] = $option['voterids'];
				$voterids .= "\t".$option['voterids'];
				$polloptions[] = array
				(
					'polloptionid'	=> $option['polloptionid'],
					'polloption'	=> preg_replace("/\[url=(https?|ftp|gopher|news|telnet|rtsp|mms|callto|bctp|ed2k|thunder|synacast){1}:\/\/([^\[\"']+?)\](.+?)\[\/url\]/i",
						"<a href=\"\\1://\\2\" target=\"_blank\">\\3</a>", $option['polloption']),
					'votes'		=> $option['votes'],
					'views'		=> $option['views'],
					'width'		=> @round($option['votes'] * 300 / $count['max']) + 2,
					'percent'	=> @sprintf("%01.2f", $option['votes'] * 100 / $count['total']),
					'color'		=> $bgcolor,
					'describe'	=> preg_replace("/\[url=(https?|ftp|gopher|news|telnet|rtsp|mms|callto|bctp|ed2k|thunder|synacast){1}:\/\/([^\[\"']+?)\](.+?)\[\/url\]/i",
		"<a href=\"\\1://\\2\" target=\"_blank\">\\3</a>", $option['describe']),
					'attachment'=> $refcheck ? "attachment.php?aid=$aidencode&amp;noupdate=yes" : "$imgatturl/$option[attachment]"
				);
				$bgcolor++;
			}
			
			$voters = explode("\t", preg_replace('/\|\d+/ies', '', $voterids));
			$voterids = explode("\t", $voterids);
			$voters = array_unique($voters);
			$voterscount = count($voters) - 1;
			array_shift($voters);

			if(!$expiration) {
				$expirations = $timestamp + 86400;
			} else {
				$expirations = $expiration;
				if($expirations > $timestamp) {
					$thread['remaintime'] = remaintime($expirations - $timestamp);
				}
			}

			$allowpollnum_last = 1;
			$allowvotepolled = 1;
			if ($allowpollnum) {
				$allowpollnum_poll = 0;
				foreach ($voterids as $k => $v) {
					list($uid, $pollnum) = explode('|', $v);
					if ($uid == ($discuz_uid ? $discuz_uid : $onlineip)) {
						if ($pollnum) {
							$allowpollnum_poll += $pollnum;
						} else {
							$allowpollnum_poll += 1;
						}
						
					}
				}
				$allowpollnum_last = $allowpollnum - $allowpollnum_poll;
				$allowpollnum_last <= 0 && $allowpollnum_last = $allowvotepolled = 0;
				$imgpollmaxchoices = $allowpollnum_last < $imgpollmaxchoices ? $allowpollnum_last : $imgpollmaxchoices;
			} else {
				$allowvotepolled = !in_array(($discuz_uid ? $discuz_uid : $onlineip), $voters);
			}
			$allowvotethread = (!$thread['closed'] && !checkautoclose() || $alloweditpoll) && $timestamp < $expirations && $expirations > 0;

			$allowvote = $allowvotepolled && $allowvotethread && $allowpollnum_last;

			$optiontype = $multiple ? 'checkbox' : 'radio';
			$visiblepoll = $visible || $forum['ismoderator'] || ($discuz_uid && $discuz_uid == $thread['authorid']) || ($expirations >= $timestamp && in_array(($discuz_uid ? $discuz_uid : $onlineip), $voters)) || $expirations < $timestamp ? 0 : 1;
		}
		include template('imgpoll:viewthread_imgpoll');
		return $return;
	}

	function _getconfig() {
		@include_once DISCUZ_ROOT.'./forumdata/cache/plugin_imgpoll.php';
		return $_DPLUGIN['imgpoll'];
	}
}

?>

