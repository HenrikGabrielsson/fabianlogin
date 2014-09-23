<?php
    class Helpers {
    	// Lägger till en sträng i en fil. En radbrytning läggs in i slutet av strängen.
		public static function WriteLineToFile($file, $line) {
			$handle = fopen($file, "a");
			if($handle) {
				fwrite($handle, $line . PHP_EOL);
			}
			fclose($handle);
		}
    }
?>