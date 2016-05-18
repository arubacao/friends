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

        // Database Check
        $this->seeInDatabase('friendships', [
            'sender_id'     => $sender->id,
            'recipient_id'  => $recipient->id,
            'status'        => Status::PENDING
        ]);
        // Query Check
        $this->assertTrue(\Arubacao\Friendships\Models\Friendship::whereSender($sender)
            ->whereRecipient($recipient)
            ->whereStatus(Status::PENDING)
            ->exists()
        );
        // Methods Check
        $this->assertCount(1, $recipient->getReceivingPendingFriendships());
    }

    /** @test */
    public function user_can_not_send_a_friend_request_to_himself()
    {
        $user = factory(Arubacao\Tests\Friendships\Models\User::class)->create();

        $user->sendFriendshipRequestTo($user);

        // Database Check
        $this->dontSeeInDatabase('friendships', [
            'sender_id'     => $user->id,
            'recipient_id'  => $user->id,
        ]);
        // Query Check
        $this->assertFalse(\Arubacao\Friendships\Models\Friendship::whereSender($user)
            ->whereRecipient($user)
            ->exists()
        );
        // Methods Check
        $this->assertCount(0, $user->getReceivingPendingFriendships());
    }

    /** @test */
    public function user_can_send_a_friend_request_to_a_blocked_user()
    {
        $sender = factory(Arubacao\Tests\Friendships\Models\User::class)->create();
        $recipient = factory(Arubacao\Tests\Friendships\Models\User::class)->create();

        $sender->blockModel($recipient);
        $sender->sendFriendshipRequestTo($recipient);

        // Database Check
        $this->seeInDatabase('friendships', [
            'sender_id'     => $sender->id,
            'recipient_id'  => $recipient->id,
            'status'        => Status::PENDING
        ]);
        // Query Check
        $this->assertTrue(\Arubacao\Friendships\Models\Friendship::whereSender($sender)
            ->whereRecipient($recipient)
            ->whereStatus(Status::PENDING)
            ->exists()
        );
        $this->assertCount(1, \Arubacao\Friendships\Models\Friendship::all());
        // Methods Check
        $this->assertCount(1, $recipient->getReceivingPendingFriendships());
    }

    /** @test */
    public function user_can_not_send_a_friend_request_to_a_user_that_blocked_him()
    {
        $sender = factory(Arubacao\Tests\Friendships\Models\User::class)->create();
        $recipient = factory(Arubacao\Tests\Friendships\Models\User::class)->create();

        $recipient->blockModel($sender);
        $sender->sendFriendshipRequestTo($recipient);

        // Database Check
        $this->dontSeeInDatabase('friendships', [
            'sender_id'     => $sender->id,
            'recipient_id'  => $recipient->id,
            'status'        => Status::PENDING
        ]);
        // Query Check
        $this->assertFalse(\Arubacao\Friendships\Models\Friendship::whereSender($sender)
            ->whereRecipient($recipient)
            ->whereStatus(Status::PENDING)
            ->exists()
        );
        $this->assertCount(1, \Arubacao\Friendships\Models\Friendship::all());
        // Methods Check
        $this->assertCount(0, $recipient->getReceivingPendingFriendships());
    }

    /** @test */
    public function user_can_not_send_a_new_friend_request_to_friend()
    {
        $sender = factory(Arubacao\Tests\Friendships\Models\User::class)->create();
        $recipient = factory(Arubacao\Tests\Friendships\Models\User::class)->create();

        $sender->sendFriendshipRequestTo($recipient);
        $recipient->acceptFriendshipRequestFrom($sender);
        $sender->sendFriendshipRequestTo($recipient);

        // Database Check
        $this->dontSeeInDatabase('friendships', [
            'sender_id'     => $sender->id,
            'recipient_id'  => $recipient->id,
            'status'        => Status::PENDING
        ]);
        $this->seeInDatabase('friendships', [
            'sender_id'     => $sender->id,
            'recipient_id'  => $recipient->id,
            'status'        => Status::ACCEPTED
        ]);
        // Query Check
        $this->assertFalse(\Arubacao\Friendships\Models\Friendship::whereStatus(Status::PENDING)
            ->exists()
        );
        $this->assertTrue(\Arubacao\Friendships\Models\Friendship::whereSender($sender)
            ->whereRecipient($recipient)
            ->whereStatus(Status::ACCEPTED)
            ->exists()
        );
        $this->assertCount(1, \Arubacao\Friendships\Models\Friendship::BetweenModels($sender, $recipient)->get());
        $this->assertCount(1, \Arubacao\Friendships\Models\Friendship::all());
        // Methods Check
        $this->assertCount(0, $recipient->getReceivingPendingFriendships());
    }

    /** @test */
    public function user_can_not_send_a_new_friend_request_if_friendship_is_pending()
    {
        $sender = factory(Arubacao\Tests\Friendships\Models\User::class)->create();
        $recipient = factory(Arubacao\Tests\Friendships\Models\User::class)->create();

        $sender->sendFriendshipRequestTo($recipient);
        $sender->sendFriendshipRequestTo($recipient);
        $sender->sendFriendshipRequestTo($recipient);

        // Query Check
        $this->assertCount(1, \Arubacao\Friendships\Models\Friendship::betweenModels($sender, $recipient)->get());
        $this->assertCount(1, \Arubacao\Friendships\Models\Friendship::all());
        // Methods Check
        $this->assertCount(1, $recipient->getReceivingPendingFriendships());
    }

    /** @test */
    public function user_can_resend_a_friend_request_if_previous_request_was_denied()
    {
        $sender = factory(Arubacao\Tests\Friendships\Models\User::class)->create();
        $recipient = factory(Arubacao\Tests\Friendships\Models\User::class)->create();

        $sender->sendFriendshipRequestTo($recipient);
        $recipient->denyFriendshipRequestFrom($sender);
        $sender->sendFriendshipRequestTo($recipient);

        // Database Check
        $this->seeInDatabase('friendships', [
            'sender_id'     => $sender->id,
            'recipient_id'  => $recipient->id,
            'status'        => Status::PENDING
        ]);
        // Query Check
        $this->assertTrue(\Arubacao\Friendships\Models\Friendship::whereSender($sender)
            ->whereRecipient($recipient)
            ->whereStatus(Status::PENDING)
            ->exists()
        );
        $this->assertCount(1, \Arubacao\Friendships\Models\Friendship::BetweenModels($sender, $recipient)->get());
        $this->assertCount(1, \Arubacao\Friendships\Models\Friendship::all());
        // Methods Check
        $this->assertCount(1, $recipient->getReceivingPendingFriendships());
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

        // Database Check
        $this->seeInDatabase('friendships', [
            'sender_id'     => $sender->id,
            'recipient_id'  => $recipient->id,
            'status'        => Status::ACCEPTED
        ]);
        // Query Check
        $this->assertTrue(\Arubacao\Friendships\Models\Friendship::whereSender($sender)
            ->whereRecipient($recipient)
            ->whereStatus(Status::ACCEPTED)
            ->exists()
        );
        $this->assertCount(1, \Arubacao\Friendships\Models\Friendship::BetweenModels($sender, $recipient)->get());
        // Methods Check
        $this->assertTrue($recipient->hasAcceptedFriendshipWith($sender));
        $this->assertTrue($sender->hasAcceptedFriendshipWith($recipient));
        $this->assertCount(0, $recipient->getReceivingPendingFriendships());
    }

    /** @test */
    public function user_is_not_friend_with_another_user_until_he_accepts_a_friend_request()
    {
        $sender = factory(Arubacao\Tests\Friendships\Models\User::class)->create();
        $recipient = factory(Arubacao\Tests\Friendships\Models\User::class)->create();

        $sender->sendFriendshipRequestTo($recipient);

        $this->assertFalse($recipient->hasAcceptedFriendshipWith($sender));
        $this->assertFalse($sender->hasAcceptedFriendshipWith($recipient));
        $this->assertCount(1, $recipient->getReceivingPendingFriendships());
    }

    /** @test */
    public function user_does_not_have_a_friend_request_if_he_accepted_the_friend_request()
    {
        $sender = factory(Arubacao\Tests\Friendships\Models\User::class)->create();
        $recipient = factory(Arubacao\Tests\Friendships\Models\User::class)->create();

        $sender->sendFriendshipRequestTo($recipient);
        $recipient->acceptFriendshipRequestFrom($sender);

        // Database Check
        $this->dontSeeInDatabase('friendships', [
            'sender_id'     => $sender->id,
            'recipient_id'  => $recipient->id,
            'status'        => Status::PENDING
        ]);
        // Query Check
        $this->assertFalse(\Arubacao\Friendships\Models\Friendship::whereSender($sender)
            ->whereRecipient($recipient)
            ->whereStatus(Status::PENDING)
            ->exists()
        );
        // Methods Check
        $this->assertFalse($recipient->hasFriendshipRequestFrom($sender));
        $this->assertFalse($sender->hasFriendshipRequestFrom($recipient));
    }

    /** @test */
    public function user_cannot_accept_his_own_send_friend_request(){
        $sender = factory(Arubacao\Tests\Friendships\Models\User::class)->create();
        $recipient = factory(Arubacao\Tests\Friendships\Models\User::class)->create();

        $sender->sendFriendshipRequestTo($recipient);
        $sender->acceptFriendshipRequestFrom($recipient);

        // Database Check
        $this->dontSeeInDatabase('friendships', [
            'sender_id'     => $sender->id,
            'recipient_id'  => $recipient->id,
            'status'        => Status::ACCEPTED
        ]);
        // Query Check
        $this->assertFalse(\Arubacao\Friendships\Models\Friendship::whereSender($sender)
            ->whereRecipient($recipient)
            ->whereStatus(Status::ACCEPTED)
            ->exists()
        );
        // Methods Check
        $this->assertFalse($recipient->hasAcceptedFriendshipWith($sender));
    }

    /** @test */
    public function user_can_deny_a_friend_request()
    {
        $sender = factory(Arubacao\Tests\Friendships\Models\User::class)->create();
        $recipient = factory(Arubacao\Tests\Friendships\Models\User::class)->create();

        $sender->sendFriendshipRequestTo($recipient);
        $recipient->denyFriendshipRequestFrom($sender);

        // Database Check
        $this->seeInDatabase('friendships', [
            'sender_id'     => $sender->id,
            'recipient_id'  => $recipient->id,
            'status'        => Status::DENIED
        ]);
        // Query Check
        $this->assertTrue(\Arubacao\Friendships\Models\Friendship::whereSender($sender)
            ->whereRecipient($recipient)
            ->whereStatus(Status::DENIED)
            ->exists()
        );
        // Methods Check
        $this->assertFalse($recipient->hasAcceptedFriendshipWith($sender));
        $this->assertCount(0, $recipient->getReceivingPendingFriendships());
        $this->assertCount(1, $sender->getDeniedFriendships());
    }

    /** @test */
    public function user_can_not_deny_a_nonexistent_friend_request()
    {
        $sender = factory(Arubacao\Tests\Friendships\Models\User::class)->create();
        $recipient = factory(Arubacao\Tests\Friendships\Models\User::class)->create();

        $recipient->denyFriendshipRequestFrom($sender);

        // Database Check
        $this->dontSeeInDatabase('friendships', [
            'status'        => Status::DENIED
        ]);
        // Query Check
        $this->assertFalse(\Arubacao\Friendships\Models\Friendship::whereStatus(Status::DENIED)
            ->exists()
        );
        $this->assertCount(0, \Arubacao\Friendships\Models\Friendship::all());
        // Methods Check
        $this->assertCount(0, $recipient->getDeniedFriendships());
    }

    /** @test */
    public function user_can_not_accept_a_nonexistent_friend_request()
    {
        $sender = factory(Arubacao\Tests\Friendships\Models\User::class)->create();
        $recipient = factory(Arubacao\Tests\Friendships\Models\User::class)->create();

        $recipient->acceptFriendshipRequestFrom($sender);

        // Database Check
        $this->dontSeeInDatabase('friendships', [
            'status'        => Status::ACCEPTED
        ]);
        // Query Check
        $this->assertFalse(\Arubacao\Friendships\Models\Friendship::whereStatus(Status::ACCEPTED)
            ->exists()
        );
        $this->assertCount(0, \Arubacao\Friendships\Models\Friendship::all());
        // Methods Check
        $this->assertFalse($recipient->hasAcceptedFriendshipWith($sender));
    }

    /** @test */
    public function user_can_block_another_user(){
        $sender = factory(Arubacao\Tests\Friendships\Models\User::class)->create();
        $recipient = factory(Arubacao\Tests\Friendships\Models\User::class)->create();

        $sender->blockModel($recipient);

        // Database Check
        $this->seeInDatabase('friendships', [
            'sender_id'     => $sender->id,
            'recipient_id'  => $recipient->id,
            'status'        => Status::BLOCKED
        ]);
        // Query Check
        $this->assertTrue(\Arubacao\Friendships\Models\Friendship::whereSender($sender)
            ->whereRecipient($recipient)
            ->whereStatus(Status::BLOCKED)
            ->exists()
        );
        // Methods Check
        $this->assertTrue($recipient->isBlockedBy($sender));
        $this->assertTrue($sender->hasBlocked($recipient));
        $this->assertFalse($sender->isBlockedBy($recipient));
        $this->assertFalse($recipient->hasBlocked($sender));
    }

    /** @test */
    public function user_can_only_block_another_user_once(){
        $sender = factory(Arubacao\Tests\Friendships\Models\User::class)->create();
        $recipient = factory(Arubacao\Tests\Friendships\Models\User::class)->create();

        $sender->blockModel($recipient);
        $sender->blockModel($recipient);
        $sender->blockModel($recipient);

        // Database Check
        $this->seeInDatabase('friendships', [
            'sender_id'     => $sender->id,
            'recipient_id'  => $recipient->id,
            'status'        => Status::BLOCKED
        ]);
        // Query Check
        $this->assertTrue(\Arubacao\Friendships\Models\Friendship::whereSender($sender)
            ->whereRecipient($recipient)
            ->whereStatus(Status::BLOCKED)
            ->exists()
        );
        $this->assertCount(1, \Arubacao\Friendships\Models\Friendship::all());
        // Methods Check
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

        // Database Check
        $this->dontSeeInDatabase('friendships', [
            'sender_id'     => $sender->id,
            'recipient_id'  => $recipient->id,
            'status'        => Status::BLOCKED
        ]);
        // Query Check
        $this->assertFalse(\Arubacao\Friendships\Models\Friendship::whereSender($sender)
            ->whereRecipient($recipient)
            ->whereStatus(Status::BLOCKED)
            ->exists()
        );
        $this->assertCount(0, \Arubacao\Friendships\Models\Friendship::all());
        // Methods Check
        $this->assertFalse($recipient->isBlockedBy($sender));
        $this->assertFalse($sender->hasBlocked($recipient));
    }

    /** @test */
    public function a_previously_denied_friendship_request_reverts_sender_on_friendship_request() {
        $sender = factory(Arubacao\Tests\Friendships\Models\User::class)->create();
        $recipient = factory(Arubacao\Tests\Friendships\Models\User::class)->create();

        $sender->sendFriendshipRequestTo($recipient);
        $recipient->denyFriendshipRequestFrom($sender);
        $recipient->sendFriendshipRequestTo($sender);

        // Database Check
        $this->seeInDatabase('friendships', [
            'sender_id'     => $recipient->id,
            'recipient_id'  => $sender->id,
            'status'        => Status::PENDING
        ]);
        // Query Check
        $this->assertTrue(\Arubacao\Friendships\Models\Friendship::whereSender($recipient)
            ->whereRecipient($sender)
            ->whereStatus(Status::PENDING)
            ->exists()
        );
        $this->assertCount(1, \Arubacao\Friendships\Models\Friendship::all());
        // Methods Check
        $this->assertTrue($sender->hasFriendshipRequestFrom($recipient));
    }

    /** @test */
    public function a_pending_friendship_request_gets_accepted_on_own_friendship_request() {
        $sender = factory(Arubacao\Tests\Friendships\Models\User::class)->create();
        $recipient = factory(Arubacao\Tests\Friendships\Models\User::class)->create();

        $sender->sendFriendshipRequestTo($recipient);
        $recipient->sendFriendshipRequestTo($sender);

        // Database Check
        $this->seeInDatabase('friendships', [
            'sender_id'     => $sender->id,
            'recipient_id'  => $recipient->id,
            'status'        => Status::ACCEPTED
        ]);
        // Query Check
        $this->assertTrue(\Arubacao\Friendships\Models\Friendship::whereSender($sender)
            ->whereRecipient($recipient)
            ->whereStatus(Status::ACCEPTED)
            ->exists()
        );
        $this->assertCount(1, \Arubacao\Friendships\Models\Friendship::all());
        // Methods Check
        $this->assertTrue($sender->hasAcceptedFriendshipWith($recipient));
        $this->assertTrue($recipient->hasAcceptedFriendshipWith($sender));
    }
}
