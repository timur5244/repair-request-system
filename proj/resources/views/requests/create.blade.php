@extends('layouts.app')

@section('title', 'Создать заявку')

@section('content')
    <div class="card">
        <div class="row" style="justify-content:space-between;">
            <h2 style="margin:0;">Новая заявка</h2>
            <a class="btn" href="{{ route('requests.index') }}">← К списку</a>
        </div>

        <form method="post" action="{{ route('requests.store') }}">
            @csrf

            <div class="grid grid-2">
                <div>
                    <label for="clientName">ФИО клиента</label>
                    <input id="clientName" name="clientName" value="{{ old('clientName') }}" required>
                </div>
                <div>
                    <label for="phone">Телефон</label>
                    <input id="phone" name="phone" value="{{ old('phone') }}" required>
                </div>
            </div>

            <label for="address">Адрес</label>
            <input id="address" name="address" value="{{ old('address') }}" required>

            <label for="problemText">Описание проблемы</label>
            <textarea id="problemText" name="problemText" required>{{ old('problemText') }}</textarea>

            @if($errors->any())
                <div class="error">
                    @foreach($errors->all() as $e)
                        <div>{{ $e }}</div>
                    @endforeach
                </div>
            @endif

            <div class="row" style="margin-top: 14px;">
                <button class="btn" type="submit">Создать</button>
            </div>
        </form>
    </div>
@endsection

