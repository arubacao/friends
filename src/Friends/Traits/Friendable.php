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
     * Alias to eloquent many-to-many relation's attach() method.
     *
     * @param int|self $user
     * @return bool
     */
    public function sendFriendRequest($user)
    {
        $userId = $this->retrieveUserId($user);

        if($userId == $this->getKey()) {
            // Not allowed to send a friend request to self.
            return false;
        }

        $relationship = $this->getRelationshipWith($userId, [
            Status::PENDING,
            Status::ACCEPTED
        ]);

        if(! is_null($relationship)){
            if ($relationship->pivot->status === Status::ACCEPTED) {
                // Already friends
                return false;
            }
            if ($relationship->pivot->status == Status::PENDING &&
                $relationship->pivot->recipient_id == $this->getKey())
            {
                // Recipient already sent a friend request
                // Accept pending friend request
                $relationship->pivot->status = Status::ACCEPTED;
                $relationship->pivot->save();
                /** @todo: fire event friend request accepted */
                $this->reload();
                return true;
            }
            return false;
        }
        
        $this->friends_i_am_sender()->attach($userId, [
            'status' => Status::PENDING,
        ]);
        /** @todo: fire event friend request sent */

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
     * @param int|self $user
     * @return bool
     */
    public function deleteFriend($user) {
        $userId = $this->retrieveUserId($user);

        if($userId == $this->getKey()) {
            return false;
        }

        while($relationship = $this->getRelationshipWith($userId, [Status::ACCEPTED, Status::PENDING])) {
            $relationship->pivot->delete();
        }
        return true;
    }


    /**
     * @param int|array|self $user
     * @return int|null
     */
    protected function retrieveUserId($user)
    {
        if (is_object($user)) {
            return $user->getKey();
        }
        if (is_array($user) && isset($user[ 'id' ])) {
            return $user[ 'id' ];
        }

        return $user;
    }

    /**
     * @param int|array|self $user
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
     * @param int|array|self $user
     * @return bool
     */
    public function isFriendWith($user) {
        $userId = $this->retrieveUserId($user);

        return $this->hasRelationshipWith($userId, [Status::ACCEPTED]);
    }

    /**
     * @param int|array|self $user
     * @param array $status
     * @return bool
     */
    public function hasRelationshipWith($user, $status) {
        $userId = $this->retrieveUserId($user);

        $relationship = $this->getRelationshipWith($userId, $status);

        return (is_null($relationship)) ? false : true;

    }

    /**
     * @param int|array|self $user
     * @param array $status
     * @return bool
     */
    public function getRelationshipWith($user, $status) {
        $userId = $this->retrieveUserId($user);

        $attempt1 = $this->friends_i_am_recipient()
            ->wherePivotIn('status', $status)
            ->wherePivot('sender_id', $userId)
            ->first();

        if ( ! is_null($attempt1) ){
            return $attempt1;
        }

        $attempt2 = $this->friends_i_am_sender()
            ->wherePivotIn('status', $status)
            ->wherePivot('recipient_id', $userId)
            ->first();

        if ( ! is_null($attempt2) ){
            return $attempt2;
        }

        return null;

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

    /**
     * Eager load relations on the model.
     */
    private function reload()
    {
        $this->load('friends_i_am_recipient', 'friends_i_am_sender');
    }

    /**
     * @param self $me
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