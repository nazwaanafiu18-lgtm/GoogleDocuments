<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentRevision;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function index()
    {
        $document = Document::firstOrCreate(
            ['id' => 1],
            ['title' => 'Tugas Kuliah Kolaborasi', 'content' => '<h2>Selamat datang di Editor Kolaboratif!</h2><p>Ketik sesuatu di sini bersama teman Anda secara real-time.</p>']
        );

        return redirect()->route('documents.show', $document);
    }

    public function show(Document $document)
    {
        $revisions = $document->revisions()->latest()->get();
        return view('documents.editor', compact('document', 'revisions'));
    }

    public function setName(Request $request)
    {
        $request->validate(['name' => 'required|string|max:50']);
        session()->put('user_name', $request->name);
        session()->put('name_set', true);
        return redirect()->back()->with('success', 'Nama berhasil diatur!');
    }

    public function saveRevision(Request $request, Document $document)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        $authorName = $request->input('author_name', auth()->user()?->name ?? session('user_name', 'Anonymous'));

        $document->update(['content' => $request->content]);

        $revision = $document->revisions()->create([
            'author_name' => $authorName,
            'content' => $request->content,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Revisi berhasil disimpan!',
            'revision' => $revision,
        ]);
    }

    public function getRevisions(Document $document)
    {
        return response()->json($document->revisions()->latest()->get());
    }

    public function getRevision(DocumentRevision $revision)
    {
        return response()->json($revision);
    }
}