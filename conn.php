<?php
  class Mysql
  {
      var $host = 'localhost';
      var $user = 'root';
      //var $pass = '';
      var $pass = 'bitnami';
      var $database = 'aroundme';

      var $link;
      
      function conectar()
      {
          if (!$this->link = mysql_connect($this->host, $this->user, $this->pass)) 
          {
              echo 'Impossível conectar ao mysql';
              exit;
          }
      
          if (!mysql_select_db($this->database, $this->link)) 
          {
              echo 'Impossível selecionar a database';
              exit;
          }        
      }
      
      function executar_query($sql)
      {
          if (is_null($this->link))
          {
              $this->conectar();
          }
          
          $result = mysql_query($sql, $this->link);
          
          if (!$result) 
          {
              $error = mysql_error();
              
              if (stristr($error, "duplicate") == false)
              {
                  echo 'DB Error, impossível executar a query\n';
                  echo 'MySQL Error: ' . $error;
                  exit;
              }
              else
              {
                  $result = "duplicate";
              }
          }
          
          return $result; 
      }
      
      function ultimo_id()
      {
          return mysql_insert_id($this->link);
      }
  }
?>
