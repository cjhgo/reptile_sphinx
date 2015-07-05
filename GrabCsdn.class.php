<?php
include_once 'sql.class.php';
include_once 'simple_html_dom.php';
include_once 'CURL.php';
class GrabCsdn
{
  private $count=0;
  private $model;
  private $curl;
  //在csdn抓取20000数据
  private $totalnums=20000;
  private $waittime=60;
  private $cache;
  function GrabCsdn()
  {
     $this->model=new Model();
     $this->curl=new CURL();
     $this->cache=array();
  }
  function init()
  {
  	 $starturl="http://www.csdn.net/article/sitemap.txt";
     while(!$filecontent=file_get_contents($starturl))
        ;
     $lines=explode("http://", $filecontent);
     array_splice($lines, 0 , 1);
     array_walk($lines,function(&$value,$key)
     {
     	$value="http://".trim($value);
     });
     $this->model->put_wait($lines);
  }
  function run()
  {
  	while($this->count<=$this->totalnums)
  	{
  	    //当待抓取的队列不为空的时候
  	    $cwait=0;
  		while($this->model->table_empty("wait")&&$cwait<$this->waittime)
  		{
             sleep(5);
  		}
  		if($cwait>$this->waittime)
  			exit(0);
  		$urls=$this->model->waiturls(3000);
  		//一次多线程处理200条
  		foreach ($urls as $key => $value) 
  		{
  			$url=array($value);
  			$callback=array(array($this,'deal'),array($value));
  			$this->curl->add($url,$callback);
  		}
  		$this->curl->go();
  	}
  	if(!empty($this->cache))
  	{
  		$this->model->adds($this->cache);
  	}
  }
  function in_cache($url)
  {
  	foreach ($this->cache as $key => $value) {
  		if($value['url']==$url)
  			return true;
  	}
  	return false;
  }
  function deal($r,$url)
  {
  	//TODO:检测是否已经是完成的数据
  	if(!$this->model->is_new($url)||$this->in_cache($url))
  	{
  		return;
    }

  	//如果是文章的话
  	if(strchr($url,"http://www.csdn.net/article")!==false)
  	{
      $this->deal_article($r['content'],$url);
  	}
  	//如果是标签类的话
  	elseif (strchr($url,"http://www.csdn.net/tag")!==false) 
  	{
      $this->deal_tags($r['content'],$url);
  	}
  	//如果是分享
  	elseif(strchr($url,"http://share.csdn.net")!==false) 
  	{
      $this->deal_share($r['content'],$url);
  	}
  	//博客
  	elseif (strchr($url,"http://blog.csdn.net")!==false) 
  	{
      $this->deal_blog($r['content'],$url);
  	}
  	//极客头条
  	elseif (strchr($url,"http://geek.csdn.net")!==false) 
  	{
      $this->deal_geek($r['content'],$url);
  	}
  	else
  	{
       $this->deal_other($r['content'],$url);
  	}
  }
  function deal_article($r,$url)
  {
  	$html=str_get_html($r);
  	$atag = $html->find(".tag",0);
  	$asummary=$html->find(".summary",0);
  	$acontent=$html->find(".news_content",0);
  	if($atag&&$asummary&&$acontent)
  	{
  	   $data['title']=$html->find("title",0)->plaintext;
  	   $data['content']=$atag->plaintext." ".$asummary->plaintext." ".$acontent->plaintext;
  	   $data['url']=$url;
  	   $this->add2model($data);
  	}
  	else
  	{

  	}
  }
  //标签类
  function deal_tags($r,$url)
  {
     $html=str_get_html($r);
  	 $urlobj = $html->find("a.tit_list");
  	 if($urlobj)
  	 {
  	 	   $urls=array();
  	     foreach ($urlobj as $key => $value)
  	     {
  	 	   array_push($urls, $value->href);
  	     }
  	     $data['title']=$html->find("title",0)->plaintext;
  	     $data['content']=$html->find("body",0)->plaintext;
  	     $this->model->put_wait($urls);
  	     $data['url']=$url;
  	     $this->add2model($data);
  	 }
  	 else
  	 {

  	 }
  }
  //分享
  function deal_share($r,$url)
  {
  	$html=str_get_html($r);
    $content=$html->find(".content",0);
    $authors=$html->find(".authors",0);
    $tags=$html->find(".tag",0);
    if($content&&$authors&&$tags)
    {
       $data['title']=$html->find("title",0)->plaintext;
       $data['content']=$authors->plaintext." ".$content->plaintext." ".$tags->plaintext;
       $data['url']=$url;
  	   $this->add2model($data);
    }
    else
    {
  
    }
  }
  //博客
  function deal_blog($r,$url)
  {
    $html=str_get_html($r);
    $content = $html->find("#article_content",0);
    if($content)
    {
       $data['title']=$html->find("title",0)->plaintext;
       $data['content']=$content->plaintext;
       $data['url']=$url;
  	   $this->add2model($data);
    }  
    else
    {
      
    }
  }
  //极客头条
  function deal_geek($r,$url)
  {
  	  $html=str_get_html($r);
      $content=$html->find(".news_description",0);
      if($content)
      {
      	 $data['title']=$html->find("title",0)->plaintext;
      	 $link =$html->find(".link_detail",0);
      	 $aurl="";
      	 if($link)
      	 {
            $aurl=$link->href;
            $this->model->put_wait(array($aurl));
      	 }
      	 $data['content']=$content->plaintext;
      	 $data['url']=$url;
      	 $this->add2model($data);
      }
  }
  //其他
  function deal_other($r,$url)
  {	
  	$html=str_get_html($r);
  	$body=$html->find("body",0);
  	$title=$html->find("title",0);
  	if($title&&$body)
  	{
  	  $data['content']=$body->plaintext;
  	  $data['title']=$title->plaintext;
  	  $data['url']=$url;
      $this->add2model($data);
  	}
  	else
  	{
      
  	}
  }
  function add2model($data)
  {
  	$this->count++;
    array_push($this->cache, $data);
  	//等到插入500才继续插入
  	if($this->count%500==0)
  	{
      $this->model->adds($this->cache);
      $this->cache=array();
  	}
  }
  
};
?>