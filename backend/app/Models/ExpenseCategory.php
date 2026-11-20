<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ExpenseCategory extends Model { protected $guarded=[]; public function company(){return $this->belongsTo(Company::class);} public function expenses(){return $this->hasMany(Expense::class,'category_id');} }
