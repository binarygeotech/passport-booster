# Passport Booster
This package extends Laravel Passport functionalities by enabling you to use multiple User Models (Authentication Guard).
A middleware which enables multi-auth is included and registered automatically as `passport.guard`.

#Installation
`composer require binarygeotech/passport-booster`

# Setup
- Add `passport.guard` to Laravel Passport's routes setup in the boot method of `AuthServiceProvider.php` file.
```
use Laravel\Passport\Passport; // Import Laravel Passport
use BGS\PassportBooster\PassportBooster; // Import Passport Booster

public function boot()
{
	...
	Passport::routes(
		null,
		[
			'middleware' => [
				'passport.guard'
			]
		]
	);
	
	PassportBooster::enableMultiGuard(true); // Add this line to enable Multiple Authentication Guard feature.
	
	...
}
```

# Usage

- Setup your guards in your `config/auth.php`
```
'guards' => [
    ...
    'admin' => [
        'driver' => 'passport',
        'provider' => 'admins',
        'hash' => false,
    ],
	'clients' => [
        'driver' => 'passport',
        'provider' => 'clients',
        'hash' => false,
    ],
],

'providers' => [
    ...
    'admins' => [
        'driver' => 'eloquent',
        'model' => App\Admin::class,
    ],
	'clients' => [
        'driver' => 'eloquent',
        'model' => App\Clients::class,
    ],
],

```

- Add wrap your api routes in a group with `passport.guard:{guard}` middleware

Example below
```
	// Admin Route
	Route::group(
		[
			'prefix' => 'administrator',
			'middleware' => [
				'passport.guard:admin'
			]
		],
		function () {
			/*
				Using the proxy token generation method as documented here https://laravel.com/docs/7.x/passport#requesting-password-grant-tokens,
				use the route below.
			*/
			Route::post('/access/login', 'Auth\AdminLoginController@login')
				->name('api.admin.login');

			// Define other routes by applying the guard middleware
			Route::group(
				[
					'middleware' => [
						'auth:admin',
					],
				],
				function () {
					Route::get('profile', 'Auth\AdminLoginController@profile')->name('api.admin.profile');
					
				}
			
			)
		}
	);
	
	// Client Route
	Route::group(
		[
			'prefix' => 'client',
			'middleware' => [
				'passport.guard:client'
			]
		],
		function () {
			/*
				Using the proxy token generation method as documented here https://laravel.com/docs/7.x/passport#requesting-password-grant-tokens,
				use the route below.
			*/
			Route::post('/access/login', 'Auth\ClientLoginController@login')
				->name('api.client.login');

			// Define other routes by applying the guard middleware
			Route::group(
				[
					'middleware' => [
						'auth:client',
					],
				],
				function () {
					Route::get('profile', 'Auth\ClientLoginController@profile')->name('api.client.profile');
					
				}
			
			)
		}
	);
```

- Add `guard` parameter to all your token requests (`oauth/token` or using guzzle)

Guzzle Example (https://laravel.com/docs/7.x/passport#requesting-password-grant-tokens)
```
$http = new GuzzleHttp\Client;

$response = $http->post('http://your-app.com/oauth/token', [
    'form_params' => [
        'grant_type' => 'password',
        'client_id' => 'client-id',
        'client_secret' => 'client-secret',
        'username' => 'taylor@laravel.com',
        'password' => 'my-password',
        'scope' => '',
		'guard' => 'admin'
    ],
]);

return json_decode((string) $response->getBody(), true);
```

# Additional Customisation

- Extending class files, publish the configration file `php artisan vendor:publish --tag=passport-booster-config`

```
<?php

return [
    'client_repository' => \Laravel\Passport\ClientRepository::class,
    'client_repository_bridge' => \Laravel\Passport\Bridge\ClientRepository::class,
    'access_token_repository' => \BGS\PassportBooster\Bridge\AccessTokenRepository::class,
    'bearer_token_validator' => \BGS\PassportBooster\Validators\BearerTokenValidator::class,
    'token_guard' => \BGS\PassportBooster\Guards\TokenGuard::class,
    'token_repository' => \Laravel\Passport\TokenRepository::class,
];
```
All the files in the configuration files can be replaced with proper implementation of the classes or parent classes

# Credits
Thanks to Mohamed Hamed for his package https://github.com/hamedov93/passport-multiauth,

# Notes
For Custom Grants please use Mohamed Hamed's passport-multiauth
`composer require hamedov/passport-multiauth`

# Issues
Kindly go to the issues section to open an issue.

# Contribution
You can contribute to this package, you might spot a missing feature, kindly notify me and/or open a pull request.

# License
Released under the Mit license, see [LICENSE](https://github.com/hamedov93/passport-multiauth/blob/master/LICENSE)