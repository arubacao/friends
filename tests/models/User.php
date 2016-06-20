<?php
/**
 * This file is part of Friends.
 *
 * (c) Christopher Lass <arubacao@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Foundation\Auth\User as Authenticatable;
class User extends Authenticatable {
    use \Arubacao\Friends\Traits\Friendable;
}