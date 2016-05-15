![friendships-img](http://www.arubacao.com/laravel-friendships.jpeg)

# Laravel Friendships

[![Build Status](https://img.shields.io/travis/arubacao/laravel-friendships/master.svg?style=flat-square)](https://travis-ci.org/arubacao/laravel-friendships)
[![Latest Version](https://img.shields.io/packagist/v/arubacao/laravel-friendships.svg?style=flat-square)](https://packagist.org/packages/arubacao/laravel-friendships)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![SensioLabsInsight](https://img.shields.io/sensiolabs/i/9c0e986c-44e0-417d-bd8c-96ea170bcb50.svg?style=flat-square)](https://insight.sensiolabs.com/projects/9c0e986c-44e0-417d-bd8c-96ea170bcb50)
[![Quality Score](https://img.shields.io/scrutinizer/g/arubacao/laravel-friendships.svg?style=flat-square)](https://scrutinizer-ci.com/g/arubacao/laravel-friendships)

## Organise Friendships (Relationships) Between Models (Users) in Laravel and Lumen.
#### Friendships provides everything you need to easily implement your own Facebook like Friend System.

## Models can:
- Send Friend Requests
- Accept Friend Requests
- Deny Friend Requests
- Block Another Model

## Installation

First, install the package through Composer.

```php
composer require arubacao/laravel-friendships
```

Then include the service provider inside `config/app.php`.

```php
'providers' => [
    ...
    Arubacao\Friendships\FriendshipsServiceProvider::class,
    ...
];
```
Lastly you need to publish the migration and migrate the database

```
php artisan vendor:publish --provider="Arubacao\Friendships\FriendshipsServiceProvider" && php artisan migrate
```
## Setup a Model
```php
use Arubacao\Friendships\Traits\Friendable;
class User extends Model
{
    use Friendable;
    ...
}
```

## How to use
[Check the Test file to see the package in action](https://github.com/arubacao/laravel-friendships/blob/master/tests/FriendshipsTest.php)

#### Send a Friend Request
```php
$user->sendFriendshipRequestTo($recipient);
```

#### Accept a Friend Request
```php
$user->acceptFriendshipRequestFrom($recipient);
```

#### Deny a Friend Request
```php
$user->denyFriendshipRequestFrom($recipient);
```

#### Remove Friend
```php
$user->removeFriendshipWith($recipient);
```

#### Block a Model
```php
$user->blockModel($recipient);
```

#### Unblock a Model
```php
$user->unblockModel($recipient);
```

#### Check if Model is Friend with another Model
```php
$user->hasAcceptedFriendshipWith($recipient);
```

#### Check if Model has a pending friend request from another Model
```php
$user->hasFriendshipRequestFrom($recipient);
```

#### Check if Model has blocked another Model
```php
$user->hasBlocked($recipient);
```

#### Check if Model is blocked by another Model
```php
$user->isBlockedBy($recipient);
```

#### Get a single friendship
```php
$user->getFriendship($recipient);
```

#### Get a list of all Friendships
```php
$user->getAllFriendships();
```

#### Get a list of pending Friendships
```php
$user->getPendingFriendships();
```

#### Get a list of accepted Friendships
```php
$user->getAcceptedFriendships();
```

#### Get a list of denied Friendships
```php
$user->getDeniedFriendships();
```

#### Get a list of blocked Friendships
```php
$user->getBlockedFriendships();
```

#### Get a list of pending Friend Requests
```php
$user->getReceivedFriendshipRequests();
```

#### Get the number of Friends
```php
$user->getFriendsCount();
```


### To get a collection of friend models (ex. User) you can use the following methods
#### Get Friends
```php
$user->getFriends();
```

#### Get Friends Paginated
```php
$user->getFriends($perPage = 20);
```

#### Get Friends of Friends
```php
$user->getFriendsOfFriends($perPage = 20);
```

[![Analytics](https://ga-beacon.appspot.com/UA-77737156-1/readme?pixel)](https://github.com/arubacao/laravel-friendships)
