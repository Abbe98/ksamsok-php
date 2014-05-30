<?php

class KSamsok {
  public $key;
  public $url = 'http://kulturarvsdata.se/ksamsok/api?';
  
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
    // get xml
    $xml = new SimpleXMLElement(file_get_contents($urlquery));
    // shortcut
    $result = $xml->result;

    if($type === 'search') {
      //parse
    }

    if($type === 'relations') {
      //parse
    }

    if($type === 'searchhelp') {
      //parse
    }
  }

  function __construct($key) {
    $this->key = $key;

    // check if URL does return a error
    $testquery = $this->url . 'x-api=' . $this->key . '&method=search&query=text%3D"test"';
    $this->validxml($testquery);
  }

  public function search($text, $start, $hits) {

    try {
      // check if $hits(hitsPerPage) is valid
      // http://www.ksamsok.se/in-english/api/#search
      if($hits < 1 || $hits > 500) {
        throw new Exception($hits . ' is not number between 1-500.');
      }

    } catch(Exception $e) {
      echo 'Caught Exception: ',  $e->getMessage(), "\n";
      // this is a fatal error so kill the script
      die();
    }

    // create the request URL
    $urlquery = $this->url . 'x-api=' . $this->key . '&method=search&hitsPerPage=' . $hits . '&startRecord=' . $start . '&query=text%3D"' . $text . '"';
    // check if URL does return a error
    $this->validxml($urlquery);
    
    return $this->parse($urlquery, 'search');
  }

  public function relations($objectid) {
    // create the request URL
    $urlquery = $this->url . 'x-api=' . $this->key . '&method=getRelations&relation=all&objectId=' . $objectid;
    // check if URL does return a error
    $this->validxml($urlquery);

    return $this->parse($urlquery, 'relations');
  }

  public function searchhelp($string) {
    // create the request URL
    $urlquery = $this->url . 'x-api=' . $this->key . '&method=searchHelp&index=itemMotiveWord|itemKeyWord&prefix=' . $string . '*&maxValueCount=5';
    // check if URL does return a error
    $this->validxml($urlquery);

    return $this->parse($urlquery, 'searchhelp');
  }
}
?>