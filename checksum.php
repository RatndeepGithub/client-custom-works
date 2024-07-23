<?php
class Checksum {
	public static function calculateChecksum( $secret_key, $all ) {
		$hash     = hash_hmac( 'sha256', $all, $secret_key );
		$checksum = $hash;
		return $checksum;
	}

	public static function getAllParams() {
		$all = '';
		foreach ( $_POST as $key => $value ) {
			if ( $key != 'checksum' ) {
				$all .= "'";
				if ( $key == 'returnUrl' ) {
					// pretty url check //
					$a = strstr( $value, '?' );
					if ( $a ) {
						$value .= '&wc-api=Ced_Payment';} else {
						$value .= '?wc-api=Ced_Payment';}

						$all .= $value;
				} else {
					$all .= $value;
				}
				$all .= "'";
			}
		}
		return $all;
	}



	public static function verifyChecksum( $checksum, $all, $secret ) {
		$cal_checksum = self::calculateChecksum( $secret, $all );
		$bool         = 0;
		if ( $checksum == $cal_checksum ) {
			$bool = 1;
		}

		return $bool;
	}

}

