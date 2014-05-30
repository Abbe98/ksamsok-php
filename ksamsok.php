<?php

class KSamsok {
  public $key;
  public $url = 'http://kulturarvsdata.se/ksamsok/api?';
  public $hits;
  
  private function parse($urlquery, $type) {
    $xml = new SimpleXMLElement($urlquery);
    $result = $xml->result;
  }

  function __construct($key, $hits) {
    $this->key = $key;
    
      //http://kulturarvsdata.se/ksamsok/api?x-api=test&method=search&query=text%3D"test"
    try { 
      $testquery = $this->url . 'x-api=' . $key . '&method=search&query=text%3D"test"';
      @$xml = file_get_contents($testquery);

      if($xml === false) {
        throw new Exception('Bad API request or wrong API key. (' . $testquery . ')');
      }
    } catch(Exception $e) {
      echo 'Caught Exception: ',  $e->getMessage(), "\n";
      die();
    }
  }

  public function serach($text, $start) {
    //basic search
    // example:
    //http://kulturarvsdata.se/ksamsok/api?stylesheet=&x-api=test&method=search&hitsPerPage=50&query=text%3D"test"
  }

  public function relations($objectid) {
    //http://www.ksamsok.se/api/metoder/#getRelations
  }

  public function searchHelp($string) {
    //http://www.ksamsok.se/api/metoder/#searchHelp
  }
}
?>