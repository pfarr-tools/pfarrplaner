<script defer>$(document).ready(function () {fetch('{!! $url !!}').then((res) => {
        return res.text();
    }).then((data) => {
        $('#{{ $randomId }}').html(data);
    });});</script>
<div id="{{ $randomId }}">Bitte warten, Daten werden geladen...</div>
