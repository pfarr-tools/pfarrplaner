<table class="panel" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="width: 100%; margin: 0 0 20px;">
    <tr>
        <td class="panel-content" style="padding: 16px; border-left: 4px solid #a61380; background-color: #f7edf5; color: #27303a;">
            {{ Illuminate\Mail\Markdown::parse($slot) }}
        </td>
    </tr>
</table>
