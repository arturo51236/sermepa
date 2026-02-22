# Redsys - Biblioteca PHP para Pasarela de Pagos

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Total Downloads][ico-downloads]][link-downloads]
[![Run Tests](https://github.com/ssheduardo/sermepa/actions/workflows/ci.yml/badge.svg)][link-workflows]

Biblioteca PHP para integrar la pasarela de pagos Redsys/Sermepa (Santander, Sabadell, LaCaixa, BBVA, etc.)

## Requisitos

- PHP 7.1.3+ a 8.2
- ext-curl
- ext-openssl
- ext-json

## Instalación

```bash
composer require sermepa/sermepa
```

## Inicio Rápido

```php
use Sermepa\Tpv\Tpv;

$redsys = new Tpv();
$redsys->setAmount(2500)           // 25.00€
    ->setOrder('1234AB')
    ->setMerchantcode('999008881')
    ->setCurrency('978')           // Euros
    ->setTransactiontype('0')       // Autorización
    ->setTerminal('1')
    ->setMethod('C')                // Solo tarjeta
    ->setNotification('https://tusitio.com/notificacion')
    ->setUrlOk('https://tusitio.com/ok')
    ->setUrlKo('https://tusitio.com/ko')
    ->setEnvironment('test');       // Entorno de pruebas

$signature = $redsys->generateMerchantSignature('TU_CLAVE_SECRETA');
$redsys->setMerchantSignature($signature);

echo $redsys->createForm();
```

## Entornos Disponibles

| Entorno | Descripción |
|---------|-------------|
| `test` | Pruebas (SIS Sandbox) |
| `live` | Producción |
| `restTest` | API REST Pruebas |
| `restLive` | API REST Producción |

## Métodos de Pago

| Código | Descripción |
|--------|-------------|
| `C` o `T` | Tarjeta |
| `R` | Transferencia |
| `D` | Domiciliación |
| `z` | Bizum |
| `p` | PayPal |
| `N` | Masterpass |
| `xpay` | GooglePay / ApplePay |

## Tipos de Transacción

| Código | Descripción |
|--------|-------------|
| `0` | Autorización |
| `1` | Preautorización |
| `2` | Confirmación de preautorización |
| `3` | Anulación de preautorización |
| `4` | Devolución |
| `5` | Cancelación |
| `7` | Preautorización extendida |
| `8` | Confirmación preautorización extendida |
| `9` | Baja de preautorización extendida |

## Códigos de Respuesta

El banco devuelve un código en `Ds_Response`. Los principales:

| Código | Significado |
|--------|-------------|
| `0000` a `0099` | Pago aprobado |
| `0100` a `0199` | Operatoria OK, verificación CVV obligatoria |
| `0200` | Error de formato |
| `0201` | Error de firma |
| `0204` | Error de datos |
| `0209` | Error de tajeta |
| `0214` | Fecha de caducidad errónea |
| `0215` | Error en importe mínimo (no usado) |
| `0290` | Tarjeta no autorizada |
| `0401` | Error en posición de tarjeta |
| `0404` | Error de configuración de comercio |
| `0501` | Operaciones pendientes |
| `0904` | Comercio no operativo |
| `0912` | Emisor no disponible |
| `9912` | Emisor no disponible |
| `9913` | Error en comunicación |
| `9914` | Fallo al conectar con CA |
| `9919` | Error de cryptograma |
| `9929` | Error de deslinde |
| `9932` | Error de bin |
| `9933` | Error de BS. Cambio de dinámico a estático |
| `9934` | Error de BS. Cambio de estático a dinámico |
| `9951` | Operación de ingreso OK |
| `9952` | Operación de ingreso NO OK |
| `9953` | Devolución OK |
| `9954` | Devolución NO OK |
| `9955` | Anulación OK |
| `9956` | Anulación NO OK |
| `9957` | Ajuste OK |
| `9958` | Ajuste NO OK |
| `9961` | Error en cierre batch |
| `9962` | Error en apertura batch |
| `9963` | Error de operativa |
| `9992` | Petición cancelada |
| `9993` | Operatoria abandonada por el usuario |
| `9995` | Operatoria abandonada - tiempo excedido |
| `9996` | Error de conexión |
| `9997` | Error de timeout |
| `9998` | Error de validación |
| `9999` | Error general |

> **Nota:** Para pagos exitosos, el código debe estar entre `0000` y `0099`. Convertir a entero para comparar: `$DsResponse = (int) $parameters['Ds_Response'];`

## Validar Respuesta del Banco

En tu URL de notificación (`setNotification`), debes validar la respuesta del banco:

```php
use Sermepa\Tpv\Tpv;
use Sermepa\Tpv\TpvException;

try {
    $redsys = new Tpv();
    $key = 'TU_CLAVE_SECRETA';

    // Decodificar parámetros recibidos
    $parameters = $redsys->getMerchantParameters($_POST['Ds_MerchantParameters']);
    $DsResponse = (int) $parameters['Ds_Response'];

    // Validar firma y respuesta
    if ($redsys->check($key, $_POST) && $DsResponse <= 99) {
        // Pago correcto -Ds_Response = 0000 a 0099
        // Aquí: actualizar pedido, enviar email, etc.
    } else {
        // Pago fallido
    }
} catch (TpvException $e) {
    error_log('Error TPV: ' . $e->getMessage());
}
```

## Pago con Tarjeta Guardada (Token)

Guardar la tarjeta del cliente para pagos futuros:

```php
// 1. Crear referencia (primer pago)
$redsys->setIdentifier();  // Sin parámetros - indica "REQUIRED"

// Respuesta del banco contendrá:
// $parameters['Ds_Merchant_Identifier']
// $parameters['Ds_ExpiryDate']
```

```php
// 2. Usar referencia (pagos recurrentes)
$redsys->setIdentifier('IDENTIFICADOR_GUARDADO');
$redsys->setMerchantDirectPayment(true);  // Pago directo sin autenticación 3D Secure
```

## Enviar Datos de Tarjeta Directamente

Puedes enviar los datos de la tarjeta para que el usuario no tenga que introducirlos en la pasarela:

```php
$redsys->setPan('4548812049400004');     // Número de tarjeta
$redsys->setExpiryDate('1228');          // Caducidad (AAMM)
$redsys->setCVV2('123');                 // CVV2
```

> **Nota:** Esta opción requiere que tu comercio tenga autorización del banco para enviar datos de tarjeta directamente.

## API REST (Cobros sin formulario)

Para cobros directos sin redirección del usuario:

```php
use Sermepa\Tpv\Tpv;
use Sermepa\Tpv\TpvException;

try {
    $key = 'TU_CLAVE_SECRETA';

    $redsys = new Tpv();
    $redsys->setAmount(2500)
        ->setOrder('1234AB')
        ->setMerchantcode('999008881')
        ->setCurrency('978')
        ->setTransactiontype('0')
        ->setTerminal('1')
        ->setIdentifier('IDENTIFICADOR_GUARDADO')
        ->setMerchantDirectPayment(true)
        ->setVersion('HMAC_SHA256_V1')
        ->setEnvironment('restTest')
        ->setMerchantCofIni('N');

    $signature = $redsys->generateMerchantSignature($key);
    $redsys->setMerchantSignature($signature);

    $response = json_decode($redsys->send(), true);

    // Verificar error en respuesta
    if (isset($response['errorCode'])) {
        throw new Exception("Error: " . $response['errorCode']);
    }

    // Decodificar respuesta
    $parameters = $redsys->getMerchantParameters($response['Ds_MerchantParameters']);
    $DsResponse = (int) $parameters['Ds_Response'];

    if ($redsys->check($key, $response) && $DsResponse <= 99) {
        // Cobro correcto
    }
} catch (TpvException $e) {
    echo 'Error TPV: ' . $e->getMessage();
}
```

## Redirección Automática

Para redirección automática sin mostrar el botón:

```php
$redsys->executeRedirection();
// Opcional: obtener HTML
$html = $redsys->executeRedirection(true);
```

## Integración con JavaScript de Redsys

La biblioteca incluye un método estático para obtener la ruta del JavaScript de Redsys:

```php
use Sermepa\Tpv\Tpv;

// Obtener URL del script
$jsUrl = Tpv::getJsPath('test', '3');  // Entorno test, versión 3
// https://sis-t.redsys.es:25443/sis/NC/sandbox/redsysV3.js
```

| Versión | Descripción |
|---------|-------------|
| `2` | Redsys Classic |
| `3` | Redsys API (soporte NFC, Apple Pay, Google Pay) |

## Constantes Disponibles

Usa las constantes predefinidas para evitar errores de transcripción:

```php
use Redsys\Merchant\MerchantCurrencies;
use Redsys\Merchant\MerchantTransactionTypes;
use Redsys\Merchant\MerchantConsumerLanguages;
use Redsys\Merchant\MerchantPaymethods;

// Monedas
$redsys->setCurrency(MerchantCurrencies::EUR);      // 978
$redsys->setCurrency(MerchantCurrencies::USD);      // 840
$redsys->setCurrency(MerchantCurrencies::GBP);     // 826

// Tipos de transacción
$redsys->setTransactiontype(MerchantTransactionTypes::AUTHORIZATION);  // 0
$redsys->setTransactiontype(MerchantTransactionTypes::PREAUTHORIZATION);  // 1

// Idiomas
$redsys->setLanguage(MerchantConsumerLanguages::SPANISH);   // 001
$redsys->setLanguage(MerchantConsumerLanguages::ENGLISH);   // 002
$redsys->setLanguage(MerchantConsumerLanguages::CATALAN);    // 003

// Métodos de pago
$redsys->setMethod(MerchantPaymethods::CARD);    // C
$redsys->setMethod(MerchantPaymethods::BIZUM);   // z
```

### Códigos de Moneda

Los códigos ISO 4217. Los más comunes:

- `978` - Euro (EUR)
- `840` - Dólar estadounidense (USD)
- `826` - Libra esterlina (GBP)
- `392` - Yen japonés (JPY)

### Códigos de Idioma

| Código | Idioma |
|--------|--------|
| 001 | Castellano |
| 002 | Inglés |
| 003 | Catalán |
| 004 | Francés |
| 005 | Alemán |
| 006 | Holandés |
| 007 | Italiano |
| 008 | Sueco |
| 009 | Portugués |
| 010 | Valenciano |
| 011 | Polaco |
| 012 | Gallego |
| 013 | Euskera |

## API Reference

### Métodos de Configuración

| Método | Descripción | Requerido |
|--------|-------------|-----------|
| `setAmount(float)` | Importe (se convierte a céntimos) | Sí |
| `setOrder(string)` | Número de pedido (4-12 caracteres, primeros 4 numéricos) | Sí |
| `setMerchantcode(string)` | Código de comercio (FUC) | Sí |
| `setCurrency(string)` | Código de moneda ISO | Sí |
| `setTransactiontype(string)` | Tipo de transacción | Sí |
| `setTerminal(string)` | Número de terminal | Sí |
| `setMethod(string)` | Método de pago | No |
| `setNotification(string)` | URL de notificación (callback) | Recomendado |
| `setUrlOk(string)` | URL si pago exitoso | Recomendado |
| `setUrlKo(string)` | URL si pago fallido | Recomendado |
| `setEnvironment(string)` | Entorno de conexión | No |
| `setVersion(string)` | Versión de firma | No |
| `setTradeName(string)` | Nombre del comercio | No |
| `setTitular(string)` | Titular del pago | No |
| `setProductDescription(string)` | Descripción del producto | No |

### Métodos de Firma y Envío

| Método | Descripción |
|--------|-------------|
| `generateMerchantSignature(string $key)` | Genera firma HMAC-SHA256 |
| `setMerchantSignature(string)` | Asigna la firma calculada |
| `createForm()` | Genera formulario HTML |
| `send()` | Envía petición REST (devuelve JSON) |
| `executeRedirection()` | Redirección automática con JavaScript |

### Métodos de Validación

| Método | Descripción |
|--------|-------------|
| `check(string $key, array $postData)` | Valida firma de respuesta del banco |
| `getMerchantParameters(string)` | Decodifica `Ds_MerchantParameters` |

### Métodos Auxiliares

| Método | Descripción |
|--------|-------------|
| `setNameForm(string)` | Nombre del formulario |
| `setIdForm(string)` | ID del formulario |
| `setAttributesSubmit(...)` | Personalizar botón submit |
| `setLanguage(string)` | Idioma de la pasarela |
| `setParameters(array)` | Parámetros adicionales |
| `setIdentifier(string)` | Referencia de tarjeta guardada |
| `setMerchantDirectPayment(bool)` | Pago directo sin autenticación |
| `setPan(string)` | Número de tarjeta |
| `setExpiryDate(string)` | Caducidad (AAMM) |
| `setCVV2(string)` | Código CVV2 |
| `setMerchantData(string)` | Datos adicionales del comercio |
| `getOrder()` | Obtener número de pedido |
| `getParameters()` | Obtener todos los parámetros |
| `getVersion()` | Obtener versión de firma |
| `getMerchantSignature()` | Obtener firma actual |
| `getEnvironment()` | Obtener URL del entorno |
| `getJsPath(string, string)` | Obtener ruta JS para integración moderna |
| `createOrderNumber(int)` | Generar número de pedido válido |

## Parámetros Avanzados

### Pagos Recurrentes

```php
$redsys->setMerchantCofIni('S');           // Inicio de COF
$redsys->setMerchantCofType('R');          // Tipo: R=Recurrente, I=Cuotas
$redsys->setMerchantCofTxnid('123456789'); // ID de transacción
$redsys->setSumtotal(50000);               // Importe total
$redsys->setChargeExpiryDate('2025-12-31'); // Fecha expiración
$redsys->setDateFrecuency(30);             // Frecuencia en días
```

### Excepción SCA (Strong Customer Authentication)

Algunos bancos requieren parámetros adicionales:

```php
$parameters = ['DS_MERCHANT_EXCEP_SCA' => 'MIT'];
$redsys->setParameters($parameters);
```

## Ejemplos Completos

### Ejemplo Completo: Pago Simple

```php
<?php
use Sermepa\Tpv\Tpv;
use Sermepa\Tpv\TpvException;

try {
    $key = 'sq7HjrUOBfKmC576ILgskD5srU870gJ7';

    $redsys = new Tpv();
    $redsys->setAmount(25.50)
        ->setOrder(date('YmdHis'))  // 20240215120000
        ->setMerchantcode('999008881')
        ->setCurrency('978')
        ->setTransactiontype('0')
        ->setTerminal('1')
        ->setMethod('C')
        ->setNotification('https://tusitio.com/notificacion')
        ->setUrlOk('https://tusitio.com/ok')
        ->setUrlKo('https://tusitio.com/ko')
        ->setVersion('HMAC_SHA256_V1')
        ->setTradeName('Mi Tienda')
        ->setTitular('Cliente Ejemplo')
        ->setProductDescription('Compra en Mi Tienda')
        ->setEnvironment('test');

    $signature = $redsys->generateMerchantSignature($key);
    $redsys->setMerchantSignature($signature);

    echo $redsys->createForm();

} catch (TpvException $e) {
    echo 'Error: ' . $e->getMessage();
}
```

### Ejemplo Completo: Notificación (Callback)

```php
<?php
use Sermepa\Tpv\Tpv;
use Sermepa\Tpv\TpvException;

try {
    $key = 'sq7HjrUOBfKmC576ILgskD5srU870gJ7';

    $redsys = new Tpv();

    // Decodificar parámetros
    $parameters = $redsys->getMerchantParameters($_POST['Ds_MerchantParameters']);

    // Obtener código de respuesta
    $DsResponse = (int) $parameters['Ds_Response'];
    $DsOrder = $parameters['Ds_Order'];

    // Validar firma y respuesta
    if ($redsys->check($key, $_POST)) {
        if ($DsResponse >= 0 && $DsResponse <= 99) {
            // Pago aprobado
            // Aquí: actualizar pedido en BDD, enviar confirmación, etc.
            error_log("Pago exitoso - Pedido: $DsOrder, Respuesta: $DsResponse");
        } else {
            // Pago denegado
            error_log("Pago denegado - Pedido: $DsOrder, Respuesta: $DsResponse");
        }
    } else {
        // Firma inválida - posible fraude
        error_log("Firma inválida - Pedido: $DsOrder");
    }

} catch (TpvException $e) {
    error_log('Error TPV: ' . $e->getMessage());
    http_response_code(500);
}
```

## Changelog

Ver [CHANGELOG.md](CHANGELOG.md) para más detalles.

## Licencia

MIT - Ver [LICENSE.md](LICENSE.md)

---

## Contribuidores

- [Eduardo D](mailto:ssh.eduardo@gmail.com) - Autor original
- [jaumecornado](https://github.com/jaumecornado) - Redirección automática
- [markitosgv](https://github.com/markitosgv) - Validación de respuesta

## Donación

¿Te gustaría apoyar este proyecto? ¡Gracias por tu aprecio!

[![Donar con PayPal](https://www.paypalobjects.com/es_ES/ES/i/btn/btn_donate_LG.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=ssh%2eeduardo%40gmail%2ecom&lc=ES&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donate_LG%2egif%3aNonHosted)

## Support

¿Necesitas ayuda? [Abre un issue](https://github.com/ssheduardo/sermepa/issues)

[ico-version]: https://img.shields.io/packagist/v/sermepa/sermepa.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/sermepa/sermepa.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/sermepa/sermepa
[link-downloads]: https://packagist.org/packages/sermepa/sermepa
[link-author]: https://github.com/ssheduardo
[link-workflows]: https://github.com/ssheduardo/sermepa/actions/workflows/ci.yml
