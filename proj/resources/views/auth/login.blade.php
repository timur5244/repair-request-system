@extends('layouts.app')

@section('title', 'Вход')

@section('content')
    <div class="card">
        <h2 style="margin:0 0 6px;">Вход</h2>
        <div class="muted" style="margin-bottom: 12px;">Для тестирования: выберите пользователя и войдите без пароля.</div>

        @if($errors->any())
            <div class="error">
                @foreach($errors->all() as $e)
                    <div>{{ $e }}</div>
                @endforeach
            </div>
        @endif

        <div class="grid" style="margin-top: 14px;">
            @foreach($users as $u)
                <form method="post" action="{{ route('login.store') }}">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ $u->id }}">
                    <button class="btn" type="submit" style="width:100%; justify-content:space-between;">
                        <span style="font-weight:600;">{{ $u->name }}</span>
                        <span class="muted">{{ $u->role }}</span>
                    </button>
                </form>
            @endforeach
        </div>
    </div>
@endsection

