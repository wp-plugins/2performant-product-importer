<?php
$pear_dir = WP_PLUGIN_DIR . '/' . str_replace( basename( __FILE__ ), '', plugin_basename( __FILE__ ) ) . 'api';
set_include_path( get_include_path() . PATH_SEPARATOR . $pear_dir . PATH_SEPARATOR . $pear_dir . '/PEAR' );
include_once 'api/TPerformant.php';

function tp_get_wrapper() {
	global $tp;

	if ( ! isset( $tp ) ) {
		$err = tp_verify_connection( $tp );
		if( !empty($err) )
			return false;
	}

	return $tp;
}

function tp_verify_connection( &$tp = false ) {

	$connected = false;
	$errors = array();
	$connection = get_option( 'tp_options_connection' );
	if ( ! ( $connection['network'] && $connection['username'] && $connection['password'] ) ) {
		$errors[] = 'Please define connection parameters';
	} else {
		try {
			$tp = new TPerformant( 'simple', array( "user" => trim( $connection['username'] ), "pass" => trim( $connection['password'] ) ), trim( $connection['network'] ) );
			$response = $tp->user_loggedin();
			if ( $response == 'XML_Parser: Not well-formed (invalid token) at XML input line 1:1' ) {
				$errors[] = 'Invalid username and password';
			} elseif ( $response == 'XML_Parser: Invalid document end at XML input line 1:12' ) {
				$errors[] = 'Invalid API URL';
			} elseif ( is_object( $response ) ) {
				$connected = true;

				if ( ! ( isset( $response->role ) && $response->role == 'affiliate' ) )
					$errors[] = 'Logged in user is not an affiliate';
			} else {
				$errors[] = 'Invalid API response. Please verify settings and contact affiliate network administrator.';
				ob_start();
				print_r( $response );
				trigger_error( 'API response: ' . ob_get_contents(), E_USER_WARNING );
				ob_end_clean();
			}
		} catch( TPException_Transport $e ){
			$errors[] = 'Invalid username and password';
		} catch( HTTP_Request2_Exception $e ) {
			$errors[] = 'Invalid API URL';
		}
	}
	return $errors;
}

function compareByName( $a, $b ){
		return ($a->name == $b->name) ? 0 : ( ( $a->name < $b->name ) ? -1 : 1 ) ;
}
function compareById($a, $b){
	return ($a->id == $b->id) ? 0 : ( ( $a->id < $b->id ) ? -1 : 1 ) ;
}

function tp_feed_dropdown( $options = array() ) {
	$options['value'] = isset( $options['value'] ) ? $options['value'] : '';
?>
<select <?php echo isset( $options['name'] ) ? "name='{$options['name']}'" : ''; ?> <?php echo isset( $options['id'] ) ? "id='{$options['id']}'" : ''; ?> <?php echo isset( $options['class'] ) ? "class='{$options['class']}'" : ''; ?>>
	<option value="all">All</option>
<?php
	$tp = tp_get_wrapper();
	$campaigns = $tp->campaign_affiliate_feeds();

	usort($campaigns, 'compareByName');

	foreach ($campaigns as $campaign):
		//usort($campaign, 'compareByName');
		//if(!empty($campaign)) ?>
		<option value="c_<?php echo $campaign->id; ?>" class="level-0"<?php echo $options['value'] == "c_{$campaign->id}" ? ' selected="selected"' : ''; ?>><?php echo htmlspecialchars($campaign->name); ?></option>
		<?php
		$feeds = $campaign->product_stores;
		usort($feeds, 'compareByName');

		foreach($feeds as $feed) :?>
			<option value="f_<?php echo $feed->id; ?>" class="level-1"<?php echo $options['value'] == "f_{$feed->id}" ? ' selected="selected"' : ''; ?>>&nbsp;&nbsp;&nbsp; <?php echo htmlspecialchars($feed->name); ?></option>
		<?php
		endforeach;
	endforeach;
?>
</select>
<?php
}

function tp_campaign_dropdown( $options = array() ) {
	$options['value'] = isset( $options['value'] ) ? $options['value'] : '';
?>
<select <?php echo isset( $options['name'] ) ? "name='{$options['name']}'" : ''; ?> <?php echo isset( $options['id'] ) ? "id='{$options['id']}'" : ''; ?> <?php echo isset( $options['class'] ) ? "class='{$options['class']}'" : ''; ?>>
	<option><?php _e( 'Select a campaign', 'tppi' ); ?></option>
<?php
	$tp = tp_get_wrapper();
	$campaigns = $tp->campaign_affiliate_feeds();

	usort($campaigns, 'compareByName');

	foreach ($campaigns as $campaign): ?>
		<option value="c_<?php echo $campaign->id; ?>" class="level-0"<?php echo $options['value'] == "c_{$campaign->id}" ? ' selected="selected"' : ''; ?>><?php echo htmlspecialchars($campaign->name); ?></option>
		<?php
	endforeach;
?>
</select>
<?php
}

function fix_result($object) {
	if(!is_array($object))
		if(!empty($object))
			$object = array($object);
		else
			$object = array();
	return $object;
}

?>
