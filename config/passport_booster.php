<?php

return [
    'client_repository' => \Laravel\Passport\ClientRepository::class,
    'client_repository_bridge' => \Laravel\Passport\Bridge\ClientRepository::class,
    'access_token_repository' => \BGS\PassportBooster\Bridge\AccessTokenRepository::class,
    'bearer_token_validator' => \BGS\PassportBooster\Validators\BearerTokenValidator::class,
    'token_guard' => \BGS\PassportBooster\Guards\TokenGuard::class,
    'token_repository' => \Laravel\Passport\TokenRepository::class,
];
