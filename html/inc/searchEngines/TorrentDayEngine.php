<?php

/* $Id$ */

/*************************************************************
*  TorrentFlux PHP Torrent Manager
*  www.torrentflux-ng.org
**************************************************************/
/*
	This file is part of TorrentFlux NG.

	TorrentFlux is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	TorrentFlux is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with TorrentFlux; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
/*
	v1.01 - May 2011 update URL
	v1.00 - Dec 2010 initial version by Epsylon3.
*/

class SearchEngine extends SearchEngineBase
{

	function SearchEngine($cfg)
	{
		$this->mainURL = "www.torrentday.me";
		$this->altURL = "www.torrentday.me";
		$this->mainTitle = "TorrentDay";
		$this->engineName = "TorrentDay";

		$this->author = "Epsylon3";
		$this->version = "1.01-tfng";
		//$this->updateURL = "http://www.torrentflux-ng.org/forum/viewtopic.php?f=13&t=40";

		$this->needAuth = true;

		$this->altRSS = "http://www.torrentday.me/get_rss.php?feed=direct&cat=12&user=[USER]&passkey=[MD5HASH]";

		$this->Initialize($cfg);
	}

	function populateMainCategories()
	{
		$this->mainCatalog["apps"]  = "0-Day";
		$this->mainCatalog["games"] = "Games";
		$this->mainCatalog["mp3"]   = "Mp3";
		$this->mainCatalog["vid"]   = "Videos";
	}

	//----------------------------------------------------------------
	// Function to Get Sub Categories
	function getSubCategories($mainGenre)
	{
		$output = array();

		switch ($mainGenre)
		{
			case "" :
			case "apps" :
				$output["12"]  = "0-Day";
				break;
			case "games" : //Games
				$output["4"]  = "PC";
				$output["9"]  = "XBox 360";
				$output["8"]  = "PSP";
				$output["5"]  = "PS2";
				$output["18"] = "PS3";
				$output["10"] = "Wii";
				break;
			case "mp3" :
				$output["17"]  = "Mp3";
				break;
			case "vid" :
				$output["1"]  = "Movies";
				$output["13"] = "Movies Packs";
				$output["3"]  = "Movies DVD";
				$output["11"] = "Movies HD";
				$output["6"]  = "Movies X";
				$output["16"] = "Music videos";
				$output["2"]  = "TV Shows";
				$output["14"] = "TV Packs";
				$output["7"]  = "TV HD";
				break;
		}

		return $output;

	}

	//----------------------------------------------------------------
	// Function to Make the Request (overriding base)
	function makeRequest($request)
	{
		$url = parse_url($request);
		$url['path'] = str_replace('browse.php','browse_API.php',$url['path']);
		$url['path'] = str_replace('0day.php','0day_API.php',$url['path']);
		if (strpos('browse_API.php') !== false) {
			$this->method = "POST";
			$this->postquery = "sec=jax&cata=yes&".$url['query'].$url['fragment'];
			$request = "/".$url['path'];
			//referer
			$_SESSION['lastOutBoundURI'] = "http://www.torrentday.me/browse.php?cata=no";
		}
		elseif (strpos('0day_API.php') !== false) {
			$this->method = "POST";
			$this->postquery = "sec=jax&c12&".$url['query'].$url['fragment'];
			$request = "/".$url['path'];
			//referer
			$_SESSION['lastOutBoundURI'] = "http://www.torrentday.me/0day.php";
		}
		return parent::makeRequest($request, false);
	}

	//----------------------------------------------------------------
	// Function to get Latest..
	function getLatest()
	{

		$allowPage = true;
		if (!isset($_REQUEST["subGenre"]))
		{
			$request = "0day.php";
		}
		else
		{
			if (array_key_exists("searchTerm",$_REQUEST))
			{
				$request = "0day.php?cat=12";
				//$allowPage = false;
			}
			else
			{
				$request = "browse.php?cat=".(int) $_REQUEST["subGenre"];
			}
		}

		if ($allowPage) {
			if (empty($this->pg))
				$this->pg = 0;

			$request .= "&page=" . (int) $this->pg;

			//order 1:Name 4:Date 5:Size 7:SE 9:LE 13:Type
			//$request .= "&sort=7&sort=desc";
		}

		if ($this->makeRequest($request))
		{
			return $this->parseResponse();
		}
		else
		{
			return $this->msg;
		}

	}

	//----------------------------------------------------------------
	// Function to perform Search.
	function performSearch($searchTerm)
	{
		$order = 7;
		$page = (int) $this->pg;
		$searchTerm = urlencode($searchTerm);
		$this->lastSearch = $searchTerm;

		if (array_key_exists("subGenre",$_REQUEST))
		{
			$request = "browse.php?search=$searchTerm&page=$page&sort=$order&cat=".(int)$_REQUEST["subGenre"];
			
			//if ((int)$_REQUEST['subGenre']==12) {
				$request = str_replace('browse.php','0day.php',$request);
			//}
		}/*
		elseif (array_key_exists("mainGenre",$_REQUEST))
		{
			$request = "browse.php?search=$searchTerm&page=$page&sort=$order&cat=".$_REQUEST["mainGenre"];
		}*/
		else
		{
			$request = "0day.php?c12&search=$searchTerm&page=$page&sort=$order&type=desc";
		}

		if ($this->makeRequest($request))
		{
			return $this->parseResponse();
		}
		else
		{
			return $this->msg;
		}
	}

	//----------------------------------------------------------------
	// Override the base to show custom table header.
	// Function to setup the table header
	function tableHeader()
	{
		$output = "<table width=\"100%\" cellpadding=3 cellspacing=0 bsort=0>";

		$output .= "<br>\n";
		$output .= "<tr bgcolor=\"".$this->cfg["table_header_bg"]."\">";
		$output .= "	<td>&nbsp;</td>";
		$output .= "	<td><strong>Torrent Name</strong> &nbsp;(";

		$tmpURI = str_replace(array("?hideSeedless=yes","&hideSeedless=yes","?hideSeedless=no","&hideSeedless=no"),"",$_SERVER["REQUEST_URI"]);

		// Check to see if Question mark is there.
		if (strpos($tmpURI,'?'))
		{
			$tmpURI .= "&";
		}
		else
		{
			$tmpURI .= "?";
		}

		if($this->hideSeedless == "yes")
		{
			$output .= "<a href=\"". $tmpURI . "hideSeedless=no\">Show Seedless</a>";
		}
		else
		{
			$output .= "<a href=\"". $tmpURI . "hideSeedless=yes\">Hide Seedless</a>";
		}

		$output .= ")</td>";
		$output .= "	<td><strong>Category</strong></td>";
		$output .= "	<td align=center><strong>&nbsp;&nbsp;Size</strong></td>";
		$output .= "	<td><strong>Date Added</strong></td>";
		$output .= "	<td><strong>Seeds</strong></td>";
		$output .= "	<td><strong>Peers</strong></td>";
		$output .= "</tr>\n";

		return $output;
	}

	//----------------------------------------------------------------
	// Function to parse the response.
	function parseResponse()
	{
		$thing = $this->htmlPage;

		// We got a response so display it.
		// Chop the front end off.

		if (strpos($thing,"Nothing here!") !== false)
		{
			$this->msg = "Your search did not match any torrents";

		} else {
			
			$output = $this->tableHeader();
			
			if (strpos($thing,"catHead") !== false)
			{
				$thing = substr($thing,strpos($thing,"<table"));
				$thing = substr($thing,strpos($thing,"<tr>"));
				$tmpList = substr($thing,0,strpos($thing,"</table>"));
				
				// keep after for paging
				$thing = substr($thing,strlen($tmpList));
				
				// clean tabs
				$tmpList = str_replace("\t","",$tmpList);

				// ok so now we have the listing.
				$tmpListArr = explode("</tr>",$tmpList);

				$bg = $this->cfg["bgLight"];

				foreach($tmpListArr as $key => $value)
				{
					$buildLine = true;
					if (strpos($value,"download.php"))
					{

						$ts = new fileTorrentDay($value);

						// Determine if we should build this output
						if (is_int(array_search($ts->CatName,$this->catFilter)))
						{
							$buildLine = false;
						}

						if ($this->hideSeedless == "yes")
						{
							if($ts->Seeds == "N/A" || $ts->Seeds == "0")
							{
								$buildLine = false;
							}
						}

						if (!empty($ts->torrentFile) && $buildLine) {

							$output .= trim($ts->BuildOutput($bg, $this->searchURL()));

							// ok switch colors.
							if ($bg == $this->cfg["bgLight"])
							{
								$bg = $this->cfg["bgDark"];
							}
							else
							{
								$bg = $this->cfg["bgLight"];
							}
						}

					}

				} //foreach
				
			} else {
				$output .= "<tr><td>&nbsp;</td></tr>\n";	
			}

			$output .= "</table>";

			// is there paging at the bottom?
			if (strpos($thing, "page=") !== false)
			{
				// Yes, then lets grab it and display it!  ;)
				$pages = substr($thing,strpos($thing,"<p"));
				$pages = substr($pages,strpos($pages,">"));
				$pages = substr($pages,0,strpos($pages,"</p>"));
				$thing = "";
				
				$lastSearch = $this->lastSearch;
				
				$pages = str_replace("&nbsp;",' ',$pages);
				
				$tmpPageArr = explode("</a>",$pages);
				
				$pagesOut = '';
				foreach($tmpPageArr as $key => $value)
				{
					$value .= "</a>";
					if (!preg_match("#((browse|0day|torrents\-search|torrents|search)\.php[^>]+)#",$value,$matches))
						continue;

					$url = rtrim($matches[0],'"');
					$php = $matches[2]; //search,torrents...

					if (!preg_match("#page=([\d]+)#",$value,$matches))
						continue;

					$pgNum = (int) $matches[1];

					$pagesOut .= str_replace($url,"XXXURLXXX".$pgNum,$value);
				}

				$pagesout = str_replace($php.".php?page=","",$pagesOut);

				$cat = tfb_getRequestVar('subGenre');
	
				if (empty($cat) && !empty($_REQUEST['cat']))
					$cat = $_REQUEST['cat'];

				$cat = (int) $cat;
				if(stripos($this->curRequest,"LATEST"))
				{
					if (!empty($cat)) {
						$pages = str_replace("XXXURLXXX",$this->searchURL()."&LATEST=1&cat=".$cat."&pg=",$pagesOut);
					} else {
						$pages = str_replace("XXXURLXXX",$this->searchURL()."&LATEST=1&pg=",$pagesOut);
					}
	
				} else {
	
					if(!empty($cat)) {
						$pages = str_replace("XXXURLXXX",$this->searchURL()."&searchterm=".$_REQUEST["searchterm"]."&cat=".$cat."&pg=",$pagesOut);
					} else {
						$pages = str_replace("XXXURLXXX",$this->searchURL()."&searchterm=".$_REQUEST["searchterm"]."&pg=",$pagesOut);
					}
				}
	
				$output .= "<div align=center>".substr($pages,1)."</div>";
			}

		}
		return $output;
	}
}

// This is a worker class that takes in a row in a table and parses it.
class fileTorrentDay
{
	var $torrentName = "";
	var $torrentDisplayName = "";
	var $torrentFile = "";
	var $torrentMagnet = "";
	var $torrentSize = "";
	var $torrentStatus = "";
	var $CatName = "";
	var $CatId = "";
	var $MainId = "";
	var $MainCategory = "";
	var $SubId = "";
	var $SubCategory = "";
	var $Seeds = "";
	var $Peers = "";
	var $Data = "";

	var $dateAdded = "";
	var $dwnldCount = "";

	function fileTorrentDay( $htmlLine )
	{
		global $cfg;

		if (strlen($htmlLine) > 0)
		{

			$this->Data = $htmlLine;

			// Chunck up the row into columns.
			$tmpListArr = explode("</td>",$htmlLine);

			if(count($tmpListArr) >= 5)
			{
				// Cat Id
				$cat = (int) substr($tmpListArr["0"],strpos($tmpListArr["0"],"cat=")+4);
				$this->SubId = $cat;

				$td = $tmpListArr["1"];
				// TorrentName
				$tmpStr = substr($td,0,strpos($td,'</a>'));
				$this->torrentName = $this->cleanLine($tmpStr);
				
				// Date Added
				$td = substr($td, strlen($tmpStr));
				$td = substr($td, strpos($td,'<br')+4);
				$this->dateAdded = $this->cleanLine($td);

				$this->dateAdded = trim(str_replace('Uploaded:','',$this->dateAdded));

				//$this->dateAdded = str_replace('Today',date('y-m-d'),$this->dateAdded);
				//$this->dateAdded = str_replace('Y-day',date('y-m-d',strtotime('-1 day')),$this->dateAdded);
				//if (strlen($this->dateAdded) == 11 && strpos($this->dateAdded,'ago') === false)
				//	$this->dateAdded = date('y').'-'.$this->dateAdded;

				$td = $tmpListArr["2"];
				// Download Link
				$td = substr($td, strpos($td,'<a'));
				$td = substr($td, 0, strpos($td,'</a>')+4);
				$tmpStr = substr($td,strpos($td,"href=\"")+6); 
				$this->torrentFile = substr($tmpStr,0,strpos($tmpStr,"\""));

				// Comments
				//$this->Comments = $this->cleanLine($tmpListArr["3"]);

				$td = $tmpListArr["4"];
				// Size
				$td = trim(substr($td, strpos($td,',')+1));
				$size = str_replace(array(' ',"\t",' '),'',$td);
				if (preg_match('#(\d+\.\d+)#',$size,$matches))
					$this->torrentSize = round(floatval($matches[1]),2); 

				$this->Seeds = $this->cleanLine($tmpListArr["5"]);  // Seeds
				$this->Peers = $this->cleanLine($tmpListArr["6"]);  // Peers

				if ($this->Peers == '')
				{
					$this->Peers = "N/A";
					if (empty($this->Seeds)) $this->Seeds = "N/A";
				}
				if ($this->Seeds == '') $this->Seeds = "N/A";

				$this->torrentDisplayName = $this->torrentName;
				
			}
		}

	}

	function cleanLine($stringIn,$tags='')
	{
		if(empty($tags))
			return trim(str_replace(array("&nbsp;","&nbsp")," ",strip_tags($stringIn)));
		else
			return trim(str_replace(array("&nbsp;","&nbsp")," ",strip_tags($stringIn,$tags)));
	}

	//----------------------------------------------------------------
	// Function to build output for the table.
	function BuildOutput($bg, $searchURL='', $maxDisplayLength=80)
	{
		if(strlen($this->torrentDisplayName) > $maxDisplayLength)
		{
			$this->torrentDisplayName = substr($this->torrentDisplayName,0,$maxDisplayLength-3)."...";
		}
		if (strpos($this->torrentFile, "www.torrentday.me") === false) {
			$this->torrentFile = "http://www.torrentday.me/".$this->torrentFile;
		}
		$output = "<tr>\n";
		$output .= "	<td width=\"16\" bgcolor=\"".$bg."\"><a href=\"dispatcher.php?action=urlUpload&type=torrent&url=".$this->torrentFile."\"><img src=\"".getImagesPath()."download_owner.gif\" width=\"16\" height=\"16\" title=\"".$this->torrentName."\" bsort=0></a></td>\n";
		$output .= "	<td bgcolor=\"".$bg."\"><a href=\"dispatcher.php?action=urlUpload&type=torrent&url=".$this->torrentFile."\" title=\"".$this->torrentName."\">".$this->torrentDisplayName."</a></td>\n";

		if (strlen($this->MainCategory) > 1){
			if (strlen($this->SubCategory) > 1){
				$mainGenre = "<a href=\"".$searchURL."&mainGenre=".$this->MainId."\">".$this->MainCategory."</a>";
				$subGenre = "<a href=\"".$searchURL."&subGenre=".$this->SubId."\">".$this->SubCategory."</a>";
				$genre = $mainGenre."-".$subGenre;
			}else{
				$genre = "<a href=\"".$searchURL."&mainGenre=".$this->MainId."\">".$this->MainCategory."</a>";
			}
		}else{
			$genre = "<a href=\"".$searchURL."&subGenre=".$this->SubId."\">".$this->SubCategory."</a>";
		}

		$output .= "	<td bgcolor=\"".$bg."\">". $genre ."</td>\n";

		$output .= "	<td bgcolor=\"".$bg."\" align=right>".$this->torrentSize."</td>\n";
		$output .= "	<td bgcolor=\"".$bg."\" align=center>".$this->dateAdded."</td>\n";
		$output .= "	<td bgcolor=\"".$bg."\" align=center>".$this->Seeds."</td>\n";
		$output .= "	<td bgcolor=\"".$bg."\" align=center>".$this->Peers."</td>\n";
		$output .= "</tr>\n";

		return $output;

	}
}

?>
