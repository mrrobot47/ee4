<?php

class EE_DB {

	private static $db;

	public function __construct() {
		if ( empty( self::$db ) ) {
			self::init_db();
		}
	}

	/**
	 * Function to initialize db and db connection.
	 */
	public static function init_db() {
		if ( ! ( file_exists( DB ) ) ) {
			self::$db = self::create();
		} else {
			self::$db = new SQLite3( DB );
			if ( ! self::$db ) {
				EE::error( self::$db->lastErrorMsg() );
			}
		}
	}

	/**
	 * Sqlite database creation.
	 */
	public static function create() {
		self::$db = new SQLite3( DB );
		$query    = "CREATE TABLE sites (
						id INTEGER NOT NULL, 
						sitename VARCHAR, 
						site_type VARCHAR, 
						proxy_type VARCHAR, 
						cache_type VARCHAR, 
						site_path VARCHAR, 
						created_on DATETIME, 
						is_enabled BOOLEAN DEFAULT 1, 
						is_ssl BOOLEAN DEFAULT 0, 
						storage_fs VARCHAR, 
						storage_db VARCHAR, 
						db_name VARCHAR, 
						db_user VARCHAR, 
						db_password VARCHAR, 
						db_host VARCHAR, 
						is_hhvm BOOLEAN DEFAULT 0, 
						is_pagespeed BOOLEAN DEFAULT 0, 
						php_version VARCHAR, 
						PRIMARY KEY (id), 
						UNIQUE (sitename), 
						CHECK (is_enabled IN (0, 1)), 
						CHECK (is_ssl IN (0, 1)), 
						CHECK (is_hhvm IN (0, 1)), 
						CHECK (is_pagespeed IN (0, 1))
					);";
		self::$db->exec( $query );
	}

	/**
	 * Insert row in table.
	 *
	 * @param array $data in key value pair.
	 *
	 * @return bool
	 */
	public static function insert( $data ) {

		if ( empty ( self::$db ) ) {
			self::init_db();
		}

		$table_name = TABLE;

		$fields  = '`' . implode( '`, `', array_keys( $data ) ) . '`';
		$formats = '"' . implode( '", "', $data ) . '"';

		$insert_query = "INSERT INTO `$table_name` ($fields) VALUES ($formats);";

		$insert_query_exec = self::$db->exec( $insert_query );

		if ( ! $insert_query_exec ) {
			EE::debug( self::$db->lastErrorMsg() );
			self::$db->close();
		} else {
			self::$db->close();

			return true;
		}

		return false;
	}

	/**
	 * @param array $columns
	 * @param array $where
	 * Select data from the database.
	 *
	 * @return array|bool
	 */
	public static function select( $columns = array(), $where = array() ) {

		if ( empty ( self::$db ) ) {
			self::init_db();
		}

		$table_name = TABLE;

		$conditions = array();
		if ( empty( $columns ) ) {
			$columns = '*';
		} else {
			$columns = implode( ', ', $columns );
		}

		foreach ( $where as $key => $value ) {
			$conditions[] = "`$key`='" . $value . "'";
		}

		$conditions = implode( ' AND ', $conditions );

		$select_data_query = "SELECT {$columns} FROM `$table_name`";

		if ( ! empty( $conditions ) ) {
			$select_data_query .= " WHERE $conditions";
		}

		$select_data_exec = self::$db->query( $select_data_query );
		$select_data      = array();
		if ( $select_data_exec ) {
			while ( $row = $select_data_exec->fetchArray( SQLITE3_ASSOC ) ) {
				$select_data[] = $row;
			}
		}
		if ( empty( $select_data ) ) {
			return false;
		}

		return $select_data;
	}


	/**
	 * Update row in table.
	 *
	 * @param        $data
	 * @param        $where
	 *
	 * @return bool
	 */
	public static function update( $data, $where ) {
		if ( empty ( self::$db ) ) {
			self::init_db();
		}

		$table_name = TABLE;

		$fields     = array();
		$conditions = array();
		foreach ( $data as $key => $value ) {
			$fields[] = "`$key`='" . $value . "'";
		}
		foreach ( $where as $key => $value ) {
			$conditions[] = "`$key`='" . $value . "'";
		}
		$fields     = implode( ', ', $fields );
		$conditions = implode( ' AND ', $conditions );
		if ( ! empty( $fields ) ) {
			$update_query      = "UPDATE `$table_name` SET $fields WHERE $conditions";
			$update_query_exec = self::$db->exec( $update_query );
			if ( ! $update_query_exec ) {
				EE::debug( self::$db->lastErrorMsg() );
				self::$db->close();
			} else {
				self::$db->close();

				return true;
			}
		}

		return false;
	}

	/**
	 * Delete data from table.
	 *
	 * @param        $where
	 *
	 * @return bool
	 */
	public static function delete( $where ) {

		$table_name = TABLE;

		$conditions = array();
		foreach ( $where as $key => $value ) {
			$conditions[] = "`$key`='" . $value . "'";
		}

		$conditions   = implode( ' AND ', $conditions );
		$delete_query = "DELETE FROM `$table_name` WHERE $conditions";

		$delete_query_exec = self::$db->exec( $delete_query );

		if ( ! $delete_query_exec ) {
			EE::debug( self::$db->lastErrorMsg() );
			self::$db->close();
		} else {
			self::$db->close();

			return true;
		}

		return false;
	}

	/**
	 * Check if a site entry exists in the database.
	 *
	 * @param String $site_name Name of the site to be checked.
	 *
	 * @return bool Success.
	 */
	public static function site_in_db( $site_name ) {

		if ( empty ( self::$db ) ) {
			self::init_db();
		}

		$site = self::select( array( 'id' ), array( 'sitename' => $site_name ) );

		if ( $site ) {
			return true;
		} else {
			return false;
		}
	}
}
