@extends('layouts.app')

@section('title', 'Список заявок')

@section('content')
    <div class="row" style="margin-bottom: 12px;">
        <a class="btn" href="{{ route('requests.create') }}">Создать заявку</a>
    </div>

    <div class="card">
        <h2 style="margin:0 0 10px;">Заявки</h2>

        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Клиент</th>
                <th>Телефон</th>
                <th>Адрес</th>
                <th>Статус</th>
                <th>Исполнитель</th>
                <th>Создано</th>
            </tr>
            </thead>
            <tbody>
            @forelse($requests as $r)
                <tr>
                    <td><a href="{{ route('requests.show', $r) }}">#{{ $r->id }}</a></td>
                    <td>{{ $r->clientName }}</td>
                    <td class="muted">{{ $r->phone }}</td>
                    <td class="muted">{{ $r->address }}</td>
                    <td>
                        @php($s = $r->status->value ?? (string)$r->status)
                        <span class="badge {{ $s === 'done' ? 'badge-done' : ($s === 'canceled' ? 'badge-canceled' : 'badge-new') }}">
                            {{ $s }}
                        </span>
                    </td>
                    <td class="muted">{{ $r->assignee?->name ?? '—' }}</td>
                    <td class="muted">{{ $r->created_at?->format('Y-m-d H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">Пока нет заявок.</td></tr>
            @endforelse
            </tbody>
        </table>

        <div style="margin-top: 12px;">
            {{ $requests->links() }}
        </div>
    </div>
@endsection

