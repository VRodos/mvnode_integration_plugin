<?php

/**
 * Plugin Name: MVNode Integration
 * Description: This plugin is required for SSO between Mediaverse Nodes and the VRodos component
 * Version: 4.0
 * Author: Anastasios Papazoglou Chalikias, Elias Kouslis, Vicky CERTH, Sofia Kostoglou
 * Author URI: https://iti.gr
 **/


// 1. Create Registration / Login pages on plugin activation
const PLUGIN_FILE_PATH = __FILE__;
//register_activation_hook( PLUGIN_FILE_PATH, 'create_mv_register_page_on_activation' );
register_activation_hook( PLUGIN_FILE_PATH, 'create_mv_login_page_on_activation' );

function create_mv_register_page_on_activation() {
	create_new_page_on_activation('[mvnode_registration]');
}
function create_mv_login_page_on_activation() {
	create_new_page_on_activation('[mvnode_login]');
}

function create_new_page_on_activation($type) {
	if ( ! current_user_can( 'activate_plugins' ) ) return;

	$page_slug = $type == '[mvnode_login]' ? 'mv-login' : 'mv-register'; // Slug of the Page
	$page_title = $type == '[mvnode_login]' ? 'MediaVerse - Login' : 'MediaVerse - Register';

	$page_url = plugin_dir_url( __FILE__ );
	$logo_url = $page_url .'assets/mv-logo.png';

	$page_content =
		'<div>
            <div>
                <img src="'.$logo_url.'" height="160" alt="MediaVerse logo">
            </div>
            <div>
                '. $type.'
            </div>       
        </div>';

	$new_page = array(
		'post_type'     => 'page',
		'post_title'    => $page_title,
		'post_content'  => $page_content,
		'post_status'   => 'publish',
		'post_author'   => 1,
		'post_name'     => $page_slug
	);
	if (!get_page_by_path( $page_slug, OBJECT, 'page')) {
		$new_page_id = wp_insert_post($new_page);
	}
}

function registration_form($email, $password, $username, $first_name, $last_name, $dateOfBirth){

	echo '
    <style>
        .mvnode-field { margin-bottom:2px; } 
        .mvnode-field input{ margin-bottom:4px; }
    </style>
    ';

	echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-0evHe/X+R7YkIZDRvuzKMRqM+OrBnVFBL6DOitfPri4tjfHxaWutUpFmBp4vmVor" crossorigin="anonymous">
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/js/bootstrap.bundle.min.js" integrity="sha384-pprn3073KE6tl6bjs2QrFaJGz5/SUsLqktiwsUTF55Jfv3qYSDhgCecCxMW52nD2" crossorigin="anonymous"></script>
          ';


	if (! is_user_logged_in()) {
		echo '
    
    <h1 style="color: #619e85;">MEDIAVERSE</h1>
    
    <div class="mb-5">If you already have a MediaVerse account, login <a href="'. home_url() .'/mv-login/">here</a></div>
    <form action="' . $_SERVER['REQUEST_URI'] . '" method="post" class="shadow-lg p-3 mb-5 bg-white rounded">
    
    <div class="mvnode-field">
    <label for="email">Email <strong>*</strong></label>
    <input required type="text" name="email" value="' . (isset($_POST['email']) ? $email : null) . '">
    </div>
     
    <div class="mvnode-field">
    <label for="password">Password <strong>*</strong></label>
    <input required minlength="6" type="password" name="password" value="' . (isset($_POST['password']) ? $password : null) . '">
    </div>
     
    <div class="mvnode-field">
    <label for="username">Username <strong>*</strong></label>
    <input required minlength="4" type="text" name="username" value="' . (isset($_POST['username']) ? $username : null) . '">
    </div>
  
    <div class="mvnode-field">
    <label for="dateOfBirth">Date of Birth (You must be 18 years old or older) <strong>*</strong></label>
    <input required type="date" min="1950-01-01" name="dateOfBirth" value="' . (isset($_POST['dateOfBirth']) ? $dateOfBirth : null) . '">
    </div>     
      
    <div class="mvnode-field">
    <label for="fname">First Name <strong>*</strong></label>
    <input required minlength="1" type="text" name="fname" value="' . (isset($_POST['fname']) ? $first_name : null) . '">
    </div>
     
    <div class="mvnode-field">
    <label for="lname">Last Name <strong>*</strong></label>
    <input required minlength="1" type="text" name="lname" value="' . (isset($_POST['lname']) ? $last_name : null) . '">
    </div>
     
     
    <input type="submit" name="submit" class="fusion-button button-flat button-xlarge button-default button-5 fusion-button-default-span  form-form-submit button-default" value="Register"/>
    </form>
    ';
	}
	else{
		echo '<div class="login-desc" style="margin-bottom: 10px;">
            You are already logged in! <br>
            <a href='. wp_logout_url( home_url()) .' title="Logout">Logout</a>
        </div>';
	}
}

function registration_validation($email, $password, $username, $dob){

	$reg_errors = new WP_Error;

	if (empty($username) || empty($password) || empty($email) || empty($dob)) {
		$reg_errors->add('field', 'Required form field is missing');
	}

	if (4 > strlen($username)) {
		$reg_errors->add('username_length', 'Username too short. At least 4 characters is required');
	}

	if (username_exists($username)){
		$reg_errors->add('user_name', 'Sorry, that username already exists!');
	}

	if (!validate_username($username)) {
		$reg_errors->add('username_invalid', 'Sorry, the username you entered is not valid');
	}

	if (5 > strlen($password)) {
		$reg_errors->add('password', 'Password length must be greater than 6 characters');
	}

	if (!is_email($email)) {
		$reg_errors->add('email_invalid', 'Email is not valid');
	}

	if (email_exists($email)) {
		$reg_errors->add('email', 'Email Already in use in WordPress');
	}

	if(!validateAge($dob)) {
		$reg_errors->add('dob', 'You should be at least 18 years old');
	}

	if (is_wp_error($reg_errors)) {

		foreach ($reg_errors->get_error_messages() as $error) {

			echo '<div>';
			echo '<strong>ERROR</strong>:';
			echo $error . '<br/>';
			echo '</div>';
		}

		return true;
	}
	else {
		return false;
	}
}


function custom_registration_function() {
	if (isset($_POST['submit'])) {
		$has_errors = registration_validation($_POST['email'], $_POST['password'], $_POST['username'], $_POST['dateOfBirth']);

		if ($has_errors == 1) {

			// Sanitize user form input
			$username   =   sanitize_user($_POST['username']);
			$password   =   esc_attr($_POST['password']);
			$email      =   sanitize_email($_POST['email']);
			$first_name =   sanitize_text_field($_POST['fname']);
			$last_name  =   sanitize_text_field($_POST['lname']);
			$dateOfBirth   =   sanitize_text_field($_POST['dateOfBirth']);

			// call @function complete_registration to create the user
			// only when no WP_error is found
			complete_registration($username, $password, $email, $first_name, $last_name, $dateOfBirth);
		}
	}

	// registration_form($username,$password, $email, $first_name, $last_name, $dateOfBirth);
	registration_form(null, null, null, null, null, null );
}



// Register a new shortcode: [mvnode_registration]
add_shortcode('mvnode_registration', 'mvnode_registration_shortcode');

function mvnode_registration_shortcode(){
	ob_start();
	custom_registration_function();
	return ob_get_clean();
}


// MVNODE LOGIN
function mvnode_login_form() {
	echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-0evHe/X+R7YkIZDRvuzKMRqM+OrBnVFBL6DOitfPri4tjfHxaWutUpFmBp4vmVor" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/js/bootstrap.bundle.min.js" integrity="sha384-pprn3073KE6tl6bjs2QrFaJGz5/SUsLqktiwsUTF55Jfv3qYSDhgCecCxMW52nD2" crossorigin="anonymous"></script>
';

	$login = (isset($_GET['login'])) ? $_GET['login'] : 0;

	$selected_mv_node = (isset($_GET['node_src'])) ? $_GET['node_src'] : '';

	switch ($login) {
		case "failed":
			//echo '<p class="login-msg alert alert-danger" role="alert"><strong>ERROR:</strong> Invalid username and/or password.</p>';
			break;
		case "empty":
			echo '<p class="login-msg alert alert-danger" role="alert"><strong>ERROR:</strong> Username and/or Password is empty.</p>';
			break;
		case "false":
			echo '<p class="login-msg alert alert-warning" role="alert"> You are logged out.</p>';
			break;
		case "token_null":
			echo '<p class="login-msg alert alert-danger" role="alert"><strong>ERROR:</strong> Could not log you in. Check your credentials!</p>';
			break;
		case "node_err":
			echo '<p class="login-msg alert alert-danger" role="alert"><strong>ERROR:</strong> MediaVerse Node not found</p>';
			break;
		case "super_err":
			echo '<p class="login-msg alert alert-danger" role="alert"><strong>ERROR:</strong> MediaVerse Node and WP User not found. It seems you have followed a link that does not exist.</p>';
			break;
		case "token_error":
			echo '<p class="login-msg alert alert-danger" role="alert"><strong>ERROR:</strong> There was an error verifying your TOKEN.</p>';
			break;
		case "not_mv_user":
			echo '<p class="login-msg alert alert-warning" role="alert"> Your WordPress account is not registered to MediaVerse. Please <span style="color: #619e85; font-weight: 900;"><a href="'. home_url() .'/mv-register/">Register</a></span> using a different e-mail.</p>';
			break;
	}

	if (is_user_logged_in()) {
		$current_user = wp_get_current_user();

		$mv_url = get_user_meta($current_user->ID, 'mvnode_url', true);

		echo '<div class="login-desc" style="margin-bottom: 10px;">
            You are logged in! <br>
                <a href='. wp_logout_url( home_url()) .' title="Logout">Logout</a>
            </div>';

		// Add server information here
		echo   '<label for="mv_node"><b>Selected MediaVerse node: </b></label>
				<input type="text" id="lname" name="mv_node" value="'.$mv_url.'" disabled style="width: 300px;">';

		echo '<div> <form method="post">
                <input class="mb-5" type="submit" name="btn_assets" value="Click here to import your MediaVerse Projects, including added assets"></form>
              </div>';


		if(isset($_POST['btn_assets'])){
			import_projects($current_user);
		}

	} else {
		echo '<div class="login-branding">
                <h1 style="color: #619e85;">MEDIAVERSE</h1>
                <div class="login-desc" style="margin-bottom: 10px;">
                    Use your MediaVerse account to login and experience all the tools, assets and inspirational artworks the project has to offer.
                </div>
            </div>
            <label for="mv_node"><b>Selected MediaVerse node: </b></label>
            <input type="text" id="lname" name="mv_node" value="' . $selected_mv_node . '" disabled style="width: 300px;">
            <div class="login-form shadow-lg p-3 mb-5 bg-white rounded">';

		/*<div style="margin-bottom: 10px;">If you are not a Mediaverse user yet, please <span style="color: #619e85; font-weight: 900;"><a href="'. home_url() .'/mv-register/">Register</a></span> first.</div>*/
		$args = array(
			'redirect' => home_url('/mv-login/?node_src='.$selected_mv_node),
			'label_username' => 'E-mail address',
			'id_username' => 'user',
			'id_password' => 'pass'
		);
		wp_login_form($args);
		echo '</div>';
	}


	echo '<script>  
        let element = document.getElementById("wp-submit");
        element.classList.add("fusion-button");    
        element.classList.add("button-flat");
        element.classList.add("button-xlarge");
        element.classList.add("button-default");
        element.classList.add("button-5");
        element.classList.add("fusion-button-default-span");
        element.classList.add("form-form-submit");
        element.classList.add("button-default");
    </script>';

}

function redirect_login_page(){
	$login_page  = home_url('/mv-login/');
	$page_viewed = basename($_SERVER['REQUEST_URI']);

	if ($page_viewed == "wp-login.php" && $_SERVER['REQUEST_METHOD'] == 'GET') {
		wp_redirect($login_page);
		exit;
	}
}
//add_action('init', 'redirect_login_page');

function login_failed($username, $error) {
	$login_page  = home_url('/mv-login/');
	wp_redirect($login_page . '?login=failed');
	exit;
}
add_action('wp_login_failed', 'login_failed' );

function verify_username_password($user, $email, $password)
{
	$login_page = home_url('/mv-login/');

	if ($email == "" || $password == "") {
		wp_redirect($login_page . "?login=empty");
		exit;
	}

	// Check if user exists in WP
	$is_wp_user = email_exists($email);

	// 1. Check if there is a node url
	$redir_url = $_POST['redirect_to'] ?? null;

	// 2. Check if node url is valid
	$node_url = $redir_url ? substr($redir_url, strpos($redir_url, "=") + 1) : null;

	// 3. If node url is invalid then check if WP users exists, then get url from META
	if (filter_var($node_url, FILTER_VALIDATE_URL) != true) {

		if ($is_wp_user) {
			$user = get_user_by('email', $email);
			$user_id = $user->ID;

			// 4b. and there is indeed a node url
			$node_url = get_user_meta( $user_id, 'mvnode_url' );
		} else {
			wp_redirect($login_page . "?login=super_err");
			exit;
		}
	}
	// 4. If node URL is valid then continue
	else {

		// Login to MV
		$url = $node_url.'/dam/authentication/login';
		$curl = curl_init();

		curl_setopt_array($curl, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => "{\n  \"email\": \"$email\",\n  \"password\": \"$password\"\n}",
			CURLOPT_HTTPHEADER => [
				"Content-Type: application/json"
			],
		]);

		$response = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);

		if ($err) { echo "cURL Error #:" . $err; }

		$response_obj = json_decode($response);
		$mvtoken = $response_obj->token;

		// 2. If MV token exists, then user is registered in MV.
		$is_mv_user = false;
		if ($mvtoken) { // if $response_obj->message then the user is not registered in MV
			$is_mv_user = true;
		} else {
			echo 'ERROR: Mediaverse user not found';
			wp_redirect($login_page . "?login=token_null");
			exit;
		}

		// 3a. If there is no WP user with credentials, register user!
		if (!$is_wp_user) {

			$username = strstr($email,'@',true);
			$n = 3;
			$random_chars = bin2hex(random_bytes($n));

			$userdata = array(
				'user_login' => $username.$random_chars,
				'user_email' => $email,
				'user_pass' => $password,
				'role' => 'project_master',
			);

			$user_id = wp_insert_user($userdata);

		} else
			// 3b. If user exists, then get user id.
		{
			$user = get_user_by('email', $email);
			$user_id = $user->ID;
		}

		// 4. Replace token
		update_user_meta($user_id, 'mvnode_token', $mvtoken);

		// 5. Save node info
		update_user_meta($user_id, 'mvnode_url', $node_url);

		// TODO 'IF' might be redundant.
		if ($mvtoken != '') {
			wp_redirect($login_page);
			return $mvtoken;
		}
	}
}

add_filter('authenticate', 'verify_username_password', 10, 3);

// function redirect_on_login($user_login, $user){
//     $current_user = wp_get_current_user();
//     $current_user_id = $current_user->ID;
//     $vrtoken = get_user_meta($current_user_id, 'token', true);

//     if($vrtoken!=null){
//         $some_url = home_url();
//         wp_redirect($some_url."?mv_token=". $vrtoken);
//         exit;
//     }else{
//         $some_url = home_url();
//         wp_redirect($some_url . "?mv_token=empty");
//         exit;
//     }
// }
// add_action('login_redirect', 'redirect_on_login', 10, 2);

function mv_login_redirect($url, $request, $user){
	$login_page = home_url('/mv-login/');

	if ($user && is_object($user) && is_a($user, 'WP_User')) {
		$current_user_id = $user->ID;
		$vrtoken = get_user_meta($current_user_id, 'mvnode_token', true);

		if ($user->has_cap('administrator')) {
			if ($vrtoken != null) {
				/*$url = $login_page. '?mv_token='. $vrtoken;*/
				$url = $login_page;
			}else{
				$url = admin_url();
			}
		} else {
			if ($vrtoken != null) {
				/*$url = $login_page . '?mv_token=' . $vrtoken;*/
				$url = $login_page;
			} else {
				$url = home_url() . '?mv_token=empty';
			}
		}
	}
	return $url;
}
add_filter('login_redirect', 'mv_login_redirect', 11, 3);


function logout_page(){
	$login_page  = home_url('/mv-login/');
	wp_redirect($login_page . "?login=false");
	exit;
}
add_action('wp_logout', 'logout_page');

add_shortcode('mvnode_login', 'mvnode_login_shortcode');

// The callback function that will replace [book]
function mvnode_login_shortcode(){
	ob_start();
	mvnode_login_form();
	return ob_get_clean();
}

function get_mv_asset($token, $id, $node_url)
{

	$url = $node_url."/dam/assets/" . $id;
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	$headers = array(
		"Accept: application/json",
		"Authorization: Bearer " . $token,
	);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	$resp = curl_exec($curl);
	curl_close($curl);

	$info = json_decode($resp);
	$array = json_decode($resp, true);

	if(sizeof($array) == 1) {
		echo '<p class="login-msg alert alert-warning" role="alert">'. $info->message .'</p>';
		return false;
	}

	$file_extension = pathinfo($array['originalFilename'], PATHINFO_EXTENSION);
	if ($file_extension == 'glb') {
		$deepLinkKeys[] = $array['deepLinkKey'];
		$asset_name[] = $array['originalFilename'];
		$screenshot_key[] = $array['previewLinkKey'];
		$description[] = $array['description'];
		return [$deepLinkKeys, $asset_name, $screenshot_key, $description];
	} else {
		return null;
	}

}

function import_projects($user)
{
	$node_url = get_user_meta($user->ID, 'mvnode_url', true);
	$token = get_user_meta($user->ID, 'mvnode_token', true);

	// 1.a Get projects from MV
	$url = $node_url."/dam/project/userList";
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	$headers = array(
		"Accept: application/json",
		"Authorization: Bearer " . $token,
	);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	$resp = curl_exec($curl);
	curl_close($curl);

	$info = json_decode($resp);
	$array = json_decode($resp, true);


	if(!is_array($info)) {

		echo '<p class="login-msg alert alert-warning" role="alert">'. $info->message .'</p>';
		return false;

	} else {


		// 1.b Create projects in VROdos if they do not exist
		for ($i = 0; $i < count($array); ++$i) {

			// Create title and check if project with same title exists
			$title = 'mv_' . strip_tags($array[$i]['name']);

			// Custom Query
			$args = array(
				'name' => esc_attr($title),
				'post_type' => 'vrodos_game'
			);
			$query = get_posts( $args );


			// Create new projects only if they do not exist
			if (!$query) {

				$mv_project_id = $array[$i]['id'];
				$asset_ids_arr = $array[$i]['assetIds'];

				$taxonomy = get_term_by('slug', 'virtualproduction_games', 'vrodos_game_type');
				$project_type_id = $taxonomy->term_id;
				$project_taxonomies = array(
					'vrodos_game_type' => array(
						$project_type_id
					),
				);

				$data = array(
					'post_title' => esc_attr($title),
					'post_name' => esc_attr($title),
					'post_content' => '',
					'post_type' => 'vrodos_game',
					'post_status' => 'publish',
					'tax_input' => $project_taxonomies,
				);



				// Create project
				$project_id = wp_insert_post($data);
				$post = get_post($project_id);




				// Link project to game type
				wp_set_object_terms(  $post->ID, 'virtualproduction_games', 'vrodos_game_type' );

				// Create a parent game tax category for the scenes
				wp_insert_term($post->post_title,'vrodos_scene_pgame', array(
						'description'=> '-',
						'slug' => $post->post_name,
					)
				);

				// Create a parent game tax category for the assets
				wp_insert_term($post->post_title,'vrodos_asset3d_pgame',array(
						'description'=> '-',
						'slug' => $post->post_name,
					)
				);

				// Save custom field mv_project_id for WP project, to use when uploading recorded video.
				update_post_meta($post->ID, 'mv_project_id', $mv_project_id);

				// Create default scenes for each project
				vrodos_create_default_scenes_for_game($post->post_name, $project_type_id);

				//wp_set_object_terms(  $project_id , 'virtualproduction_games', 'vrodos_asset3d_pgame' );

				print_r('Fetched MediaVerse project with id: ' . $mv_project_id);
				echo '<br>';
				print_r('Created project in VROdos with id: ' . $project_id);
				echo '<br>';
				print_r('---');
				echo '<br>';

				// 2. Continue with assets import
				for ($j = 0; $j< count($asset_ids_arr); ++$j) {

					if(!empty($asset_ids_arr[$j])) {

						// 2.a Get asset entry from MV
						$asset_result = get_mv_asset($token, $asset_ids_arr[$j], $node_url);
						if ($asset_result) {

							$key = $asset_result[0][0];
							$name = $asset_result[1][0];
							$screenshot_key = $asset_result[2][0];
							$description = $asset_result[3][0];

							$file_extension = pathinfo($name, PATHINFO_EXTENSION);
							$output_filename = $key .'.'. $file_extension;
							$name = strtok($name, '.');

							$host = $node_url."/dam/deeplink/" . $key . "/download";
							$ch = curl_init();
							curl_setopt($ch, CURLOPT_URL, $host);
							curl_setopt($ch, CURLOPT_VERBOSE, 1);
							curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
							curl_setopt($ch, CURLOPT_AUTOREFERER, false);
							curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
							curl_setopt($ch, CURLOPT_HEADER, 0);
							$result = curl_exec($ch);
							curl_close($ch);

							$upload_dir = wp_upload_dir();
							$DS = DIRECTORY_SEPARATOR;
							$upload_path = str_replace('/', $DS, $upload_dir['basedir']) . $DS . 'models' . $DS . $project_id . $DS;

							// Check that folder 'Models' exist and create it if not
							// Create subfolders for each Project
							$dirname = dirname($upload_path . $output_filename);

							if (!is_dir($dirname))
							{
								mkdir($dirname, 0777, true);
							}

							// The following lines write the contents to a file in the same directory (provided permissions etc)
							if (!file_exists($upload_path . $output_filename)) {

								// Write asset
								$fp = fopen($upload_path . $output_filename, 'w');
								fwrite($fp, $result);
								fclose($fp);

								$asset_cat_id = get_term_by('slug', 'decoration', 'vrodos_asset3d_cat'); // Choose the type of asset.

								$game_entry = get_post($project_id); // Get project slug
								$game_slug = $game_entry->post_name;

								// Add metadata to asset
								$assetPGame = get_term_by('slug', $game_slug, 'vrodos_asset3d_pgame'); // Link each asset to specific project.

								$asset_id = vrodos_create_asset_frontend($assetPGame->term_id, $asset_cat_id->term_id, $game_slug, null, $name, null, null, null, $description);

								$glbFile_id = vrodos_upload_AssetText($result, $name, $asset_id, $_FILES, 0, $project_id);
								update_post_meta($asset_id, 'vrodos_asset3d_glb', $glbFile_id);

								$host_screen = $node_url."/dam/previewlink/" . $screenshot_key . "/download";
								$ch_screen = curl_init();
								curl_setopt($ch_screen, CURLOPT_URL, $host_screen);
								curl_setopt($ch_screen, CURLOPT_VERBOSE, 1);
								curl_setopt($ch_screen, CURLOPT_FOLLOWLOCATION, true);
								curl_setopt($ch_screen, CURLOPT_RETURNTRANSFER, 1);
								curl_setopt($ch_screen, CURLOPT_AUTOREFERER, false);
								curl_setopt($ch_screen, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
								curl_setopt($ch_screen, CURLOPT_HEADER, 0);
								$result_screen = curl_exec($ch_screen);
								curl_close($ch_screen);

								// Save screenshot image in Uploads
								$fp = fopen($upload_path . $screenshot_key, 'w');
								fwrite($fp, $result_screen);
								fclose($fp);
								$image_content = file_get_contents($upload_path . $screenshot_key);
								$image_base64Data = base64_encode($image_content);

								$final_image = 'data:image/png;base64,' . $image_base64Data;

								vrodos_upload_asset_screenshot($final_image, $asset_id, $project_id);
							}
						}
					}
				}
			}
		}
	}
}

function complete_registration($username, $password, $email, $first_name, $last_name, $dateOfBirth){

	$usermvdata = array(
		"email" => $email,
		"password" => $password,
		"username" => $username,
		"firstname" => $first_name,
		"lastname" => $last_name,
		"dateOfBirth" => $dateOfBirth
	);

	$args = array(
		'body' => $usermvdata,
	);

	$url = 'https://dashboard.mediaverse.atc.gr/dam/authentication/register';
	$response = wp_remote_post($url, array(
		'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
		'body'        => json_encode($args["body"]),
		'method'      => 'POST',
		'data_format' => 'body',
	));

	$token_body = json_decode($response["body"]);

	$token = $token_body->token;

	if($token) {

		$userdata = array(
			'user_login'    =>   $username,
			'user_email'    =>   $email,
			'user_pass'     =>   $password,
			'first_name'    =>   $first_name,
			'last_name'     =>   $last_name,
			'role' => 'project_master',
		);

		$user = wp_insert_user($userdata);
		// here the token is saved to user data
		add_user_meta($user, 'mvnode_token', $token);

		echo '<div class="alert alert-info" role="alert">
            Registration to VRodos is complete and your MVNode token is succesfully acquired!
            Go to <a href="' . get_site_url() . '/wp-login.php">login page</a>. to get the most of the VRodos experience!</div><br>';

		return $token;
	} else {
		//echo '<div class="alert alert-info" role="alert">MediaVerse error: '.$token_body->message.'</div>';

		return false;
	}
}

function validateAge($birthday, $age = 18)
{
	// $birthday can be UNIX_TIMESTAMP or just a string-date.
	if(is_string($birthday)) {
		$birthday = strtotime($birthday);
	}

	// check
	// 31536000 is the number of seconds in a 365 days year.
	if(time() - $birthday < $age * 31536000)  {
		return false;
	}

	return true;
}
