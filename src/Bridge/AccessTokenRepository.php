<?php

namespace BGS\PassportBooster\Bridge;

use BGS\PassportBooster\Bridge\AccessToken;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use Laravel\Passport\Bridge\AccessTokenRepository as BaseRepository;

class AccessTokenRepository extends BaseRepository
{
    /**
     * {@inheritdoc}
     */
    public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null)
    {
        // Using modified AccessToken to override Token Converter
        return new AccessToken($userIdentifier, $scopes, $clientEntity);
    }
}
