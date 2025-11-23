<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiaoDich extends Model
{
    protected $table = 'giaodich';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'gateway',
        'transaction_date',
        'account_number',
        'sub_account',
        'amount_in',
        'amount_out',
        'accumulated',
        'code',
        'transaction_content',
        'reference_number',
        'body',
        'created_at',
    ];
}
