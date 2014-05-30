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
    $this->hits = $hits;

    try {
      // check if $hits(hitsPerPage) is valid
      // http://www.ksamsok.se/in-english/api/#search
      if(!$hits >= 1 || !$hits <= 500) {
        throw new Exception($hits . ' is not number between 1-500.');
      }

      $testquery = $this->url . 'x-api=' . $key . '&method=search&query=text%3D"test"';
      // @ignore warning, it's handled below
      @$xml = file_get_contents($testquery);

      // check if file_get_contents returned a error or warning
      if($xml === false) {
        throw new Exception('Bad API request or wrong API key. (' . $testquery . ')');
      }
    } catch(Exception $e) {
      echo 'Caught Exception: ',  $e->getMessage(), "\n";
      // these are fatal errors so kill the script
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