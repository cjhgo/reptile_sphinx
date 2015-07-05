<?php
   include_once 'sql.class.php';
   include_once 'function.php';
   include_once 'search.class.php';
   header("Content-Type: text/html; charset=utf-8");
   $t1 = microtime(true);
   $word=$_GET["wd"];
   //TODO:如果页数大了，就提示只能显示1000条数据
   
   if(isset($_GET['page']))
   {
      $page=intval($_GET['page']);
      $page=($page<=0)?1:$page;
   }
   else
   {
      $page=1;
   }
   $search=new Search();
   $data=$search->run($word,$page);
   $t2 = microtime(true);
   $time=$t2-$t1;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo $word;?>-搜索结果</title>
	<link rel="stylesheet" type="text/css" href="bootstrap/css/bootstrap.min.css">
	<!--script type="text/javascript" src="bootstrap/js/bootstrap.min.js"></script-->
	<style type="text/css">
	.search{margin-top:20px;margin-left:82px;height: 40px}
  .count{width: 100%}
  #main{margin-left: 100px;padding-top: 10px;width:70%;height:auto;}
  .title{}
  .summary{}
  .url{color: green;}
  .result{margin-top: 20px;margin-bottom: 20px}
  .pages{text-align: center;margin-bottom: 100px;}
	</style>
</head>
<body>
<form  class="search" action="search.php" method="get">
  <div class="col-lg-10">
    <div class="input-group">
      <input type="text" class="form-control" placeholder="Search for..." name="wd"
      value=<?php echo "'".$word."'";?>>
      <span class="input-group-btn">
        <button class="btn btn-primary" type="submit">搜索一下</button>
      </span>
    </div>
  </div>
 </form>
 <div id="main">
    <div class="alert alert-success count" role="alert">
    找到约 <?php echo $data['total_found'];?> 条结果 （用时 <?php echo round($time,5);?> 秒）
    </div>
    <?php
    $countnum=1;
    foreach ($data['datas'] as $key => $value) 
    {
       echo '<div class="result">';
       echo '<a href="'.$value['url'].'" class="title">'.$value['title'].'</a>';
       echo '<div class="url">'.$value['url'].'</div>';
       echo '<p class="summary">'.$value['content'];
       echo '</p>';
       echo '</div>';
       echo "<hr>";
    }
 ?>
  <nav class="pages">
   <ul class="pagination">
    <?php
     pages($page,$data['total'],$word);
    ?>
  </ul>
  </nav>
 </div>
</body>
</html>