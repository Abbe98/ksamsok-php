#KSamsök-PHP
API library for the [K-Samsök API][0].

**Note that this is currently a work in progress project and that all functions is not finished.**

Things to do before "finished":
 - Finish the `point_in_polygon()` function for `geo_serach()`.
 - Replace the hacky 200 line SimpleXML parse solution with a XPath one(and remove the related "hacks")
 - Add `basic_geo_search()` because `geo_serach()` is slower.
 - Add `geo_near($point, $radius)` because it will be great to have.
 - Add some advanced search function, the existing one is so basic.
 - Add to Composer.
 - Improve usage/result examples in README.

##Usage

Set API-key(`test` works for development):

`$ksamsok = new KSamsok('test');`

Do a regular search for "Fiskmås", starting on result one returning 60 results:

`$result = $ksamsok->search('Fiskmås', 1, 60);`

Get all relations for `raa/fmi/10028201230001`:

`$ksamsok->relations('raa/fmi/10028201230001');`

Get search suggestions for the letters `kå`:

`$ksamsok->search_help('kå');`

Search for results in polygon:

**Note that this function is not fully implanted and should not be used.**

    $polygon = array('16.41958236694336,59.07164702748369',
                    '16.418466567993164,59.074624595969645',
                    '16.410484313964844,59.073764436047114',
                    '16.413745880126953,59.070786792947565',
                    '16.41958236694336,59.07164702748369');
    $ksamsok->geo_search($polygon);

The geo_serach function above visualized:

![The geo_serach function above visualized on map.](https://raw.githubusercontent.com/Abbe98/ksamsok-php/master/point_in_polygon_map.png)

[0]: http://www.ksamsok.se/in-english/