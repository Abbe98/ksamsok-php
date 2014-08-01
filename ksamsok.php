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

    // get the XML
    $xml = file_get_contents($urlquery);

    // instead of using XPath to parse RDF just by pass it
    $xml = str_replace('rdf:', 'RDF_', $xml);
    $xml = str_replace('pres:', 'pres_', $xml);
    $xml = str_replace('georss:', 'georss_', $xml);
    $xml = str_replace('gml:', 'gml_', $xml);
    $xml = str_replace('geoF:', 'geoF_', $xml);
    $xml = str_replace('foaf:', 'foaf_', $xml);
    $xml = str_replace('rel:', 'rel_', $xml);
    $xml = str_replace('ns5:', 'ns5_', $xml);
    $xml = str_replace('ns6:', 'ns6_', $xml);

    $xml = new SimpleXMLElement($xml);

    // get number of total hits
    $result['hits'] = (string) $xml->totalHits;

    $i = 0;
    foreach ($xml->records->record as $record) {
      //@ignore and just leave array values empty if they don't exists

      // get current object
      @$result['result'][$i]['object'] = (string) $record->RDF_RDF->Entity->attributes()->RDF_about;

      // get service organization
      @$result['result'][$i]['org'] = (string) $record->RDF_RDF->Entity->serviceOrganization;

      // get url
      @$result['result'][$i]['url'] = (string) $record->RDF_RDF->Entity->url;

      // get subject(RDF)
      @$result['result'][$i]['subject'] = (string) $record->RDF_RDF->Entity->subject->attributes()->RDF_resource;

      // get media type
      @$result['result'][$i]['mediaType'] = (string) $record->RDF_RDF->Entity->mediaType;

      // get licence
      @$result['result'][$i]['licence'] = (string) $record->RDF_RDF->Entity->itemLicenseUrl->attributes()->RDF_resource;

      // get title
      @$result['result'][$i]['title'] = (string) $record->RDF_RDF->Entity->itemLabel;

      // get type
      @$result['result'][$i]['pres_type'] = (string) $record->RDF_RDF->Entity->presentation->pres_item->pres_type;

      // get pres id
      @$result['result'][$i]['pres_id'] = (string) $record->RDF_RDF->Entity->presentation->pres_item->pres_id;

      // get pres item label
      @$result['result'][$i]['pres_item_label'] = (string) $record->RDF_RDF->Entity->presentation->pres_item->pres_itemLabel;

      // loop through presentation tags and store them in array
      $j = 0;
      foreach ($record->RDF_RDF->Entity->presentation->pres_item->pres_tag as $pres_tag) {
        @$result['result'][$i]['pres-tags'][$j] = (string) $pres_tag;

        $j++;
      }

      // get pres location label
      @$result['result'][$i]['pres_location_label'] = (string) $record->RDF_RDF->Entity->presentation->pres_item->pres_context->pres_placeLabel;

      // get pres coordinates
      @$result['result'][$i]['pres_coordinates'] = (string) $record->RDF_RDF->Entity->presentation->pres_item->georss_where->gml_Point->gml_coordinates;

      // get pres org
      @$result['result'][$i]['pres_org'] = (string) $record->RDF_RDF->Entity->presentation->pres_item->pres_organization;

      // get pres org short
      @$result['result'][$i]['pres_org_short'] = (string) $record->RDF_RDF->Entity->presentation->pres_item->pres_organizationShort;

      // get pres data quality
      @$result['result'][$i]['pres_data_quality'] = (string) $record->RDF_RDF->Entity->presentation->pres_item->pres_dataQuality;

      // get image in diffrent quality
      @$result['result'][$i]['pres_image']['thumbnail'] = (string) $record->RDF_RDF->Entity->presentation->pres_item->pres_image->pres_src[0];
      @$result['result'][$i]['pres_image']['lowres'] = (string) $record->RDF_RDF->Entity->presentation->pres_item->pres_image->pres_src[1];
      @$result['result'][$i]['pres_image']['highres'] = (string) $record->RDF_RDF->Entity->presentation->pres_item->pres_image->pres_src[2];

      // get image "by line"
      @$result['result'][$i]['pres_image']['by_line'] = (string) $record->RDF_RDF->Entity->presentation->pres_item->pres_image->pres_byline;

      // get image copyright holder
      @$result['result'][$i]['pres_image']['copyright'] = (string) $record->RDF_RDF->Entity->presentation->pres_item->pres_image->pres_copyright;

      // get image license
      @$result['result'][$i]['pres_image']['license'] = (string) $record->RDF_RDF->Image->mediaLicense->attributes()->RDF_resource;

      // get image photographer
      @$result['result'][$i]['pres_image']['photographer'] = (string) $record->RDF_RDF->Context->foaf_name;


      $i++;
    }

    return $result;
  }

  public function relations($objectid) {
    // create the request URL
    $urlquery = $this->url . 'x-api=' . $this->key . '&method=getRelations&relation=all&objectId=' . $objectid;

    // check if URL does return a error and kill the script if it does
    $this->validxml($urlquery);

    // get the XML
    $xml = file_get_contents($urlquery);
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

  public function searchhelp($string) {
    // create the request URL
    $urlquery = $this->url . 'x-api=' . $this->key . '&method=searchHelp&index=itemMotiveWord|itemKeyWord&prefix=' . $string . '*&maxValueCount=5';
    // replace spaces in url
    $urlquery = preg_replace('/\\s/', '%20', $urlquery);
    // Force UTF-8
    $urlquery = utf8_decode($urlquery);

    // check if URL does return a error and kill the script if it does
    $this->validxml($urlquery);

    // get the XML
    $xml = file_get_contents($urlquery);
    $xml = new SimpleXMLElement($xml);

    // process the xml to array
    $i = 0;
    foreach ($xml->terms->term as $term) {
      $terms[$i]['value'] = (string) $term->value;
      $terms[$i]['count'] = (string) $term->count;
      $i++;
    }

    return $terms;
  }
}
?>