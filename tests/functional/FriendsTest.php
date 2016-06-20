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

class FriendsTest extends AbstractTestCase
{
    use \Illuminate\Foundation\Testing\DatabaseTransactions;
//    use \Illuminate\Foundation\Testing\DatabaseMigrations;

    /**
     * @test
     */
    public function a_user_can_send_a_user_a_friend_request() {
        $sender = factory(User::class)->create();
        $recipient = factory(User::class)->create();

        $bool = $sender->sendFriendRequestTo($recipient);

        $this->assertTrue($bool);
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
    public function a_user_can_not_send_a_user_a_friend_request_to_himself() {
        $sender = factory(User::class)->create();

        $bool = $sender->sendFriendRequestTo($sender);

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
    public function a_user_can_only_send_one_friend_request_to_a_user() {
        $sender = factory(User::class)->create();
        $recipient = factory(User::class)->create();

        $true = $sender->sendFriendRequestTo($recipient);
        $false1 = $sender->sendFriendRequestTo($recipient);
        $false2 = $sender->sendFriendRequestTo($recipient);
        $false3 = $sender->sendFriendRequestTo($recipient);


        $this->assertTrue($true);
        $this->assertFalse($false1);
        $this->assertFalse($false2);
        $this->assertFalse($false3);
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

        $true1 = $sender->sendFriendRequestTo($recipient);
        $true2 = $recipient->acceptFriendRequestFrom($sender);
        $false = $sender->sendFriendRequestTo($recipient);

        $this->assertTrue($true1);
        $this->assertTrue($true2);
        $this->assertFalse($false);
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

        $true1 = $sender->sendFriendRequestTo($recipient);
        $true2 = $recipient->acceptFriendRequestFrom($sender);
        $false = $recipient->sendFriendRequestTo($sender);

        $this->assertTrue($true1);
        $this->assertTrue($true2);
        $this->assertFalse($false);
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

        $sender->sendFriendRequestTo($recipient);
        $recipient->acceptFriendRequestFrom($sender);
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
    public function a_user_can_accept_a_friend_request() {
        $sender = factory(User::class)->create();
        $recipient = factory(User::class)->create();

        $sender->sendFriendRequestTo($recipient);
        $recipient->acceptFriendRequestFrom($sender);

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

        $sender->sendFriendRequestTo($recipient);
        $recipient->acceptFriendRequestFrom($sender);

        $this->assertTrue($sender->isFriendWith($recipient));
        $this->assertTrue($recipient->isFriendWith($sender));
    }

    /**
     * @test
     */
    public function a_user_can_deny_a_friend_request() {
        $sender = factory(User::class)->create();
        $recipient = factory(User::class)->create();

        $sender->sendFriendRequestTo($recipient);
        $recipient->denyFriendRequestFrom($sender);

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

        $bool = $recipient->acceptFriendRequestFrom($sender);

        $this->assertFalse($bool);
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

        $sender->sendFriendRequestTo($recipient);
        $recipient->sendFriendRequestTo($sender);

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

    /**
     * @test
     */
    public function friends_method_returns_empty_array_after_friend_request() {
        $sender = factory(User::class)->create();
        $recipient = factory(User::class)->create();

        $sender->sendFriendRequestTo($recipient);

        $this->assertCount(0, $sender->friends());
        $this->assertCount(0, $recipient->friends());
    }

    /**
     * @test
     */
    public function friends_method_returns_correct_count_of_friends__2_requests_and_2_accepts() {
        $sender = factory(User::class)->create();
        $recipient1 = factory(User::class)->create();
        $recipient2 = factory(User::class)->create();

        $sender->sendFriendRequestTo($recipient1);
        $sender->sendFriendRequestTo($recipient2);
        $recipient1->acceptFriendRequestFrom($sender);
        $recipient2->acceptFriendRequestFrom($sender);

        $this->seeInDatabase('friends', [
            'sender_id' => $sender->id,
            'recipient_id' => $recipient1->id,
            'status' => Status::ACCEPTED,
        ]);
        $this->seeInDatabase('friends', [
            'sender_id' => $sender->id,
            'recipient_id' => $recipient2->id,
            'status' => Status::ACCEPTED,
        ]);
        $this->assertCount(2, $sender->friends());
        $this->assertCount(1, $recipient1->friends());
        $this->assertCount(1, $recipient2->friends());
    }

    /**
     * @test
     */
    public function friends_method_returns_correct_count_of_friends__50_requests_and_50_accepts() {
        $sender = factory(User::class)->create();
        $recipients = factory(User::class)->times(50)->create();

        foreach ($recipients as $recipient) {
            $sender->sendFriendRequestTo($recipient);
            $recipient->acceptFriendRequestFrom($sender);
            $this->assertCount(1, $recipient->friends());
        }

        $this->assertCount(50, $sender->friends());
    }

    /**
     * @test
     */
    public function friends_method_returns_correct_count_of_friends__50_requests_and_25_accepts() {
        $sender = factory(User::class)->create();
        $recipients = factory(User::class)->times(50)->create();

        foreach ($recipients as $key => $recipient) {
            $sender->sendFriendRequestTo($recipient);
            if ($key % 2) {
                $recipient->acceptFriendRequestFrom($sender);
                $this->assertCount(1, $recipient->friends());
                continue;
            }
            $this->assertCount(0, $recipient->friends());
        }

        $this->assertCount(25, $sender->friends());
    }

    /**
     * @test
     */
    public function friends_method_returns_correct_friends__2_requests_and_2_accepts() {
        $sender = factory(User::class)->create();
        $recipient0 = factory(User::class)->create();
        $recipient1 = factory(User::class)->create();

        $sender->sendFriendRequestTo($recipient0);
        $sender->sendFriendRequestTo($recipient1);
        $recipient0->acceptFriendRequestFrom($sender);
        $recipient1->acceptFriendRequestFrom($sender);

        $this->seeInDatabase('friends', [
            'sender_id' => $sender->id,
            'recipient_id' => $recipient0->id,
            'status' => Status::ACCEPTED,
        ]);
        $this->seeInDatabase('friends', [
            'sender_id' => $sender->id,
            'recipient_id' => $recipient1->id,
            'status' => Status::ACCEPTED,
        ]);
        $friends = $sender->friends();
        $this->assertEquals($recipient0->id, $friends[0]->id);
        $this->assertEquals($recipient1->id, $friends[1]->id);
    }

    /**
     * @test
     */
    public function any_friends_method_returns_1_user_after_friend_request() {
        $sender = factory(User::class)->create();
        $recipient = factory(User::class)->create();

        $sender->sendFriendRequestTo($recipient);

        $this->assertCount(1, $sender->any_friends());
        $this->assertCount(1, $recipient->any_friends());
    }

    /**
     * @test
     */
    public function any_friends_method_returns_correct_count_of_any_friends__50_requests_and_25_accepts() {
        $sender = factory(User::class)->create();
        $recipients = factory(User::class)->times(50)->create();

        foreach ($recipients as $key => $recipient) {
            $sender->sendFriendRequestTo($recipient);
            if ($key % 2) {
                $recipient->acceptFriendRequestFrom($sender);
                $this->assertCount(1, $recipient->any_friends());
                continue;
            }
            $this->assertCount(1, $recipient->any_friends());
        }

        $this->assertCount(50, $sender->any_friends());
    }

    /**
     * @test
     */
    public function any_friends_method_returns_correct_count_of_any_friends__50_requests_and_25_accepts_25_denies() {
        $sender = factory(User::class)->create();
        $recipients = factory(User::class)->times(50)->create();

        foreach ($recipients as $key => $recipient) {
            $sender->sendFriendRequestTo($recipient);
            if ($key % 2) {
                $recipient->acceptFriendRequestFrom($sender);
                $this->assertCount(1, $recipient->any_friends());
                continue;
            }
            $recipient->denyFriendRequestFrom($sender);
            $this->assertCount(0, $recipient->any_friends());
        }

        $this->assertCount(25, $sender->any_friends());
    }

    /**
     * @test
     */
    public function incoming_friends_method_returns_1_user_after_friend_request() {
        $sender = factory(User::class)->create();
        $recipient = factory(User::class)->create();

        $sender->sendFriendRequestTo($recipient);

        $this->assertCount(0, $sender->incoming_friends());
        $this->assertCount(1, $recipient->incoming_friends());
    }

    /**
     * @test
     */
    public function incoming_friends_method_returns_correct_count_of_any_friends__50_requests_and_25_accepts() {
        $recipient = factory(User::class)->create();
        $senders = factory(User::class)->times(50)->create();

        foreach ($senders as $key => $sender) {
            $sender->sendFriendRequestTo($recipient);
            if ($key % 2) {
                $recipient->acceptFriendRequestFrom($sender);
                continue;
            }
        }

        $this->assertCount(25, $recipient->incoming_friends());
    }
}