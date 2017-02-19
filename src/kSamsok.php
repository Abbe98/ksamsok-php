<?php
class kSamsok {
  public $key;
  public $url;

  public function __construct($key, $url = 'http://kulturarvsdata.se/') {
    $this->key = $key;
    $this->url = $url;
    // checks if API Key or request URL is bad
    // check if URL does return a error
    $testQuery = $this->url . 'ksamsok/api?x-api=' . $this->key . '&method=search&query=text%3D"test"&recordSchema=presentation';
    @$xml = file_get_contents($testQuery);
    return ($xml === false ? false : true);
  }

  protected function killXmlNamespace($xml): string {
    $xml = str_replace('pres:', 'pres_', $xml);
    $xml = str_replace('georss:', 'georss_', $xml);
    $xml = str_replace('gml:', 'gml_', $xml);
    $xml = str_replace('geoF:', 'geoF_', $xml);
    $xml = str_replace('rel:', 'rel_', $xml);
    $xml = str_replace('xmlns:', 'xmlns_', $xml);

    return $xml;
  }

  protected function parseRecord($record): array {
    // use a shortcut $variable for presentation tags
    // if record is first xml tag parse it if, not presentation is the first
    $pres = (empty($record->pres_item) ? $pres = $record : $record->pres_item);

    // SUPER HACCCKKY HICCUP THING that has been on my "to rewrite" list for a while.
    // @ignore and just leave array values empty if they don't exists
    @$resultRecord['presentation']['version'] = (string) $pres->pres_version;
    @$resultRecord['presentation']['uri'] = (string) $pres->pres_entityUri;
    @$resultRecord['presentation']['type'] = (string) $pres->pres_type;
    @$resultRecord['presentation']['id'] = (string) $pres->pres_id;
    @$resultRecord['presentation']['id_label'] = (string) $pres->pres_idLabel;
    @$resultRecord['presentation']['item_label'] = (string) $pres->pres_itemLabel;

    // loop through presentation tags and store them in array only if at least one isset()
    if (isset($pres->pres_tag)) {
      $i = 0;
      foreach ($pres->pres_tag as $pres_tag) {
        @$resultRecord['presentation']['pres_tags'][$i] = (string) $pres_tag;
        $i++;
      }
    }

    @$resultRecord['presentation']['description'] = (string) $pres->pres_description;
    @$resultRecord['presentation']['content'] = (string) $pres->pres_content;

    // loop through presentation contexts and store child nodes only if at least one isset()
    if (isset($pres->pres_context)) {
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
    if (isset($pres->pres_image)) {
      $i = 0;
      foreach ($pres->pres_image as $image) {
        // loop through the different source resolutions(type)
        foreach ($image->pres_src as $src) {
          @$type = (string) $src->attributes()->{'type'};
          @$resultRecord['presentation']['images'][$i][$type] = (string) $src;
        }
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
    if (isset($pres->pres_references->pres_reference)) {
      $i = 0;
      foreach ($pres->pres_references->pres_reference as $reference) {
        @$resultRecord['presentation']['references'][$i] = (string) $reference;
        $i++;
      }
    }

    // sometimes the presentation model is broken so we need to use isset() for representations too
    if (isset($pres->pres_representations->pres_representation)) {
      // loop to determine representation format(they come in no specific order...)
      foreach ($pres->pres_representations->pres_representation as $representation) {
        if (strpos($representation, 'html')) {
          @$resultRecord['presentation']['representation']['html'] = (string) $representation;
        } elseif (strpos($representation, 'xml')) {
          @$resultRecord['presentation']['representation']['presentation'] = (string) $representation;
        } elseif (strpos($representation, 'rdf')) {
          @$resultRecord['presentation']['representation']['rdf'] = (string) $representation;
        }
      }
    }

    return $resultRecord;
  }

  public function uriFormat($id, $format, $validate = false): string {
    // if the entire url is present strip it off
    if (stripos($id, $this->url) !== false) {
      $id = str_replace($this->url, '', $id);
    }

    // strip off format
    $id = str_replace('xml/', '', $id);
    $id = str_replace('rdf/', '', $id);
    $id = str_replace('html/', '', $id);
    $id = str_replace('jsonld/', '', $id);

    // find spot ti insert format string
    $formatLocation = strrpos($id, '/', 0);

    if ($validate) {
      // build URL/validate using call
      $urlQuery = $this->url . substr_replace($id, '/xml', $formatLocation, 0);
      @$xml = get_file_contents($urlQuery);
      if ($xml === false) {
        return '';
      }
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
      case 'jsonld':
        return substr_replace($id, '/jsonld', $formatLocation, 0);
        break;
      case 'rdfurl':
        return $this->url . substr_replace($id, '/rdf', $formatLocation, 0);
        break;
      case 'htmlurl':
        return $this->url . substr_replace($id, '/html', $formatLocation, 0);
        break;
      case 'xmlurl':
        return $this->url . substr_replace($id, '/xml', $formatLocation, 0);
        break;
      case 'jsonldurl':
        return $this->url . substr_replace($id, '/jsonld', $formatLocation, 0);
        break;
      case 'rawurl':
        return $this->url . $id;
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
    $urlQuery = $this->url . 'ksamsok/api?x-api=' . $this->key . '&method=search&hitsPerPage=' . $hits . '&startRecord=' . $start . '&query=text%3D"' . utf8_decode($text) . '"&recordSchema=presentation';
    // if $images = true add &thumbnailExists=j to url
    if ($images) {
      $urlQuery = $urlQuery . '&thumbnailExists=j';
    }

    // get and validate the XML
    @$xml = file_get_contents($urlQuery);
    if ($xml === false) {
      return false;
    }

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
    if ($hits < 1 || $hits > 500) {
      return false;
    }

    // construct request URL
    $urlQuery = $this->url . 'ksamsok/api?x-api=' . $this->key . '&method=search&hitsPerPage=' . $hits . '&startRecord=' . $start . '&query=boundingBox=/WGS84%20"' . $west . '%20' . $south . '%20' . $east . '%20' . $north . '"&recordSchema=presentation';

    // get and validate the XML
    @$xml = file_get_contents($urlQuery);
    if ($xml === false) {
      return false;
    }

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
    $urlQuery = $this->url . $this->uriFormat($objectId, 'xml');

    // get and validate the XML
    @$xml = file_get_contents($urlQuery);
    if ($xml === false) {
      return false;
    }

    // bypass XML namespace
    $xml = $this->killXmlNamespace($xml);
    $xml = new SimpleXMLElement($xml);

    return $this->parseRecord($xml);
  }

  public function relations($objectId): array {
    // format inputed $objectId
    $objectId = $this->uriFormat($objectId, 'raw');
    // create the request URL
    $urlQuery = $this->url . 'ksamsok/api?x-api=' . $this->key . '&method=getRelations&relation=all&objectId=' . $objectId;

    // get and validate the XML
    @$xml = file_get_contents($urlQuery);
    if ($xml === false) {
      return [];
    }

    $xml = new SimpleXMLElement($xml);

    $relations = [];

    foreach ($xml->relations->relation as $relation) {
      $relationObj['link'] = (string) $relation;
      $relationObj['type'] = (string) $relation['type'];

      $relations[] = $relationObj;
    }

    return $relations;
  }

  public function searchHint($string, $count = 5): array {
    if (!is_string($string)) {
      return [];
    }

    if (!is_numeric($count) || $count < 1) {
      return [];
    }

    // create the request URL
    $urlQuery = $this->url . 'ksamsok/api?x-api=' . $this->key . '&method=searchHelp&index=itemMotiveWord|itemKeyWord&prefix=' . utf8_decode($string) . '*&maxValueCount=' . $count;

    // get and validate the XML
    @$xml = file_get_contents($urlQuery);
    if ($xml === false) {
      return [];
    }

    $xml = new SimpleXMLElement($xml);

    // process the xml to array
    $terms = [];

    foreach ($xml->terms->term as $term) {
      $termObj['value'] = (string) $term->value;
      $termObj['count'] = (string) $term->count;

      $terms[] = $termObj;
    }

    return $terms;
  }
}
