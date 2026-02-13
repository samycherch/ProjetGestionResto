<?php

namespace Resto\models;

use Illuminate\Database\Eloquent\Model;

class Plat extends Model
{
    protected $table = 'plat';
    protected $primaryKey = 'numplat';
    public $timestamps = false;

    public function plat(){
        return $this->belongsToMany(Reservation::class,
            'commande',
            'numplat',
            'numres')
            -> withPivot('quantite');
    }
}