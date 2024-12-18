<script defer>document.addEventListener("DOMContentLoaded", function(event) {
        fetch('{{ $url }}').then((res) => {
            return res.text();
        }).then((data) => {
            document.getElementById('{{ $randomId }}').innerHTML = data;
        });
    });</script>
<div id="{{ $randomId }}"><img src="https://www.pfarrplaner.de/img/spinner.gif"/> Bitte warten, Daten werden geladen...
</div>
