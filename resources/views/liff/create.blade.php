{{-- Part of todo project. --}}
@extends('liff.app')

@section('title', '新增待辦清單')

@section('script')
    <script src="{{ secure_asset('create_event.js') }}"></script>
@endsection

@section('content')
    <div class="container">
        <div class="form-group">
            <label for="event">待辦事項</label>
            <input type="text" class="form-control" id="event" placeholder="請輸入待辦事項" required>
        </div>
        <div class="form-group">
            <label for="deadline">Deadline</label>
            <input type="text" class="form-control datetimepicker" id="deadline" placeholder="請輸入Deadline">
        </div>
        <button type="button" class="btn btn-primary" id="submit-btn">送出</button>
    </div>
@endsection
