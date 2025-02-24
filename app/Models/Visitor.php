<?php
namespace App\Models;

use App\Enums\Gender;
use App\Enums\VisitorOptions;
use App\Enums\VisitorStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'gender',
        'phone_number',
        'invited_by',
        'group',
        'city',
        'observation',
        'created_by',
        'status',
    ];

    public function scopeActives($query)
    {
        return $query->where('status', VisitorStatus::ACTIVED);
    }

    public function scopeFilter($query, $attribute, $search)
    {
        if (!$attribute || !$search || !isset(VisitorOptions::options()[$attribute])) {
            return $query;
        }

        return $query->where($attribute, 'like', '%' . $search . '%');
    }

    public function getCreatedAtFormattedAttribute()
    {
        return \Carbon\Carbon::parse($this->created_at)->format('d/m/Y H:i');
    }

    public function getUpdatedAtFormattedAttribute()
    {
        return \Carbon\Carbon::parse($this->updated_at)->format('d/m/Y H:i');
    }

    public function getPhoneNumberFormattedAttribute()
    {
        return str_starts_with($this->phone_number, '+')
            ? $this->phone_number
            : '351' . $this->phone_number;
    }

    public function getGenderNameAttribute()
    {
        return isset(Gender::options()[$this->gender]) ? __(Gender::options()[$this->gender]) : 'N/D';
    }

    public function getStatusNameAttribute()
    {
        return isset(VisitorStatus::options()[$this->status]) ? __(VisitorStatus::options()[$this->marital_status]) : 'N/D';
    }

    public function isActive()
    {
        return $this->status == VisitorStatus::ACTIVED;
    }

    public function isInactive()
    {
        return $this->status == VisitorStatus::INACTIVED;
    }
}
