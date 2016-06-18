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
    }

    /**
     * @test
     */
    public function friends_is_empty_after_friend_request() {
        $sender = factory(User::class)->create();
        $recipient = factory(User::class)->create();

        $sender->sendFriendRequest($recipient);

        $this->assertCount(0, $sender->friends());
        $this->assertCount(0, $recipient->friends());
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
    public function user_model_reloads_after_accepting_friend_request() {
        $sender = factory(User::class)->create();
        $recipient = factory(User::class)->create();

        $sender->sendFriendRequest($recipient);
        $recipient->sendFriendRequest($sender);

        $array = $recipient->toArray();
        dd($array);
        $this->assertCount(1, $array["friendship_recipient"]);
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
    public function user_model_reloads_after_friend_request() {
        $sender = factory(User::class)->create();
        $recipient = factory(User::class)->create();

        $sender->sendFriendRequest($recipient);

        $array = $sender->toArray();

        $this->assertCount(1, $array["friendship_sender"]);
    }

    /**
     * @test
     */
    public function worker(){
        $sender = factory(User::class)->create();
        $sender2 = factory(User::class)->create();
        $recipient = factory(User::class)->create();

        $sender->sendFriendRequest($recipient);
        $sender2->sendFriendRequest($recipient);

        echo 'Sender: '.$sender->name.PHP_EOL;
        echo 'Sender2: '.$sender2->name.PHP_EOL;
        echo 'Recipient: '.$recipient->name.PHP_EOL;

//        dd($recipient->friends()->get());
        $u = $sender->friends()->get();
        foreach ($u as $friend) {
            echo "Friend: " . $friend->name.PHP_EOL;
        }
    }
}