# EEv4

## Requirements

* Docker
* Docker-Compose
* PHP

## Installing

Once you've verified requirements, download the [easyengine.phar](https://raw.githubusercontent.com/easyengine/easyengine-builds/master/easyengine.phar) file using `wget` or `curl`:

```bash
curl -O https://raw.githubusercontent.com/easyengine/easyengine-builds/master/easyengine.phar
```

Next, check the Phar file to verify that it's working:

```bash
php easyengine.phar cli info
```

To use EEv4 from the command line by typing `ee4`, make the file executable and move it to somewhere in your PATH. For example:

```bash
chmod +x easyengine.phar
sudo mv easyengine.phar /usr/local/bin/ee4
```

If EE was installed successfully, you should see something like this when you run `ee4 cli version`:

```bash
$ ee4 cli version
EE 0.0.1
```

## Commands

### ee4 site

Creates, lists and deletes WordPress websites.

~~~
ee4 site
~~~


**EXAMPLES**

    # Create simple WordPress website. No parameter flag defaults to --wp
    $ ee4 site create site.test
    Success: ee4_nginx-proxy container launched successfully.
    Configuring project...
    Creating WordPress site site.test...
    Copying configuration files...
    Success: Configuration files copied.
    Updating configuration files...
    Success: Configuration files updated.
    Success: Network started.
    Success: Site connected to ee4_nginx-proxy.
    Success: Host entry successfully added.
    Checking and verifying site-up status. This may take some time.
    ..........
    Installing WordPress site...
    Success: http://site.test has been created successfully!
    Access phpMyAdmin :	pma.site.test
    Access mail :	mail.site.test
    Site Title :	site.test
    Username :	admin
    Password :	DrwKpMsaGiuI
    DB Password :	Si23era8cnmR
    E-Mail :	mail@site.test
    Site entry created.

    $ ee4 site create site2.test --wpredis --user=admin --pass=admin --email=admin@admin.example --title="Site by EasyEngine"
    Configuring project...
    Creating WordPress site site2.test...
    Copying configuration files...
    Success: Configuration files copied.
    Updating configuration files...
    Success: Configuration files updated.
    Success: Network started.
    Success: Site connected to ee4_nginx-proxy.
    Success: Site connected to ee4_redis.
    Success: Host entry successfully added.
    Checking and verifying site-up status. This may take some time.
    ..........
    Installing WordPress site...
    Success: http://site2.test has been created successfully!
    Access phpMyAdmin :	pma.site2.test
    Access mail :	mail.site2.test
    Site Title :	Site by EasyEngine
    Username :	admin
    Password :	admin
    DB Password :	B4B6ggCBcJyE
    E-Mail :	admin@admin.example
    Site entry created.

    $ ee4 site list
    List of Sites:

      - site.test
      - site2.test

    $ ee4 site delete site.test
    [site.test] Docker Containers removed.
    [site.test] Disconnected from Docker network nginx-proxy
    [site.test] Docker network nginx-proxy removed.
    [sudo] password for mrrobot: 
    [site.test] site root removed.
    Removing database entry.
    Site site.test deleted.

### ee4 site create

~~~
ee4 site create <site-name> [--wp|--wpredis] [--letsencrypt] [--title=<title>] [--user=<username>] [--pass=<password>] [--email=<email>]
~~~

Creates WordPress site. 

**OPTIONS**

	[--wp]
		Creates simple WordPress website.

	[--wpredis]
		Creates WordPress website with Redis caching.

	[--letsencrypt]
		Generates letsencrypt certificates for the site.

    [--title=<title>]
    	Title of website.

    [--user=<username>]
		Username of the WordPress administrator.
	 
	[--pass=<password>]
		Password for the WordPress administrator.
	
	[--email=<email>]
		E-Mail of the WordPress administrator.

### ee4 site list

~~~
ee4 site list
~~~

Lists all the sites created by EasyEngine.

### ee4 site delete

~~~
ee4 site delete <site-name>
~~~

Deletes the given site if it was created by EasyEngine.

### ee4 wp

Run all the wp commands for site created by EasyEngine.

~~~
ee4 wp
~~~

### Usage

~~~
ee4 wp <site-name> <wp-command>
~~~

**EXAMPLES**

    $ ee4 wp site.test plugin list
    +---------+----------+-----------+---------+
    | name    | status   | update    | version |
    +---------+----------+-----------+---------+
    | akismet | inactive | available | 4.0.2   |
    | hello   | inactive | none      | 1.6     |
    +---------+----------+-----------+---------+

    $ ee4 wp site.test user create author1 author1@site.test --user_pass=password --role=administrator
    Success: Created user 2.