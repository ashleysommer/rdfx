<?php

namespace Drupal\rdfx\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RdfxSubscriber implements EventSubscriberInterface {

    public function initRdfx(GetResponseEvent $event) {
        // Get the path to the library.
        if (\Drupal::moduleHandler()->moduleExists('libraries')) {
            $path = libraries_get_path('ARC2') . '/arc';
        }
        else {
            $path = drupal_get_path('module', 'rdfx') . '/vendor/arc';
        }

        // Attempts to load the ARC2 library, if available.
        if (!class_exists('ARC2') && file_exists($path . '/ARC2.php')) {
            @include_once $path . '/ARC2.php';
        }
        module_load_include('inc', 'rdfx', 'rdfx.terms');
        module_load_include('inc', 'rdfx', 'rdfx.import');
        module_load_include('inc', 'rdfx', 'rdfx.query');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        $events[KernelEvents::REQUEST][] = array('initRdfx');
        return $events;
    }

}
