<?php

$context = \Timber\Timber::get_context();

$offers = apply_filters( 'deco_get_offers_aspo_biz_report_xml', '' );

$context['date']   = date( 'Y-m-d H:i:s' );
$context['offers'] = $offers;

header( 'Content-type: text/xml' );

Timber::render( 'bundles/offers-xml/aspo_biz_report.twig', $context );