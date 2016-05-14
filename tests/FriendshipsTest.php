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

class FriendshipsTest extends \Arubacao\Tests\Friendships\AbstractTestCase
{
    use DatabaseTransactions;




    /** @test */
    public function it_returns_all_user_friendships(){
        $sender = factory(Arubacao\Tests\Friendships\Models\User::class)->create();
        $recipients = factory(Arubacao\Tests\Friendships\Models\User::class)->times(3)->create();

        foreach ($recipients as $recipient) {
            $sender->sendFriendshipRequestTo($recipient);
        }

        $recipients[0]->acceptFriendshipRequestFrom($sender);
        $recipients[1]->acceptFriendshipRequestFrom($sender);
        $recipients[2]->denyFriendshipRequestFrom($sender);
        $this->assertCount(3, $sender->getAllFriendships());
    }

    /** @test */
    public function it_returns_accepted_user_friendships_number(){
        $sender = factory(Arubacao\Tests\Friendships\Models\User::class)->create();
        $recipients = factory(Arubacao\Tests\Friendships\Models\User::class)->times(3)->create();

        foreach ($recipients as $recipient) {
            $sender->sendFriendshipRequestTo($recipient);
        }

        $recipients[0]->acceptFriendshipRequestFrom($sender);
        $recipients[1]->acceptFriendshipRequestFrom($sender);
        $recipients[2]->denyFriendshipRequestFrom($sender);
        $this->assertEquals(2, $sender->getFriendsCount());
    }

    /** @test */
    public function it_returns_accepted_user_friendships(){
        $sender = factory(Arubacao\Tests\Friendships\Models\User::class)->create();
        $recipients = factory(Arubacao\Tests\Friendships\Models\User::class)->times(3)->create();

        foreach ($recipients as $recipient) {
            $sender->sendFriendshipRequestTo($recipient);
        }

        $recipients[0]->acceptFriendshipRequestFrom($sender);
        $recipients[1]->acceptFriendshipRequestFrom($sender);
        $recipients[2]->denyFriendshipRequestFrom($sender);
        $this->assertCount(2, $sender->getAcceptedFriendships());
    }

    /** @test */
    public function it_returns_only_accepted_user_friendships(){
        $sender = factory(Arubacao\Tests\Friendships\Models\User::class)->create();
        $recipients = factory(Arubacao\Tests\Friendships\Models\User::class)->times(4)->create();

        foreach ($recipients as $recipient) {
            $sender->sendFriendshipRequestTo($recipient);
        }

        $recipients[0]->acceptFriendshipRequestFrom($sender);
        $recipients[1]->acceptFriendshipRequestFrom($sender);
        $recipients[2]->denyFriendshipRequestFrom($sender);
        $this->assertCount(2, $sender->getAcceptedFriendships());

        $this->assertCount(1, $recipients[0]->getAcceptedFriendships());
        $this->assertCount(1, $recipients[1]->getAcceptedFriendships());
        $this->assertCount(0, $recipients[2]->getAcceptedFriendships());
        $this->assertCount(0, $recipients[3]->getAcceptedFriendships());
    }

    /** @test */
    public function it_returns_pending_user_friendships(){
        $sender = factory(Arubacao\Tests\Friendships\Models\User::class)->create();
        $recipients = factory(Arubacao\Tests\Friendships\Models\User::class)->times(3)->create();

        foreach ($recipients as $recipient) {
            $sender->sendFriendshipRequestTo($recipient);
        }

        $recipients[0]->acceptFriendshipRequestFrom($sender);
        $this->assertCount(2, $sender->getPendingFriendships());
    }

    /** @test */
    public function it_returns_denied_user_friendships(){
        $sender = factory(Arubacao\Tests\Friendships\Models\User::class)->create();
        $recipients = factory(Arubacao\Tests\Friendships\Models\User::class)->times(3)->create();

        foreach ($recipients as $recipient) {
            $sender->sendFriendshipRequestTo($recipient);
        }

        $recipients[0]->acceptFriendshipRequestFrom($sender);
        $recipients[1]->acceptFriendshipRequestFrom($sender);
        $recipients[2]->denyFriendshipRequestFrom($sender);
        $this->assertCount(1, $sender->getDeniedFriendships());
    }

    /** @test */
    public function it_returns_blocked_user_friendships(){
        $sender = factory(Arubacao\Tests\Friendships\Models\User::class)->create();
        $recipients = factory(Arubacao\Tests\Friendships\Models\User::class)->times(3)->create();

        foreach ($recipients as $recipient) {
            $sender->sendFriendshipRequestTo($recipient);
        }

        $recipients[0]->acceptFriendshipRequestFrom($sender);
        $recipients[1]->acceptFriendshipRequestFrom($sender);
        $recipients[2]->blockModel($sender);
        $this->assertCount(1, $sender->getBlockedFriendships());
    }

    /** @test */
    public function it_returns_user_friends(){
        $sender = factory(Arubacao\Tests\Friendships\Models\User::class)->create();
        $recipients = factory(Arubacao\Tests\Friendships\Models\User::class)->times(4)->create();

        foreach ($recipients as $recipient) {
            $sender->sendFriendshipRequestTo($recipient);
        }

        $recipients[0]->acceptFriendshipRequestFrom($sender);
        $recipients[1]->acceptFriendshipRequestFrom($sender);
        $recipients[2]->denyFriendshipRequestFrom($sender);

        $this->assertCount(2, $sender->getFriends());
        $this->assertCount(1, $recipients[1]->getFriends());
        $this->assertCount(0, $recipients[2]->getFriends());
        $this->assertCount(0, $recipients[3]->getFriends());

        $this->containsOnlyInstancesOf(Arubacao\Tests\Friendships\Models\User::class, $sender->getFriends());
    }

    /** @test */
    public function it_returns_user_friends_per_page(){
        $sender = factory(Arubacao\Tests\Friendships\Models\User::class)->create();
        $recipients = factory(Arubacao\Tests\Friendships\Models\User::class)->times(6)->create();

        foreach ($recipients as $recipient) {
            $sender->sendFriendshipRequestTo($recipient);
        }

        $recipients[0]->acceptFriendshipRequestFrom($sender);
        $recipients[1]->acceptFriendshipRequestFrom($sender);
        $recipients[2]->denyFriendshipRequestFrom($sender);
        $recipients[3]->acceptFriendshipRequestFrom($sender);
        $recipients[4]->acceptFriendshipRequestFrom($sender);


        $this->assertCount(2, $sender->getFriends(2));
        $this->assertCount(4, $sender->getFriends(0));
        $this->assertCount(4, $sender->getFriends(10));
        $this->assertCount(1, $recipients[1]->getFriends());
        $this->assertCount(0, $recipients[2]->getFriends());
        $this->assertCount(0, $recipients[5]->getFriends(2));

        $this->containsOnlyInstancesOf(Arubacao\Tests\Friendships\Models\User::class, $sender->getFriends());
    }

    /** @test */
    public function it_returns_user_friends_of_friends(){
        $sender = factory(Arubacao\Tests\Friendships\Models\User::class)->create();
        $recipients = factory(Arubacao\Tests\Friendships\Models\User::class)->times(2)->create();
        $fofs = factory(Arubacao\Tests\Friendships\Models\User::class)->times(5)->create()->chunk(3);
        foreach ($recipients as $key => $recipient) {
            $sender->sendFriendshipRequestTo($recipient);
            $recipient->acceptFriendshipRequestFrom($sender);

            //add some friends to each recipient too
            foreach ($fofs[$key] as $fof) {
                $recipient->sendFriendshipRequestTo($fof);
                $fof->acceptFriendshipRequestFrom($recipient);
            }
        }

        $this->assertCount(2, $sender->getFriends());
        $this->assertCount(4, $recipients[0]->getFriends());
        $this->assertCount(3, $recipients[1]->getFriends());

        $this->assertCount(5, $sender->getFriendsOfFriends());

        $this->containsOnlyInstancesOf(Arubacao\Tests\Friendships\Models\User::class, $sender->getFriendsOfFriends());
    }
}
