
WHY WebSVN?

WebSVN offers a view onto your subversion repositories that's been designed to
reflect the Subversion methodology.  You can view the log of any file or
directory and see a list of all the files changed, added or deleted in any
given revision.  You can also view the differences between 2 versions of a file
so as to see exactly what was changed in a particular revision.

WebSVN isn't a feature laden product, but it does offer a clean, easy to use
interface, that should provide most users with all the functionality they need
from a read-only web interface onto their Subversion repositories.

Since it's written using PHP, WebSVN is also very portable and easy to install.
The disadvantage of PHP is that there are currently no SVN/PHP bindings.  Until
these become available WebSVN will use the svnlook command to analyse
repositories.  Although this may slightly limit the performance, I've found that
in practice the speed is perfectly adequate and that svnlook provides a
interface that remains very stable between subversion releases.

INSTALLATION

Grab the source and stick it somewhere that your server can get to.  You
obviously need to have PHP installed and working.  Also note that WebSVN
won't currently work in safe mode, due to the need to call svnlook.

You'll also need diff (preferably the GNU version; for Windows users I'd
recommend the Cygwin version) and svnlook available.

Rename distconfig.inc as config.inc and then edit it as directed in the file
itself.

If everything has gone well, you should be able to view your projects by
pointing your browser at the index.php file.

For those of you wishing to customise the look and feel a little, you should
read templates.txt, which explains the highly configurable template system.

NOTE:

In order to return results with a reasonable speed, WebSVN caches the results
of it's requests to svnlook.  Under normal usage this works correctly since it's
not generally possible to change a revision with subversion.

That said, one case that may cause confusion is if someone changes the log
message of a given revision.  WebSVN will have cached the previous log message
and won't know that there's a new one available.  There are various solutions
to this problem:

1) Turn off caching in the config file.  This will severely impede the perfomance
   of WebSVN.

2) Change the post-revprop-change hook so that is deletes the contents of the
   cache after any change to a revision property

3) Only allow the administrator to change revision properties.  He can then
   delete the cache by hand should this occur.


LICENCE

GNU Public licence.

 
