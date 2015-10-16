================
Adding Site Data
================

One of the main advantages of Brancher is its ability to store arbitrarily structured site data in the filesystem, and
to allow iteration over and processing of that data in your site.

Basics
======

As mentioned, the structure of the data is the filesystem. Think about it this way:

* directory => array, with the directory name as the key and list of files as the value
* file => object with access to the contents, with the filename as the key

As an example, consider the following directory structure:

``_data``::

    .
    ├── _data
        ├── news
        |   ├── 2015-08-10-post-of-some-sort.html
        |   └── 2015-09-20-another-news-post.html
        └── staff
            ├── ceo.html
            ├── cfo.html
            └── software_engineer.html

This would roughly translate to the following PHP data structure::

    [
        'news' => [
            '2015-08-10-post-of-some-sort.html' => new DataFile(...),
            '2015-09-20-another-news-post.html' => new DataFile(...),
        ],
        'staff' => [
            'ceo.html' => new DataFile(...),
            'cfo.html' => new DataFile(...),
            'software_engineer.html' => new DataFile(...),
        ],
    ]

All of this is provided in the **Twig global ``data``**, which can be used in a template like so::

    {% for post in data.news %}
        {# Raw contents of the file #}
        {{ post.contents }}

        {# Render the file as a Twig template #}
        {% include post.template %}
    {% endfor %}

Beyond these basics, the real power lies in the capabilities of each file object, which, as shown above, allows for
including the contents in raw, rendering it as a Twig template, among other things.

File Objects
============

Every data file object contains a number of properties that can be used:

================  =======================================================================
    Property                                    Description
================  =======================================================================
filename          Filename of the file
extension         Extension of the file
path              Absolute path (without the filename) to the file
pathname          Absolute path to the file
relativePath      Path to the file (without the filename), relative to the data directory
relativePathname  Path to the file, relative to the data directory
contents          Raw contents of the file
template          Name for a Twig template that will render the file
data              Array of data in the front YAML of the file (see below)
================  =======================================================================

Note that there are more properties than just those listed above. The file object extends PHP's `SplFileInfo`_ class,
and thus has all the properties it has.

Front YAML: Data within Data
============================

For each file in your data directory, the file can contain something called `Front YAML`_. This is data stored in YAML
at the top of the file that can be accessed in Twig.

``_data/news/2015-08-10-post-of-some-sort.html``::

    ---
    title: Post of Some Sort
    author: John Doe
    date: August 10th, 2015
    ---
    NEW YORK - This past Wednesday, there was a festival in New York that a lot
    of people attended...

Here the top of the file, from ``---`` to ``---``, contains data attributes about the file. These attributes will be
automatically removed from the file when using including the file's contents or rendering it as a Twig template.

The data can be used in your site like so::

    {% for post in data.news %}
        {# Display title and author of post #}
        {{ post.data.title }} ({{ post.data.author }})

        {# Render the file as a Twig template #}
        {% include post.template %}
    {% endfor %}

Every data file object has a ``data`` property, which contains all of the Front YAML data that was extracted from the
file.

Advanced: Generating files based on data
========================================

So far you know how to iterate over data in a file, but what if you want Brancher to create a new file for every data
file? An example would be if you have a list of news posts in your data directory, and you want your site to have a
corresponding HTML file for every post.

This feature is actually not supported by core Brancher, but can be done with one of the built-in extensions of
Brancher: the Post Generator extension.

``<root>/posts/.brancher.yml``::

    generator:
        source: /data/news

``_data/news/2015-08-10-post-of-some-sort.html``::

    ---
    title: Post of Some Sort
    author: John Doe
    date: August 10th, 2015
    ---
    {% extends 'base.twig.html' %}
    {% block content %}
        NEW YORK - This past Wednesday, there was a festival in New York that a lot
        of people attended...
    {% endblock %}

Here we use the Brancher per-directory configuration file, ``.brancher.yml``. (The name of the file can be customized.)
We activate the Post Generator extension, and tell it to make a file in that directory for every item in ``data.news``.

Some notes:

* The Post Generator extension uses a `JSON Pointer`_ to determine what to use for the iteration.
* Each file is rendered by Twig, as if it were any other file.
* The Front YAML data is made available in the Twig context when the file is rendered.

For more details, see the documentation on the `Built-in Extensions`_.


.. _Front YAML: https://github.com/mnapoli/FrontYAML
.. _SplFileInfo: https://secure.php.net/SplFileInfo
.. _JSON Pointer: https://tools.ietf.org/html/rfc6901
.. _Built-in Extensions: built-in-extensions.rst
