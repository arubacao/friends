<?php
/**
 * This file is part of Friends.
 *
 * (c) Christopher Lass <arubacao@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class UnitTest extends AbstractTestCase
{

    /**
     * @test
     */
    public function retrieveUserId_returns_user_id_for_user_model(){
        $sender = factory(User::class)->create();
        $id = $this->invokeMethod($sender, 'retrieveUserId', [$sender]);
        $this->assertEquals($sender->id, $id);
    }

    /**
     * @test
     */
    public function retrieveUserId_returns_user_id_for_user_array(){
        $sender = factory(User::class)->create();
        $id = $this->invokeMethod($sender, 'retrieveUserId', [$sender->toArray()]);
        $this->assertEquals($sender->id, $id);
    }

    /**
     * @test
     */
    public function retrieveUserId_returns_user_id_for_user_id_integer(){
        $sender = factory(User::class)->create();
        $id = $this->invokeMethod($sender, 'retrieveUserId', [$sender->id]);
        $this->assertEquals($sender->id, $id);
    }
}