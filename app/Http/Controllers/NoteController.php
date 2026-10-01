<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    /**
     * Store a newly created note in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $note = Note::create([
            'message' => $request->message,
            'manager_id' => auth()->id(),
            'is_read' => false
        ]);

        return response()->json(['success' => true, 'note' => $note]);
    }

    /**
     * Mark a note as read.
     *
     * @param  \App\Models\Note  $note
     * @return \Illuminate\Http\Response
     */
    public function markAsRead(Note $note)
    {
        $note->update(['is_read' => true]);
        return response()->json(['success' => true]);
    }
} 