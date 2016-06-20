<?php
/**
 * This file is part of Friends.
 *
 * (c) Christopher Lass <arubacao@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Arubacao\Friends;

use Illuminate\Support\ServiceProvider;

class FriendsServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishConfig();
        $this->publishMigration();
    }

    /**
     * Publish Friends configuration.
     */
    protected function publishConfig()
    {
        // Publish config files
        $this->publishes([
            __DIR__.'/../config/friends.php' => config_path('friends.php'),
        ]);
    }

    /**
     * Publish Friends migration.
     */
    protected function publishMigration()
    {
        if (class_exists('CreateFriendsTable')) {
            return;
        }

        $published_migration = glob(database_path('/migrations/*_create_friends_table.php'));
        if (count($published_migration) != 0) {
            return;
        }

        $stub = __DIR__.'/../database/migrations/2016_06_18_000000_create_friends_table.php';
        $target = database_path('/migrations/'.date('Y_m_d_His').'_create_friends_table.php');
        $this->publishes([$stub => $target], 'migrations');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfig();
    }

    /**
     * Merges user's and friends's configs.
     *
     * @return void
     */
    protected function mergeConfig()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/friends.php', 'friends'
        );
    }
}
