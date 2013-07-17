=== uTubeVideo Gallery ===
Contributors: dman25560
Donate link: http://www.codeclouds.net/utubevideo-gallery/
Tags: video, gallery, youtube
Requires at least: 3.0.1
Tested up to: 3.5.2
Stable tag: 1.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Display unlimited galleries of YouTube videos in any post or page within your site. 

== Description ==

This plugin allows the displaying of galleries of YouTube videos within any post or page within your site. No API keys are necessary. Just install and start creating.

Features Include:

- Unlimited video galleries.
- Built in Fancybox support with options to use Fancybox already installed by another plugin.
- Thumbnail images for videos are cached on your own website.
- Annotations are hidden automatically for a movie like experience.
- Set size of video player
- Set progress bar color of video player
- Set starting resolution of videos (480p, 720p, or 1080p)
- Order video albums by newest or oldest videos first
- Possiblity of skipping video albums and just display videos
- Set video thumbnails as either square or rectangle
- View counts of video albums and videos within a gallery in the backend
- Add youtube playlists to an album
- Fallback to use Youtube thumbnails if images are not saving correctly
- Permalinks for galleries embedded within a page

== Installation ==

1. Unzip 'utubevideo-gallery.zip' and upload `utubevideo-gallery` to the `/wp-content/plugins/` directory.
2. Activate the plugin (uTubeVideo Gallery) through the 'Plugins' menu in WordPress.
3. Create a gallery and copy shortcode onto a page or post.
4. Create video albums for gallery just created.
5. Add videos to video album just created.
6. Set thumbnail of video album by clicking on 'edit' while on the video album page in utubevideo gallery settings.

== Frequently Asked Questions ==

= How many video galleries can I create? =

Unlimited.

= Are Vevo youtube videos supported? = 

Yes, all youtube videos should work unless embedding has been disabled.

= Can I use any version of Fancybox? =

Only Fancybox v1 is supported due to conflicts in the licence for Fancbox v2.

= Should I include the Fancybox scripts in the adminstrative panel for uTubeVideo Gallery? = 

Only include the Fancybox scripts if you do not have another Fancybox plugin in use.

= Are any other types of videos supported (ie not youtube)? =

No, only Youtube videos are supported, hence the name of the plugin.

= Can I change the size of the video player? =

Yes the video player size can be set in the 'General Settings' section in the settings page.

= How do I change older videos into the new square thumbnail type? = 

Just go to edit the video in question: edit the thumbnail type and save changes.

= How do I set a thumbnail for a new video album? =

After adding at least one video to the album click on the edit link for the video album and select a thumbnail and then click save changes.

= How do I just display videos with no albums? = 

After creating a gallery, at least one album, and adding videos to the album add --skipalbums="true"-- to the shortcode where you are embedding the gallery.

= How big of a playlist can I add to an album at a time? =

Do to script timeouts and such it seems around 80 videos is the limit, but you will have to experiment to find what works best for you.

= What should I do if my thumbnails are not displaying? =

If your thumbnails are not displaying or if you are getting a image editor error you can check the "Load Thumbnails from Youtube" in the settings to load thumbnails directly from Youtube. However all thumbnails will be rectangular when using this option; square thumbnails will be ignored.

= What if my Permalink Status is NOT Ok? = 

If your permalink status is not Ok, it means permalinks are not enabled correctly. Check to see that you have permalinks turned on for Wordpress and if this does not fix the problem, deactivate and reactivate this plugin.

== Screenshots ==

1. A gallery embedded on a page showing the videos in an album
2. A gallery showing a video playing
3. A listing of videos in the administration panel

== Changelog ==

= 1.5 = 

* Slight changes to frontend gallery interface
* Fixed issue with thumbnail sizes defaulting to square and stretching rectangular thumbnails
* Moved all scripts to the footer
* Patched included fancybox library to support jQuery 1.10
* Optimized fancybox call javascript
* Slight changes to admin interface
* Added settings for fancybox overlay color and opacity
* Added support for adding youtube playlists to an album
* Fixed admin navigation link bugs
* Changed main icon for admin interface and moved menu out of settings, into its own menu slot
* Added better error messages to admin section for forms
* Fixed uninstall script - it now deletes thumbnails when plugin is uninstalled
* Added workaround for thumbnail problems by loading thumbnails directly from Youtube
* Localized plugin, translations not provided, however
* Adding permalinks for albums for galleries embedded on a page, posts still use old method
* And possibly some other stuff I forgot.... 

= 1.3.5 =
* Fixed admin script processing hook
* Fixed general settings not showing as updated when first updated
* Fixed a link from appearing when no albums were found in a gallery
* Fixed a problem with single quotes being escaped when displayed in album titles in admin
* Added support for skipping video albums from being displayed in a gallery
* Added a button to add videos from admin video display page
* Fixed album and video counting bug
* Added ability to set starting resolution of videos
* Added setting to set the color of the player progress bar
* Added better security to plugin

= 1.3 =
* Complete redesign of code to a more object oriented design for namespacing
* Tweaks to admin interface, more minimal
* Added video album and video count to admin interface
* Added album backlink for video albums on frontend with no videos

= 1.2.5 =
* Added setting for video ordering in albums
* Added setting for video thumbnail type
* Fixed version information on included files
* Fixed bug when including multiple galleries on the same post or page

= 1.2 = 
* Minor tweaks to administrative panel
* Changed the way album thumbnails are set
* Added settings for video player size
* Added confirmation for deleting galleries, albums, and videos
* Added FAQ's to administrative panel
* Updated readme to correct a typo and more faqs/installation instructions
* Updated code documentation
* Minor tweaks to css styles

= 1.1.1 =
* Fixed issue with cached images getting deleted on update. This update will invalidate older galleries, unfortunatly

= 1.1 =
* Fixed major script inclusion bug and prevented some styles from being overidden

= 1.0 =
* Inital release of uTubeVideo Gallery
