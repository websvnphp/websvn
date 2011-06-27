#!/bin/sh
VERSION="2.3.3"
svn export --no-auth-cache --username guest --password "" http://websvn.tigris.org/svn/websvn/tags/$VERSION websvn-$VERSION
tar -cf websvn-$VERSION.tar websvn-$VERSION
gzip -9 websvn-$VERSION.tar
