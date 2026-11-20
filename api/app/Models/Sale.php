<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Sale extends Model { protected $guarded=[]; protected $casts=['sale_date'=>'datetime','subtotal'=>'decimal:2','discount'=>'decimal:2','tax'=>'decimal:2','total'=>'decimal:2']; public function company(){return $this->belongsTo(Company::class);} public function branch(){return $this->belongsTo(Branch::class);} public function client(){return $this->belongsTo(Client::class);} public function user(){return $this->belongsTo(User::class);} public function items(){return $this->hasMany(SaleItem::class);} public function invoice(){return $this->hasOne(Invoice::class);} }
