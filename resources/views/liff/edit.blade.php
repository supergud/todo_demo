{{-- Part of todo project. --}}
@extends('liff.app')

@section('title', '編輯待辦清單')

@section('script')
    <script src="{{ secure_asset('edit_event.js') }}"></script>
@endsection

@section('content')
    <div class="container">
        <div class="form-group">
            <label for="event">待辦事項</label>
            <input type="text" class="form-control" id="event" value="{{ $event->event ?? '' }}" placeholder="請輸入待辦事項" required>
        </div>
        <div class="form-group">
            <label for="deadline">Deadline</label>
            <input type="text" class="form-control datetimepicker" id="deadline" value="{{ $event->deadline ?? '' }}" placeholder="請輸入Deadline">
        </div>
        <input type="hidden" id="event_id" value="{{ $event->id ?? '' }}"/>
        <button type="button" class="btn btn-primary" id="submit-btn">送出</button>
    </div>
@endsection
