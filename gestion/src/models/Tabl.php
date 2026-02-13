<?php

namespace Resto\models;

use Illuminate\Database\Eloquent\Model;

class Tabl extends Model
{
    protected $table = 'tabl';
    protected $primaryKey = 'numtab';
    public $timestamps = false;

    public function table(){
        return $this->hasMany(Reservation::class, 'numtab', 'numtab');
    }

}