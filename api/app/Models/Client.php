<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Client extends Model { protected $guarded=[]; protected $casts=['credit_limit'=>'decimal:2','opening_balance'=>'decimal:2','is_active'=>'boolean']; public function company(){return $this->belongsTo(Company::class);} public function branch(){return $this->belongsTo(Branch::class);} public function sales(){return $this->hasMany(Sale::class);} public function invoices(){return $this->hasMany(Invoice::class);} public function payments(){return $this->hasMany(Payment::class);} public function getOutstandingAttribute(){return (float)$this->opening_balance + (float)$this->invoices()->whereIn('status',['issued','partial','overdue'])->sum('balance');} }
