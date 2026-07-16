<?php

declare(strict_types=1);

namespace Migration;

use Cycle\Migrations\Migration;

class OrmDefaultCreateDleFakerStaticAndAssets extends Migration {

	protected const DATABASE = 'default';

	public function up(): void {
		$this->table('faker_static_files')
			->addColumn('id', 'bigPrimary', [
				'nullable'      => false,
				'defaultValue'  => null,
				'size'          => 20,
				'autoIncrement' => true,
				'unsigned'      => false,
				'zerofill'      => false,
				'comment'       => '',
			])
			->addColumn('kind', 'string', [
				'nullable'     => false,
				'defaultValue' => null,
				'size'         => 16,
				'comment'      => '',
			])
			->addColumn('original_name', 'string', [
				'nullable'     => false,
				'defaultValue' => null,
				'size'         => 255,
				'comment'      => '',
			])
			->addColumn('stored_name', 'string', [
				'nullable'     => false,
				'defaultValue' => null,
				'size'         => 255,
				'comment'      => '',
			])
			->addColumn('mime', 'string', [
				'nullable'     => false,
				'defaultValue' => '',
				'size'         => 128,
				'comment'      => '',
			])
			->addColumn('size_bytes', 'integer', [
				'nullable'     => false,
				'defaultValue' => 0,
				'comment'      => '',
			])
			->addColumn('created_at', 'datetime', [
				'nullable'     => false,
				'defaultValue' => null,
				'comment'      => '',
			])
			->addIndex(['kind'], ['name' => 'dle_faker_static_files_index_kind', 'unique' => false])
			->setPrimaryKeys(['id'])
			->create();

		$this->table('faker_template_assets')
			->addColumn('id', 'bigPrimary', [
				'nullable'      => false,
				'defaultValue'  => null,
				'size'          => 20,
				'autoIncrement' => true,
				'unsigned'      => false,
				'zerofill'      => false,
				'comment'       => '',
			])
			->addColumn('template_id', 'integer', [
				'nullable'     => false,
				'defaultValue' => 0,
				'comment'      => '',
			])
			->addColumn('kind', 'string', [
				'nullable'     => false,
				'defaultValue' => null,
				'size'         => 16,
				'comment'      => '',
			])
			->addColumn('original_name', 'string', [
				'nullable'     => false,
				'defaultValue' => null,
				'size'         => 255,
				'comment'      => '',
			])
			->addColumn('stored_name', 'string', [
				'nullable'     => false,
				'defaultValue' => null,
				'size'         => 255,
				'comment'      => '',
			])
			->addColumn('mime', 'string', [
				'nullable'     => false,
				'defaultValue' => '',
				'size'         => 128,
				'comment'      => '',
			])
			->addColumn('size_bytes', 'integer', [
				'nullable'     => false,
				'defaultValue' => 0,
				'comment'      => '',
			])
			->addIndex(['template_id'], ['name' => 'dle_faker_template_assets_index_template_id', 'unique' => false])
			->setPrimaryKeys(['id'])
			->create();
	}

	public function down(): void {
		$this->table('faker_template_assets')->drop();
		$this->table('faker_static_files')->drop();
	}

}
