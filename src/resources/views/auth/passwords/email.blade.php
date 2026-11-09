<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Passwort zurücksetzen</title>
</head>
<body>
    <main>
        <h1>Passwort zurücksetzen</h1>
        @if (session('status'))
            <p>{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif
        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <label for="email">E-Mailadresse</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
            <button type="submit">Link zum Zurücksetzen senden</button>
        </form>
    </main>
</body>
</html>
