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

/**
 * login
 */
function image_login() {
	global $cfg;
	$bgImage = ((strpos($cfg["default_theme"], '/')) === false)
		? 'themes/'.$cfg["default_theme"].'/images/code_bg'
		: 'themes/tf_standard_themes/images/code_bg';
	$rndCode = loginImageCode($cfg["db_user"], tfb_getRequestVar('rnd'));
	$tc = image_getTextcolor(80, 80, 80);
	Image::paintLabelFromImage($bgImage, $rndCode, 
							   5, 12, 2, 
							   $tc['r'], $tc['g'], $tc['b']);
}

/**
 * test
 */
function image_test() {
	global $cfg;
	$bgImage = ((strpos($cfg["theme"], '/')) === false)
		? 'themes/'.$cfg["theme"].'/images/code_bg'
		: 'themes/tf_standard_themes/images/code_bg';
	$tc = image_getTextcolor(0, 0, 0);
	Image::paintLabelFromImage($bgImage, 'tf-b4rt', 
							   5, 8, 2, 
							   $tc['r'], $tc['g'], $tc['b']);
}

/**
 * pieTransferTotals
 */
function image_pieTransferTotals() {
	global $cfg;
	// transfer-id
	$transfer = tfb_getRequestVar('transfer');
	if (empty($transfer))
		Image::paintNoOp();
	// validate transfer
	$validTransfer = false;

	if (isHash($transfer)) 
		$hash = $transfer;
	else
		$hash = getTransferHash($transfer);

	if ($cfg["transmission_rpc_enable"]) {
		require_once('inc/functions/functions.rpc.transmission.php');
		$options = array('uploadedEver','downloadedEver');
		$transTransfer = getTransmissionTransfer($hash, $options); // false if not found; TODO check if transmission enabled
		if ( is_array($transTransfer) ) {
			$uptotal = $transTransfer['uploadedEver'];
			$downtotal = $transTransfer['downloadedEver'];
			$validTransfer = true;
		}
	}
	if (!$validTransfer) { // If not found in transmission transfer
		if ( tfb_isValidTransfer($transfer) ) {
			// client-handler + totals
			$ch = ClientHandler::getInstance(getTransferClient($transfer));
			$totals = $ch->getTransferTotal($transfer);
			$uptotal = $totals["uptotal"];
			$downtotal = $totals["downtotal"];
			$validTransfer = true;
		}
	}

	if (!$validTransfer) {
		AuditAction($cfg["constants"]["error"], "INVALID TRANSFER: ".$transfer);
		Image::paintNoOp();
	}

	// draw image
	Image::paintPie3D(
		202,
		160,
		100,
		50,
		200,
		100,
		20,
		Image::stringToRGBColor($cfg["body_data_bg"]),
		array($uptotal + 1, $downtotal + 1),
		image_getColors(),
		array('Up : '.@formatFreeSpace($uptotal / 1048576), 'Down : '.@formatFreeSpace($downtotal / 1048576)),
		48,
		130,
		2,
		14
	);
}

/**
 * pieTransferPeers
 */
function image_pieTransferPeers() {
	global $cfg;
	// transfer-id
	$transfer = tfb_getRequestVar('transfer');
	if (empty($transfer))
		Image::paintNoOp();
	// validate transfer
	$validTransfer = false;

	if (isHash($transfer))
                $hash = $transfer;
	else
		$hash = getTransferHash($transfer);

	if ($cfg["transmission_rpc_enable"]) {
		require_once('inc/functions/functions.rpc.transmission.php');
		$options = array('trackerStats','peers');
		$transTransfer = getTransmissionTransfer($hash, $options); // false if not found; TODO check if transmission enabled
		if ( is_array($transTransfer) ) {
			$validTransfer = true;
			$client = "transmissionrpc";
		}
	}
	if (!$validTransfer) { // If not found in transmission transfer
		if ( tfb_isValidTransfer($transfer) ) {
			// stat
			$sf = new StatFile($transfer);
			$seeds = trim($sf->seeds);
			$peers = trim($sf->peers);
			// client-switch + get peer-data
			$peerData = array();
			$peerData['seeds'] = 0;
			$peerData['peers'] = 0;
			$peerData['seedsLabel'] = ($seeds != "") ? $seeds : 0;
			$peerData['peersLabel'] = ($peers != "") ? $peers : 0;
			$client = getTransferClient($transfer);
			$validTransfer = true;
		}
	}
	if ( !$validTransfer ) {
		AuditAction($cfg["constants"]["error"], "INVALID TRANSFER: ".$transfer);
		Image::paintNoOp();
	}

	switch ($client) {
		case "tornado":
			if ($seeds != "") {
				if (strpos($seeds, "+") !== false)
					$seeds = preg_replace('/(\d+)\+.*/i', '${1}', $seeds);
				if (is_numeric($seeds))
					$peerData['seeds'] = $seeds;
				$peerData['seedsLabel'] = $seeds;
			}
			if ($peers != "") {
				if (strpos($peers, "+") !== false)
					$peers = preg_replace('/(\d+)\+.*/i', '${1}', $peers);
				if (is_numeric($peers))
					$peerData['peers'] = $peers;
				$peerData['peersLabel'] = $peers;
			}
			break;
		case "transmission":
		case "transmissionrpc":
			$peers = sizeof($transTransfer['peers']);
			$seeds = 0;
			foreach ( $transTransfer['trackerStats'] as $tracker ) {
				$seeds += ($tracker['seederCount'] == -1 ? 0:$tracker['seederCount']);
			}
			$peerData['seedsLabel'] = $seeds;
			$peerData['seeds'] = $seeds;
			$peerData['peersLabel'] = $peers;
			$peerData['peers'] = $peers;
			break;
		case "vuzerpc":
			if (empty($seeds) || empty($peers)) {
				$ch = ClientHandler::getInstance($client);
				$running = $ch->monitorRunningTransfers();
				$hash = strtoupper(getTransferHash($transfer));
				if (!empty($running[$hash]) ) {
					$t = $running[$hash];
					$peerData['seeds'] = $t['seeds'];
					$peerData['seedsLabel'] = $t['seeds'];
					$peerData['peers'] = $t['peers'];
					$peerData['peersLabel'] = $t['peers'];
				}
			}
			break;
		case "azureus":
			if ($seeds != "") {
				if (strpos($seeds, "(") !== false)
					$seeds = preg_replace('/.*(\d+) .*/i', '${1}', $seeds);
				if (is_numeric($seeds))
					$peerData['seeds'] = $seeds;
				$peerData['seedsLabel'] = $seeds;
			}
			if ($peers != "") {
				if (strpos($peers, "(") !== false)
					$peers = preg_replace('/.*(\d+) .*/i', '${1}', $peers);
				if (is_numeric($peers))
					$peerData['peers'] = $peers;
				$peerData['peersLabel'] = $peers;
			}
			break;
		case "mainline":
			if (($seeds != "") && (is_numeric($seeds))) {
				$peerData['seeds'] = $seeds;
				$peerData['seedsLabel'] = $seeds;
			}
			if (($peers != "") && (is_numeric($peers))) {
				$peerData['peers'] = $peers;
				$peerData['peersLabel'] = $peers;
			}
			break;
		case "wget":
		case "nzbperl":
			$peerData['seeds'] = ($seeds != "") ? $seeds : 0;
			$peerData['peers'] = ($peers != "") ? $peers : 0;
			break;
		default:
			AuditAction($cfg["constants"]["error"], "INVALID TRANSFER: ".$transfer);
			Image::paintNoOp();
	}
	// draw image
	Image::paintPie3D(
		202,
		160,
		100,
		50,
		200,
		100,
		20,
		Image::stringToRGBColor($cfg["body_data_bg"]),
		array($peerData['seeds'] + 0.00001, $peerData['peers'] + 0.00001),
		image_getColors(),
		array('Seeds : '.$peerData['seedsLabel'], 'Peers : '.$peerData['peersLabel']),
		58,
		130,
		2,
		14
	);
}

/**
 * pieTransferScrape
 */
function image_pieTransferScrape() {
	global $cfg;
	// transfer-id
	$transfer = tfb_getRequestVar('transfer');
	if (empty($transfer))
		Image::paintNoOp();
	// validate transfer
	if (tfb_isValidTransfer($transfer) !== true) {
		AuditAction($cfg["constants"]["error"], "INVALID TRANSFER: ".$transfer);
		Image::paintNoOp();
	}
	// get scrape-data
	require_once('inc/functions/functions.common.php');
	$scrape = @trim(getTorrentScrapeInfo($transfer));
	if ((!empty($scrape)) && (preg_match("/(\d+) seeder\(s\), (\d+) leecher\(s\).*/i", $scrape, $reg))) {
		$seeder = $reg[1];
		$leecher = $reg[2];
		// draw image
		Image::paintPie3D(
			202,
			160,
			100,
			50,
			200,
			100,
			20,
			Image::stringToRGBColor($cfg["body_data_bg"]),
			array($seeder + 0.00001, $leecher + 0.00001),
			image_getColors(),
			array('Seeder : '.$seeder, 'Leecher : '.$leecher),
			58,
			130,
			2,
			14
		);
	} else {
		// output image
		Image::paintNoOp();
	}
}

/**
 * pieServerBandwidth
 */
function image_pieServerBandwidth() {
	global $cfg;
	// get vars
	getTransferListArray();
	$bwU = (isset($cfg["total_upload"])) ? $cfg["total_upload"] : 0.0;
	$bwD = (isset($cfg["total_download"])) ? $cfg["total_download"] : 0.0;
	// check vars
	if (($bwU < 0) || ($bwD < 0)) {
		// output image
		Image::paintNoOp();
	}
	// draw image
	Image::paintPie3D(
		202,
		160,
		100,
		50,
		200,
		100,
		20,
		Image::stringToRGBColor($cfg["body_data_bg"]),
		array($bwU + 0.00001, $bwD + 0.00001),
		image_getColors(),
		array('Up : '.@number_format($bwU, 2)." kB/s", 'Down : '.@number_format($bwD, 2)." kB/s"),
		48,
		130,
		2,
		14
	);
}

/**
 * pieServerDrivespace
 */
function image_pieServerDrivespace() {
	global $cfg;
	// get vars
	$df_b = @disk_free_space($cfg["path"]);
	$dt_b = @disk_total_space($cfg["path"]);
	// check vars
	if (($df_b < 0) || ($dt_b < 0)) {
		// output image
		Image::paintNoOp();
	}
	$du_b = $dt_b - $df_b;
	if ($du_b < 0) {
		// output image
		Image::paintNoOp();
	}
	// draw image
	Image::paintPie3D(
		202,
		160,
		100,
		50,
		200,
		100,
		20,
		Image::stringToRGBColor($cfg["body_data_bg"]),
		array($df_b + 0.00001, $du_b + 0.00001),
		image_getColors(),
		array('Free : '.formatFreeSpace($df_b / 1048576), 'Used : '.formatFreeSpace($du_b / 1048576)),
		58,
		130,
		2,
		14
	);
}

/**
 * mrtg
 */
function image_mrtg() {
	global $cfg;
	// filename
	$fileName = tfb_getRequestVar('f');
	if (empty($fileName))
		Image::paintNoOp();
	$targetFile = $cfg["path"].'.mrtg/'.$fileName;
	// validate file
	if (!((tfb_isValidPath($targetFile) === true)
		&& (preg_match('/^[0-9a-zA-Z_]+(-day|-week|-month|-year)(.png)$/D', $fileName))
		&& (@is_file($targetFile))
		)) {
		AuditAction($cfg["constants"]["error"], "ILLEGAL MRTG-IMAGE: ".$cfg["user"]." tried to access ".$fileName);
		Image::paintNoOp();
	}
	// send content
	@header('Accept-Ranges: bytes');
	@header('Content-Length: '.filesize($targetFile));
	@header('Content-Type: image/png');
	@fpassthru(fopen($targetFile, 'rb'));
	exit();
}

/**
 * spacer
 */
function image_spacer() {
	// output image
	Image::paintSpacer();
}

/**
 * notsup
 */
function image_notsup() {
	// output image
	Image::paintNotSupported();
}

/**
 * noop
 */
function image_noop() {
	// output image
	Image::paintNoOp();
}

/**
 * get array with text-color. try to read values from the request-vars. 
 * use default-color created from function-args if no colors provided in 
 * request-vars.
 *
 * @param $r
 * @param $g
 * @param $b
 *
 * @return color[]
 */
function image_getTextcolor($r = 0, $g = 0, $b = 0) {
	$rtc = tfb_getRequestVar('tc');
	return (empty($rtc)) 
		? array('r' => $r, 'g' => $g, 'b' => $b) 
		: Image::stringToRGBColor($rtc);
}
	
/**
 * get array with colors. try to read values from the request-vars. 
 * use default-colors if no colors provided in request-vars.
 *
 * @return colors[][]
 */
function image_getColors() {
	$rc1 = tfb_getRequestVar('c1');
	$rc2 = tfb_getRequestVar('c2');
	$color1 = (empty($rc1)) 
		? array('r' => 0x00, 'g' => 0xEB, 'b' => 0x0C) 
		: Image::stringToRGBColor($rc1);
	$color2 = (empty($rc2)) 
		? array('r' => 0x10, 'g' => 0x00, 'b' => 0xFF) 
		: Image::stringToRGBColor($rc2);
	return (array($color1, $color2));
}

?>
