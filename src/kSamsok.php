<?php
class kSamsok {
  public $key;
  protected $url = 'http://kulturarvsdata.se/ksamsok/api?';

  public function __construct($key) {
    $this->key = $key;
    // checks if API Key or request URL is bad
    // check if URL does return a error
    $testQuery = $this->url . 'x-api=' . $this->key . '&method=search&query=text%3D"test"&recordSchema=presentation';
    if(!$this->validResponse($testQuery)) {
      // return false if response is invalid
      return false;
    }
  }

  // Checks if a valid response is returned
  protected function validResponse($url) {
    // @ignore warning, it's handled below
    @$xml = file_get_contents($url);
    // check if file_get_contents returned a error or warning
    if($xml === false) {
      return false;
    } else {
      return true;
    }
  }

  protected function prepareUrl($url) {
    // replace withe space
    $url = preg_replace('/\\s/', '%20', $url);
    // Force UTF-8
    return utf8_decode($url);
  }

  protected function killXmlNamespace($xml) {
    $xml = str_replace('pres:', 'pres_', $xml);
    $xml = str_replace('georss:', 'georss_', $xml);
    $xml = str_replace('gml:', 'gml_', $xml);
    $xml = str_replace('geoF:', 'geoF_', $xml);
    $xml = str_replace('rel:', 'rel_', $xml);
    $xml = str_replace('xmlns:', 'xmlns_', $xml);

    return $xml;
  }

  protected function parseRecord($record) {
    // use a shortcut $variable for presentation tags
    // if record is first xml tag parse it if not presentation is the first
    if (!empty($record->pres_item)) {
      $pres = $record->pres_item;
    } else {
      $pres = $record;
    }

    // @ignore and just leave array values empty if they don't exists
    @$resultRecord['presentation']['version'] = (string) $pres->pres_version;
    @$resultRecord['presentation']['uri'] = (string) $pres->pres_entityUri;
    @$resultRecord['presentation']['type'] = (string) $pres->pres_type;
    @$resultRecord['presentation']['id'] = (string) $pres->pres_id;
    @$resultRecord['presentation']['id_label'] = (string) $pres->pres_idLabel;
    @$resultRecord['presentation']['item_label'] = (string) $pres->pres_itemLabel;

    // loop through presentation tags and store them in array only if at least one isset()
    if (isset($pres->pres_tag) === true) {
      $i = 0;
      foreach ($pres->pres_tag as $pres_tag) {
        @$resultRecord['presentation']['pres_tags'][$i] = (string) $pres_tag;
        $i++;
      }
    }

    @$resultRecord['presentation']['description'] = (string) $pres->pres_description;
    @$resultRecord['presentation']['content'] = (string) $pres->pres_content;

    // loop through presentation contexts and store child nodes only if at least one isset()
    if (isset($pres->pres_context) === true) {
      $i = 0;
      foreach ($pres->pres_context as $pres_context) {
        @$resultRecord['presentation']['contexts'][$i]['event'] = (string) $pres_context->pres_event;
        @$resultRecord['presentation']['contexts'][$i]['place_label'] = (string) $pres_context->pres_placeLabel;
        @$resultRecord['presentation']['contexts'][$i]['time_label'] = (string) $pres_context->pres_timeLabel;
        @$resultRecord['presentation']['contexts'][$i]['name_label'] = (string) $pres_context->pres_nameLabel;
        $i++;
      }
    }

    @$resultRecord['presentation']['coordinates'] = (string) $pres->georss_where->gml_Point->gml_coordinates;

    // loop for all the images and their child nodes only if at least one isset()
    if (isset($pres->pres_image) === true) {
      $i = 0;
      foreach ($pres->pres_image as $image) {
        @$resultRecord['presentation']['images'][$i]['thumbnail'] = (string) $image->pres_src[0];
        @$resultRecord['presentation']['images'][$i]['lowres'] = (string) $image->pres_src[1];
        @$resultRecord['presentation']['images'][$i]['highres'] = (string) $image->pres_src[2];
        @$resultRecord['presentation']['images'][$i]['by_line'] = (string) $image->pres_byline;
        @$resultRecord['presentation']['images'][$i]['motive'] = (string) $image->pres_motive;
        @$resultRecord['presentation']['images'][$i]['copyright'] = (string) $image->pres_copyright;
        @$resultRecord['presentation']['images'][$i]['license'] = (string) $image->pres_mediaLicense;
        @$resultRecord['presentation']['images'][$i]['license_url'] = (string) $image->pres_mediaLicenseUrl;
         @$resultRecord['presentation']['images'][$i]['uri'] = (string) $image->pres_mediaUri;
        @$resultRecord['presentation']['images'][$i]['html_url'] = (string) $image->pres_mediaUrl;
        $i++;
      }
    }

    // loop to get all references only if at least one isset()
    if(isset($pres->pres_references->pres_reference) == true) {
      $i = 0;
      foreach ($pres->pres_references->pres_reference as $reference) {
        @$resultRecord['presentation']['references'][$i] = (string) $reference;
        $i++;
      }
    }

    // sometimes the presentation model is broken so we need to use isset() for representations too
    if (isset($pres->pres_representations->pres_representation) === true) {
      // loop to determine representation format(they come in no specific order...)
      foreach ($pres->pres_representations->pres_representation as $representation) {
        if(strpos($representation, 'html')) {
          @$resultRecord['presentation']['representation']['html'] = (string) $representation;
        } elseif(strpos($representation, 'xml')) {
          @$resultRecord['presentation']['representation']['presentation'] = (string) $representation;
        } elseif(strpos($representation, 'rdf')) {
          @$resultRecord['presentation']['representation']['rdf'] = (string) $representation;
        }
      }
    }

    return $resultRecord;
  }

  public function idFormat($id, $format = 'raw') {
    // if the entire url is present strip it off
    if (stripos($id, 'http://kulturarvsdata.se/') !== false) {
      $id = str_replace('http://kulturarvsdata.se/', '', $id);
    }

    // strip off format
    $id = str_replace('xml/', '', $id);
    $id = str_replace('rdf/', '', $id);
    $id = str_replace('html/', '', $id);

    // find spot ti insert format string
    $formatLocation = strrpos($id, '/', 0);

    // build URL/validate using call
    $urlQuery = 'http://kulturarvsdata.se/' . substr_replace($id, '/xml', $formatLocation, 0);
    if(!$this->validResponse($urlQuery)) {
      return false;
    }

    switch ($format) {
      case 'xml':
        return substr_replace($id, '/xml', $formatLocation, 0);
        break;
      case 'rdf':
        return substr_replace($id, '/rdf', $formatLocation, 0);
        break;
      case 'html':
        return substr_replace($id, '/html', $formatLocation, 0);
        break;
      case 'rdfurl':
        return 'http://kulturarvsdata.se/' . substr_replace($id, '/rdf', $formatLocation, 0);
        break;
      case 'htmlurl':
        return 'http://kulturarvsdata.se/' . substr_replace($id, '/html', $formatLocation, 0);
        break;
      case 'xmlurl':
        return 'http://kulturarvsdata.se/' . substr_replace($id, '/xml', $formatLocation, 0);
        break;
      case 'rawurl':
        return 'http://kulturarvsdata.se/' . $id;
        break;
      // defaults to 'raw'
      default:
        return $id;
        break;
    }
  }

  public function search($text, $start, $hits, $images = false) {
    // check if $text isn't a string
    if (!is_string($text)) {
      return false;
    }

    if (!is_numeric($start) || $start < 1) {
      return false;
    }

    // check if $hits(hitsPerPage) is valid(1-500)
    if ($hits < 1 || $hits > 500) {
      return false;
    }

    // create the request URL
    $urlQuery = $this->url . 'x-api=' . $this->key . '&method=search&hitsPerPage=' . $hits . '&startRecord=' . $start . '&query=text%3D"' . $text . '"&recordSchema=presentation';
    // if $images = true add &thumbnailExists=j to url
    if ($images) {
      $urlQuery = $urlQuery . '&thumbnailExists=j';
    }
    // prepare url
    $urlQuery = $this->prepareUrl($urlQuery);
    // check if URL does return a error and return false if it does
    if(!$this->validResponse($urlQuery)) {
      return false;
    }
    // get the XML
    $xml = file_get_contents($urlQuery);
    // bypass XML namespace
    $xml = $this->killXmlNamespace($xml);
    $xml = new SimpleXMLElement($xml);

    // get number of total hits
    $result['hits'] = (string) $xml->totalHits;

    // parse each record and push to $result array
    foreach ($xml->records->record as $record) {
      $result[] = $this->parseRecord($record);
    }

    return $result;
  }

  public function geoSearch($west, $south, $east, $north, $start, $hits = 60) {
    if (!is_numeric($start) || $start < 1) {
      return false;
    }

    // check if $hits(hitsPerPage) is valid(1-500)
    if($hits < 1 || $hits > 500) {
      return false;
    }

    // construct request URL
    $urlQuery = $this->url . 'x-api=' . $this->key . '&method=search&hitsPerPage=' . $hits . '&startRecord=' . $start . '&query=boundingBox=/WGS84%20"' . $west . '%20' . $south . '%20' . $east . '%20' . $north . '"&recordSchema=presentation';
        // check if URL does return a error and return false if it does
    if(!$this->validResponse($urlQuery)) {
      return false;
    }
    // get the XML
    $xml = file_get_contents($urlQuery);
    // bypass XML namespace
    $xml = $this->killXmlNamespace($xml);
    $xml = new SimpleXMLElement($xml);

    $result['hits'] = (string) $xml->totalHits;

    // parse each record and push to $result array
    foreach ($xml->records->record as $record) {
      $result[] = $this->parseRecord($record);
    }

    return $result;
  }

  public function object($objectId) {
    // format inputed $objectId
    $urlQuery = $this->idFormat($objectId, 'xmlurl');
    // check if URL does return a error and return false if it does
    if(!$this->validResponse($urlQuery)) {
      return false;
    }
    // get the XML
    $xml = file_get_contents($urlQuery);
    // bypass XML namespace
    $xml = $this->killXmlNamespace($xml);
    $xml = new SimpleXMLElement($xml);

    return $this->parseRecord($xml);
  }

  public function relations($objectId) {
    // format inputed $objectId
    $objectId = $this->idFormat($objectId);
    // create the request URL
    $urlQuery = $this->url . 'x-api=' . $this->key . '&method=getRelations&relation=all&objectId=' . $objectId;
    // check if URL does return a error and return false if it does
    if(!$this->validResponse($urlQuery)) {
      return false;
    }
    // get the XML
    $xml = file_get_contents($urlQuery);
    $xml = new SimpleXMLElement($xml);

    // get number of relations
    $relations['count'] = (string) $xml->relations['count'];

    // foreach loop for all relations
    $i = 0;
    foreach ($xml->relations->relation as $relation) {
      // get the innerXML
      $relations[$i]['link'] = (string) $relation;
      // get the type attribute
      $relations[$i]['type'] = (string) $relation['type'];
      $i++;
    }

    return $relations;
  }

  public function searchHint($string, $count = 5) {
    if (!is_string($string)) {
      return false;
    }

    if (!is_numeric($count) || $count < 1) {
      return false;
    }

    // create the request URL
    $urlQuery = $this->url . 'x-api=' . $this->key . '&method=searchHelp&index=itemMotiveWord|itemKeyWord&prefix=' . $string . '*&maxValueCount=' . $count;
    // prepare url
    $urlQuery = $this->prepareUrl($urlQuery);
    // check if URL does return a error and return false if it does
    if(!$this->validResponse($urlQuery)) {
      return false;
    }
    // get the XML
    $xml = file_get_contents($urlQuery);
    $xml = new SimpleXMLElement($xml);

    // process the xml to array
    $result['count'] = (string) $xml->numberOfTerms;

    $i = 0;
    foreach ($xml->terms->term as $term) {
      $terms[$i]['value'] = (string) $term->value;
      $terms[$i]['count'] = (string) $term->count;
      $i++;
    }

      // push $terms to $result
    if (isset($terms)) {
      $result['hints'] = $terms;
    }

    return $result;
  }
}