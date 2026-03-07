<?php
declare(strict_types=1);

namespace Redsys\Merchant;

use ReflectionClass;

/**
 * InSite Error codes for Redsys payment forms
 * 
 * Error codes returned by the InSite JavaScript validation.
 */
class MerchantInsiteError
{
    public const MSG_01 = 'msg1';   // Ha de rellenar los datos de la tarjeta
    public const MSG_02 = 'msg2';   // La tarjeta es obligatoria
    public const MSG_03 = 'msg3';   // La tarjeta ha de ser numérica
    public const MSG_04 = 'msg4';   // La tarjeta no puede ser negativa
    public const MSG_05 = 'msg5';   // El mes de caducidad de la tarjeta es obligatorio
    public const MSG_06 = 'msg6';   // El mes de caducidad de la tarjeta ha de ser numérico
    public const MSG_07 = 'msg7';   // El mes de caducidad de la tarjeta es incorrecto
    public const MSG_08 = 'msg8';   // El año de caducidad de la tarjeta es obligatorio
    public const MSG_09 = 'msg9';   // El año de caducidad de la tarjeta ha de ser numérico
    public const MSG_10 = 'msg10'; // El año de caducidad de la tarjeta no puede ser negativo
    public const MSG_11 = 'msg11'; // El código de seguridad de la tarjeta no tiene la longitud correcta
    public const MSG_12 = 'msg12'; // El código de seguridad de la tarjeta ha de ser numérico
    public const MSG_13 = 'msg13'; // El código de seguridad de la tarjeta no puede ser negativo
    public const MSG_14 = 'msg14'; // El código de seguridad no es necesario para su tarjeta
    public const MSG_15 = 'msg15'; // La longitud de la tarjeta no es correcta
    public const MSG_16 = 'msg16'; // Debe introducir un número de tarjeta válido (sin espacios ni guiones)
    public const MSG_17 = 'msg17'; // Validación incorrecta por parte del comercio
    public const MSG_18 = 'msg18'; // Error de inicialización de dominio

    /**
     * Error descriptions in Spanish
     */
    public const DESCRIPTIONS = [
        'msg1' => 'Ha de rellenar los datos de la tarjeta',
        'msg2' => 'La tarjeta es obligatoria',
        'msg3' => 'La tarjeta ha de ser numérica',
        'msg4' => 'La tarjeta no puede ser negativa',
        'msg5' => 'El mes de caducidad de la tarjeta es obligatorio',
        'msg6' => 'El mes de caducidad de la tarjeta ha de ser numérico',
        'msg7' => 'El mes de caducidad de la tarjeta es incorrecto',
        'msg8' => 'El año de caducidad de la tarjeta es obligatorio',
        'msg9' => 'El año de caducidad de la tarjeta ha de ser numérico',
        'msg10' => 'El año de caducidad de la tarjeta no puede ser negativo',
        'msg11' => 'El código de seguridad de la tarjeta no tiene la longitud correcta',
        'msg12' => 'El código de seguridad de la tarjeta ha de ser numérico',
        'msg13' => 'El código de seguridad de la tarjeta no puede ser negativo',
        'msg14' => 'El código de seguridad no es necesario para su tarjeta',
        'msg15' => 'La longitud de la tarjeta no es correcta',
        'msg16' => 'Debe introducir un número de tarjeta válido (sin espacios ni guiones)',
        'msg17' => 'Validación incorrecta por parte del comercio',
        'msg18' => 'Error de inicialización de dominio',
    ];

    /**
     * Get error description
     *
     * @param string $code Error code (msg1, msg2, etc.)
     * @return string Error description in Spanish
     */
    public static function getDescription(string $code): string
    {
        return self::DESCRIPTIONS[$code] ?? 'Error desconocido';
    }
}