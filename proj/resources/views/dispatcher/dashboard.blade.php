@extends('layouts.app')

@section('title', 'Панель диспетчера')

@section('content')
    <div class="card" style="margin-bottom: 12px;">
        <div class="row" style="justify-content:space-between;">
            <div>
                <h2 style="margin:0 0 6px;">Панель диспетчера</h2>
                <div class="muted">Все заявки, фильтры и операции назначения/отмены.</div>
            </div>
            <a class="btn" href="{{ route('requests.create') }}">Создать заявку</a>
        </div>

        <form method="get" action="{{ route('dispatcher.dashboard') }}" class="grid grid-2" style="margin-top: 12px;">
            <div>
                <label for="status">Статус</label>
                <select id="status" name="status">
                    <option value="">— любой —</option>
                    @foreach($statuses as $st)
                        <option value="{{ $st->value }}" @selected(($filters['status'] ?? '') === $st->value)>{{ $st->value }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="master_id">Мастер</label>
                <select id="master_id" name="master_id">
                    <option value="">— любой —</option>
                    @foreach($masters as $m)
                        <option value="{{ $m->id }}" @selected(($filters['master_id'] ?? '') == (string)$m->id)>{{ $m->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="row" style="margin-top: 10px;">
                <button class="btn" type="submit">Применить</button>
                <a class="btn" href="{{ route('dispatcher.dashboard') }}">Сбросить</a>
            </div>
        </form>

        @if($errors->any())
            <div class="error" style="margin-top: 12px;">
                @foreach($errors->all() as $e)
                    <div>{{ $e }}</div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="card">
        <h3 style="margin:0 0 10px; font-size: 16px;">Заявки</h3>

        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Клиент</th>
                <th>Телефон</th>
                <th>Адрес</th>
                <th>Статус</th>
                <th>Назначенный мастер</th>
                <th>Действия</th>
            </tr>
            </thead>
            <tbody>
            @forelse($requests as $r)
                @php($s = $r->status->value ?? (string)$r->status)
                <tr>
                    <td><a href="{{ route('requests.show', $r) }}">#{{ $r->id }}</a></td>
                    <td>{{ $r->clientName }}</td>
                    <td class="muted">{{ $r->phone }}</td>
                    <td class="muted">{{ $r->address }}</td>
                    <td>
                        <span class="badge {{ $s === 'done' ? 'badge-done' : ($s === 'canceled' ? 'badge-canceled' : 'badge-new') }}">{{ $s }}</span>
                    </td>
                    <td class="muted">{{ $r->assignee?->name ?? '—' }}</td>
                    <td>
                        <div class="row">
                            @if($s === 'new')
                                <button
                                    type="button"
                                    class="btn"
                                    data-open-assign
                                    data-request-id="{{ $r->id }}"
                                    data-request-title="#{{ $r->id }} — {{ $r->clientName }}"
                                >
                                    Назначить мастера
                                </button>
                            @endif

                            @if(in_array($s, ['new','assigned','in_progress'], true))
                                <form method="post"
                                      action="{{ route('dispatcher.requests.cancel', $r) }}"
                                      onsubmit="return confirm('Отменить заявку #{{ $r->id }}?')">
                                    @csrf
                                    <button class="btn btn-danger" type="submit">Отменить</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">Нет заявок по выбранным фильтрам.</td></tr>
            @endforelse
            </tbody>
        </table>

        <div style="margin-top: 12px;">
            {{ $requests->links() }}
        </div>
    </div>

    {{-- Modal: Assign master --}}
    <div id="assignModal"
         style="display:none; position: fixed; inset: 0; background: rgba(0,0,0,.55); padding: 24px; z-index: 50;">
        <div class="card" style="max-width: 560px; margin: 10vh auto; position: relative;">
            <div class="row" style="justify-content:space-between;">
                <div>
                    <h3 style="margin:0 0 6px; font-size: 16px;">Назначить мастера</h3>
                    <div class="muted" id="assignTitle"></div>
                </div>
                <button type="button" class="btn btn-danger" id="assignClose">Закрыть</button>
            </div>

            <form method="post" id="assignForm" style="margin-top: 12px;">
                @csrf
                <label for="assign_master_id">Мастер</label>
                <select id="assign_master_id" name="master_id" required>
                    <option value="">— выбрать —</option>
                    @foreach($masters as $m)
                        <option value="{{ $m->id }}">{{ $m->name }}</option>
                    @endforeach
                </select>

                <div class="row" style="margin-top: 12px;">
                    <button class="btn" type="submit">Назначить</button>
                    <button type="button" class="btn" id="assignCancel">Отмена</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('assignModal');
            const titleEl = document.getElementById('assignTitle');
            const form = document.getElementById('assignForm');
            const closeBtn = document.getElementById('assignClose');
            const cancelBtn = document.getElementById('assignCancel');

            function openModal(requestId, requestTitle) {
                titleEl.textContent = requestTitle || ('#' + requestId);
                form.action = '{{ url('/dispatcher/requests') }}/' + requestId + '/assign';
                modal.style.display = 'block';
            }

            function closeModal() {
                modal.style.display = 'none';
            }

            document.querySelectorAll('[data-open-assign]').forEach(btn => {
                btn.addEventListener('click', () => {
                    openModal(btn.dataset.requestId, btn.dataset.requestTitle);
                });
            });

            closeBtn.addEventListener('click', closeModal);
            cancelBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });
        })();
    </script>
@endsection

