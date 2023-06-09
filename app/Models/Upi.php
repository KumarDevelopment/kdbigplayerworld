<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Upi extends Model
{
    public $timestamps = false;
    
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'upi';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['upi_id','created_at'];


   
}
