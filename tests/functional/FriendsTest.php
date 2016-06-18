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
    public function a_user_can_send_a_user_a_friend_request(){
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