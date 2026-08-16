<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pk extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];

    public function getT1PerAttribute()
    {
        if (($this->t1_score + $this->t2_score) > 0) {
            $res = $this->t1_score / ($this->t1_score + $this->t2_score);
        } else {
            $res = 0.5;
        }

        return number_format($res, 2);
    }

    public function getT2PerAttribute()
    {
        if (($this->t1_score + $this->t2_score) > 0) {
            $res = $this->t2_score / ($this->t1_score + $this->t2_score);
        } else {
            $res = 0.5;
        }

        return number_format($res, 2);
    }

    public function team1Boss()
    {
        return $this->belongsTo(User::class, 'team_1_boss')->with('profile');
    }

    public function team2Boss()
    {
        return $this->belongsTo(User::class, 'team_2_boss')->with('profile');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }
}
