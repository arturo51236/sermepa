<?php
  declare(strict_types=1);

	namespace Redsys\Merchant;

	use ReflectionClass;

	class MerchantEnvironment {
		public const LIVE = 'live';
		public const TEST = 'test';
		public const REST_LIVE = 'restLive';
		public const REST_TEST = 'restTest';
		public const START_REST_LIVE = 'startRequestRestLive';
		public const START_REST_TEST = 'startRequestRestTest';
		public const MANAGE_REQUEST_REST_LIVE = 'manageRequestRestLive';
		public const MANAGE_REQUEST_REST_TEST = 'manageRequestRestTest';

		// InSite environments
		public const INSITE_SANDBOX = 'insiteSandbox';
		public const INSITE_LIVE = 'insiteLive';
		public const INSITE_REST_SANDBOX = 'insiteRestSandbox';
		public const INSITE_REST_LIVE = 'insiteRestLive';

		// InSite JS URLs
		public const INSITE_JS_SANDBOX = 'https://sis-t.redsys.es:25443/sis/NC/sandbox/redsysV3.js';
		public const INSITE_JS_LIVE = 'https://sis.redsys.es/sis/NC/redsysV3.js';

		public static function isValid (string $value) : bool {
			return in_array($value, (new ReflectionClass(self::class))->getConstants());
		}
	}