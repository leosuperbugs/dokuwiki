<?php
use Illuminate\Database\Capsule\Manager as Capsule;

require __DIR__ .'vendor/autoload.php';

require __DIR__ .'/database.php';

Capsule::schema()->create('tasks', function($table) {
	$table->inctements('id');
	$table->string('title');
	$table->string('body');
	$table->timestamps();
});

echo 'Table created successfully!';
