# PXE-Manager

Very simple web-app for managing PXE Boot environments.

The app allows you to create "Bootable Images" (Pre-defined Templates that define a pxelinux config snippet and a kickstart config) and then set Servers to boot from these images.

Bootable Images define a set of variables that can be specified when setting a server to boot from the image.

Creating a server that has a bootable image will create the appropriate file in `/tftpboot/pxelinux.cfg/` to cause the server to boot the template.

With the exception of the dynamic files/urls created by the web app, all other files (eg `vmlinuz` and `initrd` files) must already exist on the server within the `/tftpboot/` directory.

Currently all access is unauthenticated. This will change in future.

## Deploying:

Requires PHP 7.1 or later.

 - Clone the repo
 - Run `composer install` from within the checkout
 - Set any custom settings within `config.local.php`
 - Create the `template_c` directory and sqlite db file and make them writable by the webserver user
 - Ensure the webserver user can write to `/tftpboot/pxelinux.cfg/`
 - run `php admin/init.php` to create/update the database schema
 - Point apache at the ./public directory

## Updating:

 - `git pull` from within the repo checkout
 - Run `composer install`
 - run `php admin/init.php` to update the database schema (this will kepe any current database entries)

## Pull Requests
Pull requests are appreciated and welcome.

## Questions
I can be found idling on various different IRC Networks, but the best way to get in touch would be to message "Dataforce" on Quakenet, or drop me a mail (email address is in my [github profile](https://github.com/ShaneMcC))

## Comments, Questions, Bugs, Feature Requests etc.

Bugs and Feature Requests should be raised on the [[https://github.com/ShaneMcC/PXE-Manager/issues|issue tracker on github]], and I'm happy to recieve code pull requests via github.

I can be found idling on various different IRC Networks, but the best way to get in touch would be to message "Dataforce" on Quakenet, or drop me a mail (email address is in my [github profile](https://github.com/ShaneMcC))
