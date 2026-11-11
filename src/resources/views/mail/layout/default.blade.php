<!DOCTYPE html>
<html lang="de" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        @media only screen and (max-width: 600px) {
            .mail-container { width: 100% !important; }
            .mail-content { padding: 24px !important; }
        }
    </style>
</head>
<body style="width: 100%; margin: 0; padding: 0; background-color: #f4f5f7; color: #27303a; font-family: Arial, Helvetica, sans-serif; -webkit-text-size-adjust: none;">
    <div style="display: none; max-height: 0; overflow: hidden;">@yield('preview')</div>
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="width: 100%; padding: 24px 0; background-color: #f4f5f7;">
        <tr>
            <td align="center">
                <table class="mail-container" width="600" cellpadding="0" cellspacing="0" role="presentation" style="width: 600px; max-width: 100%;">
                    <tr>
                        <td align="center" style="padding-bottom: 16px;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #a61380; border-radius: 6px 6px 0 0;">
                                <tr>
                                    <td align="center" style="padding: 20px 24px; color: #ffffff; font-size: 20px; font-weight: bold;">
                                        <a href="{{ config('app.url') }}" style="color: #ffffff; text-decoration: none;">{{ config('app.name', 'Pfarrplaner') }}</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="mail-content" style="padding: 32px; border: 1px solid #e2e5e9; border-radius: 6px; background-color: #ffffff; color: #27303a; font-size: 16px; line-height: 1.5;">
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding: 16px 24px 0; color: #6b7280; font-size: 12px; line-height: 1.5;">
                            © {{ date('Y') }} {{ config('app.name', 'Pfarrplaner') }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
