#!/usr/bin/php -q
<?php
/*

Destiny of an Emperor III Script Dumper

Extracts the script from Destiny of an Emperor III for the Sega Mega Drive
in GB2312 or Big5 format.

Version:   0.5
Author:    Derrick Sobodash <derrick@sobodash.com>
Copyright: (c) 2003, 2012 Derrick Sobodash
Web site:  https://github.com/sobodash/doae3/
License:   BSD License <http://opensource.org/licenses/bsd-license.php>

*/

//-------------------------------------------------
// Character Set
//-------------------------------------------------
// Uncomment either traditional or simplified. The
// dumper can produce a script in either character
// set.
$charset = "s";   // Simplified
//$charset = "t"; // Traditional

echo ("Destiny of an Emperor III Script Dumper 0.5 (cli)\nCopyright (c) 2003, 2012 Derrick Sobodash\n");
set_time_limit(6000000);

// first string at 0xe52ca
print "Loading ROM into memory...\n";
$fd = fopen("doae3.bin", "rb");
$fddump = fread($fd, filesize("doae3.bin"));
fclose($fd);

print "Checking for pointers file...";
if (file_exists("doae3_pointers.txt")){
	print "found!\nLoading pointers...";
	$pt = fopen("doae3_pointers.txt", "rb");
	$ptdump = fread($pt, filesize("doae3_pointers.txt"));
	fclose($pt);
	$pointers = split("\n", $ptdump);
	unset($pt, $ptdump);
}
else $pointers = loc_ptr($fddump);

print "\nDumping strings for " . count($pointers) . " pointers...\n";
$output = "";

list($tblf0, $tblf1, $tblf2, $tblf3, $tblf4, $tblf5, $tblf6, $tblf7) = maketablearray($charset);
$known_bad = array(0xf9fb7, 0x175999, 0x148153, 0x178d3e, 0x18e726, 0x18e84e,
		   0x125631);

for ($i=0; $i<count($pointers); $i++) {
	if(!in_array(hexdec($pointers[$i]), $known_bad))
		$pointer = hexdec(bin2hex(substr($fddump, hexdec($pointers[$i]), 4)));
	print "  Dumping string $i...";
	print " $pointer (" . $pointers[$i] . ")... ";
	$thisline = ""; $chrchr = "";
	while ($chrchr != chr(0xff)){
		$chrchr = substr($fddump, $pointer, 1); $pointer++;

		if($chrchr==chr(0xff)){
			$chrchr = substr($fddump, $pointer, 1); $pointer++;
			if($chrchr==chr(0xff)) {
				$thisline .= "{clsr}\r\n";
				$chrchr = substr($fddump, $pointer, 1); $pointer++;
				$thisline .= "{" . str_pad(bin2hex($chrchr), 2, "0", STR_PAD_LEFT) . "}\r\n";
				$chrchr = substr($fddump, $pointer, 1); $pointer++;
			}
			else if($chrchr==chr(0x00))
				break;
		}
		if($chrchr==chr(0xf0)){
			$bank = $tblf0;
			$chrchr = substr($fddump, $pointer, 1); $pointer++;
		}
		else if($chrchr==chr(0xf1)){
			$bank = $tblf1;
			$chrchr = substr($fddump, $pointer, 1); $pointer++;
		}
		else if($chrchr==chr(0xf2)){
			$bank = $tblf2;
			$chrchr = substr($fddump, $pointer, 1); $pointer++;
		}
		else if($chrchr==chr(0xf3)){
			$bank = $tblf3;
			$chrchr = substr($fddump, $pointer, 1); $pointer++;
		}
		else if($chrchr==chr(0xf4)){
			$bank = $tblf4;
			$chrchr = substr($fddump, $pointer, 1); $pointer++;
		}
		else if($chrchr==chr(0xf5)){
			$bank = $tblf5;
			$chrchr = substr($fddump, $pointer, 1); $pointer++;
		}
		else if($chrchr==chr(0xf6)){
			$bank = $tblf6;
			$chrchr = substr($fddump, $pointer, 1); $pointer++;
		}
		else if($chrchr==chr(0xf7)){
			$bank = $tblf7;
			$chrchr = substr($fddump, $pointer, 1); $pointer++;
		}
		if(isset($bank))
			$thisline .= $bank[hexdec(bin2hex($chrchr))];
		else
			$thisline .= "{" . str_pad(bin2hex($chrchr), 2, "0", STR_PAD_LEFT) . "}\r\n";
	}
	unset($bank);
	$output .= "{" . $pointers[$i] . "}\r\n$thisline{end}\r\n\r\n";
	print "done!\n";
}

$fo = fopen("doae3_script.txt", "w");
fputs($fo, $output);
fclose($fo);

print "\nAll done!\n";

function loc_ptr($fddump) {
	//$pnt_arr = array(0x8f1ca, 0xa3910);
	$pnt_arr = array(0x8f6ae, 0x90101, 0xa3f60);
	//$end_arr = array(0x8f6ae, 0xa3f60);
	$end_arr = array(0x98a9e, 0x97e8d, 0xa7998);
	
	$i=0;

	print "\nLocating string pointers...\n";

	for ($z=0; $z<count($pnt_arr); $z++) {
		$pointer = $pnt_arr[$z];
		$end = $end_arr[$z];
		while ($pointer < $end) {
			if(strpos($fddump, pack("N", $pointer)) === FALSE) {
				$pointer++;
				$strings[$i] = strpos($fddump, pack("N", $pointer));
			}
			else {
				$strings[$i] = strpos($fddump, pack("N", $pointer));
			}
			$pointer = strpos($fddump, chr(0xff), $pointer) + 1;
			$i++;
			//print "  Found pointer ". str_pad($i, 4, "0", STR_PAD_LEFT) . "...\n";
		}
	}
	
	$newstring = array();
	for($i=0; $i<count($strings); $i++) {
		if(!in_array($strings[$i], $newstring))
			$newstring[] = $strings[$i];
	}
	print count($newstring) . " pointers found!\n";
	$output = "";
	for ($i=0; $i<count($newstring); $i++)
		if($newstring[$i] != 0)
			$output .= dechex($newstring[$i]) . "\n";
	
	$fo = fopen("doae3_pointers.txt", "w");
	fputs($fo, rtrim($output));
	fclose($fo);

	return ($strings);
}

function maketablearray($charset) {
	if($charset == "s") $zh = "tbl_gb";
	else if($charset == "t") $zh = "tbl_big";
	// Bank 1
	$fd = fopen ("$zh/t0.txt", "rb");
	$fddump = fread ($fd, filesize ("$zh/t0.txt"));
	$fddump = str_replace("\r\n", "", $fddump);
	fclose ($fd);
	$k=0;
	for ($i = 0; $i < strlen($fddump); $i = $i+2) {
		$tblf0[$k] = substr($fddump, $i, 2);
		$k++;
	}
	
	// Bank 2
	$fd = fopen ("$zh/t1.txt", "rb");
	$fddump = fread ($fd, filesize ("$zh/t1.txt"));
	$fddump = str_replace("\r\n", "", $fddump);
	fclose ($fd);
	$k=0;
	for ($i = 0; $i < strlen($fddump); $i = $i+2) {
		$tblf1[$k] = substr($fddump, $i, 2);
		$k++;
	}
	
	// Bank 3
	$fd = fopen ("$zh/t2.txt", "rb");
	$fddump = fread ($fd, filesize ("$zh/t2.txt"));
	$fddump = str_replace("\r\n", "", $fddump);
	fclose ($fd);
	$k=0;
	for ($i = 0; $i < strlen($fddump); $i = $i+2) {
		$tblf2[$k] = substr($fddump, $i, 2);
		$k++;
	}
	
	// Bank 4
	$fd = fopen ("$zh/t3.txt", "rb");
	$fddump = fread ($fd, filesize ("$zh/t3.txt"));
	$fddump = str_replace("\r\n", "", $fddump);
	fclose ($fd);
	$k=0;
	for ($i = 0; $i < strlen($fddump); $i = $i+2) {
		$tblf3[$k] = substr($fddump, $i, 2);
		$k++;
	}
	
	// Bank 5
	$fd = fopen ("$zh/t4.txt", "rb");
	$fddump = fread ($fd, filesize ("$zh/t4.txt"));
	$fddump = str_replace("\r\n", "", $fddump);
	fclose ($fd);
	$k=0;
	for ($i = 0; $i < strlen($fddump); $i = $i+2) {
		$tblf4[$k] = substr($fddump, $i, 2);
		$k++;
	}
	
	// Bank 6
	$fd = fopen ("$zh/t5.txt", "rb");
	$fddump = fread ($fd, filesize ("$zh/t5.txt"));
	$fddump = str_replace("\r\n", "", $fddump);
	fclose ($fd);
	$k=0;
	for ($i = 0; $i < strlen($fddump); $i = $i+2) {
		$tblf5[$k] = substr($fddump, $i, 2);
		$k++;
	}
	
	// Bank 7
	$fd = fopen ("$zh/t6.txt", "rb");
	$fddump = fread ($fd, filesize ("$zh/t6.txt"));
	$fddump = str_replace("\r\n", "", $fddump);
	fclose ($fd);
	$k=0;
	for ($i = 0; $i < strlen($fddump); $i = $i+2) {
		$tblf6[$k] = substr($fddump, $i, 2);
		$k++;
	}
	
	// Bank 8
	$fd = fopen ("$zh/t7.txt", "rb");
	$fddump = fread ($fd, filesize ("$zh/t7.txt"));
	$fddump = str_replace("\r\n", "", $fddump);
	fclose ($fd);
	$k=0;
	for ($i = 0; $i < strlen($fddump); $i = $i+2) {
		$tblf7[$k] = substr($fddump, $i, 2);
		$k++;
	}
		
	return array ($tblf0, $tblf1, $tblf2, $tblf3, $tblf4, $tblf5, $tblf6, $tblf7);
}

?>
