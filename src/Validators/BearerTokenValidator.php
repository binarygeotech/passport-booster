<?php

namespace BGS\PassportBooster\Validators;

use BadMethodCallException;
use InvalidArgumentException;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\ValidationData;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\AuthorizationValidators\BearerTokenValidator as
BaseBearerTokenValidator;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use BGS\PassportBooster\Bridge\AccessTokenRepository;
use BGS\PassportBooster\PassportBooster;

class BearerTokenValidator extends BaseBearerTokenValidator
{

    /**
     * @param AccessTokenRepository $accessTokenRepository
     */
    public function __construct(AccessTokenRepository $accessTokenRepository)
    {
        $this->accessTokenRepository = $accessTokenRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthorization(ServerRequestInterface $request)
    {
        if ($request->hasHeader('authorization') === false) {
            throw OAuthServerException::accessDenied('Missing "Authorization" header');
        }

        $header = $request->getHeader('authorization');
        $jwt = trim((string) preg_replace('/^(?:\s+)?Bearer\s/', '', $header[0]));

        try {
            // Attempt to parse and validate the JWT
            $token = (new Parser())->parse($jwt);
            try {
                if ($token->verify(new Sha256(), $this->publicKey->getKeyPath()) === false) {
                    throw OAuthServerException::accessDenied('Access token could not be verified');
                }
            } catch (BadMethodCallException $exception) {
                throw OAuthServerException::accessDenied('Access token is not signed', null, $exception);
            }

            // Ensure access token hasn't expired
            $data = new ValidationData();
            $data->setCurrentTime(time());

            if ($token->validate($data) === false) {
                throw OAuthServerException::accessDenied('Access token is invalid');
            }

            // Check if token has been revoked
            if ($this->accessTokenRepository->isAccessTokenRevoked($token->getClaim('jti'))) {
                throw OAuthServerException::accessDenied('Access token has been revoked');
            }


            $postValidator = PassportBooster::getPostValidator();

            if (is_callable($postValidator)) {
                $validation = call_user_func($postValidator, $token);

                if (!$validation['status']) {
                    $message = $validation['message'] ?? 'Access token authentication failed';
                    throw OAuthServerException::accessDenied($message);
                }
            }

            if (PassportBooster::isMultiGuard()) {
                if (!$token->getClaim('guard')) {
                    throw OAuthServerException::accessDenied('Authentication Guard is Missing');
                } else {
                    if (PassportBooster::getGuard() !== $token->getClaim('guard')) {
                        throw OAuthServerException::accessDenied('Authentication failed');
                    }
                }
            }

            $attributes = [
                'access_token_id' => $token->getClaim('jti'),
                'client_id' => $token->getClaim('aud'),
                'user_id' => $token->getClaim('sub'),
                'scopes' => $token->getClaim('scopes'),
            ];

            $allClaims = $token->getClaims();

            foreach ($allClaims as $claim => $data) {
                if (!in_array($claim, ['jti', 'aud', 'sub', 'scopes', 'iat', 'exp', 'nbf'])) {
                    $attributes[$claim] = $token->getClaim($claim);
                }
            }

            foreach ($attributes as $attribute => $val) {
                $request = $request->withAttribute('oauth_' . $attribute, $val);
            }

            return $request;

            // Return the request with additional attributes
            // return $request
            //     ->withAttribute('oauth_access_token_id', $token->getClaim('jti'))
            //     ->withAttribute('oauth_client_id', $token->getClaim('aud'))
            //     ->withAttribute('oauth_user_id', $token->getClaim('sub'))
            //     ->withAttribute('oauth_scopes', $token->getClaim('scopes'))
            //     ->withAttribute('oauth_guard', $token->getClaim('guard'));
        } catch (InvalidArgumentException $exception) {
            // JWT couldn't be parsed so return the request as is
            throw OAuthServerException::accessDenied($exception->getMessage(), null, $exception);
        } catch (RuntimeException $exception) {
            //JWR couldn't be parsed so return the request as is
            throw OAuthServerException::accessDenied('Error while decoding to JSON', null, $exception);
        }
    }
}
