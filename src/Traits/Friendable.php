<?php
/**
 * This file is part of Laravel Friendships.
 *
 * (c) Christopher Lass <arubacao@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace Arubacao\Friendships\Traits;

use Arubacao\Friendships\Models\Friendship;
use Arubacao\Friendships\Status;
use Illuminate\Database\Eloquent\Model;

trait Friendable
{
    /**
     * 1) Return existing Friendship
     * 2) When existing DENIED Friendship
     *    -> set status to PENDING
     *    -> return updated Friendship
     * 3) Return newly created Friendship.
     *
     * @param Model $recipient
     *
     * @return \Arubacao\Friendships\Models\Friendship|false
     */
    public function sendFriendshipRequestTo(Model $recipient)
    {
        if (!$this->canSendFriendshipRequest($recipient)) {
            return $this->getFriendshipWith($recipient);
        }

        $friendship = Friendship::firstOrNewRecipient($recipient)
            ->fill([
            'status' => Status::PENDING,
        ]);

        $this->friendships()->save($friendship);
        return $friendship;
    }

    /**
     * @param Model $recipient
     *
     * @return bool
     */
    public function removeFriendshipWith(Model $recipient)
    {
        return $this->findFriendship($recipient)->delete();
    }

    /**
     * @param Model $recipient
     *
     * @return bool
     */
    public function hasFriendshipRequestFrom(Model $recipient)
    {
        return Friendship::whereRecipient($this)
            ->whereSender($recipient)
            ->whereStatus(Status::PENDING)
            ->exists();
    }

    /**
     * @param Model $recipient
     *
     * @return bool
     */
    public function hasAcceptedFriendshipWith(Model $recipient)
    {
        return $this->findFriendship($recipient)
            ->where('status', Status::ACCEPTED)
            ->exists();
    }

    /**
     * @param Model $recipient
     *
     * @return bool|int
     */
    public function acceptFriendRequest(Model $recipient)
    {
        return $this->findFriendship($recipient)
            ->whereRecipient($this)
            ->update([
                'status' => Status::ACCEPTED,
            ]);
    }

    /**
     * @param Model $recipient
     *
     * @return bool|int
     */
    public function denyFriendRequest(Model $recipient)
    {
        return $this->findFriendship($recipient)
            ->whereRecipient($this)
            ->update([
                'status' => Status::DENIED,
            ]);
    }

    /**
     * @param Model $recipient
     *
     * @return \Arubacao\Friendships\Models\Friendship
     */
    public function blockFriend(Model $recipient)
    {
        //if there is a friendship between two users delete it
        $this->findFriendship($recipient)
            ->delete();

        $friendship = Friendship::firstOrNewRecipient($recipient)
            ->fill([
                'status' => Status::BLOCKED,
            ]);

        return $this->friendships()->save($friendship);
    }

    /**
     * @param Model $recipient
     *
     * @return mixed
     */
    public function unblockFriend(Model $recipient)
    {
        return $this->findFriendship($recipient)->delete();
    }

    /**
     * @param Model $recipient
     *
     * @return \Arubacao\Friendships\Models\Friendship
     */
    public function getFriendshipWith(Model $recipient)
    {
        return $this->findFriendship($recipient)->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllFriendships()
    {
        return $this->findFriendships()->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPendingFriendships()
    {
        return $this->findFriendships(Status::PENDING)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAcceptedFriendships()
    {
        return $this->findFriendships(Status::ACCEPTED)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDeniedFriendships()
    {
        return $this->findFriendships(Status::DENIED)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getBlockedFriendships()
    {
        return $this->findFriendships(Status::BLOCKED)->get();
    }

    /**
     * @param Model $recipient
     *
     * @return bool
     */
    public function hasBlocked(Model $recipient)
    {
        return $this->friendships()->whereRecipient($recipient)->whereStatus(Status::BLOCKED)->exists();
    }

    /**
     * @param Model $recipient
     *
     * @return bool
     */
    public function isBlockedBy(Model $recipient)
    {
        return $recipient->hasBlocked($this);
    }

     /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFriendRequests()
    {
        return Friendship::whereRecipient($this)->whereStatus(Status::PENDING)->get();
    }

    /**
     * This method will not return Friendship models
     * It will return the 'friends' models. ex: App\User.
     *
     * @param int $perPage Number
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFriends($perPage = 0)
    {
        if ($perPage == 0) {
            return $this->getFriendsQueryBuilder()->get();
        } else {
            return $this->getFriendsQueryBuilder()->paginate($perPage);
        }
    }

    /**
     * This method will not return Friendship models
     * It will return the 'friends' models. ex: App\User.
     *
     * @param int $perPage Number
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFriendsOfFriends($perPage = 0)
    {
        if ($perPage == 0) {
            return $this->friendsOfFriendsQueryBuilder()->get();
        } else {
            return $this->friendsOfFriendsQueryBuilder()->paginate($perPage);
        }
    }

    /**
     * Get the number of friends.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFriendsCount()
    {
        $friendsCount = $this->findFriendships(Status::ACCEPTED)->count();

        return $friendsCount;
    }

    /**
     * @param Model $recipient
     *
     * @return bool
     */
    public function canSendFriendshipRequest($recipient)
    {
        /*
         * When there is no existing Friendship
         * Or there is an existing DENIED Friendship
         * --> true
         */
        $friendship = $this->getFriendshipWith($recipient);
        if ($friendship && $friendship->status != Status::DENIED) {
            return false;
        }

        return true;
    }

    /**
     * @param Model $recipient
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function findFriendship(Model $recipient)
    {
        return Friendship::betweenModels($this, $recipient);
    }

    /**
     * @param $status
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function findFriendships($status = '%')
    {
        return Friendship::where('status', 'LIKE', $status)
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->whereSender($this);
                })->orWhere(function ($q) {
                    $q->whereRecipient($this);
                });
            });
    }

    /**
     * Get the query builder of the 'friend' model.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function getFriendsQueryBuilder()
    {
        $friendships = $this->findFriendships(Status::ACCEPTED)->get(['sender_id', 'recipient_id']);
        $recipients = $friendships->lists('recipient_id')->all();
        $senders = $friendships->lists('sender_id')->all();

        return $this->where('id', '!=', $this->getKey())->whereIn('id', array_merge($recipients, $senders));
    }

    /**
     * Get the query builder for friendsOfFriends ('friend' model).
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function friendsOfFriendsQueryBuilder()
    {
        $friendships = $this->findFriendships(Status::ACCEPTED)->get(['sender_id', 'recipient_id']);
        $recipients = $friendships->lists('recipient_id')->all();
        $senders = $friendships->lists('sender_id')->all();

        $friendIds = array_unique(array_merge($recipients, $senders));

        $fofs = Friendship::where('status', Status::ACCEPTED)
                          ->where(function ($query) use ($friendIds) {
                              $query->where(function ($q) use ($friendIds) {
                                  $q->whereIn('sender_id', $friendIds);
                              })->orWhere(function ($q) use ($friendIds) {
                                  $q->whereIn('recipient_id', $friendIds);
                              });
                          })->get(['sender_id', 'recipient_id']);

        $fofIds = array_unique(
            array_merge($fofs->pluck('sender_id')->all(), $fofs->lists('recipient_id')->all())
        );

//      Alternative way using collection helpers
//        $fofIds = array_unique(
//            $fofs->map(function ($item) {
//                return [$item->sender_id, $item->recipient_id];
//            })->flatten()->all()
//        );

        return $this->whereIn('id', $fofIds)->whereNotIn('id', $friendIds);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function friendships()
    {
        return $this->morphMany(Friendship::class, 'sender');
    }
}
