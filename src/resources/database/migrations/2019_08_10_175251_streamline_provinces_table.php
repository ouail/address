<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Konekt\Address\Models\ProvinceTypeProxy;

class StreamlineProvincesTable extends Migration
{
    /**
     * With Laravel up to v5.8 renaming any column in a table that also has a column
     * of type enum is not currently supported. This problem only seems to affect
     * MySQL, this workaround provides a bit dirty but working solution for it
     *
     * @see https://laravel.com/docs/5.8/migrations#modifying-columns
     * @see https://stackoverflow.com/q/33140860/1016746
     */
    public function __construct()
    {
        $platform = DB::getDoctrineSchemaManager()->getDatabasePlatform();

        if (!$platform->hasDoctrineTypeMappingFor('enum')) {
            $platform->registerDoctrineTypeMapping('enum', 'string');
        }
    }

    public function up()
    {
        /** based on ISO 3166-2 https://en.wikipedia.org/wiki/ISO_3166-2 */
        Schema::table('provinces', function (Blueprint $table) {
            // Get rid of enum field, they're fkcn painful with Laravel
            $table->string('type', 16)->default(ProvinceTypeProxy::defaultValue())->change();
            $table->integer('parent_id')->unsigned()->nullable();

            $table->index('code', 'provinces_code_index');
            $table->unique(['country_id', 'code'], 'provinces_country_id_code_index');
            $table->foreign('parent_id')
                  ->references('id')
                  ->on('provinces')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('provinces', function (Blueprint $table) {
            $table->dropUnique('provinces_country_id_code_index');
            $table->dropIndex('provinces_code_index');
            $table->enum('type', ProvinceTypeProxy::values())->default(ProvinceTypeProxy::defaultValue())->change();
            $table->dropColumn('parent_id');
        });
    }
}
