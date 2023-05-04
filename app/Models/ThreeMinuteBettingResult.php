<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class ThreeMinuteBettingResult extends Model
{
    public $timestamps = false;
    
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'three_minute_betting_result';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['winning_amount','betting_amount','game_id','user_id','winning_value'];


   
}
