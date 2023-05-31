<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Variant;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use Illuminate\Http\Request;
use App\Http\Requests\ProductFilterRequest;
use App\Http\Requests\ProductStoreRequest;

class ProductController extends Controller
{
/**
 * Display a listing of the resource.
 *
 * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
 */
public function index(ProductFilterRequest $request)
{
//  dd($request->all());
// For search or filter Product..........
$query = Product::query();

if ($request->filled('title')) {
    $query->where('title', 'like', '%' . $request->input('title') . '%');
}

if ($request->filled('variant')) {
    $query->whereHas('variantPrices', function ($subQuery) use ($request) {
        $subQuery->whereIn('product_variant_one', [$request->input('variant')])
            ->orWhereIn('product_variant_two', [$request->input('variant')])
            ->orWhereIn('product_variant_three', [$request->input('variant')]);
    });
}

if ($request->filled('price_from') && $request->filled('price_to')) {
    $query->whereHas('variantPrices', function ($subQuery) use ($request) {
        $subQuery->whereBetween('price', [$request->input('price_from'), $request->input('price_to')]);
    });
}

if ($request->filled('date')) {
    $query->whereDate('created_at', $request->input('date'));
}

$filteredProducts = $query->paginate(5);

//  dd($filteredProducts);

// For list of the Tables.....

$products = Product::with(['variantPrices'])->paginate(5);
$product_variants = Variant::pluck('title');
// dd($product_variants);
$variantOneValues = ProductVariant::distinct('variant')->pluck('variant');
// dd($variantOneValues);
$variantTwoValues = ProductVariant::distinct('variant')->pluck('variant');
// dd($variantTwoValues);
$variantThreeValues = ProductVariant::distinct('variant')->pluck('variant');

$data = [
    'Color' => $variantOneValues->slice(0, 3),
    'Size' => $variantOneValues->slice(3, 4),
    'Style' => $variantOneValues->slice(7),
];

// dd($products);

return view('products.index',
    compact(
        'products',
        'product_variants',
        'filteredProducts',
        'data'
        
    ));

}

/**
 * Show the form for creating a new resource.
 *
 * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
 */
public function create()
{
$variants = Variant::all();
return view('products.create', compact('variants'));
}

/**
 * Store a newly created resource in storage.
 *
 * @param \Illuminate\Http\Request $request
 * @return \Illuminate\Http\JsonResponse
 */
public function store(ProductStoreRequest $request)
{

// dd($request->all());
$product = new Product();
$product->title = $request->input('product_name');
$product->sku = $request->input('product_sku');
$product->description = $request->input('product_description');
$product->save();

// Handle variants
$variants = $request->input('product_variant');
$variantPreviews = $request->input('product_preview');

if (!empty($variants) && !empty($variantPreviews)) {
    foreach ($variants as $variantIndex => $variant) {
        $selectedOption = $variant['option'];
        foreach ($variant['value'] as $variantValue) {    
            $newVariant = new ProductVariant();
            $newVariant->variant = $variantValue;
            $newVariant->product_id = $product->id;
            $variantRecord = Variant::where('id', $selectedOption)->first();
            if ($variantRecord) {
                
                $newVariant->variant_id = $variantRecord->id;
            }
            $newVariant->save();
            $newVariantId = $newVariant->id;
            if (is_array($variantPreviews) && isset($variantPreviews[$variantIndex]) && is_array($variantPreviews[$variantIndex])) {
                $previews = $variantPreviews[$variantIndex];
                foreach ($previews as $variantPreview) {
                    if (is_array($variantPreview)) {
                        $variantValues = explode('/', $variantPreview['variant']);
                        // echo '<pre>';
                        // print_r($variantValues);
                        // echo '</pre>';
                        $newVariantPrice = new ProductVariantPrice();
                        $newVariantPrice->price = $variantPreview['price'];
                        $newVariantPrice->stock = $variantPreview['stock'];
                        $newVariantPrice->product_id = $product->id;
                        $variantOne = ProductVariant::where('variant', $variantValues[0])
                            ->where('product_id', $product->id)
                            ->first();
                        $variantTwo = ProductVariant::where('variant', $variantValues[1])
                            ->where('product_id', $product->id)
                            ->first();
                        $variantThree = ProductVariant::where('variant', $variantValues[2])
                            ->where('product_id', $product->id)
                            ->first();
                        if ($variantOne) {
                            $newVariantPrice->product_variant_one = $variantOne->id;
                        }
                        if ($variantTwo) {
                            $newVariantPrice->product_variant_two = $variantTwo->id;
                        }
                        if ($variantThree) {
                            $newVariantPrice->product_variant_three = $variantThree->id;
                        }
                        $newVariantPrice->save();
                        $newVariantPriceId = $newVariantPrice->id;
                        // Store the product image
                        $productImage = new ProductImage();
                        $productImage->product_id = $product->id;
                        $productImage->file_path = $variantPreview['image'];
                        $productImage->thumbnail = $variantPreview['thumbnail'];
                        $productImage->save();
                        
                    } else {
                        
                        echo "not insert";
                    }
                }
            }
        }
    }
}
return redirect()->route('product.index')->with('success', 'Product created successfully.');
}





/**
 * Display the specified resource.
 *
 * @param \App\Models\Product $product
 * @return \Illuminate\Http\Response
 */
public function show($product)
{

}

/**
 * Show the form for editing the specified resource.
 *
 * @param \App\Models\Product $product
 * @return \Illuminate\Http\Response
 */
public function edit(Product $product)
{
$variants = Variant::all();
return view('products.edit', compact('variants'));
}

/**
 * Update the specified resource in storage.
 *
 * @param \Illuminate\Http\Request $request
 * @param \App\Models\Product $product
 * @return \Illuminate\Http\Response
 */
public function update(Request $request, Product $product)
{
//
}

/**
 * Remove the specified resource from storage.
 *
 * @param \App\Models\Product $product
 * @return \Illuminate\Http\Response
 */
public function destroy(Product $product)
{
//
}
}
