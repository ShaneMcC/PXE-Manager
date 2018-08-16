# Simple Dockerfile to run PXE-Manager
#
# Required volumes:
#   - `/var/lib/tftpboot` - This is where tftp data will go.
#   - `/pxemanager/data`  - data persistence (eg server and image data)
#
# A `config.local.php` file can be bind-mounted to `/pxemanager/config.local.php`
# if required.
#
# The `/var/lib/tftpboot` volume should be shared with a tftp server that
# actually serves the data. This will need to be pre-seeded manually with the
# required pxelinux.0, ldlinux.c32 and ipxe.lkrn files.

FROM shanemcc/docker-apache-php-base:latest
MAINTAINER Shane Mc Cormack <dataforce@dataforce.org.uk>

COPY . /pxemanager

RUN \
  rm -Rfv /var/www/html && \
  ln -s /pxemanager/public /var/www/html && \
  mkdir -p /pxemanager/data && \
  mkdir -p  /var/lib/tftpboot/pxelinux.cfg/ && \
  chown -Rfv www-data: /pxemanager/ /var/www/  /var/lib/tftpboot/pxelinux.cfg/ && \
  su www-data --shell=/bin/bash -c "cd /pxemanager; /usr/bin/composer install; "

# Replace ENTRYPOINT to let us run init.php
ENTRYPOINT ["/pxemanager/docker/entrypoint.sh"]
# Replacing ENTRYPOINT also unsets CMD (https://github.com/moby/moby/issues/19611), so reset it.
CMD ["apache2-foreground"]
