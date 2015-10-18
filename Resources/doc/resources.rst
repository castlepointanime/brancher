===========================
Using Assetic for Resources
===========================

Brancher has integration with `Assetic`_, a library for managing, processing, and versioning JavaScript, CSS, and image
resources for a website.

Assetic is very powerful, and Brancher is not currently capable of using it to its fullest extent. It can, however, be
used for basic management of resources in order to allow for your website to be served more efficiently.

Basics
======

Starting with the basics, here is how you would add your site's CSS file.

``_resources/css/my_styles.css``::

    html { display: none; }
    ...

``<root>/index.html``::

    <!DOCTYPE html>
    <head>
        {% stylesheets 'css/my_styles.css' %}
            <link href="{{ asset_url }}" type="text/css" rel="stylesheet" />
        {% endstylesheets %}
    </head>
    ...

Of course, this can be done in a template as well. The basic idea is that you use the ``stylesheets``, ``javascripts``,
and ``image`` blocks, and pass it the path to the asset(s) you want, relative to the resources directory.

You can list multiple resources at once, and Assetic will render them into a single file in the final site.


.. _Assetic: https://github.com/kriswallsmith/assetic
