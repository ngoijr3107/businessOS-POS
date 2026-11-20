<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class StockMovement extends Model
{
    protected $guarded = [];
    protected $casts = ['quantity' => 'decimal:3', 'balance_after' => 'decimal:3'];
    public function product(){return $this->belongsTo(Product::class);}
    public function company(){return $this->belongsTo(Company::class);}
    public function user(){return $this->belongsTo(User::class);}
}
