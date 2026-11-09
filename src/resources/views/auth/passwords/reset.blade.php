<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Neues Passwort festlegen</title>
</head>
<body>
    <main>
        <h1>Neues Passwort festlegen</h1>
        @if ($errors->any())
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif
        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <label for="email">E-Mailadresse</label>
            <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autofocus>
            <label for="password">Neues Passwort</label>
            <input id="password" type="password" name="password" required>
            <label for="password-confirmation">Neues Passwort wiederholen</label>
            <input id="password-confirmation" type="password" name="password_confirmation" required>
            <button type="submit">Passwort speichern</button>
        </form>
    </main>
</body>
</html>
