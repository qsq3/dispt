<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: install.php 20657 2009-11-17 08:48:36Z Ted $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$sql = <<<EOF

CREATE TABLE IF NOT EXISTS `cdb_imgpolls` (
  `tid` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `overt` tinyint(1) NOT NULL DEFAULT '0',
  `multiple` tinyint(1) NOT NULL DEFAULT '0',
  `visible` tinyint(1) NOT NULL DEFAULT '0',
  `allowguestpoll` tinyint(1) NOT NULL DEFAULT '0',
  `maxchoices` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `allowpollnum` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `expiration` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`tid`)
) ENGINE=MyISAM DEFAULT CHARSET=gbk;

CREATE TABLE IF NOT EXISTS `cdb_imgpolloptions` (
  `polloptionid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tid` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `aid` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `votes` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `views` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `displayorder` tinyint(3) NOT NULL DEFAULT '0',
  `polloption` varchar(80) NOT NULL DEFAULT '',
  `describe` mediumtext NOT NULL,
  `voterids` mediumtext NOT NULL,
  PRIMARY KEY (`polloptionid`),
  KEY `tid` (`tid`,`displayorder`)
) ENGINE=MyISAM  DEFAULT CHARSET=gbk;

EOF;

runquery($sql);

$finish = TRUE;

?>