<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;

class LiffController extends Controller
{
    public function create()
    {
        return view('liff.create');
    }

    public function edit(Request $request)
    {
        $event_id = $request->input('event_id');

        $event = Event::query()->where('id', $event_id)->first();

        return view('liff.edit', compact('event'));
    }
}
