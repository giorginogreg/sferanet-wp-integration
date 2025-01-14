<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link  https://github.com/giorginogreg
 * @since 1.0.0
 *
 * @package    Sferanet_Wordpress_Integration
 * @subpackage Sferanet_Wordpress_Integration/admin
 */

use Sferanet_Wordpress_Integration\Statuses\Account_Status;
use Sferanet_Wordpress_Integration\Statuses\Financial_Transaction_Status;
use Sferanet_Wordpress_Integration\Statuses\Generic_Status;
use Sferanet_Wordpress_Integration\Statuses\Practice_Status;
use Sferanet_Wordpress_Integration\Types\Movement_Type;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Sferanet_Wordpress_Integration
 * @subpackage Sferanet_Wordpress_Integration/admin
 * @author     Gregorio Giorgino <g.giorgino@grifomultimedia.it>
 */
class Sferanet_WordPress_Integration_Admin {


	/**
	 * The ID of this plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * Base url used for API calls
	 *
	 * @var [string] Base url used for API calls
	 */
	protected $base_url;

	/**
	 * Token used for auth in API calls
	 *
	 * @var [string] Token used for auth in API calls
	 */
	protected $token;

	/**
	 * Token used for researching a single user
	 *
	 * @var [string] Token used for auth in API calls
	 */
	protected $facilews_token;



	protected $options;
	/**
	 * Get the value of token
	 */
	public function get_token() {

		return $this->token ?? get_option( 'sferanet_token' );
	}
	/**
	 * Set the value of token
	 *
	 * @param mixed $token JWT token.
	 *
	 * @return Sferanet_WordPress_Integration_Admin
	 */
	public function set_token( $token ) {
		update_option( 'sferanet_token', $token );
		$this->token = $token;
		return $this;
	}
	/**
	 * Get the value of token
	 */
	public function get_facilews_token() {

		return $this->facilews_token ?? get_option( 'sferanet_facilews_token' );
	}

	/**
	 * Set the value of token
	 *
	 * @param mixed $token JWT token.
	 *
	 * @return Sferanet_WordPress_Integration_Admin
	 */
	public function set_facilews_token( $token ): Sferanet_WordPress_Integration_Admin {
		update_option( 'sferanet_facilews_token', $token );
		$this->facilews_token = $token;
		return $this;
	}

	/**
	 * The version of this plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string    $version    The current version of this plugin.
	 */
	private $version;

	private $logger;
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name = 'Sferanet', $version = '1.0.0' ) {

		require_once plugin_dir_path( __FILE__ ) . '../logs/class-sferanet-wordpress-integration-logs-admin.php';
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->base_url    = 'https://catture.partnersolution.it';
		$this->options     = get_option( 'sferanet-settings' );

		$this->logger = Sferanet_Wordpress_Integration_Logs_Admin::getInstance();
		try {
			$this->validate_token();
		} catch ( Exception $th ) {
		 //phpcs:ignore
		 wp_die( 'Login error: ' . $th->getMessage() );
		}
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Sferanet_Wordpress_Integration_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Sferanet_Wordpress_Integration_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/sferanet-wordpress-integration-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Sferanet_Wordpress_Integration_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Sferanet_Wordpress_Integration_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/sferanet-wordpress-integration-admin.js', array( 'jquery' ), $this->version, false );

	}

	 /**
	  * Registers a new settings page under Settings.
	  */
	public function admin_menu() {

		add_options_page(
			__( 'SferaNet Settings', 'sferanet' ),
			__( 'SferaNet Settings', 'sferanet' ),
			'manage_options',
			'options_sferanet',
			array( $this, 'settings_page' )
		);
	}

	public function admin_register_setting() {

		register_setting( 'sferanet-settings-group', 'sferanet-settings' );

		add_settings_section(
			'settings', // section ID
			__( 'Settings', 'sferanet' ), // title (if needed)
			'', // callback function (if needed)
			'sferanet-settings-group' // page slug
		);

		add_settings_field(
			'agency_code_field',
			__( 'Agency code', 'sferanet' ),
			array( $this, 'agency_code_field_render' ),
			'sferanet-settings-group', // page slug
			'settings'
			// section ID
		);
		add_settings_field(
			'agency_id_field',
			__( 'Agency ID', 'sferanet' ),
			array( $this, 'agency_id_field_render' ),
			'sferanet-settings-group', // page slug
			'settings' // section ID
		);
		add_settings_field(
			'attachment_type_id_field',
			__( 'Attachment Type ID', 'sferanet' ),
			array( $this, 'attachment_type_id_field_render' ),
			'sferanet-settings-group', // page slug
			'settings' // section ID
		);
	}

	/**
	 * Settings page display callback.
	 */
	function settings_page() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/settings.php';
	}

	function agency_code_field_render() {
		?>
		<label>
			<input type='text' name='sferanet-settings[agency_code_field]' value='<?php echo $this->options['agency_code_field']; ?>'>
		</label>
		<?php
	}
	function agency_id_field_render() {
		?>
		<label>
			<input type='text' name='sferanet-settings[agency_id_field]' value='<?php echo $this->options['agency_id_field']; ?>'>
		</label>
		<?php
	}
	function attachment_type_id_field_render() {
		?>
		<label>
			<input type='text' name='sferanet-settings[attachment_type_id_field]' value='<?php echo $this->options['attachment_type_id_field']; ?>'>
		</label>
		<?php
	}


	/**
	 * Make login into management software and return the token
	 *
	 * @throws Exception Method that throws exception if the http request had some trouble or if the credentials are not valid.
	 * @return string|bool Token
	 */
	public function login_sferanet() {

		$this->logger->sferanet_logs( 'Logging into sferanet...' );

		$ep = '/login_check';

		$response = wp_remote_post(
			$this->base_url . $ep,
			array(
				'body' => array(
					'_username' => getenv( 'SFERANET_USERNAME' ),
					'_password' => getenv( 'SFERANET_PASSWORD' ),
				),
			)
		);
		if ( is_wp_error( $response ) ) {
			$this->logger->sferanet_logs( 'Token successfully acquired.' );
			// throw new \Exception( 'Error during login call in Sfera Net: ' . $response->get_error_message(), 1 );
		}
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		$is_token_in_body = isset( $body['token'] );
		if ( $is_token_in_body ) {
			$this->logger->sferanet_logs( 'Token successfully acquired.' );

			$this->set_token( $body['token'] );
			return $body['token'];
		} else {
			// It can be also credentials mismatch
			$this->logger->sferanet_logs( 'Error Processing Request: token not set in response body. JSON from response: ' . wp_json_encode( $body ) );

			// throw new \Exception( 'Error Processing Request: token not set in response body', 1 );
		}
		return false;
	}

	/**
	 * Make login into management software and return the token
	 *
	 * @return string Token
	 * @throws Exception Method that throws exception if the http request had some trouble or if the credentials are not valid.
	 */
	public function login_facilews() {

		$this->logger->sferanet_logs( 'Logging into facilews...' );

		$ep = 'http://facilews.partnersolution.it/public/login.php';

		$response = wp_remote_post(
			$ep,
			array(
				'body' => array(
					'username' => getenv( 'FACILEWS_USERNAME' ),
					'password' => getenv( 'FACILEWS_PASSWORD' ),
				),
			)
		);
		if ( is_wp_error( $response ) ) {
			throw new Exception( 'Error during login call in Sfera Net: ' . $response->get_error_message(), 1 );
		}
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		$is_token_in_body = isset( $body['jwt'] );
		if ( $is_token_in_body ) {
			$this->logger->sferanet_logs( 'Token from facilews successfully acquired.' );
			update_option( 'sferanet_facilews_token', $body['jwt'] );
			$this->set_facilews_token( $body['jwt'] );

			return $body['jwt'];
		} else {
			// It can be also credentials mismatch
			$this->logger->sferanet_logs( 'Error Processing Request: token from facilews not set in response body' );
		}
		return false;
	}

	/**
	 * Return if token is at least valid for more than 5 minutes
	 *
	 * @param  mixed $token JWT token.
	 * @return [type]
	 */
	public function is_token_valid( $token ) {

		if ( ! $token ) {
			return false;
		}

		list($header, $payload, $signature) = explode( '.', $token );
		// $token_decoded = JWT::decode($token );
		// $token_decoded->time

		$payload = json_decode( base64_decode( $payload ) );
		// $payload->exp; // altri dati: username, iat, roles (array di stringhe)
		return ( $payload->exp > strtotime( '+5 min' ) );

	}

	//phpcs:ignore
	public function validate_token() {
		if ( ! $this->is_token_valid( $this->get_token() ) ) {
			$this->logger->sferanet_logs( "Token Not Valid, value: {$this->get_token()}" );
			$this->logger->sferanet_logs( 'Refreshing token...' );

			$this->login_sferanet();
			$this->logger->sferanet_logs( 'Token refreshed successfully' );
		}
	}

	public function validate_facilews_token() {
		if ( ! $this->is_token_valid( $this->get_facilews_token() ) ) {
			$this->logger->sferanet_logs( "FacileWS Token Not Valid, value: {$this->get_facilews_token()}" );
			$this->logger->sferanet_logs( 'Refreshing token...' );

			$this->login_facilews();
			$this->logger->sferanet_logs( 'FacileWS Token refreshed successfully' );
		}
	}

	private function get_all_accounts( $contractor = null ) {
		$ep = '/accounts';
		$this->logger->sferanet_logs( 'Getting all accounts.' );

		$this->validate_token();
		$response = wp_remote_get(
			$this->base_url . $ep,
			array(
				'headers' => $this->build_headers(),
			)
		);
		if ( is_wp_error( $response ) ) {
			$this->logger->sferanet_logs( 'Error while getting all accounts. Error: ' . $response->get_error_message() );

			throw new Exception( 'Error while getting all accounts. Error: ' . $response->get_error_message(), 1 );
		}
		$response_code = wp_remote_retrieve_response_code( $response );
		$status        = false;
		switch ( $response_code ) {
			case 200:
				$status = true;
				$msg    = 'Accounts retrieved successfully.';
				$body   = json_decode( wp_remote_retrieve_body( $response ) );
				$data   = $body->{'hydra:member'};
				break;

			default:
				$msg = 'Generic error, debug please.';
		}
		$this->logger->sferanet_logs( 'Response from get_accounts API :' );
		$response = array(
			'status' => $status,
			'msg'    => $msg,
			'data'   => $data,
		);
		$this->logger->sferanet_logs( $response );

		return $response;
	}

	/**
	 * id can be VAT code or TAX code
	 *
	 * @param string $id User ID
	 * @param bool $is_business Bool value that represent if a user is business or not
     *
	 * @return bool | stdClass
	 */
	public function get_user_by_id(string $id, bool $is_business = false ) {
		$field = $is_business ? 'piva' : 'cf';
		$this->validate_facilews_token();
		$ep  = "https://facilews3.partnersolution.it/Api/Rest/Account/{$this->options['agency_code_field']}";
		$ep .= "?$field=$id";

		$this->logger->sferanet_logs( 'Getting user by id at EP ' . $ep );

		$response = wp_remote_get(
			$ep,
			array(
				'headers' => $this->build_headers( $this->get_facilews_token() ),
			)
		);
		$this->logger->sferanet_logs( 'Response: ' . wp_json_encode( wp_remote_retrieve_body( $response ) ) );

		return json_decode( wp_remote_retrieve_body( $response ) );
	}

	/**
	 * Add passenger to a practice already existent
	 *
	 * @param mixed $passenger   - Object with properties.
	 *                           - cognome * - nome * -
	 *                           is_contraente * -
	 *                           data_nascita -> format
	 *                           01/01/1990 - sesso -
	 *                           cellulare.
	 *
	 * @param  mixed $practice_id Id of the practice already existent.
	 * @return array('status'=> true | false, "msg" => "")
	 * @throws Exception An exception is thrown if the http call had some trouble issues.
	 */
	public function add_passenger_practice( $passenger, $practice_id ) {

		$ep = '/prt_praticapasseggeros';

		$this->validate_token();
		$this->logger->sferanet_logs( 'Calling add_passenger_practice API.' );
		$body = array(
			'pratica'       => "prt_praticas/$practice_id",
			'cognomepax'    => $passenger->surname,
			'nomepax'       => $passenger->name,
			'annullata'     => 0, // ?
			'iscontraente'  => 0,
			'datadinascita' => $passenger->birthday,
			'sesso'         => $passenger->sex,
		);

		if ( isset( $passenger->phone ) ) {
			$body['cellulare'] = $passenger->phone;
		}
		/*
		if ( isset( $passenger->email_address ) ) {
			$body['email'] = $passenger->email_address;
		}
		*/
		/*
		if ( isset( $passenger->birthplace ) ) {
			$body['luogo_nascita'] = $passenger->birthplace;
		}
		*/

		if ( isset( $passenger->attachments ) ) {
			$this->add_allegatos( $passenger->attachments, $practice_id );
		}

		$this->logger->sferanet_logs( 'Payload: ' . wp_json_encode( $body ) );
		$response = wp_remote_post(
			$this->base_url . $ep,
			array(
				'body'    => wp_json_encode( $body ),
				'headers' => $this->build_headers(),
			)
		);
		if ( is_wp_error( $response ) ) {
			$this->logger->sferanet_logs( "Error while adding a passenger to the practice. Passenger: $passenger->surname $passenger->name. Error: " . $response->get_error_message() );
			throw new Exception( "Error while adding a passenger to the practice. Passenger: $passenger->surname $passenger->name. Error: " . $response->get_error_message(), 1 );
		}
		$response_code = wp_remote_retrieve_response_code( $response );
		$status        = false;
		switch ( $response_code ) {
			case 201:
				$status = true;
				$msg    = "Passenger associated successfully to practice $practice_id";
				break;
			case 400:
				$msg = 'Invalid input';
				break;
			case 404:
				$msg = 'Practice id not found.';
				break;
			default:
				$msg = 'Generic error, debug please.';
		}

		$response = array(
			'status' => $status,
			'msg'    => $msg,
			'data'   => wp_remote_retrieve_body( $response ),
		);
		$this->logger->sferanet_logs( 'Response: ' . wp_json_encode( $response ) );

		return $response;
	}

	/**
	 * Create a new practice with work in progress status.
	 *
	 * @param mixed $contractor User with the following properties:
	 *                          - Surname
	 *                          - Name.
	 * @param mixed $practice_data
	 * @return [type]
	 * @throws Exception An exception is thrown if the http call had some trouble issues.
	 */
	public function create_practice( $contractor, $practice_data = null ) {

		$ep = '/prt_praticas';
		$this->validate_token();

		$this->logger->sferanet_logs( 'Creating a new practice.' );

		$date = gmdate( 'Y-m-d\TH:i:s.v\Z' );

		$body = array(
			'codiceagenzia'      => $this->options['agency_code_field'],
			'tipocattura'        => Sferanet_WordPress_Integration::CAPTURE_TYPE,
			// 'passeggeri'      => ['nome e cognome', 'nome2'...],
			// 'codicecliente'      => 'string',
			// 'externalid'         => '123456mioid',

			/*
			'servizi'          => array(
				'string',
			),
			*/
			// "codextpuntovendita"=> "string", // optional=> length 36
			// "codicecliente"=> "string", // optional=> length 36
			// "capcliente"=> "string", //  optional=> length 10
			// "localitacliente" => "string", //localita cliente length 100
			// "nazionecliente" => "string",  Nazione Cliente iso length 3
			// "externalid" => "string", // id riga Pratica (Vostro id/guid riferimento Pratica/vendita ) Opzionale
			// "delivering" => "string", // di collegamento con Commesse etc.. ex comm:xxx Opzionale
			// 'id'                 => 'string', // id della pratica
			'datacreazione'      => $date,
			'datasaldo'          => $date,
			'datamodifica'       => $date,
			'stato'              => Practice_Status::WORK_IN_PROGRESS,
			'descrizionepratica' => $practice_data->description, // Ciò che apparirà sulla fattura
			'noteinterne'        => '',
			'noteesterne'        => '',

			/*
				'prtPraticaservizio' => array(
					'string', ??
				),
			*/
			// 'user'               => 'string',
			// 'elaborata'          => 0,

			// Contractor data
			'cognomecliente'     => $contractor->surname,
			'nomecliente'        => $contractor->name,
		);

		if ( isset( $contractor->address ) ) {
			$body['indirizzo'] = $contractor->address;
		}
		if ( isset( $contractor->phone_number ) ) {
			$body['telefonocliente'] = $contractor->phone_number;
		}
		if ( isset( $contractor->email_address ) ) {
			$body['emailcliente'] = $contractor->email_address;
		}

		$this->logger->sferanet_logs( 'Payload: ' . wp_json_encode( $body ) );

		$response = wp_remote_post(
			$this->base_url . $ep,
			array(
				'body'    => wp_json_encode( $body ),
				'headers' => $this->build_headers(),
			)
		);
		if ( is_wp_error( $response ) ) {
			$this->logger->sferanet_logs( 'Error while creating a new practice. Error: ' . $response->get_error_message() );

			throw new Exception( 'Error while creating a new practice. Error: ' . $response->get_error_message(), 1 );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$status        = false;
		switch ( $response_code ) {
			case 201:
				$status      = true;
				$msg         = 'Practice created successfully';
				$body        = json_decode( wp_remote_retrieve_body( $response ) );
				$practice_id = explode( '/', $body->{@'id'} ); //phpcs:ignore
				$data        = array(
					'practice_id' => $practice_id[ count( $practice_id ) - 1 ],
				);
				break;
			case 400:
				$msg = 'Invalid input';
				break;
			case 404:
				$msg = 'Resource not found.';
				break;
		}

		$response = array(
			'status' => $status,
			'msg'    => $msg,
			'data'   => $data,
		);
		$this->logger->sferanet_logs( 'Response: ' . wp_json_encode( $response ) );

		return $response;
	}


	/**
	 * Adds a new customer into the managerial software SferaNet.
	 *
	 * @param mixed $customer Customer object.
	 *
	 * @return [type]
	 * @throws Exception
	 */
	public function create_account( $customer ) {

		$ep = '/accounts';
		$this->validate_token();

		$this->logger->sferanet_logs( 'Creating a new account. ' );

		$date    = gmdate( 'Y-m-d\TH:i:s.v\Z' );
		$options = get_option( 'sferanet-settings' );
		$body    = array(
			'codiceagenzia'      => $options['agency_code_field'],
			'tipocattura'        => Sferanet_WordPress_Integration::CAPTURE_TYPE,
			'cognome'            => $customer->surname, // Surname or business name
			'flagpersonafisica'  => (int) $customer->is_physical_person,
			'codicefiscale'      => $customer->fiscal_code, // Can be also VAT number
			'iscliente'          => 0,
			'isfornitore'        => 0,
			'ispromotore'        => 0,
			'creazione'          => $date,
			'indirizzo1'         => $customer->first_address,
			'stato'              => Account_Status::INSERTING,
			'emailcomunicazioni' => $customer->email_address,
			'datanascita'        => $customer->birthday,
		);

		$optional_values = array(
			// Managerial sw key 	 => $object key

			'partitaiva'             => 'VAT_number',
			'externalid'             => 'external_id', // Univoque id from the supplier
			'nome'                   => 'name',
			'localitanascitacitta'   => 'born_city',
			'localitaresidenzacitta' => 'residence_city',
			'nazione'                => 'nation',
			'cap'                    => 'postal_code',

			'indirizzo2'             => 'additional_address',
			'sex'                    => 'sex',
			'id'                     => 'id',
			'user'                   => 'user',
		);

		foreach ( $optional_values as $mgr_sw_key => $obj_key ) {
			if ( isset( $customer->$obj_key ) ) {
				$body[ $mgr_sw_key ] = $customer->$obj_key;
			}
		}

		$this->logger->sferanet_logs( 'Payload: ' . wp_json_encode( $body ) );
		$headers  = $this->build_headers();
		$response = wp_remote_post(
			$this->base_url . $ep,
			array(
				'body'    => wp_json_encode( $body ),
				'headers' => $headers,
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->logger->sferanet_logs( 'Error while creating a new customer. Error: ' . $response->get_error_message() );
			throw new Exception( 'Error while creating a new customer. Error: ' . $response->get_error_message(), 1 );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$this->logger->sferanet_logs( "Response with code $response_code after parsing: " . wp_remote_retrieve_body( $response ) );
		$status = false;
		switch ( $response_code ) {
			case 201:
				$status = true;
				$msg    = 'Customer created successfully';
				$body   = json_decode( wp_remote_retrieve_body( $response ) );
				$data   = array(
					'account_created' => $body,
				);
				break;
			case 400:
				$msg = 'Invalid input';
				break;
			case 404:
				$msg = 'Resource not found.';
				break;
		}

		$response = array(
			'status' => $status,
			'msg'    => $msg,
			'data'   => $data,
		);

		$this->logger->sferanet_logs( 'Response: ' . wp_json_encode( $response ) );

		return $response;
	}

	public function add_service( $service, $practice_id = '' ) {
		// Tipo pacchetto: catalogo o crociera
		// Tipo vendita: ORG (Netta? - Default), INT (Commissionabile?) TODO: Chiedere a savio se in fase di creazione è corretta
		// TODO: campo itinerario not found

		// Opzionali

		/*
			// Per voli aerei e per pacchetti tour operator

			clausole idoneità esigenze specifiche del viaggiatore (non trovato su api) - Da inserire anche in ecommerce

			prtPraticaservizioquota (Array[string], optional): Add prtQuote
			pratica (string, optional): di appartenenza @ORM\ManyToOne(targetEntity="PrtPratica",inversedBy="servizi") ,
			quote (Array[string], optional): One servizio has many quote.
			prtServizio (object, optional, read only),
			prtQuote (object, optional, read only),
			externalid (string, optional): id riga servizio (Vostro id/guid riferimento praticaservizio ) Opzionale ,
			regimevendita (string, optional): Regime Vendita "74T", "ORD"
			codiceisodestinazione (string, optional): Codice Iso Destinazione length 10 ,
			codicefornitore (string): length 36
			// - Codice del fornitore del servizio. Nel sistema del provider può corrispondere al codice provider
				// Se possibile utilizzare come codice, in ordine di priorità, la Partita Iva del fornitore o
				// il Codice Fiscale (se persona fisica senza Partita Iva) o
				// il codice identificativo del fornitore nel sistema di origine (meglio se univoco)
			brand (string, optional): Brand servizio length 10
			localitaDescrizioneLibera (string, optional): Localita descrizione servizio length 255
			riferimentopressofornitore (string, optional): Person / email length 50
			nomestrutturavoucher (string, optional),
			indirizzostrutturavoucher (string, optional),
			mailstrutturavoucher (string, optional),
			telefonostrutturavoucher (string, optional),

			noteinterne (string, optional),
			noteesterne (string, optional),
			bookingagencyrefext (string, optional): Riferimento Pcc (se attivo B2b) ,
			codiceagenzianetwork (string, optional),
			codicechannel (string, optional),
			descrizionechannel (string, optional),
			passeggeri (string, optional),
			fileorigine (string, optional),
			nazionechannel (string, optional): Nazione Channel iso length 3
			nazionefornitore (string, optional): Nazione Fornitore iso length 3
			id (string, optional, read only): id del servizio
			user (string, optional)
		*/

		// Assicurazione annull:
		// -> Proposta ma non accettata: se a pagamento ma non accettata.
		// -> Inclusa: se prezzo è 0
		// -> Stipulata: prezzo non inserito?
		// -> Non prevista

		$ep = '/prt_praticaservizios';
		$this->validate_token();
		$date = gmdate( 'Y-m-d\TH:i:s.v\Z' );
		$this->logger->sferanet_logs( "Associating a new service to the practice $practice_id" );

		$body = array(
			'annullata'           => 0,
			'datacreazione'       => $date,
			'tipodestinazione'    => $service->destination_type,
			'tiposervizio'        => $service->type,
			'descrizione'         => $service->name, // Ciò che apparirà sulla fattura (stesso della pratica)
			'ragsocfornitore'     => $service->supplier_business_name,
			'codicefornitore'     => $service->supplier_business_code,
			'codicefilefornitore' => $service->supplier_file_code, // Codice di Conferma del fornitore per la prenotazione
			'datainizioservizio'  => $service->start_date,
			'datafineservizio'    => $service->end_date,
			'duratagg'            => $service->duration_days,
			'duratant'            => $service->duration_nights,
			'nrpaxadulti'         => $service->no_pax_adults,
			'nrpaxchild'          => $service->no_pax_childs,
			'nrpaxinfant'         => $service->no_pax_infants,
		);

		$optional_values = array(
			// Managerial sw key 	 => $object key
			'partenzada'                 => '', // descrittivo partenza
			'rientroa'                   => '', // descrittivo rientro
			'destinazione'               => '',
			'sistemazione'               => '', // TODO: La select ha molte opzioni, noi invece inseriamo un testo libero, da discutere
			'struttura'                  => '', // nome struttura solo per pacchetti tour operator
			'trattamento'                => '', // TODO: sull'ecommerce non presente?
			'trasporti'                  => '', // Campo descrittivo (len 255)
			'altriservizi'               => '', // TODO: non trovato su gestionale? - Titoli di quello che ha acquistato
			'quote'                      => '',
			'prtServizio'                => '',
			'prtQuote'                   => '',
			'externalid'                 => '',
			'regimevendita'              => '',
			'codiceisodestinazione'      => '',
			'brand'                      => '',
			'localitaDescrizioneLibera'  => '',
			'riferimentopressofornitore' => '',
			'nomestrutturavoucher'       => '',
			'indirizzostrutturavoucher'  => '',
			'mailstrutturavoucher'       => '',
			'telefonostrutturavoucher'   => '',
			'noteinterne'                => '',
			'noteesterne'                => '',
			'bookingagencyrefext'        => '',
			'codiceagenzianetwork'       => '',
			'codicechannel'              => '',
			'descrizionechannel'         => '',
			'passeggeri'                 => '',
			'fileorigine'                => '',
			'nazionechannel'             => '',
			'nazionefornitore'           => '',
			'id'                         => '',
			'prtPraticaservizioquota'    => '',
			'user'                       => '',
		);

		if ( $practice_id ) {
			$body['pratica'] = "/prt_praticas/$practice_id";
		}

		foreach ( $optional_values as $mgr_sw_key => $obj_key ) {
			if ( isset( $service->$obj_key ) ) {
				$body[ $mgr_sw_key ] = $service->$obj_key;
			}
		}

		$this->logger->sferanet_logs( 'Payload: ' . wp_json_encode( $body ) );

		$response = wp_remote_post(
			$this->base_url . $ep,
			array(
				'body'    => wp_json_encode( $body ),
				'headers' => $this->build_headers(),
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->logger->sferanet_logs( 'Error while associating a service to a practice. Error: ' . $response->get_error_message() );
			throw new Exception( 'Error while associating a service to a practice. Error: ' . $response->get_error_message(), 1 );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$status        = false;
		$response_body = json_decode( wp_remote_retrieve_body( $response ) );

		switch ( $response_code ) {
			case 201:
				$status = true;
				$msg    = 'Service associated to practice successfully';
				$data   = array(
					'service_associated' => $response_body,
				);
				break;
			case 400:
				$msg  = 'Invalid input';
				$data = $response_body;
				break;
			case 404:
				$msg = 'Resource not found.';
				break;
			default:
				$msg = 'Generic error, debug please';
		}

		$response = array(
			'status' => $status,
			'msg'    => $msg,
			'data'   => $data,
		);

		$this->logger->sferanet_logs( 'Response: ' . wp_json_encode( $response ) );

		return $response;
	}

	public function add_quote_service( $sold_services, $service_id ) {
		/*
			servizio (string, optional): di appartenenza @ORM\ManyToOne(targetEntity="PrtPraticaservizio",inversedBy="quote")
			datacambiocosto (string, optional): data del cambio
			codiceisovalutacosto (string, optional)
			tassocambiocosto (number, optional): Valore di cambio alla data indicata da datacambiocosto ,
			cambioineurocosto (integer, optional): (0|1) Vale 0 se il cambio è espresso in valuta estera Vs Euro. Esempio: Dollari necessari per acquistare per 1 euro. Vale 1 se il cambio è espresso in Euro Vs valuta estera. Esempio: Euro necessari per acquistare 1 Dollaro. ,
			id (string, optional, read only),
			user (string, optional)
		*/

		$ep = '/prt_praticaservizioquotas';
		$this->validate_token();
		$date = gmdate( 'Y-m-d\TH:i:s.v\Z' );
		$this->logger->sferanet_logs( 'Adding quote to service with id ' . $service_id );

		$body = array(
			'descrizionequota'                 => 'Test descrizione',
			'datavendita'                      => $date,
			'quantitacosto'                    => 1, // fornitore?
			'costovalutaprimaria'              => 0, // costo fornitore
			'codiceisovalutacosto'             => 'EUR',
			'quantitaricavo'                   => count( $sold_services ), // No. services sold
			'ricavovalutaprimaria'             => array_sum( array_column( $sold_services, 'price' ) ),
			'codiceisovalutaricavo'            => 'EUR', // ??
			'commissioniattivevalutaprimaria'  => 0,
			'commissionipassivevalutaprimaria' => 0,
			'progressivo'                      => 0,
			'annullata'                        => 0,
			'servizio'                         => "prt_praticaservizios/$service_id",
		);

		/*
		$optional_values = array(
			// Managerial sw key 	 => $object key
			'datacambiocosto'   => '',
			'tassocambiocosto'  => '',
			'cambioineurocosto' => '',
			'id'                => '',
			'user'              => '',
		);
		*/

		/*
		  foreach ( $optional_values as $mgr_sw_key => $obj_key ) {
			if ( isset( $customer->$obj_key ) ) {
				$body[ $mgr_sw_key ] = $customer->$obj_key;
			}
		}
		*/

		$this->logger->sferanet_logs( 'Payload: ' . wp_json_encode( $body ) );

		$response = wp_remote_post(
			$this->base_url . $ep,
			array(
				'body'    => wp_json_encode( $body ),
				'headers' => $this->build_headers(),
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->logger->sferanet_logs( 'Error while creating a practice quote related to a service. Error: ' . $response->get_error_message() );

			throw new Exception( 'Error while creating a practice quote related to a service. Error: ' . $response->get_error_message(), 1 );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$status        = false;
		$response_body = json_decode( wp_remote_retrieve_body( $response ) );

		switch ( $response_code ) {
			case 201:
				$status = true;
				$msg    = 'Quote created and associated to the service successfully';
				$data   = array(
					'quote_created' => $response_body,
				);
				break;
			case 400:
				$msg  = 'Invalid input';
				$data = $response_body;
				break;
			case 404:
				$msg = 'Resource not found.';
				break;
			default:
				$msg = 'Generic error, debug please';
		}

		$response = array(
			'status' => $status,
			'msg'    => $msg,
			'data'   => $data,
		);
		$this->logger->sferanet_logs( 'Response: ' . wp_json_encode( $response ) );
		return $response;
	}

	public function add_financial_transaction( $financial_transaction, $practice_id, $order_id = null ) {
		/*
			- Manca operatore ADV
			- Deposito finanziario - manca

			stato (string): stato della Movimento ( INS, MOD, CANC)
				- INS quando è stata caricata completamente
				- MOD quando è stata modificata in uno dei suoi elementi
				- WPRELOAD per ricaricaricare completamente gli elementi interni (tutti i child verranno annullati) lo stato dovrà poi essere settato a MOD
			externalid (string): id riga Pratica/lista (Vostro id/guid riferimento Pratica/vendita/lista )

			codicefile (string, optional): Codice di Conferma del fornitore per la prenotazione quando disponibile length 20
			codiceaida (string, optional): Codice carta Aida
			spesebancarie (number, optional),
			id (string, optional): id del Movimento ,
			datamatrimonio (string, optional),
			firma (string, optional),
			dedica (string, optional),
			user (string, optional)
		*/

		$ep = '/mov_finanziarios';
		$this->validate_token();
		$date = gmdate( 'Y-m-d\TH:i:s.v\Z' );
		$this->logger->sferanet_logs( 'Adding financial transaction.' );

		$options = get_option( 'sferanet-settings' );

		$body = array(
			'codiceagenzia' => $options['agency_code_field'],
			'tipocattura'   => Sferanet_WordPress_Integration::CAPTURE_TYPE,
			'externalid'    => $order_id ?? wp_unique_id(),
			'codcausale'    => 'POS', // TODO: testare se corretto
			'datamovimento' => $date,
			'datacreazione' => $date,
			'datamodifica'  => $date,
			'descrizione'   => $financial_transaction->description,
			'importo'       => $financial_transaction->total,
			'stato'         => Financial_Transaction_Status::INSERTING,
			'tipomovimento' => Movement_Type::RECESSED,
		);

		/*
		$optional_values = array(
			// Managerial sw key 	 => $object key
			'datacambiocosto'   => '',
			'tassocambiocosto'  => '',
			'cambioineurocosto' => '',
			'id'                => '',
			'user'              => '',
		);
		*/

		/*
		  foreach ( $optional_values as $mgr_sw_key => $obj_key ) {
			if ( isset( $customer->$obj_key ) ) {
				$body[ $mgr_sw_key ] = $customer->$obj_key;
			}
		}
		*/
		$this->logger->sferanet_logs( 'Payload: ' . wp_json_encode( $body ) );

		$response = wp_remote_post(
			$this->base_url . $ep,
			array(
				'body'    => wp_json_encode( $body ),
				'headers' => $this->build_headers(),
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->logger->sferanet_logs( 'Error while creating a transactional movement. Error: ' . $response->get_error_message() );
			throw new Exception( 'Error while creating a transactional movement. Error: ' . $response->get_error_message(), 1 );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$status        = false;
		$response_body = json_decode( wp_remote_retrieve_body( $response ) );

		switch ( $response_code ) {
			case 201:
				$status = true;
				$msg    = 'Financial Movement created correctly';
				$data   = array(
					'financial_movement' => $response_body,
				);
				break;
			case 400:
				$msg  = 'Invalid input';
				$data = $response_body;
				break;
			case 404:
				$msg = 'Resource not found.';
				break;
			default:
				$data = $response_body;
				$msg  = 'Generic error, debug please. Error code: ' . $response_code;
		}

		$response = array(
			'status' => $status,
			'msg'    => $msg,
			'data'   => $data,
		);

		$this->logger->sferanet_logs( 'Response: ' . wp_json_encode( $response ) );
		return $response;
	}

	/**
	 * Undocumented function
	 *
	 * @param [type] $attachments
	 * @param [type] $practice_id
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function add_allegatos( $attachments, $practice_id ) {
		$ep = '/allegatos';
		$this->validate_token();
		$this->logger->sferanet_logs( 'Adding attachments.' );
		$optional_values = array(
			// 'id'                            => 'string',
			'agenziaid'                     => $this->options['agency_id_field'],
			'allegatotipoid'                => $this->options['attachment_type_id_field'],
			'visibileingestionedocumentale' => 1,
			'note'                          => '',
			// 'uploaddownload'                => '',
			'nometabella'                   => 'PRT_Pratica',
			'idrecord'                      => $practice_id,
			// 'subidrecord'                   => '',
			'pubblico'                      => 0,
			'flagannullato'                 => false,
			'stato'                         => Generic_Status::INSERTING,
			'codiceagenzia'                 => $this->options['agency_code_field'],
		);
		foreach ( $attachments as $i => $url_attachment ) {

			$date = gmdate( 'Y-m-d\TH:i:s.v\Z' );

			$attachment = wp_remote_get( parse_url( $url_attachment, PHP_URL_PATH ) );

			$body                    = array( 'data' => base64_encode( $attachment ) );
			$body                    = array_merge( $body, $optional_values );
			$body['nomefile']        = "attachment_$i"; // _{$practice_id}_
			$body['datainserimento'] = $date;
			$body['descrizione']     = 'Attachment';

			$response = wp_remote_post(
				$this->base_url . $ep,
				array(
					'body'    => wp_json_encode( $body ),
					'headers' => $this->build_headers(),
				)
			);

			if ( is_wp_error( $response ) ) {
				$this->logger->sferanet_logs( 'Error while adding an attachment. Error: ' . $response->get_error_message() );
				throw new Exception( 'Error while adding an attachment. Error: ' . $response->get_error_message(), 1 );
			}

			$response_code = wp_remote_retrieve_response_code( $response );
			$status        = false;
			$response_body = json_decode( wp_remote_retrieve_body( $response ) );

			switch ( $response_code ) {
				case 201:
					$status = true;
					$msg    = 'Attachments created correctly';
					$data   = array(
					// 'financial_movement' => $response_body,
					);
					break;
				case 400:
					$msg  = 'Invalid input';
					$data = $response_body;
					break;
				case 404:
					$msg = 'Resource not found.';
					break;
				default:
					$msg = 'Generic error, debug please';
			}
		}

		$response = array(
			'status' => $status,
			'msg'    => $msg,
			'data'   => $data,
		);

		$this->logger->sferanet_logs( 'Response: ' . json_encode( $response ) );
		return $response;
	}

	public function finalize_practice( $practice_id ) {
		$this->logger->sferanet_logs( 'Finalizing practice ' . $practice_id );

		$ep = "/prt_praticas/$practice_id";

		$body = array(
			'stato' => Practice_Status::INSERTING,
		);

		$args = array(
			'body'    => wp_json_encode( $body ),
			'headers' => $this->build_headers(),
			'method'  => 'PUT',
		);

		$response      = wp_remote_request( $this->base_url . $ep, $args );
		$response_code = wp_remote_retrieve_response_code( $response );
		$status        = false;
		$response_body = json_decode( wp_remote_retrieve_body( $response ) );

		switch ( $response_code ) {
			case 200:
				$status = true;
				$msg    = 'Practice finalized correctly';
				$data   = array(
				// 'financial_movement' => $response_body,
				);
				break;
			case 400:
				$msg  = 'Invalid input';
				$data = $response_body;
				break;
			case 404:
				$msg = 'Resource not found.';
				break;
			default:
				$msg  = 'Generic error, debug please';
				$data = $response_body;
		}

		$response = array(
			'status' => $status,
			'msg'    => $msg,
			'data'   => $data,
		);
		$this->logger->sferanet_logs( 'Response: ' . json_encode( $response ) );
		return $response;
	}

	private function build_headers( $token = null ) {
		$ttoken = $token ?? $this->get_token();
		return array(
			'Authorization' => 'Bearer ' . $ttoken,
			'Content-Type'  => 'application/json',
		);
	}
}
