<?php

namespace BGS\PassportBooster;

use Illuminate\Http\Request;
use PhpParser\Node\Expr\Cast\Array_;

class PassportBooster
{
    // protected $postTokenValidator;

    protected static $claimData = [];
    protected static $tokenPostValidator;
    protected static $enableMultiGuard;
    protected static $guard = 'api';

    public static function setPostTokenValidator(callable $validatorFunction)
    {
        // $this->$postTokenValidator = $validatorFunction;

        self::$tokenPostValidator = $validatorFunction;
    }

    public static function setTokenClaims(/* Array|Callable */$data)
    {
        if (is_callable($data)) {
            $data = call_user_func($data, request());
        }

        if ($data) {
            self::$claimData = $data;
        }
    }

    public static function getClaimData(Request $request, int $userId): array
    {
        $isMulti = self::isMultiGuard();

        $claims = [];

        if ($isMulti) {
            $guard = $request->get('guard');

            $claims['guard'] = $guard;
        }
        // return self::$claimData;
        return $claims;
    }

    public static function getPostValidator()
    {
        return self::$tokenPostValidator;
    }

    public static function enableMultiGuard(bool $value)
    {
        self::$enableMultiGuard = $value;
    }

    public static function isMultiGuard()
    {
        return self::$enableMultiGuard ?? false;
    }

    public static function setGuard($guard)
    {
        self::$guard = $guard;
    }

    public static function getGuard()
    {
        return self::$guard;
    }
}
