<?php

namespace Tests\Model;

use Tests\RegistersPackage;
use Tests\MocksDatabaseFile;
use Orchestra\Testbench\TestCase;
use Tests\CleansProjectFromScaffoldData;
use const DIRECTORY_SEPARATOR as DS;

class GlobalScopesTest extends TestCase
{
    use RegistersPackage;
    use CleansProjectFromScaffoldData;
    use MocksDatabaseFile;

    public function test_does_not_creates_scopes()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'name' => 'string'
                    ],
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $this->assertDirectoryNotExists($this->app->path('Scopes' . DS . 'User'));
    }

    public function test_creates_scopes_from_a_list_and_appends_scope_to_each()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'name' => 'string'
                    ],
                    'scopes' => [
                        'Foo',
                        'QuxScope'
                    ],
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $fooObserver = $this->app->path('Scopes' . DS . 'User' . DS . 'FooScope.php');
        $quxObserver = $this->app->path('Scopes' . DS . 'User' . DS . 'QuxScope.php');

        $this->assertFileExists($fooObserver);
        $this->assertFileExists($quxObserver);

        $this->assertStringContainsString('use App\User;', $this->filesystem->get($fooObserver));
        $this->assertStringContainsString('namespace App\Scopes\User;', $this->filesystem->get($fooObserver));
        $this->assertStringContainsString('class FooScope', $this->filesystem->get($fooObserver));
        $this->assertStringContainsString(
            '@param  \Illuminate\Database\Eloquent\Model|\App\User', $this->filesystem->get($fooObserver)
        );
        $this->assertStringContainsString(
            'public function apply(Builder $builder, User $user)', $this->filesystem->get($fooObserver)
        );
    }

    protected function tearDown() : void
    {
        $this->cleanProject();

        parent::tearDown();
    }
}