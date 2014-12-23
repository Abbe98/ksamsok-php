#KSamsök-PHP
API library for the [K-Samsök API][0].

##Features:

 - Make a basic search request(`string`).
 - Get relations form any object.
 - Get search suggestions.
 - Search by geographical bounding box.

##Usage

Set API-key(`test` works for development):

`$ksamsok = new KSamsok('test');`

Do a regular search for "Fiskmås", starting on result one returning 60 results:

`$ksamsok->search('Fiskmås', 1, 60);`

Optional image parameter, if you only want to return results with images:

`$ksamsok->search('Fiskmås', 1, 60, true);`

Get all relations for `raa/fmi/10028201230001`:

`$ksamsok->relations('raa/fmi/10028201230001');`

Get search suggestions for the letters `kå`:

`$ksamsok->searchHint('kå');`

Search by bounding box:
    
    $west = '16.410484313964844';
    $south = '59.070786792947565';
    $east = '16.41958236694336';
    $north = '59.074624595969645';
    $ksamsok->geoSearch($west, $south, $east, $north);


[0]: http://www.ksamsok.se/in-english/