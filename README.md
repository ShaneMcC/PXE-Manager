# PXE-Manager

Very simple web-app for managing PXE Boot environments.

The app allows you to create "Bootable Images" (Pre-defined Templates that define a pxelinux config snippet and a kickstart config) and then set Servers to boot from these images.

Bootable Images define a set of variables that can be specified when setting a server to boot from the image.

Creating a server that has a bootable image will create the appropriate file in `/tftpboot/pxelinux.cfg/` to cause the server to boot the template.

With the exception of the dynamic files/urls created by the web app, all other files (eg `vmlinuz` and `initrd` files) must already exist on the server within the `/tftpboot/` directory.

Currently all access is unauthenticated. This will change in future.

## Deploying

See the [Install Guide](https://github.com/ShaneMcC/PXE-Manager/wiki/Install-Guide) for information on how to deploy pxe-mananger.

## Updating

You can update pxe-manager by pulling an updated copy of the repo (`git pull`) and then re-running `composer install` and `php admin/init.php` (this will not overwrite your database, it will just apply any schema changes required.)

## Pull Requests
Pull requests are appreciated and welcome.

## Questions
I can be found idling on various different IRC Networks, but the best way to get in touch would be to message "Dataforce" on Quakenet, or drop me a mail (email address is in my [github profile](https://github.com/ShaneMcC))

## Comments, Questions, Bugs, Feature Requests etc.

Bugs and Feature Requests should be raised on the [[https://github.com/ShaneMcC/PXE-Manager/issues|issue tracker on github]], and I'm happy to recieve code pull requests via github.

I can be found idling on various different IRC Networks, but the best way to get in touch would be to message "Dataforce" on Quakenet, or drop me a mail (email address is in my [github profile](https://github.com/ShaneMcC))

## Screenshots

### Main Index
![Main Index](/screenshots/index.png?raw=true "Main Index")

### Images
![Images](/screenshots/images.png?raw=true "Images")
![Bootable1](/screenshots/bootable1.png?raw=true "Bootable1")
![Bootable2](/screenshots/bootable2.png?raw=true "Bootable2")
![Bootable3](/screenshots/bootable3.png?raw=true "Bootable3")
![Bootable-Edit](/screenshots/bootable-edit.png?raw=true "Bootable-Edit")

### Servers
![Servers](/screenshots/servers.png?raw=true "Servers")
![Servers1](/screenshots/servers1.png?raw=true "Servers1")
![Servers-Edit](/screenshots/servers-edit.png?raw=true "Servers-Edit")
![Servers-Preview](/screenshots/servers-preview.png?raw=true "Servers-Preview")
