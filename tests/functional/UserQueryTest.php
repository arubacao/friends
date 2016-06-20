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

class UserQueryTest extends AbstractTestCase
{

    /**
     * @test
     */
    public function query_user_with_relationships() {
        $users = factory(User::class)->times(10)->create();

        $users[0]->sendFriendRequestTo($users[1]);
        $users[1]->acceptFriendRequestFrom($users[0]);

        $users[0]->sendFriendRequestTo($users[2]);
        $users[2]->acceptFriendRequestFrom($users[0]);

        $users[0]->sendFriendRequestTo($users[3]);

        $users[4]->sendFriendRequestTo($users[0]);

        $results = User::includeRelationshipsWith($users[0])->get();

        $users_with_relationships = collect([]);
        foreach ($results as $key => $result) {
            $users_with_relationships->put($key, $results->where('name', $users[$key]->name)->first());
        }

        // User 0 : No relations with self
        $this->assertCount(0, $users_with_relationships[0]->friends_i_am_recipient);
        $this->assertCount(0, $users_with_relationships[0]->friends_i_am_sender);

        // User 1 : Friends with User 0 - Is Recipient
        $this->assertCount(1, $users_with_relationships[1]->friends_i_am_recipient);
        $this->assertCount(0, $users_with_relationships[1]->friends_i_am_sender);
        $this->assertEquals($users[1]->id, $users_with_relationships[1]->friends_i_am_recipient->first()->pivot->recipient_id);
        $this->assertEquals($users[0]->id, $users_with_relationships[1]->friends_i_am_recipient->first()->pivot->sender_id);
        $this->assertEquals(Status::ACCEPTED, $users_with_relationships[1]->friends_i_am_recipient->first()->pivot->status);

        // User 2 : Friends with User 0 - Is Recipient
        $this->assertCount(1, $users_with_relationships[2]->friends_i_am_recipient);
        $this->assertCount(0, $users_with_relationships[2]->friends_i_am_sender);
        $this->assertEquals($users[2]->id, $users_with_relationships[2]->friends_i_am_recipient->first()->pivot->recipient_id);
        $this->assertEquals($users[0]->id, $users_with_relationships[2]->friends_i_am_recipient->first()->pivot->sender_id);
        $this->assertEquals(Status::ACCEPTED, $users_with_relationships[2]->friends_i_am_recipient->first()->pivot->status);

        // User 3 : Pending Request from User 0 - Is Recipient
        $this->assertCount(1, $users_with_relationships[3]->friends_i_am_recipient);
        $this->assertCount(0, $users_with_relationships[3]->friends_i_am_sender);
        $this->assertEquals($users[3]->id, $users_with_relationships[3]->friends_i_am_recipient->first()->pivot->recipient_id);
        $this->assertEquals($users[0]->id, $users_with_relationships[3]->friends_i_am_recipient->first()->pivot->sender_id);
        $this->assertEquals(Status::PENDING, $users_with_relationships[3]->friends_i_am_recipient->first()->pivot->status);

        // User 4 : Pending Request for User 0 - Is Sender
        $this->assertCount(0, $users_with_relationships[4]->friends_i_am_recipient);
        $this->assertCount(1, $users_with_relationships[4]->friends_i_am_sender);
        $this->assertEquals($users[4]->id, $users_with_relationships[4]->friends_i_am_sender->first()->pivot->sender_id);
        $this->assertEquals($users[0]->id, $users_with_relationships[4]->friends_i_am_sender->first()->pivot->recipient_id);
        $this->assertEquals(Status::PENDING, $users_with_relationships[4]->friends_i_am_sender->first()->pivot->status);
    }


}