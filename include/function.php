<?php
	function tt($v){
		echo "<xmp>";
		print_R($v);
		echo "</xmp><br>";
	}
	function enter_del($val){
		$text = preg_replace('/\r\n|\r|\n/','',$val);
		return $text;
	}
	// ip 틀린경우가 발생
	/*
		하지만 경우에 따라서는 사용자의 IP주소를 올바르게 가져오지 못하는 경우가 있는데요, 
		예를 들면 사용자가 프록시 서버를 경유해 특정 웹사이트로 접근하면 프록시 서버에 의해 사용자의 실제 IP주소를 숨길 수 있기 때문입니다.
		그런데 이러한 경우에도, 다른 방법을 통해 실제 사용자의 IP주소를 알아낼 수 있습니다.
		웹사이트에 접근할 때, 여러 가지 헤더정보를 넘겨 주게 되는데, 거기에 원래(실제) 사용자의 IP주소도 같이 넘겨 받게 됩니다. 
		그 메소드가 "X-Forwarded-For"이고, PHP에서는 "HTTP_X_FORWARDED_FOR" 변수에 저장됩니다.
		그러므로 HTTP_X_FORWARDED_FOR 변수로 비교 체크하여 불량IP주소를 어느정도 걸러낼 수 있습니다.
		참고 : HTTP_X_FORWARDED_FOR 는 때로는 내부IP주소 또는 로컬IP주소를 표시하는 경우가 있고, 여러 IP주소일 경우에는 콤마(,)로 구분되어 표시됩니다.

		참고 사이트 : https://www.phpinfo.kr/entry/PHP-%EC%8B%A4%EC%A0%9C-%ED%81%B4%EB%9D%BC%EC%9D%B4%EC%96%B8%ED%8A%B8-IP%EC%A3%BC%EC%86%8C-%EA%B0%80%EC%A0%B8%EC%98%A4%EA%B8%B0
	*/
	function get_real_client_ip() {
		$ipaddress = '';
		if(isset($_SERVER['HTTP_CLIENT_IP']) ) {
			$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
		} else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) ) {
			$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else if(isset($_SERVER['HTTP_X_FORWARDED']) ) {
			$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
		} else if(isset($_SERVER['HTTP_FORWARDED_FOR']) ) {
			$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
		} else if(isset($_SERVER['HTTP_FORWARDED']) ) {
			$ipaddress = $_SERVER['HTTP_FORWARDED'];
		} else if(isset($_SERVER['REMOTE_ADDR']) ) {
			$ipaddress = $_SERVER['REMOTE_ADDR'];
		} else {
			$ipaddress = '';
		}

		if ( strpos($ipaddress,",") !== false ){
			$ipaddress = explode(",",$ipaddress)[0];
		}
		return $ipaddress;
	}

	// url 퓨니코드 체크 및 치환
	function url_puny_code_conv($url){
		// 주소중 ui4j ( 자동라이브러리 ) 를 사용한곳때문에 치환
		$url			= preg_replace("/ui4j-[^>]*:http/si", "http", $url);
		$temp_url		= explode("?",$url);
		$url_domain		= $temp_url[0];
		$url_parse		= parse_url($url_domain);
		$scheme			= "";
		if ( isset($url_parse['scheme']) ) {
			$scheme = "{$url_parse['scheme']}://";
			$url_domain			= str_replace($scheme, "", $url_domain);
		}

		if (extension_loaded('intl')){
			$url_domain = idn_to_ascii($url_domain);
		}

		if ( $scheme != '' ) $url_domain = $scheme . $url_domain;
		$conv_url = $url_domain . ( count($temp_url) > 1  ?  "?" . $temp_url[1] : "" ) ;
		return $conv_url;
	}

	// visit_id 생성
	function set_visit_id($visit_id  , $adkey , $mi_ip , $decrypt_visit_id , $key ){
		$result = array();

		if ( strlen($visit_id) < 10  ) $visit_id ="" ;
		if ( $decrypt_visit_id != '' ) {
			$arr_visit_info = explode("_" , $decrypt_visit_id);
			if ( count($arr_visit_info) == 3 ) {
				if ( $arr_visit_info[1] != $adkey ) $visit_id ="" ;
			}else{
				$visit_id ="" ;
			}
		}

		// 기간 일시적으로 visit_id 에 . 이 포함 되어 있으면 재발급
		if ( strpos($visit_id , "." ) !== false ) $visit_id ="" ;

		if($visit_id =="" || $visit_id == "undefined") {
			$logtime = date('dHis');
			//ip 에서 . => - 치환
			$encrypt_str = str_replace("." , "-" , $mi_ip)."_".$adkey."_".$logtime;
			$encrypt_str = substr($encrypt_str,0,50);
			$visit_id = encrypt($encrypt_str,$key); 
		}
		return $visit_id;
	}

	// visit_id 랜던 키
	function randomKey($length){
		$ip = get_real_client_ip();
		$rand = rand();
		$time = time() ;
		$str = "ABCDEFGHIJKLMNOPQRTSUVWXYZ";
		$md5 = md5($ip. $rand.$time );
		$str_shuffle = str_shuffle($md5 . $str  );
		$result =substr($str_shuffle, 0, $length);
		return $result;
	}

	//복호화 decrypt 함수
	function decrypt($key2,$secret , $is_adkey = false ) {
		$encrypt_these_chars = "1234567890ABCDEFGHIJKLMNOPQRTSUVWXYZabcdefghijklmnopqrstuvwxyz-_";
		if($is_adkey){
			$encrypt_these_chars = "1234567890ABCDEFGHIJKLMNOPQRTSUVWXYZabcdefghijklmnopqrstuvwxyz";
		}
		$input		= $key2;
		$output		= "";
		$debug		= "";
		$k			= $secret;
		$t			= $input;
		$result;
		$ki;
		$ti;
		$keylength	= strlen($k);
		$textlength = strlen($t);
		$modulo		= strlen($encrypt_these_chars);
		$dbg_key;
		$dbg_inp;
		$dbg_sum;
		for ($result = "", $ki = $ti = 0; $ti < $textlength; $ti++, $ki++){
			if ($ki >= $keylength){
				$ki = 0;
			}
			$c = strpos($encrypt_these_chars, substr($t, $ti,1));
			if ($c >= 0) {
				$c = ($c - strpos($encrypt_these_chars , substr($k, $ki,1)) + $modulo) % $modulo;
				$result .= substr($encrypt_these_chars , $c, 1);
			} else {
				$result += substr($t, $ti,1);
			}
		}
		return $result;
	}

	//암호화 함수
	function encrypt($data,$k) { 
		//$encrypt_these_chars = "1234567890ABCDEFGHIJKLMNOPQRTSUVWXYZabcdefghijklmnopqrstuvwxyz.,/?!$@^*()_+-=:;~{}";
		$encrypt_these_chars = "1234567890ABCDEFGHIJKLMNOPQRTSUVWXYZabcdefghijklmnopqrstuvwxyz-_";
		$t = $data;
		$result2;
		$ki;
		$ti;
		$keylength = strlen($k);
		$textlength = strlen($t);
		$modulo = strlen($encrypt_these_chars);
		$dbg_key;
		$dbg_inp;
		$dbg_sum;
		for ($result2 = "", $ki = $ti = 0; $ti < $textlength; $ti++, $ki++) {
			if ($ki >= $keylength) {
				$ki = 0;
			}
			@$dbg_inp += "["+$ti+"]="+strpos($encrypt_these_chars, substr($t, $ti,1))+" ";   
			@$dbg_key += "["+$ki+"]="+strpos($encrypt_these_chars, substr($k, $ki,1))+" ";   
			@$dbg_sum += "["+$ti+"]="+strpos($encrypt_these_chars, substr($k, $ki,1))+ strpos($encrypt_these_chars, substr($t, $ti,1)) % $modulo +" ";
			$c = strpos($encrypt_these_chars, substr($t, $ti,1));
			$d;
			$e;
			if ($c >= 0) {
				$c = ($c + strpos($encrypt_these_chars, substr($k, $ki,1))) % $modulo;
				$d = substr($encrypt_these_chars, $c,1);
				$e .= $d;
			} else {
				$e += $t.substr($ti,1);
			}
		}
		$key2 = $result2;
		@$debug = "Key  : "+$k+"\n"+"Input: "+$t+"\n"+"Key  : "+$dbg_key+"\n"+"Input: "+$dbg_inp+"\n"+"Enc  : "+$dbg_sum;
		return $e . "";
	}


	// 전화번호 하이프 (-) 추가
	function add_hyphen($tel){
		$tel = preg_replace("/[^0-9]/", "", $tel);    // 숫자 이외 제거
		if (substr($tel,0,2)=='02')
			return preg_replace("/([0-9]{2})([0-9]{3,4})([0-9]{4})$/", "\\1-\\2-\\3", $tel);
		else if (strlen($tel)=='8' && (substr($tel,0,2)=='15' || substr($tel,0,2)=='16' || substr($tel,0,2)=='18'))
			// 지능망 번호이면
			return preg_replace("/([0-9]{4})([0-9]{4})$/", "\\1-\\2", $tel);
		else
			return preg_replace("/([0-9]{3})([0-9]{3,4})([0-9]{4})$/", "\\1-\\2-\\3", $tel);
	}

	// 유입 매체 , 키워드 수집
	function get_inflow($visit_info){
        
		$url			= $visit_info['u'];
		$url_domain		= $visit_info['u_domain'];
		$ref_url		= $visit_info['ru'];
		$ref_url_domain	= $visit_info['ru_domain'];
		$ref_url_path	= "";

		$result = array( 
			'keyword' => "" 
			, 'is_ad'			=> 0
			, 'is_engine'		=> 0
			, 'site_name'		=> "" 
			, 'site_type'		=> 0 
			, 'site_group'		=> 0
			, 'site_engine'		=> 0
			, 'site_social'		=> 0
			, 'site_ad'			=> 0
			, 'n_rank'			=> "" 
			, 'n_campaign_type'	=> "" 
		);

		// 추적 URL
		$check_url_array = array();
		if( strpos($url, "?") !== false ){
			$url_array = explode("&",explode("?",$url)[1]);
			if ( count($url_array) ) {
				foreach($url_array as $val) {
					$v = explode("=",$val);
					$check_url_array[$v[0]] = $v[1];
				}
			}
		}
		
		// 레퍼러 URL
		$check_ref_url_array = array();
		if( strpos($ref_url, "?") !== false ){

			$tmp_domain = explode("?",$ref_url);
			$ref_url_path = $tmp_domain[0];
			$tmp_ref_domain = parse_url($tmp_domain[0]);

			$ref_url_array = explode("&",$tmp_domain[1]);
			if ( count($ref_url_array) ) {
				foreach($ref_url_array as $val) {
					$v = explode("=",$val);
					$check_ref_url_array[$v[0]] = $v[1];
				}
			}
		}

		// 광고 키워드 로 유입
		if( !empty($check_url_array['n_keyword']) || !empty($check_url_array['NVKWD']) || !empty($check_url_array['DMKW']) || !empty($check_url_array['gclid']) || !empty($check_url_array['k_keyword']) || ( !empty($check_url_array['n_campaign']) &&  empty($check_url_array['n_keyword']) &&  !empty($check_url_array['n_query']) ) ) {

			// 네이버 광고
			if( !empty($check_url_array['n_keyword']) || !empty($check_url_array['NVKWD']) || ( !empty($check_url_array['n_campaign']) &&  empty($check_url_array['n_keyword']) &&  !empty($check_url_array['n_query']) )  ) {
				$result['site_name']	= '네이버';
				$result['site_type']	= 1;
				$result['site_group']	= 20;
				$result['site_engine']	= 13;
				$result['site_social']	= 0;
				$result['site_ad']		= 5;


				$result['is_ad']		= 1;
				$result['ad_type']		= 'ad';

				if( !empty($check_url_array['n_keyword']) ){
					$result['keyword']		= urldecode($check_url_array['n_keyword']);
				}else if ( !empty($check_url_array['NVKWD'])  ) {
					$result['keyword']		= urldecode($check_url_array['NVKWD']);
				}else  {
					$result['keyword']		= urldecode($check_url_array['n_query']);
				}


				$result['n_campaign_type']	= urldecode($check_url_array['n_campaign_type']);
				$result['n_rank']			= urldecode($check_url_array['n_rank']);
			}
			// 다음광고
			else if( !empty($check_url_array['k_keyword']) || !empty($check_url_array['DMKW']) ){
				$result['site_name']	= '다음';
				$result['site_type']	= 2;
				$result['site_group']	= 20;
				$result['site_engine']	= 14;
				$result['site_social']	= 0;
				$result['site_ad']		= 6;

				$result['is_ad']		= 1;
				$result['ad_type']		= 'ad';
				if( !empty($check_url_array['k_keyword']) ){
					$result['keyword']		= urldecode($check_url_array['k_keyword']);
				}else{
					$result['keyword']		= urldecode($check_url_array['DMKW']);
				}
			}
			// 구글 광고
			else if( !empty($check_url_array['gclid']) ){
				$result['site_name']	= '구글';
				$result['site_type']	= 3;
				$result['site_group']	= 20;
				$result['site_engine']	= 15;
				$result['site_social']	= 0;
				$result['site_ad']		= 7;
				$result['is_ad']		= 1;
				$result['ad_type']		= 'ad';
				$result['keyword']		= urldecode($check_url_array['gclid']);
			}
			

		// 검색 키워드 로 유입
		}else{
			// 네이버
			if( strpos($ref_url_domain, "naver.com") !== false ){
				$result['site_type']		= 1;

				// 네이버 블로그
				if( strpos($ref_url_domain, "blog.naver.com") !== false ){
					$result['site_group']	= 1;

				// 네이버 카페
				} else if( strpos($ref_url_domain, "cafe.naver.com") !== false ){
					$result['site_group'] = 2;

				// 블로그 , 카페 제외한 네이버
				}else {
					// 광고
					if( !empty($check_url_array['n_keyword']) || !empty($check_url_array['NVKWD']) || ( !empty($check_url_array['n_campaign']) &&  empty($check_url_array['n_keyword']) &&  !empty($check_url_array['n_query']) ) ){
						$result['site_name']	= '네이버';
						$result['site_group']	= 20;
						$result['site_engine']	= 13;
						$result['site_social']	= 0;
						$result['site_ad']		= 5;


						$result['is_ad']		= 1;
						$result['ad_type']		= 'ad';

						if( !empty($check_url_array['n_keyword']) ){
							$result['keyword']		= urldecode($check_url_array['n_keyword']);
						}else if ( !empty($check_url_array['NVKWD'])  ) {
							$result['keyword']		= urldecode($check_url_array['NVKWD']);
						}else  {
							$result['keyword']		= urldecode($check_url_array['n_query']);
						}


						$result['n_campaign_type']	= urldecode($check_url_array['n_campaign_type']);
						$result['n_rank']			= urldecode($check_url_array['n_rank']);
					}
					// 검색
					else{
						$result['site_name']	= '네이버';
						$result['site_group']	= 21;
						$result['site_engine']	= 13;
						$result['site_social']	= 0;
						$result['site_ad']		= 0;

						$result['is_ad']		= 0;
						$result['keyword']		= urldecode($check_ref_url_array['query']);
					}
				}

			// 다음, 네이트
			} else if( strpos($ref_url_domain, "daum.net") !== false ){

				// 다음 블로그
				if( strpos($ref_url_domain, "blog.daum.net") !== false ){
					$result['site_type'] = 2;
					$result['site_group'] = 1;

				// 다음 카페
				} else if( strpos($ref_url_domain, "cafe.daum.net") !== false ){
					$result['site_type'] = 2;
					$result['site_group'] = 2;
				} else{

					$nate_check = substr($ref_url_path,-4);
					// 네이트
					if( $nate_check  == "nate" ){
						$result['site_name'] = '네이트';
						$result['site_type'] = 4;
						$result['site_engine']	= 16;
						$result['site_social']	= 0;

						// 광고
						if( !empty($check_url_array['k_keyword']) || !empty($check_url_array['DMKW']) ){
							$result['site_group']	= 20;
							$result['site_ad']		= 6;
							$result['is_ad']		= 1;
							$result['ad_type']		= 'ad';
							if( !empty($check_url_array['k_keyword']) ){
								$result['keyword']		= urldecode($check_url_array['k_keyword']);
							}else{
								$result['keyword']		= urldecode($check_url_array['DMKW']);
							}
							$result['n_rank']			= urldecode($check_url_array['k_rank']);
						}
						// 검색
						else{
							$result['site_group']	= 21;
							$result['site_ad']		= 0;
							$result['is_ad']		= 0;
							$result['keyword']		= urldecode($check_ref_url_array['q']);
						}

					// 다음
					}else{
						$result['site_name'] = '다음';
						$result['site_type'] = 2;
						$result['site_engine']	= 14;
						$result['site_social']	= 0;

						// 광고
						if( !empty($check_url_array['k_keyword']) || !empty($check_url_array['DMKW']) ){
							$result['site_group']	= 20;
							$result['site_ad']		= 6;
							$result['is_ad']		= 1;
							$result['ad_type']		= 'ad';
							if( !empty($check_url_array['k_keyword']) ){
								$result['keyword']		= urldecode($check_url_array['k_keyword']);
							}else{
								$result['keyword']		= urldecode($check_url_array['DMKW']);
							}
						}
						// 검색
						else{
							$result['site_group']	= 21;
							$result['site_ad']		= 0;
							$result['is_ad']		= 0;
							$result['keyword']		= urldecode($check_ref_url_array['q']);
						}
					}
				}
			// 구글
			} else if( strpos($ref_url_domain, "google.co.kr") !== false || strpos($ref_url_domain, "google.com") !== false ){
				$result['site_type']	= 3;
				$result['site_name']	= '구글';
				$result['site_engine']	= 15;
				$result['site_social']	= 0;

				// 광고
				if( !empty($check_url_array['gclid']) ){
					$result['site_group']	= 20;
					$result['site_ad']		= 7;
					$result['is_ad']		= 1;
					$result['ad_type']		= 'ad';
					$result['keyword']		= urldecode($check_url_array['gclid']);
				}else{
					$result['site_group']	= 21;
					$result['site_ad']		= 0;
					$result['is_ad']		= 0;
					$result['keyword']		= urldecode($check_ref_url_array['q']);
				}

			// 카카오
			} else if( strpos($ref_url_domain, "kakao.com") !== false ){
				$result['site_type']		= 5;
				$result['site_engine']	= 0;
				$result['site_group']	= 0;
				$result['site_ad']		= 0;

				// 카카오 스토리
				if( strpos($ref_url_domain, "story.kakao.com") !== false ){
					$result['site_group']	= 3;
					$result['site_social']	= 12;
				}

			// 줌
			} else if( strpos($ref_url_domain, "zum.com") !== false ){
				$result['site_name']	= '줌';
				$result['site_type']	= 6;
				$result['site_engine']	= 18;
				$result['site_social']	= 0;

				// 광고
				if( !empty($check_url_array['n_keyword']) || !empty($check_url_array['NVKWD']) || ( !empty($check_url_array['n_campaign']) &&  empty($check_url_array['n_keyword']) &&  !empty($check_url_array['n_query']) ) ){
					$result['site_group']	= 20;
					$result['site_ad']		= 5;

					if( !empty($check_url_array['n_keyword']) ){
						$result['keyword'] = urldecode($check_url_array['n_keyword']);
						$result['is_ad'] = 1;
					}else if ( !empty($check_url_array['NVKWD'])  ) {
						$result['keyword'] = urldecode($check_url_array['NVKWD']);
						$result['is_ad'] = 1;
					}else  {
						$result['keyword'] = urldecode($check_url_array['n_query']);
						$result['is_ad'] = 1;
					}

					$result['n_campaign_type']	= urldecode($check_url_array['n_campaign_type']);
					$result['n_rank']			= urldecode($check_url_array['n_rank']);
				}
				// 검색
				else{
					$result['site_group']	= 21;
					$result['site_ad']		= 0;
					$result['keyword'] = urldecode($check_ref_url_array['query']);
					$result['is_ad'] = 0;
				}

			// bing
			} else if( strpos($ref_url_domain, "bing.com") !== false ){
				$result['site_name'] = '빙';
				$result['site_type'] = 7;
				$result['site_engine']	= 17;
				$result['site_social']	= 0;

				// 광고
				if( !empty($check_url_array['DMCOL']) ){
					$result['keyword'] = urldecode($check_url_array['DMKW']);
					$result['is_ad'] = 1;
					$result['site_group']	= 20;
					$result['site_ad']		= 5;
				}else if(!empty($check_url_array['k_media'])){
					$result['keyword'] = urldecode($check_url_array['k_keyword']);
					$result['is_ad'] = 1;
					$result['site_group']	= 20;
					$result['site_ad']		= 5;
				}
				// 검색
				else{
					$result['keyword'] = urldecode($check_ref_url_array['q']);
					$result['is_ad'] = 0;
					$result['site_group']	= 21;
					$result['site_ad']		= 0;
				}

			// 11번가
			} else if( strpos($ref_url_domain, "11st.co.kr") !== false ){
				$result['site_type']		= 8;
				$result['site_name']		= '11번가';
				$result['site_group']	= 22;
				$result['site_ad']		= 5;
				$result['keyword']			= urldecode($check_url_array['n_keyword']);
				$result['ad_type']			= 'ad';
				$result['n_campaign_type']	= urldecode($check_url_array['n_campaign_type']);
				$result['n_rank']			= urldecode($check_url_array['n_rank']);

			// 옥션
			} else if( strpos($ref_url_domain, "auction.co.kr") !== false ){
				$result['site_type']		= 9;
				$result['site_name']		= '옥션';

				$result['site_group']	= 22;
				$result['site_ad']		= 5;

				$result['keyword']			= urldecode($check_url_array['n_keyword']);
				$result['ad_type']			= 'ad';
				$result['n_campaign_type']	= urldecode($check_url_array['n_campaign_type']);
				$result['n_rank']			= urldecode($check_url_array['n_rank']);

			// 지마켓
			} else if( strpos($ref_url_domain, "gmarket.co.kr") !== false ){
				$result['site_type']		= 10;
				$result['site_name']		= '지마켓';

				$result['site_group']	= 22;
				$result['site_ad']		= 5;

				$result['keyword']			= urldecode($check_url_array['n_keyword']);
				$result['ad_type']			= 'ad';
				$result['n_campaign_type']	= urldecode($check_url_array['n_campaign_type']);
				$result['n_rank']			= urldecode($check_url_array['n_rank']);

			// 페이스북
			} else if( strpos($ref_url_domain, "facebook.com") !== false ){
				$result['site_type']	= 11;
				$result['site_group']	= 3;
				$result['site_social']	= 8;


			// 트위터
			} else if( strpos($ref_url_domain, "twitter.com") !== false ){
				$result['site_type']		= 12;
				$result['site_group'] = 3;
				$result['site_social']	= 10;


			// 인스타그램
			} else if( strpos($ref_url_domain, "instagram.com") !== false ){
				$result['site_type']		= 13;
				$result['site_group'] = 3;
				$result['site_social']	= 9;

			// 유튜브
			} else if( strpos($ref_url_domain, "youtube.com") !== false ){
				$result['site_type']		= 14;
				$result['site_group'] = 3;
				$result['site_social']	= 11;

			// 뉴스
			} else if( 
				strpos($ref_url_domain, "asiae.co.kr"			) !== false ||
				strpos($ref_url_domain, "heraldcorp.com"		) !== false || 
				strpos($ref_url_domain, "ebn.co.kr"				) !== false || 
				strpos($ref_url_domain, "nocutnews.co.kr"		) !== false || 
				strpos($ref_url_domain, "starseoultv.com"		) !== false || 
				strpos($ref_url_domain, "etoday.co.kr"			) !== false || 
				strpos($ref_url_domain, "seoul.co.kr"			) !== false || 
				strpos($ref_url_domain, "donga.com"				) !== false ||
				strpos($ref_url_domain, "etnews.com"			) !== false ||
				strpos($ref_url_domain, "xportsnews.com"		) !== false ||
				strpos($ref_url_domain, "joongboo.com"			) !== false || 
				strpos($ref_url_domain, "gjfnews.org"			) !== false || 
				strpos($ref_url_domain, "ytn.co.kr"				) !== false || 
				strpos($ref_url_domain, "yna.co.kr"				) !== false ||
				strpos($ref_url_domain, "news1.kr"				) !== false ||
				strpos($ref_url_domain, "newsis.com"			) !== false ||
				strpos($ref_url_domain, "joongang.joins.com"	) !== false ||
				strpos($ref_url_domain, "mt.co.kr"				) !== false ||
				strpos($ref_url_domain, "sbs.co.kr"				) !== false ||
				strpos($ref_url_domain, "edaily.co.kr"			) !== false ||
				strpos($ref_url_domain, "hani.co.kr"			) !== false ||
				strpos($ref_url_domain, "hankookilbo.com"		) !== false ||
				strpos($ref_url_domain, "chosun.com"			) !== false ||
				strpos($ref_url_domain, "segye.com"				) !== false ||
				strpos($ref_url_domain, "kmib.co.kr"			) !== false ||
				strpos($ref_url_domain, "khan.co.kr"			) !== false ||
				strpos($ref_url_domain, "jtbc.joins.com"		) !== false 
			){
				$result['site_group'] = 4;
				if		( strpos($ref_url_domain, "asiae.co.kr"			) !== false )  $result['site_type']		= 15; 
				else if ( strpos($ref_url_domain, "heraldcorp.com"		) !== false )  $result['site_type']		= 16; 
				else if ( strpos($ref_url_domain, "ebn.co.kr"			) !== false )  $result['site_type']		= 17; 
				else if ( strpos($ref_url_domain, "nocutnews.co.kr"		) !== false )  $result['site_type']		= 18; 
				else if ( strpos($ref_url_domain, "starseoultv.com"		) !== false )  $result['site_type']		= 19; 
				else if ( strpos($ref_url_domain, "etoday.co.kr"		) !== false )  $result['site_type']		= 20; 
				else if ( strpos($ref_url_domain, "seoul.co.kr"			) !== false )  $result['site_type']		= 21; 
				else if ( strpos($ref_url_domain, "donga.com"			) !== false )  $result['site_type']		= 22; 
				else if ( strpos($ref_url_domain, "etnews.com"			) !== false )  $result['site_type']		= 23; 
				else if ( strpos($ref_url_domain, "xportsnews.com"		) !== false )  $result['site_type']		= 24; 
				else if ( strpos($ref_url_domain, "joongboo.com"		) !== false )  $result['site_type']		= 25; 
				else if ( strpos($ref_url_domain, "gjfnews.org"			) !== false )  $result['site_type']		= 26; 
				else if ( strpos($ref_url_domain, "ytn.co.kr"			) !== false )  $result['site_type']		= 27; 
				else if ( strpos($ref_url_domain, "yna.co.kr"			) !== false )  $result['site_type']		= 28; 
				else if ( strpos($ref_url_domain, "news1.kr"			) !== false )  $result['site_type']		= 29; 
				else if ( strpos($ref_url_domain, "newsis.com"			) !== false )  $result['site_type']		= 30; 
				else if ( strpos($ref_url_domain, "joongang.joins.com"	) !== false )  $result['site_type']		= 31; 
				else if ( strpos($ref_url_domain, "mt.co.kr"			) !== false )  $result['site_type']		= 32; 
				else if ( strpos($ref_url_domain, "sbs.co.kr"			) !== false )  $result['site_type']		= 33; 
				else if ( strpos($ref_url_domain, "edaily.co.kr"		) !== false )  $result['site_type']		= 34; 
				else if ( strpos($ref_url_domain, "hani.co.kr"			) !== false )  $result['site_type']		= 35; 
				else if ( strpos($ref_url_domain, "hankookilbo.com"		) !== false )  $result['site_type']		= 36; 
				else if ( strpos($ref_url_domain, "chosun.com"			) !== false )  $result['site_type']		= 37; 
				else if ( strpos($ref_url_domain, "segye.com"			) !== false )  $result['site_type']		= 38; 
				else if ( strpos($ref_url_domain, "kmib.co.kr"			) !== false )  $result['site_type']		= 39; 
				else if ( strpos($ref_url_domain, "khan.co.kr"			) !== false )  $result['site_type']		= 40; 
				else if ( strpos($ref_url_domain, "jtbc.joins.com"		) !== false )  $result['site_type']		= 41; 

			// 북마크
			} else if( strpos(substr($ref_url_domain,0,8), "bookmark") !== false ){
				$result['site_type']	= 98;
				$result['site_group']	= 19;

			// 레퍼가 없을때 ( 쿠키는 있으나 레퍼러 정보 없을때 ) 자기자신
			} else if( strpos(substr($ref_url_domain,0,8), "refnoget") !== false ){
				$result['site_type']	= 0;

			// 외부 페이지
			} else if( strpos($ref_url_domain, $url_domain) === false && $ref_url != "") {
				$result['site_type']	= 99;
				$result['site_group']	= 22;
			}

			if ( $result['keyword'] == '' ) $result['is_ad'] = 0;
		}
		return $result;
	}

	// 유입 도메인 및 vc 체크
	function get_domain_info($u, $ru , $vc , $logtime ) {
		$ru = $u;

		//tt(array($u, $ru , $vc , $logtime ));

		// 레퍼러가 없음 
		if($ru == '' || $ru == 'undefined'){
			if($vc != ''){ // 쿠키가 있으면 재방문 
				$ru = "refnoget";
			}else{ // 쿠키가 없으면 북마크 
				$ru = "bookmark";
			}
		}

		$vc_first	= 0;
		$u_t		= explode("?",$u)[0];
		$ru_t		= explode("?",$ru)[0];

		$u_domain	= parse_url($u_t)['host'];
		$ru_domain	= parse_url($ru_t)['host'];

		if ( is_null($ru_domain) ) $ru_domain = $ru;

		// refnoget :  쿠키는 있으나, 레퍼러가 안잡히는 경우 페이지뷰로 인정
		if( strpos(substr($ru,0,8), "refnoget") !== false ){
			$vc = $vc;

		// bookmark or 실 레퍼러 주소
		}else{
			// 유입 도메인이 다른경우 
			if( strpos($ru_domain, $u_domain) === false && $ru != ""){
				$vc = $logtime;
				$vc_first =1;
			}
			// 유입 도메인은 같으나 방문 쿠키가 없을경우 
			else if( strpos($ru_domain, $u_domain) !== false && $vc == ""){
				$ru = "bookmark";
				$vc = $logtime;
				$vc_first =1;
			}
			// 쿠키가 없을 경우
			else if( $vc == "" ){
				$vc = $logtime;
				$vc_first =1;
			}
			else{
				$vc = $vc;
			}
		}


		$result = array(
			"u"=>$u
			,"ru"=>$ru
			,"vc"=>$vc
			,"is_first"=>$vc_first 
			,"u_domain"=>$u_domain 
			,"ru_domain"=>$ru_domain 
		);
		return $result;
    }
	// 전환 가공
	function init_conv(&$mi_conv){
		$result = false;
		$temp_conv = json_decode($mi_conv , true);
		if ( is_array($temp_conv) ) {
			$mi_conv = $temp_conv;
			$result = true;
		}
		return $result;
	}

	// 미톡에 전달할 키워드 ip 정보 조합
	function talk_use_keyword_info($inflowInfo , $mi_ip ,  &$keyword_info ){
		$keyword_arr = array(
			"keyword"	=> $inflowInfo['keyword']
			, "ad_type" => isset($inflowInfo['ad_type']) ? 'ad' : 'search'
			, "domain" => $inflowInfo['site_name']
		);

		$keyword = $keyword_arr['keyword'] != ''  ? $keyword_arr['keyword'] : '' ;
		$urlencode_txt = "ip_address=$mi_ip";
		if ( $keyword != '' ) {
			$keyword_info = $keyword_arr;
			$str = implode('&', array_map(
				function ($v, $k) { return sprintf("%s=%s", $k, $v); },
				$keyword_arr,
				array_keys($keyword_arr)
			));
			$urlencode_txt .= "&" . $str;
		}
		$urlencode_txt = urlencode($urlencode_txt) ;
		return $urlencode_txt;
	}

	// 운영요리 함수
	function getWeekStr($data){
		$week_arr = array("일","월","화","수","목","금","토");
		$arr = array();
		$result = '';
		foreach($data as $k=>$v){
			if ( $v=='Y' ) $arr[] = $week_arr[$k];
		}
		$result = join(", " , $arr);
		if ( $result != '' ) $result .= " : ";
		return $result;
	}



	function arrayOrder($c , $o ){
		$result = array();
		if ( is_array($c) && is_array($o) ) {
			foreach ( $o  as $k2 => $o2 ) {
				foreach ( $o2 as $k=>$v){
					$c[$k] = trim($v);
				}
				$result[$k2] = $c;
			}
		}
		return $result;
	}

	// 배열안에 있는 모든 데이터 디코딩
	function str_urldecode($data) {
		if(is_array($data)) {
			foreach($data AS $k => $v) {
				if(is_array($v)) $new_data[$k] = str_urldecode($v);
				else $new_data[$k] = urldecode($v);
				//else $new_data[$k] = rawurldecode($v);
			}
		} else {
			$new_data = urldecode($data);
			//$new_data = rawurldecode($data);
		}
		return $new_data;
	}


	function get_keyword($url , $ref_url_domain ){
		$result = array( 
			'keyword'	=> "" 
			,'ad_type'	=> "" 
			,'domain'	=> "" 
		);

		// 추적 URL
		$check_url_array = array();
		if( strpos($url, "?") !== false ){
			$url_array = explode("&",explode("?",$url)[1]);
			if ( count($url_array) ) {
				foreach($url_array as $val) {
					$v = explode("=",$val);
					if ( count($v) == 2 ) 
						$check_url_array[$v[0]] = $v[1];
				}
			}
		}
		
		// 레퍼러 URL
		$check_ref_url_array = array();
		if( strpos($ref_url_domain, "?") !== false ){
			$ref_url_array = explode("&",explode("?",$ref_url_domain)[1]);
			if ( count($ref_url_array) ) {
				foreach($ref_url_array as $val) {
					$v = explode("=",$val);
					if ( count($v) == 2 ) 
						$check_ref_url_array[$v[0]] = $v[1];
				}
			}
		}

		if( !empty($check_url_array['n_keyword']) || !empty($check_url_array['NVKWD']) || !empty($check_url_array['DMKW']) || !empty($check_url_array['gclid']) || !empty($check_url_array['k_keyword']) || ( !empty($check_url_array['n_campaign']) &&  empty($check_url_array['n_keyword']) &&  !empty($check_url_array['n_query']) ) ){
			// 네이버 광고
			if( !empty($check_url_array['n_keyword']) || !empty($check_url_array['NVKWD']) || ( !empty($check_url_array['n_campaign']) &&  empty($check_url_array['n_keyword']) &&  !empty($check_url_array['n_query']) ) ){

				if( !empty($check_url_array['n_keyword']) ){
					$result['keyword'] = urldecode($check_url_array['n_keyword']);
					$result['ad_type'] = 'ad';
					$result['domain'] = '네이버';
				}else if ( !empty($check_url_array['NVKWD'])  ) {
					$result['keyword'] = urldecode($check_url_array['NVKWD']);
					$result['ad_type'] = 'ad';
					$result['domain'] = '네이버';
				}else  {
					$result['keyword'] = urldecode($check_url_array['n_query']);
					$result['ad_type'] = 'ad';
					$result['domain'] = '네이버';
				}

			}
			// 다음광고
			else if( !empty($check_url_array['k_keyword']) || !empty($check_url_array['DMKW']) ){
				if( !empty($check_url_array['k_keyword']) ){
					$result['keyword'] = urldecode($check_url_array['k_keyword']);
					$result['ad_type'] = 'ad';
					$result['domain'] = '다음';
				}else{
					$result['keyword'] = urldecode($check_url_array['DMKW']);
					$result['ad_type'] = 'ad';
					$result['domain'] = '다음';
				}

			}
			// 구글 광고
			else if( !empty($check_url_array['gclid']) ){
				$result['keyword'] = urldecode($check_url_array['gclid']);
				$result['ad_type'] = 'ad';
				$result['domain'] = '구글';
			}
		}else{
			// 네이버
			if( strpos($ref_url_domain, "naver.com") !== false ){
				// 광고
				if( !empty($check_url_array['n_keyword']) || !empty($check_url_array['NVKWD']) || ( !empty($check_url_array['n_campaign']) &&  empty($check_url_array['n_keyword']) &&  !empty($check_url_array['n_query']) ) ){

					if( !empty($check_url_array['n_keyword']) ){
						$result['keyword'] = urldecode($check_url_array['n_keyword']);
						$result['ad_type'] = 'ad';
						$result['domain'] = '네이버';
					}else if ( !empty($check_url_array['NVKWD'])  ) {
						$result['keyword'] = urldecode($check_url_array['NVKWD']);
						$result['ad_type'] = 'ad';
						$result['domain'] = '네이버';
					}else  {
						$result['keyword'] = urldecode($check_url_array['n_query']);
						$result['ad_type'] = 'ad';
						$result['domain'] = '네이버';
					}

				}
				// 검색
				else{
					$result['keyword'] = urldecode($check_ref_url_array['query']);
					$result['ad_type'] = 'search';
					$result['domain'] = '네이버';
				}
			}
			// 다음, 네이트
			else if( strpos($ref_url_domain, "daum.net") !== false ){

				$nate_check = substr($ref_url_domain,-4);
				// 네이트
				if( $nate_check  == "nate" ){
					$result['domain'] = '네이트';
				}else{
					$result['domain'] = '다음';
				}

				// 광고
				if( !empty($check_url_array['DMKW']) ){
					$result['keyword'] = urldecode($check_url_array['DMKW']);
					$result['ad_type'] = 'ad';
				}else if( !empty($check_url_array['k_keyword']) ){
					$result['keyword'] = urldecode($check_url_array['k_keyword']);
					$result['ad_type'] = 'ad';
				}
				// 검색
				else{
					$result['keyword'] = urldecode($check_ref_url_array['q']);
					$result['ad_type'] = 'search';
				}
			}
			// 줌
			else if( strpos($ref_url_domain, "zum.com") !== false ){
				$result['domain'] = '줌';
				// 광고
				if( !empty($check_url_array['n_keyword']) || !empty($check_url_array['NVKWD']) || ( !empty($check_url_array['n_campaign']) &&  empty($check_url_array['n_keyword']) &&  !empty($check_url_array['n_query']) )){

					if( !empty($check_url_array['n_keyword']) ){
						$result['keyword'] = urldecode($check_url_array['n_keyword']);
						$result['ad_type'] = 'ad';
					}else if ( !empty($check_url_array['NVKWD'])  ) {
						$result['keyword'] = urldecode($check_url_array['NVKWD']);
						$result['ad_type'] = 'ad';
					}else  {
						$result['keyword'] = urldecode($check_url_array['n_query']);
						$result['ad_type'] = 'ad';
					}

				}
				// 검색
				else{
					$result['keyword'] = urldecode($check_ref_url_array['query']);
					$result['ad_type'] = 'search';
				}
			}
			// bing
			else if( strpos($ref_url_domain, "bing.com") !== false ){
				$result['domain'] = '빙';
				// 광고
				if( !empty($check_url_array['DMCOL'])){
					$result['keyword'] = urldecode($check_url_array['DMKW']);
					$result['ad_type'] = 'ad';
				}else if(!empty($check_url_array['k_media'])){
					$result['keyword'] = urldecode($check_url_array['k_keyword']);
					$result['ad_type'] = 'ad';
				}
				// 검색
				else{
					$result['keyword'] = urldecode($check_ref_url_array['q']);
					$result['ad_type'] = 'search';
				}
			}
			// 구글
			else if( strpos($ref_url_domain, "google.co.kr") !== false || strpos($ref_url_domain, "google.com") !== false ){
				$result['domain'] = '구글';
				// 광고
				if( !empty($check_url_array['gclid']) ){
					$result['keyword'] = urldecode($check_url_array['gclid']);
					$result['ad_type'] = 'ad';
				}else{
					$result['keyword'] = urldecode($check_ref_url_array['q']);
					$result['ad_type'] = 'search';
					$result['dd'] = $ref_url_domain;
				}
			}
			// 11번가
			else if( strpos($ref_url_domain, "11st.co.kr") !== false ){
				$result['domain'] = '11번가';
				$result['keyword'] = urldecode($check_url_array['n_keyword']);
				$result['ad_type'] = 'ad';
			}
			// 옥션
			else if( strpos($ref_url_domain, "auction.co.kr") !== false ){
				$result['domain'] = '옥션';
				$result['keyword'] = urldecode($check_url_array['n_keyword']);
				$result['ad_type'] = 'ad';
			}
			// 지마켓
			else if( strpos($ref_url_domain, "gmarket.co.kr") !== false ){
				$result['domain'] = '지마켓';
				$result['keyword'] = urldecode($check_url_array['n_keyword']);
				$result['ad_type'] = 'ad';
			}
		}
		return $result;
	}

	function makeData($logtime, $adkey, $mi_ip, $user_agent, $vc, $vc_first, $visit_id, $u, $ru, $mi_type, $mi_val, $mi_wm, $mi_order_num){
		$today				= $logtime;
		$adkey				= $adkey;
		$ip					= $mi_ip;
		$browser			= $user_agent;
		$first_date			= $vc;
		$first_check		= $vc_first;
		$visit_id			= $visit_id;
		$url				= $u;
		$ref_url			= $ru;
		$c_type				= $mi_type;
		$c_val				= $mi_val;
		$agent				= $mi_wm;

		$url				= explode("?",$url);
		$url_domain			= isset($url[0]) ? $url[0] : '' ;
		$url_param			= isset($url[1]) ? $url[1] : '' ;
		$ref_url_str		= explode("?",$ref_url);
		$ref_url_domain		= isset($ref_url_str[0]) ? $ref_url_str[0] : '' ;
		$ref_url_param		= isset($ref_url_str[1]) ? $ref_url_str[1] : '' ;

		// 변수리스트
		$NaPm				= "";
		$m_type				= 0;
		$m_detail			= 0;
		$ad_keyword			= "";
		$search_keyword		= "";
		$section			= "";
		$NVADID				= "";
		$NVADRANK			= "";
		$n_media			= "0";
		$n_ad_group			= "";
		$n_keyword_id		= "";
		if( $c_val == "" )	$c_val = "0";

		// 1차 도메인만 정규식
		$pattern = "/[a-z0-9]+\.([a-z0-9]|co\.kr)+$/si";

		// 레퍼러 도메인
		$tmp_ref_domain = parse_url($ref_url_domain);
		$ref_domain = isset($tmp_ref_domain['host']) ? $tmp_ref_domain['host'] : '' ;
		preg_match($pattern, $ref_domain, $match_ref);
		$ref_url_only_domain = isset($match_ref[0]) ? $match_ref[0] : '' ;

		// URL 도메인
		$tmp_url_domain = parse_url($url_domain);
		if(preg_match("/[\xE0-\xFF][\x80-\xFF][\x80-\xFF]/", $tmp_url_domain['host'])){
			$url_only_domain = $tmp_url_domain['host'];
		}else{
			preg_match($pattern, $tmp_url_domain['host'], $match);
			$url_only_domain = $match[0];
		}
					
		// 추적 URL
		$url_array = explode("&",$url_param);
		$check_url_array = array();
		foreach($url_array as $val) {
			$v = explode("=",$val);
			if ( count($v) == 2 ) 
				$check_url_array[$v[0]] = $v[1];
		}
		
		// 레퍼러 URL
		$ref_url_array = explode("&",$ref_url_param);
		$check_ref_url_array = array();
		foreach($ref_url_array as $val) {
			$v = explode("=",$val);
			if ( count($v) == 2 ) 
				$check_ref_url_array[$v[0]] = $v[1];
		}

		// 한글도메인 -> 퓨니코드 변환
		$ref_url_preg = str_replace("http://", "", $ref_url);
		$ref_url_preg = str_replace("https://", "", $ref_url_preg);
		$ref_url_preg_str = explode("?",$ref_url_preg);

		$ref_url_puny_str = $ref_url_preg_str[0];
		$ref_url_puny = explode("/",$ref_url_puny_str);
		$ref_url_puny = $ref_url_puny[0];

		if (extension_loaded('intl')){
			$ref_url_puny = idn_to_ascii($ref_url_puny);
			$url_only_domain = idn_to_ascii($url_only_domain);
			$ref_domain = idn_to_ascii($ref_domain);
		}

		// 캠페인타입
		$n_campaign_type = '';
		if(isset($check_url_array['n_campaign_type'])){
			$n_campaign_type = $check_url_array['n_campaign_type'];
		}

		// 마케팅분석
		if(isset($check_url_array['e_type'])){
			$check_url_array['m_type'] = $check_url_array['e_type'];
		}
		$check_url_array['m_type'] = isset($check_url_array['m_type']) ? $check_url_array['m_type'] : '';
		if($check_url_array['m_type']=='email' || $check_url_array['m_type']=='blog' || $check_url_array['m_type']=='banner' || $check_url_array['m_type']=='cafe' && ($check_url_array['m_code']!='') ){

			// 코드분류
			if( $check_url_array['m_type'] =="email" ){
				$m_type = 1000;
			}else if( $check_url_array['m_type'] =="blog" ){
				$m_type = 1001;
			}else if( $check_url_array['m_type'] =="banner" ){
				$m_type = 1002;
			}else if( $check_url_array['m_type'] =="cafe" ){
				$m_type = 1003;
			}

			$m_detail = $check_url_array['m_code'];

		}else{

			//****************************************************************//
			//*** 레퍼러는 없으나, 광고 파라미터가 있는 경우는 광고로 판단 ***//
			//****************************************************************//

			if( !empty($check_url_array['n_keyword']) || !empty($check_url_array['NVKWD']) || !empty($check_url_array['DMKW']) || !empty($check_url_array['gclid']) || !empty($check_url_array['k_keyword'])){

				// 네이버 광고
				if( !empty($check_url_array['n_keyword']) || !empty($check_url_array['NVKWD']) ){
					$m_type = 1;
					$m_detail = 19;


					// 네이버 쇼핑
					if( strpos($ref_url_domain, "shopping.naver.com") !== false ){
						$m_detail = 1;
						// 네이버 쇼핑박스
						if( strpos($ref_url_domain, "castbox.shopping.naver.com") !== false ){
							$m_detail = 4;
						}
					}


					if( !empty($check_url_array['n_keyword']) ){
						$ad_keyword = urldecode($check_url_array['n_keyword']);
					}else{
						$ad_keyword = urldecode($check_url_array['NVKWD']);
					}
					$section = urldecode($check_url_array['NVAR']);
					$NVADID = urldecode($check_url_array['n_ad']);
					$NVADRANK = urldecode($check_url_array['n_rank']);
					$n_media = urldecode($check_url_array['n_media']);
					$n_ad_group = urldecode($check_url_array['n_ad_group']);
					$n_keyword_id = urldecode($check_url_array['n_keyword_id']);
				}
				// 다음광고
				else if( !empty($check_url_array['DMKW']) ){
					$m_type = 2;
					$m_detail = 21;
					$ad_keyword = urldecode($check_url_array['DMKW']);
					$section = urldecode($check_url_array['DMCOL']);		// PM=프리미엄링크, WIDELINK=와이드링크

				}else if( !empty($check_url_array['k_keyword']) ){
					$m_type = 2;
					$m_detail = 21;
					$ad_keyword = urldecode($check_url_array['k_keyword']);
					$section = urldecode($check_url_array['k_media']);
					$NVADRANK = urldecode($check_url_array['k_rank']);
					$n_ad_group = urldecode($check_url_array['k_adgroup']);
					$n_keyword_id = urldecode($check_url_array['k_keyword_id']);
				}
				// 구글 광고
				else if( !empty($check_url_array['gclid']) ){
					$m_type = 3;
					$m_detail = 22;

					$ad_keyword = urldecode($check_url_array['gclid']);
				}

			}else{
		
				//****************************************************************//
				//*** 레퍼러 체크 포함  ***//
				//****************************************************************//
			
				// 네이버
				if( strpos($ref_url_domain, "naver.com") !== false ){
					$m_type = 1;

					// 광고
					if( !empty($check_url_array['n_keyword']) || !empty($check_url_array['NVKWD']) ){
						if( !empty($check_url_array['n_keyword']) ){
							$ad_keyword = urldecode($check_url_array['n_keyword']);
						}else{
							$ad_keyword = urldecode($check_url_array['NVKWD']);
						}
						$section = urldecode($check_url_array['NVAR']);
						$NVADID = urldecode($check_url_array['n_ad']);
						$NVADRANK = urldecode($check_url_array['n_rank']);
						$n_media = urldecode($check_url_array['n_media']);
						$n_ad_group = urldecode($check_url_array['n_ad_group']);
						$n_keyword_id = urldecode($check_url_array['n_keyword_id']);
					}
					// 검색
					else{
						$search_keyword = urldecode($check_ref_url_array['query']);
					}

					// 클릭초이스
					if( strpos($ref_url_domain, "search.naver.com") !== false ){
						if( $ad_keyword != "" ){
							$m_detail = 19;
						}else{
							$m_detail = 38;
						}
					}
					// 네이버 쇼핑
					else if( strpos($ref_url_domain, "shopping.naver.com") !== false ){
						$m_detail = 1;

						// 네이버 쇼핑박스
						if( strpos($ref_url_domain, "castbox.shopping.naver.com") !== false ){
							$m_detail = 4;
						}
					}
					// 네이버 스토어팜
					else if( strpos($ref_url_domain, "storefarm.naver.com") !== false ){
						$m_detail = 3;
					}
					// 네이버 지식IN
					else if( strpos($ref_url_domain, "kin.naver.com") !== false ){
						$m_detail = 5;
					}
					// 네이버 캐스트
					else if( strpos($ref_url_domain, "navercast.naver.com") !== false ){
						$m_detail = 6;
					}
					// 네이버 블로그
					else if( strpos($ref_url_domain, "blog.naver.com") !== false ){
						$m_detail = 7;
					}
					// 네이버 광고주센터
					else if( strpos($ref_url_domain, "saedu.naver.com") !== false ){
						$m_detail = 8;
					}
					// 네이버 카페
					else if( strpos($ref_url_domain, "cafe.naver.com") !== false ){
						$m_detail = 9;
					}
					// 네이버 네이버페이
					else if( strpos($ref_url_domain, "pay.naver.com") !== false ){
						$m_detail = 10;
					}
					// 네이버 톡톡
					else if( strpos($ref_url_domain, "talk.naver.com") !== false ){
						$m_detail = 11;
					}
					// 네이버 지도
					else if( strpos($ref_url_domain, "map.naver.com") !== false ){
						$m_detail = 12;
					}
					// 네이버 메일
					else if( strpos($ref_url_domain, "mail.naver.com") !== false ){
						$m_detail = 13;
					}

				}
				// 다음, 네이트
				else if( strpos($ref_url_domain, "daum.net") !== false ){
					$nate_check = substr($ref_url_domain,-4);
					// 네이트
					if( $nate_check  == "nate" ){
						$m_type = 6;
					}
					// 다음
					else{
						$m_type = 2;
					}
					// 광고
					if( !empty($check_url_array['DMKW']) ){
						$ad_keyword = urldecode($check_url_array['DMKW']);
						$section = urldecode($check_url_array['DMCOL']);		// PM=프리미엄링크, WIDELINK=와이드링크
					}else if( !empty($check_url_array['k_keyword']) ){
						$ad_keyword = urldecode($check_url_array['k_keyword']);
						$section = urldecode($check_url_array['k_media']);
						$NVADRANK = urldecode($check_url_array['k_rank']);
						$n_ad_group = urldecode($check_url_array['k_adgroup']);
						$n_keyword_id = urldecode($check_url_array['k_keyword_id']);
					}
					// 검색
					else{
						$search_keyword = urldecode($check_ref_url_array['q']);
					}


					// 다음 검색광고
					if( strpos($ref_url_domain, "search.daum.net") !== false ){
						if( $ad_keyword != "" ) $m_detail = 21;
					}
					// 다음 쇼핑하우
					else if( strpos($ref_url_domain, "shopping.daum.net") !== false ){
						$m_detail = 2;
					}
					// 다음 카페
					else if( strpos($ref_url_domain, "cafe.daum.net") !== false ){
						$m_detail = 14;
					}
					// 다음 블로그
					else if( strpos($ref_url_domain, "blog.daum.net") !== false ){
						$m_detail = 15;
					}
					// 다음 메일
					else if( strpos($ref_url_domain, "mail.daum.net") !== false ){
						$m_detail = 16;
					}
					// 다음 지도
					else if( strpos($ref_url_domain, "map.daum.net") !== false ){
						$m_detail = 17;
					}
					// 다음 미디어
					else if( strpos($ref_url_domain, "media.daum.net") !== false ){
						$m_detail = 18;
					}

				}
				// 줌
				else if( strpos($ref_url_domain, "zum.com") !== false ){
					$m_type = 4;
					// 광고
					if( !empty($check_url_array['n_keyword']) || !empty($check_url_array['NVKWD']) ){
						if( !empty($check_url_array['n_keyword']) ){
							$ad_keyword = urldecode($check_url_array['n_keyword']);
						}else{
							$ad_keyword = urldecode($check_url_array['NVKWD']);
						}
						$section = urldecode($check_url_array['NVAR']);
						$NVADID = urldecode($check_url_array['n_ad']);
						$NVADRANK = urldecode($check_url_array['n_rank']);
						$n_media = urldecode($check_url_array['n_media']);
						$n_ad_group = urldecode($check_url_array['n_ad_group']);
						$n_keyword_id = urldecode($check_url_array['n_keyword_id']);
						$m_detail = 23;
					}
					// 검색
					else{
						$search_keyword = urldecode($check_ref_url_array['query']);
					}
				}
				// bing
				else if( strpos($ref_url_domain, "bing.com") !== false ){
					$m_type = 5;
					
					// 광고
					if( !empty($check_url_array['DMCOL']) ){
						$ad_keyword = urldecode($check_url_array['DMKW']);
						$section = urldecode($check_url_array['DMCOL']);		// PM=프리미엄링크, WIDELINK=와이드링크
						$m_detail = 24;
					}else if( !empty($check_url_array['k_media']) ){
						$m_detail = 24;
						$ad_keyword = urldecode($check_url_array['k_keyword']);
						$section = urldecode($check_url_array['k_media']);
						$NVADRANK = urldecode($check_url_array['k_rank']);
						$n_ad_group = urldecode($check_url_array['k_adgroup']);
						$n_keyword_id = urldecode($check_url_array['k_keyword_id']);
					}
					// 검색
					else{
						$search_keyword = urldecode($check_ref_url_array['q']);
					}

				}
				// 구글
				else if( strpos($ref_url_domain, "google.co.kr") !== false || strpos($ref_url_domain, "google.com") !== false ){
					$m_type = 3;
					
					// 광고
					if( !empty($check_url_array['gclid']) ){
						$ad_keyword = urldecode($check_url_array['gclid']);
						$m_detail = 22;
					}
					$search_keyword = urldecode($check_ref_url_array['q']);
				}
				// 11번가
				else if( strpos($ref_url_domain, "11st.co.kr") !== false ){
					$m_type = 7;
					$ad_keyword = urldecode($check_url_array['n_keyword']);
					$NVADID = urldecode($check_url_array['n_ad']);
					$NVADRANK = urldecode($check_url_array['n_rank']);
					$n_media = urldecode($check_url_array['n_media']);
					$n_ad_group = urldecode($check_url_array['n_ad_group']);
					$n_keyword_id = urldecode($check_url_array['n_keyword_id']);
				}
				// 인크루트
				else if( strpos($ref_url_domain, "incruit.com") !== false ){
					$m_type = 8;
				}
				// 옥션
				else if( strpos($ref_url_domain, "auction.co.kr") !== false ){
					$m_type = 9;
					$ad_keyword = urldecode($check_url_array['n_keyword']);
					$NVADID = urldecode($check_url_array['n_ad']);
					$NVADRANK = urldecode($check_url_array['n_rank']);
					$n_media = urldecode($check_url_array['n_media']);
					$n_ad_group = urldecode($check_url_array['n_ad_group']);
					$n_keyword_id = urldecode($check_url_array['n_keyword_id']);
				}
				// 뉴스
				else if( 
					strpos($ref_url_domain, "asiae.co.kr") !== false ||
					strpos($ref_url_domain, "heraldcorp.com") !== false || 
					strpos($ref_url_domain, "ebn.co.kr") !== false || 
					strpos($ref_url_domain, "nocutnews.co.kr") !== false || 
					strpos($ref_url_domain, "starseoultv.com") !== false || 
					strpos($ref_url_domain, "etoday.co.kr") !== false || 
					strpos($ref_url_domain, "seoul.co.kr") !== false || 
					strpos($ref_url_domain, "donga.com") !== false ||
					strpos($ref_url_domain, "etnews.com") !== false ||
					strpos($ref_url_domain, "xportsnews.com") !== false ||
					strpos($ref_url_domain, "joongboo.com") !== false || 
					strpos($ref_url_domain, "gjfnews.org") !== false || 
					strpos($ref_url_domain, "ytn.co.kr") !== false
				){
					$m_type = 21;
					
					// 아시아 경제
					if( strpos($ref_url_domain, "asiae.co.kr") !== false ){
						$m_detail = 25;
					}
					// 헤드럴경제
					else if( strpos($ref_url_domain, "heraldcorp.com") !== false ){
						$m_detail = 26;
					}
					// EBN
					else if( strpos($ref_url_domain, "ebn.co.kr") !== false ){
						$m_detail = 27;
					}
					// 노컷뉴스
					else if( strpos($ref_url_domain, "nocutnews.co.kr") !== false ){
						$m_detail = 28;
					}
					// 스타서울
					else if( strpos($ref_url_domain, "starseoultv.com") !== false ){
						$m_detail = 29;
					}
					// 이투데이
					else if( strpos($ref_url_domain, "etoday.co.kr") !== false ){
						$m_detail = 30;
					}
					// 서울신문
					else if( strpos($ref_url_domain, "seoul.co.kr") !== false ){
						$m_detail = 31;
					}
					// 동아일보
					else if( strpos($ref_url_domain, "donga.com") !== false ){
						$m_detail = 32;
					}
					// 전자신문
					else if( strpos($ref_url_domain, "etnews.com") !== false ){
						$m_detail = 33;
					}
					// 엑스포츠
					else if( strpos($ref_url_domain, "xportsnews.com") !== false ){
						$m_detail = 34;
					}
					// 중부일보
					else if( strpos($ref_url_domain, "joongboo.com") !== false ){
						$m_detail = 35;
					}
					// 국제언론인클럽
					else if( strpos($ref_url_domain, "gjfnews.org") !== false ){
						$m_detail = 36;
					}
					// YTN
					else if( strpos($ref_url_domain, "ytn.co.kr") !== false ){
						$m_detail = 37;
					}

				}
				// 페이스북
				else if( strpos($ref_url_domain, "facebook.com") !== false ){
					$m_type = 18;
				}
				// 지마켓
				else if( strpos($ref_url_domain, "gmarket.co.kr") !== false ){
					$m_type = 19;
					$ad_keyword = urldecode($check_url_array['n_keyword']);
					$NVADID = urldecode($check_url_array['n_ad']);
					$NVADRANK = urldecode($check_url_array['n_rank']);
					$n_media = urldecode($check_url_array['n_media']);
					$n_ad_group = urldecode($check_url_array['n_ad_group']);
					$n_keyword_id = urldecode($check_url_array['n_keyword_id']);
				}
				// kakao
				else if( strpos($ref_url_domain, "kakao.com") !== false ){
					$m_type = 20;
				}else if(strpos($ref_url_domain, "instagram.com") !== false){
					$m_type = 22;
				}else if(strpos($ref_url_domain, "youtube.com") !== false){
					$m_type = 23;
				}else if(strpos($ref_url_domain, "zigzag.kr") !== false){
					$m_type = 24;
				}
				// 이외 검색엔진
				else if( strpos($ref_url_puny, $url_only_domain) === false && $ref_url != "bookmark" && $ref_url != ""){
					if( $ref_url_domain != ""){
						$m_type = 99;	
					}else{
						$m_type = 0;	
					}
				}

				if( strpos(substr($ref_url,0,8), "bookmark") !== false ){
					$m_type = 0;
				}
				if( strpos(substr($ref_url,0,8), "refnoget") !== false ){
					$m_type = 0;
				}
				if( preg_replace("/\s+/", "", $ref_url) == "" ){
					$m_type = 0;
				}

				if( strpos(substr($ref_url,0,9), "undefined") !== false ){
					$m_type = 0;
					$ref_url  = "bookmark";
				}
			}
		}
		
		if( strpos(substr($ref_domain,-1), "_") !== false ){
			$ref_domain = substr($ref_domain , 0, -1);
		}

		// 레퍼도메인 네이버앱 별도 체크
		if( strpos(substr($ref_url,0,14), "naversearchapp") !== false ){
			$ref_domain = 'naversearchapp://main';
		}

		//  전환값 체크
		if($c_type != "" && $c_type != "undefined"){
			$c_type = $c_type;
			$c_val = $c_val;
		}else{
			$c_type = "";
			$c_val = 0;
		}
		
		$mon = substr($today, 0, 4).substr($today, 5, 2); 
		$dat = substr($today, 0, 10);
		$todayyyymd = substr($today, 0, 4).substr($today, 5, 2).substr($today, 8, 2); 

		// PC / 모바일 구분 (0: PC, 1: 모바일)
		if( $agent == 'P' ){
			$dataAgent = '0';
		}else{
			$dataAgent = '1';
		}

		$log_data[0] = array(
			  $today
			, $todayyyymd
			, $adkey
			, $visit_id
			, $ip
			, $url_domain
			, $url_param
			, $m_type
			, $m_detail
			, addslashes($ad_keyword)
			, addslashes($search_keyword)
			, $section
			, $NVADRANK
			, $n_media
			, $n_ad_group
			, $NVADID
			, $n_keyword_id
			, $first_date
			, $first_check
			, $browser
			, $ref_url
			, $ref_domain
			, $c_type
			, $c_val
			, $dataAgent
			, $mon
		);

		$log_data[1] = array(
			  'today'			=>$today
			, 'todayyyymd'		=>$todayyyymd
			, 'adkey'			=>$adkey
			, 'visit_id'		=>$visit_id
			, 'ip'				=>$ip
			, 'url_domain'		=>$url_domain
			, 'url_param'		=>$url_param
			, 'm_type'			=>$m_type
			, 'm_detail'		=>$m_detail
			, 'ad_keyword'		=>addslashes($ad_keyword)
			, 'search_keyword'	=>addslashes($search_keyword)
			, 'section'			=>$section
			, 'NVADRANK'		=>$NVADRANK
			, 'n_media'			=>$n_media
			, 'n_ad_group'		=>$n_ad_group
			, 'NVADID'			=>$NVADID
			, 'n_keyword_id'	=>$n_keyword_id
			, 'first_date'		=>$first_date
			, 'first_check'		=>$first_check
			, 'browser'			=>$browser
			, 'ref_url'			=>$ref_url
			, 'ref_domain'		=>$ref_domain
			, 'c_type'			=>$c_type
			, 'c_val'			=>$c_val
			, 'dataAgent'		=>$dataAgent
			, 'mon'				=>$mon
		);
		return $log_data;
	}


	function makeData_new($logtime,$adkey,$mi_ip,$user_agent,$vc,$vc_first,$visit_id,$u,$ru,$mi_type,$mi_val,$mi_wm,$mi_order_num,$user_os,$vc_second,$inflow){
		$today				= $logtime;
		$adkey				= $adkey;
		$ip					= $mi_ip;
		$browser			= $user_agent;
		$first_date			= $vc;
		$first_check		= $vc_first;
		$visit_id			= $visit_id;
		$url				= $u;
		$ref_url			= $ru;
		$c_type				= $mi_type;
		$c_val				= $mi_val;
		$agent				= $mi_wm;
		$os					= $user_os;

		$url_full			= preg_replace("/\s+/", "", $url);
		$url_path			= parse_url($url,PHP_URL_PATH);
		$url_onlydomain		= parse_url($url,PHP_URL_HOST);
		$url_path_array		= explode("/",$url_path);
		$url_page			= end($url_path_array);
		//$url_page			= explode('?',$url_full)[0];
		$url				= explode("?",$url);
		$url_domain			= isset($url[0]) ? preg_replace("/\s+/", "", $url[0]) : '' ;
		$url_param			= isset($url[1]) ? $url[1] : '' ;

		$ref_url_full			= $ref_url;
		$ref_url_path			= ($ref_url != "bookmark" && $ref_url != "refnoget") ? parse_url($ref_url,PHP_URL_PATH) : '';
		$ref_url_onlydomain		= parse_url($ref_url,PHP_URL_HOST);
		$ref_url_path_array		= explode("/",$ref_url_path);
		$ref_url_page			= ($ref_url != "bookmark" && $ref_url != "refnoget") ? end($ref_url_path_array) : '';
		$ref_url_str		= explode("?",$ref_url);
		$ref_url_domain		= isset($ref_url_str[0]) ? $ref_url_str[0] : '' ;
		$ref_url_param		= isset($ref_url_str[1]) ? $ref_url_str[1] : '' ;

		// 변수리스트
		$NaPm			= "";
		$m_type			= 0;
		$m_detail		= 0;
		$ad_keyword		= "";
		$search_keyword	= "";
		$section		= "";
		$NVADID			= "";
		$NVADRANK		= "";
		$n_media		= "0";
		$n_ad_group			= "";
		$n_keyword_id	= "";
		if( $c_val == "" ) $c_val = "0";
		$keyword_before = "";

		// 1차 도메인만 정규식
		$pattern = "/[a-z0-9]+\.([a-z0-9]|co\.kr)+$/si";

		// 레퍼러 도메인
		$tmp_ref_domain = parse_url($ref_url_domain);
		$ref_domain = isset($tmp_ref_domain['host']) ? $tmp_ref_domain['host'] : '' ;
		preg_match($pattern, $ref_domain, $match_ref);
		$ref_url_only_domain = isset($match_ref[0]) ? $match_ref[0] : '' ;

		// URL 도메인
		$tmp_url_domain = parse_url($url_domain);
		if(preg_match("/[\xE0-\xFF][\x80-\xFF][\x80-\xFF]/", $tmp_url_domain['host'])){
			$url_only_domain = $tmp_url_domain['host'];
		}else{
			preg_match($pattern, $tmp_url_domain['host'], $match);
			$url_only_domain = $match[0];
		}
					
		// 추적 URL
		$url_array = explode("&",$url_param);
		$check_url_array = array();
		foreach($url_array as $val) {
			$v = explode("=",$val);
			if ( count($v) == 2 ) 
				$check_url_array[$v[0]] = $v[1];
		}
		
		// 레퍼러 URL
		$ref_url_array = explode("&",$ref_url_param);
		$check_ref_url_array = array();
		foreach($ref_url_array as $val) {
			$v = explode("=",$val);
			if ( count($v) == 2 ) 
				$check_ref_url_array[$v[0]] = $v[1];
		}

		// 한글도메인 -> 퓨니코드 변환
		$ref_url_preg = str_replace("http://", "", $ref_url);
		$ref_url_preg = str_replace("https://", "", $ref_url_preg);
		$ref_url_preg_str = explode("?",$ref_url_preg);

		$ref_url_puny_str = $ref_url_preg_str[0];
		$ref_url_puny = explode("/",$ref_url_puny_str);
		$ref_url_puny = $ref_url_puny[0];

		if (extension_loaded('intl')){
			$ref_url_puny = idn_to_ascii($ref_url_puny);
			$url_only_domain = idn_to_ascii($url_only_domain);
			$ref_domain = idn_to_ascii($ref_domain);
		}

		// 캠페인타입
		$n_campaign_type = '';
		if(isset($check_url_array['n_campaign_type'])){
			$n_campaign_type = $check_url_array['n_campaign_type'];
		}

		// 마케팅분석
		if(isset($check_url_array['e_type'])){
			$check_url_array['m_type'] = $check_url_array['e_type'];
		}
		$check_url_array['m_type'] = isset($check_url_array['m_type']) ? $check_url_array['m_type'] : '';
		if($check_url_array['m_type']=='email' || $check_url_array['m_type']=='blog' || $check_url_array['m_type']=='banner' || $check_url_array['m_type']=='cafe' && ($check_url_array['m_code']!='') ){

			// 코드분류
			if( $check_url_array['m_type'] =="email" ){
				$m_type = 1000;
			}else if( $check_url_array['m_type'] =="blog" ){
				$m_type = 1001;
			}else if( $check_url_array['m_type'] =="banner" ){
				$m_type = 1002;
			}else if( $check_url_array['m_type'] =="cafe" ){
				$m_type = 1003;
			}

			$m_detail = $check_url_array['m_code'];

		}else{

			//****************************************************************//
			//*** 레퍼러는 없으나, 광고 파라미터가 있는 경우는 광고로 판단 ***//
			//****************************************************************//

			if( !empty($check_url_array['n_keyword']) || !empty($check_url_array['NVKWD']) || !empty($check_url_array['DMKW']) || !empty($check_url_array['gclid']) || !empty($check_url_array['k_keyword']) || ( !empty($check_url_array['n_campaign']) &&  empty($check_url_array['n_keyword']) &&  !empty($check_url_array['n_query']) ) ) {

				// 네이버 광고
				if( !empty($check_url_array['n_keyword']) || !empty($check_url_array['NVKWD']) || ( !empty($check_url_array['n_campaign']) &&  empty($check_url_array['n_keyword']) &&  !empty($check_url_array['n_query']) ) ){
					$m_type = 1;
					$m_detail = 19;


					// 네이버 쇼핑
					if( strpos($ref_url_domain, "shopping.naver.com") !== false ){
						$m_detail = 1;
						// 네이버 쇼핑박스
						if( strpos($ref_url_domain, "castbox.shopping.naver.com") !== false ){
							$m_detail = 4;
						}
					}

					if( !empty($check_url_array['n_keyword']) ){
						$ad_keyword = urldecode($check_url_array['n_keyword']);
					}else if ( !empty($check_url_array['NVKWD'])  ) {
						$ad_keyword = urldecode($check_url_array['NVKWD']);
					}else  {
						$ad_keyword = urldecode($check_url_array['n_query']);
					}

					$section = urldecode($check_url_array['NVAR']);
					$NVADID = urldecode($check_url_array['n_ad']);
					$NVADRANK = urldecode($check_url_array['n_rank']);
					$n_media = urldecode($check_url_array['n_media']);
					$n_ad_group = urldecode($check_url_array['n_ad_group']);
					$n_keyword_id = urldecode($check_url_array['n_keyword_id']);
				}
				// 다음광고
				else if( !empty($check_url_array['DMKW']) ){
					$m_type = 2;
					$m_detail = 21;
					$ad_keyword = urldecode($check_url_array['DMKW']);
					$section = urldecode($check_url_array['DMCOL']);		// PM=프리미엄링크, WIDELINK=와이드링크

				}else if( !empty($check_url_array['k_keyword']) ){
					$m_type = 2;
					$m_detail = 21;
					$ad_keyword = urldecode($check_url_array['k_keyword']);
					$section = urldecode($check_url_array['k_media']);
					$NVADRANK = urldecode($check_url_array['k_rank']);
					$n_ad_group = urldecode($check_url_array['k_adgroup']);
					$n_keyword_id = urldecode($check_url_array['k_keyword_id']);
				}
				// 구글 광고
				else if( !empty($check_url_array['gclid']) ){
					$m_type = 3;
					$m_detail = 22;

					$ad_keyword = urldecode($check_url_array['gclid']);
				}

			}else{
		
				//****************************************************************//
				//*** 레퍼러 체크 포함  ***//
				//****************************************************************//

				// 네이버
				if( strpos($ref_url_domain, "naver.com") !== false ){
					$m_type = 1;

					// 광고
					if( !empty($check_url_array['n_keyword']) || !empty($check_url_array['NVKWD']) || ( !empty($check_url_array['n_campaign']) &&  empty($check_url_array['n_keyword']) &&  !empty($check_url_array['n_query']) ) ){

						if( !empty($check_url_array['n_keyword']) ){
							$ad_keyword = urldecode($check_url_array['n_keyword']);
						}else if ( !empty($check_url_array['NVKWD'])  ) {
							$ad_keyword = urldecode($check_url_array['NVKWD']);
						}else  {
							$ad_keyword = urldecode($check_url_array['n_query']);
						}

						$section = urldecode($check_url_array['NVAR']);
						$NVADID = urldecode($check_url_array['n_ad']);
						$NVADRANK = urldecode($check_url_array['n_rank']);
						$n_media = urldecode($check_url_array['n_media']);
						$n_ad_group = urldecode($check_url_array['n_ad_group']);
						$n_keyword_id = urldecode($check_url_array['n_keyword_id']);
					}
					// 검색
					else{
						$search_keyword = urldecode($check_ref_url_array['query']);
					}

					// 클릭초이스
					if( strpos($ref_url_domain, "search.naver.com") !== false ){
						if( $ad_keyword != "" ){
							$m_detail = 19;
						}else{
							$m_detail = 38;
						}
					}
					// 네이버 쇼핑
					else if( strpos($ref_url_domain, "shopping.naver.com") !== false ){
						$m_detail = 1;

						// 네이버 쇼핑박스
						if( strpos($ref_url_domain, "castbox.shopping.naver.com") !== false ){
							$m_detail = 4;
						}
					}
					// 네이버 스토어팜
					else if( strpos($ref_url_domain, "storefarm.naver.com") !== false ){
						$m_detail = 3;
					}
					// 네이버 지식IN
					else if( strpos($ref_url_domain, "kin.naver.com") !== false ){
						$m_detail = 5;
					}
					// 네이버 캐스트
					else if( strpos($ref_url_domain, "navercast.naver.com") !== false ){
						$m_detail = 6;
					}
					// 네이버 블로그
					else if( strpos($ref_url_domain, "blog.naver.com") !== false ){
						$m_detail = 7;
					}
					// 네이버 광고주센터
					else if( strpos($ref_url_domain, "saedu.naver.com") !== false ){
						$m_detail = 8;
					}
					// 네이버 카페
					else if( strpos($ref_url_domain, "cafe.naver.com") !== false ){
						$m_detail = 9;
					}
					// 네이버 네이버페이
					else if( strpos($ref_url_domain, "pay.naver.com") !== false ){
						$m_detail = 10;
					}
					// 네이버 톡톡
					else if( strpos($ref_url_domain, "talk.naver.com") !== false ){
						$m_detail = 11;
					}
					// 네이버 지도
					else if( strpos($ref_url_domain, "map.naver.com") !== false ){
						$m_detail = 12;
					}
					// 네이버 메일
					else if( strpos($ref_url_domain, "mail.naver.com") !== false ){
						$m_detail = 13;
					}
					// 스마트스토어 유입시 광고 키워드 처리 
					if(!empty($check_ref_url_array['query']) and strpos($url_onlydomain,"smartstore.naver.com")!==false){
						$search_keyword = '';
						$ad_keyword = $check_ref_url_array['query'];
					}
				}
				// 다음, 네이트
				else if( strpos($ref_url_domain, "daum.net") !== false ){
					$nate_check = substr($ref_url_domain,-4);
					// 네이트
					if( $nate_check  == "nate" ){
						$m_type = 6;
					}
					// 다음
					else{
						$m_type = 2;
					}
					// 광고
					if( !empty($check_url_array['DMKW']) ){
						$ad_keyword = urldecode($check_url_array['DMKW']);
						$section = urldecode($check_url_array['DMCOL']);		// PM=프리미엄링크, WIDELINK=와이드링크
					}else if( !empty($check_url_array['k_keyword']) ){
						$ad_keyword = urldecode($check_url_array['k_keyword']);
						$section = urldecode($check_url_array['k_media']);
						$NVADRANK = urldecode($check_url_array['k_rank']);
						$n_ad_group = urldecode($check_url_array['k_adgroup']);
						$n_keyword_id = urldecode($check_url_array['k_keyword_id']);
					}
					// 검색
					else{
						$search_keyword = urldecode($check_ref_url_array['q']);
					}


					// 다음 검색광고
					if( strpos($ref_url_domain, "search.daum.net") !== false ){
						if( $ad_keyword != "" ) $m_detail = 21;
					}
					// 다음 쇼핑하우
					else if( strpos($ref_url_domain, "shopping.daum.net") !== false ){
						$m_detail = 2;
					}
					// 다음 카페
					else if( strpos($ref_url_domain, "cafe.daum.net") !== false ){
						$m_detail = 14;
					}
					// 다음 블로그
					else if( strpos($ref_url_domain, "blog.daum.net") !== false ){
						$m_detail = 15;
					}
					// 다음 메일
					else if( strpos($ref_url_domain, "mail.daum.net") !== false ){
						$m_detail = 16;
					}
					// 다음 지도
					else if( strpos($ref_url_domain, "map.daum.net") !== false ){
						$m_detail = 17;
					}
					// 다음 미디어
					else if( strpos($ref_url_domain, "media.daum.net") !== false ){
						$m_detail = 18;
					}

				}
				// 줌
				else if( strpos($ref_url_domain, "zum.com") !== false ){
					$m_type = 4;
					// 광고
					if( !empty($check_url_array['n_keyword']) || !empty($check_url_array['NVKWD']) || ( !empty($check_url_array['n_campaign']) &&  empty($check_url_array['n_keyword']) &&  !empty($check_url_array['n_query']) ) ){

						if( !empty($check_url_array['n_keyword']) ){
							$ad_keyword = urldecode($check_url_array['n_keyword']);
						}else if ( !empty($check_url_array['NVKWD'])  ) {
							$ad_keyword = urldecode($check_url_array['NVKWD']);
						}else  {
							$ad_keyword = urldecode($check_url_array['n_query']);
						}

						$section = urldecode($check_url_array['NVAR']);
						$NVADID = urldecode($check_url_array['n_ad']);
						$NVADRANK = urldecode($check_url_array['n_rank']);
						$n_media = urldecode($check_url_array['n_media']);
						$n_ad_group = urldecode($check_url_array['n_ad_group']);
						$n_keyword_id = urldecode($check_url_array['n_keyword_id']);
						$m_detail = 23;
					}
					// 검색
					else{
						$search_keyword = urldecode($check_ref_url_array['query']);
					}
					// 스마트스토어 유입시 광고 키워드 처리
					if(!empty($check_ref_url_array['query']) and strpos($url_onlydomain,"smartstore.naver.com")!==false){
						$search_keyword = '';
						$ad_keyword = $check_ref_url_array['query'];
					}
				}
				// bing
				else if( strpos($ref_url_domain, "bing.com") !== false ){
					$m_type = 5;
					
					// 광고
					if( !empty($check_url_array['DMCOL']) ){
						$ad_keyword = urldecode($check_url_array['DMKW']);
						$section = urldecode($check_url_array['DMCOL']);		// PM=프리미엄링크, WIDELINK=와이드링크
						$m_detail = 24;
					}else if( !empty($check_url_array['k_media']) ){
						$m_detail = 24;
						$ad_keyword = urldecode($check_url_array['k_keyword']);
						$section = urldecode($check_url_array['k_media']);
						$NVADRANK = urldecode($check_url_array['k_rank']);
						$n_ad_group = urldecode($check_url_array['k_adgroup']);
						$n_keyword_id = urldecode($check_url_array['k_keyword_id']);
					}
					// 검색
					else{
						$search_keyword = urldecode($check_ref_url_array['q']);
					}
					// 스마트스토어 유입시 광고 키워드 처리 
					if(!empty($check_ref_url_array['q']) and strpos($url_onlydomain,"smartstore.naver.com")!==false){
						$search_keyword = '';
						$ad_keyword = $check_ref_url_array['q'];
					}
				}
				// 구글
				else if( strpos($ref_url_domain, "google.co.kr") !== false || strpos($ref_url_domain, "google.com") !== false ){
					$m_type = 3;
					
					// 광고
					if( !empty($check_url_array['gclid']) ){
						$ad_keyword = urldecode($check_url_array['gclid']);
						$m_detail = 22;
					}
					$search_keyword = urldecode($check_ref_url_array['q']);
				}
				// 11번가
				else if( strpos($ref_url_domain, "11st.co.kr") !== false ){
					$m_type = 7;
					$ad_keyword = urldecode($check_url_array['n_keyword']);
					$NVADID = urldecode($check_url_array['n_ad']);
					$NVADRANK = urldecode($check_url_array['n_rank']);
					$n_media = urldecode($check_url_array['n_media']);
					$n_ad_group = urldecode($check_url_array['n_ad_group']);
					$n_keyword_id = urldecode($check_url_array['n_keyword_id']);
				}
				// 인크루트
				else if( strpos($ref_url_domain, "incruit.com") !== false ){
					$m_type = 8;
				}
				// 옥션
				else if( strpos($ref_url_domain, "auction.co.kr") !== false ){
					$m_type = 9;
					$ad_keyword = urldecode($check_url_array['n_keyword']);
					$NVADID = urldecode($check_url_array['n_ad']);
					$NVADRANK = urldecode($check_url_array['n_rank']);
					$n_media = urldecode($check_url_array['n_media']);
					$n_ad_group = urldecode($check_url_array['n_ad_group']);
					$n_keyword_id = urldecode($check_url_array['n_keyword_id']);
				}
				// 뉴스
				else if( 
					strpos($ref_url_domain, "asiae.co.kr") !== false ||
					strpos($ref_url_domain, "heraldcorp.com") !== false || 
					strpos($ref_url_domain, "ebn.co.kr") !== false || 
					strpos($ref_url_domain, "nocutnews.co.kr") !== false || 
					strpos($ref_url_domain, "starseoultv.com") !== false || 
					strpos($ref_url_domain, "etoday.co.kr") !== false || 
					strpos($ref_url_domain, "seoul.co.kr") !== false || 
					strpos($ref_url_domain, "donga.com") !== false ||
					strpos($ref_url_domain, "etnews.com") !== false ||
					strpos($ref_url_domain, "xportsnews.com") !== false ||
					strpos($ref_url_domain, "joongboo.com") !== false || 
					strpos($ref_url_domain, "gjfnews.org") !== false || 
					strpos($ref_url_domain, "ytn.co.kr") !== false
				){
					$m_type = 21;
					
					// 아시아 경제
					if( strpos($ref_url_domain, "asiae.co.kr") !== false ){
						$m_detail = 25;
					}
					// 헤드럴경제
					else if( strpos($ref_url_domain, "heraldcorp.com") !== false ){
						$m_detail = 26;
					}
					// EBN
					else if( strpos($ref_url_domain, "ebn.co.kr") !== false ){
						$m_detail = 27;
					}
					// 노컷뉴스
					else if( strpos($ref_url_domain, "nocutnews.co.kr") !== false ){
						$m_detail = 28;
					}
					// 스타서울
					else if( strpos($ref_url_domain, "starseoultv.com") !== false ){
						$m_detail = 29;
					}
					// 이투데이
					else if( strpos($ref_url_domain, "etoday.co.kr") !== false ){
						$m_detail = 30;
					}
					// 서울신문
					else if( strpos($ref_url_domain, "seoul.co.kr") !== false ){
						$m_detail = 31;
					}
					// 동아일보
					else if( strpos($ref_url_domain, "donga.com") !== false ){
						$m_detail = 32;
					}
					// 전자신문
					else if( strpos($ref_url_domain, "etnews.com") !== false ){
						$m_detail = 33;
					}
					// 엑스포츠
					else if( strpos($ref_url_domain, "xportsnews.com") !== false ){
						$m_detail = 34;
					}
					// 중부일보
					else if( strpos($ref_url_domain, "joongboo.com") !== false ){
						$m_detail = 35;
					}
					// 국제언론인클럽
					else if( strpos($ref_url_domain, "gjfnews.org") !== false ){
						$m_detail = 36;
					}
					// YTN
					else if( strpos($ref_url_domain, "ytn.co.kr") !== false ){
						$m_detail = 37;
					}

				}
				// 페이스북
				else if( strpos($ref_url_domain, "facebook.com") !== false ){
					$m_type = 18;
				}
				// 지마켓
				else if( strpos($ref_url_domain, "gmarket.co.kr") !== false ){
					$m_type = 19;
					$ad_keyword = urldecode($check_url_array['n_keyword']);
					$NVADID = urldecode($check_url_array['n_ad']);
					$NVADRANK = urldecode($check_url_array['n_rank']);
					$n_media = urldecode($check_url_array['n_media']);
					$n_ad_group = urldecode($check_url_array['n_ad_group']);
					$n_keyword_id = urldecode($check_url_array['n_keyword_id']);
				}
				// kakao
				else if( strpos($ref_url_domain, "kakao.com") !== false ){
					$m_type = 20;
				}else if(strpos($ref_url_domain, "instagram.com") !== false){
					$m_type = 22;
				}else if(strpos($ref_url_domain, "youtube.com") !== false){
					$m_type = 23;
				}else if(strpos($ref_url_domain, "zigzag.kr") !== false){
					$m_type = 24;
				}
				// 이외 검색엔진
				else if( strpos($ref_url_puny, $url_only_domain) === false && $ref_url != "bookmark" && $ref_url != ""){
					if( $ref_url_domain != ""){
						$m_type = 99;	
					}else{
						$m_type = 0;	
					}
				}

				if( strpos(substr($ref_url,0,8), "bookmark") !== false ){
					$m_type = 0;
				}
				if( strpos(substr($ref_url,0,8), "refnoget") !== false ){
					$m_type = 0;
				}
				if( preg_replace("/\s+/", "", $ref_url) == "" ){
					$m_type = 0;
				}

				if( strpos(substr($ref_url,0,9), "undefined") !== false ){
					$m_type = 0;
					$ref_url  = "bookmark";
				}
			}
		}



		// 이전 키워드 체크
		if( !empty($check_ref_url_array['oquery']) ){
			$keyword_before = urldecode($check_ref_url_array['oquery']);
		}

		if ( $keyword_before == '' && !empty($check_ref_url_array['prevQuery']) ){
			$keyword_before = urldecode($check_ref_url_array['prevQuery']);
		}

		if( strpos(substr($ref_domain,-1), "_") !== false ){
			$ref_domain = substr($ref_domain , 0, -1);
		}

		// 레퍼도메인 네이버앱 별도 체크
		if( strpos(substr($ref_url,0,14), "naversearchapp") !== false ){
			$ref_domain = 'naversearchapp://main';
		}

		//  전환값 체크
		if($c_type != "" && $c_type != "undefined"){
			$c_type = $c_type;
			$c_val = $c_val;
		}else{
			$c_type = "";
			$c_val = 0;
		}
		
		$mon = substr($today, 0, 4).substr($today, 5, 2); 
		$dat = substr($today, 0, 10);
		$todayyyymd = substr($today, 0, 4).substr($today, 5, 2).substr($today, 8, 2); 
		$todayyyymmww = getWeekInfo($todayyyymd);
		$todayyyymdh = substr($today, 0, 4).substr($today, 5, 2).substr($today, 8, 2).substr($today, 11, 2);
		$todayyyy = substr($today, 0, 4);
		$todayq   = substr($today, 5, 2);
		$todayq = ceil( (int)$todayq / 3);
		

		//키워드구성
		$_keyword_type = "";
		$_keyword = "";
		if($ad_keyword != ""){
			$_keyword_type = "advertising";
			$_keyword =$ad_keyword;
		}else if($search_keyword != ""){
			$_keyword_type = "default";
			$_keyword = $search_keyword;
		}

		// PC / 모바일 구분 (0: PC, 1: 모바일)
		if( $agent == 'P' ){
			$dataAgent = '0';
		}else{
			$dataAgent = '1';
		}

		$log_data[0] = array(
			$adkey,
			$visit_id,
			$first_check,
			$vc_second,
			$first_date,
			$url_full,
			$url_onlydomain,
			$url_param,
			$url_path,
			$url_page,
			$ref_url_full,
			$ref_url_onlydomain,
			$ref_url_param,
			$ref_url_path,
			$ref_url_page,
			$m_type,
			$m_detail,
			$_keyword,
			$_keyword_type,
			$c_type,
			$c_val,
			$os,
			$dataAgent,
			$browser,
			$ip,
			$todayyyy,
			$todayq,
			$mon,
			$todayyyymmww,
			$todayyyymd,
			$todayyyymdh,
			$today,
			$NVADRANK,
			$section,
			$n_media,
			$n_ad_group,
			$NVADID,
			$n_keyword_id,
			$n_campaign_type ,
			$keyword_before
		);

		// 유입했을때의 데이터를 갖고 있다면 교체해준다.
		if($inflow != "false"){
			$m_type			= $inflow["m_type"];
			$m_detail		= $inflow["m_detail"];
			$_keyword		= $inflow["keyword"];
			$_keyword_type	= $inflow["keyword_type"];
		}

		// 유입했을때의 데이터를 갖고 있다면 교체해준다.
		//if($inflow != "false"){
			////처음 유입된 타입이 광고시
			//if ( $inflow["keyword_type"] == 'advertising' ){
				//if ( $_keyword_type == 'advertising' ) {
				//}else if ( $_keyword_type == 'default' ) {
					//$m_type			= $inflow["m_type"];
					//$m_detail		= $inflow["m_detail"];
					//$_keyword		= $inflow["keyword"];
					//$_keyword_type	= $_keyword_type;
				//}
			//}
		//}

		$log_data[1] = array(
			'adkey'=>$adkey,
			'visit_id'=>$visit_id,
			'first_check'=>$first_check,
			'vc_second'=>$vc_second,
			'first_date',$first_date,
			'url_full'=>$url_full,
			'url_onlydomain'=>$url_onlydomain,
			'url_param'=>$url_param,
			'url_path'=>$url_path,
			'url_page'=>$url_page,
			'ref_url_pull'=>'',
			'ref_url_onlydomain'=>$ref_url_onlydomain,
			'ref_url_param'=>$ref_url_param,
			'ref_url_path'=>$ref_url_path,
			'ref_url_page'=>$ref_url_page,
			'm_type'=>$m_type,
			'm_detail'=>$m_detail,
			'_keyword'=>$_keyword,
			'_keyword_type'=>$_keyword_type,
			'c_type'=>$c_type,
			'c_val'=>$c_val,
			'os'=>$os,
			'dataAgent'=>$dataAgent,
			'browser'=>$browser,
			'ip'=>$ip,
			'todayyyy'=>$todayyyy,
			'todayq'=>$todayq,
			'mon'=>$mon,
			'todayyyymmww'=>$todayyyymmww,
			'todayyyymd'=>$todayyyymd,
			'todayyyymdh'=>$todayyyymdh,
			'today'=>$today,
			'NVADRANK'=>$NVADRANK,
			'section'=>$section,
			'n_media'=>$n_media,
			'n_ad_group'=>$n_ad_group,
			'NVADID'=>$NVADID,
			'n_keyword_id'=>$n_keyword_id,
			'n_campaign_type'=>$n_campaign_type
		);
		return $log_data;
	}


	function iconv_utf8($str){
		return iconv("euc-kr", "utf-8", $str);
	}

	// 개행문자 , 탭 제거
	function removeCRLF($data) {
		$str = preg_replace('/\r\n|\r|\n|\t/','',$data);
		return $str;
	}

	function redisPostSend($data){
		$url = "http://log1.toup.net/rediscall.php";
		$ch = curl_init();                                 //curl 초기화
		curl_setopt($ch, CURLOPT_URL, $url);               //URL 지정하기
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);    //요청 결과를 문자열로 반환 
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);      //connection timeout 10초 
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);   //원격 서버의 인증서가 유효한지 검사 안함
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));       //POST data
		curl_setopt($ch, CURLOPT_POST, true);              //true시 post 전송 
		$response = curl_exec($ch);
		curl_close($ch);
		return $response;
	}

	//월 주차 구하기
    function getWeekInfo($_date)
	{
		$today_num = date('w',strtotime($_date));
        if($today_num == 0) $today_num = "7";
        return date('Ymd',strtotime('-'.($today_num - 1).'days',strtotime($_date)));
	}

	function getParam(){
		$str = "";
		if ( count($_REQUEST) ){
			foreach ( $_REQUEST as $k=>$v){
				if ( !in_array( strtolower($k) , array ( 'adid' , 'adkey' , 'n_final_url') ) ){
					if ( $str != '' ) $str .="&";
					//$str .= $k . "=" . $v;
					$str .= $k . "=" . urlencode($v);
				}
			}
		}
		return $str;
	}

	function getDevice(){
		$mobile_str = 'iphone|ipod|android|windows ce|blackberry|symbian|windows phone|webos|opera mini|opera mobi|polaris|iemobile|lgtelecom|nokia|sonyericsson|lg|samsung|samsung';
		$str = 'P';
		if(preg_match('/'.$mobile_str.'/i', $_SERVER['HTTP_USER_AGENT'])){
			$str = 'M';
		}
		return $str;
	}

	function getAgent(){
		$userAgent = $_SERVER['HTTP_USER_AGENT'];
		$str = "";
		$rv = '';

		// Trident\
		$imgPattern = "/trident\/(.*?); rv/si";
		preg_match_all($imgPattern,$userAgent,$match, PREG_SET_ORDER);

		if ( count($match) ){
			$ie_v = $match[0][1];
			if ( $ie_v == '7.0' ) $str = "IE11";
			if ( $ie_v == '6.0' ) $str = "IE10";
			if ( $ie_v == '5.0' ) $str = "IE9";
			if ( $ie_v == '4.0' ) $str = "IE8";
		}else{

			if (		preg_match('/whale/i',	$userAgent ) ) $str = "Whale";
			else if (	preg_match('/edg/i', $userAgent ) ) $str = "Edge";
			else if (	preg_match('/opera|opr/i', $userAgent ) ) $str = "Opera";
			else if (	preg_match('/chrome/i', $userAgent ) ) $str = "Chrome";
			else if (	preg_match('/staroffice/i', $userAgent ) ) $str = "Star Office";
			else if (	preg_match('/webtv/i', $userAgent ) ) $str = "WebTV";
			else if (	preg_match('/beonex/i', $userAgent ) ) $str = "Beonex";
			else if (	preg_match('/chimera/i', $userAgent ) ) $str = "Chimera";
			else if (	preg_match('/netpositive/i', $userAgent ) ) $str = "NetPositive";
			else if (	preg_match('/phoenix/i', $userAgent ) ) $str = "Phoenix";
			else if (	preg_match('/firefox/i', $userAgent ) ) $str = "Firefox";
			else if (	preg_match('/safari/i', $userAgent ) ) $str = "Safari";
			else if (	preg_match('/skipstone/i', $userAgent ) ) $str = "SkipStone";
			else if (	preg_match('/netscape/i', $userAgent ) ) $str = "Netscape";
			else if (	preg_match('/naver(inapp/i', $userAgent ) ) $str = "NAVER App";
			else if (	preg_match('/mozilla\/5.0/i', $userAgent ) ) $str = "Mozilla5.0";
			else if (	preg_match('/mozilla\/4.0/i', $userAgent ) ) $str = "Mozilla4.0";
			else $str = "Unknown";

		}

		return $str;
	}

	function getOs(){
		$userAgent = $_SERVER['HTTP_USER_AGENT'];
		$str = "";
		if ( preg_match('/linux/i', $userAgent ) ) {
			if (		preg_match('/android/i',	$userAgent ) ) $str = "Android";
			else if (	preg_match('/blackberry9000/i', $userAgent ) ) $str = "BlackBerry9000";
			else if (	preg_match('/blackberry9300/i', $userAgent ) ) $str = "BlackBerry9300";
			else if (	preg_match('/blackberry9700/i', $userAgent ) ) $str = "BlackBerry9700";
			else if (	preg_match('/blackberry9780/i', $userAgent ) ) $str = "BlackBerry9780";
			else if (	preg_match('/blackberry9900/i', $userAgent ) ) $str = "BlackBerry9900";
			else if (	preg_match('/blackberry;opera mini/i', $userAgent ) ) $str = "Opera/9.80";    
			else if (	preg_match('/symbian\/3/i', $userAgent ) ) $str = "Symbian OS3";   
			else if (	preg_match('/symbianos\/6/i', $userAgent ) ) $str = "Symbian OS6";   
			else if (	preg_match('/symbianos\/9/i', $userAgent ) ) $str = "Symbian OS9";   
			else if (	preg_match('/ubuntu/i', $userAgent ) ) $str = "Ubuntu";        
			else if (	preg_match('/pda/i', $userAgent ) ) $str = "PDA";           
			else if (	preg_match('/nintendowii/i', $userAgent ) ) $str = "Nintendo Wii";  
			else if (	preg_match('/psp/i', $userAgent ) ) $str = "PlayStation";   
			else if (	preg_match('/playstation/i', $userAgent ) ) $str = "PlayStation";   
			else $str = "Linux (Unknown)";

		} else if ( preg_match('/unix/i', $userAgent ) ) {
			$str  = "UNIX";

		} else if ( preg_match('/macintosh|mac os x/i', $userAgent ) ) {
			if (		preg_match('/iphone/i', $userAgent ) ) $str = "IOS";
			else if (	preg_match('/macos|macintosh/i', $userAgent ) ) $str = "Mac";

		} else if ( preg_match('/windows|win32/i', $userAgent ) ) {
			if (		preg_match('/nt 5.0/i', $userAgent ) || preg_match('/nt 5.0/i', $userAgent )  ) $str = "Windows 2000";
			else if (	preg_match('/nt 5.1/i', $userAgent ) || preg_match('/nt 5.0/i', $userAgent )  ) $str = "Windows XP";
			else if (	preg_match('/nt 5.2/i', $userAgent ) ) $str = "Windows Server 2003";
			else if (	preg_match('/nt 6.0/i', $userAgent ) ) $str = "Windows Vista";
			else if (	preg_match('/nt 6.1/i', $userAgent ) ) $str = "Windows 7";
			else if (	preg_match('/nt 6.2/i', $userAgent ) || preg_match('/wow64/i', $userAgent )  ) $str = "Windows 8";
			else if (	preg_match('/win98/i', $userAgent )  || preg_match('/windows 98/i', $userAgent ) ) $str = "Windows 98";
			else if (	preg_match('/win95/i', $userAgent ) || preg_match('/windows 95/i', $userAgent )  ) $str = "Windows 95";
			else if (	preg_match('/linux/i', $userAgent ) ) $str = "Linux";
			else if (	preg_match('/mac/i', $userAgent ) ) $str = "Mac";
			else if (	preg_match('/nt 10/i', $userAgent ) || preg_match('/windows 10/i', $userAgent )  ) $str = "Window 10";
			else if (	preg_match('/nt 11/i', $userAgent ) || preg_match('/windows 11/i', $userAgent )  ) $str = "Window 11";
			else $str = "unknow";
		} else {
			$str = "Unknown OS";
		}

		return $str;
	}

	function getSmartStoreVisitId($mi_ip , $adkey){
		$logtime2	= date('d H:i:s');
		$new_vi		= "";
		$cookie_name = $adkey ."_mi_log_vi";

		if ( isset($_COOKIE[$cookie_name]) && $_COOKIE[$cookie_name] != "" ) {
			$new_vi = $_COOKIE[$cookie_name];
		}else{
			$deckey =	"mirae#inc123";
			$new_vis = decrypt($mi_ip.$logtime2,$deckey); 
			setcookie($cookie_name , $new_vis , time() + ( 60 * 60 * 24 * 365 ));
		}
		return $new_vi;
	}

	function setSmartStoreLogFile($data){
		global $log_path;
		$log_file = substr(date('YmdHi'),0,11)."0";
		$l4_ip	 = $_SERVER['SERVER_ADDR'];
		$file_name	= $log_path. "{$l4_ip}_lending_{$log_file}.txt";
		
		$data	= array_map("removeCRLF",$data);
		$data	= implode("\t" , $data ) . "\n";
		$log_fp		= @fopen($file_name,"a+");
		@fwrite($log_fp,$data);
		@fclose($log_fp);
	}

	function setLog_InflowSite(){
		global $regdate_time;
		global $adkey;
		global $this_page;
		global $referer;
		global $ip;
		$log_datas = array();
		$log_datas[] = '[ call_history ]';
		$log_datas[] = "[ $ip ]";
		$log_datas[] = $regdate_time;
		$log_datas[] = $adkey;
		$log_datas[] = $this_page;
		$log_datas[] = $referer;
		setSmartStoreLogFile($log_datas );
	}

	function setLog_SendResponse(){
		global $regdate_time;
		global $adkey;
		global $log_url;
		global $ip;
		$log_datas = array();
		$log_datas[] = '[ call_logurl  ]';
		$log_datas[] = "[ $ip ]";
		$log_datas[] = $regdate_time;
		$log_datas[] = $adkey;
		$log_datas[] = $log_url;
		setSmartStoreLogFile( $log_datas );
	}

	function setLog_DbInsert(){
		global $regdate_time;
		global $adid;             
		global $adkey;            
		global $n_final_url;      
		global $n_campaign;       
		global $n_ad_group;       
		global $n_media;          
		global $n_ad;             
		global $n_ad_extension;   
		global $n_keyword;        
		global $n_keyword_id;     
		global $n_query;          
		global $n_match;          
		global $n_network;        
		global $n_rank;           
		global $n_campaign_type	; 
		global $n_mall_id;        
		global $n_mall_pid;       
		global $n_contract;       
		global $n_ad_group_type;  
		global $referer;          
		global $query_string;     
		global $ip;               
		global $location_url;     
		global $s_date;           
		global $regdate;          

		$log_datas = array();
		$log_datas[] = '[ call_info    ]';
		$log_datas[] = "[ $ip ]";
		$log_datas[] = $regdate_time;
		$log_datas[] = $adkey;
		$log_datas[] = $adid;
		$log_datas[] = $adkey;
		$log_datas[] = $n_final_url;
		$log_datas[] = $n_campaign;
		$log_datas[] = $n_ad_group;
		$log_datas[] = $n_media;
		$log_datas[] = $n_ad;
		$log_datas[] = $n_ad_extension;
		$log_datas[] = $n_keyword;
		$log_datas[] = $n_keyword_id;
		$log_datas[] = $n_query;
		$log_datas[] = $n_match;
		$log_datas[] = $n_network;
		$log_datas[] = $n_rank;
		$log_datas[] = $n_campaign_type	;
		$log_datas[] = $n_mall_id;
		$log_datas[] = $n_mall_pid;
		$log_datas[] = $n_contract;
		$log_datas[] = $n_ad_group_type;
		$log_datas[] = $referer;
		$log_datas[] = $query_string;
		$log_datas[] = $ip;
		$log_datas[] = $location_url;
		$log_datas[] = $s_date;
		$log_datas[] = $regdate;
		setSmartStoreLogFile($log_datas );
	}

	function checkFinalUrl($url){
		$pattern[] = "!brand.naver.com/(.*?)!si";
		$pattern[] = "!smartstore.naver.com/(.*?)!si";
		$pattern[] = "!blog.naver.com/(.*?)!si";
		$pattern[] = "!(.*?).modoo.at!si";
		$pattern[] = "!cafe.naver.com/(.*?)!si";

		$cnt = 0;
		foreach($pattern as $val){
			preg_match($val ,$url ,$arr_result);
			if ( count($arr_result) ) $cnt++;
		}
		return $cnt ? true : false;
	}

?>