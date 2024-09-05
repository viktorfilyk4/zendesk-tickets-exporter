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

2. Rename `.env.example` to `.env`. Set `PHPSTORM_SERVER_NAME` 
value to `your_desired_server_name` from step 1.
3. In root directory, run `composer install`.
4. In root directory, run `docker-compose up`.
   
Now you can work in `app` folder. Any changes will be synced with Docker container.

## Production
`docker-compose -f docker-compose.prod.yml up`
