<?php
declare(strict_types=1);

namespace Redsys\Merchant;

use ReflectionClass;

/**
 * InSite Style constants for Redsys payment forms
 * 
 * Style options for InSite forms.
 */
class MerchantInsiteStyle
{
    // InSite form styles
    public const INLINE = 'inline';
    public const TWO_ROWS = 'twoRows';
    
    /**
     * Check if a style is valid
     *
     * @param string $value Style code
     * @return bool
     */
    public static function isValid(string $value): bool
    {
        return in_array($value, [self::INLINE, self::TWO_ROWS]);
    }
}