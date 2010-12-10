@echo off
SET VERSION="2.3.2"
svn export --no-auth-cache --username guest --password "" http://websvn.tigris.org/svn/websvn/tags/%VERSION% websvn-%VERSION%
"C:\Program Files\7-Zip\7z.exe" a -r websvn-%VERSION%.zip websvn-%VERSION%/*
