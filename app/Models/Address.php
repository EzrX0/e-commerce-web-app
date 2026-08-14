<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = ['label', 'street', 'city', 'postal_code', 'is_default'];


    public function user(){
        return $this->belongesTo(user::class);
    }

    
}

