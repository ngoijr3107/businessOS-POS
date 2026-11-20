<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Product extends Model { protected $guarded=[]; protected $casts=['cost_price'=>'decimal:2','selling_price'=>'decimal:2','tax_rate'=>'decimal:2','stock_quantity'=>'decimal:3','minimum_stock'=>'decimal:3','track_stock'=>'boolean','is_active'=>'boolean']; public function company(){return $this->belongsTo(Company::class);} public function category(){return $this->belongsTo(ProductCategory::class,'category_id');} public function saleItems(){return $this->hasMany(SaleItem::class);} public function invoiceItems(){return $this->hasMany(InvoiceItem::class);}
 public function stockMovements(){return $this->hasMany(StockMovement::class);} }
