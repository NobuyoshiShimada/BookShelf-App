<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use App\Models\Genre;
use App\Policies\BookPolicy;
use App\Http\Requests\BookRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $books = Book::with('genres')->latest()->paginate(10);

        return view('books.index', compact('books'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $genres = Genre::all();

        return view('books.create', compact('genres'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BookRequest $request)
    {
        $validated = $request->validated();

        $book = Book::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'],
            'published_date' => $validated['published_date'],
            'description' => $validated['description'],
            'image_url' => $validated['image_url'],
        ]);

        $book->genres()->sync($request->genres);

        return redirect()->route('books.index')->with('success', '書籍「' . $book->title . '」を新しく登録しました。');
    }

    /**
     * Display the specified resource.
     */
    public function show(Book $book)
    {
        $book->load(['genres', 'favoritedByUsers', 'reviews.likedByUsers']);

        return view('books.show', compact('book'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Book $book)
    {
        $this->authorize('update', $book);

        $genres = Genre::all();
        $book->load('genres');

        return view('books.edit', compact('book', 'genres'));


    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BookRequest $request, Book $book)
    {
        $this->authorize('update', $book);

        return view('books.show', compact('book'));
    }



    /**
     * Remove the specified resource from storage.
     */
    public function delete(Book $book)
    {
        $this->authorize('delete', $book);

        return view('books.index', compact('book'));
    }
}
