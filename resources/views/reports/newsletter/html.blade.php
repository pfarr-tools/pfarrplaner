@extends('reports.newsletter.layouts.container')

@section('content')
    @if($data['includeWeeklyVerse']) @component('reports.newsletter.layouts.image', ['src' => $start->format('Y-m-d')])@endcomponent @endif
    @component('reports.newsletter.layouts.h1')Unsere nächsten Veranstaltungen @endcomponent
    <hr/>
    <table border="0" cellpadding="2" cellspacing="0">
        @foreach($events as $date => $theseEvents)
            @php $firstLine = true; @endphp
            @foreach($theseEvents as $event)
                <tr>
                    <td valign="top" style="text-align: left;">@if($firstLine){!! str_replace(' ', '&nbsp;', $event->start->isoFormat('dd., DD. MMM')) !!}@php $firstLine = false;@endphp@endif</td>
                    <td valign="top" style="text-align: right;">{!!  str_replace(' ', '&nbsp;', $event->event->timeText() )!!}</td>
                    <td valign="top" style="text-align: left; font-weight: bold;">{{ $event->event->titleText(false) }}</td>
                    <td valign="top" style="text-align: left;">{{ $event->event->locationTextWithCity }}</td>
                </tr>
            @endforeach
            <tr></tr>
        @endforeach
    </table>
    @foreach(($featuredEvents ?? []) as $event)
        <hr />
        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #F1F5F7; ;;;width: 100%; table-layout: fixed; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; " class="bgcolor-1 editable" data-style="background-color:inherit" data-limit-to="background,background-color" data-name="Outer Container">
            <tbody><tr>
                <td style="font-size: 0px; ">
                    <!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" class="" role="presentation" style="" width="600" gc-width-fix=".cr-maxwidth:max-width" ><tr><td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;"><![endif]-->
                    <div class="cr-maxwidth" style="max-width: 600px; ;;;margin:0px auto;">

                        <table align="center" border="0" cellpadding="0" cellspacing="0" style="max-width: 600px; ;;;background-color: #FFFFFF; ;;;width:100%; " class="cr-maxwidth bgcolor-2 editable" data-style="background-color:inherit" data-limit-to="background,background-color" data-name="Container">
                            <tbody>
                            <tr>
                                <td style="direction:ltr;font-size:0px;text-align:center;">
                                    <!--[if mso | IE]><table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"><tr><td><![endif]-->

                                    <table cellspacing="0" cellpadding="0" width="100%"><tbody><tr><td class="" style="padding:10px 20px;" data-style="padding:inherit;color:inherit;" data-name="Image">
                                                <!--#image nodesign="true" replace="true"#--><table cellpadding="0" cellspacing="0" style="width:100%;border:0px;padding:0px;margin:0px;"><tbody><tr><td align="center" style="text-align: center; line-height: 0px;"><a target="_blank" title="" href="" style="color: #8D197C; ;;;pointer-events: none; user-select: none;"><img src="{{ $event->service->getImageCutUrl('bildschirm-16x9') }}" width="100%" style="display: inline;"></a></td></tr></tbody></table><!--#/image#-->
                                            </td></tr></tbody></table>

                                    <!--[if mso | IE]></td></tr></table><![endif]-->
                                </td>
                            </tr>
                            </tbody>
                        </table>

                    </div>
                    <!--[if mso | IE]></td></tr></table><![endif]-->

                </td>
            </tr>
            </tbody></table>
        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #F1F5F7; ;;;width: 100%; table-layout: fixed; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; " class="bgcolor-1 editable" data-style="background-color:inherit" data-limit-to="background,background-color" data-name="Outer Container">
            <tbody><tr>
                <td style="font-size: 0px; ">
                    <!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" class="" role="presentation" style="" width="600" gc-width-fix=".cr-maxwidth:max-width" ><tr><td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;"><![endif]-->
                    <div class="cr-maxwidth" style="max-width: 600px; ;;;margin:0px auto;">

                        <table align="center" border="0" cellpadding="0" cellspacing="0" style="max-width: 600px; ;;;background-color: #FFFFFF; ;;;width:100%; " class="cr-maxwidth bgcolor-2 editable" data-style="background-color:inherit" data-limit-to="background,background-color" data-name="Container">
                            <tbody>
                            <tr>
                                <td style="direction:ltr;font-size:0px;text-align:center;">
                                    <!--[if mso | IE]><table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"><tr><td><![endif]-->

                                    <table cellspacing="0" cellpadding="0" width="100%"><tbody><tr><td class="cr-text color-2" style="color: #000000; ;;;font-family: Helvetica, Arial, sans-serif; font-size: 14px; ;;;padding:10px 20px;" data-style="padding:inherit;color:inherit;" data-name="Text">
                                                <!--#html#--><p align="left"><span style="font-size: 18px;"><strong>{{ $event->service->titleText(false) }}<br>
                                                            <span style="font-size: 14px;">{{ $event->start->isoFormat('LL') }}, {{ $event->service->timeText() }}, {{ $event->service->locationTextWithCity }}</span></strong></span><br><br>{{ $event->getAdText('newsletter') }}</p><!--#/html#-->
                                            </td></tr></tbody></table>

                                    <!--[if mso | IE]></td></tr></table><![endif]-->
                                </td>
                            </tr>
                            </tbody>
                        </table>

                    </div>
                    <!--[if mso | IE]></td></tr></table><![endif]-->

                </td>
            </tr>
            </tbody></table>
    @endforeach
    <hr />
@endsection

