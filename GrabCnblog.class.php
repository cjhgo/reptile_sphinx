<?php
include_once 'sql.class.php';
include_once 'simple_html_dom.php';
include_once 'CURL.php';
class GrabCnblog
{
  private $count=0;
  private $model;
  private $curl;
  private $totalnums=10000;
  private $waittime=60;
  private $cache;
  function GrabCnblog()
  {
     $this->model=new Model();
     $this->curl=new CURL();
     $this->cache=array();
  }
  function init()
  {
  	 $starturl="http://www.cnblogs.com/sitemap.xml";
     while(!$filecontent=file_get_contents($starturl))
        ;
     $xml_array=simplexml_load_string($filecontent); 
     $urls=array();
     foreach($xml_array as $tmp)
     { 
     	array_push($urls, $tmp->loc);
     }
     $this->model->put_wait($urls);
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
      $urls=$this->model->waiturls(2000);
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
    if(!$this->model->is_new($url)||$this->in_cache($url)||$r['info']['http_code']!=200)
    {
      return;
    }
    //如果是分类的话
    if(strchr($url,"http://www.cnblogs.com/cate/")!==false)
    {
      // echo "分类";
      $this->deal_cate($r['content'],$url);
    }
    //如果是文章页的话
    else if(strchr($url,"/p/")!==false)
    {
       // echo "文章";
      $this->deal_article($r['content'],$url);
    }
    else
    {
      $html=str_get_html($r['content']);
      $sign=$html->find(".catListTitle");
      // var_dump($sign);
      //如果是个人主页的话
      if($sign)
      {
         // echo "主页";
         $this->deal_person($r['content'],$url);
      }
      //如果是其他的话
      else
      {
         // echo "其他";
         $this->deal_other($r['content'],$url);
      }
    }
  }
  function deal_cate($r,$url)
  {
     $html=str_get_html($r);
     $pager= $html->find(".pager",0);
     if(!$pager)
        return;
     if(strchr(($pager->last_child()->plaintext),'Next')!==false)
     {
        $pagenums=$pager->last_child()->prev_sibling()->plaintext;
     }
     else
     { 
        $pagenums=intval($pager->last_child()->plaintext);
     }
     if(stripos($url,"#")!==false)
        $url=substr($url,0,stripos($url,"#"));
     for ($i=1; $i <= $pagenums; $i++) 
     {
        $this->deal_cate_page($url."#".$i);
     }
  }
  function deal_cate_page($url)
  {
    $mycurl=new CURL();
    $html=str_get_html($mycurl->read($url)['content']);
    $items=$html->find(".post_item");
    if($items)
    {
       $urls=array();
       foreach ($items as $oneitem) 
       {
         $link=$oneitem->find(".titlelnk",0);
         if($oneitem)
         {
          array_push($urls,$link->href);
         }
       }
       $this->model->put_wait($urls);
    }

  }
  function deal_person($r,$url)
  { 
       $mycurl=new CURL();
       $html=str_get_html($mycurl->read($url."/default.html?page=2")['content']);
       // echo $url."/default.html?page=2";
       $pager= $html->find(".pager",0);
       if(!$pager)
           return ;

       if($pager->last_child()->plaintext=="末页")
       {
           $pagenums=$pager->last_child()->prev_sibling()->prev_sibling()->plaintext;
       }
       else if($pager->last_child()->plaintext=="下一页")
       {
           $pagenums=$pager->last_child()->prev_sibling()->plaintext;
       }
       else
       {
           $pagenums=$pager->last_child()->plaintext;
       }
       for ($i=1; $i <= $pagenums; ++$i) 
       {
           $this->deal_person_page($url."/default.html?page=".$i);
       }
  }
  function deal_person_page($url)
  {
      $mycurl=new CURL();
      $html=str_get_html($mycurl->read($url)['content']);
      $items=$html->find(".day");
      if(!$items)
        return ;
      $urls=array();
      foreach ($items as $oneitem) 
      {
        $href=$oneitem->find(".postTitle",0)->find("a",0)->href;
        array_push($urls, $href);
      }
      $this->model->put_wait($urls);
  }
  function deal_article($r,$url)
  {
    $html=str_get_html($r);
    $content= $html->find("#cnblogs_post_body",0);
    if($content)
    {
       $data['title']=$html->find("title",0)->plaintext;
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
      echo "Complete:".$this->count."\n";
    }
  }
};

