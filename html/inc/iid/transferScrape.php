<?php

/* $Id$ */

/*******************************************************************************

 LICENSE

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License (GPL)
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.

 To read the license please visit http://www.gnu.org/copyleft/gpl.html

*******************************************************************************/

// prevent direct invocation
if ((!isset($cfg['user'])) || (isset($_REQUEST['cfg']))) {
	@ob_end_clean();
	@header("location: ../../index.php");
	exit();
}

/******************************************************************************/

// common functions
require_once('inc/functions/functions.common.php');

// transfer functions
require_once('inc/functions/functions.transfer.php');

// init template-instance
tmplInitializeInstance($cfg["theme"], "page.transferScrape.tmpl");

// init transfer
transfer_init();

// client-switch
if (substr($transfer, -8) == ".torrent") {
	// this is a t-client
	$tmpl->setvar('hasScrape', 1);
	$tmpl->setvar('scrapeInfo', getTorrentScrapeInfo($transfer));
} else if (substr($transfer, -5) == ".wget") {
	// this is wget.
	$tmpl->setvar('hasScrape', 0);
	$tmpl->setvar('scrapeInfo', "Scrape not supported by wget");
} else if (substr($transfer, -4) == ".nzb") {
	// this is nzbperl.
	$tmpl->setvar('hasScrape', 0);
	$tmpl->setvar('scrapeInfo', "Scrape not supported by nzbperl");
} else {
	AuditAction($cfg["constants"]["error"], "INVALID TRANSFER: ".$transfer);
	@error("Invalid Transfer", "", "", array($transfer));
}

// title + foot
tmplSetFoot(false);
tmplSetTitleBar($transferLabel." - Scrape", false);

// iid
tmplSetIidVars();

// parse template
$tmpl->pparse();

?>