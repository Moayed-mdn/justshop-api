<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminFileUploadController extends Controller
{
    /**
     * Upload hero banner image
     */
    public function uploadHeroBannerImage(Request $request)
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'], // 5MB max
        ]);

        $file = $request->file('image');
        
        // Generate unique filename
        $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();
        
        // Store in public disk under hero directory
        $path = $file->storeAs('hero', $filename, 'public');
        
        return response()->json([
            'success' => true,
            'data' => [
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
                'full_url' => config('app.url') . '/storage/' . $path,
            ],
        ]);
    }

    /**
     * Delete hero banner image
     */
    public function deleteHeroBannerImage(Request $request)
    {
        $request->validate([
            'path' => ['required', 'string'],
        ]);

        $path = $request->input('path');
        
        // Security: ensure path is within hero directory
        if (!str_starts_with($path, 'hero/')) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid file path',
            ], 400);
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        return response()->json([
            'success' => true,
            'message' => 'Image deleted successfully',
        ]);
    }
}
