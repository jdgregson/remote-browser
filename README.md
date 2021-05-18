# Remote Browser

Remote Browser is a self-hosted web interface intended to provide a tolerable
web browsing experience without rendering untrusted web content on your device.
It does this by capturing a screenshot of a specified page, or capturing only
the text using the Lynx browser.

Remote Browser is designed to work without JavaScript or cookies, and is aimed
at iPhone users who have achieved a truly unrealistic level of digital paranoia
(so just me, basically). However, it should work no matter what device you use.

The idea is that users spin up a virtual server which runs Remote Browser, and
then pin the PWA to their home screen. There is no authentication currently as
it is assumed that each Remote Browser instance will be firewalled off and only
accessible to the intended user via a VPN or SSH tunnel.

## Features

### Screenshots
Remote Browser primarily captures screenshots of websites for you to read.

### Lynx and Linked Lynx
Remote Browser provides the text of a website as captured by the Lynx browser.
It also provides a Reading view where the references are linked to the original
URL.

### No JavaScript
Remote Browser does not use JavaScript so you are able to disable it entirely on
your device's browser. Remote Browser uses a Content Security Policy which
explicitly disables JavaScript from all sources, and adds Canaries in places
which will show an alert if JavaScript has somehow been re-enabled.

### No Cookies
Remote Browser does not use cookies so you are able to disable them entirely on
your device's browser. Additionally, each screenshot is captured in a new
Firefox profile in a Private Window, so no cookies are used on the remote side
either.

### Tabs
Remote Browser has tabs.

### User Agent Switching
You can change user agents if the website doesn't look right in the screenshot.

### Width Adjustment
You can change the screenshot width if the web site isn't responsive enough.

## Setup

To install and configure Remote Browser, do the following (untested, may be
missing some steps):

 1. Install dependencies: `apt-get install lighttpd lynx firefox`
 2. Create a new user for the backend: `adduser browser`
 3. Copy the contents of `browser` to `/var/www/html/browser`
 4. Copy the contents of `browser-backend` to `/opt/browser`
 5. Change the owner of `/opt/browser` to `browser:www-data` and permissions to `664`
 6. Change the owner of `/opt/browser/content` to `browser:www-data` and permissions to `664`
 7. Change the owner of `/opt/browser/sessions` to `browser:www-data` and permissions to `664`
 8. Change the owner of `/opt/browser/useragent` to `browser:www-data` and permissions to `664`
 9. Make sure `abort`, `lynx`, `lynx-links`, `reset`, and `screenshot` in `/opt/browser` are executable
 10. Add the following to `/etc/sudoers` using `visudo`:

    www-data        ALL = (browser) NOPASSWD: /opt/browser/screenshot
    www-data        ALL = (browser) NOPASSWD: /opt/browser/abort
    www-data        ALL = (browser) NOPASSWD: /opt/browser/lynx
    www-data        ALL = (browser) NOPASSWD: /opt/browser/pandoc
    www-data        ALL = (browser) NOPASSWD: /opt/browser/reset
    www-data        ALL = (browser) NOPASSWD: /opt/browser/lynx-links

## Todo

 - Re-write Remote Browser in Python
 - Distribute Remote Browser via Docker (if that's still cool)
 - Think of a cool project name
