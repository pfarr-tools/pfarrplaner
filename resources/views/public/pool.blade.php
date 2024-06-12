@extends('layouts.public')

@section('title')
    Ansprechpartner für: {{ $pool->name }}
@endsection

@section('contents')
    <div class="container py-5">
        <h1>Ansprechpartner für: {{ $pool->name }}</h1>
        <div>Vom {{ $startOfRelevantPeriod->format('d.m.Y') }} bis {{ $endOfRelevantPeriod->format('d.m.Y') }}</div>
        <hr/>
        @foreach($pool->cities->sort() as $city)
            <h2 class="pt-3">{{ $city->name }}</h2>
            <table class="table table-striped">
                <tbody>
                @foreach ($city->pastors as $user)
                    <tr>
                        <th width="25%">{{ $user->formatName(\App\Services\NameService::TITLE_FIRST_LAST) }}</th>
                        <td width="25%">
                            @if($user->office)
                                {{ $user->office }}<br/>
                            @endif
                            @if($user->phone)
                                Tel {{ $user->phone }}<br/>
                            @endif
                            @if($user->email)
                                E-Mail {{ $user->email }}
                            @endif
                        </td>
                        <td width="50%">
                            <table width="100%">
                                <tbody>
                                @foreach($absences[$user->id] ?? [] as $absence)
                                    @foreach ($absence as $replacement)
                                        <tr>
                                            <td width="50%">
                                                {{ $replacement['period'] }}
                                            </td>
                                            <td width="50%">
                                                @if ($replacement['user'])
                                                    <b>{{ $replacement['user']->formatName(\App\Services\NameService::TITLE_FIRST_LAST) }}@if($replacement['poolmaster'])
                                                            <br/>(Poolmaster:in "{{ $replacement['poolmaster'] }}")
                                                        @endif</b><br/>
                                                    @if($replacement['user']->office)
                                                        {{ $replacement['user']->office }}<br/>
                                                    @endif
                                                    @if($replacement['user']->phone)
                                                        Tel {{ $replacement['user']->phone }}<br/>
                                                    @endif
                                                    @if($replacement['user']->email)
                                                        E-Mail {{ $replacement['user']->email }}
                                                    @endif
                                                @else
                                                    Abwesend; eine Vertretung wurde bisher nicht angegeben.
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                                </tbody>
                            </table>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endforeach
    </div>
@endsection
