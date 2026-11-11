<!DOCTYPE html>
<html lang="de" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <style>
        @media only screen and (max-width: 600px) {
            .inner-body, .footer { width: 100% !important; }
            .content-cell { padding: 24px !important; }
        }
        @media only screen and (max-width: 500px) {
            .button { width: 100% !important; }
        }
    </style>
</head>
<body style="width: 100%; margin: 0; padding: 0; background-color: #f4f5f7; color: #27303a; font-family: Arial, Helvetica, sans-serif; -webkit-text-size-adjust: none;">
<table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="width: 100%; margin: 0; padding: 24px 0; background-color: #f4f5f7;">
    <tr>
        <td align="center">
            <table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="width: 100%; max-width: 600px;">
                {{ $header ?? '' }}
                <tr>
                    <td class="body" width="100%" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border: 1px solid #e2e5e9; border-radius: 6px;">
                        <table class="inner-body" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation" style="width: 570px; max-width: 100%;">
                            <tr>
                                <td class="content-cell" style="padding: 32px; color: #27303a; font-family: Arial, Helvetica, sans-serif; font-size: 16px; line-height: 1.5;">
                                    {{ Illuminate\Mail\Markdown::parse($slot) }}
                                    {{ $subcopy ?? '' }}
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                {{ $footer ?? '' }}
            </table>
        </td>
    </tr>
</table>
</body>
</html>
