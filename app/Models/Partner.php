<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    protected $table = 'customers_suppliers';

    protected $guarded = [];

    public function batches()
    {
        return $this->hasMany(Batch::class, 'supplier_id');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'customer_id');
    }
}
