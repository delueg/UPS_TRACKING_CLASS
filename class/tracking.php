<?php
/*
 * Author: Sven Delueg
 * http://delueg.org
 * http://heartcoded.de
 * svendelueg@gmail.com
 *
 * This CLASS is free to use, please keep this commentblock inside. Have fun!*/

class ups_tracking{

	private $str_xml;

	public function __construct(){

		$this->str_xml = "<?xml version=\"1.0\"?>
			<AccessRequest xml:lang='en-US'>
					<AccessLicenseNumber>%%ACCESS%%</AccessLicenseNumber>
					<UserId>%%USER%%</UserId>
					<Password>%%PW%%</Password>
			</AccessRequest>
			<?xml version=\"1.0\"?>
			<TrackRequest>
					<Request>
							<TransactionReference>
									<CustomerContext>
											<InternalKey>bla</InternalKey>
									</CustomerContext>
									<XpciVersion>1.0</XpciVersion>
							</TransactionReference>
							<RequestAction>Track</RequestAction>
						<RequestOption>15</RequestOption>
					</Request>
			<TrackingNumber>%%TRACKING%%</TrackingNumber>
			<ShipperAccountInfo>
			<PostalCode>%%POST%%</PostalCode>
			<CountryCode>%%COUNTRY%%</CountryCode>
			</ShipperAccountInfo>
			</TrackRequest>";

	}

	/*
	 * $arr_user_data
	 * schema
	 *
	 * This is how $arr_user_data has to be, so just change the values to your settings.
	 *
	 * array(
	 	"%%ACCESS%%"	=> "AccessLicenseNumber",
		"%%USER%%"		=> "UserId",
		"%%PW%%"		=> "Password",
		"%%TRACKING%%"	=> "TrackingNumber",
		"%%POST%%"		=> "PostalCode",
		"%%COUNTRY%%"	=> "CountryCode"
	);
	 *
	 * */
	private function set_tracking_xml( $arr_user_data ){
		$str_tracking_xml = $this->str_xml;
		foreach($arr_user_data as $key => $value){
			$str_tracking_xml = str_replace($key,$value,$str_tracking_xml);
		}
		return $str_tracking_xml;
	}

	public function get_raw_tracking_data( $arr_user_data ){
		$ch = curl_init("https://wwwcie.ups.com/ups.app/xml/Track");
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch,CURLOPT_POST,1);
		curl_setopt($ch,CURLOPT_TIMEOUT, 60);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$this->set_tracking_xml($arr_user_data));
		$result = curl_exec ($ch);
		curl_close($ch);
		return $result;
	}

	public function get_tracking_xml( $arr_user_data ){
		return simplexml_load_string(strstr($this->get_raw_tracking_data( $arr_user_data ),'<?'));
	}

	public function get_tracking_array( $arr_user_data ){
		return json_decode(json_encode($this->get_tracking_xml( $arr_user_data )),TRUE);
	}

	public function  get_pre_formatted_tracking_array( $arr_user_data , $str_your_company=false){
		if($str_your_company == false){
			$str_your_company = "UPS";
		}
		$arr_ups_data = $this->get_tracking_array( $arr_user_data );
		if($arr_ups_data['Response']['ResponseStatusCode'] == 0){
			echo "Some error occured!";
			return false;
		}
		$arr_formatted_data = array();

		//it has been delivered
		if($arr_ups_data['Shipment']['Package']['Activity'][0]['ActivityLocation']['SignedForByName']){

			$raw_deliverday = $arr_ups_data['Shipment']['Package']['Activity'][0]['Date'];
			$raw_delivertime = $arr_ups_data['Shipment']['Package']['Activity'][0]['Time'];

			$arr_formatted_data['delivered'] = array(
				"signed_by" 		=> $arr_ups_data['Shipment']['Package']['Activity'][0]['ActivityLocation']['SignedForByName'],
				"signed_where"		=> $arr_ups_data['Shipment']['Package']['Activity'][0]['ActivityLocation']['Description'],
				"signed_year"		=> substr($raw_deliverday,0,4),
				"signed_month"		=> substr($raw_deliverday,4,2),
				"signed_day"		=> substr($raw_deliverday,6,2),
				"signed_hour"		=> substr($raw_delivertime,0,2),
				"signed_minute"		=> substr($raw_delivertime,2,2)
			);
		}

		$loc;

		for($i=1;$i<count($arr_ups_data['Shipment']['Package']['Activity']); $i++){
			if(isset($arr_ups_data['Shipment']['Package']['Activity'][$i]['ActivityLocation']['Address']['City'])){
				$loc = $arr_ups_data['Shipment']['Package']['Activity'][$i]['ActivityLocation']['Address']['City'];
			}else{
				$loc = "";
			}
			$str_date = $arr_ups_data['Shipment']['Package']['Activity'][$i]['Date'];
			$str_time = $arr_ups_data['Shipment']['Package']['Activity'][$i]['Time'];
			$raw_status = $ups_array['Shipment']['Package']['Activity'][$i]['Status']['StatusType']['Description'];

			$arr_formatted_data["activity"][$i-1] = array(
				"location"	=> $loc,
				"year"		=> substr($str_date,0,4),
				"month"		=> substr($str_date,4,2),
				"day"		=> substr($str_date,6,2),
				"hour"		=> substr($str_time,0,2),
				"minute"	=> substr($str_time,2,2),
				"status"	=> str_replace('UPS',$str_your_company,$raw_status)
			);
		}

		return $arr_formatted_data;
	}
}

?>