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

trait Friendable
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function friends_i_am_sender()
    {
        return $this->belongsToMany(
            Config::get('friends.user_model'),
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
            Config::get('friends.user_model'),
            'friends',
            'recipient_id', 'sender_id')
            ->withTimestamps()
            ->withPivot([
                'status',
            ])
            ->orderBy('updated_at', 'desc');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIncludeRelationshipsWith($query, $user)
    {
        $userId = $this->retrieveUserId($user);

        return $query->with([
            'friends_i_am_sender' => function ($queryIn) use ($userId) {
                $queryIn->where('recipient_id', $userId)
                    ->get();
            },
            'friends_i_am_recipient' => function ($queryIn) use ($userId) {
                $queryIn->where('sender_id', $userId)
                    ->get();
            },
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function friends()
    {
        $me = $this->with([
            'friends_i_am_sender' => function ($query) {
                $query->where('status', Status::ACCEPTED)
                    ->get();
            },
            'friends_i_am_recipient' => function ($query) {
                $query->where('status', Status::ACCEPTED)
                    ->get();
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
    public function incoming_friends()
    {
        $me = $this->with([
            'friends_i_am_recipient' => function ($query) {
                $query->where('status', Status::PENDING)
                    ->get();
            },
        ])
            ->where('id', '=', $this->getKey())
            ->first();

        return $me->friends_i_am_recipient;
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
     * Alias to eloquent many-to-many relation's attach() method.
     *
     * @param int|User $user
     * @return bool
     */
    public function sendFriendRequestTo($user)
    {
        $userId = $this->retrieveUserId($user);

        if ($userId == $this->getKey()) {
            // Method not allowed on self
            return false;
        }

        $relationship = $this->getRelationshipWith($userId, [
            Status::PENDING,
            Status::ACCEPTED,
        ]);

        if (! is_null($relationship)) {
            if ($relationship->pivot->status === Status::ACCEPTED) {
                // Already friends
                return false;
            }
            if ($relationship->pivot->status == Status::PENDING &&
                $relationship->pivot->recipient_id == $this->getKey()) {
                // Recipient already sent a friend request
                // Accept pending friend request
                $relationship->pivot->status = Status::ACCEPTED;
                $relationship->pivot->save();
                /* @todo: fire event friend request accepted */
                $this->reload();

                return true;
            }

            return false;
        }

        $this->friends_i_am_sender()->attach($userId, [
            'status' => Status::PENDING,
        ]);
        /* @todo: fire event friend request sent */

        $this->reload();

        return true;
    }

    /**
     * @param int|User $user
     * @return bool
     */
    public function acceptFriendRequestFrom($user)
    {
        $userId = $this->retrieveUserId($user);

        if ($userId == $this->getKey()) {
            // Method not allowed on self
            return false;
        }

        $relationship = $this->getPendingRequestFrom($userId);

        if (! is_null($relationship)) {
            $relationship->pivot->status = Status::ACCEPTED;
            $relationship->pivot->save();
            /* @todo: fire event friend request accepted */
            $this->reload();

            return true;
        }

        return false;
    }

    /**
     * @param int|User $user
     * @return bool
     */
    public function denyFriendRequestFrom($user)
    {
        $userId = $this->retrieveUserId($user);

        if ($userId == $this->getKey()) {
            // Method not allowed on self
            return false;
        }

        $relationship = $this->getPendingRequestFrom($userId);

        if (! is_null($relationship)) {
            $relationship->pivot->delete();
            /* @todo: fire event friend request denied */
            $this->reload();

            return true;
        }

        return false;
    }

    /**
     * @param int|User $user
     * @return bool
     */
    public function deleteFriend($user)
    {
        $userId = $this->retrieveUserId($user);

        if ($userId == $this->getKey()) {
            // Method not allowed on self
            return false;
        }

        while ($relationship = $this->getRelationshipWith($userId, [
            Status::ACCEPTED,
            Status::PENDING,
        ])) {
            $relationship->pivot->delete();
            /* @todo: fire event friend deleted */
        }
        $this->reload();

        return true;
    }

    /**
     * @param int|array|User $user
     * @return bool
     */
    public function isFriendWith($user)
    {
        $userId = $this->retrieveUserId($user);

        return $this->hasRelationshipWith($userId, [Status::ACCEPTED]);
    }

    /**
     * @param int|array|User $user
     * @param array $status
     * @return bool
     */
    public function hasRelationshipWith($user, $status)
    {
        $userId = $this->retrieveUserId($user);

        $relationship = $this->getRelationshipWith($userId, $status);

        return (is_null($relationship)) ? false : true;
    }

    /**
     * @param int|array|User $user
     * @param array $status
     * @return mixed
     */
    public function getRelationshipWith($user, $status)
    {
        $userId = $this->retrieveUserId($user);

        if ($userId == $this->getKey()) {
            // Method not allowed on self
            return;
        }

        $attempt1 = $this->friends_i_am_recipient()
            ->wherePivotIn('status', $status)
            ->wherePivot('sender_id', $userId)
            ->first();

        if (! is_null($attempt1)) {
            return $attempt1;
        }

        $attempt2 = $this->friends_i_am_sender()
            ->wherePivotIn('status', $status)
            ->wherePivot('recipient_id', $userId)
            ->first();

        if (! is_null($attempt2)) {
            return $attempt2;
        }
    }

    /**
     * @param int|array|User $user
     * @return bool
     */
    public function hasPendingRequestFrom($user)
    {
        $userId = $this->retrieveUserId($user);

        if ($userId == $this->getKey()) {
            // Method not allowed on self
            return false;
        }

        $relationship = $this->getPendingRequestFrom($userId);

        if (! is_null($relationship)) {
            return true;
        }

        return false;
    }

    /**
     * @param int|array|User $user
     * @return int|null
     */
    private function retrieveUserId($user)
    {
        if (is_object($user)) {
            return $user->getKey();
        }
        if (is_array($user) && isset($user['id'])) {
            return $user['id'];
        }

        return $user;
    }

    /**
     * @param int $userId
     * @return mixed
     */
    private function getPendingRequestFrom($userId)
    {
        return $this->friends_i_am_recipient()
            ->wherePivot('status', Status::PENDING)
            ->wherePivot('sender_id', $userId)
            ->first();
    }

    /**
     * Eager load relations on the model.
     */
    private function reload()
    {
        $this->load('friends_i_am_recipient', 'friends_i_am_sender');
    }

    /**
     * @param User $me
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
