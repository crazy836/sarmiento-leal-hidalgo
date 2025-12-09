<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $image = ProductImage::findOrFail($id);
        return response()->json($image);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $image = ProductImage::findOrFail($id);
        
        // Delete image files from storage
        Storage::delete($image->image_path);
        Storage::delete($image->thumbnail_path);
        
        // If this was the primary image, try to set another one as primary
        if ($image->is_primary) {
            $nextImage = ProductImage::where('product_id', $image->product_id)
                ->first();
                
            if ($nextImage) {
                $nextImage->is_primary = true;
                $nextImage->save();
            }
        }
        
        // Delete the database record
        $image->delete();
        
        return redirect()->back()->with('success', 'Image deleted successfully!');
    }
    
    /**
     * Set the specified image as primary for its product.
     */
    public function setPrimary(string $id)
    {
        $image = ProductImage::findOrFail($id);
        
        // Unset any existing primary image for this product
        ProductImage::where('product_id', $image->product_id)
            ->where('is_primary', true)
            ->update(['is_primary' => false]);
        
        // Set this image as primary
        $image->is_primary = true;
        $image->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Primary image updated successfully!'
        ]);
    }
}