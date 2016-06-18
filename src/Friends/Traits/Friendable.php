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

trait Friendable
{

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function friends()
    {
        $me = $this->with([
            'friends_i_am_sender' => function ($query) {
                $query->where('status', Status::ACCEPTED)
                    ->first()
                ;
            },
            'friends_i_am_recipient' => function ($query) {
                $query->where('status', Status::ACCEPTED)
                    ->first()
                ;
            },
        ])
            ->where('id', '=', $this->getKey())
            ->first();

        $friends = $this->mergedFriends($me);

        return $friends;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function any_friends()
    {
        $me = $this->with([
            'friends_i_am_sender',
            'friends_i_am_recipient',
        ])
            ->where('id', '=', $this->getKey())
            ->first();

        $any_friends = $this->mergedFriends($me);

        return $any_friends;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function friends_i_am_sender()
    {
        return $this->belongsToMany(
            self::class,
            'friends',
            'sender_id', 'recipient_id')
            ->withTimestamps()
            ->withPivot([
                'status',
            ])
            ->orderBy('updated_at', 'desc');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function friends_i_am_recipient()
    {
        return $this->belongsToMany(
            self::class,
            'friends',
            'recipient_id', 'sender_id')
            ->withTimestamps()
            ->withPivot([
                'status',
            ])
            ->orderBy('updated_at', 'desc');
    }

    /**
     * Alias to eloquent many-to-many relation's attach() method.
     *
     * @param int|self $user
     * @return bool
     */
    public function sendFriendRequest($user)
    {
        $userId = $this->retrieveUserId($user);

        if($userId === $this->id) {
            return false;
        }



        $this->friends_i_am_sender()->attach($userId, [
            'status' => Status::PENDING,
        ]);

        $this->reload();

        return true;
    }

    /**
     * @param int|self $user
     * @return bool
     */
    public function acceptFriendRequest($user)
    {
        $userId = $this->retrieveUserId($user);

        $relationship = $this->getPendingRequest($userId);

        if( ! is_null($relationship)) {
            $relationship->pivot->status = Status::ACCEPTED;
            $relationship->pivot->save();

            $this->reload();

            return true;
        }
        return false;
    }

    /**
     * @param int|self $user
     * @return bool
     */
    public function denyFriendRequest($user)
    {
        $userId = $this->retrieveUserId($user);

        $relationship = $this->getPendingRequest($userId);

        if( ! is_null($relationship)) {
            $relationship->pivot->delete();

            $this->reload();

            return true;
        }
        return false;
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

    /**
     * @param int|self $user
     * @return bool
     */
    public function hasPendingRequestFrom($user) {
        $userId = $this->retrieveUserId($user);

        $relationship = $this->getPendingRequest($userId);

        if( ! is_null($relationship)) {
            return true;
        }
        return false;
    }

    /**
     * @param int|self $user
     * @return bool
     */
    public function isFriendWith($user) {
        $userId = $this->retrieveUserId($user);

        $result = $this->with([
            'friends_i_am_sender' => function ($query) use ($userId) {
                $query->where('status', Status::ACCEPTED)
                    ->where('id', $userId)
                    ->first()
                ;
            },
            'friends_i_am_recipient' => function ($query) use ($userId) {
                $query->where('status', Status::ACCEPTED)
                    ->where('id', $userId)
                    ->first()
                ;
            },
        ])
            ->where('id', '=', $this->getKey())
            ->first();

        return $result ? true : false;
    }

    /**
     * @param int $userId
     * @return mixed
     */
    private function getPendingRequest($userId)
    {
        return $this->friends_i_am_recipient()
            ->wherePivot('status', Status::PENDING)
            ->wherePivot('sender_id', $userId)
            ->first();
    }

    private function reload()
    {
        $this->load('friends_i_am_recipient', 'friends_i_am_sender');
    }

    /**
     * @param $me
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function mergedFriends($me)
    {
        $friends = collect([]);
        $friends->push($me->friends_i_am_sender);
        $friends->push($me->friends_i_am_recipient);
        $friends = $friends->flatten();
        return $friends;
    }
}
