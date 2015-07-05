<?php
header("Content-Type: text/html; charset=utf-8");
include_once 'function.php';
class Model
{
	private $link; 
  private $name="root";
	private $password="root";
	private $database="test";

	function __construct()
	{
		@$this->link= mysql_connect("localhost", $this->name, $this->password) or die("连接服务器失败，请检查后重试");
         mysql_select_db($this->database)or die("初始化数据库失败，请检查后重试"); 
         mysql_query('set names utf8');
	}
	function __destruct()
  {
    	mysql_close($this->link);
  }
    //添加一条数据
    function add($data)
    {
        $data['content']=mysql_real_escape_string($data['content']);
        $query="insert into data (url,title,content) values(
             '".$data['url']."',
             '".$data['title']."',
             '".$data['content']."')";
        mysql_query($query) or die("插入失败，请重试");
    }
    function adds($datas)
    {
        $count=1;
        foreach ($datas as $key => $data) 
        {
            $data['content']=mysql_real_escape_string($data['content']);
            if($count==1)
            {

                $query="insert into data (url,title,content) values(
                       '".$data['url']."',
                       '".$data['title']."',
                       '".$data['content']."')";
            }
            else
            {

                $query.=",(
                       '".$data['url']."',
                       '".$data['title']."',
                       '".$data['content']."')";
            }
            $count++;  
        }
        mysql_query($query) or die("插入已完成队列失败，请重试".mysql_error());
    }
    function find($data,$page=1)
    {
        $query="select * from data where title like '%".$data."%' or content like '%".$data."%' ";
        $result=mysql_query($query) or die("插入失败，请重试");
        $res=array();
        for ($i=0; $i <mysql_num_rows($result); $i++) 
        { 
          array_push($res,mysql_fetch_assoc($result));
        }
        return $res;
    }
    function put_wait($urls)
    {
        if(empty($urls))
          return ;
        //TODO:检测主键
        $count=1;
        $query="";
        //SQL语句竟然可以这样用。。刚发现。。处理速度大大加快！！
        foreach ($urls as $key => $value) {
            $value=cn_urlencode($value);
            if($count==1)
                $query="insert into wait values('".$value."')";
            else
                $query.=",('".$value."')";
            $count++;
        }
        mysql_query($query) or die("插入待查询队列失败，请重试");
    }
    //检查某个表是否为空
    function table_empty($tablename)
    {
      $query="select count(*) as nums from ".$tablename;
      $result=mysql_query($query) or die("table_empty失败，请重试");
      return (mysql_fetch_assoc($result)['nums']==0)?true:false;
    }
    //从等待队列中抽取出,取出之后并从队列中删除
    function waiturls($nums)
    {
        $query="select * from wait limit 0,".$nums;
        $result=mysql_query($query) or die("选择失败，请重试");
        $res=array();
        for ($i=0; $i <mysql_num_rows($result); $i++) 
        { 
          array_push($res,mysql_fetch_assoc($result)['url']);
        }
        $range=implode("','",$res);
        $query="delete from wait where url in ('".$range."')";
        mysql_query($query) or die("删除wait失败，请重试");
        return $res;
    }
    function is_new($url)
    {
       $query="select count(*) as nums from data where url='".$url."'";
       $result=mysql_query($query) or die("选择data失败，请重试");
       return (mysql_fetch_assoc($result)['nums']==0)?true:false;
    }
    
    function select($ids)
    {
      $range=implode(",", $ids);
      $query="select * from data where id in ($range)";
      $result=mysql_query($query) or die("table_empty失败，请重试");
      $res=array();
      for ($i=0; $i <mysql_num_rows($result); $i++) 
      { 
          array_push($res,mysql_fetch_assoc($result));
      }
      return  $res;
    }
    function coolshell()
    {
     $query="select * from data where url like '%coolshell%'";
     $result=mysql_query($query) or die("table_empty失败，请重试");
     $res=array();
     for ($i=0; $i <mysql_num_rows($result); $i++) 
     { 
          array_push($res,mysql_fetch_assoc($result));
     }
     return  $res;
    }
};
?>