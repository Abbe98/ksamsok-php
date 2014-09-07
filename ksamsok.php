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

  private function parse_record($record) {
    // @ignore and just leave array values empty if they don't exists

    // wrapp it in a try() block so we can throw Exceptions
    try {
      // parse Entity content if Entity exists
      if (isset($record->RDF_RDF->Entity)) {

        // use a shortcut $variable for presentation tags
        $pres = $record->RDF_RDF->Entity->presentation->pres_item;

        @$result_record['presentation']['version'] = (string) $pres->pres_version;
        @$result_record['presentation']['uri'] = (string) $pres->pres_entityUri;
        @$result_record['presentation']['type'] = (string) $pres->pres_type;
        @$result_record['presentation']['id'] = (string) $pres->pres_id;
        @$result_record['presentation']['id_label'] = (string) $pres->pres_idLabel;
        @$result_record['presentation']['item_label'] = (string) $pres->pres_itemLabel;

        // loop through presentation tags and store them in array only if at least one isset()
        if (isset($pres->pres_tag) === true) {
          $i = 0;
          foreach ($pres->pres_tag as $pres_tag) {
            @$result_record['presentation']['pres_tags'][$i] = (string) $pres_tag;
            $i++;
          }
        }

        @$result_record['presentation']['description'] = (string) $pres->pres_description;
        @$result_record['presentation']['content'] = (string) $pres->pres_content;

        // loop through presentation contexts and store child nodes only if at least one isset()
        if (isset($pres->pres_context) === true) {
          $i = 0;
          foreach ($pres->pres_context as $pres_context) {
            @$result_record['presentation']['contexts'][$i]['event'] = (string) $pres_context->pres_event;
            @$result_record['presentation']['contexts'][$i]['place_label'] = (string) $pres_context->pres_placeLabel;
            @$result_record['presentation']['contexts'][$i]['time_label'] = (string) $pres_context->pres_timeLabel;
            @$result_record['presentation']['contexts'][$i]['name_label'] = (string) $pres_context->pres_nameLabel;
            $i++;
          }
        }

        @$result_record['presentation']['coordinates'] = (string) $pres->georss_where->gml_Point->gml_coordinates;

        // loop for all the images and their child nodes only if at least one isset()
        if (isset($pres->pres_image) === true) {
          $i = 0;
          foreach ($pres->pres_image as $image) {
            @$result_record['presentation']['images'][$i]['thumbnail'] = (string) $image->pres_src[0];
            @$result_record['presentation']['images'][$i]['lowres'] = (string) $image->pres_src[1];
            @$result_record['presentation']['images'][$i]['highres'] = (string) $image->pres_src[2];
            @$result_record['presentation']['images'][$i]['by_line'] = (string) $image->pres_byline;
            @$result_record['presentation']['images'][$i]['motive'] = (string) $image->pres_motive;
            @$result_record['presentation']['images'][$i]['copyright'] = (string) $image->pres_copyright;
            @$result_record['presentation']['images'][$i]['license'] = (string) $image->pres_mediaLicense;
            @$result_record['presentation']['images'][$i]['license_url'] = (string) $image->pres_mediaLicenseUrl;
            @$result_record['presentation']['images'][$i]['uri'] = (string) $image->pres_mediaUri;
            @$result_record['presentation']['images'][$i]['html_url'] = (string) $image->pres_mediaUrl;
            $i++;
          }
        }

        // loop to get all references only if at least one isset()
        if(isset($pres->pres_references->pres_reference) == true) {
          $i = 0;
          foreach ($pres->pres_references->pres_reference as $reference) {
            @$result_record['presentation']['references'][$i] = (string) $reference;
            $i++;
          }
        }

        // sometimes the presentation model is broken so we need to use isset() for representations too
        if (isset($pres->pres_representations->pres_representation) === true) {
          // loop to determine representation format(they come in no specific order...)
          foreach ($pres->pres_representations->pres_representation as $representation) {
            if(strpos($representation, 'html')) {
              @$result_record['presentation']['representation']['html'] = (string) $representation;
            } elseif(strpos($representation, 'xml')) {
              @$result_record['presentation']['representation']['presentation'] = (string) $representation;
            } elseif(strpos($representation, 'rdf')) {
              @$result_record['presentation']['representation']['rdf'] = (string) $representation;
            }
          }
        }

        // Parse rdf_Description content only if no Entity node exists
      } elseif (isset($record->RDF_RDF->RDF_Description)) {

        // use a shortcut $variable for presentation tags
        $pres = $record->RDF_RDF->RDF_Description->ns5_presentation->pres_item;

        @$result_record['presentation']['version'] = (string) $pres->pres_version;
        @$result_record['presentation']['uri'] = (string) $pres->pres_entityUri;
        @$result_record['presentation']['type'] = (string) $pres->pres_type;
        @$result_record['presentation']['id'] = (string) $pres->pres_id;
        @$result_record['presentation']['id_label'] = (string) $pres->pres_idLabel;
        @$result_record['presentation']['item_label'] = (string) $pres->pres_itemLabel;

        // loop through presentation tags and store them in array only if at least one isset()
        if (isset($pres->pres_tag) === true) {
          $i = 0;
          foreach ($pres->pres_tag as $pres_tag) {
            @$result_record['presentation']['pres_tags'][$i] = (string) $pres_tag;
            $i++;
          }
        }

        @$result_record['presentation']['description'] = (string) $pres->pres_description;
        @$result_record['presentation']['content'] = (string) $pres->pres_content;

        // loop through presentation contexts and store child nodes only if at least one isset()
        if (isset($pres->pres_context) === true) {
          $i = 0;
          foreach ($pres->pres_context as $pres_context) {
            @$result_record['presentation']['contexts'][$i]['event'] = (string) $pres_context->pres_event;
            @$result_record['presentation']['contexts'][$i]['place_label'] = (string) $pres_context->pres_placeLabel;
            @$result_record['presentation']['contexts'][$i]['time_label'] = (string) $pres_context->pres_timeLabel;
            @$result_record['presentation']['contexts'][$i]['name_label'] = (string) $pres_context->pres_nameLabel;
            $i++;
          }
        }

        @$result_record['presentation']['coordinates'] = (string) $pres->georss_where->gml_Point->gml_coordinates;

        // loop for all the images and their child nodes only if at least one isset()
        if (isset($pres->pres_image) === true) {
          $i = 0;
          foreach ($pres->pres_image as $image) {
            @$result_record['presentation']['images'][$i]['thumbnail'] = (string) $image->pres_src[0];
            @$result_record['presentation']['images'][$i]['lowres'] = (string) $image->pres_src[1];
            @$result_record['presentation']['images'][$i]['highres'] = (string) $image->pres_src[2];
            @$result_record['presentation']['images'][$i]['by_line'] = (string) $image->pres_byline;
            @$result_record['presentation']['images'][$i]['motive'] = (string) $image->pres_motive;
            @$result_record['presentation']['images'][$i]['copyright'] = (string) $image->pres_copyright;
            @$result_record['presentation']['images'][$i]['license'] = (string) $image->pres_mediaLicense;
            @$result_record['presentation']['images'][$i]['license_url'] = (string) $image->pres_mediaLicenseUrl;
            @$result_record['presentation']['images'][$i]['uri'] = (string) $image->pres_mediaUri;
            @$result_record['presentation']['images'][$i]['html_url'] = (string) $image->pres_mediaUrl;
            $i++;
          }
        }

        // loop to get all references only if at least one isset()
        if(isset($pres->pres_references->pres_reference) == true) {
          $i = 0;
          foreach ($pres->pres_references->pres_reference as $reference) {
            @$result_record['presentation']['references'][$i] = (string) $reference;
            $i++;
          }
        }

        // sometimes the presentation model is broken so we need to use isset() for representations too
        if (isset($pres->pres_representations->pres_representation) === true) {
          // loop to determine representation format(they come in no specific order...)
          foreach ($pres->pres_representations->pres_representation as $representation) {
            if(strpos($representation, 'html')) {
              @$result_record['presentation']['representation']['html'] = (string) $representation;
            } elseif(strpos($representation, 'xml')) {
              @$result_record['presentation']['representation']['presentation'] = (string) $representation;
            } elseif(strpos($representation, 'rdf')) {
              @$result_record['presentation']['representation']['rdf'] = (string) $representation;
            }
          }
        }

      } else {
        // If both Entity and rdf_Description does not exist throw a fatal error
        throw new Exception('Unknown RDF format.');
      }
    } catch(Exception $e) {
      echo 'Caught Exception: ',  $e->getMessage(), "\n";
      // fatal error so die
      die();
    }

    return $result_record;
  }

  private function point_in_polygon($point, $polygon) {
    #TODO: finish this function
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

    // parse each record and puch to $result array
    foreach ($xml->records->record as $record) {
      $result[] = $this->parse_record($record);
    }

    return $result;
  }

  public function geo_search($coordinates) {
    
    // expolde() each coordinate to get lang/lat separately
    foreach ($coordinates as $coordinate) {
      $longlat_array[] = explode(',', $coordinate);
    }

    // push lat/long to separately arrays($longlat_array was a polygon) for future calculation
    $i = 0;
    foreach ($longlat_array as $longlat) {
      $lat[] = $longlat[1];
      $long[] = $longlat[0];
      $i++;
    }

    // get search area borders for K-Samsök
    $west = min($long);
    $south = min($lat);
    $east = max($long);
    $north = max($lat);

    // construct request URL
    $urlquery = $this->url . 'x-api=' . $this->key . '&method=search&query=boundingBox=/WGS84%20"' . $west . '%20' . $south . '%20' . $east . '%20' . $north . '"';

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

    // parse each record and puch to $result array
    foreach ($xml->records->record as $record) {
      $result[] = $this->parse_record($record);
    }

    // loop throug all $record coordinates
    foreach ($result as $record) {
      // if point_in_polygon() returns true add to $new_result[], if not do nothing
      if($this->point_in_polygon($record['presentation']['coordinates'], $longlat_array)) {
        $new_result[] = $record;
      }
    }

    return $new_result;
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