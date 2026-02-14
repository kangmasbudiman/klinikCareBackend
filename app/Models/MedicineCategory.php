<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicineCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Get medicines in this category
     */
    public function medicines(): HasMany
    {
        return $this->hasMany(Medicine::class, 'category_id');
    }

    /**
     * Get active medicines count
     */
    public function getActiveMedicinesCountAttribute(): int
    {
        return $this->medicines()->where('is_active', true)->count();
    }
}
