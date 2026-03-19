@php($items = $items ?? collect())

<table data-master-list="{{ $mode }}">
    <thead>
    <tr>
        <th>ID</th>
        <th>Клиент</th>
        <th>Телефон</th>
        <th>Адрес</th>
        <th>Статус</th>
        <th>Действия</th>
    </tr>
    </thead>
    <tbody>
    @forelse($items as $r)
        @php($s = $r->status->value ?? (string)$r->status)
        <tr id="req-{{ (int)$r->id }}" data-request-id="{{ (int)$r->id }}" data-status="{{ $s }}" data-version="{{ (int)($r->version ?? 1) }}">
            <td><a href="{{ route('requests.show', $r) }}">#{{ $r->id }}</a></td>
            <td>{{ $r->clientName }}</td>
            <td class="muted">{{ $r->phone }}</td>
            <td class="muted">{{ $r->address }}</td>
            <td>
                <span class="badge {{ $s === 'done' ? 'badge-done' : ($s === 'canceled' ? 'badge-canceled' : 'badge-new') }}">{{ $s }}</span>
            </td>
            <td>
                <div class="row">
                    @if($mode === 'assigned')
                        <form method="post"
                              action="{{ route('master.requests.take', $r) }}"
                              data-action="take">
                            @csrf
                            <input type="hidden" name="version" value="{{ (int)($r->version ?? 1) }}">
                            <button class="btn" type="submit">Взять в работу</button>
                        </form>
                    @endif

                    @if($mode === 'in_progress')
                        <form method="post"
                              action="{{ route('master.requests.finish', $r) }}"
                              data-action="finish"
                              data-confirm="Завершить заявку #{{ (int)$r->id }}?">
                            @csrf
                            <button class="btn" type="submit">Завершить</button>
                        </form>
                    @endif
                </div>
            </td>
        </tr>
    @empty
        <tr><td colspan="6" class="muted">Пусто.</td></tr>
    @endforelse
    </tbody>
</table>
