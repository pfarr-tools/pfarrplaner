<script defer>document.addEventListener("DOMContentLoaded", function(event) {
        fetch('{!! $url !!}').then((res) => {
            return res.text();
        }).then((data) => {
            $('#{{ $randomId }}').html(data);
        });
    });</script>
<div id="{{ $randomId }}">Bitte warten, Daten werden geladen...</div>
