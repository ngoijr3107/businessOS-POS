<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CollectionFollowup extends Model { protected $guarded=[]; protected $casts=['follow_up_date'=>'date','completed_at'=>'datetime']; public function company(){return $this->belongsTo(Company::class);} public function client(){return $this->belongsTo(Client::class);} public function invoice(){return $this->belongsTo(Invoice::class);} public function user(){return $this->belongsTo(User::class);} }
