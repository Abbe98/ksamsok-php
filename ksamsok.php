<?php

class KSamsok {
  public $key;
  public $url = 'http://kulturarvsdata.se/ksamsok/api?';
  public $hits;
  
  private function validxml($url) {
    try {
      // @ignore warning, it's handled below
      @$xml = file_get_contents($url);

      // check if file_get_contents returned a error or warning
      if($xml === false) {
        throw new Exception('Bad API request. (' . $url . ')');
      }
    } catch(Exception $e) {
      echo 'Caught Exception: ',  $e->getMessage(), "\n";
      // these are fatal errors so kill the script
      die();
    }
  }

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
      if(!$hits >= 1 && !$hits <= 500) {
        throw new Exception($hits . ' is not number between 1-500.');
      }

    } catch(Exception $e) {
      echo 'Caught Exception: ',  $e->getMessage(), "\n";
      // this is a fatal error so kill the script
      die();
    }

    // check if URL does return a error
    $testquery = $this->url . 'x-api=' . $this->key . '&method=search&query=text%3D"test"';
    $this->validxml($testquery);
  }

  public function search($text, $start) {
    // create the request URL
    $urlquery = $this->url . 'x-api=' . $this->key . '&method=search&hitsPerPage=' . $this->hits . '&startRecord=' . $start . '&query=text%3D"' . $text . '"';
    // check if URL does return a error
    $this->validxml($urlquery);
    

  }

  public function relations($objectid) {
    //http://www.ksamsok.se/api/metoder/#getRelations
  }

  public function searchHelp($string) {
    //http://www.ksamsok.se/api/metoder/#searchHelp
  }
}
?>