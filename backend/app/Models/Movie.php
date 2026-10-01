<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'title',
    'description',
    'duration_minutes',
    'release_date',
    'age_rating',
    'poster_url',
    'trailer_url',
])]
class Movie extends Model
{
    use HasFactory;

    public $timestamps = false;

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'movie_genres');
    }
    public function screenings(): HasMany
{
    return $this->hasMany(Screening::class);
}
}