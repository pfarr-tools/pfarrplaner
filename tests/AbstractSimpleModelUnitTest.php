<?php
/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://codeberg.org/pfarr.tools/pfarrplaner
 * @version git: $Id$
 */

namespace Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

/**
 * Lightweight base for unit-testing models that do not use the full Action/Contract infrastructure.
 *
 * Subclasses only need to set $modelClass. Set $hasFactory = false or $hasPolicy = false to skip
 * those checks where they do not apply.
 */
abstract class AbstractSimpleModelUnitTest extends TestCase
{
    use RefreshDatabase;

    /** @var string Fully-qualified model class name */
    protected string $modelClass = '';

    /** @var bool Whether a Eloquent factory is expected to exist for this model */
    protected bool $hasFactory = true;

    /** @var bool Whether a Policy is expected to be registered for this model */
    protected bool $hasPolicy = true;

    /**
     * Read a protected property from an object via reflection.
     */
    protected static function getProtectedProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionClass($object);
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);
        return $prop->getValue($object);
    }

    /**
     * Test that the model class exists and is a descendant of Eloquent Model.
     */
    public function testModelClassExists(): void
    {
        $this->assertTrue(class_exists($this->modelClass), 'Model class ' . $this->modelClass . ' does not exist.');
        $this->assertTrue(
            is_subclass_of($this->modelClass, Model::class),
            $this->modelClass . ' does not extend ' . Model::class
        );
    }

    /**
     * Test that the model's database table exists.
     */
    public function testModelTableExists(): void
    {
        $model = new $this->modelClass;
        $this->assertTrue(
            Schema::hasTable($model->getTable()),
            'Missing table: ' . $model->getTable()
        );
    }

    /**
     * Test that every column listed in $fillable exists in the database table.
     */
    public function testColumnsFromFillableArePresent(): void
    {
        $model = new $this->modelClass;
        $table = $model->getTable();
        $fillable = $this->getProtectedProperty($model, 'fillable');
        $this->assertIsArray($fillable, $this->modelClass . '::$fillable is not an array');
        foreach ($fillable as $column) {
            $this->assertTrue(
                Schema::hasColumn($table, $column),
                'Table ' . $table . ' is missing column: ' . $column
            );
        }
    }

    /**
     * Test that a factory exists and can be instantiated (skipped when $hasFactory is false).
     */
    public function testFactoryExists(): void
    {
        if (!$this->hasFactory) {
            $this->markTestSkipped('No factory expected for ' . $this->modelClass);
        }

        $traits = class_uses_recursive($this->modelClass);
        $this->assertContains(
            HasFactory::class,
            $traits,
            $this->modelClass . ' does not use the HasFactory trait'
        );

        $factory = ($this->modelClass)::factory();
        $this->assertInstanceOf(Factory::class, $factory);
    }

    /**
     * Test that a Gate policy is registered for this model (skipped when $hasPolicy is false).
     */
    public function testPolicyExists(): void
    {
        if (!$this->hasPolicy) {
            $this->markTestSkipped('No policy expected for ' . $this->modelClass);
        }

        $policy = Gate::getPolicyFor($this->modelClass);
        $this->assertNotNull($policy, 'No registered policy for ' . $this->modelClass);
    }
}
