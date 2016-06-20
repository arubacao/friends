<?php
/**
 * This file is part of Friends.
 *
 * (c) Christopher Lass <arubacao@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$factory->define(User::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->name,
    ];
});
