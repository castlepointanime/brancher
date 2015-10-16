===================
Built-in Extensions
===================

Brancher comes installed with a number of extensions. Every extension hooks into the build process, and can provide
additional functionality for site rendering.

Extensions receive configuration information from a "special file". This file is named, by default, ``.brancher.yml``.
Special files are per-directory, so you can have a ``brancher.yml`` in a sub-directory and it will override whatever
the parent directory's configuration is.

Current Built-in Extensions:

* Post Generator

For information on building your own extension, see the `Making or Installing Extension`_ documentation.

Post Generator extension
========================

The Post Generator extension will, upon entering a directory of the site, generate new files in that directory
dynamically based on arbitrary site data.

The process is:

1. Grab an iterator using the specified `JSON Pointer`_.
2. For each file in the iterator:
    a) Extract the `Front YAML`_ from the file.
    b) Render the contents of the file with Twig, passing the Front YAML as the context.
    c) Output the file into a file of the same name in the current directory

Configuration
-------------

``.brancher.yml``::

    generator:
        # JSON pointer to the source iterator
        source: /data/...


.. _Front YAML: https://github.com/mnapoli/FrontYAML
.. _JSON Pointer: https://tools.ietf.org/html/rfc6901
.. _Making or Installing Extension: extensions.rst
