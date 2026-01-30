<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageController extends Controller
{
    /**
     * Display a listing of all pages
     */
    public function index()
    {
        $pages = Page::ordered()->get();
        return view('admin.pages.index', compact('pages'));
    }

    /**
     * Show the form for creating a new page
     */
    public function create()
    {
        return view('admin.pages.create');
    }

    /**
     * Store a newly created page
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:pages,slug',
            'content' => 'required|string',
            'is_active' => 'boolean',
            'show_in_menu' => 'boolean',
            'show_in_footer' => 'boolean',
            'show_in_app' => 'boolean',
            'order' => 'nullable|integer',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ]);

        // Set defaults for checkboxes
        $validated['is_active'] = $request->has('is_active');
        $validated['show_in_menu'] = $request->has('show_in_menu');
        $validated['show_in_footer'] = $request->has('show_in_footer');
        $validated['show_in_app'] = $request->has('show_in_app');
        $validated['order'] = $validated['order'] ?? 0;

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        Page::create($validated);

        return redirect()
            ->route('admin.pages.index')
            ->with('success', 'Page created successfully.');
    }

    /**
     * Show the form for editing a page
     */
    public function edit($id)
    {
        $page = Page::findOrFail($id);
        return view('admin.pages.edit', compact('page'));
    }

    /**
     * Update the specified page
     */
    public function update(Request $request, $id)
    {
        $page = Page::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:pages,slug,' . $id,
            'content' => 'required|string',
            'is_active' => 'boolean',
            'show_in_menu' => 'boolean',
            'show_in_footer' => 'boolean',
            'show_in_app' => 'boolean',
            'order' => 'nullable|integer',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ]);

        // Set defaults for checkboxes
        $validated['is_active'] = $request->has('is_active');
        $validated['show_in_menu'] = $request->has('show_in_menu');
        $validated['show_in_footer'] = $request->has('show_in_footer');
        $validated['show_in_app'] = $request->has('show_in_app');
        $validated['order'] = $validated['order'] ?? 0;

        $page->update($validated);

        return redirect()
            ->route('admin.pages.index')
            ->with('success', 'Page updated successfully.');
    }

    /**
     * Delete the specified page
     */
    public function destroy($id)
    {
        $page = Page::findOrFail($id);
        $page->delete();

        return redirect()
            ->route('admin.pages.index')
            ->with('success', 'Page deleted successfully.');
    }

    /**
     * Toggle page status
     */
    public function toggleStatus($id)
    {
        $page = Page::findOrFail($id);
        $page->is_active = !$page->is_active;
        $page->save();

        return redirect()
            ->route('admin.pages.index')
            ->with('success', 'Page status updated successfully.');
    }
}
