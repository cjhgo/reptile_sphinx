<?php
header("Content-type: text/html; charset=utf-8");
// include_once 'simple_html_dom.php';
// include_once 'CURL.php';
// include_once 'GrabRyf.class.php';
// include_once 'GrabCsdn.class.php';
include_once 'GrabCnblog.class.php';

include_once 'GrabCShell.class.php';
$gb=new GrabCShell();
// $url="http://coolshell.cn/articles/17049.html";
// $r['content']=file_get_contents($url);
// $gb->deal_post($r,$url);
$gb->run();
?>