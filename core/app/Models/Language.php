<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    protected $fillable = ['name', 'code', 'is_default', 'image', 'is_rtl'];
    
    protected $casts = [
        'is_rtl' => 'boolean',
    ];
    
    /**
     * Check if the language is RTL (Right-to-Left)
     */
    public function isRtl(): bool
    {
        return (bool) $this->is_rtl;
    }
    
    /**
     * Get RTL languages codes
     */
    public static function rtlLanguages(): array
    {
        return ['fa', 'ar', 'he', 'ur', 'fa-IR', 'ar-SA', 'ps', 'ku'];
    }
    
    /**
     * Check if a language code is RTL
     */
    public static function isRtlLanguage(string $code): bool
    {
        return in_array($code, self::rtlLanguages());
    }
}
