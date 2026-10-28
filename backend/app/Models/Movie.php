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

    /** Allowed values, the same list as the movies_age_rating_valid constraint. */
    public const AGE_RATINGS = ['0+', '6+', '12+', '16+', '18+'];

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
