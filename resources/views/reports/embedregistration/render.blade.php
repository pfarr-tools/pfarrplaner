<script defer>document.addEventListener("DOMContentLoaded", function(event) {
        var url = '{{ $url }}';
        fetch(url).then((res) => {
            return res.text();
        }).then((data) => {
            $('#{{ $randomId }}').html(data);
        });
    });
</script>
<div id="{{ $randomId }}">Bitte warten, Daten werden geladen...</div>
