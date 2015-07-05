<?php
include_once 'sphinxapi.php';
include_once 'sql.class.php';
header("Content-type: text/html; charset=utf-8");
//TODO：标题高亮显示
class Search
{
	private $sphinx;
	private $index="myindex";
	private $limit=10;//设置每页出现的个数
	private $model;
	private $keyword;
	private $opts;
	function Search()
	{ 
		    $this->model=new Model();
        $this->sphinx = new SphinxClient ();
        $this->sphinx->SetServer ( '127.0.0.1', 9312);
        $this->sphinx->SetConnectTimeout ( 1 );
        $this->sphinx->SetArrayResult ( true );
        $this->sphinx->SetMatchMode(SPH_MATCH_ANY);
        $this->sphinx->SetSortMode(SPH_SORT_RELEVANCE);
        //设置权重
        $this->sphinx->SetFieldWeights(array(
          "title"=>100,
          "content"=>1
        ));
        $this->opts = array
        (
           "before_match"  => "<span style='color:red'>",
           "after_match"  => "</span>",
           "chunk_separator" => " ... ",
           "limit"    => 256,
           "around"   => 10,
           "exact_phrase"=>false
        );
	}

	function Query($page)
	{
	   $this->sphinx->SetLimits(($page-1)*$this->limit,$this->limit); 
       return $this->sphinx->Query($this->keyword, $this->index);
	}
	function run($keyword,$page)
	{ 
	   $this->keyword=$keyword;
	   $result=$this->Query($page);
       if($result===false)
       {
       	   return false;
       }
       else
       {
       	   $ret['datas']=$this->getdata($result["matches"]);
       	   $ret['total_found']=$result["total_found"];
       	   $ret['total']= $result['total'];
       	   return $ret;
       }
	}
	//根据主键，从数据库中提取正文
	function getdata($matches)
	{
	   $ids=array();

       foreach ($matches as $value) 
       {
       	   array_push($ids, $value['id']);
       }

       $datas=$this->model->select($ids);
       foreach ($datas as &$data) 
       {
        //自动提取正文并着色
       	 $data['content']=$this->setcolor($data['content']);
         //为标题着色
         $data['title']=$this->titlecolor($data['title']);
       }
       return $datas;
	}
	//自动摘要及关键字着色
	function setcolor($content)
	{
       $res=$this->sphinx->BuildExcerpts(array($content),$this->index,$this->keyword,$this->opts);
       if($res===false)
       {
       	 echo "查询关键字时失败！";
       	 exit();
       }
       return $res[0];
	}
  //为标题着色
  function titlecolor($title)
  {
    $keywords=$this->sphinx->BuildKeywords($this->keyword, $this->index, false);
    foreach ($keywords as  $value) 
    {
       $wors="/".$value["tokenized"]."/i";
       $title=preg_replace($wors,"<font color='red'>$0</font>",$title);
    }
    return $title;   
  }
};
?>