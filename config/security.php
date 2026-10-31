<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

return [
    /**
     * HTTP headers sent on the front-end responses, set a header to null to not send it.
     */
    'headers' => [
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
    ],

    /**
     * Disable XML-RPC, the pingback header and the RSD / WLW links.
     */
    'disableXmlRpc' => true,

    /**
     * Remove the generator tag and the `ver` query argument of the front-end scripts and styles.
     */
    'hideVersion' => false,

    /**
     * Return a 404 on the author archives and the `?author=` requests, and hide the REST users routes to the visitors.
     */
    'preventUserEnumeration' => false,

    /**
     * Disable the oEmbed routes, links and rewrite rules.
     */
    'disableOEmbed' => false,

    /**
     * Remove the links advertising the REST API.
     */
    'hideRestApiDiscovery' => true,

    /**
     * Disable the redirections of `/admin`, `/login`, `/dashboard`, `/wp-admin` and `/wp-login.php` to the admin and login pages,
     * so that a custom admin location is not revealed.
     */
    'disableAdminRedirects' => false,

    'restApi' => [
        /**
         * Hide the REST routes to the users without the capability, except the routes of the public namespaces.
         */
        'restricted' => false,
        'publicNamespaces' => [],
        'capability' => 'manage_options',
    ],
];
