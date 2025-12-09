<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image as ImageFacade;

class ImageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = ProductImage::with(['product', 'product.category']);
        
        if (request()->has('search')) {
            $search = request()->get('search');
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }
        
        $images = $query->orderBy('created_at', 'desc')->paginate(20);
        
        return view('admin.images.index', compact('images'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'images.*' => 'required|image|max:2048',
            'set_primary' => 'boolean'
        ]);

        $product = Product::findOrFail($request->product_id);
        $setPrimary = $request->boolean('set_primary', false);
        $uploadedImages = [];

        foreach ($request->file('images') as $index => $imageFile) {
            // Generate unique filenames
            $filename = uniqid() . '_' . $imageFile->getClientOriginalName();
            $thumbnailFilename = 'thumb_' . $filename;
            
            // Define paths
            $path = 'products/' . $filename;
            $thumbnailPath = 'products/thumbnails/' . $thumbnailFilename;
            
            // Create and store thumbnail
            $thumbnail = ImageFacade::make($imageFile)->fit(300, 300);
            Storage::put($thumbnailPath, $thumbnail->encode());
            
            // Store original image
            Storage::put($path, file_get_contents($imageFile));
            
            // Determine if this should be the primary image
            $isPrimary = $setPrimary && $index === 0;
            
            // If setting as primary, unset any existing primary image for this product
            if ($isPrimary) {
                ProductImage::where('product_id', $product->id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }
            
            // Create product image record
            $productImage = ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $path,
                'thumbnail_path' => $thumbnailPath,
                'is_primary' => $isPrimary,
            ]);
            
            $uploadedImages[] = $productImage;
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Images uploaded successfully!',
            'images' => $uploadedImages
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $image = ProductImage::findOrFail($id);
        
        // Delete image files from storage
        Storage::delete([
            $image->image_path,
            $image->thumbnail_path
        ]);
        
        // Delete the database record
        $image->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Image deleted successfully!'
        ]);
    }
}