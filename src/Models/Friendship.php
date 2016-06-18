<?php
/**
 * This file is part of Laravel Friendships.
 *
 * (c) Christopher Lass <arubacao@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Arubacao\Friendships\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Arubacao\Friendships\Models\Friendship.
 *
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $sender
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $recipient
 * @method static \Illuminate\Database\Query\Builder whereRecipient($model)
 * @method static \Illuminate\Database\Query\Builder whereSender($model)
 * @method static \Illuminate\Database\Query\Builder betweenModels($sender, $recipient)
 * @method static \Illuminate\Database\Query\Builder allMyFriendships($model)
 * @mixin \Eloquent
 */
class Friendship extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'friendships';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function sender()
    {
        return $this->morphTo('sender');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function recipient()
    {
        return $this->morphTo('recipient');
    }

    /**
     * @param Model $recipient
     * @param Model $sender
     * @return Friendship
     */
    public static function firstOrNewFriendship($sender, $recipient)
    {
        return self::firstOrNew([
            'sender_id'      => $sender->getKey(),
            'sender_type'    => $sender->getMorphClass(),
            'recipient_id'   => $recipient->getKey(),
            'recipient_type' => $recipient->getMorphClass(),
        ]);
    }

    /**
     * @param $query
     * @param Model $model
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereRecipient($query, $model)
    {
        return $query->where('recipient_id', $model->getKey())
            ->where('recipient_type', $model->getMorphClass());
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Model $model
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereSender($query, $model)
    {
        return $query->where('sender_id', $model->getKey())
            ->where('sender_type', $model->getMorphClass());
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Model $sender
     * @param Model $recipient
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBetweenModels($query, $sender, $recipient)
    {
        return $query->where(function ($queryIn) use ($sender, $recipient) {
            $queryIn->where(function ($q) use ($sender, $recipient) {
                $q->whereSender($sender)->whereRecipient($recipient);
            })->orWhere(function ($q) use ($sender, $recipient) {
                $q->whereSender($recipient)->whereRecipient($sender);
            });
        });
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Model $model
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAllMyFriendships($query, $model)
    {
        return $query->where(function ($query) use ($model) {
            $query->where(function ($q) use ($model) {
                $q->whereSender($model);
            })->orWhere(function ($q) use ($model) {
                $q->whereRecipient($model);
            });
        });
    }
}
