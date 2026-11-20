<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class CategoryController extends Controller {
 public function index(Request $r){return ProductCategory::where('company_id',$r->user()->company_id)->withCount('products')->latest()->get();}
 public function store(Request $r){$data=$r->validate(['name'=>['required','string','max:100',Rule::unique('product_categories','name')->where('company_id',$r->user()->company_id)]]);$data['company_id']=$r->user()->company_id;return response()->json(ProductCategory::create($data),201);}
 public function show(Request $r,ProductCategory $category){abort_unless($category->company_id===$r->user()->company_id,404);return $category->loadCount('products');}
 public function update(Request $r,ProductCategory $category){abort_unless($category->company_id===$r->user()->company_id,404);$data=$r->validate(['name'=>['required','string','max:100',Rule::unique('product_categories','name')->ignore($category->id)->where('company_id',$r->user()->company_id)]]);$category->update($data);return $category->fresh();}
 public function destroy(Request $r,ProductCategory $category){abort_unless($category->company_id===$r->user()->company_id,404);if($category->products()->exists())return response()->json(['message'=>'Category has products and cannot be deleted.'],422);$category->delete();return response()->noContent();}
}
