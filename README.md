![friends-img](http://www.arubacao.com/friends.png)

# Friends (Laravel 5 Package)

[![Build Status](https://img.shields.io/travis/arubacao/friends/master.svg?style=flat-square)](https://travis-ci.org/arubacao/friends)
[![Latest Version](https://img.shields.io/packagist/v/arubacao/friends.svg?style=flat-square)](https://packagist.org/packages/arubacao/friends)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![SensioLabsInsight](https://img.shields.io/sensiolabs/i/9c0e986c-44e0-417d-bd8c-96ea170bcb50.svg?style=flat-square)](https://insight.sensiolabs.com/projects/9c0e986c-44e0-417d-bd8c-96ea170bcb50)
[![Quality Score](https://img.shields.io/scrutinizer/g/arubacao/friends.svg?style=flat-square)](https://scrutinizer-ci.com/g/arubacao/friends)

## Organise Friends and Relationships Between Users in Laravel and Lumen.
#### Friends provides everything you need to easily implement your own Facebook like Friend System.

## Users can:
- Send Friend Requests
- Accept Friend Requests
- Deny Friend Requests
- Delete Friends

## Contents

- [Installation](#installation)  
- [Configuration](#configuration)  
- [Usage](#usage)  
    - [Friend Requests](#friend-requests)  
            - [Send Friend Request](#send-friend-request)  
            - [Accept Friend Request](#accept-friend-request)  
            - [Deny Friend Request](#deny-friend-request)  
    - [My Friends](#my-friends)  
            - [Is Friend With](#is-friend-with)  
            - [Delete Friend](#delete-friend)  
            - [Retrieve Friends](#retrieve-friends)  
            - [Retrieve Incoming Friends](#retrieve-incoming-friends)  
            - [Retrieve Any Friends](#retrieve-any-friends)  
    - [Relationships](#relationships)  
            - [Has Relationship With](#has-relationship-with)  
            - [Get Relationship With](#get-relationship-with)  
            - [Has Pending Request From](#has-pending-request-from)  
    - [Query Users Including Relationships](#query-users)  
- [License](#license)  

<a name="installation" />
## Installation

## For Laravel 5.*

### Pull in Package with Composer
`composer require arubacao/friends`

### Register Service Provider
Include the service provider inside `config/app.php`.

```php
'providers' => [
    ...
    Arubacao\Friends\FriendsServiceProvider::class,
    ...
];
```

### Run Migrations
Publish the migration and migrate the database

```bash
php artisan vendor:publish --provider="Arubacao\Friends\FriendsServiceProvider"
php artisan migrate
```

After the migration, 1 new table will be created:

- `friends` &mdash; stores [relationships/friendships](http://laravel.com/docs/5.2/eloquent-relationships#many-to-many) between Users

The `vendor:publish` command will also create a `friends.php` file in your config directory.  
The default configuration should work just fine for most applications.  
Otherwise check out [Configuration](#configuration).

### Prepare User Model
Include `Friendable` Trait in `User` Model
```php

use Arubacao\Friends\Traits\Friendable;

class User extends Model
{
    use Friendable; // Add this trait to your model
    ...
}
```

**And you are ready to go.**

<a name="configuration"/>
## Configuration

### Configuration File `friends.php` _(Optional)_ 
Find `friends.php` in your config folder. Make sure you published the package beforehand.

- `user_model` &mdash; This is the applications `User` model used by Friends.
- `users_table` &mdash; This is the applications `users` table name used by Friends.

<a name="usage" />
## Usage

<a name="friend-requests" />
### Friend Requests

<a name="send-friend-request" />
#### Send Friend Request
```php
$user->sendFriendRequestTo($recipient);
```
`$user` must be instance of `User`  
`$recipient` must be instance of `User`, `User` array or integer (User id)  

<a name="accept-friend-request" />
#### Accept Friend Request
```php
$user->acceptFriendRequestFrom($sender);
```
`$user` must be instance of `User`  
`$sender` must be instance of `User`, `User` array or integer (User id)  

<a name="deny-friend-request" />
#### Deny Friend Request
```php
$user->denyFriendRequestFrom($sender);
```
`$user` must be instance of `User`  
`$sender` must be instance of `User`, `User` array or integer (User id)  

<a name="my-friends" />
### My Friends

<a name="delete-friend" />
#### Delete Friend
```php
$user->deleteFriend($douchebag);
```
`$user` must be instance of `User`  
`$douchebag` must be instance of `User`, `User` array or integer (User id)  

<a name="retrieve-friends" />
#### Retrieve Friends
- Get all friends of a user
- `status` is always `1` *ACCEPTED*

```php
$friends = $user->friends();
```
`$user` must be instance of `User`

`$friends`: 
```json
[{
	"id": 3,
	"name": "harri121",
	"created_at": "2016-06-18 19:08:45",
	"updated_at": "2016-06-18 19:08:45",
	"pivot": {
		"sender_id": 1,
		"recipient_id": 3,
		"created_at": "2016-06-19 19:53:27",
		"updated_at": "2016-06-19 22:56:40",
		"status": 1
	}
}]
```

<a name="retrieve-incoming-friends" />
#### Retrieve Incoming Friends
- Get all users who send friend request to `$user`
- `status` is always `0` *PENDING* 
- `recipient_id` is always `id` of `$user`

```php
$friends = $user->incoming_friends();
```
`$user` must be instance of `User`

`$friends`: 
```json
[{
	"id": 3,
	"name": "ejoebstl",
	"created_at": "2016-06-18 19:08:45",
	"updated_at": "2016-06-18 19:08:45",
	"pivot": {
		"sender_id": 3,
		"recipient_id": 1,
		"created_at": "2016-06-19 19:53:27",
		"updated_at": "2016-06-19 22:56:40",
		"status": 0
	}
}]
```

<a name="retrieve-any-friends" />
#### Retrieve Any Friends
**Remember:**
> Just like in the real life a 'friend' or 'friendship' can be anything, also negative ;)

- Get all users who have any kind of friendship/relationship with `$user`

```php
$friends = $user->any_friends();
```
`$user` must be instance of `User`

`$friends`: 
```json
[{
	"id": 3,
	"name": "harri121",
	"created_at": "2016-06-18 19:08:45",
	"updated_at": "2016-06-18 19:08:45",
	"pivot": {
		"sender_id": 1,
		"recipient_id": 3,
		"created_at": "2016-06-19 19:53:27",
		"updated_at": "2016-06-19 22:56:40",
		"status": 1
	}
},
{
	"id": 2,
	"name": "ejoebstl",
	"created_at": "2016-06-18 19:08:41",
	"updated_at": "2016-06-18 19:08:41",
	"pivot": {
		"recipient_id": 1,
		"sender_id": 2,
		"created_at": "2016-06-19 19:53:27",
		"updated_at": "2016-06-19 19:53:27",
		"status": 0
	}
}]
```

<a name="relationships" />
### Relationships

<a name="has-relationship-with" />
#### Has Relationship With
```php
$user->hasRelationshipWith($person, $status);
```
`$user` must be instance of `User`  
`$person` must be instance of `User`, `User` array or integer (User id)  
`$status` must be array of integers (`Status`)  

<a name="get-relationship-with" />
#### Get Relationship With
```php
$user->getRelationshipWith($person, $status);
```
`$user` must be instance of `User`  
`$person` must be instance of `User`, `User` array or integer (User id)  
`$status` must be array of integers (`Status`)  

<a name="has-pending-request-from" />
#### Has Pending Request From
```php
$user->hasPendingRequestFrom($person);
```
`$user` must be instance of `User`  
`$person` must be instance of `User`, `User` array or integer (User id)  

<a name="query-users" />
### Query Users Including Relationships

```php
$users = \App\User::whereIn('id', [2,3,4])
      ->includeRelationshipsWith(1)
      ->get();
```

`$users`: 
```json
[{
	"id": 2,
	"name": "ejoebstl",
	"created_at": "2016-06-18 19:08:41",
	"updated_at": "2016-06-18 19:08:41",
	"friends_i_am_sender": [{
		"id": 1,
		"name": "arubacao",
		"created_at": "2016-06-18 19:08:35",
		"updated_at": "2016-06-18 19:08:35",
		"pivot": {
			"sender_id": 2,
			"recipient_id": 1,
			"created_at": "2016-06-19 19:53:27",
			"updated_at": "2016-06-19 19:53:27",
			"status": 0
		}
	}],
	"friends_i_am_recipient": []
},
{
	"id": 3,
	"name": "harri121",
	"created_at": "2016-06-18 19:08:45",
	"updated_at": "2016-06-18 19:08:45",
	"friends_i_am_sender": [],
	"friends_i_am_recipient": [{
		"id": 1,
		"name": "arubacao",
		"created_at": "2016-06-18 19:08:35",
		"updated_at": "2016-06-18 19:08:35",
		"pivot": {
			"recipient_id": 3,
			"sender_id": 1,
			"created_at": "2016-06-19 19:53:27",
			"updated_at": "2016-06-19 22:56:40",
			"status": 1
		}
	}]
},
{
	"id": 4,
	"name": "random_user",
	"created_at": "2016-06-19 19:55:25",
	"updated_at": "2016-06-19 19:55:25",
	"friends_i_am_sender": [],
	"friends_i_am_recipient": []
}]
```


<a name="license" />
## License

Friends is free software distributed under the terms of the MIT license.

[![Analytics](https://ga-beacon.appspot.com/UA-77737156-1/readme?pixel)](https://github.com/arubacao/friends)
