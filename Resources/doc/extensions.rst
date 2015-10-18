==============================
Making or Installing Extension
==============================

Brancher is very tightly integrated with Symfony components. A Brancher extension is nothing more than a Symfony service
that is registered with the Brancher build tool.

Installing Extensions
=====================

Since a Brancher extension is a Symfony service, and the ``_config.yml`` file for a site is actually just a Symfony
config file passed to the dependency-injection container, extensions can be installed through the site config file.

As an example, if you want to install an extension whose class name is ``Vendor\Project\MyExtension``, you can add the
following to your ``_config.yml`` file to install it::

    services:
        myext:
            class: Vendor\Project\MyExtension
            tags:
                - { name: brancher.extension }

Alternatively, if the extension has its own Symfony configuration file, it can be imported like so::

    imports:
        - { resource: path/to/services.yml }

It is recommended that, if you are writing an extension that is not tightly coupled with your website, you make the
extension its own separate Composer library. Then you install the library in your site's ``composer.json`` file. This
allows the extension to be reused across projects.

Making an Extension
===================

A Brancher extension is:

* An event subscriber that receives events from the Brancher event dispatcher
* A configuration provider, that gives a configuration tree that may be used in special files

This is expressed in the ``BrancherExtensionInterface``, which extends Symfony's ``ConfigurationInterface`` and
``EventSubscriberInterface``.

Providing a Configuration
-------------------------

Brancher reads configuration from special per-directory config files (the default name of such files is
``.brancher.yml``, but that can be customized).

::

    use Symfony\Component\Config\Definition\Builder\TreeBuilder;
    use CastlePointAnime\Brancher\Extension\BrancherExtensionInterface;

    class MyExtension implements BrancherExtensionInterface
    {
        public function getName()
        {
            return 'myext';
        }

        public function getConfigTreeBuilder()
        {
            $builder = new TreeBuilder();
            $root = $builder->root($this->getName());

            $root
                ->children()
                    ->scalarNode('key')->end()
                    ->scalarNode('key2')->end()
                ->end();

            return $builder;
        }

The extension returns an object representing the configuration schema. Then Brancher will use that to process
configuration it finds in special files.

For example, for the above extension, you might find the following in ``.brancher.yml``::

    myext:
        key: hello
        key2: hello2

This will be processed, mixed together with the configuration from parent directories, and the extension will receive
a PHP array that might look like::

    [
        'key' => 'hello',
        'key2' => 'hello2',
    ]

This is just the basics. For more information, check out the `Symfony Config documentation`_, which explains how to make
more complicated configuration schemas.

Listening for Events
--------------------

The other end of an extension is receiving events. Brancher will dispatch the following events during the build process:

brancher.oldfile
    The first step of the Brancher build process involves searching the output directory for "old files", that is, files
    that do not exist anymore in the root directory. This event is fired for every file that is being considered for
    deletion because it is old.

brancher.setup
    This event is triggered as the build is being setup. It allows an extension to change the Finder object that
    Brancher uses to find files to be rendered.

brancher.directory_enter
    This event is triggered every time Brancher enters a new directory. This can be used to dynamically render files in
    a directory, or do other per-directory functions.

brancher.render
    This event is triggered right as Brancher is about to render an individual file. This can be used to change the
    output of a file.

brancher.teardown
    Finally, this event is fired at the end of the build process, for any postmortem functions that need to be
    performed.

These events are collected in the ``BrancherEvents`` class, which can be used like this::

    use CastlePointAnime\Brancher\BrancherEvents;
    use CastlePointAnime\Brancher\Event\RenderEvent;
    use CastlePointAnime\Brancher\Extension\BrancherExtensionInterface;

    class MyExtension implements BrancherExtensionInterface
    {
        public static function getSubscribedEvents()
        {
            return [
                BrancherEvents::RENDER => 'myListener',
            ];
        }

        public function myListener(RenderEvent $event)
        {
            ...
        }

First you implement a function ``getSubscribedEvents``, that lists which events the extension wants to receive and how,
and then you implement the listeners.

For more details, check out the corresponding Event classes, as well as the `Symfony EventDispatcher documentation`_.


.. _Symfony Config documentation: http://symfony.com/doc/current/components/config/definition.html
.. _Symfony EventDispatcher documentation: http://symfony.com/doc/current/components/event_dispatcher/introduction.html
