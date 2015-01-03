<?php
class kSamsok {
  public $key;
  public $url = 'http://kulturarvsdata.se/ksamsok/api?';

  public function __construct($key) {
    $this->key = $key;
    // checks if API Key or request URL is bad(can also )
    // check if URL does return a error
    $testQuery = $this->url . 'x-api=' . $this->key . '&method=search&query=text%3D"test"&recordSchema=presentation';
    $this->validXml($testQuery);
  }

  // Checks if valid xml is returned, if not throw Exception and kill the script
  protected function validXml($url) {
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

  protected function idFormat($id, $format = 'raw') {
    // $format can be string 'xml' or string 'raw'

    // if is the entire url strip it off
    if (stripos($id, 'http://kulturarvsdata.se/') !== false) {
      $id = str_replace('http://kulturarvsdata.se/', '', $id);
    }

    if ($format === 'raw') {
      // if nor '/xml/' / '/rdf/' / '/html/' is found in $id
      if (strpos($id,'/xml/') === false && strpos($id,'/rdf/') === false && strpos($id,'/html/') === false) {
        return $id;
      // if format exists in $id remove it
      } elseif (strpos($id,'/rdf/') !== false) {
        return str_replace('/rdf/', '', $id);
      } elseif (strpos($id,'/html/') !== false) {
        return str_replace('/html/', '', $id);
      } elseif (strpos($id,'/xml/') !== false) {
        return str_replace('/xml/', '', $id);
      }
    }

    if ($format === 'xml') {
      // if string contains '/xml/'
      if (strpos($id,'/xml/') !== false) {
        return $id;
      // replace '/html/' / '/rdf/' if found
      } elseif (strpos($id,'/rdf/') !== false) {
        return str_replace('/rdf/', '/xml/', $id);
      } elseif (strpos($id,'/html/') !== false) {
        return str_replace('/html/', '/xml/', $id);
      } else {
        // if no format exists add '/xml/'
        $formatLocation = strrpos($id, '/', 0);
        return substr_replace($id, '/xml', $formatLocation, 0);
      }
    }
  }

  public function search($text, $start, $hits, $images = false) {
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
    $urlQuery = $this->url . 'x-api=' . $this->key . '&method=search&hitsPerPage=' . $hits . '&startRecord=' . $start . '&query=text%3D"' . $text . '"&recordSchema=presentation';
    // if $images = true add &thumbnailExists=j to url
    if ($images) {
      $urlQuery = $urlQuery . '&thumbnailExists=j';
    }
    // replace spaces in url
    $urlQuery = preg_replace('/\\s/', '%20', $urlQuery);
    // Force UTF-8
    $urlQuery = utf8_decode($urlQuery);
    // check if URL does return a error and kill the script if it does
    $this->validXml($urlQuery);
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

  public function geoSearch($west, $south, $east, $north) {
    // construct request URL
    $urlQuery = $this->url . 'x-api=' . $this->key . '&method=search&query=boundingBox=/WGS84%20"' . $west . '%20' . $south . '%20' . $east . '%20' . $north . '"&recordSchema=presentation';
    // check if URL does return a error and kill the script if it does
    $this->validXml($urlQuery);
    // get the XML
    $xml = file_get_contents($urlQuery);
    // bypass XML namespace
    $xml = $this->killXmlNamespace($xml);
    $xml = new SimpleXMLElement($xml);

    // parse each record and push to $result array
    foreach ($xml->records->record as $record) {
      $result[] = $this->parseRecord($record);
    }

    return $result;
  }

  public function object($objectId) {
    // format inputed $objectId
    $objectId = $this->idFormat($objectId, 'xml');
    // create the request url(for object API key isn't required).
    $urlQuery = 'http://kulturarvsdata.se/' . $objectId;
    // check if URL does return a error and kill the script if it does
    $this->validXml($urlQuery);
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
    // check if URL does return a error and kill the script if it does
    $this->validXml($urlQuery);
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

  public function searchHint($string, $count = '5') {
    // create the request URL
    $urlQuery = $this->url . 'x-api=' . $this->key . '&method=searchHelp&index=itemMotiveWord|itemKeyWord&prefix=' . $string . '*&maxValueCount=' . $count;
    // replace spaces in url
    $urlQuery = preg_replace('/\\s/', '%20', $urlQuery);
    // Force UTF-8
    $urlQuery = utf8_decode($urlQuery);
    // check if URL does return a error and kill the script if it does
    $this->validXml($urlQuery);
    // get the XML
    $xml = file_get_contents($urlQuery);
    $xml = new SimpleXMLElement($xml);

    // process the xml to array
    $i = 0;
    foreach ($xml->terms->term as $term) {
      $terms[$i]['value'] = (string) $term->value;
      $terms[$i]['count'] = (string) $term->count;
      $i++;
    }

    // check if any results exists
    if (isset($terms)) {
      return $terms;
    } else {
      return false;
    }
  }
}