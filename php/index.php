<?
  $dir=@scandir(__DIR__);
  if(is_array($dir)){
    foreach($dir as $item){
      $ext_filename=strtolower(pathinfo($item,PATHINFO_EXTENSION));  
      if($ext_filename=="php" && $item!="index.php"){
        require_once(__DIR__."/{$item}");
      }
    }
  }