<?php
/**
 * This file is part of Friends.
 *
 * (c) Christopher Lass <arubacao@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Arubacao\Friends\Traits;

use Arubacao\Friends\Status;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

trait Friendable
{

    public function scopeFriends($query)
    {
        $query->with([
            'friends_sender' => function ($query) {
                $query->where('status', Status::PENDING);

            },
            'friends_recipient' => function ($query) {
                $query->where('status', Status::PENDING);

            },
        ])
            ->where('id', '!=', $this->getKey());
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function friends_sender()
    {
        return $this->belongsToMany(
            self::class,
            'friends',
            'sender_id', 'recipient_id')
            ->withTimestamps()
            ->withPivot([
                'status',
                'deleted_at',
            ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function friends_recipient()
    {
        return $this->belongsToMany(
            self::class,
            'friends',
            'recipient_id', 'sender_id')
            ->withTimestamps()
            ->withPivot([
                'status',
                'deleted_at',
            ]);
    }

    /**
     * Alias to eloquent many-to-many relation's attach() method.
     *
     * @param int|User $user
     * @return $this
     */
    public function sendFriendRequest($user)
    {
        $userId = $this->retrieveUserId($user);

        $this->friends_sender()->attach($userId, [
            'status' => Status::PENDING,
        ]);

        return $this;
    }

    /**
     * @param $user
     * @return mixed
     */
    protected function retrieveUserId($user)
    {
        if (is_object($user)) {
            $user = $user->getKey();
        }
        if (is_array($user) && isset($user[ 'id' ])) {
            $user = $user[ 'id' ];
        }

        return $user;
    }
}
