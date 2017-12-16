<?php
class mysql{
  protected static $_instance = NULL;
  protected $db_hostname = NULL;
  protected $db_port = NULL;
  protected $db_database = NULL;
  protected $db_username = NULL;
  protected $db_password = NULL;
  protected $last_connectionTime = 0;
  protected $errorMsg="";
  protected $pdo = 0;
  
  private function init(){
    $this->pdo=null;
    try {
      $this->last_connectionTime=time();
      $this->pdo=new PDO(
        "mysql:host={$this->db_hostname};port={$this->db_port};",
        $this->db_username,
        $this->db_password,
        array(
          PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
        )
      );
      $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      
      if($use)
        $this->selectDB($this->db_database);
    }
    catch(PDOException $e){
      echo "database connect error: ({$this->db_hostname}:{$this->db_port}) \n".$e->getMessage()."\n";
      exit;
    }
  }
  private function connectToDB($use=true){
    /*try {
      //$this->pdo->query('SELECT 1');
      if(!$this->pdo)
        $this->init();
      else{
        $prepare=$this->pdo->prepare("select 1");
        $prepare->execute();
      }
    } catch (PDOException $e) {
      $this->init();            // Don't catch exception here, so that re-connect fail will throw exception
    }*/
    if(time()-$this->last_connectionTime>500){
      $this->init();
    }/**/
  }  
  public function __construct($db_hostname,$db_port,$db_username,$db_password,$db_database=""){
    $this->db_hostname=$db_hostname;
    $this->db_port=$db_port;
    $this->db_username=$db_username;
    $this->db_password=$db_password;
    $this->last_connectionTime=0;
    $this->pdo=null;
    $this->connectToDB();
    $this->selectDB($db_database);
  }
  function pdo(){
    return $this->pdo;
  }
  function driver(){
    return $this->pdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
  }
  function getIncrementValue($database,$tableName){
    if($rs=$this->_query("select `auto_increment` from  information_schema.tables where table_schema = '{$database}' and   table_name = '{$tableName}'",1))
      return intval($rs['AUTO_INCREMENT']);
    return false;
  }
  function getInsertID(){
    $this->connectToDB();
    return $this->pdo->lastInsertId();
  }
  function selectDB($db_database=""){
    if($db_database!=""){
      if($this->db_exists($db_database)){
        $this->db_database=$db_database;
        $this->_query("use {$this->db_database}");
        //echo "[selectDB] use {$this->db_database}\n";
      }
      else{
        //echo "[selectDB] xxxx002\n";
      }
    }
    else{
      //echo "[selectDB] xxxx001\n";
    }
  }
  function getCount($sql,$bindValue=[]){
    $this->connectToDB();
    if(is_object($sql) && get_class($sql)=='PDOStatement'){
      $prepare=$sql;
    }
    else
      $prepare=$this->prepare($sql);
    foreach($bindValue as $key => $value)
      $prepare->bindValue($key,$value);
    if($rs=$this->_query($prepare,1)){
      return $rs[0]*1;
    }
    return -1;
  }
  function db_exists($dbName){
    $this->connectToDB();
    $sql="select schema_name from information_schema.schemata where schema_name = '{$dbName}'";
    $result=$this->_query($sql);
    return ($result!==false) && (count($result)>0);
  }
  function table_exists($tableName){
    $this->connectToDB();
    $sql="show tables like '{$tableName}'";
    $result=$this->_query($sql);
    return ($result!==false) && (count($result)>0);
  }
  function column_exists($database,$table,$column){
    $this->connectToDB();
    $sql="select count(*) from information_schema.columns where table_schema='{$database}' and table_name='{$table}' and column_name='{$column}'";
    return $this->getCount($sql)>0;
  }
  private function prepare($sql){
    $this->connectToDB();
    try {
      return $this->pdo->prepare($sql); 
    }
    catch(PDOException $e){
      return false;
    }
  }
  function query($sql,$bindValue=[],$limit_one=false){
    
    if(is_array($bindValue)){
      $prepare=$this->prepare($sql);
      foreach($bindValue as $key => $value)
        $prepare->bindValue($key,$value);
    }
    else{
      if(count(func_get_args())==2){
        $limit_one=$bindValue;
      }
    }
    return $this->_query($prepare,$limit_one);
  }
  private function _query($query,$limit_one=false){
    $this->connectToDB();
    if(@get_class($query)=='PDOStatement'){
      $this->connectToDB();
      try{
        $k=$query->execute();
        try {
          $result=$query->fetchAll(PDO::FETCH_ASSOC);
          //echo "xxxxxxxxxxxxxxxxx------------------3\n";
          //var_dump($query);
          //var_dump($result);
          if($limit_one){
            if(count($result)!=1){
              return false;
            }
            if(is_array($result)){
              foreach($result as $rs){
                return $rs;
              }
            }
            return false;
          }
          //echo "xxxxxxxxxxxxxxxxx------------------2\n";
          //var_dump($query);
          //var_dump($result);
          return $result;
        }
        catch(PDOException $e){
          return $k;
        }
      }
      catch(PDOException $e){
        $this->_errorMsg="[query error] ".$e->getMessage()."\n".print_r(debug_backtrace(),1)."\n";
        
        echo $this->_errorMsg;
        
        echo "Database error!\n";
        exit;
        
        //echo "[query error] ".$e->getMessage()."\n";
        //print_r(debug_backtrace());
        //exit;
        //echo "error!!!";
        //throw("xx",$e);
        //throwError("查詢錯誤\n".$e->getMessage()."<br />db_database:".db_database."<pre>".print_r($GLOBALS['setting'],1)."<pre>");
        return false;
      }
    }
    else{
      //echo "[Query] {$query}\n";
      $prepare=$this->prepare($query);
      if(@get_class($prepare)=='PDOStatement'){
        $result=$this->_query($prepare,$limit_one);
        //echo "xxxxxxxxxxxxxxxxx------------------1\n";
        //var_dump($result);
        return $result;
      }
      else
        return false;
    }
    return false;
  }
  function __destruct(){
    $this->pdo=null;
  }
  public function errorMsg(){
    return $this->_errorMsg;
  }
};

