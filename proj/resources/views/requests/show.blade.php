@extends('layouts.app')

@section('title', 'Заявка #'.$requestModel->id)

@section('content')
    <div class="row" style="margin-bottom: 12px;">
        <a class="btn" href="{{ route('requests.index') }}">← К списку</a>
        <a class="btn" href="{{ route('requests.create') }}">Создать ещё</a>
    </div>

    <div class="card">
        <div class="row" style="justify-content:space-between;">
            <h2 style="margin:0;">Заявка #{{ $requestModel->id }}</h2>
            @php($s = $requestModel->status->value ?? (string)$requestModel->status)
            <span class="badge {{ $s === 'done' ? 'badge-done' : ($s === 'canceled' ? 'badge-canceled' : 'badge-new') }}">{{ $s }}</span>
        </div>

        <div class="grid grid-2" style="margin-top: 12px;">
            <div>
                <div class="muted" style="font-size:12px;">Клиент</div>
                <div style="font-size:16px; font-weight:600;">{{ $requestModel->clientName }}</div>
            </div>
            <div>
                <div class="muted" style="font-size:12px;">Телефон</div>
                <div style="font-size:16px; font-weight:600;">{{ $requestModel->phone }}</div>
            </div>
            <div>
                <div class="muted" style="font-size:12px;">Адрес</div>
                <div>{{ $requestModel->address }}</div>
            </div>
            <div>
                <div class="muted" style="font-size:12px;">Исполнитель</div>
                <div>{{ $requestModel->assignee?->name ?? '— не назначен —' }}</div>
            </div>
        </div>

        <div style="margin-top: 14px;">
            <div class="muted" style="font-size:12px;">Описание проблемы</div>
            <div style="white-space:pre-wrap;">{{ $requestModel->problemText }}</div>
        </div>

        <hr style="border:none; border-top:1px solid rgba(255,255,255,.08); margin: 16px 0;">

        <div class="grid grid-2">
            <div class="card" style="background: rgba(6,9,20,.45);">
                <h3 style="margin:0 0 10px; font-size: 16px;">Назначить мастера</h3>
                <form method="post" action="{{ route('requests.assign', $requestModel) }}">
                    @csrf
                    <label for="assignedTo">Мастер</label>
                    <select id="assignedTo" name="assignedTo">
                        <option value="">— снять назначение —</option>
                        @foreach($masters as $m)
                            <option value="{{ $m->id }}" @selected($requestModel->assignedTo === $m->id)>{{ $m->name }}</option>
                        @endforeach
                    </select>
                    <div class="row" style="margin-top: 12px;">
                        <button class="btn" type="submit">Сохранить назначение</button>
                    </div>
                </form>
            </div>

            <div class="card" style="background: rgba(6,9,20,.45);">
                <h3 style="margin:0 0 10px; font-size: 16px;">Сменить статус</h3>
                <form method="post" action="{{ route('requests.status', $requestModel) }}">
                    @csrf
                    <label for="status">Статус</label>
                    <select id="status" name="status">
                        @foreach($statuses as $st)
                            <option value="{{ $st->value }}" @selected($s === $st->value)>{{ $st->value }}</option>
                        @endforeach
                    </select>
                    <div class="row" style="margin-top: 12px;">
                        <button class="btn" type="submit">Сохранить статус</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="muted" style="margin-top: 14px; font-size: 12px;">
            Создано: {{ $requestModel->created_at?->format('Y-m-d H:i') }},
            обновлено: {{ $requestModel->updated_at?->format('Y-m-d H:i') }}
        </div>
    </div>
@endsection

