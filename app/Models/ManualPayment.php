<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class ManualPayment extends Model
{
    public $timestamps = false;
    
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'ManualPayment';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['screenshorts','amount','userId','status','created_at','transactionId'];


   
}