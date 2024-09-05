FROM nginx:alpine
COPY nginx/default.conf /etc/nginx/conf.d
COPY ./app/ /var/www/app/