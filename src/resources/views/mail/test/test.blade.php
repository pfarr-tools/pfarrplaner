@extends('mail.layout.default')

@section('preview')
    Testnachricht vom Pfarrplaner
@endsection

@section('content')
    <h1>Testnachricht zum Überprüfen der Mailfunktion</h1>
    <p>Mit dieser Nachricht soll überprüft werden, ob Sie E-Mails vom Pfarrplaner empfangen können.</p>
    <p>Vermutlich erhalten Sie diese Nachricht, weil ein Sie Problem mit dem Mailempfang gemeldet haben.</p>
    <p>Bitte antworten Sie kurz auf diese Nachricht, damit wir wissen, dass Sie E-Mails vom Pfarrplaner empfangen können.
    </p>
    <p>Wenn Sie nicht sicher sind, warum Sie diese Nachricht erhalten haben, kontaktieren Sie den Entwickler:
        <br />
        Pfarrer Christoph Fischer<br />
        Buchenstr. 29<br />
        71126 Gäufelden<br />
        <br />
        Telefon 07032 75567<br />
        E-Mail (privat)<a href="mailto:chris@toph.de"> chris@toph.de</a>
    </p>
@endsection
