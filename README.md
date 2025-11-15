# Purple Tree 3
*by John D. Allsup*

Purple Tree 3 is the third iteration of the wiki system I use to
organise much of my notes and online presence. It is relatively
simple, under 10,000 lines of PHP and a under 10,000 lines
of Javascript. It uses no frameworks, and a small amount
of third party code. The only *necessary* third party dependency
is Parsedown, which converts Markdown into HTML.
The wiki then uses a wrapper class called PTMD which
turns WikiWords into links, and also allows fenced blocks
to be given special interpretations, something of which
I make copious use.

## History
The first Wiki system I used was called WabiSabi, which
I used to build my first Wiki, called TheWikiMan. Over
a time, I hacked more and more features into it, until
it became cumbersome.

Sometime in July 2021 I decided to try my hand at making
Youtube coding tutorials. A minimalist Wiki in PHP seemed like
an ideal project to use. At this point, having explored
WabiSabi, and recalling the original WikiWikiWeb, which was
a small Perl script, I knew a Wiki could be written in 
very little code. So I came up with as minimal a Wiki
as I could, and it came to under 100 lines. Then adding a
few conviences and it was still under 150.

I had planned to teach bits of PHP using a more complex
Wiki. In designing it, I found I had something more suitable
for me than the WabiSabi-based TheWikiMan for managing
notes and such. So it developed into what I now consider
the first proper version of Purple Tree. Though back then
it was just called 'Allsup Wiki' and 'Purple Tree' referred
to the YouTube channel and the Wiki there.

That version had the same shortcoming many Wikis have,
namely that the do not allow for a hierarchical directory
structure. So to manage hierarchical directories, I had
to have one clone of the code for *every* subdirectory
I wanted to use. So it was time for a rewrite.

So it happened that in the summer of 2023 I sat down and
did the rewrite. The result was Purple Tree 2. This Wiki
could manage an entire subdomain. And at first I had only
one subdomain, [pt2.allsup.co](https://pt2.allsup.co/),
but slowly the number of subdomains grew, and each instance
of a Wiki on a subdomain required its own copy of the code,
because config data had become intermingled with the PHP
that ran the site. So another rewrite was due.

That rewrite was done in November of 2025, in two stages.
The first was to test out how to organise the subdomains.
After a little experimentation I came up with the system
used here. In the root of the Wiki system, there is a
subdirectory called `sites` which contains data specific to
each subdomain. (Actually, this title is arbtary, subject
to not clashing with subdirectories the Wiki uses, like
`php`.) The static Javascript and CSS goes in `static/js`
and `static/css` and are symlinked to from within each
site's subdirectory.

## The Backend
The key feature of how it works is that the directory
the `httpd` sees contains only a `.htaccess`, which routes
*everything* through `index.php`, the `index.php`
which is a three-line stub which makes a note of
the directory it is in (necessary for the PHP code
to know where the data for a particular site is stored),
defines the location of the `config` files, and then
bootstraps the rest of the code by `require`ing
`php/main.php` (`php` here is a symlink, and it should
be noted that PHP resolves symlinks when considering
the location of files, and of files relative to each
other.)

A new site is made by the `makesite` script in `sites`
which clones from a template. The template contains
six directories, four files, six symlinks. A `tar.xz`
of the template is under one kilobyte! Very lightweight.

All the files are stored in the `files` subdirectory,
and versions of each file stored goes in `versions`.
Importantly, the Wiki has no means to delete or overwrite
files written into the `version` directory.

Then, requests get resolved by `main.php` which is a 40-line
dispatcher, which looks at the request URI and decides
whether the request is for a page, a file, or an API.
Filenames are used as a means to differentiate:
paths with no extension (that is, with no `.` anywhere)
refer to pages; files with extensions refer to files;
filenames starting with a `.` are api calls.
API calls inside a subdirectory are often scoped to
that subdirectory. So `/.r` lists recent files on the
entire Wiki, but `/path/to/.r` lists only those within
`/path/to`, and simliarly for the two search API's:
`/.t/tagname` for hashtags, and `/.w/word` for simple
word searches. The indexing is done by a Python script,
which is run by a `cron` job and generates the required
data as JSON. This avoids the need for any kind of SQL
database.

Crucially, all the files are stored as ordinary files,
so you can poke around from the command line over `ssh`,
and can copy/move/backup pages and files using standard
command line tools like `mv`, `cp` and `rsync`. This is
far less cumbersome than what needs to be done if the
site's backing store is an SQL database.

Page templates go in the `templates` directory.
A template is reponsible for taking the Wiki and page
data and rendering it. It is still a little messy,
but is functional.

Overriding `fenced code blocks`, define methods
in the `PTMD` class which have the name
`special_inline_XYZ` or `special_block_XYZ`.
In the case of fenced code blocks, we use e.g.
```XYZ
...
```
or [[XYZ:...]] to invoke these. Be careful, as they
can have arbtrary code.

## Security
Whether a user has read or edit (or in principle any)
permissions is controlled by a function `is_auth($what)`
in `$site/config/auth.php`. It returns `true` for allowed,
or `false` for denied. The administrator of the site
is responsible for plumbing in whatever auth code they
want. The auth code I use is not included.

## The Frontend
The frontend is written in Javascript. The Wiki is designed
for an efficient, keyboard-driven editing workflow. Most tasks
can be accomplished with only a few keypresses. For example,
if authenticated to edit, `C-backquote` opens the page in edit
mode, `S-v` brings up versions, `S-r` goes to the recents
list, `C-g` brings up a 'goto box' which is slightly more
efficient than editing URL's in the URL bar.

Editing just uses a textarea. I did experiment with Code
Mirror, but ended up removing it.

## Random Features
If you use `\(...\)` or `\[...\]` in a page, it includes MathJax
from the appropraite CDN. If you use an `abc` block, it includes
the Javascript implementation of ABC (but doesn't include these
if not used, to save page loading time and bandwidth). So it
is easy to include mathematical equations and sheet music.

## Wrapping up
And that is basically that. A simple, still reasonably minimal
Wiki system that can manage an arbitrary number of subdomains
with a single pile of code.

The `.tar.xz` of everything is about 280K.
