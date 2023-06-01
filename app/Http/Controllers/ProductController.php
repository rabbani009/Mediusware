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

    $product = new Product();
    $product->title = $request->input('product_name');
    $product->sku = $request->input('product_sku');
    $product->description = $request->input('product_description');
    $product->save();
    
    // Handle variants
    $variants = $request->input('product_variant');
    $variantPreviews = $request->input('product_preview');
    
    if (!empty($variants) && !empty($variantPreviews)) {
        // Loop through each variant
        foreach ($variants as $variantIndex => $variant) {
            // Get the selected option for the variant
            $selectedOption = $variant['option'];
    
            // Loop through each variant value
            foreach ($variant['value'] as $variantValue) {
                // Create a new variant instance
                $newVariant = new ProductVariant();
                $newVariant->variant = $variantValue;
                $newVariant->product_id = $product->id;
    
                // Find the variant record based on the selected option and value
                $variantRecord = Variant::where('id', $selectedOption)->first();
    
                // Check if the variant record exists
                if ($variantRecord) {
                    // Assign the variant ID from the variant record to the new variant
                    $newVariant->variant_id = $variantRecord->id;
                }
    
                $newVariant->save();
            }
        }
    
        // Fetch the variant IDs based on the variant values and product ID
        foreach ($variantPreviews as $variantPreview) {
            // Ensure the $variantPreview has the "variant" key
            if (is_array($variantPreview) && isset($variantPreview['variant'])) {
                // Extract the variant string from the array
                $variantString = $variantPreview['variant'];
    
                // Explode the variant string into individual values
                $variantValues = explode('/', $variantString);
    
                // // Debugging statement
                // echo "Variant Preview: " . $variantString . "<br>";
                // echo "Variant Values: ";
                // var_dump($variantValues);
                // echo "<br>";
    
                // Initialize the variant IDs
                $variantOne = null;
                $variantTwo = null;
                $variantThree = null;
    
                // Fetch the variant IDs based on the variant values and product ID
                $variantOne = ProductVariant::where('variant', $variantValues[0])
                    ->where('product_id', $product->id)
                    ->first();
    
                // // Debugging statement
                // echo "Variant One: ";
                // var_dump($variantOne);
                // echo "<br>";
    
                if (isset($variantValues[1])) {
                    $variantTwo = ProductVariant::where('variant', $variantValues[1])
                        ->where('product_id', $product->id)
                        ->first();
    
                    // // Debugging statement
                    // echo "Variant Two: ";
                    // var_dump($variantTwo);
                    // echo "<br>";
                }
    
                if (isset($variantValues[2])) {
                    $variantThree = ProductVariant::where('variant', $variantValues[2])
                        ->where('product_id', $product->id)
                        ->first();
    
                    // // Debugging statement
                    // echo "Variant Three: ";
                    // var_dump($variantThree);
                    // echo "<br>";
                }
                    // Create a new variant price instance
                    $newVariantPrice = new ProductVariantPrice();
                    $newVariantPrice->product_id = $product->id;

                    // Assign the variant IDs to the new variant price instance
                    $newVariantPrice->product_variant_one = $variantOne ? $variantOne->variant_id : null;
                    $newVariantPrice->product_variant_two = $variantTwo ? $variantTwo->variant_id : null;
                    $newVariantPrice->product_variant_three = $variantThree ? $variantThree->variant_id : null;

                    // Save the new variant price instance
                    $newVariantPrice->save();
    
                // // Debugging statement
                // echo "New Variant Price: ";
                // var_dump($newVariantPrice);
                // echo "<br>";
    
                // Save the new variant price instance
                $newVariantPrice->save();
    
                // Debugging statement
                // echo "New Variant Price Saved<br>";
    
                // Stop the script execution for debugging purposes
                // die();
            }
        }
    }
    
    
    
    
    
    
    
    
    

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
