<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeDailyRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'total_points',
        'total_customers',
        'disagreements',
        'changed_clothes_attempts',
        'payment_attempts',
        'check_in',
        'record_date',
    ];
    protected $casts = [
        'disagreements' => 'array',
        'record_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
