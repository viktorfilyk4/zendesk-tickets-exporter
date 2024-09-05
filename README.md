# Relokia Test Assignment

## Requirements
- Docker
- Docker Compose
- PHP (for development only)
- Composer (for development only)
- PHPStorm (for development only)

## Development
1. In development, we use XDebug for debuging purposes.  
In PHPStorm, open `Settings > PHP > Servers`.
Add server with next params:
```
- Name: your_desired_server_name
- Host: localhost
- Port: 8080
- Debugger: XDebug
- Enable checkbox 'Use path mappings...'. Here, map local `app` directory with `/var/www/html`
- Apply and save  
```
In PHPStorm, enable `Run > Start listening for PHP Debug Connections`.  
Optionally, enable `Run > Break at first line in PHP scripts`.  
(If you are on Linux, you may also need to uncomment `extra_hosts` in `docker-compose.yml`)  

2. Rename `.env.example` to `.env`.  
Set `PHPSTORM_SERVER_NAME` variable to `your_desired_server_name` from step one.  
Set other variables with your config.
3. In root directory, run `composer install`.
4. In root directory, run `docker-compose up`.
5. In browser, open `localhost:8080` to view changes.

## Production
1. Rename `.env.example` to `.env` and setup your config. (`PHPSTORM_SERVER_NAME` variable not needed)
2. Run `docker-compose -f docker-compose.prod.yml up`.
3. In browser, open `localhost:8080`. The program will generate `tickets.csv` file in `app/public` folder inside Docker container.
4. Copy file from container to host.  
Run `docker cp relokia-php-fpm-app:/var/www/app/public/tickets.csv ./app/public`
