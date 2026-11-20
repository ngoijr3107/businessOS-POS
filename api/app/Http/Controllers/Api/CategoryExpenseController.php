<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class CategoryExpenseController extends Controller {
 public function index(Request $r){return ExpenseCategory::where('company_id',$r->user()->company_id)->withCount('expenses')->latest()->get();}
 public function store(Request $r){$data=$r->validate(['name'=>['required','string','max:100',Rule::unique('expense_categories','name')->where('company_id',$r->user()->company_id)]]);$data['company_id']=$r->user()->company_id;return response()->json(ExpenseCategory::create($data),201);}
 public function show(Request $r,ExpenseCategory $expenseCategory){abort_unless($expenseCategory->company_id===$r->user()->company_id,404);return $expenseCategory->loadCount('expenses');}
 public function update(Request $r,ExpenseCategory $expenseCategory){abort_unless($expenseCategory->company_id===$r->user()->company_id,404);$data=$r->validate(['name'=>['required','string','max:100',Rule::unique('expense_categories','name')->ignore($expenseCategory->id)->where('company_id',$r->user()->company_id)]]);$expenseCategory->update($data);return $expenseCategory->fresh();}
 public function destroy(Request $r,ExpenseCategory $expenseCategory){abort_unless($expenseCategory->company_id===$r->user()->company_id,404);if($expenseCategory->expenses()->exists())return response()->json(['message'=>'Category has expenses and cannot be deleted.'],422);$expenseCategory->delete();return response()->noContent();}
}
