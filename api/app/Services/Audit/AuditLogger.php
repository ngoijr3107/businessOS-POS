<?php
namespace App\Services\Audit;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
class AuditLogger { public function log(Request $request,string $action,Model $model,?array $old=null,?array $new=null,?string $reason=null): void { $data=['company_id'=>$request->user()?->company_id,'user_id'=>$request->user()?->id,'action'=>$action,'auditable_type'=>$model::class,'auditable_id'=>$model->getKey(),'old_values'=>$old,'new_values'=>$new,'ip_address'=>$request->ip()]; if($reason)$data['new_values']=array_merge($new??[],['_reason'=>$reason]); AuditLog::create($data); } }
