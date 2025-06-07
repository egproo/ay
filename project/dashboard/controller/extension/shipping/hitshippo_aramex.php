<?php
class ControllerExtensionShippinghitshippoaramex extends Controller {
	private $error = array();
	public function install() {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "hitshippo_aramex_details_new` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `order_id` text NOT NULL,
		  `tracking_num` text NOT NULL,
		  `shipping_label` text COLLATE utf8_bin NOT NULL,
		  `invoice` text COLLATE utf8_bin NOT NULL,
		  `return_label` text COLLATE utf8_bin  NULL,
		  `return_invoice` text COLLATE utf8_bin  NULL,
		  `one` text COLLATE utf8_bin  NULL,
		  `two` text COLLATE utf8_bin  NULL,
		  `three` text COLLATE utf8_bin  NULL,
		  PRIMARY KEY (`id`)
		)");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "hitshippo_aramex_pickup_details` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `order_id` text NOT NULL,
		  `status` text NOT NULL,
		  `confirm_no` text COLLATE utf8_bin NOT NULL,
		  `ready_time` text COLLATE utf8_bin NOT NULL,
		  `pickup_date` text COLLATE utf8_bin  NULL,
		  `one` text COLLATE utf8_bin  NULL,
		  `two` text COLLATE utf8_bin  NULL,
		  `three` text COLLATE utf8_bin  NULL,
		  PRIMARY KEY (`id`)
		)");
	}
	public function index() {
		$this->install();
		$this->load->language('extension/shipping/hitshippo_aramex');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');
		
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && isset($_POST['btn_licence_activation'])) {

			if (!$this->request->post['shipping_hitshippo_aramex_licence_licence'] && empty($this->request->post['shipping_hitshippo_aramex_licence_licence'])) {
			return 'Enter Licence Key';
			}
			if (!$this->request->post['shipping_hitshippo_aramex_licence_email'] && empty($this->request->post['shipping_hitshippo_aramex_licence_email'])) {
				return 'Enter Purchased Email Address';
			}

				$licence_key = trim($this->request->post['shipping_hitshippo_aramex_licence_licence']);
				$licence_email = trim($this->request->post['shipping_hitshippo_aramex_licence_email']);
				$licenc_check_url = "https://hittechmarket.com/?edd_action=check_license&item_id=2288&license=".$licence_key;
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_URL, $licenc_check_url);
				$result = curl_exec($ch);
				curl_close($ch);
				$obj = json_decode($result);
				
				if(isset($obj->success) && $obj->success == true)
				{
					if(isset($obj->license) && ($obj->license == 'inactive' || $obj->license == 'valid'))
					{
						if(isset($obj->customer_email) && $obj->customer_email == $licence_email)
						{
							if(isset($obj->activations_left) && $obj->activations_left != '0')
							{
								$licenc_activate_url = "https://hittechmarket.com/?edd_action=activate_license&item_id=2288&license=".$licence_key;
								$ch = curl_init();
								curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
								curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
								curl_setopt($ch, CURLOPT_URL, $licenc_activate_url);
								$result = curl_exec($ch);
								curl_close($ch);
								$obj = json_decode($result);
								
								if(isset($obj->expires))
								{
									$this->request->post['shipping_hitshippo_aramex_licence_expires'] = $obj->expires;
									$this->request->post['shipping_hitshippo_aramex_licence_status_licence'] = 'Activated';
								}

								}else
							{
								$this->error['warning'] = 'Licence Is Already Activated In another one Site. Report HIT TECH.';
							}

						}else
						{
							$this->error['warning'] = 'Purchased Email Address is Invalid.';
						}

					}else
					{
						$this->error['warning'] = 'The Licence is not valid for this Product.';
					}
				}
				else{
					$this->error['warning'] = 'invalid Licence Key';
				}
		$this->model_setting_setting->editSetting('shipping_hitshippo_aramex_licence', $this->request->post);
			
		}else if (($this->request->server['REQUEST_METHOD'] == 'POST') && isset($_POST['btn_renew_activation'])) {

			if (!$this->request->post['shipping_hitshippo_aramex_renew_licence'] && empty($this->request->post['shipping_hitshippo_aramex_renew_licence'])) {
				$this->error['warning'] = 'Enter Licence Key.';
			return 'Enter Licence Key';
			}
			if (!$this->request->post['shipping_hitshippo_aramex_renew_email'] && empty($this->request->post['shipping_hitshippo_aramex_renew_email'])) {
				$this->error['warning'] = 'Enter Purchased Email Address.';
				return 'Enter Purchased Email Address';
			}

				$licence_key = trim($this->request->post['shipping_hitshippo_aramex_renew_licence']);
				$licence_email = trim($this->request->post['shipping_hitshippo_aramex_renew_email']);
				$licenc_check_url = "https://hittechmarket.com/?edd_action=check_license&item_id=2288&license=".$licence_key;
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_URL, $licenc_check_url);
				$result = curl_exec($ch);
				curl_close($ch);
				$obj = json_decode($result);
				
				if(isset($obj->success) && $obj->success == true)
				{
					if(isset($obj->license) && ($obj->license == 'inactive' || $obj->license == 'valid'))
					{
						if(isset($obj->customer_email) && $obj->customer_email == $licence_email)
						{
							
							$this->request->post['shipping_hitshippo_aramex_renew_expires'] = $obj->expires;
						}else
						{
							$this->error['warning'] = 'Purchased Email Address is Invalid.';
						}

					}else
					{
						$this->error['warning'] = 'The Licence is not valid for this Product.';
					}
				}
				else{
					$this->error['warning'] = 'invalid Licence Key';
				}
		$this->model_setting_setting->editSetting('shipping_hitshippo_aramex_renew', $this->request->post);
			
		}else if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('shipping_hitshippo_aramex', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true));
		}



		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}
		
		if (isset($this->error['key'])) {
			$data['error_key'] = $this->error['key'];
		} else {
			$data['error_key'] = '';
		}

		if (isset($this->error['password'])) {
			$data['error_password'] = $this->error['password'];
		} else {
			$data['error_password'] = '';
		}

		if (isset($this->error['account'])) {
			$data['error_account'] = $this->error['account'];
		} else {
			$data['error_account'] = '';
		}
		
		if (isset($this->error['postcode'])) {
			$data['error_postcode'] = $this->error['postcode'];
		} else {
			$data['error_postcode'] = '';
		}

		if (isset($this->error['dimension'])) {
			$data['error_dimension'] = $this->error['dimension'];
		} else {
			$data['error_dimension'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/shipping/hitshippo_aramex', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/shipping/hitshippo_aramex', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true);

		
		if (isset($this->request->post['shipping_hitshippo_aramex_test'])) {
			$data['shipping_hitshippo_aramex_test'] = $this->request->post['shipping_hitshippo_aramex_test'];
		} else {
			$data['shipping_hitshippo_aramex_test'] = $this->config->get('shipping_hitshippo_aramex_test');
		}
		
		if (isset($this->request->post['shipping_hitshippo_aramex_key'])) {
			$data['shipping_hitshippo_aramex_key'] = $this->request->post['shipping_hitshippo_aramex_key'];
		} else {
			$data['shipping_hitshippo_aramex_key'] = $this->config->get('shipping_hitshippo_aramex_key');
		}

		if (isset($this->request->post['shipping_hitshippo_aramex_password'])) {
			$data['shipping_hitshippo_aramex_password'] = $this->request->post['shipping_hitshippo_aramex_password'];
		} else {
			$data['shipping_hitshippo_aramex_password'] = $this->config->get('shipping_hitshippo_aramex_password');
		}

		if (isset($this->request->post['shipping_hitshippo_aramex_account'])) {
			$data['shipping_hitshippo_aramex_account'] = $this->request->post['shipping_hitshippo_aramex_account'];
		} else {
			$data['shipping_hitshippo_aramex_account'] = $this->config->get('shipping_hitshippo_aramex_account');
		}

		if (isset($this->request->post['shipping_hitshippo_aramex_account_entity'])) {
			$data['shipping_hitshippo_aramex_account_entity'] = $this->request->post['shipping_hitshippo_aramex_account_entity'];
		} else {
			$data['shipping_hitshippo_aramex_account_entity'] = $this->config->get('shipping_hitshippo_aramex_account_entity');
		}

		if (isset($this->request->post['shipping_hitshippo_aramex_account_pin'])) {
			$data['shipping_hitshippo_aramex_account_pin'] = $this->request->post['shipping_hitshippo_aramex_account_pin'];
		} else {
			$data['shipping_hitshippo_aramex_account_pin'] = $this->config->get('shipping_hitshippo_aramex_account_pin');
		}
		
		if (isset($this->request->post['shipping_hitshippo_aramex_status'])) {
			$data['shipping_hitshippo_aramex_status'] = $this->request->post['shipping_hitshippo_aramex_status'];
		} else {
			$data['shipping_hitshippo_aramex_status'] = $this->config->get('shipping_hitshippo_aramex_status');
		}
		
		if (isset($this->request->post['shipping_hitshippo_aramex_sort_order'])) {
			$data['shipping_hitshippo_aramex_sort_order'] = $this->request->post['shipping_hitshippo_aramex_sort_order'];
		} else {
			$data['shipping_hitshippo_aramex_sort_order'] = $this->config->get('shipping_hitshippo_aramex_sort_order');
		}

		if (isset($this->request->post['shipping_hitshippo_aramex_shipper_name'])) {
			$data['shipping_hitshippo_aramex_shipper_name'] = $this->request->post['shipping_hitshippo_aramex_shipper_name'];
		} else {
			$data['shipping_hitshippo_aramex_shipper_name'] = $this->config->get('shipping_hitshippo_aramex_shipper_name');
		}
		
		if (isset($this->request->post['shipping_hitshippo_aramex_company_name'])) {
			$data['shipping_hitshippo_aramex_company_name'] = $this->request->post['shipping_hitshippo_aramex_company_name'];
		} else {
			$data['shipping_hitshippo_aramex_company_name'] = $this->config->get('shipping_hitshippo_aramex_company_name');
		}
		
		if (isset($this->request->post['shipping_hitshippo_aramex_phone_num'])) {
			$data['shipping_hitshippo_aramex_phone_num'] = $this->request->post['shipping_hitshippo_aramex_phone_num'];
		} else {
			$data['shipping_hitshippo_aramex_phone_num'] = $this->config->get('shipping_hitshippo_aramex_phone_num');
		}
		
		if (isset($this->request->post['shipping_hitshippo_aramex_email_addr'])) {
			$data['shipping_hitshippo_aramex_email_addr'] = $this->request->post['shipping_hitshippo_aramex_email_addr'];
		} else {
			$data['shipping_hitshippo_aramex_email_addr'] = $this->config->get('shipping_hitshippo_aramex_email_addr');
		}
		
		if (isset($this->request->post['shipping_hitshippo_aramex_address1'])) {
			$data['shipping_hitshippo_aramex_address1'] = $this->request->post['shipping_hitshippo_aramex_address1'];
		} else {
			$data['shipping_hitshippo_aramex_address1'] = $this->config->get('shipping_hitshippo_aramex_address1');
		}
		
		if (isset($this->request->post['shipping_hitshippo_aramex_address2'])) {
			$data['shipping_hitshippo_aramex_address2'] = $this->request->post['shipping_hitshippo_aramex_address2'];
		} else {
			$data['shipping_hitshippo_aramex_address2'] = $this->config->get('shipping_hitshippo_aramex_address2');
		}
		
		
		if (isset($this->request->post['shipping_hitshippo_aramex_city'])) {
			$data['shipping_hitshippo_aramex_city'] = $this->request->post['shipping_hitshippo_aramex_city'];
		} else {
			$data['shipping_hitshippo_aramex_city'] = $this->config->get('shipping_hitshippo_aramex_city');
		}
		
		
		if (isset($this->request->post['shipping_hitshippo_aramex_state'])) {
			$data['shipping_hitshippo_aramex_state'] = $this->request->post['shipping_hitshippo_aramex_state'];
		} else {
			$data['shipping_hitshippo_aramex_state'] = $this->config->get('shipping_hitshippo_aramex_state');
		}

		if (isset($this->request->post['shipping_hitshippo_aramex_language'])) {
			$data['shipping_hitshippo_aramex_language'] = $this->request->post['shipping_hitshippo_aramex_language'];
		} else {
			$data['shipping_hitshippo_aramex_language'] = $this->config->get('shipping_hitshippo_aramex_language');
		}		
		
		if (isset($this->request->post['shipping_hitshippo_aramex_country_code'])) {
			$data['shipping_hitshippo_aramex_country_code'] = $this->request->post['shipping_hitshippo_aramex_country_code'];
		} else {
			$data['shipping_hitshippo_aramex_country_code'] = $this->config->get('shipping_hitshippo_aramex_country_code');
		}
		$data['countrylist'] = array(
			'AF' => 'Afghanistan',
			'AX' => 'Aland Islands',
			'AL' => 'Albania',
			'DZ' => 'Algeria',
			'AS' => 'American Samoa',
			'AD' => 'Andorra',
			'AO' => 'Angola',
			'AI' => 'Anguilla',
			'AQ' => 'Antarctica',
			'AG' => 'Antigua and Barbuda',
			'AR' => 'Argentina',
			'AM' => 'Armenia',
			'AW' => 'Aruba',
			'AU' => 'Australia',
			'AT' => 'Austria',
			'AZ' => 'Azerbaijan',
			'BS' => 'Bahamas',
			'BH' => 'Bahrain',
			'BD' => 'Bangladesh',
			'BB' => 'Barbados',
			'BY' => 'Belarus',
			'BE' => 'Belgium',
			'BZ' => 'Belize',
			'BJ' => 'Benin',
			'BM' => 'Bermuda',
			'BT' => 'Bhutan',
			'BO' => 'Bolivia',
			'BQ' => 'Bonaire, Saint Eustatius and Saba',
			'BA' => 'Bosnia and Herzegovina',
			'BW' => 'Botswana',
			'BV' => 'Bouvet Island',
			'BR' => 'Brazil',
			'IO' => 'British Indian Ocean Territory',
			'VG' => 'British Virgin Islands',
			'BN' => 'Brunei',
			'BG' => 'Bulgaria',
			'BF' => 'Burkina Faso',
			'BI' => 'Burundi',
			'KH' => 'Cambodia',
			'CM' => 'Cameroon',
			'CA' => 'Canada',
			'CV' => 'Cape Verde',
			'KY' => 'Cayman Islands',
			'CF' => 'Central African Republic',
			'TD' => 'Chad',
			'CL' => 'Chile',
			'CN' => 'China',
			'CX' => 'Christmas Island',
			'CC' => 'Cocos Islands',
			'CO' => 'Colombia',
			'KM' => 'Comoros',
			'CK' => 'Cook Islands',
			'CR' => 'Costa Rica',
			'HR' => 'Croatia',
			'CU' => 'Cuba',
			'CW' => 'Curacao',
			'CY' => 'Cyprus',
			'CZ' => 'Czech Republic',
			'CD' => 'Democratic Republic of the Congo',
			'DK' => 'Denmark',
			'DJ' => 'Djibouti',
			'DM' => 'Dominica',
			'DO' => 'Dominican Republic',
			'TL' => 'East Timor',
			'EC' => 'Ecuador',
			'EG' => 'Egypt',
			'SV' => 'El Salvador',
			'GQ' => 'Equatorial Guinea',
			'ER' => 'Eritrea',
			'EE' => 'Estonia',
			'ET' => 'Ethiopia',
			'FK' => 'Falkland Islands',
			'FO' => 'Faroe Islands',
			'FJ' => 'Fiji',
			'FI' => 'Finland',
			'FR' => 'France',
			'GF' => 'French Guiana',
			'PF' => 'French Polynesia',
			'TF' => 'French Southern Territories',
			'GA' => 'Gabon',
			'GM' => 'Gambia',
			'GE' => 'Georgia',
			'DE' => 'Germany',
			'GH' => 'Ghana',
			'GI' => 'Gibraltar',
			'GR' => 'Greece',
			'GL' => 'Greenland',
			'GD' => 'Grenada',
			'GP' => 'Guadeloupe',
			'GU' => 'Guam',
			'GT' => 'Guatemala',
			'GG' => 'Guernsey',
			'GN' => 'Guinea',
			'GW' => 'Guinea-Bissau',
			'GY' => 'Guyana',
			'HT' => 'Haiti',
			'HM' => 'Heard Island and McDonald Islands',
			'HN' => 'Honduras',
			'HK' => 'Hong Kong',
			'HU' => 'Hungary',
			'IS' => 'Iceland',
			'IN' => 'India',
			'ID' => 'Indonesia',
			'IR' => 'Iran',
			'IQ' => 'Iraq',
			'IE' => 'Ireland',
			'IM' => 'Isle of Man',
			'IL' => 'Israel',
			'IT' => 'Italy',
			'CI' => 'Ivory Coast',
			'JM' => 'Jamaica',
			'JP' => 'Japan',
			'JE' => 'Jersey',
			'JO' => 'Jordan',
			'KZ' => 'Kazakhstan',
			'KE' => 'Kenya',
			'KI' => 'Kiribati',
			'XK' => 'Kosovo',
			'KW' => 'Kuwait',
			'KG' => 'Kyrgyzstan',
			'LA' => 'Laos',
			'LV' => 'Latvia',
			'LB' => 'Lebanon',
			'LS' => 'Lesotho',
			'LR' => 'Liberia',
			'LY' => 'Libya',
			'LI' => 'Liechtenstein',
			'LT' => 'Lithuania',
			'LU' => 'Luxembourg',
			'MO' => 'Macao',
			'MK' => 'Macedonia',
			'MG' => 'Madagascar',
			'MW' => 'Malawi',
			'MY' => 'Malaysia',
			'MV' => 'Maldives',
			'ML' => 'Mali',
			'MT' => 'Malta',
			'MH' => 'Marshall Islands',
			'MQ' => 'Martinique',
			'MR' => 'Mauritania',
			'MU' => 'Mauritius',
			'YT' => 'Mayotte',
			'MX' => 'Mexico',
			'FM' => 'Micronesia',
			'MD' => 'Moldova',
			'MC' => 'Monaco',
			'MN' => 'Mongolia',
			'ME' => 'Montenegro',
			'MS' => 'Montserrat',
			'MA' => 'Morocco',
			'MZ' => 'Mozambique',
			'MM' => 'Myanmar',
			'NA' => 'Namibia',
			'NR' => 'Nauru',
			'NP' => 'Nepal',
			'NL' => 'Netherlands',
			'NC' => 'New Caledonia',
			'NZ' => 'New Zealand',
			'NI' => 'Nicaragua',
			'NE' => 'Niger',
			'NG' => 'Nigeria',
			'NU' => 'Niue',
			'NF' => 'Norfolk Island',
			'KP' => 'North Korea',
			'MP' => 'Northern Mariana Islands',
			'NO' => 'Norway',
			'OM' => 'Oman',
			'PK' => 'Pakistan',
			'PW' => 'Palau',
			'PS' => 'Palestinian Territory',
			'PA' => 'Panama',
			'PG' => 'Papua New Guinea',
			'PY' => 'Paraguay',
			'PE' => 'Peru',
			'PH' => 'Philippines',
			'PN' => 'Pitcairn',
			'PL' => 'Poland',
			'PT' => 'Portugal',
			'PR' => 'Puerto Rico',
			'QA' => 'Qatar',
			'CG' => 'Republic of the Congo',
			'RE' => 'Reunion',
			'RO' => 'Romania',
			'RU' => 'Russia',
			'RW' => 'Rwanda',
			'BL' => 'Saint Barthelemy',
			'SH' => 'Saint Helena',
			'KN' => 'Saint Kitts and Nevis',
			'LC' => 'Saint Lucia',
			'MF' => 'Saint Martin',
			'PM' => 'Saint Pierre and Miquelon',
			'VC' => 'Saint Vincent and the Grenadines',
			'WS' => 'Samoa',
			'SM' => 'San Marino',
			'ST' => 'Sao Tome and Principe',
			'SA' => 'Saudi Arabia',
			'SN' => 'Senegal',
			'RS' => 'Serbia',
			'SC' => 'Seychelles',
			'SL' => 'Sierra Leone',
			'SG' => 'Singapore',
			'SX' => 'Sint Maarten',
			'SK' => 'Slovakia',
			'SI' => 'Slovenia',
			'SB' => 'Solomon Islands',
			'SO' => 'Somalia',
			'ZA' => 'South Africa',
			'GS' => 'South Georgia and the South Sandwich Islands',
			'KR' => 'South Korea',
			'SS' => 'South Sudan',
			'ES' => 'Spain',
			'LK' => 'Sri Lanka',
			'SD' => 'Sudan',
			'SR' => 'Suriname',
			'SJ' => 'Svalbard and Jan Mayen',
			'SZ' => 'Swaziland',
			'SE' => 'Sweden',
			'CH' => 'Switzerland',
			'SY' => 'Syria',
			'TW' => 'Taiwan',
			'TJ' => 'Tajikistan',
			'TZ' => 'Tanzania',
			'TH' => 'Thailand',
			'TG' => 'Togo',
			'TK' => 'Tokelau',
			'TO' => 'Tonga',
			'TT' => 'Trinidad and Tobago',
			'TN' => 'Tunisia',
			'TR' => 'Turkey',
			'TM' => 'Turkmenistan',
			'TC' => 'Turks and Caicos Islands',
			'TV' => 'Tuvalu',
			'VI' => 'U.S. Virgin Islands',
			'UG' => 'Uganda',
			'UA' => 'Ukraine',
			'AE' => 'United Arab Emirates',
			'GB' => 'United Kingdom',
			'US' => 'United States',
			'UM' => 'United States Minor Outlying Islands',
			'UY' => 'Uruguay',
			'UZ' => 'Uzbekistan',
			'VU' => 'Vanuatu',
			'VA' => 'Vatican',
			'VE' => 'Venezuela',
			'VN' => 'Vietnam',
			'WF' => 'Wallis and Futuna',
			'EH' => 'Western Sahara',
			'YE' => 'Yemen',
			'ZM' => 'Zambia',
			'ZW' => 'Zimbabwe',
		);

		$data['pickup_loc_type'] = array('B' =>'B (Business)','R' =>'R (Residence)','C' =>'C (Business/Residence)');
		$data['pickup_del_type'] = array('DD' => 'DD (DoorToDoor)','DA' => 'DA (DoorToAirport)','DC' => 'DC (DoorToDoor non-complaint)');
		$data['pickup_type'] = array('S' => 'S-SameDayPickup','A' => 'A-AdvancedPickup');

		
		if (isset($this->request->post['shipping_hitshippo_aramex_postcode'])) {
			$data['shipping_hitshippo_aramex_postcode'] = $this->request->post['shipping_hitshippo_aramex_postcode'];
		} else {
			$data['shipping_hitshippo_aramex_postcode'] = $this->config->get('shipping_hitshippo_aramex_postcode');
		}
		
		if (isset($this->request->post['shipping_hitshippo_aramex_realtime_rates'])) {
			$data['shipping_hitshippo_aramex_realtime_rates'] = $this->request->post['shipping_hitshippo_aramex_realtime_rates'];
		} else {
			$data['shipping_hitshippo_aramex_realtime_rates'] = $this->config->get('shipping_hitshippo_aramex_realtime_rates');
		}
		if (isset($this->request->post['shipping_hitshippo_aramex_insurance'])) {
			$data['shipping_hitshippo_aramex_insurance'] = $this->request->post['shipping_hitshippo_aramex_insurance'];
		} else {
			$data['shipping_hitshippo_aramex_insurance'] = $this->config->get('shipping_hitshippo_aramex_insurance');
		}

		if (isset($this->request->post['shipping_hitshippo_aramex_translation'])) {
			$data['shipping_hitshippo_aramex_translation'] = $this->request->post['shipping_hitshippo_aramex_translation'];
		} else {
			$data['shipping_hitshippo_aramex_translation'] = $this->config->get('shipping_hitshippo_aramex_translation');
		}

		if (isset($this->request->post['shipping_hitshippo_aramex_translation_key'])) {
			$data['shipping_hitshippo_aramex_translation_key'] = $this->request->post['shipping_hitshippo_aramex_translation_key'];
		} else {
			$data['shipping_hitshippo_aramex_translation_key'] = $this->config->get('shipping_hitshippo_aramex_translation_key');
		}
		
		if (isset($this->request->post['shipping_hitshippo_aramex_display_time'])) {
			$data['shipping_hitshippo_aramex_display_time'] = $this->request->post['shipping_hitshippo_aramex_display_time'];
		} else {
			$data['shipping_hitshippo_aramex_display_time'] = $this->config->get('shipping_hitshippo_aramex_display_time');
		}
		if (isset($this->request->post['shipping_hitshippo_aramex_front_end_logs'])) {
			$data['shipping_hitshippo_aramex_front_end_logs'] = $this->request->post['shipping_hitshippo_aramex_front_end_logs'];
		} else {
			$data['shipping_hitshippo_aramex_front_end_logs'] = $this->config->get('shipping_hitshippo_aramex_front_end_logs');
		}
			
		if (isset($this->request->post['shipping_hitshippo_aramex_payment_type'])) {
			$data['shipping_hitshippo_aramex_payment_type'] = $this->request->post['shipping_hitshippo_aramex_payment_type'];
		} else {
			$data['shipping_hitshippo_aramex_payment_type'] = $this->config->get('shipping_hitshippo_aramex_payment_type');
		}

		if (isset($this->request->post['shipping_hitshippo_aramex_pay_con'])) {
			$data['shipping_hitshippo_aramex_pay_con'] = $this->request->post['shipping_hitshippo_aramex_pay_con'];
		} else {
			$data['shipping_hitshippo_aramex_pay_con'] = $this->config->get('shipping_hitshippo_aramex_pay_con');
		}

		if (isset($this->request->post['shipping_hitshippo_aramex_cus_pay_con'])) {
			$data['shipping_hitshippo_aramex_cus_pay_con'] = $this->request->post['shipping_hitshippo_aramex_cus_pay_con'];
		} else {
			$data['shipping_hitshippo_aramex_cus_pay_con'] = $this->config->get('shipping_hitshippo_aramex_cus_pay_con');
		}
		
		
		if (isset($this->request->post['shipping_hitshippo_aramex_service'])) {
			$data['shipping_hitshippo_aramex_service'] = $this->request->post['shipping_hitshippo_aramex_service'];
		} elseif ($this->config->has('shipping_hitshippo_aramex_service')) {
			$data['shipping_hitshippo_aramex_service'] = $this->config->get('shipping_hitshippo_aramex_service');
		} else {
			$data['shipping_hitshippo_aramex_service'] = array();
		}

		$data['services'] = array();

		$data['services'][] = array(
			'text'  => $this->language->get('text_aramex_1'),
			'value' => 'PDX'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_aramex_2'),
			'value' => 'PPX'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_aramex_3'),
			'value' => 'PLX'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_aramex_4'),
			'value' => 'DDX'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_aramex_5'),
			'value' => 'DPX'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_aramex_6'),
			'value' => 'GDX'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_aramex_7'),
			'value' => 'GPX'
		);

		
		
		if (isset($this->request->post['shipping_hitshippo_aramex_weight'])) {
			$data['shipping_hitshippo_aramex_weight'] = $this->request->post['shipping_hitshippo_aramex_weight'];
		} else {
			$data['shipping_hitshippo_aramex_weight'] = $this->config->get('shipping_hitshippo_aramex_weight');
		}
		
		if (isset($this->request->post['shipping_hitshippo_aramex_packing_type'])) {
			$data['shipping_hitshippo_aramex_packing_type'] = $this->request->post['shipping_hitshippo_aramex_packing_type'];
		} else {
			$data['shipping_hitshippo_aramex_packing_type'] = $this->config->get('shipping_hitshippo_aramex_packing_type');
		}
		
		if (isset($this->request->post['shipping_hitshippo_aramex_per_item'])) {
			$data['shipping_hitshippo_aramex_per_item'] = $this->request->post['shipping_hitshippo_aramex_per_item'];
		} else {
			$data['shipping_hitshippo_aramex_per_item'] = $this->config->get('shipping_hitshippo_aramex_per_item');
		}
		
		
			
		if (isset($this->request->post['shipping_hitshippo_aramex_wight_b'])) {
			$data['shipping_hitshippo_aramex_wight_b'] = $this->request->post['shipping_hitshippo_aramex_wight_b'];
		} else {
			$data['shipping_hitshippo_aramex_wight_b'] = $this->config->get('shipping_hitshippo_aramex_wight_b');
		}
		
				
		if (isset($this->request->post['shipping_hitshippo_aramex_weight_c'])) {
			$data['shipping_hitshippo_aramex_weight_c'] = $this->request->post['shipping_hitshippo_aramex_weight_c'];
		} else {
			$data['shipping_hitshippo_aramex_weight_c'] = $this->config->get('shipping_hitshippo_aramex_weight_c');
		}
		
		if (isset($this->request->post['shipping_hitshippo_aramex_int_key'])) {
			$data['shipping_hitshippo_aramex_int_key'] = $this->request->post['shipping_hitshippo_aramex_int_key'];
		} else {
			$data['shipping_hitshippo_aramex_int_key'] = $this->config->get('shipping_hitshippo_aramex_int_key');
		}
		if (isset($this->request->post['shipping_hitshippo_aramex_auto_label'])) {
			$data['shipping_hitshippo_aramex_auto_label'] = $this->request->post['shipping_hitshippo_aramex_auto_label'];
		} else {
			$data['shipping_hitshippo_aramex_auto_label'] = $this->config->get('shipping_hitshippo_aramex_auto_label');
		}
		if (isset($this->request->post['shipping_hitshippo_aramex_send_mail_to'])) {
			$data['shipping_hitshippo_aramex_send_mail_to'] = $this->request->post['shipping_hitshippo_aramex_send_mail_to'];
		} else {
			$data['shipping_hitshippo_aramex_send_mail_to'] = $this->config->get('shipping_hitshippo_aramex_send_mail_to');
		}	
		if (isset($this->request->post['shipping_hitshippo_aramex_plt'])) {
			$data['shipping_hitshippo_aramex_plt'] = $this->request->post['shipping_hitshippo_aramex_plt'];
		} else {
			$data['shipping_hitshippo_aramex_plt'] = $this->config->get('shipping_hitshippo_aramex_plt');
		}

		if (isset($this->request->post['shipping_hitshippo_aramex_sat'])) {
			$data['shipping_hitshippo_aramex_sat'] = $this->request->post['shipping_hitshippo_aramex_sat'];
		} else {
			$data['shipping_hitshippo_aramex_sat'] = $this->config->get('shipping_hitshippo_aramex_sat');
		}

		if (isset($this->request->post['shipping_hitshippo_aramex_cod'])) {
			$data['shipping_hitshippo_aramex_cod'] = $this->request->post['shipping_hitshippo_aramex_cod'];
		} else {
			$data['shipping_hitshippo_aramex_cod'] = $this->config->get('shipping_hitshippo_aramex_cod');
		}
		
		if (isset($this->request->post['shipping_hitshippo_aramex_email_trach'])) {
			$data['shipping_hitshippo_aramex_email_trach'] = $this->request->post['shipping_hitshippo_aramex_email_trach'];
		} else {
			$data['shipping_hitshippo_aramex_email_trach'] = $this->config->get('shipping_hitshippo_aramex_email_trach');
		}
				
		if (isset($this->request->post['shipping_hitshippo_aramex_airway'])) {
			$data['shipping_hitshippo_aramex_airway'] = $this->request->post['shipping_hitshippo_aramex_airway'];
		} else {
			$data['shipping_hitshippo_aramex_airway'] = $this->config->get('shipping_hitshippo_aramex_airway');
		}

		if (isset($this->request->post['shipping_hitshippo_aramex_addcomment_check'])) {
			$data['shipping_hitshippo_aramex_addcomment_check'] = $this->request->post['shipping_hitshippo_aramex_addcomment_check'];
		} else {
			$data['shipping_hitshippo_aramex_addcomment_check'] = $this->config->get('shipping_hitshippo_aramex_addcomment_check');
		}

		if (isset($this->request->post['shipping_hitshippo_aramex_addcomment_box'])) {
			$data['shipping_hitshippo_aramex_addcomment_box'] = $this->request->post['shipping_hitshippo_aramex_addcomment_box'];
		}elseif(empty($this->config->get('shipping_hitshippo_aramex_addcomment_box'))){
			$data['shipping_hitshippo_aramex_addcomment_box'] = "Shipment created for your order: #{order number} . Track the shipment here https://www.aramex.com/track/track-results-new?ShipmentNumber={tracking number}";
		} else {
			$data['shipping_hitshippo_aramex_addcomment_box'] = $this->config->get('shipping_hitshippo_aramex_addcomment_box');
		}
				
		if (isset($this->request->post['shipping_hitshippo_aramex_dropoff_type'])) {
			$data['shipping_hitshippo_aramex_dropoff_type'] = $this->request->post['shipping_hitshippo_aramex_dropoff_type'];
		} else {
			$data['shipping_hitshippo_aramex_dropoff_type'] = $this->config->get('shipping_hitshippo_aramex_dropoff_type');
		}
				
		if (isset($this->request->post['shipping_hitshippo_aramex_duty_type'])) {
			$data['shipping_hitshippo_aramex_duty_type'] = $this->request->post['shipping_hitshippo_aramex_duty_type'];
		} else {
			$data['shipping_hitshippo_aramex_duty_type'] = $this->config->get('shipping_hitshippo_aramex_duty_type');
		}
		if (isset($this->request->post['shipping_hitshippo_aramex_output_type'])) {
			$data['shipping_hitshippo_aramex_output_type'] = $this->request->post['shipping_hitshippo_aramex_output_type'];
		} else {
			$data['shipping_hitshippo_aramex_output_type'] = $this->config->get('shipping_hitshippo_aramex_output_type');
		}
		
		if (isset($this->request->post['shipping_hitshippo_aramex_shipment_content'])) {
			$data['shipping_hitshippo_aramex_shipment_content'] = $this->request->post['shipping_hitshippo_aramex_shipment_content'];
		} else {
			$data['shipping_hitshippo_aramex_shipment_content'] = $this->config->get('shipping_hitshippo_aramex_shipment_content');
		}
		if (isset($this->request->post['shipping_hitshippo_aramex_logo'])) {
			$data['shipping_hitshippo_aramex_logo'] = $this->request->post['shipping_hitshippo_aramex_logo'];
		} else {
			$data['shipping_hitshippo_aramex_logo'] = $this->config->get('shipping_hitshippo_aramex_logo');
		}
		
		if (isset($this->request->post['shipping_hitshippo_aramex_pickup_auto'])) {
			$data['shipping_hitshippo_aramex_pickup_auto'] = $this->request->post['shipping_hitshippo_aramex_pickup_auto'];
		} else {
			$data['shipping_hitshippo_aramex_pickup_auto'] = $this->config->get('shipping_hitshippo_aramex_pickup_auto');
		}

		if (isset($this->request->post['shipping_hitshippo_aramex_pickup_loc_type'])) {
			$data['shipping_hitshippo_aramex_pickup_loc_type'] = $this->request->post['shipping_hitshippo_aramex_pickup_loc_type'];
		} else {
			$data['shipping_hitshippo_aramex_pickup_loc_type'] = $this->config->get('shipping_hitshippo_aramex_pickup_loc_type');
		}

		if (isset($this->request->post['shipping_hitshippo_aramex_pickup_del_type'])) {
			$data['shipping_hitshippo_aramex_pickup_del_type'] = $this->request->post['shipping_hitshippo_aramex_pickup_del_type'];
		} else {
			$data['shipping_hitshippo_aramex_pickup_del_type'] = $this->config->get('shipping_hitshippo_aramex_pickup_del_type');
		}

		if (isset($this->request->post['shipping_hitshippo_aramex_pickup_type'])) {
			$data['shipping_hitshippo_aramex_pickup_type'] = $this->request->post['shipping_hitshippo_aramex_pickup_type'];
		} else {
			$data['shipping_hitshippo_aramex_pickup_type'] = $this->config->get('shipping_hitshippo_aramex_pickup_type');
		}

		if (isset($this->request->post['shipping_hitshippo_aramex_pickup_days_after'])) {
			$data['shipping_hitshippo_aramex_pickup_days_after'] = $this->request->post['shipping_hitshippo_aramex_pickup_days_after'];
		} else {
			$data['shipping_hitshippo_aramex_pickup_days_after'] = $this->config->get('shipping_hitshippo_aramex_pickup_days_after');
		}

		if (isset($this->request->post['shipping_hitshippo_aramex_pic_pack_lac'])) {
			$data['shipping_hitshippo_aramex_pic_pack_lac'] = $this->request->post['shipping_hitshippo_aramex_pic_pack_lac'];
		} else {
			$data['shipping_hitshippo_aramex_pic_pack_lac'] = $this->config->get('shipping_hitshippo_aramex_pic_pack_lac');
		}

		if (isset($this->request->post['shipping_hitshippo_aramex_picper'])) {
			$data['shipping_hitshippo_aramex_picper'] = $this->request->post['shipping_hitshippo_aramex_picper'];
		} else {
			$data['shipping_hitshippo_aramex_picper'] = $this->config->get('shipping_hitshippo_aramex_picper');
		}
			if (isset($this->request->post['shipping_hitshippo_aramex_piccon'])) {
			$data['shipping_hitshippo_aramex_piccon'] = $this->request->post['shipping_hitshippo_aramex_piccon'];
		} else {
			$data['shipping_hitshippo_aramex_piccon'] = $this->config->get('shipping_hitshippo_aramex_piccon');
		}
			if (isset($this->request->post['shipping_hitshippo_aramex_pic_open_time'])) {
			$data['shipping_hitshippo_aramex_pic_open_time'] = $this->request->post['shipping_hitshippo_aramex_pic_open_time'];
		} else {
			$data['shipping_hitshippo_aramex_pic_open_time'] = $this->config->get('shipping_hitshippo_aramex_pic_open_time');
		}
			if (isset($this->request->post['shipping_hitshippo_aramex_pic_close_time'])) {
			$data['shipping_hitshippo_aramex_pic_close_time'] = $this->request->post['shipping_hitshippo_aramex_pic_close_time'];
		} else {
			$data['shipping_hitshippo_aramex_pic_close_time'] = $this->config->get('shipping_hitshippo_aramex_pic_close_time');
		}

		//licence
		if (isset($this->request->post['shipping_hitshippo_aramex_licence_licence'])) {
			$data['shipping_hitshippo_aramex_licence_licence'] = $this->request->post['shipping_hitshippo_aramex_licence_licence'];
		} else {
			$data['shipping_hitshippo_aramex_licence_licence'] = $this->config->get('shipping_hitshippo_aramex_licence_licence');
		}
		if (isset($this->request->post['shipping_hitshippo_aramex_licence_email'])) {
			$data['shipping_hitshippo_aramex_licence_email'] = $this->request->post['shipping_hitshippo_aramex_licence_email'];
		} else {
			$data['shipping_hitshippo_aramex_licence_email'] = $this->config->get('shipping_hitshippo_aramex_licence_email');
		}
		if (isset($this->request->post['shipping_hitshippo_aramex_licence_expires'])) {
			$data['shipping_hitshippo_aramex_licence_expires'] = $this->request->post['shipping_hitshippo_aramex_licence_expires'];
		} else {
			$data['shipping_hitshippo_aramex_licence_expires'] = $this->config->get('shipping_hitshippo_aramex_licence_expires');
		}
		if (isset($this->request->post['shipping_hitshippo_aramex_licence_status_licence'])) {
			$data['shipping_hitshippo_aramex_licence_status_licence'] = $this->request->post['shipping_hitshippo_aramex_licence_status_licence'];
		} else {
			$data['shipping_hitshippo_aramex_licence_status_licence'] = $this->config->get('shipping_hitshippo_aramex_licence_status_licence');
		}
		
		//renew 
		if (isset($this->request->post['shipping_hitshippo_aramex_renew_expires'])) {
			$data['shipping_hitshippo_aramex_renew_expires'] = $this->request->post['shipping_hitshippo_aramex_renew_expires'];
		} else {
			$data['shipping_hitshippo_aramex_renew_expires'] = $this->config->get('shipping_hitshippo_aramex_renew_expires');
		}

		if($data['shipping_hitshippo_aramex_licence_expires'])
		{
			$curdate=strtotime(date('d-m-Y',strtotime('now')));
			$expire_date=strtotime( date('d-m-Y', strtotime($data['shipping_hitshippo_aramex_licence_expires']) ) );
			if($expire_date < $curdate)
			{
				if($data['shipping_hitshippo_aramex_renew_expires'])
				{
					$expire_date = $expire_date=strtotime( date('d-m-Y', strtotime($data['shipping_hitshippo_aramex_renew_expires']) ) );
					if($expire_date < $curdate)
					{
						$data['shipping_hitshippo_aramex_renew_status_licence'] = 'Expired';
					}
				}else
				{
					$data['shipping_hitshippo_aramex_renew_status_licence'] = 'Expired';
				}			
			}
			$data['shipping_hitshippo_aramex_licence_expires'] = date('D-M-Y',$expire_date);

		}

		//thilak
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
		$this->response->setOutput($this->load->view('extension/shipping/hitshippo_aramex', $data));
	}
	
	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/shipping/hitshippo_aramex')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['shipping_hitshippo_aramex_key']) {
			$this->error['key'] = $this->language->get('error_key');
		}

		if (!$this->request->post['shipping_hitshippo_aramex_password']) {
			$this->error['password'] = $this->language->get('error_password');
		}

		// if (!$this->request->post['shipping_hitshippo_aramex_account']) {
		// 	$this->error['account'] = $this->language->get('error_account');
		// }

		// if (!$this->request->post['shipping_hitshippo_aramex_postcode']) {
		// 	$this->error['postcode'] = $this->language->get('error_postcode');
		// }

		return !$this->error;
	}
}
