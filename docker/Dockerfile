FROM php:8.1-apache

# GD with JPEG support so the entrypoint can paint colored, labeled fixture photos.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libjpeg-dev libpng-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install -j$(nproc) mysqli gd \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/generate-fixtures.php /usr/local/share/generate-fixtures.php
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
CMD ["/usr/local/bin/entrypoint.sh"]

# Larger uploads for the gallery admin
RUN { \
      echo 'upload_max_filesize = 100M'; \
      echo 'post_max_size = 100M'; \
      echo 'max_file_uploads = 750'; \
      echo 'memory_limit = 256M'; \
      # The PHP files emit HTML before calling header()/setcookie(); shared
      # hosting masks this with output buffering, so we mirror that here.
      echo 'output_buffering = 4096'; \
      echo 'short_open_tag = On'; \
    } > /usr/local/etc/php/conf.d/uploads.ini
