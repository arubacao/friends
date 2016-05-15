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
     * @param Model $recipient
     *
     * @return \Arubacao\Friendships\Models\Friendship|false
     */
    public function sendFriendshipRequestTo(Model $recipient)
    {
        if (!$this->canSendFriendshipRequest($recipient)) {
            // Return existing Friendship
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
     * @return bool|int
     */
    public function acceptFriendshipRequestFrom(Model $recipient)
    {
        return $this->findFriendshipsWith($recipient)
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
    public function denyFriendshipRequestFrom(Model $recipient)
    {
        return $this->findFriendshipsWith($recipient)
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
    public function blockModel(Model $recipient)
    {
        //if there is a friendship between two models delete it
        $this->removeFriendshipsWith($recipient);

        $friendship = Friendship::firstOrNewRecipient($recipient)
            ->fill([
                'status' => Status::BLOCKED,
            ]);

        return $this->friendships()->save($friendship);
    }

    /**
     * @param Model $recipient
     *
     * @return bool|null
     */
    public function unblockModel(Model $recipient)
    {
        return $this->findFriendshipsWith($recipient)
            ->whereSender($this)
            ->where('status', Status::BLOCKED)
            ->delete();
    }

    /**
     * @param Model $recipient
     *
     * @return bool
     */
    public function removeFriendshipsWith(Model $recipient)
    {
        return $this->findFriendshipsWith($recipient)->delete();
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
    public function hasBlocked(Model $recipient)
    {
        return $this->friendships()
            ->whereRecipient($recipient)
            ->whereStatus(Status::BLOCKED)
            ->exists();
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
     * @param Model $recipient
     *
     * @return bool
     */
    public function hasAcceptedFriendshipWith(Model $recipient)
    {
        return $this->findFriendshipsWith($recipient)
            ->whereStatus(Status::ACCEPTED)
            ->exists();
    }

    /**
     * @param Model $recipient
     *
     * @return \Arubacao\Friendships\Models\Friendship
     */
    public function getFriendshipWith(Model $recipient)
    {
        return $this->findFriendshipsWith($recipient)->first();
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
        return $this->findFriendships()->whereStatus(Status::PENDING)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAcceptedFriendships()
    {
        return $this->findFriendships()->whereStatus(Status::ACCEPTED)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDeniedFriendships()
    {
        return $this->findFriendships()->whereStatus(Status::DENIED)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getBlockedFriendships()
    {
        return $this->findFriendships()->whereStatus(Status::BLOCKED)->get();
    }

     /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getReceivingPendingFriendships()
    {
        return Friendship::whereRecipient($this)
            ->whereStatus(Status::PENDING)
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSendingBlockedFriendships()
    {
        return Friendship::whereSender($this)
            ->whereStatus(Status::BLOCKED)
            ->get();
    }

    /**
     * This method will not return Friendship models
     * It will return the 'friends' models. ex: App\User.
     *
     * @param int $perPage Number
     * @param int $page Number
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFriends($perPage = 0, $page = null)
    {

        $friendships = $this->getAcceptedFriendships();

        if ($perPage == 0) {
            return $this->getFriendsQueryBuilder($friendships)->get();
        } else {
            return $this->getFriendsQueryBuilder($friendships)->paginate($perPage, $columns = ['*'], $pageName = 'page', $page);
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
        $friendsCount = $this->findFriendships()->whereStatus(Status::ACCEPTED)->count();

        return $friendsCount;
    }

    /**
     * @param Model $recipient
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function findFriendshipsWith(Model $recipient)
    {
        return Friendship::betweenModels($this, $recipient);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function findFriendships()
    {
        return Friendship::allMyFriendships($this);
    }

    /**
     * Get the query builder of the 'friend' model.
     *
     * @param \Illuminate\Database\Eloquent\Collection $friendships
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function getFriendsQueryBuilder($friendships)
    {
        $recipients = $friendships->pluck('recipient_id')->all();
        $senders = $friendships->pluck('sender_id')->all();
        $ids = collect(array_merge($recipients, $senders))->filter(function ($value, $key) {
            return $value != $this->getKey();
        });;

        return $this->where($this->getKeyName(), '!=', $this->getKey())->whereIn($this->getKeyName(), $ids);
    }

    /**
     * Get the query builder for friendsOfFriends ('friend' model).
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function friendsOfFriendsQueryBuilder()
    {
        $friendships = $this->findFriendships(Status::ACCEPTED)->get(['sender_id', 'recipient_id']);
        $recipients = $friendships->pluck('recipient_id')->all();
        $senders = $friendships->pluck('sender_id')->all();

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
