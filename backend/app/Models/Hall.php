<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['cinema_id', 'name'])]
class Hall extends Model
{
    use HasFactory;

    public $timestamps = false;

    public function cinema(): BelongsTo
    {
        return $this->belongsTo(Cinema::class);
    }

    public function seats(): HasMany
{
    return $this->hasMany(Seat::class);
}
public function screenings(): HasMany
{
    return $this->hasMany(Screening::class);
}
public function tickets(): HasMany
{
    return $this->hasMany(Ticket::class);
}
}