<?php
  //解决URL编码问题
  function cn_urlencode($url)
  { 
    $pregstr = "/[\x{4e00}-\x{9fa5}]+/u";//UTF-8中文正则 
    if(preg_match_all($pregstr,$url,$matchArray))
    {//匹配中文，返回数组 
        foreach($matchArray[0] as $key=>$val)
        { 
         $url=str_replace($val, urlencode($val), $url);//将转译替换中文 
        } 
        if(strpos($url,' '))
        {//若存在空格 
          $url=str_replace(' ','%20',$url); 
        } 
    } 
    return $url; 
   }
   //分页的函数，模仿google只显示10页
   function pages($now,$itemnums,$keyword)
   {
      //分页的规则是:当page<5的时候正常显示，而当page>5的时候，要时刻保证page在正中间
      //但是页面上必须同时出现10个按钮
      // echo $itemnums;
      $pages=caltotal($itemnums);
      $prefix="search.php?wd=$keyword&page=";
      if($pages<=1)
      {
         return; 
      }
      $preclass=($now==1)?'class="disabled"':"";
      $phref=$prefix.($now-1);
      echo "<li ".$preclass.">";
      echo '<a href="'.$phref.'" aria-label="Previous">';
      echo '<span aria-hidden="true">上一页</span>';
      echo '</a>';
      echo '</li>';
      $start=0;$end=0;
      if($pages<=10||$now<=6)
      {
        $start=1;
        $end=min(10,$pages);
      }
      else
      {
        //当总页数大于10,并且now>6
        $end=min($now+4,$pages);
        $start=$end-9;
      }
      for ($i=$start; $i <=$end; $i++) 
      { 
        $href=$prefix.$i;
        if($now==$i)
            echo '<li class="active"><a href='.$href.'>'.$i.'</a></li>';
        else
            echo '<li ><a href='.$href.'>'.$i.'</a></li>';
      }
      $nextclass=($now==$pages)?'class="disabled"':"";
      $nhref=$prefix.($now+1);
      echo "<li ".$nextclass.">";
      echo '<a href="'.$nhref.'" aria-label="Next">';
      echo '<span aria-hidden="true">下一页</span>';
      echo '</a>';
      echo '</li>';
   }
   //计算总共又多少页
   function caltotal($itemnums)
   {
      return intval($itemnums/10)+(intval($itemnums/10)?0:1);
   }
?>