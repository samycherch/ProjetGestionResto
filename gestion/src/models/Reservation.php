<?php

namespace Resto\models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $table = 'reservation';
    protected $primaryKey = 'numres';
    public $timestamps = false;

    public function plat(){
        return $this->belongsToMany(Plat::class,
            'commande',
            'numres',
            'numplat')
            -> withPivot('quantite');
    }


}