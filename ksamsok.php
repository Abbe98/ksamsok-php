<?php
class KSamsok {
  public $key;
  public $url = 'http://kulturarvsdata.se/ksamsok/api?';

  public function __construct($key) {
    $this->key = $key;

    // checks if API Key or request URL is bad(can also )
    // check if URL does return a error
    $testquery = $this->url . 'x-api=' . $this->key . '&method=search&query=text%3D"test"';
    $this->validxml($testquery);
  }

  // Checks if valid xml is returned, if not throw Exception and kill the script
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

  public function search($text, $start, $hits) {
    try {
      // check if $hits(hitsPerPage) is valid(1-500)
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
    // replace spaces in url
    $urlquery = preg_replace('/\\s/', '%20', $urlquery);
    // Force UTF-8
    $urlquery = utf8_decode($urlquery);

    // check if URL does return a error and kill the script if it does
    $this->validxml($urlquery);
    
    $result = array();

    // get the XML
    $xml = file_get_contents($urlquery);
    # write parsing stuff

    //return $result;
  }

  public function relations($objectid) {
    // create the request URL
    $urlquery = $this->url . 'x-api=' . $this->key . '&method=getRelations&relation=all&objectId=' . $objectid;

    // check if URL does return a error and kill the script if it does
    $this->validxml($urlquery);

    $relations = array();
    // get the XML
    $xml = file_get_contents($urlquery);
    $xml = new SimpleXMLElement($xml);

    // process the xml to array
    // get number of relations
    $relations['count'] = $xml->relations['count'];

    // foreach loop for all relations
    $i = 0;
    foreach ($xml->relations->relation as $relation) {
      // get the innerXML
      $relations[$i]['link'] = (string) $relation;
      // get the type attribute
      $relations[$i]['type'] = $relation['type'];

      $i++;
    }

    return $relations;
  }

  public function searchhelp($string) {
    // create the request URL
    $urlquery = $this->url . 'x-api=' . $this->key . '&method=searchHelp&index=itemMotiveWord|itemKeyWord&prefix=' . $string . '*&maxValueCount=5';
    // replace spaces in url
    $urlquery = preg_replace('/\\s/', '%20', $urlquery);
    // Force UTF-8
    $urlquery = utf8_decode($urlquery);

    // check if URL does return a error and kill the script if it does
    $this->validxml($urlquery);

    $terms = array();
    // get the XML
    $xml = file_get_contents($urlquery);
    $xml = new SimpleXMLElement($xml);

    // process the xml to array
    $i = 0;
    foreach ($xml->terms->term as $term) {
      $terms[$i]['value'] = $term->value;
      $terms[$i]['count'] = $term->count;
      $i++;
    }

    return $terms;
  }
}
?>