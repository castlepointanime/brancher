===============================
Brancher: static site generator
===============================

.. image:: https://travis-ci.org/castlepointanime/brancher.svg?branch=master
    :target: https://travis-ci.org/castlepointanime/brancher
.. image:: https://insight.sensiolabs.com/projects/3ca2b791-6596-4f5d-bfd3-f4112748f82e/mini.png
    :target: https://insight.sensiolabs.com/projects/3ca2b791-6596-4f5d-bfd3-f4112748f82e

Brancher is a generic static site generator, based on Symfony components and using the Twig templating language. It was
designed to be easy to use, but extensible and usable for any type of site structure.

License
=======

Brancher is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as
published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

For this documentation:

Copyright (C)  2015  Tyler Romeo.
Permission is granted to copy, distribute and/or modify this document under the terms of the GNU Free Documentation
License, Version 1.3 or any later version published by the Free Software Foundation; with no Invariant Sections, no
Front-Cover Texts, and no Back-Cover Texts.

Code samples in this documentation are placed in the public domain, or under the `Creative Commons 0`_ license if needed
by jurisdiction.

Why Brancher?
=============

We created brancher because there were certain features we couldn't find in other static site generators:

* Storage of arbitrarily structured data (and the ability to iterate over it)
* Easy processing and filtering of JavaScript and CSS resources
* Addition custom PHP extensions to the build tool for site-specific functionality

Unlike other static site generators, this is not blog-focused. There is no flat "_posts" directory that contains all of
the dynamic content for the site. Instead this is meant for non-blog websites that sometimes contain multiple levels of
structured data.

Requirements
============

Brancher is PHP-based, so it needs a couple of things:

* PHP_ 5.4 or greater for command-line (usually available as the package ``php5-cli``)
* Composer_, a dependency management tool for PHP

That's it! All the libraries Brancher needs will be installed in the ``vendor/`` directory of your project using
Composer. Check out the Composer_ documentation for more details.

Installation and Use
====================

Brancher can be installed and used with Composer:

::

    # Install
    composer init
    composer require castlepointanime/brancher

    # Use
    ./vendor/bin/brancher build
    # => The current folder will be rendered into _site/

Directory Structure
===================

A typical Brancher project has a following typical directory structure (note that the location of each directory
can be customized):

::

    .
    ├── index.html
    ├── _config.yml
    ├── _templates
    |   ├── base.twig.html
    |   ├── layout.twig.html
    |   ├── header.twig.html
    |   └── footer.twig.html
    ├── _resources
    |   ├── js
    |   |   ├── my_library.js
    |   |   ├── other_library.js
    |   └── css
    |       ├── base_style.css
    |       ├── home_style.css
    |       └── other_style.css
    ├── _data
    |   ├── news
    |   |   ├── 2015-08-10-post-of-some-sort.html
    |   |   └── 2015-09-20-another-news-post.html
    |   └── staff
    |       ├── ceo.html
    |       ├── cfo.html
    |       └── software_engineer.html
    └── _site

An overview of the purpose of each of the directories:

===========  ==================  =======================================================================================
 Directory       CLI Option                                       Description
===========  ==================  =======================================================================================
.            ``<root>``          The root directory that contains the actual files for the site. Every file in the root
                                 will translated to a file of the same name in the rendered site. (Of course, if the
                                 templates, data, or other special directories are inside the root, they will be ignored
                                 for rendering.)
_config.yml  ``--config``        The configuration file, which can be used to specify any of the command line options,
                                 as well as more advanced stuff, like installing extensions.
_templates   ``--template-dir``  Contains Twig templates that can be ``{% include %}`` or ``{% extend %}`` into your
                                 site. Templates in this directory can, of course, refer to other templates as well.
_resources   ``--resource-dir``  Contains JavaScript, CSS, and image resources. This directory only needs to be used if
                                 you plan on using Assetic_, which is built into Brancher, to process your resources.
_data        ``--data-dir``      Contains arbitrary structured data for your site. In this case, the filesystem
                                 determines the structure of your data. Data directories can be iterated over in your
                                 site, and files can be read and parsed.
_site        ``<output>``        The output directory where the final static HTML site is rendered into. (Warning:
                                 existing files that are not a part of the site will be erased.)
===========  ==================  =======================================================================================

Configuration File
==================

As mentioned before, there is a ``_config.yml`` file, whose location can be customized, that can be used to specify
build parameters. (Note: the config file is processed using Symfony, meaning it can be any file extension that the
Symfony config component supports, specifically YAML, XML, PHP, and INI.)

Example Config file:

::

    brancher:
        build:
            # The <root> directory
            root: src

            # The <output> directory
            output: _site

            # Any --template-dir directories
            templates:
            - templates

            # The --resource-dir directory
            resources: resources

            # Any --data-dir directories
            data:
            - data

            # Any directories to --exclude
            excludes:
            - vendor

Warning: Command line options will override whatever is in the configuration file. If an option is not specified on
either the command line or config file, then the default mentioned above will be used.

Read More
=========

That's the basics! If you want to read more about a specific component of Brancher, visit any of the pages below:

* `Writing Pages and Templates`_: Introduction to using Twig templates with Brancher
* `Adding Site Data`_: Using data in the ``--data-dir`` directories inside your site's pages
* `Using Assetic for Resources`_: Processing and versioning resources using Assetic
* `Built-in Extensions`_: Usage for some built-in extensions that provide functionality in Brancher
* `Making or Installing Extension`_: Making your own extensions and hooking into the build process

.. _PHP: https://secure.php.net/
.. _Composer: https://getcomposer.org/
.. _Assetic: https://github.com/kriswallsmith/assetic
.. _Creative Commons 0: https://creativecommons.org/publicdomain/zero/1.0/
.. _Writing Pages and Templates: templates.rst
.. _Adding Site Data: data.rst
.. _Using Assetic for Resources: resources.rst
.. _Built-in Extensions: built-in-extensions.rst
.. _Making or Installing Extension: extensions.rst
