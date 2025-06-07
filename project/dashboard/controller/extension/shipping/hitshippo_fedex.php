<?php
class ControllerExtensionShippinghitshippofedex extends Controller {
	private $error = array();
	public function install() {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "hitshippo_fedex_details_new` (
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
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "hitshippo_fedex_token` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`token` text NOT NULL,
			`timestamp_created` text NOT NULL,
			`mode` varchar(10),
			PRIMARY KEY (`id`)
		  )");

	}
	public function index() {
		$this->install();
		$this->load->language('extension/shipping/hitshippo_fedex');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');
		
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && isset($_POST['btn_licence_activation'])) {

			if (!$this->request->post['shipping_hitshippo_fedex_licence_licence'] && empty($this->request->post['shipping_hitshippo_fedex_licence_licence'])) {
			return 'Enter Licence Key';
			}
			if (!$this->request->post['shipping_hitshippo_fedex_licence_email'] && empty($this->request->post['shipping_hitshippo_fedex_licence_email'])) {
				return 'Enter Purchased Email Address';
			}

				$licence_key = trim($this->request->post['shipping_hitshippo_fedex_licence_licence']);
				$licence_email = trim($this->request->post['shipping_hitshippo_fedex_licence_email']);
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
									$this->request->post['shipping_hitshippo_fedex_licence_expires'] = $obj->expires;
									$this->request->post['shipping_hitshippo_fedex_licence_status_licence'] = 'Activated';
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
		$this->model_setting_setting->editSetting('shipping_hitshippo_fedex_licence', $this->request->post);
			
		}else if (($this->request->server['REQUEST_METHOD'] == 'POST') && isset($_POST['btn_renew_activation'])) {

			if (!$this->request->post['shipping_hitshippo_fedex_renew_licence'] && empty($this->request->post['shipping_hitshippo_fedex_renew_licence'])) {
				$this->error['warning'] = 'Enter Licence Key.';
			return 'Enter Licence Key';
			}
			if (!$this->request->post['shipping_hitshippo_fedex_renew_email'] && empty($this->request->post['shipping_hitshippo_fedex_renew_email'])) {
				$this->error['warning'] = 'Enter Purchased Email Address.';
				return 'Enter Purchased Email Address';
			}

				$licence_key = trim($this->request->post['shipping_hitshippo_fedex_renew_licence']);
				$licence_email = trim($this->request->post['shipping_hitshippo_fedex_renew_email']);
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
							
							$this->request->post['shipping_hitshippo_fedex_renew_expires'] = $obj->expires;
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
		$this->model_setting_setting->editSetting('shipping_hitshippo_fedex_renew', $this->request->post);
			
		}else if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('shipping_hitshippo_fedex', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true));
		}

		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['rest_key'])) {
			$data['rest_key'] = $this->error['rest_key'];
		} else {
			$data['rest_key'] = '';
		}

		if (isset($this->error['rest_pwd'])) {
			$data['rest_pwd'] = $this->error['rest_pwd'];
		} else {
			$data['rest_pwd'] = '';
		}

		if (isset($this->error['rest_account'])) {
			$data['rest_account'] = $this->error['rest_account'];
		} else {
			$data['rest_account'] = '';
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

		if (isset($this->error['meter'])) {
			$data['error_meter'] = $this->error['meter'];
		} else {
			$data['error_meter'] = '';
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
			'href' => $this->url->link('extension/shipping/hitshippo_fedex', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/shipping/hitshippo_fedex', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true);

		
		if (isset($this->request->post['shipping_hitshippo_fedex_test'])) {
			$data['shipping_hitshippo_fedex_test'] = $this->request->post['shipping_hitshippo_fedex_test'];
		} else {
			$data['shipping_hitshippo_fedex_test'] = $this->config->get('shipping_hitshippo_fedex_test');
		}
		
		if (isset($this->request->post['shipping_hitshippo_fedex_api_type'])) {
			$data['shipping_hitshippo_fedex_api_type'] = $this->request->post['shipping_hitshippo_fedex_api_type'];
		} else {
			if (!empty($this->config->get('shipping_hitshippo_fedex_api_type'))) {
				$data['shipping_hitshippo_fedex_api_type'] = $this->config->get('shipping_hitshippo_fedex_api_type');
			} elseif (empty($this->config->get('shipping_hitshippo_fedex_api_type')) && !empty($this->config->get('shipping_hitshippo_fedex_meter'))) {
				$data['shipping_hitshippo_fedex_api_type'] = "SOAP";
			} else {
				$data['shipping_hitshippo_fedex_api_type'] = "REST";
			}
		}
		
		if (isset($this->request->post['shipping_hitshippo_fedex_rest_api_key'])) {
			$data['shipping_hitshippo_fedex_rest_api_key'] = $this->request->post['shipping_hitshippo_fedex_rest_api_key'];
		} else {
			$data['shipping_hitshippo_fedex_rest_api_key'] = $this->config->get('shipping_hitshippo_fedex_rest_api_key');
		}
		if (isset($this->request->post['shipping_hitshippo_fedex_rest_api_sec'])) {
			$data['shipping_hitshippo_fedex_rest_api_sec'] = $this->request->post['shipping_hitshippo_fedex_rest_api_sec'];
		} else {
			$data['shipping_hitshippo_fedex_rest_api_sec'] = $this->config->get('shipping_hitshippo_fedex_rest_api_sec');
		}
		if (isset($this->request->post['shipping_hitshippo_fedex_rest_acc_num'])) {
			$data['shipping_hitshippo_fedex_rest_acc_num'] = $this->request->post['shipping_hitshippo_fedex_rest_acc_num'];
		} else {
			$data['shipping_hitshippo_fedex_rest_acc_num'] = $this->config->get('shipping_hitshippo_fedex_rest_acc_num');
		}
		if (isset($this->request->post['shipping_hitshippo_fedex_rest_grant_type'])) {
			$data['shipping_hitshippo_fedex_rest_grant_type'] = $this->request->post['shipping_hitshippo_fedex_rest_grant_type'];
		} else {
			$data['shipping_hitshippo_fedex_rest_grant_type'] = $this->config->get('shipping_hitshippo_fedex_rest_grant_type');
		}

		if (isset($this->request->post['shipping_hitshippo_fedex_key'])) {
			$data['shipping_hitshippo_fedex_key'] = $this->request->post['shipping_hitshippo_fedex_key'];
		} else {
			$data['shipping_hitshippo_fedex_key'] = $this->config->get('shipping_hitshippo_fedex_key');
		}

		if (isset($this->request->post['shipping_hitshippo_fedex_password'])) {
			$data['shipping_hitshippo_fedex_password'] = $this->request->post['shipping_hitshippo_fedex_password'];
		} else {
			$data['shipping_hitshippo_fedex_password'] = $this->config->get('shipping_hitshippo_fedex_password');
		}

		if (isset($this->request->post['shipping_hitshippo_fedex_account'])) {
			$data['shipping_hitshippo_fedex_account'] = $this->request->post['shipping_hitshippo_fedex_account'];
		} else {
			$data['shipping_hitshippo_fedex_account'] = $this->config->get('shipping_hitshippo_fedex_account');
		}
		
		if (isset($this->request->post['shipping_hitshippo_fedex_meter'])) {
			$data['shipping_hitshippo_fedex_meter'] = $this->request->post['shipping_hitshippo_fedex_meter'];
		} else {
			$data['shipping_hitshippo_fedex_meter'] = $this->config->get('shipping_hitshippo_fedex_meter');
		}

		if (isset($this->request->post['shipping_hitshippo_fedex_status'])) {
			$data['shipping_hitshippo_fedex_status'] = $this->request->post['shipping_hitshippo_fedex_status'];
		} else {
			$data['shipping_hitshippo_fedex_status'] = $this->config->get('shipping_hitshippo_fedex_status');
		}
		
		if (isset($this->request->post['shipping_hitshippo_fedex_sort_order'])) {
			$data['shipping_hitshippo_fedex_sort_order'] = $this->request->post['shipping_hitshippo_fedex_sort_order'];
		} else {
			$data['shipping_hitshippo_fedex_sort_order'] = $this->config->get('shipping_hitshippo_fedex_sort_order');
		}

		if (isset($this->request->post['shipping_hitshippo_fedex_shipper_name'])) {
			$data['shipping_hitshippo_fedex_shipper_name'] = $this->request->post['shipping_hitshippo_fedex_shipper_name'];
		} else {
			$data['shipping_hitshippo_fedex_shipper_name'] = $this->config->get('shipping_hitshippo_fedex_shipper_name');
		}
		
		if (isset($this->request->post['shipping_hitshippo_fedex_company_name'])) {
			$data['shipping_hitshippo_fedex_company_name'] = $this->request->post['shipping_hitshippo_fedex_company_name'];
		} else {
			$data['shipping_hitshippo_fedex_company_name'] = $this->config->get('shipping_hitshippo_fedex_company_name');
		}
		
		if (isset($this->request->post['shipping_hitshippo_fedex_phone_num'])) {
			$data['shipping_hitshippo_fedex_phone_num'] = $this->request->post['shipping_hitshippo_fedex_phone_num'];
		} else {
			$data['shipping_hitshippo_fedex_phone_num'] = $this->config->get('shipping_hitshippo_fedex_phone_num');
		}
		
		if (isset($this->request->post['shipping_hitshippo_fedex_email_addr'])) {
			$data['shipping_hitshippo_fedex_email_addr'] = $this->request->post['shipping_hitshippo_fedex_email_addr'];
		} else {
			$data['shipping_hitshippo_fedex_email_addr'] = $this->config->get('shipping_hitshippo_fedex_email_addr');
		}
		
		if (isset($this->request->post['shipping_hitshippo_fedex_address1'])) {
			$data['shipping_hitshippo_fedex_address1'] = $this->request->post['shipping_hitshippo_fedex_address1'];
		} else {
			$data['shipping_hitshippo_fedex_address1'] = $this->config->get('shipping_hitshippo_fedex_address1');
		}
		
		if (isset($this->request->post['shipping_hitshippo_fedex_address2'])) {
			$data['shipping_hitshippo_fedex_address2'] = $this->request->post['shipping_hitshippo_fedex_address2'];
		} else {
			$data['shipping_hitshippo_fedex_address2'] = $this->config->get('shipping_hitshippo_fedex_address2');
		}
		
		
		if (isset($this->request->post['shipping_hitshippo_fedex_city'])) {
			$data['shipping_hitshippo_fedex_city'] = $this->request->post['shipping_hitshippo_fedex_city'];
		} else {
			$data['shipping_hitshippo_fedex_city'] = $this->config->get('shipping_hitshippo_fedex_city');
		}
		
		
		if (isset($this->request->post['shipping_hitshippo_fedex_state'])) {
			$data['shipping_hitshippo_fedex_state'] = $this->request->post['shipping_hitshippo_fedex_state'];
		} else {
			$data['shipping_hitshippo_fedex_state'] = $this->config->get('shipping_hitshippo_fedex_state');
		}
		
		
		if (isset($this->request->post['shipping_hitshippo_fedex_country_code'])) {
			$data['shipping_hitshippo_fedex_country_code'] = $this->request->post['shipping_hitshippo_fedex_country_code'];
		} else {
			$data['shipping_hitshippo_fedex_country_code'] = $this->config->get('shipping_hitshippo_fedex_country_code');
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

		
		if (isset($this->request->post['shipping_hitshippo_fedex_postcode'])) {
			$data['shipping_hitshippo_fedex_postcode'] = $this->request->post['shipping_hitshippo_fedex_postcode'];
		} else {
			$data['shipping_hitshippo_fedex_postcode'] = $this->config->get('shipping_hitshippo_fedex_postcode');
		}
		
		if (isset($this->request->post['shipping_hitshippo_fedex_realtime_rates'])) {
			$data['shipping_hitshippo_fedex_realtime_rates'] = $this->request->post['shipping_hitshippo_fedex_realtime_rates'];
		} else {
			$data['shipping_hitshippo_fedex_realtime_rates'] = $this->config->get('shipping_hitshippo_fedex_realtime_rates');
		}

		if (isset($this->request->post['shipping_hitshippo_fedex_residential'])) {
			$data['shipping_hitshippo_fedex_residential'] = $this->request->post['shipping_hitshippo_fedex_residential'];
		} else {
			$data['shipping_hitshippo_fedex_residential'] = $this->config->get('shipping_hitshippo_fedex_residential');
		}

		// if (isset($this->request->post['shipping_hitshippo_fedex_insurance'])) {
		// 	$data['shipping_hitshippo_fedex_insurance'] = $this->request->post['shipping_hitshippo_fedex_insurance'];
		// } else {
		// 	$data['shipping_hitshippo_fedex_insurance'] = $this->config->get('shipping_hitshippo_fedex_insurance');
		// }
		
		if (isset($this->request->post['shipping_hitshippo_fedex_front_end_logs'])) {
			$data['shipping_hitshippo_fedex_front_end_logs'] = $this->request->post['shipping_hitshippo_fedex_front_end_logs'];
		} else {
			$data['shipping_hitshippo_fedex_front_end_logs'] = $this->config->get('shipping_hitshippo_fedex_front_end_logs');
		}
			
		if (isset($this->request->post['shipping_hitshippo_fedex_rate_type'])) {
			$data['shipping_hitshippo_fedex_rate_type'] = $this->request->post['shipping_hitshippo_fedex_rate_type'];
		} else {
			$data['shipping_hitshippo_fedex_rate_type'] = $this->config->get('shipping_hitshippo_fedex_rate_type');
		}
		
		if (isset($this->request->post['shipping_hitshippo_fedex_service'])) {
			$data['shipping_hitshippo_fedex_service'] = $this->request->post['shipping_hitshippo_fedex_service'];
		} elseif ($this->config->has('shipping_hitshippo_fedex_service')) {
			$data['shipping_hitshippo_fedex_service'] = $this->config->get('shipping_hitshippo_fedex_service');
		} else {
			$data['shipping_hitshippo_fedex_service'] = array();
		}

		$data['services'] = array();

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_1'),
			'value' => 'FIRST_OVERNIGHT'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_2'),
			'value' => 'PRIORITY_OVERNIGHT'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_3'),
			'value' => 'STANDARD_OVERNIGHT'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_4'),
			'value' => 'FEDEX_2_DAY_AM'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_5'),
			'value' => 'FEDEX_2_DAY'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_7'),
			'value' => 'SAME_DAY'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_8'),
			'value' => 'SAME_DAY_CITY'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_9'),
			'value' => 'SAME_DAY_METRO_AFTERNOON'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_10'),
			'value' => 'SAME_DAY_METRO_MORNING'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_11'),
			'value' => 'SAME_DAY_METRO_RUSH'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_12'),
			'value' => 'FEDEX_EXPRESS_SAVER'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_13'),
			'value' => 'GROUND_HOME_DELIVERY'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_14'),
			'value' => 'FEDEX_GROUND'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_15'),
			'value' => 'INTERNATIONAL_ECONOMY'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_16'),
			'value' => 'INTERNATIONAL_ECONOMY_DISTRIBUTION'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_17'),
			'value' => 'INTERNATIONAL_FIRST'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_18'),
			'value' => 'INTERNATIONAL_GROUND'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_19'),
			'value' => 'INTERNATIONAL_PRIORITY'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_19'),
			'value' => 'FEDEX_INTERNATIONAL_PRIORITY'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_20'),
			'value' => 'INTERNATIONAL_PRIORITY_DISTRIBUTION'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_21'),
			'value' => 'EUROPE_FIRST_INTERNATIONAL_PRIORITY'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_22'),
			'value' => 'INTERNATIONAL_PRIORITY_EXPRESS'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_23'),
			'value' => 'FEDEX_INTERNATIONAL_PRIORITY_PLUS'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_24'),
			'value' => 'INTERNATIONAL_DISTRIBUTION_FREIGHT'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_25'),
			'value' => 'FEDEX_1_DAY_FREIGHT'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_26'),
			'value' => 'FEDEX_2_DAY_FREIGHT'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_27'),
			'value' => 'FEDEX_3_DAY_FREIGHT'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_28'),
			'value' => 'INTERNATIONAL_ECONOMY_FREIGHT'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_29'),
			'value' => 'INTERNATIONAL_PRIORITY_FREIGHT'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_30'),
			'value' => 'SMART_POST'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_31'),
			'value' => 'FEDEX_FIRST_FREIGHT'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_32'),
			'value' => 'FEDEX_FREIGHT_ECONOMY'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_B'),
			'value' => 'FEDEX_FREIGHT_PRIORITY'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_C'),
			'value' => 'FEDEX_CARGO_AIRPORT_TO_AIRPORT'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_D'),
			'value' => 'FEDEX_CARGO_FREIGHT_FORWARDING'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_E'),
			'value' => 'FEDEX_CARGO_INTERNATIONAL_EXPRESS_FREIGHT'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_F'),
			'value' => 'FEDEX_CARGO_INTERNATIONAL_PREMIUM'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_G'),
			'value' => 'FEDEX_CARGO_MAIL'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_H'),
			'value' => 'FEDEX_CARGO_REGISTERED_MAIL'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_I'),
			'value' => 'FEDEX_CARGO_SURFACE_MAIL'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_J'),
			'value' => 'FEDEX_CUSTOM_CRITICAL_AIR_EXPEDITE_EXCLUSIVE_USE'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_K'),
			'value' => 'FEDEX_CUSTOM_CRITICAL_AIR_EXPEDITE_NETWORK'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_L'),
			'value' => 'FEDEX_CUSTOM_CRITICAL_CHARTER_AIR'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_M'),
			'value' => 'FEDEX_CUSTOM_CRITICAL_POINT_TO_POINT'
		);

		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_N'),
			'value' => 'FEDEX_CUSTOM_CRITICAL_SURFACE_EXPEDITE'
		);
		
		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_O'),
			'value' => 'FEDEX_CUSTOM_CRITICAL_SURFACE_EXPEDITE_EXCLUSIVE_USE'
		);
		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_P'),
			'value' => 'FEDEX_CUSTOM_CRITICAL_TEMP_ASSURE_AIR'
		);
		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_Q'),
			'value' => 'FEDEX_CUSTOM_CRITICAL_TEMP_ASSURE_VALIDATED_AIR'
		);
		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_R'),
			'value' => 'FEDEX_CUSTOM_CRITICAL_WHITE_GLOVE_SERVICES'
		);
		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_S'),
			'value' => 'TRANSBORDER_DISTRIBUTION_CONSOLIDATION'
		);
		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_T'),
			'value' => 'FEDEX_DISTANCE_DEFERRED'
		);
		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_U'),
			'value' => 'FEDEX_NEXT_DAY_EARLY_MORNING'
		);
		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_V'),
			'value' => 'FEDEX_NEXT_DAY_MID_MORNING'
		);
		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_W'),
			'value' => 'FEDEX_NEXT_DAY_AFTERNOON'
		);
		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_X'),
			'value' => 'FEDEX_NEXT_DAY_END_OF_DAY'
		);
		$data['services'][] = array(
			'text'  => $this->language->get('text_fedex_Y'),
			'value' => 'FEDEX_NEXT_DAY_FREIGHT'
		);
		
		if (isset($this->request->post['shipping_hitshippo_fedex_weight'])) {
			$data['shipping_hitshippo_fedex_weight'] = $this->request->post['shipping_hitshippo_fedex_weight'];
		} else {
			$data['shipping_hitshippo_fedex_weight'] = $this->config->get('shipping_hitshippo_fedex_weight');
		}
		
		if (isset($this->request->post['shipping_hitshippo_fedex_packing_type'])) {
			$data['shipping_hitshippo_fedex_packing_type'] = $this->request->post['shipping_hitshippo_fedex_packing_type'];
		} else {
			$data['shipping_hitshippo_fedex_packing_type'] = $this->config->get('shipping_hitshippo_fedex_packing_type');
		}
		
		// if (isset($this->request->post['shipping_hitshippo_fedex_per_item'])) {
		// 	$data['shipping_hitshippo_fedex_per_item'] = $this->request->post['shipping_hitshippo_fedex_per_item'];
		// } else {
		// 	$data['shipping_hitshippo_fedex_per_item'] = $this->config->get('shipping_hitshippo_fedex_per_item');
		// }
		
		
			
		if (isset($this->request->post['shipping_hitshippo_fedex_wight_b'])) {
			$data['shipping_hitshippo_fedex_wight_b'] = $this->request->post['shipping_hitshippo_fedex_wight_b'];
		} else {
			$data['shipping_hitshippo_fedex_wight_b'] = $this->config->get('shipping_hitshippo_fedex_wight_b');
		}
		
				
		// if (isset($this->request->post['shipping_hitshippo_fedex_weight_c'])) {
		// 	$data['shipping_hitshippo_fedex_weight_c'] = $this->request->post['shipping_hitshippo_fedex_weight_c'];
		// } else {
		// 	$data['shipping_hitshippo_fedex_weight_c'] = $this->config->get('shipping_hitshippo_fedex_weight_c');
		// }
		
		if (isset($this->request->post['shipping_hitshippo_fedex_int_key'])) {
			$data['shipping_hitshippo_fedex_int_key'] = $this->request->post['shipping_hitshippo_fedex_int_key'];
		} else {
			$data['shipping_hitshippo_fedex_int_key'] = $this->config->get('shipping_hitshippo_fedex_int_key');
		}
		if (isset($this->request->post['shipping_hitshippo_fedex_auto_label'])) {
			$data['shipping_hitshippo_fedex_auto_label'] = $this->request->post['shipping_hitshippo_fedex_auto_label'];
		} else {
			$data['shipping_hitshippo_fedex_auto_label'] = $this->config->get('shipping_hitshippo_fedex_auto_label');
		}
		if (isset($this->request->post['shipping_hitshippo_fedex_auto_status'])) {
			$data['shipping_hitshippo_fedex_auto_status'] = $this->request->post['shipping_hitshippo_fedex_auto_status'];
		} else {
			$data['shipping_hitshippo_fedex_auto_status'] = $this->config->get('shipping_hitshippo_fedex_auto_status');
		}
		if (isset($this->request->post['shipping_hitshippo_fedex_send_mail_to'])) {
			$data['shipping_hitshippo_fedex_send_mail_to'] = $this->request->post['shipping_hitshippo_fedex_send_mail_to'];
		} else {
			$data['shipping_hitshippo_fedex_send_mail_to'] = $this->config->get('shipping_hitshippo_fedex_send_mail_to');
		}
				
		if (isset($this->request->post['shipping_hitshippo_fedex_dropoff_type'])) {
			$data['shipping_hitshippo_fedex_dropoff_type'] = $this->request->post['shipping_hitshippo_fedex_dropoff_type'];
		} else {
			$data['shipping_hitshippo_fedex_dropoff_type'] = $this->config->get('shipping_hitshippo_fedex_dropoff_type');
		}
		
		if (isset($this->request->post['shipping_hitshippo_fedex_print_size'])) {
			$data['shipping_hitshippo_fedex_print_size'] = $this->request->post['shipping_hitshippo_fedex_print_size'];
		} else {
			$data['shipping_hitshippo_fedex_print_size'] = $this->config->get('shipping_hitshippo_fedex_print_size');
		}
		
		if (isset($this->request->post['shipping_hitshippo_fedex_fedpack_type'])) {
			$data['shipping_hitshippo_fedex_fedpack_type'] = $this->request->post['shipping_hitshippo_fedex_fedpack_type'];
		} else {
			$data['shipping_hitshippo_fedex_fedpack_type'] = $this->config->get('shipping_hitshippo_fedex_fedpack_type');
		}
		if (isset($this->request->post['shipping_hitshippo_fedex_print_type'])) {
			$data['shipping_hitshippo_fedex_print_type'] = $this->request->post['shipping_hitshippo_fedex_print_type'];
		} else {
			$data['shipping_hitshippo_fedex_print_type'] = $this->config->get('shipping_hitshippo_fedex_print_type');
		}
		
		if (isset($this->request->post['shipping_hitshippo_fedex_shipment_content'])) {
			$data['shipping_hitshippo_fedex_shipment_content'] = $this->request->post['shipping_hitshippo_fedex_shipment_content'];
		} else {
			$data['shipping_hitshippo_fedex_shipment_content'] = $this->config->get('shipping_hitshippo_fedex_shipment_content');
		}

		if (isset($this->request->post['shipping_hitshippo_fedex_language'])) {
			$data['shipping_hitshippo_fedex_language'] = $this->request->post['shipping_hitshippo_fedex_language'];
		} else {
			$data['shipping_hitshippo_fedex_language'] = $this->config->get('shipping_hitshippo_fedex_language');
		}

		if (isset($this->request->post['shipping_hitshippo_fedex_ETD_check'])) {
			$data['shipping_hitshippo_fedex_ETD_check'] = $this->request->post['shipping_hitshippo_fedex_ETD_check'];
		} else {
			$data['shipping_hitshippo_fedex_ETD_check'] = $this->config->get('shipping_hitshippo_fedex_ETD_check');
		}

		if (isset($this->request->post['shipping_hitshippo_fedex_shipping_document_types'])) {
			$data['shipping_hitshippo_fedex_shipping_document_types'] = $this->request->post['shipping_hitshippo_fedex_shipping_document_types'];
		} else {
			$data['shipping_hitshippo_fedex_shipping_document_types'] = $this->config->get('shipping_hitshippo_fedex_shipping_document_types');
		}

		if (isset($this->request->post['shipping_hitshippo_fedex_addcomment_check'])) {
			$data['shipping_hitshippo_fedex_addcomment_check'] = $this->request->post['shipping_hitshippo_fedex_addcomment_check'];
		} else {
			$data['shipping_hitshippo_fedex_addcomment_check'] = $this->config->get('shipping_hitshippo_fedex_addcomment_check');
		}

		if (isset($this->request->post['shipping_hitshippo_fedex_addcomment_box'])) {
			$data['shipping_hitshippo_fedex_addcomment_box'] = $this->request->post['shipping_hitshippo_fedex_addcomment_box'];
		}elseif(empty($this->config->get('shipping_hitshippo_fedex_addcomment_box'))){
			$data['shipping_hitshippo_fedex_addcomment_box'] = "Shipment created for your order: #{order number} . Track the shipment here https://www.fedex.com/fedextrack/?trknbr={tracking number}";
		} else {
			$data['shipping_hitshippo_fedex_addcomment_box'] = $this->config->get('shipping_hitshippo_fedex_addcomment_box');
		}
		
		// 	if (isset($this->request->post['shipping_hitshippo_fedex_picper'])) {
		// 	$data['shipping_hitshippo_fedex_picper'] = $this->request->post['shipping_hitshippo_fedex_picper'];
		// } else {
		// 	$data['shipping_hitshippo_fedex_picper'] = $this->config->get('shipping_hitshippo_fedex_picper');
		// }
		// 	if (isset($this->request->post['shipping_hitshippo_fedex_piccon'])) {
		// 	$data['shipping_hitshippo_fedex_piccon'] = $this->request->post['shipping_hitshippo_fedex_piccon'];
		// } else {
		// 	$data['shipping_hitshippo_fedex_piccon'] = $this->config->get('shipping_hitshippo_fedex_piccon');
		// }
		// 	if (isset($this->request->post['shipping_hitshippo_fedex_pickup_time'])) {
		// 	$data['shipping_hitshippo_fedex_pickup_time'] = $this->request->post['shipping_hitshippo_fedex_pickup_time'];
		// } else {
		// 	$data['shipping_hitshippo_fedex_pickup_time'] = $this->config->get('shipping_hitshippo_fedex_pickup_time');
		// }
		// 	if (isset($this->request->post['shipping_hitshippo_fedex_close_time'])) {
		// 	$data['shipping_hitshippo_fedex_close_time'] = $this->request->post['shipping_hitshippo_fedex_close_time'];
		// } else {
		// 	$data['shipping_hitshippo_fedex_close_time'] = $this->config->get('shipping_hitshippo_fedex_close_time');
		// }

		//licence
		if (isset($this->request->post['shipping_hitshippo_fedex_licence_licence'])) {
			$data['shipping_hitshippo_fedex_licence_licence'] = $this->request->post['shipping_hitshippo_fedex_licence_licence'];
		} else {
			$data['shipping_hitshippo_fedex_licence_licence'] = $this->config->get('shipping_hitshippo_fedex_licence_licence');
		}
		if (isset($this->request->post['shipping_hitshippo_fedex_licence_email'])) {
			$data['shipping_hitshippo_fedex_licence_email'] = $this->request->post['shipping_hitshippo_fedex_licence_email'];
		} else {
			$data['shipping_hitshippo_fedex_licence_email'] = $this->config->get('shipping_hitshippo_fedex_licence_email');
		}
		if (isset($this->request->post['shipping_hitshippo_fedex_licence_expires'])) {
			$data['shipping_hitshippo_fedex_licence_expires'] = $this->request->post['shipping_hitshippo_fedex_licence_expires'];
		} else {
			$data['shipping_hitshippo_fedex_licence_expires'] = $this->config->get('shipping_hitshippo_fedex_licence_expires');
		}
		if (isset($this->request->post['shipping_hitshippo_fedex_licence_status_licence'])) {
			$data['shipping_hitshippo_fedex_licence_status_licence'] = $this->request->post['shipping_hitshippo_fedex_licence_status_licence'];
		} else {
			$data['shipping_hitshippo_fedex_licence_status_licence'] = $this->config->get('shipping_hitshippo_fedex_licence_status_licence');
		}
		
		//renew 
		if (isset($this->request->post['shipping_hitshippo_fedex_renew_expires'])) {
			$data['shipping_hitshippo_fedex_renew_expires'] = $this->request->post['shipping_hitshippo_fedex_renew_expires'];
		} else {
			$data['shipping_hitshippo_fedex_renew_expires'] = $this->config->get('shipping_hitshippo_fedex_renew_expires');
		}

		if($data['shipping_hitshippo_fedex_licence_expires'])
		{
			$curdate=strtotime(date('d-m-Y',strtotime('now')));
			$expire_date=strtotime( date('d-m-Y', strtotime($data['shipping_hitshippo_fedex_licence_expires']) ) );
			if($expire_date < $curdate)
			{
				if($data['shipping_hitshippo_fedex_renew_expires'])
				{
					$expire_date = $expire_date=strtotime( date('d-m-Y', strtotime($data['shipping_hitshippo_fedex_renew_expires']) ) );
					if($expire_date < $curdate)
					{
						$data['shipping_hitshippo_fedex_renew_status_licence'] = 'Expired';
					}
				}else
				{
					$data['shipping_hitshippo_fedex_renew_status_licence'] = 'Expired';
				}			
			}
			$data['shipping_hitshippo_fedex_licence_expires'] = date('D-M-Y',$expire_date);

		}

		//thilak
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
		$this->response->setOutput($this->load->view('extension/shipping/hitshippo_fedex', $data));
	}
	
	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/shipping/hitshippo_fedex')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (isset($this->request->post['shipping_hitshippo_fedex_api_type']) && $this->request->post['shipping_hitshippo_fedex_api_type'] == "REST") {
			if (!$this->request->post['shipping_hitshippo_fedex_rest_api_key']) {
				$this->error['rest_key'] = $this->language->get('error_rest_api_key');
			}

			if (!$this->request->post['shipping_hitshippo_fedex_rest_api_sec']) {
				$this->error['rest_pwd'] = $this->language->get('error_rest_api_sec');
			}

			if (!$this->request->post['shipping_hitshippo_fedex_rest_acc_num']) {
				$this->error['rest_account'] = $this->language->get('error_rest_acc_num');
			}
		} else {
			if (!$this->request->post['shipping_hitshippo_fedex_key']) {
				$this->error['key'] = $this->language->get('error_key');
			}

			if (!$this->request->post['shipping_hitshippo_fedex_password']) {
				$this->error['password'] = $this->language->get('error_password');
			}

			if (!$this->request->post['shipping_hitshippo_fedex_account']) {
				$this->error['account'] = $this->language->get('error_account');
			}

			if (!$this->request->post['shipping_hitshippo_fedex_meter']) {
				$this->error['meter'] = $this->language->get('error_meter');
			}
		}

		if (!$this->request->post['shipping_hitshippo_fedex_postcode']) {
			$this->error['postcode'] = $this->language->get('error_postcode');
		}

		return !$this->error;
	}
}
