<?php
/**
 * Message Manager
 *
 * @package SmsConnect\Core
 */

namespace SmsConnect\Core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Message_Manager
 *
 * Manages message properties like type (SMS/LMS) based on content.
 */
class Message_Manager {
	/**
	 * Get the message type based on its content length.
	 *
	 * In Korea, SMS length is typically calculated based on EUC-KR encoding.
	 * 1 byte for English/numbers/symbols, 2 bytes for Korean characters.
	 * - SMS: <= 90 bytes
	 * - LMS: > 90 bytes
	 *
	 * @param string $content The message content.
	 * @return string 'SMS' or 'LMS'.
	 */
	public function get_message_type( $content ) {
		$byte_length = $this->calculate_byte_length( $content );

		if ( $byte_length <= 90 ) {
			return 'SMS';
		}

		return 'LMS';
	}

	/**
	 * Calculate the byte length of a string as per EUC-KR encoding rules.
	 *
	 * This is a simplified calculation. `mb_strwidth` with EUC-KR
	 * gives a good approximation for this purpose.
	 *
	 * @param string $string The string to measure.
	 * @return int The calculated byte length.
	 */
	private function calculate_byte_length( $string ) {
		// mb_strwidth returns the width of a string, where multi-byte characters
		// are usually counted as 2. This is a common way to estimate byte length
		// for Korean SMS standards without full charset conversion.
		return mb_strwidth( $string, 'EUC-KR' );
	}
} 