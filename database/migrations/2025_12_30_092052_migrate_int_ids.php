<?php

use App\Models\Service;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Migrate all "leftover" int(10) id columns to the future-safe and laravel-standard bigint(20)
 */
return new class extends Migration
{

    protected $concernsTables = [
        'absence_user',
        'absences',
        'approvals',
        'attachments',
        'baptisms',
        'cities',
        'city_day',
        'city_user',
        'comments',
        'funerals',
        'locations',
        'migrations',
        'parish_user',
        'parishes',
        'permissions',
        'replacement_user',
        'replacements',
        'roles',
        'service_groups',
        'service_service_group',
        'service_tag',
        'service_user',
        'services',
        'street_ranges',
        'subscriptions',
        'tags',
        'user_approver',
        'user_home',
        'user_settings',
        'users',
        'weddings',
    ];


    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // special case: locations: location_id could be 0 so far -> must be set to null for the constraint to work
        Service::withoutGlobalScopes()->where('location_id', 0)->update(['location_id' => null]);

        foreach ($this->concernsTables as $table) {
            $this->migrateIdFieldWithAllConstraints($table, 'unsignedBigInteger');
            $this->migrateUnconstrainedIdFields($table, 'unsignedBigInteger');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->concernsTables as $table) {
            $this->migrateIdFieldWithAllConstraints($table, 'unsignedInteger');
        }
    }

    function isColumnNullable(string $table, string $column): bool
    {
        $database = DB::getDatabaseName();

        return DB::table('information_schema.COLUMNS')
                ->where('TABLE_SCHEMA', $database)
                ->where('TABLE_NAME', $table)
                ->where('COLUMN_NAME', $column)
                ->value('IS_NULLABLE') === 'YES';
    }

    protected function migrateIdFieldWithAllConstraints(string $tableName, string $targetType) {
        $constraints = $this->foreignKeyColumnsReferencingTableId($tableName);
        // yields fk_table,fk_column,CONSTRAINT_NAME,DELETE_RULE,UPDATE_RULE

        // drop existing foreign keys
        foreach ($constraints as $c) {
            Schema::table($c->fk_table, function (Blueprint $t) use ($c) {
                $t->dropForeign($c->CONSTRAINT_NAME);
            });
        }

        // change child fields to correct type
        foreach ($constraints as $c) {
            Schema::table($c->fk_table, function (Blueprint $t) use ($c, $targetType) {
                if ($this->isColumnNullable($c->fk_table, $c->fk_column)) {
                    $t->$targetType($c->fk_column)->nullable()->change();
                } else {
                    $t->$targetType($c->fk_column)->change();
                }
            });
        }

        // change id field to correct type (with auto-increment)
        Schema::table($tableName, function (Blueprint $t) use ($targetType) {
            $t->$targetType('id', true)->change();
        });

        // rebuild constraints from cached rules
        foreach ($constraints as $c) {
            Schema::table($c->fk_table, function (Blueprint $t) use ($c, $tableName) {
                $fk = $t->foreign($c->fk_column)->references('id')->on($tableName);

                if ($c->UPDATE_RULE === 'CASCADE') {
                    $fk->onUpdate('cascade');
                }

                if ($c->DELETE_RULE === 'CASCADE') {
                    $fk->onDelete('cascade');
                }
                if ($c->DELETE_RULE === 'SET NULL') {
                    $fk->nullOnDelete();
                }

            });
        }
    }


    function foreignKeyColumnsReferencingTableId(string $table): array
    {
        $database = DB::getDatabaseName();

        return DB::table('information_schema.REFERENTIAL_CONSTRAINTS as rc')
            ->join('information_schema.KEY_COLUMN_USAGE as kcu', function ($join) {
                $join->on('rc.CONSTRAINT_NAME', '=', 'kcu.CONSTRAINT_NAME')
                    ->on('rc.CONSTRAINT_SCHEMA', '=', 'kcu.TABLE_SCHEMA');
            })
            ->where('rc.CONSTRAINT_SCHEMA', $database)
            ->where('kcu.REFERENCED_TABLE_NAME', $table)
            ->where('kcu.REFERENCED_COLUMN_NAME', 'id')
            ->get([
                      'kcu.TABLE_NAME as fk_table',
                      'kcu.COLUMN_NAME as fk_column',
                      'rc.DELETE_RULE',
                      'rc.UPDATE_RULE',
                      'rc.CONSTRAINT_NAME',
                  ])
            ->toArray();
    }

    function migrateUnconstrainedIdFields(string $tableName, string $targetType): void {
        $idFieldName = Str::singular($tableName).'_id';
        $fields = $this->findUnconstrainedForeignKeyColumns($idFieldName);
        foreach ($fields as $foreignTable => $foreignColumn) {

            // first delete orphans, otherwise constraint will fail
            DB::table($foreignTable.' as f')
                ->leftJoin($tableName.' as t', 't.id', '=', 'f.'.$idFieldName)
                ->whereNotNull('f.'.$idFieldName)
                ->whereNull('t.id')
                ->delete();


            Schema::table($foreignTable, function (Blueprint $t) use ($foreignTable, $foreignColumn, $tableName, $targetType) {
                if ($this->isColumnNullable($foreignTable, $foreignColumn)) {
                    $t->$targetType($foreignColumn)->nullable()->change();
                } else {
                    $t->$targetType($foreignColumn)->change();
                }
                $t->foreign($foreignColumn)->references('id')->on($tableName)->cascadeOnDelete();
            });
        }
    }

    /**
     * Find very old foreign key columns without constraints
     * @param string $columnName
     * @return array
     */
    function findUnconstrainedForeignKeyColumns(string $columnName): array
    {
        $database = DB::getDatabaseName();

        // Alle Spalten mit diesem Namen finden
        $columns = DB::table('information_schema.COLUMNS as c')
            ->leftJoin('information_schema.KEY_COLUMN_USAGE as k', function ($join) use ($database) {
                $join->on('k.TABLE_SCHEMA', '=', 'c.TABLE_SCHEMA')
                    ->on('k.TABLE_NAME', '=', 'c.TABLE_NAME')
                    ->on('k.COLUMN_NAME', '=', 'c.COLUMN_NAME')
                    ->whereNotNull('k.REFERENCED_TABLE_NAME'); // nur echte FKs
            })
            ->where('c.TABLE_SCHEMA', $database)
            ->where('c.COLUMN_NAME', $columnName)
            ->whereNull('k.CONSTRAINT_NAME') // kein FK vorhanden
            ->orderBy('c.TABLE_NAME')
            ->get(['c.TABLE_NAME', 'c.COLUMN_NAME']);

        // In gewünschtes Format: table => field
        $result = [];
        foreach ($columns as $col) {
            $result[$col->TABLE_NAME] = $col->COLUMN_NAME;
        }

        return $result;
    }


};
