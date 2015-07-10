<?php
require __DIR__ . '/../vendor/autoload.php';

class kSamsokTest extends PHPUnit_Framework_TestCase {
  private $key = 'test';

  // provides a template for the search test
  public function providerSearch() {
    return array(
      array('fiskmås', 1, 60, false, true),
      array('vattentorn', 50, 50, true, true),
      array('glass', 4000, 2, true, true),
      array('a', 2, 2, false, true),
      array('noresult ever super star what ever', 1, 60, false, true),

      array('hello', 3, 501, false, false),
      array('hello', 3, 0, false, false),
      array('hello', -3, 3, false, false),
      array('hello', 3, -3, true, false),
      array('hello', 3.2, 3, false, false),
      array('hello', 3, 3.2, false, false),
      array(2, 3, 3, false, false),
      array(2.2, 3, 4, false, false),
      array('hello', 3, 'hello', false, false),
      array('hello', 'hello', 7, false, false),
    );
  }

  // provides a template for geoSearch() data
  public function providerGeo() {
    return array(
      array('16.410484313964844', '59.070786792947565', '16.41958236694336', '59.074624595969645', 1, 60, true),
      array('16.410484313964844', '59.070786792947565', '16.41958236694336', '59.074624595969645', 1, 500, 60, true),
      array('16.4', '59.0', '16.4', '59.0', 1777, 1, true),
      array(16.410484313964844, '59.070786792947565', '16.41958236694336', '59.074624595969645', 1, 60, true),
      array('16.410484313964844', 59.070786792947565, '16.41958236694336', '59.074624595969645', 1, 60, true),
      array('16.410484313964844', '59.070786792947565', 16.41958236694336, '59.074624595969645', 1, 60, true),
      array('16.410484313964844', '59.070786792947565', '16.41958236694336', 59.074624595969645, 1, 60, true),

      array('16.410484313964844', '59.070786792947565', '16.41958236694336', '59.074624595969645', 0, 60, false),
      array('16.410484313964844', '59.070786792947565', '16.41958236694336', '59.074624595969645', 0.3, 60, false),
      array('16.410484313964844', '59.070786792947565', '16.41958236694336', '59.074624595969645', -2, 60, false),
      array('16.410484313964844', '59.070786792947565', '16.41958236694336', '59.074624595969645', 0, 501, false),
      array('16.410484313964844', '59.070786792947565', '16.41958236694336', '59.074624595969645', 0, 0, false),
      array('16.410484313964844', '59.070786792947565', '16.41958236694336', '59.074624595969645', 0, 2.5, false),
      array('16.410484313964844', '59.070786792947565', '16.41958236694336', '59.074624595969645', 0, -2, false),
    );
  }

  // provides a template for URIs to be tested
  public function providerId() {
    return array(
      array('raa/fmi/10028201230001', true), // raw URI
      array('shm/site/18797', true), // raw URI
      array('raa/kmb/xml/16000300020896', true), // XML URI
      array('raa/kmb/rdf/16000300020896', true), // RDF URI
      array('raa/kmb/html/16000300020896', true), // HTML URI
      array('http://kulturarvsdata.se/raa/kmb/16000300020896', true), // raw URL
      array('http://kulturarvsdata.se/raa/kmb/xml/16000300020896', true), // XML URL
      array('http://kulturarvsdata.se/raa/kmb/rdf/16000300020896', true), // RDF URL
      array('http://kulturarvsdata.se/raa/kmb/html/16000300020896', true), // HTML URL

      array('kulturarvsdata.se/raa/kmb/16000300020896', false), // raw URL missing http://
      array('rdf/16000300020896', false), // RDF URI missing provider
      array('http://kulturarvsdata.se/raa/kmb/', false), // raw URL missing id number
      array('raa/kmb/xml/', false), // XML URI missing id number
    );
  }

/**
 * @dataProvider providerSearch
 */
  public function testsearch($string, $start, $results, $image, $validate) {
    $kSamsok = new kSamsok($this->key);
    $result = $kSamsok->search($string, $start, $results, $image);

    // all URIs that should pass
    if ($validate) {
      $this->assertArrayHasKey('hits', $result);
    }

    // all URIs that shouldn't pass
    if (!$validate) {
      $this->assertFalse($result);
    }
  }

/**
 * @dataProvider providerGeo
 */
  public function testgeoSearch($coord1, $coord2, $coord3, $coord4, $start, $results, $validate) {
    $kSamsok = new kSamsok($this->key);
    $result = $kSamsok->geoSearch($coord1, $coord2, $coord3, $coord4, $start, $results);

    // all URIs that should pass
    if ($validate) {
      $this->assertArrayHasKey('hits', $result);
    }

    // all URIs that shouldn't pass
    if (!$validate) {
      $this->assertFalse($result);
    }
  }

/**
 * @dataProvider providerId
 */
  public function testobject($uri, $validate) {
    $kSamsok = new kSamsok($this->key);
    $result = $kSamsok->object($uri);

    // all URIs that should pass
    if ($validate) {
      $this->assertArrayHasKey('presentation', $result);
    }

    // all URIs that shouldn't pass
    if (!$validate) {
      $this->assertFalse($result);
    }
  }

  public function testrelations() {

  }

  public function testsearchHint() {

  }
}
