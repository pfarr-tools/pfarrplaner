<!DOCTYPE html>
<html lang="de" translate="no">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="notranslate" name="google">
    <meta content="{{ csrf_token() }}" name="csrf-token">
    <title>Was ist der Pfarrplaner?</title>

    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.1/css/all.css"
          integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('adminlte/css/adminlte.min.css') }}">
    <link href="{{ asset('css/pfarrplaner.css') }}" rel="stylesheet">
    <link href="{{ mix('/css/app.css') }}" rel="stylesheet">

    <link rel="apple-touch-icon" sizes="180x180" href="/img/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/img/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/img/favicons/favicon-16x16.png">
    <link rel="manifest" href="/img/favicons/site.webmanifest">
    <link rel="mask-icon" href="/img/favicons/safari-pinned-tab.svg" color="#5bbad5">
    <link rel="shortcut icon" href="/img/favicons/favicon.ico">
    <meta name="msapplication-TileColor" content="#da532c">
    <meta name="msapplication-config" content="/img/favicons/browserconfig.xml">
    <meta name="theme-color" content="#ffffff">

    <meta http-equiv="refresh" content="600">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>

    <style>
        body {
            background: linear-gradient(180deg, #f4f6f9 0%, #eef2f7 100%);
        }

        .pp-public-navbar {
            border-bottom: 1px solid rgba(0, 0, 0, .08);
        }

        .pp-public-navbar .brand-link {
            display: inline-flex;
            align-items: center;
            gap: .75rem;
            color: #212529;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
        }

        .pp-public-navbar .brand-link img {
            height: 32px;
            width: auto;
        }

        .pp-hero {
            padding: 4rem 0 2rem 0;
        }

        .pp-hero-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 0;
            border-radius: 1rem;
            box-shadow: 0 1rem 3rem rgba(31, 45, 61, .08);
            overflow: hidden;
        }

        .pp-badge {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .35rem .75rem;
            border-radius: 999px;
            background: rgba(0, 123, 255, .08);
            color: #0056b3;
            font-size: .85rem;
            font-weight: 600;
        }

        .pp-lead {
            font-size: 1.05rem;
            color: #5c6773;
        }

        .pp-stats {
            margin-bottom: 3rem;
        }

        .pp-stat-card {
            height: 100%;
            padding: 1.5rem 1.5rem 1.35rem 1.5rem;
            border-radius: 1rem;
            background: #fff;
            box-shadow: 0 .75rem 1.5rem rgba(31, 45, 61, .06);
        }

        .pp-stat-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .pp-stat-label {
            color: #6c757d;
            font-size: .95rem;
            line-height: 1.45;
            margin: 0;
        }

        .pp-stat-value {
            font-size: 2rem;
            line-height: 1.05;
            font-weight: 700;
            margin: 0;
            color: #212529;
            word-break: break-word;
        }

        .pp-stat-icon {
            width: 3rem;
            height: 3rem;
            flex: 0 0 3rem;
            border-radius: .85rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 123, 255, .1);
            color: #007bff;
            font-size: 1.2rem;
        }

        .pp-stat-note {
            color: #8a94a0;
            font-size: .85rem;
            margin: 0;
        }

        .pp-section-title {
            margin-bottom: 1.25rem;
        }

        .pp-section-title p {
            color: #6c757d;
            margin-bottom: 0;
        }

        .pp-feature-card,
        .pp-shot-card,
        .pp-contact-card,
        .pp-form-card {
            border: 0;
            border-radius: 1rem;
            box-shadow: 0 .75rem 2rem rgba(31, 45, 61, .06);
        }

        .pp-feature-icon {
            width: 3rem;
            height: 3rem;
            border-radius: .85rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            margin-bottom: 1rem;
        }

        .pp-feature-icon.blue { background: rgba(0, 123, 255, .12); color: #007bff; }
        .pp-feature-icon.green { background: rgba(40, 167, 69, .12); color: #28a745; }
        .pp-feature-icon.orange { background: rgba(255, 133, 27, .12); color: #ff851b; }
        .pp-feature-icon.red { background: rgba(220, 53, 69, .12); color: #dc3545; }

        .pp-shot-card img,
        .pp-hero-card img {
            width: 100%;
            height: auto;
            display: block;
        }

        .pp-shot-caption {
            color: #6c757d;
            font-size: .95rem;
        }

        .pp-contact-list p:last-child {
            margin-bottom: 0;
        }

        .pp-footer {
            color: #6c757d;
            font-size: .95rem;
        }
    </style>
</head>
<body class="hold-transition layout-top-nav">
<div class="wrapper">
    <nav class="main-header navbar navbar-expand-md navbar-white navbar-light pp-public-navbar">
        <div class="container">
            <a href="/" class="brand-link">
                <img src="/img/logo/pfarrplaner.svg" alt="Pfarrplaner">
                <span>Pfarrplaner</span>
            </a>

            <button class="navbar-toggler order-1" type="button" data-toggle="collapse" data-target="#pp-public-nav"
                    aria-controls="pp-public-nav" aria-expanded="false" aria-label="Navigation öffnen">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse order-3" id="pp-public-nav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item"><a class="nav-link" href="#funktionen">Funktionen</a></li>
                    <li class="nav-item"><a class="nav-link" href="#einblicke">Einblicke</a></li>
                    <li class="nav-item"><a class="nav-link" href="#kontakt">Kontakt</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="content-wrapper" style="background: transparent;">
        <div class="content">
            <div class="container">
                <section class="pp-hero">
                    <div class="card pp-hero-card">
                        <div class="card-body p-4 p-lg-5">
                            <div class="row align-items-center">
                                <div class="col-lg-6">
                                    <div class="pp-badge mb-3">
                                        <i class="fas fa-church"></i>
                                        Digitale Planung für Kirchengemeinden
                                    </div>
                                    <h1 class="display-4 font-weight-bold">Was ist der Pfarrplaner?</h1>
                                    <p class="pp-lead mt-3">
                                        Der Pfarrplaner bündelt die tägliche Arbeit rund um Gottesdienste,
                                        Kasualien, Liturgien und Zusammenarbeit in einer Oberfläche, die sich an
                                        echten Abläufen in Gemeindebüros und Pfarrämtern orientiert.
                                    </p>
                                    <p class="mb-4">
                                        Statt vieler Listen, Kalender und Einzelabsprachen gibt es einen zentralen
                                        Ort für Termine, Zuständigkeiten, Dateien, Kommunikation und Ausgaben.
                                    </p>
                                    <div class="d-flex flex-wrap" style="gap: .75rem;">
                                        <a href="#kontakt" class="btn btn-primary btn-lg">
                                            <i class="fas fa-envelope me-1"></i>Kontakt aufnehmen
                                        </a>
                                        <a href="https://handbuch.pfarrplaner.de" target="_blank" class="btn btn-outline-primary btn-lg">
                                            <i class="fas fa-book-open me-1"></i>Zum Handbuch
                                        </a>
                                        <a href="https://codeberg.org/pfarr.tools/pfarrplaner" target="_blank" class="btn btn-outline-secondary btn-lg">
                                            <i class="fas fa-code-branch me-1"></i>Quellcode auf Codeberg
                                        </a>
                                    </div>
                                </div>
                                <div class="col-lg-6 mt-4 mt-lg-0">
                                    <img src="/img/manual/startseite-uebersicht.png" alt="Startseite des Pfarrplaners" class="img-fluid rounded shadow-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="pp-stats">
                    <div class="row">
                        <div class="col-md-6 col-xl-3">
                            <div class="pp-stat-card">
                                <div class="pp-stat-head">
                                    <div>
                                        <p class="pp-stat-label">Kirchengemeinden</p>
                                        <h2 class="pp-stat-value">{{ $count['cities'] }}</h2>
                                    </div>
                                    <div class="pp-stat-icon"><i class="fas fa-church"></i></div>
                                </div>
                                <p class="pp-stat-note">Aktiv im System angelegte Gemeinden.</p>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <div class="pp-stat-card">
                                <div class="pp-stat-head">
                                    <div>
                                        <p class="pp-stat-label">Benutzer</p>
                                        <h2 class="pp-stat-value">{{ $count['users'] }}</h2>
                                    </div>
                                    <div class="pp-stat-icon"><i class="fas fa-users"></i></div>
                                </div>
                                <p class="pp-stat-note">Personen mit Zugang zum Pfarrplaner.</p>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <div class="pp-stat-card">
                                <div class="pp-stat-head">
                                    <div>
                                        <p class="pp-stat-label">Gottesdienste</p>
                                        <h2 class="pp-stat-value">{{ $count['services'] }}</h2>
                                    </div>
                                    <div class="pp-stat-icon"><i class="fas fa-calendar-alt"></i></div>
                                </div>
                                <p class="pp-stat-note">Verwaltete Termine und Planungen.</p>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <div class="pp-stat-card">
                                <div class="pp-stat-head">
                                    <div>
                                        <p class="pp-stat-label">Aktuelle Version</p>
                                        <h2 class="pp-stat-value">{{ $version }}</h2>
                                    </div>
                                    <div class="pp-stat-icon"><i class="fas fa-code"></i></div>
                                </div>
                                <p class="pp-stat-note">Öffentlich verfügbare Projektversion.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="funktionen" class="mb-5">
                    <div class="pp-section-title">
                        <h2 class="h3 font-weight-bold mb-2">Wofür ist der Pfarrplaner gedacht?</h2>
                        <p>Die Anwendung unterstützt die wichtigsten Arbeitsbereiche im Alltag einer Kirchengemeinde.</p>
                    </div>

                    <div class="row">
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="card pp-feature-card h-100">
                                <div class="card-body">
                                    <div class="pp-feature-icon blue"><i class="fas fa-calendar-check"></i></div>
                                    <h3 class="h5 font-weight-bold">Gottesdienste planen</h3>
                                    <p class="mb-0">Termine, Orte, Beteiligte, Kommentare, Dateien und öffentliche Ausgaben bleiben zusammen an einem Ort.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="card pp-feature-card h-100">
                                <div class="card-body">
                                    <div class="pp-feature-icon green"><i class="fas fa-cross"></i></div>
                                    <h3 class="h5 font-weight-bold">Kasualien verwalten</h3>
                                    <p class="mb-0">Taufen, Trauungen und Beerdigungen lassen sich vom ersten Kontakt bis zur Durchführung übersichtlich begleiten.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="card pp-feature-card h-100">
                                <div class="card-body">
                                    <div class="pp-feature-icon orange"><i class="fas fa-list-ol"></i></div>
                                    <h3 class="h5 font-weight-bold">Liturgien vorbereiten</h3>
                                    <p class="mb-0">Abläufe, Texte und Ausgaben für Gottesdienste entstehen direkt aus der Planung heraus und bleiben aktuell.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="card pp-feature-card h-100">
                                <div class="card-body">
                                    <div class="pp-feature-icon red"><i class="fas fa-share-alt"></i></div>
                                    <h3 class="h5 font-weight-bold">Zusammenarbeit erleichtern</h3>
                                    <p class="mb-0">Vertretungen, Dienstanfragen, Freigabelinks und Kalenderanbindungen helfen bei der Arbeit im Team.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="einblicke" class="mb-5">
                    <div class="pp-section-title">
                        <h2 class="h3 font-weight-bold mb-2">Einblicke in die Anwendung</h2>
                        <p>Die Screenshots stammen aus dem Pfarrplaner-Handbuch und zeigen typische Arbeitsbereiche.</p>
                    </div>

                    <div class="row">
                        <div class="col-lg-6 mb-4">
                            <div class="card pp-shot-card h-100">
                                <img src="/img/manual/kalender-uebersicht.png" alt="Kalenderübersicht">
                                <div class="card-body">
                                    <h3 class="h5 font-weight-bold">Kalender und Terminübersicht</h3>
                                    <p class="pp-shot-caption mb-0">Die Kalenderansicht zeigt Gottesdienste und Veranstaltungen übersichtlich nach Datum und Gemeinde.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 mb-4">
                            <div class="card pp-shot-card h-100">
                                <img src="/img/manual/liturgie-editor.png" alt="Liturgie-Editor">
                                <div class="card-body">
                                    <h3 class="h5 font-weight-bold">Liturgie-Editor</h3>
                                    <p class="pp-shot-caption mb-0">Abläufe, Texte und Bausteine für den Gottesdienst lassen sich direkt im Editor zusammenstellen.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 mb-4">
                            <div class="card pp-shot-card h-100">
                                <img src="/img/manual/kasualien-uebersicht.png" alt="Kasualien-Übersicht">
                                <div class="card-body">
                                    <h3 class="h5 font-weight-bold">Kasualien im Blick</h3>
                                    <p class="pp-shot-caption mb-0">Alle anstehenden Taufen, Trauungen und Beerdigungen sind in einer gemeinsamen Übersicht erreichbar.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 mb-4">
                            <div class="card pp-shot-card h-100">
                                <img src="/img/manual/startseite-uebersicht.png" alt="Startseite">
                                <div class="card-body">
                                    <h3 class="h5 font-weight-bold">Startseite mit Aufgabenfokus</h3>
                                    <p class="pp-shot-caption mb-0">Die Startseite bündelt anstehende Termine, offene Punkte und wichtige Bereiche für den schnellen Einstieg.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="mb-5">
                    <div class="row">
                        <div class="col-lg-6 mb-4 mb-lg-0">
                            <div class="card pp-contact-card h-100">
                                <div class="card-header border-0 bg-white pt-4 px-4">
                                    <h2 class="h4 font-weight-bold mb-0">Aus der Praxis, offen entwickelt</h2>
                                </div>
                                <div class="card-body px-4">
                                    <p>
                                        Der Pfarrplaner wird im echten Gemeindekontext weiterentwickelt. Rückmeldungen
                                        aus dem Alltag fließen direkt in neue Funktionen und Verbesserungen ein.
                                    </p>
                                    <p>
                                        Der vollständige Quellcode ist als Open Source auf
                                        <a href="https://codeberg.org/pfarr.tools/pfarrplaner" target="_blank">Codeberg</a>
                                        verfügbar.
                                    </p>
                                    <p>
                                        Eine ausführliche Einführung in alle Bereiche der Anwendung steht im
                                        <a href="https://handbuch.pfarrplaner.de" target="_blank">Pfarrplaner-Handbuch</a>.
                                    </p>
                                    <ul class="mb-0 pl-3">
                                        <li>Kurze Wege zwischen Rückmeldung und Verbesserung</li>
                                        <li>Transparente Entwicklung</li>
                                        <li>Nachvollziehbare Weiterentwicklung des Projekts</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card pp-contact-card h-100">
                                <div class="card-header border-0 bg-white pt-4 px-4">
                                    <h2 class="h4 font-weight-bold mb-0">Sicher und alltagstauglich</h2>
                                </div>
                                <div class="card-body px-4">
                                    <p>Pfarrplaner ist für den Umgang mit sensiblen Gemeindedaten und die Zusammenarbeit im Team ausgelegt.</p>
                                    <ul class="mb-0 pl-3">
                                        <li>Rechte und Zugriffe lassen sich gezielt steuern</li>
                                        <li>Dateien, Hinweise und Zuständigkeiten bleiben beim jeweiligen Termin</li>
                                        <li>Öffentliche Links können gezielt für externe Beteiligte genutzt werden</li>
                                        <li>Kalender- und Ausgabeformate unterstützen die tägliche Praxis</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="kontakt" class="pb-5">
                    <div class="pp-section-title">
                        <h2 class="h3 font-weight-bold mb-2">Kontakt</h2>
                        <p>Fragen, Rückmeldungen oder Interesse an einer Nutzung? Eine Nachricht genügt.</p>
                    </div>

                    <div class="row">
                        <div class="col-lg-4 mb-4 mb-lg-0">
                            <div class="card pp-contact-card h-100">
                                <div class="card-body pp-contact-list">
                                    <h3 class="h5 font-weight-bold">Ansprechpartner</h3>
                                    <p class="mb-3">Pfarrer Christoph Fischer</p>
                                    <p><i class="fas fa-map-marker-alt text-primary me-1"></i>Buchenstr. 29<br>71126 Gäufelden</p>
                                    <p><i class="fas fa-envelope text-primary me-1"></i><a href="mailto:christoph.fischer@elkw.de">christoph.fischer@elkw.de</a></p>
                                    <p><i class="fas fa-phone text-primary me-1"></i><a href="tel:+49703275567">07032 75567</a></p>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8">
                            <div class="card pp-form-card">
                                <div class="card-body">
                                    <form action="/kontaktformular" method="post" role="form" id="contactForm">
                                        @csrf
                                        <div class="row">
                                            <div class="col-md-6 form-group">
                                                <label for="name">Name</label>
                                                <input type="text" name="name" class="form-control" id="name" placeholder="Dein Name" required>
                                            </div>
                                            <div class="col-md-6 form-group">
                                                <label for="email">E-Mail</label>
                                                <input type="email" class="form-control" name="email" id="email" placeholder="Deine E-Mailadresse" required>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="subject">Betreff</label>
                                            <input type="text" class="form-control" name="subject" id="subject" placeholder="Betreff" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="message">Nachricht</label>
                                            <textarea class="form-control" name="message" id="message" rows="5" placeholder="Nachricht" required></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <div class="g-recaptcha" data-sitekey="{{ $recaptchaKey }}"></div>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane me-1"></i>Nachricht senden
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="text-center pb-4 pp-footer">
                    <p class="mb-0">Pfarrplaner, Version {{ $version }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('adminlte/plugins/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
