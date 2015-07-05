<?php
include_once 'sql.class.php';
include_once 'simple_html_dom.php';
include_once 'CURL.php';
class GrabCShell
{
  private $model;
  private $curl;
  function GrabCShell()
  {
     $this->model=new Model();
     $this->curl=new CURL();
  }
  function run()
  {
	  $start_url="http://coolshell.cn/";
	  $src=$this->curl->read($start_url)['content'];
	  $html=str_get_html($src);
	  $ret=$html->find('div[id=categories-367921423]')[0]->children[1];
	  $urls=array();
	  for ($i=0; $i < 28 ; $i++) 
      {  
           $href=$ret->children[$i]->find('a')[0]->href; 
           $url=array($href);
           $callback=array(array($this,'deal'),array($href));
           $this->curl->add($url,$callback);
      }
      $this->curl->go();
  }
  function deal($r,$start_url)
  {
  	$html=str_get_html($r['content']);
  	$ret = $html->find('div[id=pagenavi]',0);
    if($ret->find('div[class=wp-pagenavi]',0))
    {
    	$ret=$ret->find('div[class=wp-pagenavi]',0)
                 ->find('a');
        $pages=count($ret); //获得页数   
    }
    else
    {
    	 	$pages=1;
    }
               
    $postcount=1;    
    for ($i=1; $i <= $pages; $i++) 
    {
    	 $mycurl=new CURL();
    	 $pageurl=$start_url."/page/".$i;
         $html=str_get_html($mycurl->read($pageurl)['content']);
         $posts=$html->find('div[class=post]');
         for ($j=0; $j < count($posts); $j++)
         {
             $href=$posts[$j]->first_child()->first_child()->href;
             $url=array($href);
             $callback=array(array($this,'deal_post'),array($href));
             $this->curl->add($url,$callback);
         } 
    }
  }
  function deal_post($r,$url)
  {
  	$html=str_get_html($r['content']);
  	$data['content']=$html->find(".post",0)->plaintext;
  	if($html->find(".commentlist",0))
  	   $data['content'].=$html->find(".commentlist",0)->plaintext;
  	$data['title']=$html->find("title",0)->plaintext;
    $data['url']=$url;
    $this->model->add($data);
  }
};