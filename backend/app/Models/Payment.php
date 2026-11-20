<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Payment extends Model { protected $guarded=[]; protected $casts=['payment_date'=>'datetime','amount'=>'decimal:2']; public function company(){return $this->belongsTo(Company::class);} public function branch(){return $this->belongsTo(Branch::class);} public function client(){return $this->belongsTo(Client::class);} public function user(){return $this->belongsTo(User::class);} public function allocations(){return $this->hasMany(PaymentAllocation::class);} }
