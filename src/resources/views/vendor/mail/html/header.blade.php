<tr>
    <td class="header" align="center" style="padding: 0 0 16px;">
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #a61380; border-radius: 6px 6px 0 0;">
            <tr>
                <td align="center" style="padding: 20px 24px; color: #ffffff; font-family: Arial, Helvetica, sans-serif; font-size: 20px; font-weight: bold;">
                    @if(isset($url))<a href="{{ $url }}" style="color: #ffffff; text-decoration: none;">@endif
                        {{ $slot }}
                    @if(isset($url))</a>@endif
                </td>
            </tr>
        </table>
    </td>
</tr>
