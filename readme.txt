=== Plugin Name ===
Contributors: damianzaremba
Tags: statistics, mint, bird-feeder, stats, rss
Requires at least: 3.0
Tested up to: 3.1
Stable tag: 1.3

Integration of the mint web site analytics program with wordpress.

== Description ==

Integration of the mint web site analytics program with wordpress including js header inclusion and birdfeeder inclusion.
Both are optional and configurable though the admin interface.

== Installation ==

1. Create a mint directory in your plugins directory. Typically that's wp-content/plugins/mint/.
1. Into this new directory upload the plugin files.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. It will ask you to complete a basic setup.
1. Sit back and relax.

== Changelog ==

= 1.0 =
Initial version.

= 1.1 =
Fixed major issues with birdfeeder intergration caused by lack of global declerations.
Seed (link) intergration currently doesn't work but won't break the plugin so committing now and will fix very soon.

= 1.2 =
***SECURITY FIX***
Removed some debugging code from the settings page callback which prevented proper access checking.

= 1.3 =
Resolved issue with seed (link) intergration. Plugin is now fully functionaly.
