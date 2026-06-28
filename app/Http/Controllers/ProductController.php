<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ProductController extends Controller
{
    /**
     * Display a listing of the products.
     */
    public function index(Request $request)
    {
        $query = Product::query();

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('sku', 'like', '%' . $search . '%');
            });
        }

        $products = $query->orderBy('name', 'asc')->paginate(10);
        return view('products.index', compact('products'));
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sku' => 'nullable|string|max:50|unique:products,sku',
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0|gte:purchase_price', // Selling price must be >= purchase price
            'stock' => 'required|integer|min:0',
            'expiry_date' => 'nullable|date',
            'unit' => 'required|in:renteng,pcs,karton',
        ], [
            'selling_price.gte' => 'Harga jual tidak boleh lebih rendah dari harga beli.',
            'sku.unique' => 'Barcode/SKU ini sudah digunakan oleh produk lain.',
        ]);

        $data = $validated;

        // Handle image from either gallery upload or camera capture (base64)
        if ($request->filled('image_base64')) {
            $destinationPath = public_path('storage/products');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            
            $base64Image = $request->input('image_base64');
            $image_parts = explode(";base64,", $base64Image);
            $image_type_aux = explode("image/", $image_parts[0]);
            $image_type = $image_type_aux[1] ?? 'png';
            $image_base64 = base64_decode($image_parts[1]);
            
            $imageName = time() . '_' . uniqid() . '.' . $image_type;
            file_put_contents($destinationPath . '/' . $imageName, $image_base64);
            $data['image'] = $imageName;
        } elseif ($request->hasFile('image')) {
            $destinationPath = public_path('storage/products');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            
            $imageName = time() . '_' . uniqid() . '.' . $request->image->extension();
            $request->image->move($destinationPath, $imageName);
            $data['image'] = $imageName;
        }

        Product::create($data);

        return redirect()->route('products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    /**
     * Update the specified product in storage.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'sku' => 'nullable|string|max:50|unique:products,sku,' . $product->id,
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0|gte:purchase_price',
            'stock' => 'required|integer|min:0',
            'expiry_date' => 'nullable|date',
            'unit' => 'required|in:renteng,pcs,karton',
        ], [
            'selling_price.gte' => 'Harga jual tidak boleh lebih rendah dari harga beli.',
            'sku.unique' => 'Barcode/SKU ini sudah digunakan oleh produk lain.',
        ]);

        $data = $validated;

        $destinationPath = public_path('storage/products');
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        // Handle image from either gallery upload or camera capture (base64)
        if ($request->filled('image_base64')) {
            // Delete old image if exists
            if ($product->image) {
                $oldImagePath = $destinationPath . '/' . $product->image;
                if (file_exists($oldImagePath)) {
                    @unlink($oldImagePath);
                }
            }

            $base64Image = $request->input('image_base64');
            $image_parts = explode(";base64,", $base64Image);
            $image_type_aux = explode("image/", $image_parts[0]);
            $image_type = $image_type_aux[1] ?? 'png';
            $image_base64 = base64_decode($image_parts[1]);
            
            $imageName = time() . '_' . uniqid() . '.' . $image_type;
            file_put_contents($destinationPath . '/' . $imageName, $image_base64);
            $data['image'] = $imageName;
        } elseif ($request->hasFile('image')) {
            // Delete old image if exists
            if ($product->image) {
                $oldImagePath = $destinationPath . '/' . $product->image;
                if (file_exists($oldImagePath)) {
                    @unlink($oldImagePath);
                }
            }

            $imageName = time() . '_' . uniqid() . '.' . $request->image->extension();
            $request->image->move($destinationPath, $imageName);
            $data['image'] = $imageName;
        }

        $product->update($data);

        return redirect()->route('products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product)
    {
        // Delete image file from disk
        if ($product->image) {
            $imagePath = public_path('storage/products/' . $product->image);
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
        }

        $product->delete();

        return redirect()->route('products.index')->with('success', 'Produk berhasil dihapus.');
    }

    /**
     * Show the restock (kulakan) list.
     */
    public function restock(Request $request)
    {
        // Get all products that have stock < 10
        $lowStockProducts = Product::where('stock', '<', 10)->orderBy('stock', 'asc')->get();
        // Get all products for searching and manual addition
        $allProducts = Product::orderBy('name', 'asc')->get();

        return view('products.restock', compact('lowStockProducts', 'allProducts'));
    }
}
