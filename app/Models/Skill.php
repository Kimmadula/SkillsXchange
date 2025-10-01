<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    use HasFactory;

    protected $primaryKey = 'skill_id';
    public $timestamps = false;

    protected $fillable = ['name', 'category'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'name' => 'string',
        'category' => 'string',
    ];

    /**
     * Mutator for the name attribute.
     * Automatically normalizes skill names when saving.
     *
     * @param string $value
     * @return void
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $this->normalizeSkillName($value);
    }

    /**
     * Mutator for the category attribute.
     * Automatically normalizes category names when saving.
     *
     * @param string $value
     * @return void
     */
    public function setCategoryAttribute($value)
    {
        $this->attributes['category'] = $this->normalizeSkillName($value);
    }

    /**
     * Normalize skill name by:
     * - Trimming whitespace
     * - Converting multiple spaces to single space
     * - Converting to proper case (first letter of each word capitalized)
     *
     * @param string $name
     * @return string
     */
    private function normalizeSkillName(string $name): string
    {
        // Trim whitespace
        $name = trim($name);
        
        // Replace multiple spaces with single space
        $name = preg_replace('/\s+/', ' ', $name);
        
        // Convert to proper case (Title Case)
        $name = ucwords(strtolower($name));
        
        return $name;
    }

    /**
     * Get the users that have this skill.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_skills', 'skill_id', 'user_id', 'skill_id', 'id');
    }

    /**
     * Scope to find skills by normalized name (case-insensitive, space-normalized)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $name
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByNormalizedName($query, $name)
    {
        $normalizedName = $this->normalizeSkillName($name);
        return $query->whereRaw('LOWER(TRIM(REGEXP_REPLACE(name, "\\s+", " ", "g"))) = ?', [strtolower($normalizedName)]);
    }
}
