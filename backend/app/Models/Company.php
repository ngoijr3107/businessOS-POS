<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Company extends Model { protected $guarded=[]; public function branches(){return $this->hasMany(Branch::class);} public function clients(){return $this->hasMany(Client::class);} public function products(){return $this->hasMany(Product::class);} public function users(){return $this->hasMany(User::class);} public function invoices(){return $this->hasMany(Invoice::class);} public function sales(){return $this->hasMany(Sale::class);} public function payments(){return $this->hasMany(Payment::class);} }
