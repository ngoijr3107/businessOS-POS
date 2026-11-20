<?php
namespace App\Providers;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use App\Models\AuditLog;
class AppServiceProvider extends ServiceProvider { public function register(): void {} public function boot(): void { Model::created(function($model){$this->audit($model,'created',null,$model->getAttributes());}); Model::updated(function($model){$this->audit($model,'updated',$model->getOriginal(),$model->getAttributes());}); Model::deleted(function($model){$this->audit($model,'deleted',$model->getOriginal(),null);}); } private function audit($model,$action,$old,$new):void { if($model instanceof AuditLog || !in_array(class_basename($model),['Sale','Invoice','Payment','Expense','Client','Product','Branch','ProductCategory','ExpenseCategory','User'])) return; $user=auth()->user(); if(!$user) return; AuditLog::create(['company_id'=>$user->company_id,'user_id'=>$user->id,'action'=>$action,'auditable_type'=>$model::class,'auditable_id'=>$model->getKey(),'old_values'=>$old,'new_values'=>$new,'ip_address'=>request()->ip()]); } }
