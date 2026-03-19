@extends('layouts.app')

@section('title', 'Панель мастера')

@section('content')
    <div class="card" style="margin-bottom: 12px;">
        <div class="row" style="justify-content:space-between;">
            <div>
                <h2 style="margin:0 0 6px;">Панель мастера</h2>
                <div class="muted">Показаны только заявки, назначенные на вас.</div>
            </div>
            <a class="btn" href="{{ route('requests.index') }}">Все заявки</a>
        </div>
    </div>

    <div class="grid" style="gap: 12px;">
        <div class="card">
            <h3 style="margin:0 0 10px; font-size: 16px;">Новые назначенные</h3>
            @include('master.partials.list', ['items' => $assigned, 'mode' => 'assigned'])
        </div>

        <div class="card">
            <h3 style="margin:0 0 10px; font-size: 16px;">В работе</h3>
            @include('master.partials.list', ['items' => $inProgress, 'mode' => 'in_progress'])
        </div>

        <div class="card">
            <h3 style="margin:0 0 10px; font-size: 16px;">Выполненные</h3>
            @include('master.partials.list', ['items' => $done, 'mode' => 'done'])
        </div>
    </div>

    <script>
        (function () {
            function statusClass(status) {
                if (status === 'done') return 'badge-done';
                if (status === 'canceled') return 'badge-canceled';
                return 'badge-new';
            }

            function findRow(requestId) {
                return document.getElementById('req-' + requestId);
            }

            function moveRowToList(row, targetStatus) {
                const targetTable = document.querySelector('table[data-master-list="' + targetStatus + '"]');
                if (!targetTable) return;

                const tbody = targetTable.querySelector('tbody');
                if (!tbody) return;

                // Убираем "Пусто." строку если она одна
                const emptyRow = tbody.querySelector('tr td[colspan]');
                if (emptyRow && tbody.children.length === 1) {
                    tbody.innerHTML = '';
                }

                tbody.prepend(row);
            }

            function updateRow(row, payload) {
                const status = payload.status;
                const version = payload.version;

                row.dataset.status = status;
                row.dataset.version = String(version);

                // badge
                const badge = row.querySelector('.badge');
                if (badge) {
                    badge.classList.remove('badge-new', 'badge-done', 'badge-canceled');
                    badge.classList.add(statusClass(status));
                    badge.textContent = status;
                }

                // forms/actions
                const actions = row.querySelector('td:last-child .row');
                if (actions) {
                    actions.innerHTML = '';

                    if (status === 'assigned') {
                        const form = document.createElement('form');
                        form.method = 'post';
                        form.action = '{{ url('/master/requests') }}/' + payload.id + '/take';
                        form.setAttribute('data-action', 'take');
                        form.innerHTML = `
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <input type="hidden" name="version" value="${version}">
                            <button class="btn" type="submit">Взять в работу</button>
                        `;
                        actions.appendChild(form);
                    }

                    if (status === 'in_progress') {
                        const form = document.createElement('form');
                        form.method = 'post';
                        form.action = '{{ url('/master/requests') }}/' + payload.id + '/finish';
                        form.setAttribute('data-action', 'finish');
                        form.setAttribute('data-confirm', 'Завершить заявку #' + payload.id + '?');
                        form.innerHTML = `
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <button class="btn" type="submit">Завершить</button>
                        `;
                        actions.appendChild(form);
                    }
                }

                // Если есть скрытое поле version — обновим
                const versionInput = row.querySelector('form[data-action="take"] input[name="version"]');
                if (versionInput) versionInput.value = String(version);
            }

            async function postFormJson(form) {
                const fd = new FormData(form);
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: fd,
                });

                if (res.status === 409) {
                    const msg = await res.text();
                    throw { kind: 'conflict', message: msg || 'Заявка уже взята в работу' };
                }
                if (!res.ok) {
                    const msg = await res.text();
                    throw { kind: 'error', message: msg || 'Ошибка' };
                }

                return await res.json();
            }

            document.addEventListener('submit', async (ev) => {
                const form = ev.target;
                if (!(form instanceof HTMLFormElement)) return;

                const action = form.getAttribute('data-action');
                if (!action) return;

                ev.preventDefault();

                const confirmText = form.getAttribute('data-confirm');
                if (confirmText && !confirm(confirmText)) {
                    return;
                }

                // optimistic locking: version берём из hidden input
                try {
                    const payload = await postFormJson(form);
                    const row = findRow(payload.id);
                    if (!row) {
                        window.location.reload();
                        return;
                    }

                    updateRow(row, payload);

                    if (payload.status === 'in_progress') {
                        moveRowToList(row, 'in_progress');
                    }
                    if (payload.status === 'done') {
                        moveRowToList(row, 'done');
                    }
                } catch (e) {
                    if (e && e.kind === 'conflict') {
                        alert(e.message);
                        window.location.reload();
                        return;
                    }
                    alert((e && e.message) ? e.message : 'Ошибка');
                }
            }, true);
        })();
    </script>
@endsection

