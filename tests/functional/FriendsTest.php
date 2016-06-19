<?php
/**
 * This file is part of Friends.
 *
 * (c) Christopher Lass <arubacao@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Arubacao\Friends\Status;
use Arubacao\Tests\Friends\Models\User;

class FriendsTest extends \Arubacao\Tests\Friends\AbstractTestCase
{
    use \Illuminate\Foundation\Testing\DatabaseTransactions;
//    use \Illuminate\Foundation\Testing\DatabaseMigrations;

    /**
     * @test
     */
    public function a_user_can_send_a_user_a_friend_request() {
        $sender = factory(User::class)->create();
        $recipient = factory(User::class)->create();

        $sender->sendFriendRequest($recipient);

        $this->seeInDatabase('friends', [
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'status' => Status::PENDING,
        ]);
        $this->dontSeeInDatabase('friends', [
            'recipient_id' => $sender->id,
            'sender_id' => $recipient->id,
            'status' => Status::PENDING,
        ]);
        $this->assertCount(1, $sender->any_friends());
        $this->assertCount(1, $recipient->any_friends());
        $this->assertTrue($recipient->hasPendingRequestFrom($sender));
        $this->assertFalse($sender->hasPendingRequestFrom($recipient));
    }

    /**
     * @test
     */
    public function a_user_can_only_send_one_friend_request_to_a_user() {
        $sender = factory(User::class)->create();
        $recipient = factory(User::class)->create();

        $sender->sendFriendRequest($recipient);
        $sender->sendFriendRequest($recipient);
        $sender->sendFriendRequest($recipient);
        $sender->sendFriendRequest($recipient);

        $this->seeInDatabase('friends', [
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'status' => Status::PENDING,
        ]);
        $this->dontSeeInDatabase('friends', [
            'recipient_id' => $sender->id,
            'sender_id' => $recipient->id,
            'status' => Status::PENDING,
        ]);
        $this->assertCount(1, $sender->any_friends());
        $this->assertCount(1, $recipient->any_friends());
        $this->assertTrue($recipient->hasPendingRequestFrom($sender));
        $this->assertFalse($sender->hasPendingRequestFrom($recipient));
    }

    /**
     * @test
     */
    public function a_user_can_not_send_a_new_friend_request_to_a_friend() {
        $sender = factory(User::class)->create();
        $recipient = factory(User::class)->create();

        $sender->sendFriendRequest($recipient);
        $recipient->acceptFriendRequest($sender);
        $sender->sendFriendRequest($recipient);

        $this->seeInDatabase('friends', [
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'status' => Status::ACCEPTED,
        ]);
        $this->dontSeeInDatabase('friends', [
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'status' => Status::PENDING,
        ]);
        $this->assertCount(1, $sender->any_friends());
        $this->assertCount(1, $sender->friends());
        $this->assertCount(1, $recipient->any_friends());
        $this->assertCount(1, $recipient->friends());
        $this->assertFalse($recipient->hasPendingRequestFrom($sender));
        $this->assertFalse($sender->hasPendingRequestFrom($recipient));
    }

    /**
     * @test
     */
    public function a_user_can_not_send_a_new_friend_request_to_a_friend_2() {
        $sender = factory(User::class)->create();
        $recipient = factory(User::class)->create();

        $sender->sendFriendRequest($recipient);
        $recipient->acceptFriendRequest($sender);
        $recipient->sendFriendRequest($sender);

        $this->seeInDatabase('friends', [
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'status' => Status::ACCEPTED,
        ]);
        $this->dontSeeInDatabase('friends', [
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'status' => Status::PENDING,
        ]);
        $this->dontSeeInDatabase('friends', [
            'sender_id' => $recipient->id,
            'recipient_id' => $sender->id,
            'status' => Status::PENDING,
        ]);
        $this->assertCount(1, $sender->any_friends());
        $this->assertCount(1, $sender->friends());
        $this->assertCount(1, $recipient->any_friends());
        $this->assertCount(1, $recipient->friends());
        $this->assertFalse($recipient->hasPendingRequestFrom($sender));
        $this->assertFalse($sender->hasPendingRequestFrom($recipient));
    }

    /**
     * @test
     */
    public function a_user_can_delete_a_friend() {
        $sender = factory(User::class)->create();
        $recipient = factory(User::class)->create();

        $sender->sendFriendRequest($recipient);
        $recipient->acceptFriendRequest($sender);
        $sender->deleteFriend($recipient);

        $this->dontSeeInDatabase('friends', [
            'sender_id' => $sender->id,
        ]);
        $this->assertCount(0, $sender->any_friends());
        $this->assertCount(0, $recipient->any_friends());
        $this->assertFalse($recipient->isFriendWith($sender));
        $this->assertFalse($sender->isFriendWith($recipient));
    }

    /**
     * @test
     */
    public function friends_method_returns_empty_array_after_friend_request() {
        $sender = factory(User::class)->create();
        $recipient = factory(User::class)->create();

        $sender->sendFriendRequest($recipient);

        $this->assertCount(0, $sender->friends());
        $this->assertCount(0, $recipient->friends());
    }

    /**
     * @test
     */
    public function a_user_can_not_send_a_user_a_friend_request_to_himself() {
        $sender = factory(User::class)->create();

        $bool = $sender->sendFriendRequest($sender);

        $this->assertFalse($bool);
        $this->dontSeeInDatabase('friends', [
            'recipient_id' => $sender->id,
        ]);
        $this->dontSeeInDatabase('friends', [
            'sender_id' => $sender->id,
        ]);
        $this->assertCount(0, $sender->any_friends());
    }

    /**
     * @test
     */
    public function a_user_can_accept_a_friend_request() {
        $sender = factory(User::class)->create();
        $recipient = factory(User::class)->create();

        $sender->sendFriendRequest($recipient);
        $recipient->acceptFriendRequest($sender);

        $this->seeInDatabase('friends', [
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'status' => Status::ACCEPTED,
        ]);
        $this->dontSeeInDatabase('friends', [
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'status' => Status::PENDING,
        ]);

        $this->assertCount(1, $sender->friends());
        $this->assertCount(1, $recipient->friends());
    }

    /**
     * @test
     */
    public function a_user_is_friend_with_a_user_after_a_friend_request() {
        $sender = factory(User::class)->create();
        $recipient = factory(User::class)->create();

        $sender->sendFriendRequest($recipient);
        $recipient->acceptFriendRequest($sender);

        $this->assertTrue($sender->isFriendWith($recipient));
        $this->assertTrue($recipient->isFriendWith($sender));
    }

    /**
     * @test
     */
    public function a_user_can_deny_a_friend_request() {
        $sender = factory(User::class)->create();
        $recipient = factory(User::class)->create();

        $sender->sendFriendRequest($recipient);
        $recipient->denyFriendRequest($sender);

        $this->dontSeeInDatabase('friends', [
            'sender_id' => $sender->id,
        ]);
        $this->dontSeeInDatabase('friends', [
            'recipient_id' => $recipient->id,
        ]);

        $this->assertCount(0, $sender->any_friends());
        $this->assertCount(0, $recipient->any_friends());
    }

    /**
     * @test
     */
    public function a_user_can_not_accept_a_non_existing_friend_request() {
        $sender = factory(User::class)->create();
        $recipient = factory(User::class)->create();

        $recipient->acceptFriendRequest($sender);

        $this->dontSeeInDatabase('friends', [
            'sender_id' => $recipient->id,
            'recipient_id' => $sender->id,
        ]);
        $this->dontSeeInDatabase('friends', [
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
        ]);

        $this->assertCount(0, $sender->friends());
        $this->assertCount(0, $recipient->friends());
        $this->assertCount(0, $sender->any_friends());
        $this->assertCount(0, $recipient->any_friends());
    }

    /**
     * @test
     */
    public function a_friend_request_results_in_a_accepted_if_pending_request_is_available_from_recipient() {
        $sender = factory(User::class)->create();
        $recipient = factory(User::class)->create();

        $sender->sendFriendRequest($recipient);
        $recipient->sendFriendRequest($sender);

        $this->seeInDatabase('friends', [
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'status' => Status::ACCEPTED,
        ]);
        $this->dontSeeInDatabase('friends', [
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'status' => Status::PENDING,
        ]);
        $this->dontSeeInDatabase('friends', [
            'sender_id' => $recipient->id,
            'recipient_id' => $sender->id,
            'status' => Status::PENDING,
        ]);

        $this->assertCount(1, $sender->friends());
        $this->assertCount(1, $recipient->friends());
    }

}