=== IP City Cluster ===
Contributors: tmb
Tags: cluster map, heat map, image, city, IP, statistics
Requires at least: 2.1
Tested up to: 2.5.1
Stable tag: 0.5.8

The IP City Cluster Plugin (IPCCP) generates a geographical cluster map based on where from people access you website.

== Description ==

The IP City Cluster Plugin (IPCCP) generates a geographical cluster map based on where from people access you website (or to be precise: based on the IP they got from their provider). It requires a statistics plug-in (that keeps a database or logfile) which keeps track of the IP. Key features include a preview with a lightbox way of zooming, image maps for city names and hit counters for both the preview and zoomed image, template output, smart clustering and user control over color, sizes and such.

Some of you might be familiar with the <a href="http://www.clustrmaps.com/">ClustrMap</a> Service. I signed up this month and was instantly hooked. After a week or two I had two issues though. One: why didn't I sign up ealier (or how am I going to convince them to read my logfile)? Secondly you had to pay for the advanced service. Not my cup of tea (not to mention that you are always dependent on their service being up ;) - I decided to look into writing my own cluster map WordPress plug-in. Key problem was of course the lookup of IPs. Fortunately <a href="http://blog.vimagic.de/wordpress/go.php?http://www.maxmind.com/">MaxMind</a> offers a free database and the required API to get City name, country name as well as longitude and latitude corresponding to an IP address.

See <a href="http://wordpress.org/extend/plugins/ipccp/screenshots/">screenshots</a> for an example.


== Installation ==
= Requirements =
As mentioned above the expected requirement is an SQL database with IPs. Most statistics plug-ins will keep track of that (f.e. <a href="http://andersdrengen.dk/projects">Counterize</a>). On a technical level I think PHP5 should do the trick. IPCCP is not using any WordPress specific calls and should work with any version (tested up to 2.3.2).

**[Note:]** Running IPCCP on a large data set might require some calculation time, which could exceed the maximum PHP execution time (usually 30sec). Therefore IPCCP can try to extend this execution time, which **will require the server to not run in PHP safemode**. Check with you friendly system administer if you don't have the appropriate access level. It took more than 5 minutes to generate the picture above (admittedly on my ancient webserver - but you get the point).

= Setup =
Setup itself is the usual 1-2-3 of downloading, expanding into `/wordpress/wp-content/plugins` and activating. Check the IPCCP options panel in the admin section and set those values to your liking. You'll see a first preview there. Provided are both the direct function call (for sidebar's, footer and such) `<?php if(function_exists(ipccp)) ipccp(); ?>` and the token word access `[IPCCP]` for posts or pages. You might want to check MaxMinds website once in a while, as they offer monthly updates to their database. Simply download the GeoLiteCity databases and drop them into the GeoIP folder within the IPCCP plug-in folder.

**[Note:]** Make sure the webserver has write permissions on those files:
   `/wordpress/wp-content/plugins/ipccp/images/ipccp_out\*`

**[Note:]** You could also add
   `<?php if(function_exists(ipccp_recluster)) ipccp_recluster(); ?>`
anywhere in your template to activate the reclustering. Use carefully though!



== Frequently Asked Questions ==

= Parse error: syntax error, unexpected ')', expecting '(' in [..]/ipccp/ipccp.php on line XYZ =
Most likely you are not running PHP5, which is required for the GeoIP API to work. You need to upgrade before using IPCCP.

=
Doesn't seem precise, or what? =
Actually what we are looking for is accuracy. IPCCP is (of course) 100% precise (having no stochastic parts at all). Accuracy is a different matter. The longitude / latitude values are often more of an estimate. But MaxMind promises monthly updates, so keep checking their website and grab the current databases (or use the links above). But to be honest, for this matter I think it's all good enough. It's all about the impression if you ask me.

= What if I still want more accuracy? =
You can go ahead and buy their commercial Databases which are currently $420 (base price) + $102 (per monthly update) for the set of City and Country database.

= What's the legend to the cluster map? =
I'll add a legend as we go along. For now just this quick remark: you see four numbers at the bottom left. For the picture above they are:
* 2881492 IP-entries numbers found
* 2879499 IP-entries numbers found minus the Satellite Operators
* 45787 unique IP numbers
* 5696 unique places
* 727 unique clusters drawn

= Why is there an option to disable the image maps? =
Using the image maps will probably drastically increase the length of the HTML-code (especially if you have some million entries from all over the place). Just look at the source of this page... Some people might wish for the image only and don't care for the additional information. Hence the option.

= What was the issue with using the ipccp_out_smal.jpg picture as a preview? =
IPCCP is generating three pictures. The native 2000*1000px `ipccp_out.jpg`, the user specified large version `ipccp_out_big.jpg` and the user specified small version `ipccp_out_smal.jpg`. The scaling of the later two is done by the PHP GD library, which isn't near up to standards if you ask me. Just compare the scaled version with the scaling done by the browser (which on OSX is done by the far superior QT). Hence I recommend leaving the small GD version be and simply use the big version. Given that most people will look at the zoomed version anyway that actually saves the download of two images.

= How can I help? =
If you have rights to any maps and are willing to donate them for the project, that would be great. I'll add a feature where the user could choose his/her map from a given available set. The same holds if you have the rights to a more detailed database! Donate them and earn eternal gratitude from millions of WordPress Users.

== Screenshots ==
1. IPCCP generated cluster map
2. mouse over cluster info
3. Fully configurable in the admin options panel

== Options ==
All IPCCP options are accessible through the corresponding admin options panel. First of all make sure to enter the correct SQL table and key name (if it's anything else than "IP"). There are three ways to generate a new picture:
- pressing Redraw in the admin panel
- calling an URL with the addition of `?recluster=true`
- using the scheduling feature (which automatically generates the cluster map after a specified amount of time - or to be precise, it automatically generates the cluster map, by the next call IPCCP-call after the user specified time. But give the amount of spammers and bots that are crawling over public sites. this should be close the the specified time itself. Doesn't it give you some satisfaction, that they are actually doing some good for you :) Just be aware of the CPU time required for the generation before scheduling IPCCP too often (or adding your terabyte long logfile).



== Know Issues ==
The new "all cities per cluster" feature is not supported by all browsers. actually it's probably only supported by Safari (or Konquerer or the like) and probably it's not correct (X)HTML either. But it sure looks cool when it works :)

**[Update]** It also works in IE7! Maybe it's only Firefox that got trouble displaying multiline titles?


== License ==
This WordPress plug is released under the <a href="http://www.gnu.org/licenses/gpl.html">GPL</a> and is provided with absolutely no warranty (as if?). For support leave a comment and we'll see what the community has to say.


== Version History ==
* **13.feb.2007 - v0.b5.8**
 > - optimised code: drastically increased speed of IP lookup
 > - optimised code: now checking syntax of database IPs as well
* **13.mar.2007 - v0.b5.7** [minor update]
 > - bugfix: counting of visits fixed
* **08.mar.2007 - v0.b5.6** [minor update]
 > - optimised code: using less RAM [optimised filtering]
 > - optimised code: runs faster with large datasets [kicked array_merge()]
 > - increased verbosity while generating the cluster map
* **06.mar.2007 - v0.b5.5** [major update]
 > - new feature: JPG quality
 > - new feature: non-linearity in clustering
 > - bundled new GeoIP database (March.2007)
* **23.feb.2007 - v0.b5.4** [minor update]
 > - new feature: more details in subtitle & legend
 > - optimised code: loading image map only on needed pages/posts
 > - bugfix: scheduled task now works as claimed
* **22.feb.2007 - v0.b5.3** [major update]
 > - new feature: print all city names in a cluster
 > - optimised code: rewrote smart clustering [you might have to readjust the SCC]
 > - optimised code: lightbox JS [hardcoding of URL only in one place now]
 > - bugfix: template now correctly saved
* **20.feb.2007 - v0.b5.2** [major update]
 > - new feature: verbosity while generating the cluster map
 > - new option: Performance vs Memory efficiency
 > - new option: legend [on/off]
 > - optimised code: reading of large files now reliable and fast
 > - optimised code: _getSize rewritten
 > - corrected behaviour: when called with no data
 > - corrected behaviour: when first called
 > - corrected behaviour: _updateOptions and value validation updated
 > - bugfix: image map not precise
 > - bugfix: correct readout of international characters in city names
* **18.feb.2007 - v0.b5.1** [minor update]
 > - extended logfile support [Use any logfile and apply filter]
* **17.feb.2007 - v0.b5** first public release [WhooHoo!]
 > - new option: cron like automated generation [by setting a minimum time]
 > - slightly modified admin panel
 > - bugfix: rare change of user specified values for the template
* **16.feb.2007 - v0.a4** new option: recluster by cron
 > - new option: recluster by url
 > - finished admin panel
* **15.feb.2007 - v0.a3** lightbox + image maps [Woohooo!!!]
 > - rendering of small and big images
* **14.feb.2007 - v0.a2** smart clustering [using an adaptive clustering distance]
 > - added logfile support [fetching IPs from file]
 > - new option: image map support
 > - new option: show your place
* **13.feb.2007 - v0.a1** Preliminaries [fetch entries from SQL, group by IP, get places, group by places ]
 > - figured out how long/lat corresponds to planar maps
 > - figured out how to draw [using GD]
 > - clustering of neighbouring citys into bigger ones
