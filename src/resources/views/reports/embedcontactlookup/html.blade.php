<script defer>document.addEventListener("DOMContentLoaded", function(event) {
        var url = '{{ $url }}';
        var parish;
        if (parish = localStorage.getItem('parish')) url = url + '&parish=' + parish;
        fetch(url).then((res) => {
            return res.text();
        }).then((data) => {
            document.getElementById('{{ $randomId }}').innerHTML = data;
        });
    });
</script>
<div id="{{ $randomId }}">Bitte warten, Daten werden geladen...</div>
