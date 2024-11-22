<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\MemberOptions;
use App\Enums\MaritalStatus;
use App\Enums\MembershipStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Member
 *
 * @property $id
 * @property $first_name
 * @property $last_name
 * @property $email
 * @property $phone_number
 * @property $address
 * @property $city
 * @property $zip_code
 * @property $date_of_birth
 * @property $gender
 * @property $marital_status
 * @property $date_joined
 * @property $membership_status
 * @property $baptism_date
 * @property $notes
 * @property $created_at
 * @property $updated_at
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Member extends Model
{

    protected $perPage = 20;


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'document_number',
        'email',
        'phone_number',
        'address',
        'city',
        'zip_code',
        'date_of_birth',
        'gender',
        'marital_status',
        'date_joined',
        'membership_status',
        'baptism_date',
        'notes',
        'photo',
        'spouse_id',
        'father_id',
        'mother_id'
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($member) {
            $member->membership_status = MembershipStatus::INACTIVED;
            $member->save();
            return false;
        });
    }

    public function scopeActives($query)
    {
        return $query->where('membership_status', MembershipStatus::ACTIVED);
    }

    public function scopeByFullName($query, $name) {
        if (!$name) {
            return $query;
        }

        return $query->whereRaw('concat(first_name, " ", last_name) like ?', ['%' . trim($name) . '%']);
    }

    public function scopeFilter($query, $attribute, $search)
    {
        if (!$attribute || !$search || !isset(MemberOptions::options()[$attribute])) {
            return $query;
        }

        return $query->where($attribute, 'like', '%' . $search . '%');
    }

    public function scopeByStatus($query, $membership_status)
    {
        if (!$membership_status || !isset(MembershipStatus::options()[$membership_status])) {
            return $query;
        }

        return $query->where('membership_status', $membership_status);
    }

    public function spouse()
    {
        return $this->belongsTo(Member::class, 'spouse_id');
    }

    public function father()
    {
        return $this->belongsTo(Member::class, 'father_id');
    }

    public function mother()
    {
        return $this->belongsTo(Member::class, 'mother_id');
    }

    public function children()
    {
        return $this->hasMany(Member::class, $this->gender == 'Male' ? 'father_id' : 'mother_id');
    }

    public function getUrlPhotoAttribute()
    {
        return $this->photo && file_exists(public_path('images/' . $this->photo)) ? asset('images/' . $this->photo) : 'https://ui-avatars.com/api/?name=' . $this->first_name . '+' . $this->last_name;
    }

    public function getFirstAndLastNameAttribute() {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getParentsNameAttribute() {
        return $this->father?->first_and_last_name .' / '. $this->mother?->first_and_last_name;
    }
    public function getFullNameAttribute()
    {
        return implode(' ', array_filter([$this->first_name, $this->middle_name, $this->last_name]));
    }

    public function getAgeAttribute()
    {
        if ($this->date_of_birth) {
            $this->date_of_birth = \Carbon\Carbon::parse($this->date_of_birth);
            return $this->date_of_birth->age ? $this->date_of_birth->age. ' '. __('years') : null;
        }
        return null;
    }

    public function getFullAddressAttribute()
    {
        if (!$this->address) {
            return null;
        }

        return $this->address . ', ' . $this->city . ', ' . $this->zip_code;
    }

    public function getContactAttribute()
    {

        if ($this->phone_number && $this->email) {
            return $this->phone_number . ' / ' . $this->email;
        }

        if ($this->phone_number) {
            return $this->phone_number;
        }

        if ($this->email) {
            return $this->email;
        }
    }

    public function getGenderNameAttribute()
    {
        return isset(Gender::options()[$this->gender]) ? __(Gender::options()[$this->gender]) : 'N/D';
    }

    public function getMaritalStatusNameAttribute()
    {
        return isset(MaritalStatus::options()[$this->marital_status]) ? __(MaritalStatus::options()[$this->marital_status]) : 'N/D';
    }

    public function isActived()
    {
        return $this->membership_status == MembershipStatus::ACTIVED;
    }

    public function isPending()
    {
        return $this->membership_status == MembershipStatus::PENDING;
    }

    public function isInactived()
    {
        return $this->membership_status == MembershipStatus::INACTIVED;
    }

    public function getCreatedAtFormattedAttribute()
    {
        return \Carbon\Carbon::parse($this->created_at)->format('d/m/Y H:i');
    }

    public function getUpdatedAtFormattedAttribute()
    {
        return \Carbon\Carbon::parse($this->updated_at)->format('d/m/Y H:i');
    }

    // Definir a relação many-to-many com o model Ministry
    public function ministries()
    {
        return $this->belongsToMany(Ministry::class);
    }
}
