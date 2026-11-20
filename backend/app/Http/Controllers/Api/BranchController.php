<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
class BranchController extends Controller {
 public function index(Request $r){return Branch::where('company_id',$r->user()->company_id)->withCount(['users','clients','sales'])->latest()->get();}
 public function store(Request $r){$data=$r->validate(['name'=>'required|string|max:255','code'=>'required|string|max:30','phone'=>'nullable|string|max:50','address'=>'nullable|string','is_active'=>'boolean']);$data['company_id']=$r->user()->company_id;return response()->json(Branch::create($data),201);}
 public function show(Request $r,Branch $branch){abort_unless($branch->company_id===$r->user()->company_id,404);return $branch;}
 public function update(Request $r,Branch $branch){abort_unless($branch->company_id===$r->user()->company_id,404);$data=$r->validate(['name'=>'sometimes|required|string|max:255','code'=>'sometimes|required|string|max:30','phone'=>'nullable|string|max:50','address'=>'nullable|string','is_active'=>'boolean']);$branch->update($data);return $branch->fresh();}
 public function destroy(Request $r,Branch $branch){abort_unless($branch->company_id===$r->user()->company_id,404);if($branch->users()->exists()||$branch->clients()->exists()||$branch->sales()->exists()||$branch->invoices()->exists()||$branch->payments()->exists())return response()->json(['message'=>'Branch is in use and cannot be deleted.'],422);$branch->delete();return response()->noContent();}
}
