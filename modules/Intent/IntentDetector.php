<?php
namespace SL_AI\Modules\Intent;
class IntentDetector {
    public static function analyze($keyword) {
        $keyword = strtolower($keyword);
        // Intent patterns
        $commercial = ['satın al', 'fiyat', 'ucuz', 'indirim', 'kampanya'];
        $informational = ['nasıl', 'nedir', 'ne demek', 'anlamı'];
        $navigational = ['giriş yap', 'iletişim', 'adres', 'telefon'];
        foreach ($commercial as $pattern) {
            if (strpos($keyword, $pattern) !== false) {
                return 'commercial';
            }
        }
        foreach ($informational as $pattern) {
            if (strpos($keyword, $pattern) !== false) {
                return 'informational';
            }
        }
        foreach ($navigational as $pattern) {
            if (strpos($keyword, $pattern) !== false) {
                return 'navigational';
            }
        }
        return 'unknown';
    }
    public static function get_score($keyword) {
        $intent = self::analyze($keyword);
        $scores = [
            'commercial' => 90,
            'informational' => 60,
            'navigational' => 70,
            'unknown' => 50
        ];
        return $scores[$intent] ?? 50;
    }
}
