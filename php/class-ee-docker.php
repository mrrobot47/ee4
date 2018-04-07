<?php

use function \EE\Utils\default_debug;
use function \EE\Utils\default_launch;
use function \EE\Utils\mustache_render;

class EE_DOCKER {

	/**
	 * Generate docker-compose.yml according to requirement.
	 *
	 * @param array $filters Array of flags to determine the docker-compose.yml generation.
	 *                       Empty/Default -> Generates default WordPress docker-compose.yml
	 *                       ['le']        -> Enables letsencrypt in the generation.
	 *
	 * @return String docker-compose.yml content string.
	 */
	public static function generate_docker_composer_yml( array $filters = [] ) {
		$base = array();

		$restart_default = array( 'name' => 'always' );
		$network_default = array( 'name' => 'site-network' );

		// db configuration.
		$db['service_name'] = array( 'name' => 'db' );
		$db['image']        = array( 'name' => 'easyengine/mariadb' );
		$db['restart']      = $restart_default;
		$db['volumes']      = array( array( 'vol' => array( 'name' => './app/db:/var/lib/mysql' ) ) );
		$db['environment']  = array(
			'env' => array(
				array( 'name' => 'MYSQL_ROOT_PASSWORD' ),
				array( 'name' => 'MYSQL_DATABASE' ),
				array( 'name' => 'MYSQL_USER' ),
				array( 'name' => 'MYSQL_PASSWORD' ),
			),
		);
		$db['networks']     = $network_default;

		// PHP configuration.
		$php['service_name'] = array( 'name' => 'php' );
		$php['image']        = array( 'name' => 'easyengine/php' );
		$php['depends_on']   = array( 'name' => 'db' );
		$php['restart']      = $restart_default;
		$php['volumes']      = array( array( 'vol' => array( array( 'name' => './app/src:/var/www/html' ), array( 'name' => './config/php-fpm/php.ini:/usr/local/etc/php/php.ini' ) ) ) );
		$php['environment']  = array(
			'env' => array(
				array( 'name' => 'WORDPRESS_DB_HOST' ),
				array( 'name' => 'WORDPRESS_DB_USER=${MYSQL_USER}' ),
				array( 'name' => 'WORDPRESS_DB_PASSWORD=${MYSQL_PASSWORD}' ),
				array( 'name' => 'USER_ID=${USER_ID}' ),
				array( 'name' => 'GROUP_ID=${GROUP_ID}' ),
			),
		);
		$php['networks']     = $network_default;


		// nginx configuration..
		$nginx['service_name'] = array( 'name' => 'nginx' );
		$nginx['image']        = array( 'name' => 'easyengine/nginx' );
		$nginx['depends_on']   = array( 'name' => 'php' );
		$nginx['restart']      = $restart_default;
		if ( in_array( 'le', $filters ) ) {
			$nginx['environment'] = array( 'env' => array( array( 'name' => 'VIRTUAL_HOST' ), array( 'name' => 'LETSENCRYPT_HOST=${VIRTUAL_HOST}' ), array( 'name' => 'LETSENCRYPT_EMAIL=${VIRTUAL_HOST_EMAIL}' ) ) );
		} else {
			$nginx['environment'] = array( 'env' => array( array( 'name' => 'VIRTUAL_HOST' ) ) );
		}
		$nginx['volumes']  = array( array( 'vol' => array( array( 'name' => './app/src:/var/www/html' ), array( 'name' => './config/nginx/default.conf:/etc/nginx/conf.d/default.conf' ), array( 'name' => './logs/nginx:/var/log/nginx' ), array( 'name' => './config/nginx/common:/usr/local/openresty/nginx/conf/common' ) ) ) );
		$nginx['networks'] = $network_default;

		// PhpMyAdmin configuration.
		$phpmyadmin['service_name'] = array( 'name' => 'phpmyadmin' );
		$phpmyadmin['image']        = array( 'name' => 'easyengine/phpmyadmin' );
		$phpmyadmin['restart']      = $restart_default;
		$phpmyadmin['environment']  = array( 'env' => array( array( 'name' => 'VIRTUAL_HOST=pma.${VIRTUAL_HOST}' ) ) );
		$phpmyadmin['networks']     = $network_default;

		// mailhog configuration.
		$mail['service_name'] = array( 'name' => 'mail' );
		$mail['image']        = array( 'name' => 'easyengine/mail' );
		$mail['restart']      = $restart_default;
		$mail['command']      = array( 'name' => '["-invite-jim=false"]' );
		if ( in_array( 'le', $filters ) ) {
			$mail['environment'] = array( 'env' => array( array( 'name' => 'VIRTUAL_HOST=mail.${VIRTUAL_HOST}' ), array( 'name' => 'VIRTUAL_PORT=8025' ), array( 'name' => 'LETSENCRYPT_HOST=${VIRTUAL_HOST}' ), array( 'name' => 'LETSENCRYPT_EMAIL=${VIRTUAL_HOST_EMAIL}' ) ) );
		} else {
			$mail['environment'] = array( 'env' => array( array( 'name' => 'VIRTUAL_HOST=mail.${VIRTUAL_HOST}' ), array( 'name' => 'VIRTUAL_PORT=8025' ) ) );
		}
		$mail['networks'] = $network_default;

		$base[] = $db;
		$base[] = $php;
		$base[] = $nginx;
		$base[] = $mail;
		$base[] = $phpmyadmin;

		$binding = array(
			'services' => $base,
			'network'  => true,
		);

		$docker_compose_yml = ( mustache_render( 'docker-compose.mustache', $binding ) );

		return $docker_compose_yml;
	}

	/**
	 * Check and Start or create container if not running.
	 *
	 * @param String $container Name of the
	 *
	 * @return bool success.
	 */
	public static function boot_container( $container ) {
		$status = self::container_status( $container );
		if ( $status ) {
			if ( 'exited' === $status ) {
				return self::start_container( $container );
			} else {
				return true;
			}
		} else {
			return self::create_container( $container );
		}
	}

	public static function container_status( $container ) {
		$status = EE::launch( "docker inspect -f '{{.State.Running}}' $container", false, true );
		default_debug( $status );
		if ( ! $status->return_code ) {
			if ( preg_match( '/true/', $status->stdout ) ) {
				return 'running';
			} else {
				return 'exited';
			}
		}

		return false;
	}

	/**
	 * Function to start the container if it exists but is not running.
	 *
	 * @param String $container Container to be started.
	 *
	 * @return bool success.
	 */
	public static function start_container( $container ) {
		return default_launch( "docker start $container" );
	}

	/**
	 * Function to create and start the container if it does not exist.
	 *
	 * @param String $container Container to be created.
	 * @param String $command   Command to launch the container.
	 *
	 * @return bool success.
	 */
	private function create_container( $container, $command = '' ) {

		$HOME = HOME;

		switch ( $container ) {

			case 'ee4_nginx-proxy':
				$command = "docker run --name ee4_nginx-proxy -e LOCAL_USER_ID=`id -u` -e LOCAL_GROUP_ID=`id -g` --restart=always -d -p 80:80 -p 443:443 -v $HOME/.ee4/nginx/certs:/etc/nginx/certs -v $HOME/.ee4/nginx/dhparam:/etc/nginx/dhparam -v $HOME/.ee4/nginx/conf.d:/etc/nginx/conf.d -v $HOME/.ee4/nginx/htpasswd:/etc/nginx/htpasswd -v $HOME/.ee4/nginx/vhost.d:/etc/nginx/vhost.d -v /var/run/docker.sock:/tmp/docker.sock:ro -v $HOME/.ee4:/app/ee4 easyengine/nginx-proxy";
				break;

			case 'ee4_redis':
				$command = "docker run --name ee4_redis -d --restart=always easyengine/redis";
				break;
		}

		return default_launch( $command );
	}

	/**
	 * Create docker network.
	 *
	 * @param String $name Name of the network to be created.
	 *
	 * @return bool success.
	 */
	public static function create_network( $name ) {
		return default_launch( "docker network create $name" );
	}

	/**
	 * Connect to given docker network.
	 *
	 * @param String $name       Name of the network that has to be connected.
	 * @param String $connect_to Name of the network to which connection has to be established.
	 *
	 * @return bool success.
	 */
	public static function connect_network( $name, $connect_to ) {
		return default_launch( "docker network connect $name $connect_to" );
	}

	/**
	 * Remove docker network.
	 *
	 * @param String $name Name of the network to be removed.
	 *
	 * @return bool success.
	 */
	public static function rm_network( $name ) {
		return default_launch( "docker network rm $name" );
	}

	/**
	 * Disconnect docker network.
	 *
	 * @param String $name         Name of the network to be disconnected.
	 * @param String $connected_to Name of the network from which it has to be disconnected.
	 *
	 * @return bool success.
	 */
	public static function disconnect_network( $name, $connected_to ) {
		return default_launch( "docker network disconnect $name $connected_to" );
	}

	/**
	 * Function to boot the containers.
	 *
	 * @param String $dir Path to docker-compose.yml.
	 *
	 * @return bool success.
	 */
	public static function docker_compose_up( $dir ) {
		$chdir_return_code = chdir( $dir );
		if ( $chdir_return_code ) {
			$docker_compose_up = EE::launch( "docker-compose up -d", false, true );
			default_debug( $docker_compose_up );

			if ( $docker_compose_up->return_code ) {
				throw new Exception( 'There was some error in docker-compose up.' );
			}
		} else {
			throw new Exception( 'Error in changing directory.' );
		}
	}

	/**
	 * Function to destroy the containers.
	 *
	 * @param String $dir Path to docker-compose.yml.
	 *
	 * @return bool success.
	 */
	public static function docker_compose_down( $dir ) {
		$chdir_return_code = chdir( $dir );
		if ( $chdir_return_code ) {
			return default_launch( 'docker-compose down' );
		}

		return false;
	}
}
