<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];
    const DEFAULTS = [
        'name' => 'GEN AURA', 'ar' => 'جين أورا', 'top' => '3 Days Delivery All Over BD',
        'title' => 'Wear Your Aura. Dress With Confidence.',
        'sub' => 'Premium T-shirts, shirts, pants and panjabis, made for everyday style and comfort.',
        'wa' => '8801700000000', 'ship' => '60', 'cats' => 'T-Shirt,Shirt,Pant,Panjabi',
        'fb' => 'https://www.facebook.com/p/GEN-AURA-61579509689159/',
        'hero_image' => '',
    ];
    protected static ?array $cache = null;
    public static function all_(): array
    {
        if (self::$cache === null) {
            try { self::$cache = array_merge(self::DEFAULTS, self::query()->pluck('value', 'key')->all()); }
            catch (\Throwable $e) { self::$cache = self::DEFAULTS; }
        }
        return self::$cache;
    }
    public static function put(array $d): void
    {
        foreach ($d as $k => $v) self::updateOrCreate(['key' => $k], ['value' => (string) $v]);
        self::$cache = null;
    }
}
