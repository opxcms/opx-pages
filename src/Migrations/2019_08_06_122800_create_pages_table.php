<?php

use Illuminate\Support\Facades\Schema;
use Core\Foundation\Database\OpxBlueprint;
use Core\Foundation\Database\OpxMigration;

class CreatePagesTable extends OpxMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        $this->schema->create('pages', static function (OpxBlueprint $table) {
            $table->id();
            $table->parentId();
            $table->alias();

            $table->name();
            $table->content();
            $table->image();
            $table->image('images');

            $table->data();

            $table->template();
            $table->template('child_template');
            $table->layout();
            $table->layout('child_layout');

            $table->publication();
            $table->seo();
            $table->robots();
            $table->sitemap();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['id', 'alias']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::drop('pages');
    }
}
