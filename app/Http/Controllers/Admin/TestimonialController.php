<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TestimonialController extends Controller
{
    /**
     * Display a listing of testimonials
     */
    public function index()
    {
        $testimonials = Testimonial::ordered()->paginate(20);
        return view('admin.testimonials.index', compact('testimonials'));
    }

    /**
     * Store a newly created testimonial
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_name' => 'required|string|max:255',
            'user_photo' => 'nullable|image|max:2048',
            'rating' => 'required|integer|min:1|max:5',
            'message' => 'required|string|max:500',
            'is_active' => 'boolean',
            'order' => 'nullable|integer',
        ]);

        if ($request->hasFile('user_photo')) {
            $path = $request->file('user_photo')->store('testimonials', 'public');
            $validated['user_photo'] = asset('storage/' . $path);
        }

        $validated['is_active'] = $request->has('is_active');
        $validated['order'] = $validated['order'] ?? 0;

        Testimonial::create($validated);

        return redirect()->route('admin.testimonials.index')
            ->with('success', 'Testimonial added successfully!');
    }

    /**
     * Update the specified testimonial
     */
    public function update(Request $request, Testimonial $testimonial)
    {
        $validated = $request->validate([
            'user_name' => 'required|string|max:255',
            'user_photo' => 'nullable|image|max:2048',
            'rating' => 'required|integer|min:1|max:5',
            'message' => 'required|string|max:500',
            'is_active' => 'boolean',
            'order' => 'nullable|integer',
        ]);

        if ($request->hasFile('user_photo')) {
            // Delete old photo
            if ($testimonial->user_photo) {
                $oldPath = str_replace(asset('storage/'), '', $testimonial->user_photo);
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('user_photo')->store('testimonials', 'public');
            $validated['user_photo'] = asset('storage/' . $path);
        }

        $validated['is_active'] = $request->has('is_active');
        $validated['order'] = $validated['order'] ?? 0;

        $testimonial->update($validated);

        return redirect()->route('admin.testimonials.index')
            ->with('success', 'Testimonial updated successfully!');
    }

    /**
     * Remove the specified testimonial
     */
    public function destroy(Testimonial $testimonial)
    {
        // Delete photo if exists
        if ($testimonial->user_photo) {
            $path = str_replace(asset('storage/'), '', $testimonial->user_photo);
            Storage::disk('public')->delete($path);
        }

        $testimonial->delete();

        return redirect()->route('admin.testimonials.index')
            ->with('success', 'Testimonial deleted successfully!');
    }

    /**
     * Toggle active status
     */
    public function toggleStatus(Testimonial $testimonial)
    {
        $testimonial->update(['is_active' => !$testimonial->is_active]);

        return redirect()->route('admin.testimonials.index')
            ->with('success', 'Status updated successfully!');
    }
}
