<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class WheelocityID extends Model
{
    public $timestamps = false;
    
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'wheelocity_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['wheelocity_id', 'created_time'];


    protected $appends = ['time_left'];


    protected static function boot()
    {
        parent::boot();

        static::creating(function($model) {
            $model->created_time = time();
        });
    }


    public function getTimeLeftAttribute(){
        return WHEELOCITY_PLAY_TIME - (time() - $this->created_time);
    }
   
}
