<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable; use Laravel\Sanctum\HasApiTokens; use Illuminate\Notifications\Notifiable; use Spatie\Permission\Traits\HasRoles;
class User extends Authenticatable { use HasApiTokens, Notifiable, HasRoles; protected $guarded=['id']; protected $hidden=['password','remember_token']; protected function casts(): array { return ['email_verified_at'=>'datetime','password'=>'hashed','is_active'=>'boolean']; } public function company(){return $this->belongsTo(Company::class);} public function branch(){return $this->belongsTo(Branch::class);} }
