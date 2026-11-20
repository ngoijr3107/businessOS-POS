<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Branch extends Model { protected $guarded=[]; protected $casts=['is_active'=>'boolean']; public function company(){return $this->belongsTo(Company::class);} public function users(){return $this->hasMany(User::class);} public function clients(){return $this->hasMany(Client::class);} public function sales(){return $this->hasMany(Sale::class);} public function invoices(){return $this->hasMany(Invoice::class);} public function payments(){return $this->hasMany(Payment::class);} }
