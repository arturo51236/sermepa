<?php
declare(strict_types=1);

namespace Redsys\Merchant;

use ReflectionClass;

/**
 * InSite Language codes for Redsys payment forms
 * 
 * Both SIS codes and ISO 639-1 codes are supported.
 */
class MerchantInsiteLanguage
{
    // SIS Language Codes
    public const SPANISH = '1';
    public const ENGLISH = '2';
    public const CATALAN = '3';
    public const FRENCH = '4';
    public const GERMAN = '5';
    public const DUTCH = '6';
    public const ITALIAN = '7';
    public const SWEDISH = '8';
    public const PORTUGUESE = '9';
    public const VALENCIAN = '10';
    public const POLISH = '11';
    public const GALICIAN = '12';
    public const BASQUE = '13';
    public const BULGARIAN = '100';
    public const CHINESE = '156';
    public const CROATIAN = '191';
    public const CZECH = '203';
    public const DANISH = '208';
    public const ESTONIAN = '233';
    public const FINNISH = '246';
    public const GREEK = '300';
    public const HUNGARIAN = '348';
    public const HINDI = '356';
    public const JAPANESE = '392';
    public const KOREAN = '410';
    public const LATVIAN = '428';
    public const LITHUANIAN = '440';
    public const MALTESE = '470';
    public const ROMANIAN = '642';
    public const RUSSIAN = '643';
    public const ARABIC = '682';
    public const SLOVAK = '703';
    public const SLOVENIAN = '705';
    public const TURKISH = '792';

    // ISO 639-1 Codes (also supported)
    public const ISO_ES = 'ES';
    public const ISO_EN = 'EN';
    public const ISO_CA = 'CA';
    public const ISO_FR = 'FR';
    public const ISO_DE = 'DE';
    public const ISO_NL = 'NL';
    public const ISO_IT = 'IT';
    public const ISO_SV = 'SV';
    public const ISO_PT = 'PT';
    public const ISO_VA = 'VA';
    public const ISO_PL = 'PL';
    public const ISO_GL = 'GL';
    public const ISO_EU = 'EU';
    public const ISO_BG = 'BG';
    public const ISO_ZH = 'ZH';
    public const ISO_HR = 'HR';
    public const ISO_CS = 'CS';
    public const ISO_DA = 'DA';
    public const ISO_ET = 'ET';
    public const ISO_FI = 'FI';
    public const ISO_EL = 'EL';
    public const ISO_HU = 'HU';
    public const ISO_HI = 'HI';
    public const ISO_JA = 'JA';
    public const ISO_KO = 'KO';
    public const ISO_LV = 'LV';
    public const ISO_LT = 'LT';
    public const ISO_MT = 'MT';
    public const ISO_RO = 'RO';
    public const ISO_RU = 'RU';
    public const ISO_AR = 'AR';
    public const ISO_SK = 'SK';
    public const ISO_SL = 'SL';
    public const ISO_TR = 'TR';

    /**
     * Check if a language code is valid
     *
     * @param string $value Language code (SIS or ISO 639-1)
     * @return bool
     */
    public static function isValid(string $value): bool
    {
        return in_array($value, (new ReflectionClass(self::class))->getConstants());
    }

    /**
     * Get all valid language codes
     *
     * @return array
     */
    public static function getAll(): array
    {
        return (new ReflectionClass(self::class))->getConstants();
    }
}