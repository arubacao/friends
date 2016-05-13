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


namespace Arubacao\Tests\Friendships;


use GrahamCampbell\TestBench\AbstractPackageTestCase;
use Arubacao\Friendships\FriendshipsServiceProvider;

abstract class AbstractTestCase extends AbstractPackageTestCase
{
    /**
     * Get the service provider class.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     *
     * @return string
     */
    protected function getServiceProviderClass($app)
    {
        return FriendshipsServiceProvider::class;
    }

    /**
     * Setup the test environment.
     */
    public function setUp()
    {
        parent::setUp();

        $this->artisan('migrate', [
            '--realpath' => realpath(__DIR__ . '/../vendor/laravel/laravel/database/migrations'),
        ]);
        $this->artisan('migrate', [
            '--realpath' => realpath(__DIR__ . '/database/migrations'),
        ]);

        $this->withFactories(realpath(__DIR__ . '/database/factories'));
    }

}