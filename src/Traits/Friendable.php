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
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function friendships()
    {
        return $this->morphMany(Friendship::class, 'sender');
    }

    /**
     * @param Model $recipient
     *
     * @return \Arubacao\Friendships\Models\Friendship|false
     */
    public function sendFriendshipRequestTo(Model $recipient)
    {
        // Cannot send to himself
        if($recipient == $this) {
            return false;
        }

        // Get Friendship if available
        $friendship = $this->getFriendshipWith($recipient);

        // If a Friendship already exists
        if($friendship) {
            return $this->handleExistingFriendship($friendship, $recipient);
        }

        $friendship = Friendship::firstOrNewFriendship($this, $recipient)
            ->fill([
            'status' => Status::PENDING,
        ]);

        $this->friendships()->save($friendship);

        return $friendship;
    }

    /**
     * @param Friendship $friendship
     * @param Model $recipient
     * @return Friendship|false
     */
    private function handleExistingFriendship(Friendship $friendship, Model $recipient)
    {
        // Accepted Friendship
        if($friendship->status == Status::ACCEPTED) {
            return $friendship;
        }
        // Pending Friendship
        if($friendship->status == Status::PENDING) {
            if($this->getKey() == $friendship->recipient_id) {
                // $this sends a friendship request to a $recipient that already send one
                // Update Friendship to accepted
                $friendship->update(['status' => Status::ACCEPTED]);
                /** @todo: fire accepted event */
            }
            return $friendship;
        }
        // Denied Friendship
        if($friendship->status == Status::DENIED) {
            if($recipient->getKey() == $friendship->recipient_id) {
                // $this retries previously denied attempt
                $friendship->update(['status' => Status::PENDING]);
            } else {
                // revert sender/recipient
                $friendship->update([
                    'sender_id'      => $this->getKey(),
                    'sender_type'    => $this->getMorphClass(),
                    'recipient_id'   => $recipient->getKey(),
                    'recipient_type' => $recipient->getMorphClass(),
                    'status' => Status::PENDING
                ]);
            }
            /** @todo: fire request event */
            return $friendship;
        }
        // Blocked Friendship
        if($friendship->status == Status::BLOCKED) {
            if($this->getKey() == $friendship->recipient_id) {
                // $this is blocked by $recipient
                return false;
            }
            // unblock user
            // revert sender/recipient
            // send request
            $friendship->update([
                'sender_id'      => $this->getKey(),
                'sender_type'    => $this->getMorphClass(),
                'recipient_id'   => $recipient->getKey(),
                'recipient_type' => $recipient->getMorphClass(),
                'status' => Status::PENDING
            ]);
            /** @todo: fire request event */
            return $friendship;
        }
    }

    /**
     * @param Model $recipient
     *
     * @return bool
     * @deprecated Will be removed/changed
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
        return Friendship::whereSender($recipient)
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
        return Friendship::whereSender($recipient)
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

        $friendship = Friendship::firstOrNewFriendship($this, $recipient)
            ->fill([
                'status' => Status::BLOCKED,
            ]);

        $this->friendships()->save($friendship);
        return $friendship;
    }

    /**
     * @param Model $recipient
     *
     * @return bool|null
     */
    public function unblockModel(Model $recipient)
    {
        return Friendship::whereRecipient($recipient)
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
        return $this->findFriendshipsWith($recipient)
            ->delete();
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
        return Friendship::whereRecipient($recipient)
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
     * @param int $perPage Number
     * @param int $page    Number
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getReceivingPendingModels($perPage = 0, $page = null)
    {
        $friendships = $this->getReceivingPendingFriendships();
        $query = $this->getFriendsQueryBuilder($friendships);

        if ($perPage == 0) {
            return $query->get();
        } else {
            return $query->paginate($perPage, $columns = ['*'], $pageName = 'page', $page);
        }
    }

    /**
     * @param int $perPage Number
     * @param int $page    Number
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAcceptedModels($perPage = 0, $page = null)
    {
        $friendships = $this->getAcceptedFriendships();
        $query = $this->getFriendsQueryBuilder($friendships);

        if ($perPage == 0) {
            return $query->get();
        } else {
            return $query->paginate($perPage, $columns = ['*'], $pageName = 'page', $page);
        }
    }

    /**
     * @param int $perPage Number
     * @param int $page    Number
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSendingBlockedModels($perPage = 0, $page = null)
    {
        $friendships = $this->getSendingBlockedFriendships();
        $query = $this->getFriendsQueryBuilder($friendships);

        if ($perPage == 0) {
            return $query->get();
        } else {
            return $query->paginate($perPage, $columns = ['*'], $pageName = 'page', $page);
        }
    }

    /**
     * Get the number of accepted Friendships.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAcceptedFriendshipsCount()
    {
        return $this->getAcceptedFriendships()->count();
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
     * This method will not return Friendship models
     * It will return the 'friends' models. ex: App\User.
     *
     * @param int $perPage Number
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSecondDegreeAcceptedModels($perPage = 0)
    {
        if ($perPage == 0) {
            return $this->modelsOfFriendshipsQueryBuilder()->get();
        } else {
            return $this->modelsOfFriendshipsQueryBuilder()->paginate($perPage);
        }
    }

    /**
     * Get the query builder of the 'friend' model.
     *
     * @param \Illuminate\Database\Eloquent\Collection $friendships
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function getFriendsQueryBuilder($friendships)
    {
        $recipients = $friendships->pluck('recipient_id')->all();
        $senders = $friendships->pluck('sender_id')->all();
        $friendIds = collect(array_merge($recipients, $senders))
            ->unique()
            ->filter(function ($value, $key) {
                return $value != $this->getKey();
            });

        return $this->where($this->getKeyName(), '!=', $this->getKey())
            ->whereIn($this->getKeyName(), $friendIds);
    }

    /**
     * Get the query builder for friendsOfFriends ('friend' model).
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function modelsOfFriendshipsQueryBuilder()
    {
        $friendships = $this->findFriendships()
            ->whereStatus(Status::ACCEPTED)
            ->get([
                'sender_id',
                'recipient_id',
            ]);
        $recipients = $friendships->pluck('recipient_id')->all();
        $senders = $friendships->pluck('sender_id')->all();

        $friendIds = collect(array_merge($recipients, $senders))
            ->unique()
            ->filter(function ($value, $key) {
                return $value != $this->getKey();
            });

        $fofs = Friendship::where('status', Status::ACCEPTED)
                          ->where(function ($query) use ($friendIds) {
                              $query->where(function ($q) use ($friendIds) {
                                  $q->whereIn('sender_id', $friendIds);
                              })->orWhere(function ($q) use ($friendIds) {
                                  $q->whereIn('recipient_id', $friendIds);
                              });
                          })->get(['sender_id', 'recipient_id']);

        $fofIds = collect(array_merge($fofs->pluck('sender_id')->all(), $fofs->lists('recipient_id')->all()))
            ->unique()
            ->filter(function ($value, $key) use ($friendIds) {
                return $value != $this->getKey() && ! $friendIds->contains($value);
            });

        return $this->whereNotIn('id', $friendIds)
            ->whereIn($this->getKeyName(), $fofIds);
    }
}
