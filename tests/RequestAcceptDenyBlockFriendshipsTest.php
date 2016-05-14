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

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Arubacao\Friendships\Status;

class RequestFriendshipsTest extends \Arubacao\Tests\Friendships\AbstractTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function user_can_send_a_friend_request()
    {
        $sender = factory(Arubacao\Tests\Friendships\Models\User::class)->create();
        $recipient = factory(Arubacao\Tests\Friendships\Models\User::class)->create();

        $sender->sendFriendshipRequestTo($recipient);

        $this->assertCount(1, $recipient->getFriendRequests());
    }


    /** @test */
    public function see_friend_request_in_database()
    {
        $sender = factory(Arubacao\Tests\Friendships\Models\User::class)->create();
        $recipient = factory(Arubacao\Tests\Friendships\Models\User::class)->create();

        $sender->sendFriendshipRequestTo($recipient);

        $this->seeInDatabase('friendships', [
            'sender_id'     => $sender->id,
            'recipient_id'  => $recipient->id,
            'status'        => Status::PENDING
        ]);
    }

    /** @test */
    public function user_can_not_send_a_new_friend_request_if_friendship_is_pending()
    {
        $sender = factory(Arubacao\Tests\Friendships\Models\User::class)->create();
        $recipient = factory(Arubacao\Tests\Friendships\Models\User::class)->create();

        $sender->sendFriendshipRequestTo($recipient);
        $sender->sendFriendshipRequestTo($recipient);
        $sender->sendFriendshipRequestTo($recipient);

        $this->assertCount(1, $recipient->getFriendRequests());
        $this->assertCount(1, \Arubacao\Friendships\Models\Friendship::betweenModels($sender, $recipient)->get());
    }

    /** @test */
    public function user_can_send_a_friend_request_if_friendship_is_denied()
    {
        $sender = factory(Arubacao\Tests\Friendships\Models\User::class)->create();
        $recipient = factory(Arubacao\Tests\Friendships\Models\User::class)->create();

        $sender->sendFriendshipRequestTo($recipient);
        $recipient->denyFriendshipRequestFrom($sender);
        $sender->sendFriendshipRequestTo($recipient);

        $this->assertCount(1, $recipient->getFriendRequests());
    }

    /** @test */
    public function user_only_updates_the_friendship_with_a_friend_request_if_friendship_was_denied()
    {
        $sender = factory(\Arubacao\Tests\Friendships\Models\User::class)->create();
        $recipient = factory(\Arubacao\Tests\Friendships\Models\User::class)->create();

        $sender->sendFriendshipRequestTo($recipient);
        $recipient->denyFriendshipRequestFrom($sender);
        $sender->sendFriendshipRequestTo($recipient);

        $this->assertCount(1, \Arubacao\Friendships\Models\Friendship::betweenModels($sender, $recipient)->get());
    }

    /** @test */
    public function user_has_friend_request_from_another_user_if_he_received_a_friend_request()
    {
        $sender = factory(Arubacao\Tests\Friendships\Models\User::class)->create();
        $recipient = factory(Arubacao\Tests\Friendships\Models\User::class)->create();
        
        $sender->sendFriendshipRequestTo($recipient);

        $this->assertTrue($recipient->hasFriendshipRequestFrom($sender));
        $this->assertFalse($sender->hasFriendshipRequestFrom($recipient));
    }

    /** @test */
    public function user_is_friend_with_another_user_if_accepts_a_friend_request()
    {
        $sender = factory(Arubacao\Tests\Friendships\Models\User::class)->create();
        $recipient = factory(Arubacao\Tests\Friendships\Models\User::class)->create();

        $sender->sendFriendshipRequestTo($recipient);
        $recipient->acceptFriendshipRequestFrom($sender);

        $this->assertTrue($recipient->hasAcceptedFriendshipWith($sender));
        $this->assertTrue($sender->hasAcceptedFriendshipWith($recipient));
        $this->assertCount(0, $recipient->getFriendRequests());
    }

    /** @todo: db check */

    /** @test */
    public function user_is_not_friend_with_another_user_until_he_accepts_a_friend_request()
    {
        $sender = factory(Arubacao\Tests\Friendships\Models\User::class)->create();
        $recipient = factory(Arubacao\Tests\Friendships\Models\User::class)->create();

        $sender->sendFriendshipRequestTo($recipient);

        $this->assertFalse($recipient->hasAcceptedFriendshipWith($sender));
        $this->assertFalse($sender->hasAcceptedFriendshipWith($recipient));
        $this->assertCount(1, $recipient->getFriendRequests());
    }

    /** @test */
    public function user_does_not_have_a_friend_request_if_he_accepted_the_friend_request()
    {
        $sender = factory(Arubacao\Tests\Friendships\Models\User::class)->create();
        $recipient = factory(Arubacao\Tests\Friendships\Models\User::class)->create();

        $sender->sendFriendshipRequestTo($recipient);
        $recipient->acceptFriendshipRequestFrom($sender);

        $this->assertFalse($recipient->hasFriendshipRequestFrom($sender));
        $this->assertFalse($sender->hasFriendshipRequestFrom($recipient));
    }


    /** @todo: User cannot send a request to himself */
    /** @test */
    public function user_cannot_accept_his_own_friend_request(){
        $sender = factory(Arubacao\Tests\Friendships\Models\User::class)->create();
        $recipient = factory(Arubacao\Tests\Friendships\Models\User::class)->create();

        $sender->sendFriendshipRequestTo($recipient);

        $sender->acceptFriendshipRequestFrom($recipient);
        $this->assertFalse($recipient->hasAcceptedFriendshipWith($sender));
    }

    /** @test */
    public function user_can_deny_a_friend_request()
    {
        $sender = factory(Arubacao\Tests\Friendships\Models\User::class)->create();
        $recipient = factory(Arubacao\Tests\Friendships\Models\User::class)->create();
        $sender->sendFriendshipRequestTo($recipient);

        $recipient->denyFriendshipRequestFrom($sender);

        $this->assertFalse($recipient->hasAcceptedFriendshipWith($sender));


        $this->assertCount(0, $recipient->getFriendRequests());
        $this->assertCount(1, $sender->getDeniedFriendships());
    }

    /** @test */
    public function user_can_block_another_user(){
        $sender = factory(Arubacao\Tests\Friendships\Models\User::class)->create();
        $recipient = factory(Arubacao\Tests\Friendships\Models\User::class)->create();

        $sender->blockModel($recipient);

        $this->assertTrue($recipient->isBlockedBy($sender));
        $this->assertTrue($sender->hasBlocked($recipient));

        $this->assertFalse($sender->isBlockedBy($recipient));
        $this->assertFalse($recipient->hasBlocked($sender));
    }

    /** @test */
    public function user_can_unblock_a_blocked_user(){
        $sender = factory(Arubacao\Tests\Friendships\Models\User::class)->create();
        $recipient = factory(Arubacao\Tests\Friendships\Models\User::class)->create();

        $sender->blockModel($recipient);
        $sender->unblockModel($recipient);

        $this->assertFalse($recipient->isBlockedBy($sender));
        $this->assertFalse($sender->hasBlocked($recipient));
    }
}
