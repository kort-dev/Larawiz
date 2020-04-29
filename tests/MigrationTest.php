<?php

namespace Tests;

use LogicException;
use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use const DIRECTORY_SEPARATOR as DS;

class MigrationTest extends TestCase
{
    use RegistersPackage;
    use CleansProjectFromScaffoldData;
    use MocksDatabaseFile;

    public function test_migration_sets_table_name_and_class_name()
    {
        $this->mockDatabaseFile([
            'migrations' => [
                'foos' => [
                    'name' => 'string',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $this->assertFileExists(
            $file = $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_foos_table.php')
        );

        $this->assertStringContainsString('class CreateFoosTable extends Migration', $this->filesystem->get($file));
        $this->assertStringContainsString("Schema::create('foos'", $this->filesystem->get($file));
        $this->assertStringContainsString("Schema::dropIfExists('foos');", $this->filesystem->get($file));
    }

    public function test_migration_receives_column_declarations()
    {
        $this->mockDatabaseFile([
            'model' => [
                'test',
            ],
            'migrations' => [
                'foos' => [
                    'foo' => 'bar:quz',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $this->assertFileExists(
            $file = $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_foos_table.php')
        );

        $this->assertStringContainsString("\$table->bar('foo', 'quz');", $this->filesystem->get($file));
    }

    public function test_migration_does_not_receives_relations()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Relations are not allowed in the [relation] column definition.');

        $this->mockDatabaseFile([
            'migrations' => [
                'foos' => [
                    'relation' => 'hasMany:things',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_error_when_migration_table_name_duplicated_for_normal_model()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("The migration already has a table named 'foos' for the [Foo] model.");

        $this->mockDatabaseFile([
            'models' => [
                'Foo' => [
                    'columns' => [
                        'foo' => 'bar',
                    ],
                ]
            ],
            'migrations' => [
                'foos' => [
                    'foo' => 'bar',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_migration_overrides_automatic_pivot_table()
    {
        $this->mockDatabaseFile([
            'models' => [
                'Post' => [
                    'tags' => 'belongsToMany:Tag',
                ],
                'Tag' => [
                    'posts' => 'belongsToMany:Post'
                ]
            ],
            'migrations' => [
                'post_tag' => [
                    'foo' => 'bar',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $this->assertFileExists(
            $file = $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_post_tag_table.php')
        );

        $this->assertStringContainsString('class CreatePostTagTable extends Migration', $this->filesystem->get($file));
        $this->assertStringContainsString("\$table->bar('foo');", $this->filesystem->get($file));
        $this->assertStringNotContainsString("\$table->unsignedBigInteger('post_id');", $this->filesystem->get($file));
        $this->assertStringNotContainsString("\$table->unsignedBigInteger('tag_id');", $this->filesystem->get($file));
    }

    protected function tearDown() : void
    {
        $this->cleanProject();

        parent::tearDown();
    }
}