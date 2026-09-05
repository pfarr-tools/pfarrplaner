<script defer>document.addEventListener("DOMContentLoaded", function(event) {fetch('{!! $url !!}').then((res) => {
        return res.text();
    }).then((data) => {
        document.getElementById('{{ $randomId }}').innerHTML = data;
    });});</script>
<div id="{{ $randomId }}">Bitte warten, Daten werden geladen...</div>
