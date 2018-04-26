<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$sql = <<<EOF

DROP TABLE cdb_imgpolls;
DROP TABLE cdb_imgpolloptions;

EOF;

//runquery($sql);

$finish = TRUE;

?>