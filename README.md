# timelapse-capture
**Don't** judge me on this repo! It's been hacked up for a zero-budget favour for a friend, and for fun. There are
better ways to do basically all of this, I'm sure.

This is a set of scripts, configs, etc that have been put together to grab and build a timelapse video of a construction
project over a prolonged period of time. This uses a Ubiquiti Aircam IP camera, mounted on the house of a neighbour. The
neighbour has allowed use of their Wifi but we obviously want to do the absolute minimum of configuration/dependency
on their network and avoid access to their property as much as possible. Moreover, none of us will be regularly onsite
for most of the project so remote monitoring and remote preview of the footage is important so we can keep an eye on
whether the camera has been knocked by a pigeon or otherwise working.

Basic setup:

* Ubiquiti camera mounted externally on the building and cabled back to garage
* Old (old, old) domestic Zoom AP+4 router that I happened to have handy
* Remote ubuntu server to actually run the thing.

### Network setup

The router is configured in wireless ISP mode, connected to their domestic wifi. It has a private
wired network (just connected to the camera) and forwards ports 22 and 8080 (for camera SSH and 
camera web interface) to the camera. The Zoom AP+4 doesn't seem to have IP reservation so I had
to configure a static IP on the camera itself. Remaining wired ports are unused but available for
local configuration/debugging.

The router is configured to use a PPTP WAN connection to the remote server that will actually do
the work. The remote server is a ubuntu box configured with pptpd as per
https://www.digitalocean.com/community/tutorials/how-to-setup-your-own-vpn-with-pptp - this allows
the router to call in to our fixed IP address and expose the camera/router for remote management
and imaging without needing to open ports or know the IP of the domestic wifi. The local router
appears at 10.80.20.100 on the remote controller server, with the router web interface on port 80,
camera web interface on port 8080 and camera SSH on port 22.

Usually the router manages to re-establish dropped connections, but sometimes it fails and seems
to get stuck in a loop of partial PPTP negotiation followed by SIGHUP. The only solution appears
to be a reboot - but there is nothing running locally that can trigger this.

Therefore the whole kit is plugged into a Belkin WEMO wifi-controlled plug, which uses a 
separate vendor-provided network connection to allow control over the public internet. There's
no direct API, but it links with IFTT. This means that when the controller server detects that
the camera is unreachable, it can remotely trigger a full power cycle for the equipment onsite.
So far this has always been enough to re-establish the tunnel and get the kit back online.

### Camera setup

The camera takes little configuration - a static IP set through the admin web interface at 
http://10.0.0.100 (when connected to the onsite wired network). It captures a frame once
a minute - but by default this has an on-screen timestamp and is at half the native resolution
of the camera and heavily compressed.

This can be changed by configuring the /etc/ubnt-streamer.conf file but annoyingly there's no
way to persist changes to this file. Therefore a copy with the desired custom config is stored
on persistent memory with some helper scripts to apply it and restart the streamer if required
(there's no pkill available).

### Software setup - capturing

Every time we want to grab a frame, we:

* check the SSH port on the camera is open. If not, call IFTT to trigger a remote power cycle 
  and then poll until the tunnel is re-established and the SSH port appears.
* run the script on the camera to compare the settings in /etc/ubnt-streamer.conf with the 
  customised version in persistent memory. If different, copy the new settings file over,
  kill the ubnt-streamer process and wait for the camera's watchdog to restart it and take
  a new snapshot.
* Finally (!) grab the most recent snapshot and store it in the appropriate date/timestamped
  snapshots folder. Also update a copy in report/last-snapshot.jpeg - this directory is 
  copied back to the external jenkins server that triggers the job so we can have an easy
  way to view the most recently captured snapshot alongside the job report (using the 
  HTML publisher plugin)
  
Still to come: upload the snapshot to S3 for long-term storage

### Software setup - timelapse video preview

Still to come: regularly produce a video of the last 36 hours for ongoing quality control

`cat *.jpeg | avconv -r 15 -f image2pipe -codec:v mjpeg -i - -pix_fmt yuvj420p -r 30 -c:v libx264  -y output.mp4`

### Software setup - reporting and alerting

Still to come: daily email of missed captures, notable events, link to last 36 hours video

### Software setup - video production

Still to come: script to produce video over period of time with options:

* fit to duration (=> frame rate => desired number of frames => calculate which to drop)
* accelerate/decelerate for night time (ideally with smooth curve)
* accelerate/decelerate for other days of particular interest
