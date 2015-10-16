===========================
Writing Pages and Templates
===========================

In Brancher, every file in the root directory is one-to-one translated into a rendered file in the output directory. The
current process involves running the file through Twig. (In the future, we may support further rendering options, such
as Markdown or Textile.)

Here are some very basic Twig concepts, just as a quick reference. We strongly recommend reading the
`Twig documentation`_, which contains the full guide

Includes
========

Goal
    Include the contents of one template inside another.

Use Case
    Extracting common HTML, such as a header, nav bar, or footer, into its own file

``index.html``::

    <!DOCTYPE html>
    <html>
    <head>
        {% include "head.twig.html" %}
    </head>
    <body>
        Hello world!
    </body>
    </html>

``_templates/head.twig.html``::

    <meta charset="UTF-8" />
    <title>My Title</title>

The result will include the rendered contents of the template inside of the index page.

Inheritance
===========

Goal
    Have multiple pages use the same file layout

Use Case
    Having a single HTML layout that pages insert content into

``index.html``::

    {% extends "base.twig.html" %}
    {% block content %}
        Hello world!
    {% endblock %}

``_templates/base.twig.html``::

    <!DOCTYPE html>
    <html>
    <head>
        {% include "head.twig.html" %}
    </head>
    <body>
        {% block content %}{% endblock %}
    </body>
    </html>

Here, the resulting ``index.html`` will contain the entire contents of the base template, but with the ``Hello world!``
block substituted in the appropriate location.

Looping over Data
=================

Goal
    Loop over some variable (usually site data)

Use Case
    Rendering a list of news posts, possibly with excerpts

``index.html``::

    <ul>
    {% for post in data.news %}
        <li>{{ post.data.title }}: {{ post.data.contents }}</li>
    {% endfor %}
    </ul>

``_data/news/example_post.html``::

    ---
    title: Title of the Post
    ---
    Contents of the post

This is a basic example, and you should read the documentation on `Adding Site Data`_ for more details. The for-loop
will iterate over the ``news`` sub-directory, and will receive an object for each file in it. You can then access data
and contents from the file

.. _Twig documentation: http://twig.sensiolabs.org/
.. _Adding Site Data: data.rst
