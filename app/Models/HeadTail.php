<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class HeadTail extends Model
{
    public $timestamps = false;
    
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'heads_and_tail';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['head_tail_id'];


   
}
