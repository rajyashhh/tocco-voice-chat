<?php

namespace App\Models;

use Carbon\Carbon;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;
use Modules\Events\Entities\GeneralRole;
use Illuminate\Database\Eloquent\Casts\Attribute;

class HomeCarousel extends Model
{
    use TimestampsWithTimezone;

    protected $table = 'home_carousels';

    protected $guarded = [];

    protected $casts = [
        'display_discover' => 'integer',
        'display_home_top' => 'integer',
        'display_home_middle' => 'integer',
        'display_live' => 'integer',
        'display_country' => 'integer',
        'display_at' => 'array',


    ];


    public function user()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function room()
    {
        return $this->hasOne(Room::class, 'uid', 'owner_id')->where('type', 'audio');
    }

    public function generalRole()
    {
        return $this->hasOne(GeneralRole::class, 'type', 'event_type');
    }

    public function countries()
    {
        return $this->belongsToMany(Country::class, 'banner_country');
    }

    public function countriesLite()
    {
        return $this->belongsToMany(Country::class, 'banner_country', 'home_carousel_id', 'country_id')
                    ->select(['countries.id', 'countries.name', 'countries.e_name', 'countries.flag'])
                    ->withPivot('home_carousel_id', 'country_id');
    }
    protected static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
             $newDuration = Carbon::now();
            if ($model->form !== null) {

                $newDuration = Carbon::now();
                $duration = match ($model->form) {
                    '1' => $model->input > 1 ? $newDuration->addHours($model->input) : $newDuration->addMinute($model->input * 60),
                    '2' => $newDuration->addDays($model->input),
                    '3' => $newDuration->addMonths($model->input),
                    '4' => 0,
                    default => null
                };
                // $model->duration = $duration->timestamp;
            }
        });

        self::saving(function ($model) {
            $newDuration = Carbon::now();

            if ($model->form !== null) {
                if ($model->isDirty('input') || $model->isDirty('form')) {
                    $duration = match ($model->form) {
                        '1' => $model->input > 1 ? $newDuration->addHours($model->input) : $newDuration->addMinute($model->input * 60),
                        '2' => $newDuration->addDays($model->input),
                        '3' => $newDuration->addMonths($model->input),
                        '4' => 0,

                        default => null
                    };
                    // $model->duration = $duration->timestamp;
                }
            }
        });

        self::updating(function ($model) {
            $newDuration = Carbon::now();
            if ($model->form !== null) {
                if ($model->isDirty('input') || $model->isDirty('form')) {
                    $duration = match ($model->form) {
                        '1' => $model->input > 1 ? $newDuration->addHours($model->input) : $newDuration->addMinute($model->input * 60),
                        '2' => $newDuration->addDays($model->input),
                        '3' => $newDuration->addMonths($model->input),
                        '4' => 0,
                        default => null
                    };
                    // $model->duration = $duration->timestamp;
                }
            }
        });
    }


    public function displays()
    {
        return $this->hasMany(HomeCarouselDisplay::class, 'home_carousel_id');
    }

    public function getIsActiveAttribute()
    {
        return is_null($this->duration) || $this->duration > Carbon::now()->timestamp;
    }

    public function setDurationAttribute($value)
    {
        $this->attributes['duration'] = $value;
    }

    public function getDurationAttribute($value)
    {
        return $value;
    }

    public function displayDiscover(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->relationLoaded('displays')) {
                    return $this->displays->contains('display_type', 'discover');
                }
                return $this->displays()->where('display_type', 'discover')->exists();
            },
            set: fn($value) => $this->syncDisplay('discover', $value)
        );
    }

    public function displayHomeTop(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->relationLoaded('displays')) {
                    return $this->displays->contains('display_type', 'home_top');
                }
                return $this->displays()->where('display_type', 'home_top')->exists();
            },
            set: fn($value) => $this->syncDisplay('home_top', $value)
        );
    }

    public function displayHomeMiddle(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->relationLoaded('displays')) {
                    return $this->displays->contains('display_type', 'home_middle');
                }
                return $this->displays()->where('display_type', 'home_middle')->exists();
            },
            set: fn($value) => $this->syncDisplay('home_middle', $value)
        );
    }

    public function displayLive(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->relationLoaded('displays')) {
                    return $this->displays->contains('display_type', 'live');
                }
                return $this->displays()->where('display_type', 'live')->exists();
            },
            set: fn($value) => $this->syncDisplay('live', $value)
        );
    }

    public function displayCountry(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->relationLoaded('displays')) {
                    return $this->displays->contains('display_type', 'country');
                }
                return $this->displays()->where('display_type', 'country')->exists();
            },
            set: fn($value) => $this->syncDisplay('country', $value)
        );
    }

    public function displayInRoom(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->relationLoaded('displays')) {
                    return $this->displays->contains('display_type', 'in_room');
                }
                return $this->displays()->where('display_type', 'in_room')->exists();
            },
            set: fn($value) => $this->syncDisplay('in_room', $value)
        );
    }

    protected function syncDisplay(string $type, $value)
    {
        if ($value) {
            $this->displays()->firstOrCreate(['display_type' => $type]);
        } else {
            $this->displays()->where('display_type', $type)->delete();
        }
    }








    protected function getDisplayAtAttribute($value)
    {
        $decoded = $this->displays?->pluck('display_type')->toArray();
        return implode(',',$decoded);
    }

}
